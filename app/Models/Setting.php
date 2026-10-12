<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

/**
 * Site settings with a per-request cache.
 */
final class Setting extends Model
{
    protected static string $table = 'site_settings';

    /** @var array<string,string|null>|null */
    private static ?array $cache = null;

    public static function get(string $key, ?string $default = null): ?string
    {
        $all = self::all();

        return array_key_exists($key, $all) && $all[$key] !== null ? (string) $all[$key] : $default;
    }

    /**
     * @return array<string,string|null>
     */
    public static function all(): array
    {
        if (self::$cache !== null) {
            return self::$cache;
        }

        self::$cache = [];

        try {
            $rows = Database::select('SELECT `setting_key`, `setting_value` FROM `site_settings`');
            foreach ($rows as $row) {
                self::$cache[(string) $row['setting_key']] = $row['setting_value'];
            }
        } catch (\Throwable) {
            // Settings table may not exist during installation; fall back to defaults.
            self::$cache = [];
        }

        return self::$cache;
    }

    public static function set(string $key, ?string $value, int $userId, string $group = 'general', string $type = 'string', bool $isPublic = false): void
    {
        Database::run(
            'INSERT INTO `site_settings` (`setting_key`, `setting_value`, `setting_group`, `setting_type`, `is_public`, `updated_by`)
             VALUES (?, ?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE `setting_value` = VALUES(`setting_value`),
                                     `setting_group` = VALUES(`setting_group`),
                                     `setting_type`  = VALUES(`setting_type`),
                                     `is_public`     = VALUES(`is_public`),
                                     `updated_by`    = VALUES(`updated_by`)',
            [$key, $value, $group, $type, $isPublic ? 1 : 0, $userId]
        );

        self::$cache = null;
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public static function group(string $group): array
    {
        return self::allBy(['setting_group' => $group], 'setting_key ASC');
    }

    public static function flush(): void
    {
        self::$cache = null;
    }
}
