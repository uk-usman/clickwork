<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class PortfolioItem extends Model
{
    protected static string $table = 'portfolio_items';

    /**
     * @return array<int,array<string,mixed>>
     */
    public static function forUser(int $userId, bool $onlyVisible = false): array
    {
        $sql = 'SELECT pi.*, c.name AS category_name
                FROM `portfolio_items` pi
                LEFT JOIN `categories` c ON c.id = pi.category_id
                WHERE pi.user_id = ?';
        if ($onlyVisible) {
            $sql .= ' AND pi.is_visible = 1';
        }
        $sql .= ' ORDER BY pi.sort_order ASC, pi.created_at DESC';

        return Database::select($sql, [$userId]);
    }

    /**
     * @return array<string,mixed>|null
     */
    public static function ownedBy(int $id, int $userId): ?array
    {
        return self::findBy(['id' => $id, 'user_id' => $userId]);
    }

    public static function countForUser(int $userId): int
    {
        return self::countBy(['user_id' => $userId]);
    }
}
