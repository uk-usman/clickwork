<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Minimal .env reader. No Composer, no external dependency.
 *
 * Supports: KEY=value, KEY="quoted value", KEY='single', # comments,
 * and inline `#` comments after unquoted values.
 */
final class Env
{
    /** @var array<string,string> */
    private static array $values = [];

    private static bool $loaded = false;

    public static function load(string $path): void
    {
        if (!is_readable($path)) {
            self::$loaded = true;
            return;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];

        foreach ($lines as $line) {
            $line = trim($line);

            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            // Allow an optional leading "export ".
            if (str_starts_with($line, 'export ')) {
                $line = trim(substr($line, 7));
            }

            $pos = strpos($line, '=');
            if ($pos === false) {
                continue;
            }

            $key = trim(substr($line, 0, $pos));
            $value = trim(substr($line, $pos + 1));

            if ($key === '') {
                continue;
            }

            if ($value !== '' && ($value[0] === '"' || $value[0] === "'")) {
                $quote = $value[0];
                $end = strrpos($value, $quote);
                if ($end !== false && $end > 0) {
                    $value = substr($value, 1, $end - 1);
                } else {
                    $value = substr($value, 1);
                }
            } else {
                // Strip inline comment for unquoted values.
                $hash = strpos($value, ' #');
                if ($hash !== false) {
                    $value = rtrim(substr($value, 0, $hash));
                }
            }

            self::$values[$key] = $value;
        }

        self::$loaded = true;
    }

    public static function isLoaded(): bool
    {
        return self::$loaded;
    }

    public static function get(string $key, ?string $default = null): ?string
    {
        // Real environment variables always win (useful for hosting panels).
        $fromEnv = getenv($key);
        if ($fromEnv !== false && $fromEnv !== '') {
            return $fromEnv;
        }

        return self::$values[$key] ?? $default;
    }

    public static function bool(string $key, bool $default = false): bool
    {
        $value = self::get($key);
        if ($value === null) {
            return $default;
        }

        return in_array(strtolower(trim($value)), ['1', 'true', 'yes', 'on'], true);
    }

    public static function int(string $key, int $default = 0): int
    {
        $value = self::get($key);
        if ($value === null || $value === '' || !is_numeric($value)) {
            return $default;
        }

        return (int) $value;
    }
}
