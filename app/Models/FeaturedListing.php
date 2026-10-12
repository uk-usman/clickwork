<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class FeaturedListing extends Model
{
    protected static string $table = 'featured_listings';

    /**
     * @return array<int,array<string,mixed>>
     */
    public static function forProject(int $projectId): array
    {
        return Database::select(
            'SELECT fl.*, u.name AS purchased_by_name, v.name AS verified_by_name
             FROM `featured_listings` fl
             INNER JOIN `users` u ON u.id = fl.purchased_by
             LEFT JOIN `users` v ON v.id = fl.verified_by
             WHERE fl.project_id = ? ORDER BY fl.created_at DESC',
            [$projectId]
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
            $conditions[] = 'fl.status = ?';
            $params[] = $filters['status'];
        }

        return Database::select(
            'SELECT fl.*, p.title AS project_title, p.reference AS project_reference, u.name AS purchased_by_name
             FROM `featured_listings` fl
             INNER JOIN `projects` p ON p.id = fl.project_id
             INNER JOIN `users` u ON u.id = fl.purchased_by
             WHERE ' . implode(' AND ', $conditions) . '
             ORDER BY FIELD(fl.status, \'pending\', \'active\', \'expired\', \'rejected\'), fl.created_at DESC
             LIMIT ' . max(1, min($limit, 100)) . ' OFFSET ' . max(0, $offset),
            $params
        );
    }

    public static function adminCount(array $filters): int
    {
        $conditions = ['1=1'];
        $params = [];
        if (!empty($filters['status'])) {
            $conditions[] = 'fl.status = ?';
            $params[] = $filters['status'];
        }

        return (int) Database::scalar('SELECT COUNT(*) FROM `featured_listings` fl WHERE ' . implode(' AND ', $conditions), $params);
    }

    public static function expireOverdue(): int
    {
        return Database::affected(
            'UPDATE `featured_listings` SET `status` = \'expired\'
             WHERE `status` = \'active\' AND `ends_at` IS NOT NULL AND `ends_at` <= UTC_TIMESTAMP()'
        );
    }
}
