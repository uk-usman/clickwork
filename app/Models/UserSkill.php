<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class UserSkill extends Model
{
    protected static string $table = 'user_skills';

    /**
     * @return array<int,array<string,mixed>>
     */
    public static function forUser(int $userId): array
    {
        return Database::select(
            'SELECT us.*, s.name, s.slug, c.name AS category_name
             FROM `user_skills` us
             INNER JOIN `skills` s ON s.id = us.skill_id
             LEFT JOIN `categories` c ON c.id = s.category_id
             WHERE us.user_id = ? ORDER BY s.name ASC',
            [$userId]
        );
    }

    /**
     * @param array<int,array{skill_id:int,proficiency?:string,years_experience?:int|null}> $rows
     */
    public static function sync(int $userId, array $rows): void
    {
        Database::affected('DELETE FROM `user_skills` WHERE `user_id` = ?', [$userId]);

        foreach ($rows as $row) {
            $skillId = (int) ($row['skill_id'] ?? 0);
            if ($skillId <= 0) {
                continue;
            }
            Database::run(
                'INSERT INTO `user_skills` (`user_id`, `skill_id`, `proficiency`, `years_experience`)
                 VALUES (?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE `proficiency` = VALUES(`proficiency`),
                                         `years_experience` = VALUES(`years_experience`)',
                [
                    $userId,
                    $skillId,
                    (string) ($row['proficiency'] ?? 'intermediate'),
                    isset($row['years_experience']) ? (int) $row['years_experience'] : null,
                ]
            );
        }
    }
}
