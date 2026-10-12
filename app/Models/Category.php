<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class Category extends Model
{
    protected static string $table = 'categories';

    /**
     * @return array<int,array<string,mixed>>
     */
    public static function activeList(): array
    {
        return Database::select(
            'SELECT * FROM `categories` WHERE `is_active` = 1 ORDER BY `sort_order` ASC, `name` ASC'
        );
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public static function allOrdered(): array
    {
        return Database::select('SELECT * FROM `categories` ORDER BY `sort_order` ASC, `name` ASC');
    }

    /**
     * Categories with a live count of published projects.
     *
     * @return array<int,array<string,mixed>>
     */
    public static function popularWithCounts(int $limit = 8): array
    {
        return Database::select(
            'SELECT c.*, COUNT(p.id) AS project_count
             FROM `categories` c
             LEFT JOIN `projects` p ON p.category_id = c.id AND p.status IN (\'published\', \'in_progress\', \'completed\')
             WHERE c.is_active = 1
             GROUP BY c.id
             ORDER BY project_count DESC, c.sort_order ASC
             LIMIT ' . max(1, min($limit, 30))
        );
    }

    public static function findBySlug(string $slug): ?array
    {
        return self::findBy(['slug' => $slug]);
    }
}
