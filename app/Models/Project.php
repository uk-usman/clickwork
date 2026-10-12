<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class Project extends Model
{
    protected static string $table = 'projects';

    public const STATUSES = ['draft','pending','published','rejected','in_progress','completed','cancelled','closed'];

    /**
     * Project with client and category context.
     *
     * @return array<string,mixed>|null
     */
    public static function detailed(int $id): ?array
    {
        return Database::selectOne(
            'SELECT p.*, c.name AS category_name, c.slug AS category_slug,
                    u.name AS client_name, u.status AS client_status, u.email AS client_email,
                    u.created_at AS client_since,
                    up.city AS client_city, up.state AS client_state, up.avatar_path AS client_avatar,
                    up.company_name AS client_company, up.company_registered AS client_company_registered
             FROM `projects` p
             LEFT JOIN `categories` c ON c.id = p.category_id
             INNER JOIN `users` u ON u.id = p.client_id
             LEFT JOIN `user_profiles` up ON up.user_id = u.id
             WHERE p.id = ? LIMIT 1',
            [$id]
        );
    }

    /**
     * @return array<string,mixed>|null
     */
    public static function findByReference(string $reference): ?array
    {
        return self::findBy(['reference' => $reference]);
    }

    /**
     * Public listing query with filters, sorting and pagination.
     *
     * @param  array<string,mixed> $filters
     * @return array<int,array<string,mixed>>
     */
    public static function publicList(array $filters, string $sort, int $limit, int $offset): array
    {
        [$where, $params] = self::publicFilterSql($filters);

        $orderBy = match ($sort) {
            'budget_high' => 'p.is_featured DESC, GREATEST(COALESCE(p.budget_max,0), COALESCE(p.budget_min,0)) DESC, p.published_at DESC',
            'budget_low'  => 'p.is_featured DESC, LEAST(COALESCE(p.budget_min,0), COALESCE(p.budget_max,0)) ASC, p.published_at DESC',
            'deadline'    => 'p.is_featured DESC, p.deadline IS NULL ASC, p.deadline ASC',
            'oldest'      => 'p.is_featured DESC, p.published_at ASC',
            default       => 'p.is_featured DESC, p.published_at DESC',
        };

        $sql = 'SELECT p.id, p.reference, p.title, p.slug, p.description, p.budget_type, p.budget_min, p.budget_max,
                       p.currency, p.deadline, p.is_featured, p.featured_until, p.published_at, p.proposal_count,
                       p.visibility, p.status, c.name AS category_name, c.slug AS category_slug, c.icon AS category_icon,
                       u.name AS client_name, up.city AS client_city, up.state AS client_state
                FROM `projects` p
                LEFT JOIN `categories` c ON c.id = p.category_id
                INNER JOIN `users` u ON u.id = p.client_id
                LEFT JOIN `user_profiles` up ON up.user_id = u.id
                WHERE ' . $where . '
                ORDER BY ' . $orderBy . '
                LIMIT ' . max(1, min($limit, 50)) . ' OFFSET ' . max(0, $offset);

        return Database::select($sql, $params);
    }

    /**
     * @param array<string,mixed> $filters
     */
    public static function publicCount(array $filters): int
    {
        [$where, $params] = self::publicFilterSql($filters);

        return (int) Database::scalar(
            'SELECT COUNT(*) FROM `projects` p
             LEFT JOIN `categories` c ON c.id = p.category_id
             INNER JOIN `users` u ON u.id = p.client_id
             WHERE ' . $where,
            $params
        );
    }

    /**
     * @param  array<string,mixed> $filters
     * @return array{0:string,1:array<int,mixed>}
     */
    private static function publicFilterSql(array $filters): array
    {
        $conditions = ["p.status IN ('published','in_progress')", "p.visibility = 'public'", "u.status = 'active'"];
        $params = [];

        if (!empty($filters['q'])) {
            $conditions[] = '(p.title LIKE ? OR p.description LIKE ?)';
            $like = '%' . $filters['q'] . '%';
            $params[] = $like;
            $params[] = $like;
        }
        if (!empty($filters['category_id'])) {
            $conditions[] = 'p.category_id = ?';
            $params[] = (int) $filters['category_id'];
        }
        if (!empty($filters['category_slug'])) {
            $conditions[] = 'c.slug = ?';
            $params[] = $filters['category_slug'];
        }
        if (!empty($filters['budget_type'])) {
            $conditions[] = 'p.budget_type = ?';
            $params[] = $filters['budget_type'];
        }
        if (!empty($filters['budget_min'])) {
            $conditions[] = 'COALESCE(p.budget_max, p.budget_min, 0) >= ?';
            $params[] = (float) $filters['budget_min'];
        }
        if (!empty($filters['budget_max'])) {
            $conditions[] = 'COALESCE(p.budget_min, p.budget_max, 0) <= ?';
            $params[] = (float) $filters['budget_max'];
        }
        if (!empty($filters['deadline_before'])) {
            $conditions[] = 'p.deadline IS NOT NULL AND p.deadline <= ?';
            $params[] = $filters['deadline_before'];
        }
        if (!empty($filters['client_id'])) {
            $conditions[] = 'p.client_id = ?';
            $params[] = (int) $filters['client_id'];
        }
        if (!empty($filters['skill_id'])) {
            $conditions[] = 'EXISTS (SELECT 1 FROM project_skills ps WHERE ps.project_id = p.id AND ps.skill_id = ?)';
            $params[] = (int) $filters['skill_id'];
        }
        if (isset($filters['featured_only']) && $filters['featured_only']) {
            $conditions[] = 'p.is_featured = 1 AND (p.featured_until IS NULL OR p.featured_until > UTC_TIMESTAMP())';
        }

        return [implode(' AND ', $conditions), $params];
    }

    /**
     * @param array<string,mixed> $filters
     * @return array<int,array<string,mixed>>
     */
    public static function adminList(array $filters, int $limit, int $offset): array
    {
        [$where, $params] = self::adminFilterSql($filters);

        return Database::select(
            'SELECT p.*, c.name AS category_name, u.name AS client_name, u.email AS client_email
             FROM `projects` p
             LEFT JOIN `categories` c ON c.id = p.category_id
             INNER JOIN `users` u ON u.id = p.client_id
             WHERE ' . $where . '
             ORDER BY FIELD(p.status, \'pending\') DESC, p.created_at DESC
             LIMIT ' . max(1, min($limit, 100)) . ' OFFSET ' . max(0, $offset),
            $params
        );
    }

    /**
     * @param array<string,mixed> $filters
     */
    public static function adminCount(array $filters): int
    {
        [$where, $params] = self::adminFilterSql($filters);

        return (int) Database::scalar(
            'SELECT COUNT(*) FROM `projects` p INNER JOIN `users` u ON u.id = p.client_id WHERE ' . $where,
            $params
        );
    }

    /**
     * @param  array<string,mixed> $filters
     * @return array{0:string,1:array<int,mixed>}
     */
    private static function adminFilterSql(array $filters): array
    {
        $conditions = ['1=1'];
        $params = [];

        if (!empty($filters['q'])) {
            $conditions[] = '(p.title LIKE ? OR p.reference LIKE ? OR u.email LIKE ?)';
            $like = '%' . $filters['q'] . '%';
            array_push($params, $like, $like, $like);
        }
        if (!empty($filters['status'])) {
            $conditions[] = 'p.status = ?';
            $params[] = $filters['status'];
        }
        if (!empty($filters['category_id'])) {
            $conditions[] = 'p.category_id = ?';
            $params[] = (int) $filters['category_id'];
        }

        return [implode(' AND ', $conditions), $params];
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public static function skillsFor(int $projectId): array
    {
        return Database::select(
            'SELECT s.id, s.name, s.slug FROM `project_skills` ps
             INNER JOIN `skills` s ON s.id = ps.skill_id
             WHERE ps.project_id = ? ORDER BY s.name ASC',
            [$projectId]
        );
    }

    /**
     * @param array<int,int> $skillIds
     */
    public static function syncSkills(int $projectId, array $skillIds): void
    {
        Database::affected('DELETE FROM `project_skills` WHERE `project_id` = ?', [$projectId]);

        foreach (array_unique($skillIds) as $skillId) {
            Database::run(
                'INSERT IGNORE INTO `project_skills` (`project_id`, `skill_id`) VALUES (?, ?)',
                [$projectId, (int) $skillId]
            );
        }
    }

    public static function incrementViews(int $projectId): void
    {
        Database::affected('UPDATE `projects` SET `views_count` = `views_count` + 1 WHERE `id` = ?', [$projectId]);
    }

    public static function refreshProposalCount(int $projectId): void
    {
        Database::affected(
            'UPDATE `projects` SET `proposal_count` = (
                SELECT COUNT(*) FROM `proposals` WHERE `project_id` = ? AND `status` <> \'withdrawn\'
             ) WHERE `id` = ?',
            [$projectId, $projectId]
        );
    }

    public static function activeFeaturedCount(int $clientId): int
    {
        return (int) Database::scalar(
            'SELECT COUNT(*) FROM `projects`
             WHERE `client_id` = ? AND `is_featured` = 1
               AND (`featured_until` IS NULL OR `featured_until` > UTC_TIMESTAMP())',
            [$clientId]
        );
    }

    public static function expireFeatured(): int
    {
        return Database::affected(
            'UPDATE `projects` SET `is_featured` = 0
             WHERE `is_featured` = 1 AND `featured_until` IS NOT NULL AND `featured_until` <= UTC_TIMESTAMP()'
        );
    }
}
