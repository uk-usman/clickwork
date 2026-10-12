<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class AdminAnnouncement extends Model
{
    protected static string $table = 'admin_announcements';

    /**
     * @return array<int,array<string,mixed>>
     */
    public static function published(?string $audience = null, int $limit = 5): array
    {
        $sql = 'SELECT a.*, u.name AS created_by_name
                FROM `admin_announcements` a
                INNER JOIN `users` u ON u.id = a.created_by
                WHERE a.is_published = 1
                  AND (a.expires_at IS NULL OR a.expires_at > UTC_TIMESTAMP())';
        $params = [];

        if ($audience !== null) {
            $sql .= ' AND a.audience IN (?, \'all\')';
            $params[] = $audience;
        }

        $sql .= ' ORDER BY a.published_at DESC LIMIT ' . max(1, min($limit, 20));

        return Database::select($sql, $params);
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public static function adminList(int $limit, int $offset): array
    {
        return Database::select(
            'SELECT a.*, u.name AS created_by_name
             FROM `admin_announcements` a
             INNER JOIN `users` u ON u.id = a.created_by
             ORDER BY a.created_at DESC
             LIMIT ' . max(1, min($limit, 100)) . ' OFFSET ' . max(0, $offset)
        );
    }

    public static function adminCount(): int
    {
        return self::countBy();
    }
}
