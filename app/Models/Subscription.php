<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class Subscription extends Model
{
    protected static string $table = 'subscriptions';

    /**
     * The single active subscription for a user, if any (expiry enforced server-side).
     *
     * @return array<string,mixed>|null
     */
    public static function activeFor(int $userId): ?array
    {
        return Database::selectOne(
            'SELECT s.*, p.code AS plan_code, p.name AS plan_name, p.audience, p.features, p.limits, p.price, p.currency
             FROM `subscriptions` s
             INNER JOIN `subscription_plans` p ON p.id = s.plan_id
             WHERE s.user_id = ? AND s.status = \'active\'
               AND (s.expires_at IS NULL OR s.expires_at > UTC_TIMESTAMP())
             ORDER BY s.expires_at DESC, s.id DESC LIMIT 1',
            [$userId]
        );
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public static function forUser(int $userId): array
    {
        return Database::select(
            'SELECT s.*, p.name AS plan_name, p.code AS plan_code, p.price, p.currency
             FROM `subscriptions` s
             INNER JOIN `subscription_plans` p ON p.id = s.plan_id
             WHERE s.user_id = ? ORDER BY s.created_at DESC',
            [$userId]
        );
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public static function adminList(array $filters, int $limit, int $offset): array
    {
        $conditions = ['1=1'];
        $params = [];

        if (!empty($filters['status'])) {
            $conditions[] = 's.status = ?';
            $params[] = $filters['status'];
        }

        return Database::select(
            'SELECT s.*, p.name AS plan_name, u.name AS user_name, u.email AS user_email
             FROM `subscriptions` s
             INNER JOIN `subscription_plans` p ON p.id = s.plan_id
             INNER JOIN `users` u ON u.id = s.user_id
             WHERE ' . implode(' AND ', $conditions) . '
             ORDER BY FIELD(s.status, \'pending\', \'active\', \'expired\', \'cancelled\'), s.created_at DESC
             LIMIT ' . max(1, min($limit, 100)) . ' OFFSET ' . max(0, $offset),
            $params
        );
    }

    public static function adminCount(array $filters): int
    {
        $conditions = ['1=1'];
        $params = [];
        if (!empty($filters['status'])) {
            $conditions[] = 's.status = ?';
            $params[] = $filters['status'];
        }

        return (int) Database::scalar('SELECT COUNT(*) FROM `subscriptions` s WHERE ' . implode(' AND ', $conditions), $params);
    }

    public static function expireOverdue(): int
    {
        return Database::affected(
            'UPDATE `subscriptions` SET `status` = \'expired\'
             WHERE `status` = \'active\' AND `expires_at` IS NOT NULL AND `expires_at` <= UTC_TIMESTAMP()'
        );
    }
}
