<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class EmailVerificationToken extends Model
{
    protected static string $table = 'email_verification_tokens';

    public static function issue(int $userId, string $rawToken, int $ttlHours): int
    {
        Database::affected(
            'UPDATE `email_verification_tokens` SET `used_at` = UTC_TIMESTAMP()
             WHERE `user_id` = ? AND `used_at` IS NULL',
            [$userId]
        );

        return self::create([
            'user_id'    => $userId,
            'token_hash' => hash('sha256', $rawToken),
            'expires_at' => gmdate('Y-m-d H:i:s', time() + ($ttlHours * 3600)),
        ]);
    }

    /**
     * @return array<string,mixed>|null
     */
    public static function findUsable(string $rawToken): ?array
    {
        return Database::selectOne(
            'SELECT * FROM `email_verification_tokens`
             WHERE `token_hash` = ? AND `used_at` IS NULL AND `expires_at` > UTC_TIMESTAMP()
             LIMIT 1',
            [hash('sha256', $rawToken)]
        );
    }

    public static function consume(int $id): void
    {
        Database::affected(
            'UPDATE `email_verification_tokens` SET `used_at` = UTC_TIMESTAMP() WHERE `id` = ?',
            [$id]
        );
    }
}
