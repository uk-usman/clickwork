<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\App;
use App\Core\Request;
use App\Core\Response;

/**
 * Serves user-uploaded images (avatars, portfolio images) from private storage.
 *
 * Files live outside the public web root so they can never be executed. This
 * endpoint validates the path, restricts it to the uploads directory and sends
 * a safe content type.
 */
final class MediaController extends Controller
{
    public function avatar(Request $request): void
    {
        $this->serve((string) ($request->route('path') ?? ''));
    }

    public function portfolio(Request $request): void
    {
        $this->serve((string) ($request->route('path') ?? ''));
    }

    private function serve(string $storedName): void
    {
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

        // Reject traversal and absolute paths before touching the filesystem.
        if ($storedName === '' || str_contains($storedName, '..') || str_starts_with($storedName, '/')) {
            Response::status(404);
            echo 'Not found.';
            return;
        }

        $extension = strtolower((string) pathinfo($storedName, PATHINFO_EXTENSION));

        if (!in_array($extension, $allowed, true)) {
            Response::status(404);
            echo 'Not found.';
            return;
        }

        $base = realpath((string) App::configValue('storage.uploads', ''));
        $path = $base !== false ? realpath($base . '/' . $storedName) : false;

        if ($base === false || $path === false || !str_starts_with($path, $base . DIRECTORY_SEPARATOR) || !is_file($path)) {
            Response::status(404);
            echo 'Not found.';
            return;
        }

        $mime = (string) (new \finfo(FILEINFO_MIME_TYPE))->file($path);
        $safeMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

        if (!in_array($mime, $safeMimes, true)) {
            Response::status(404);
            echo 'Not found.';
            return;
        }

        header('Content-Type: ' . $mime);
        header('Content-Length: ' . (string) filesize($path));
        header('X-Content-Type-Options: nosniff');
        header('Content-Disposition: inline; filename="' . basename($path) . '"');
        header('Cache-Control: public, max-age=86400');

        readfile($path);
    }
}
