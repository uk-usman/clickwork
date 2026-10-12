<?php

declare(strict_types=1);

namespace App\Core;

/**
 * HTTP response helpers.
 */
final class Response
{
    /**
     * @param array<string,mixed> $data
     */
    public static function json(array $data, int $status = 200): never
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * Consistent JSON envelope for AJAX endpoints.
     *
     * @param array<string,mixed>|null $data
     * @param array<string,string>    $errors
     */
    public static function jsonEnvelope(
        bool $ok,
        ?array $data = null,
        string $message = '',
        array $errors = [],
        int $status = 200
    ): never {
        self::json([
            'ok'      => $ok,
            'message' => $message,
            'data'    => $data,
            'errors'  => $errors,
        ], $status);
    }

    public static function redirect(string $url, int $status = 302): never
    {
        header('Location: ' . self::safeUrl($url), true, $status);
        exit;
    }

    /**
     * Only allow redirects to our own application (prevents open redirects).
     */
    public static function safeUrl(string $url): string
    {
        if (preg_match('#^(https?:)?//#i', $url) === 1 || str_starts_with($url, '\\')) {
            return url('/');
        }

        return $url;
    }

    public static function status(int $code): void
    {
        http_response_code($code);
    }
}
