<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class Proposal extends Model
{
    protected static string $table = 'proposals';

    public const STATUSES = ['submitted','shortlisted','accepted','rejected','withdrawn'];

    /**
     * @return array<string,mixed>|null
     */
    public static function detailed(int $id): ?array
    {
        return Database::selectOne(
            'SELECT pr.*, p.title AS project_title, p.reference AS project_reference, p.status AS project_status,
                    p.client_id, p.budget_type, p.budget_min, p.budget_max, p.deadline AS project_deadline,
                    u.name AS freelancer_name, u.email AS freelancer_email, u.status AS freelancer_status,
                    up.freelancer_title, up.city AS freelancer_city, up.state AS freelancer_state,
                    up.rating_average, up.rating_count, up.completed_contracts, up.avatar_path AS freelancer_avatar,
                    up.hourly_rate
             FROM `proposals` pr
             INNER JOIN `projects` p ON p.id = pr.project_id
             INNER JOIN `users` u ON u.id = pr.freelancer_id
             LEFT JOIN `user_profiles` up ON up.user_id = u.id
             WHERE pr.id = ? LIMIT 1',
            [$id]
        );
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public static function forProject(int $projectId, ?string $status = null): array
    {
        $sql = 'SELECT pr.*, u.name AS freelancer_name, u.status AS freelancer_status,
                       up.freelancer_title, up.city, up.state, up.rating_average, up.rating_count,
                       up.completed_contracts, up.avatar_path
                FROM `proposals` pr
                INNER JOIN `users` u ON u.id = pr.freelancer_id
                LEFT JOIN `user_profiles` up ON up.user_id = u.id
                WHERE pr.project_id = ?';
        $params = [$projectId];

        if ($status !== null) {
            $sql .= ' AND pr.status = ?';
            $params[] = $status;
        }

        $sql .= ' ORDER BY FIELD(pr.status, \'accepted\', \'shortlisted\', \'submitted\', \'rejected\', \'withdrawn\'), pr.created_at ASC';

        return Database::select($sql, $params);
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public static function forFreelancer(int $freelancerId, ?string $status = null): array
    {
        $sql = 'SELECT pr.*, p.title AS project_title, p.reference AS project_reference, p.status AS project_status,
                       p.deadline AS project_deadline, c.name AS category_name, u.name AS client_name
                FROM `proposals` pr
                INNER JOIN `projects` p ON p.id = pr.project_id
                INNER JOIN `users` u ON u.id = p.client_id
                LEFT JOIN `categories` c ON c.id = p.category_id
                WHERE pr.freelancer_id = ?';
        $params = [$freelancerId];

        if ($status !== null) {
            $sql .= ' AND pr.status = ?';
            $params[] = $status;
        }

        $sql .= ' ORDER BY pr.created_at DESC';

        return Database::select($sql, $params);
    }

    public static function existsFor(int $projectId, int $freelancerId): bool
    {
        return (int) Database::scalar(
            'SELECT COUNT(*) FROM `proposals` WHERE `project_id` = ? AND `freelancer_id` = ?',
            [$projectId, $freelancerId]
        ) > 0;
    }

    public static function findFor(int $projectId, int $freelancerId): ?array
    {
        return self::findBy(['project_id' => $projectId, 'freelancer_id' => $freelancerId]);
    }

    public static function acceptedForProject(int $projectId): ?array
    {
        return self::findBy(['project_id' => $projectId, 'status' => 'accepted']);
    }
}
