<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class SubscriptionPayment extends Model
{
    protected static string $table = 'subscription_payments';

    /**
     * @return array<int,array<string,mixed>>
     */
    public static function forSubscription(int $subscriptionId): array
    {
        return Database::select(
            'SELECT sp.*, recorder.name AS recorded_by_name, verifier.name AS verified_by_name
             FROM `subscription_payments` sp
             INNER JOIN `users` recorder ON recorder.id = sp.recorded_by
             LEFT JOIN `users` verifier ON verifier.id = sp.verified_by
             WHERE sp.subscription_id = ? ORDER BY sp.created_at DESC',
            [$subscriptionId]
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
            $conditions[] = 'sp.status = ?';
            $params[] = $filters['status'];
        }

        return Database::select(
            'SELECT sp.*, u.name AS user_name, u.email AS user_email, p.name AS plan_name,
                    recorder.name AS recorded_by_name, verifier.name AS verified_by_name
             FROM `subscription_payments` sp
             INNER JOIN `users` u ON u.id = sp.user_id
             INNER JOIN `subscription_plans` p ON p.id = sp.plan_id
             INNER JOIN `users` recorder ON recorder.id = sp.recorded_by
             LEFT JOIN `users` verifier ON verifier.id = sp.verified_by
             WHERE ' . implode(' AND ', $conditions) . '
             ORDER BY FIELD(sp.status, \'pending\', \'verified\', \'rejected\'), sp.created_at DESC
             LIMIT ' . max(1, min($limit, 100)) . ' OFFSET ' . max(0, $offset),
            $params
        );
    }

    public static function adminCount(array $filters): int
    {
        $conditions = ['1=1'];
        $params = [];
        if (!empty($filters['status'])) {
            $conditions[] = 'sp.status = ?';
            $params[] = $filters['status'];
        }

        return (int) Database::scalar(
            'SELECT COUNT(*) FROM `subscription_payments` sp WHERE ' . implode(' AND ', $conditions),
            $params
        );
    }
}
