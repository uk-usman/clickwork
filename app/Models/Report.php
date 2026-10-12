<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class Report extends Model
{
    protected static string $table = 'reports';

    /**
     * @return array<int,array<string,mixed>>
     */
    public static function adminList(array $filters, int $limit, int $offset): array
    {
        [$where, $params] = self::filterSql($filters);

        return Database::select(
            'SELECT r.*, reporter.name AS reporter_name, reporter.email AS reporter_email,
                    assignee.name AS assigned_to_name, resolver.name AS resolved_by_name
             FROM `reports` r
             INNER JOIN `users` reporter ON reporter.id = r.reporter_id
             LEFT JOIN `users` assignee ON assignee.id = r.assigned_to
             LEFT JOIN `users` resolver ON resolver.id = r.resolved_by
             WHERE ' . $where . '
             ORDER BY FIELD(r.status, \'open\', \'in_review\', \'resolved\', \'dismissed\'), r.created_at DESC
             LIMIT ' . max(1, min($limit, 100)) . ' OFFSET ' . max(0, $offset),
            $params
        );
    }

    public static function adminCount(array $filters): int
    {
        [$where, $params] = self::filterSql($filters);

        return (int) Database::scalar('SELECT COUNT(*) FROM `reports` r WHERE ' . $where, $params);
    }

    /**
     * @param  array<string,mixed> $filters
     * @return array{0:string,1:array<int,mixed>}
     */
    private static function filterSql(array $filters): array
    {
        $conditions = ['1=1'];
        $params = [];

        if (!empty($filters['status'])) {
            $conditions[] = 'r.status = ?';
            $params[] = $filters['status'];
        }
        if (!empty($filters['type'])) {
            $conditions[] = 'r.reportable_type = ?';
            $params[] = $filters['type'];
        }

        return [implode(' AND ', $conditions), $params];
    }

    public static function openCount(): int
    {
        return (int) Database::scalar('SELECT COUNT(*) FROM `reports` WHERE `status` IN (\'open\', \'in_review\')');
    }
}
