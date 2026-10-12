<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

/**
 * Only a SHA-256 hash of the reset token is stored. The raw token is emailed.
 */
final class PasswordResetToken extends Model
{
    protected static string $table = 'password_reset_tokens';

    public static function issue(int $userId, string $rawToken, int $ttlMinutes, string $ip): int
    {
        // Invalidate previous unused tokens for this user.
        Database::affected(
            'UPDATE `password_reset_tokens` SET `used_at` = UTC_TIMESTAMP()
             WHERE `user_id` = ? AND `used_at` IS NULL',
            [$userId]
        );

        return self::create([
            'user_id'    => $userId,
            'token_hash' => hash('sha256', $rawToken),
            'expires_at' => gmdate('Y-m-d H:i:s', time() + ($ttlMinutes * 60)),
            'ip_address' => $ip,
        ]);
    }

    /**
     * @return array<string,mixed>|null
     */
    public static function findUsable(string $rawToken): ?array
    {
        return Database::selectOne(
            'SELECT * FROM `password_reset_tokens`
             WHERE `token_hash` = ? AND `used_at` IS NULL AND `expires_at` > UTC_TIMESTAMP()
             LIMIT 1',
            [hash('sha256', $rawToken)]
        );
    }

    public static function consume(int $id): void
    {
        Database::affected(
            'UPDATE `password_reset_tokens` SET `used_at` = UTC_TIMESTAMP() WHERE `id` = ?',
            [$id]
        );
    }
}
