<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class User extends Model
{
    protected static string $table = 'users';

    /**
     * @return array<string,mixed>|null
     */
    public static function findByEmail(string $email): ?array
    {
        return Database::selectOne(
            'SELECT * FROM `users` WHERE `email` = ? LIMIT 1',
            [mb_strtolower(trim($email))]
        );
    }

    /**
     * User row joined with their profile (used almost everywhere).
     *
     * @return array<string,mixed>|null
     */
    public static function withProfile(int $id): ?array
    {
        return Database::selectOne(
            'SELECT u.*, p.id AS profile_id, p.headline, p.bio, p.avatar_path, p.city, p.state, p.country,
                    p.timezone, p.website, p.company_name, p.company_registered, p.company_registration_number,
                    p.company_size, p.company_industry, p.company_description, p.freelancer_title,
                    p.freelancer_overview, p.experience_years, p.hourly_rate, p.rate_currency,
                    p.availability, p.languages, p.profile_visibility, p.rating_average, p.rating_count,
                    p.completed_contracts
             FROM `users` u
             LEFT JOIN `user_profiles` p ON p.user_id = u.id
             WHERE u.id = ? LIMIT 1',
            [$id]
        );
    }

    /**
     * @param array<string,mixed> $data
     * @param array<string,mixed> $profile
     */
    public static function createWithProfile(array $data, array $profile = []): int
    {
        return Database::transaction(static function () use ($data, $profile): int {
            $userId = self::create($data);
            $profile['user_id'] = $userId;
            UserProfile::create($profile);

            return $userId;
        });
    }

    /**
     * @param array<string,mixed> $filters
     * @return array<int,array<string,mixed>>
     */
    public static function publicFreelancers(int $limit, int $offset, array $filters = []): array
    {
        $sql = 'SELECT u.id, u.name, u.created_at, p.headline, p.avatar_path, p.city, p.state,
                       p.freelancer_title, p.freelancer_overview, p.hourly_rate, p.rate_currency,
                       p.rating_average, p.rating_count, p.completed_contracts, p.availability
                FROM `users` u
                INNER JOIN `user_profiles` p ON p.user_id = u.id
                WHERE u.role = ? AND u.status = ? AND p.profile_visibility = ?';
        $params = ['user', 'active', 'public'];

        [$extra, $extraParams] = self::freelancerFilterSql($filters);
        $sql .= $extra;
        $params = array_merge($params, $extraParams);

        $sql .= ' ORDER BY p.completed_contracts DESC, p.rating_average DESC, u.created_at DESC';
        $sql .= ' LIMIT ' . max(1, min($limit, 60)) . ' OFFSET ' . max(0, $offset);

        return Database::select($sql, $params);
    }

    /**
     * @param array<string,mixed> $filters
     */
    public static function countPublicFreelancers(array $filters = []): int
    {
        $sql = 'SELECT COUNT(*)
                FROM `users` u
                INNER JOIN `user_profiles` p ON p.user_id = u.id
                WHERE u.role = ? AND u.status = ? AND p.profile_visibility = ?';
        $params = ['user', 'active', 'public'];

        [$extra, $extraParams] = self::freelancerFilterSql($filters);
        $sql .= $extra;
        $params = array_merge($params, $extraParams);

        return (int) Database::scalar($sql, $params);
    }

    /**
     * @param array<string,mixed> $filters
     * @return array{0:string,1:array<int,mixed>}
     */
    private static function freelancerFilterSql(array $filters): array
    {
        $sql = '';
        $params = [];

        if (!empty($filters['city'])) {
            $sql .= ' AND p.city = ?';
            $params[] = $filters['city'];
        }
        if (!empty($filters['state'])) {
            $sql .= ' AND p.state = ?';
            $params[] = $filters['state'];
        }
        if (!empty($filters['q'])) {
            $sql .= ' AND (u.name LIKE ? OR p.headline LIKE ? OR p.freelancer_title LIKE ? OR p.freelancer_overview LIKE ?)';
            $like = '%' . $filters['q'] . '%';
            array_push($params, $like, $like, $like, $like);
        }
        if (!empty($filters['skill_id'])) {
            $sql .= ' AND EXISTS (SELECT 1 FROM user_skills us WHERE us.user_id = u.id AND us.skill_id = ?)';
            $params[] = (int) $filters['skill_id'];
        }

        return [$sql, $params];
    }

    /**
     * Recalculate the cached rating aggregate from real published reviews.
     */
    public static function recalculateRating(int $userId): void
    {
        $row = Database::selectOne(
            'SELECT COUNT(*) AS total, COALESCE(AVG(rating), 0) AS average
             FROM `reviews` WHERE `reviewee_id` = ? AND `status` = \'published\'',
            [$userId]
        );

        UserProfile::updateAggregate(
            $userId,
            round((float) ($row['average'] ?? 0), 2),
            (int) ($row['total'] ?? 0)
        );
    }

    public static function recalculateCompletedContracts(int $userId): void
    {
        $count = (int) Database::scalar(
            'SELECT COUNT(*) FROM `contracts`
             WHERE `status` = \'completed\' AND (`client_id` = ? OR `freelancer_id` = ?)',
            [$userId, $userId]
        );

        UserProfile::updateCompletedContracts($userId, $count);
    }

    /**
     * @param  array<string,mixed> $filters
     * @return array<int,array<string,mixed>>
     */
    public static function adminList(array $filters, int $limit, int $offset): array
    {
        [$where, $params] = self::adminFilterSql($filters);

        return Database::select(
            'SELECT u.*, p.city, p.state, p.freelancer_title, p.profile_visibility, p.completed_contracts
             FROM `users` u
             LEFT JOIN `user_profiles` p ON p.user_id = u.id
             WHERE ' . $where . '
             ORDER BY u.created_at DESC
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
            'SELECT COUNT(*) FROM `users` u LEFT JOIN `user_profiles` p ON p.user_id = u.id WHERE ' . $where,
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
            $conditions[] = '(u.name LIKE ? OR u.email LIKE ?)';
            $like = '%' . $filters['q'] . '%';
            $params[] = $like;
            $params[] = $like;
        }
        if (!empty($filters['role'])) {
            $conditions[] = 'u.role = ?';
            $params[] = $filters['role'];
        }
        if (!empty($filters['status'])) {
            $conditions[] = 'u.status = ?';
            $params[] = $filters['status'];
        }
        if (!empty($filters['state'])) {
            $conditions[] = 'p.state = ?';
            $params[] = $filters['state'];
        }

        return [implode(' AND ', $conditions), $params];
    }
}
