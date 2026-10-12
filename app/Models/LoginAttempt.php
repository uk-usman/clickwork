<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

/**
 * Server-side login throttling store.
 */
final class LoginAttempt extends Model
{
    protected static string $table = 'login_attempts';

    public static function record(string $email, string $ip, bool $successful): void
    {
        self::create([
            'email'      => mb_strtolower(trim($email)),
            'ip_address' => $ip,
            'successful' => $successful ? 1 : 0,
        ]);
    }

    public static function recentFailures(string $email, string $ip, int $windowSeconds): int
    {
        return (int) Database::scalar(
            'SELECT COUNT(*) FROM `login_attempts`
             WHERE `successful` = 0
               AND (`email` = ? OR `ip_address` = ?)
               AND `attempted_at` > DATE_SUB(UTC_TIMESTAMP(), INTERVAL ? SECOND)',
            [mb_strtolower(trim($email)), $ip, $windowSeconds]
        );
    }

    public static function clearFor(string $email, string $ip): void
    {
        Database::affected(
            'DELETE FROM `login_attempts` WHERE `successful` = 0 AND (`email` = ? OR `ip_address` = ?)',
            [mb_strtolower(trim($email)), $ip]
        );
    }

    public static function prune(int $olderThanSeconds = 604800): int
    {
        return Database::affected(
            'DELETE FROM `login_attempts` WHERE `attempted_at` < DATE_SUB(UTC_TIMESTAMP(), INTERVAL ? SECOND)',
            [$olderThanSeconds]
        );
    }
}
