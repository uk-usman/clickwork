<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\App;
use App\Core\Logger;

/**
 * Secure file upload handling.
 *
 * - validates real content via finfo, not just the client-supplied type
 * - validates the extension against an allow-list
 * - enforces a size limit
 * - generates a random server-side filename (never trusts the client name)
 * - stores files outside the public web root
 */
final class UploadService
{
    /** Allowed image extensions for avatars and portfolio images. */
    public const IMAGE_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

    /** Allowed document extensions for deliverables and payment evidence. */
    public const DOCUMENT_EXTENSIONS = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'csv', 'zip', 'png', 'jpg', 'jpeg', 'gif', 'webp'];

    /**
     * @var array<string,array<int,string>>
     */
    private const MIME_MAP = [
        'jpg'  => ['image/jpeg'],
        'jpeg' => ['image/jpeg'],
        'png'  => ['image/png'],
        'gif'  => ['image/gif'],
        'webp' => ['image/webp'],
        'pdf'  => ['application/pdf'],
        'txt'  => ['text/plain'],
        'csv'  => ['text/plain', 'text/csv', 'application/csv'],
        'zip'  => ['application/zip', 'application/x-zip-compressed'],
        'doc'  => ['application/msword'],
        'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/zip'],
        'xls'  => ['application/vnd.ms-excel'],
        'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/zip'],
        'ppt'  => ['application/vnd.ms-powerpoint'],
        'pptx' => ['application/vnd.openxmlformats-officedocument.presentationml.presentation', 'application/zip'],
    ];

    /**
     * Store an uploaded file and return metadata.
     *
     * @param  array<string,mixed> $file    $_FILES entry
     * @param  array<int,string>   $allowedExtensions
     * @return array{ok:bool,error?:string,data?:array<string,mixed>}
     */
    public static function store(array $file, string $directory, array $allowedExtensions, ?int $maxBytes = null): array
    {
        $maxBytes ??= (int) App::configValue('security.upload_max_bytes', 5 * 1024 * 1024);

        $error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);

        if ($error !== UPLOAD_ERR_OK) {
            return ['ok' => false, 'error' => self::uploadErrorMessage($error)];
        }

        $tmpPath = (string) ($file['tmp_name'] ?? '');

        if ($tmpPath === '' || !is_uploaded_file($tmpPath)) {
            Logger::security('Rejected upload: not an uploaded file', ['tmp' => $tmpPath]);
            return ['ok' => false, 'error' => 'The uploaded file could not be verified.'];
        }

        $size = (int) ($file['size'] ?? 0);
        if ($size <= 0) {
            return ['ok' => false, 'error' => 'The uploaded file is empty.'];
        }
        if ($size > $maxBytes) {
            return ['ok' => false, 'error' => 'The file is larger than the allowed limit of ' . self::humanSize($maxBytes) . '.'];
        }

        $originalName = (string) ($file['name'] ?? 'upload');
        // Strip any directory components (path traversal defence).
        $originalName = str_replace(["\\", '/'], '', basename($originalName));
        $originalName = preg_replace('/[\x00-\x1F\x7F]/', '', $originalName) ?? 'upload';
        $originalName = mb_substr($originalName, 0, 200);

        $extension = strtolower((string) pathinfo($originalName, PATHINFO_EXTENSION));

        $extensionError = self::checkExtension($extension, $allowedExtensions);

        if ($extensionError !== null) {
            return ['ok' => false, 'error' => $extensionError];
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $detectedMime = (string) $finfo->file($tmpPath);

        $expectedMimes = self::MIME_MAP[$extension] ?? [];
        if ($expectedMimes !== [] && !in_array($detectedMime, $expectedMimes, true)) {
            Logger::security('Rejected upload: MIME mismatch', [
                'extension' => $extension,
                'detected'  => $detectedMime,
            ]);
            return ['ok' => false, 'error' => 'The file content does not match its extension. Please upload a genuine ' . strtoupper($extension) . ' file.'];
        }

        // For images, additionally verify the content is a real image.
        if (in_array($extension, self::IMAGE_EXTENSIONS, true) && @getimagesize($tmpPath) === false) {
            return ['ok' => false, 'error' => 'The uploaded image file appears to be corrupted.'];
        }

        $directory = rtrim($directory, '/');
        if (!is_dir($directory) && !@mkdir($directory, 0775, true) && !is_dir($directory)) {
            Logger::error('Unable to create upload directory', ['directory' => $directory]);
            return ['ok' => false, 'error' => 'The server could not store the file. Please try again later.'];
        }

        $storedName = date('Y/m') . '/' . bin2hex(random_bytes(20)) . '.' . $extension;
        $targetDir = $directory . '/' . dirname($storedName);

        if (!is_dir($targetDir) && !@mkdir($targetDir, 0775, true) && !is_dir($targetDir)) {
            return ['ok' => false, 'error' => 'The server could not store the file. Please try again later.'];
        }

        $targetPath = $directory . '/' . $storedName;

        if (!@move_uploaded_file($tmpPath, $targetPath)) {
            Logger::error('move_uploaded_file failed', ['target' => $targetPath]);
            return ['ok' => false, 'error' => 'The file could not be saved. Please try again.'];
        }

        @chmod($targetPath, 0640);

        return [
            'ok'   => true,
            'data' => [
                'original_name'   => $originalName,
                'stored_name'     => $storedName,
                'absolute_path'   => $targetPath,
                'mime_type'       => $detectedMime,
                'size_bytes'      => $size,
                'checksum_sha256' => hash_file('sha256', $targetPath) ?: '',
            ],
        ];
    }

    /**
     * Delete a previously stored private file (never follows symlinks outside the base).
     */
    public static function delete(string $baseDirectory, string $storedName): bool
    {
        $base = realpath($baseDirectory);
        if ($base === false) {
            return false;
        }

        $path = realpath($base . '/' . ltrim($storedName, '/'));
        if ($path === false || !str_starts_with($path, $base . DIRECTORY_SEPARATOR)) {
            Logger::security('Blocked unsafe file deletion attempt', ['stored' => $storedName]);
            return false;
        }

        return @unlink($path);
    }

    /**
     * Extensions that must never be accepted, regardless of the allow-list.
     *
     * @var array<int,string>
     */
    public const FORBIDDEN_EXTENSIONS = [
        'php', 'phtml', 'php3', 'php4', 'php5', 'php7', 'php8', 'phar', 'pl', 'py', 'sh',
        'cgi', 'asp', 'aspx', 'js', 'mjs', 'html', 'htm', 'svg', 'exe', 'bat', 'cmd', 'com',
        'htaccess', 'htpasswd',
    ];

    /**
     * Validate a filename extension against the allow-list and the forbidden list.
     *
     * Returns an error message, or null when the extension is acceptable.
     *
     * @param array<int,string> $allowedExtensions
     */
    public static function checkExtension(string $extension, array $allowedExtensions): ?string
    {
        $extension = strtolower(ltrim($extension, '.'));
        $allowedExtensions = array_map(static fn (string $item): string => strtolower(ltrim($item, '.')), $allowedExtensions);

        if ($extension === '') {
            return 'The file must have a recognised extension.';
        }

        if (in_array($extension, self::FORBIDDEN_EXTENSIONS, true)) {
            Logger::security('Rejected upload with forbidden extension', ['extension' => $extension]);

            return 'This file type is not permitted for security reasons.';
        }

        if (!in_array($extension, $allowedExtensions, true)) {
            return 'This file type is not allowed. Permitted types: ' . implode(', ', $allowedExtensions) . '.';
        }

        return null;
    }

    public static function humanSize(int $bytes): string
    {
        if ($bytes >= 1048576) {
            return round($bytes / 1048576, 1) . ' MB';
        }
        if ($bytes >= 1024) {
            return round($bytes / 1024) . ' KB';
        }

        return $bytes . ' bytes';
    }

    private static function uploadErrorMessage(int $code): string
    {
        return match ($code) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'The file is too large to upload.',
            UPLOAD_ERR_PARTIAL                       => 'The file was only partially uploaded. Please try again.',
            UPLOAD_ERR_NO_FILE                       => 'No file was selected.',
            UPLOAD_ERR_NO_TMP_DIR                    => 'The server has no temporary folder configured.',
            UPLOAD_ERR_CANT_WRITE                    => 'The server could not write the file to disk.',
            UPLOAD_ERR_EXTENSION                     => 'The upload was stopped by a server extension.',
            default                                  => 'The file could not be uploaded.',
        };
    }
}
