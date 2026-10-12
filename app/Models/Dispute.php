<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class Dispute extends Model
{
    protected static string $table = 'disputes';

    /**
     * @return array<string,mixed>|null
     */
    public static function detailed(int $id): ?array
    {
        return Database::selectOne(
            'SELECT d.*, ct.reference AS contract_reference, ct.client_id, ct.freelancer_id,
                    opener.name AS opened_by_name, against.name AS against_name,
                    resolver.name AS resolved_by_name, p.title AS project_title
             FROM `disputes` d
             INNER JOIN `contracts` ct ON ct.id = d.contract_id
             INNER JOIN `projects` p ON p.id = ct.project_id
             INNER JOIN `users` opener ON opener.id = d.opened_by
             LEFT JOIN `users` against ON against.id = d.against_user_id
             LEFT JOIN `users` resolver ON resolver.id = d.resolved_by
             WHERE d.id = ? LIMIT 1',
            [$id]
        );
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public static function forContract(int $contractId): array
    {
        return Database::select(
            'SELECT d.*, opener.name AS opened_by_name, against.name AS against_name
             FROM `disputes` d
             INNER JOIN `users` opener ON opener.id = d.opened_by
             LEFT JOIN `users` against ON against.id = d.against_user_id
             WHERE d.contract_id = ? ORDER BY d.created_at DESC',
            [$contractId]
        );
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public static function adminList(array $filters, int $limit, int $offset): array
    {
        $conditions = ['1=1'];
        $params = [];

        if (!empty($filters['status'])) {
            $conditions[] = 'd.status = ?';
            $params[] = $filters['status'];
        }

        return Database::select(
            'SELECT d.*, ct.reference AS contract_reference, opener.name AS opened_by_name,
                    against.name AS against_name
             FROM `disputes` d
             INNER JOIN `contracts` ct ON ct.id = d.contract_id
             INNER JOIN `users` opener ON opener.id = d.opened_by
             LEFT JOIN `users` against ON against.id = d.against_user_id
             WHERE ' . implode(' AND ', $conditions) . '
             ORDER BY FIELD(d.status, \'open\', \'in_review\', \'resolved\', \'closed\'), d.created_at DESC
             LIMIT ' . max(1, min($limit, 100)) . ' OFFSET ' . max(0, $offset),
            $params
        );
    }

    public static function adminCount(array $filters): int
    {
        $conditions = ['1=1'];
        $params = [];
        if (!empty($filters['status'])) {
            $conditions[] = 'd.status = ?';
            $params[] = $filters['status'];
        }

        return (int) Database::scalar('SELECT COUNT(*) FROM `disputes` d WHERE ' . implode(' AND ', $conditions), $params);
    }

    public static function openCount(): int
    {
        return (int) Database::scalar('SELECT COUNT(*) FROM `disputes` WHERE `status` IN (\'open\', \'in_review\')');
    }
}
