<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class UserProfile extends Model
{
    protected static string $table = 'user_profiles';

    /**
     * @return array<string,mixed>|null
     */
    public static function forUser(int $userId): ?array
    {
        return self::findBy(['user_id' => $userId]);
    }

    public static function ensureForUser(int $userId): int
    {
        $existing = self::forUser($userId);
        if ($existing !== null) {
            return (int) $existing['id'];
        }

        return self::create(['user_id' => $userId]);
    }

    public static function updateAggregate(int $userId, float $average, int $count): void
    {
        Database::affected(
            'UPDATE `user_profiles` SET `rating_average` = ?, `rating_count` = ? WHERE `user_id` = ?',
            [number_format($average, 2, '.', ''), $count, $userId]
        );
    }

    public static function updateCompletedContracts(int $userId, int $count): void
    {
        Database::affected(
            'UPDATE `user_profiles` SET `completed_contracts` = ? WHERE `user_id` = ?',
            [$count, $userId]
        );
    }
}
