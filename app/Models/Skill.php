<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class Skill extends Model
{
    protected static string $table = 'skills';

    /**
     * @return array<int,array<string,mixed>>
     */
    public static function activeList(): array
    {
        return Database::select(
            'SELECT s.*, c.name AS category_name
             FROM `skills` s
             LEFT JOIN `categories` c ON c.id = s.category_id
             WHERE s.is_active = 1
             ORDER BY s.name ASC'
        );
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public static function allOrdered(): array
    {
        return Database::select(
            'SELECT s.*, c.name AS category_name
             FROM `skills` s
             LEFT JOIN `categories` c ON c.id = s.category_id
             ORDER BY s.name ASC'
        );
    }

    /**
     * Resolve a list of skill ids, keeping only active ones.
     *
     * @param  array<int,mixed> $ids
     * @return array<int,int>
     */
    public static function validIds(array $ids): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids), static fn (int $i): bool => $i > 0)));

        if ($ids === []) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $rows = Database::select(
            'SELECT id FROM `skills` WHERE is_active = 1 AND id IN (' . $placeholders . ')',
            $ids
        );

        return array_map(static fn (array $r): int => (int) $r['id'], $rows);
    }
}
