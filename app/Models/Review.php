<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class Review extends Model
{
    protected static string $table = 'reviews';

    /**
     * @return array<int,array<string,mixed>>
     */
    public static function forUser(int $userId, int $limit = 20, int $offset = 0): array
    {
        return Database::select(
            'SELECT r.*, reviewer.name AS reviewer_name, reviewer.id AS reviewer_id,
                    c.reference AS contract_reference, p.title AS project_title
             FROM `reviews` r
             INNER JOIN `users` reviewer ON reviewer.id = r.reviewer_id
             INNER JOIN `contracts` c ON c.id = r.contract_id
             INNER JOIN `projects` p ON p.id = c.project_id
             WHERE r.reviewee_id = ? AND r.status = \'published\'
             ORDER BY r.created_at DESC
             LIMIT ' . max(1, min($limit, 50)) . ' OFFSET ' . max(0, $offset),
            [$userId]
        );
    }

    public static function countForUser(int $userId): int
    {
        return (int) Database::scalar(
            'SELECT COUNT(*) FROM `reviews` WHERE `reviewee_id` = ? AND `status` = \'published\'',
            [$userId]
        );
    }

    /**
     * @return array<string,mixed>|null
     */
    public static function forContractByReviewer(int $contractId, int $reviewerId): ?array
    {
        return self::findBy(['contract_id' => $contractId, 'reviewer_id' => $reviewerId]);
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public static function adminList(array $filters, int $limit, int $offset): array
    {
        $conditions = ['1=1'];
        $params = [];

        if (!empty($filters['status'])) {
            $conditions[] = 'r.status = ?';
            $params[] = $filters['status'];
        }
        if (!empty($filters['rating'])) {
            $conditions[] = 'r.rating = ?';
            $params[] = (int) $filters['rating'];
        }

        return Database::select(
            'SELECT r.*, reviewer.name AS reviewer_name, reviewee.name AS reviewee_name,
                    c.reference AS contract_reference
             FROM `reviews` r
             INNER JOIN `users` reviewer ON reviewer.id = r.reviewer_id
             INNER JOIN `users` reviewee ON reviewee.id = r.reviewee_id
             INNER JOIN `contracts` c ON c.id = r.contract_id
             WHERE ' . implode(' AND ', $conditions) . '
             ORDER BY r.created_at DESC
             LIMIT ' . max(1, min($limit, 100)) . ' OFFSET ' . max(0, $offset),
            $params
        );
    }

    public static function adminCount(array $filters): int
    {
        $conditions = ['1=1'];
        $params = [];
        if (!empty($filters['status'])) {
            $conditions[] = 'r.status = ?';
            $params[] = $filters['status'];
        }

        return (int) Database::scalar('SELECT COUNT(*) FROM `reviews` r WHERE ' . implode(' AND ', $conditions), $params);
    }
}
