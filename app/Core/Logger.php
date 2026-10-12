<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Lightweight file logger. Detailed errors go to protected server logs,
 * never to the browser.
 */
final class Logger
{
    private static ?string $path = null;

    public static function setPath(string $path): void
    {
        self::$path = rtrim($path, '/');
        if (!is_dir(self::$path)) {
            @mkdir(self::$path, 0775, true);
        }
    }

    /**
     * @param array<string,mixed> $context
     */
    public static function log(string $level, string $message, array $context = []): void
    {
        $line = sprintf(
            "[%s] %s: %s%s%s",
            gmdate('Y-m-d H:i:s') . ' UTC',
            strtoupper($level),
            $message,
            $context === [] ? '' : ' ' . json_encode($context, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            PHP_EOL
        );

        $dir = self::$path ?? (defined('CIRCLEWORK_ROOT') ? CIRCLEWORK_ROOT . '/storage/logs' : sys_get_temp_dir());

        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }

        $file = $dir . '/app-' . gmdate('Y-m-d') . '.log';

        @file_put_contents($file, $line, FILE_APPEND | LOCK_EX);
    }

    /**
     * @param array<string,mixed> $context
     */
    public static function error(string $message, array $context = []): void
    {
        self::log('error', $message, $context);
    }

    /**
     * @param array<string,mixed> $context
     */
    public static function warning(string $message, array $context = []): void
    {
        self::log('warning', $message, $context);
    }

    /**
     * @param array<string,mixed> $context
     */
    public static function info(string $message, array $context = []): void
    {
        self::log('info', $message, $context);
    }

    /**
     * @param array<string,mixed> $context
     */
    public static function security(string $message, array $context = []): void
    {
        self::log('security', $message, $context);
    }
}
