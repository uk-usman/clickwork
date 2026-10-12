<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class Contract extends Model
{
    protected static string $table = 'contracts';

    public const STATUSES = ['awaiting_acceptance','active','in_progress','deliverable_submitted','revision_requested','completed','cancelled','disputed'];

    /**
     * @return array<string,mixed>|null
     */
    public static function detailed(int $id): ?array
    {
        return Database::selectOne(
            'SELECT ct.*, p.reference AS project_reference, p.title AS project_title,
                    p.description AS project_description, p.category_id, p.budget_type,
                    cl.name AS client_name, cl.email AS client_email, cl.status AS client_status,
                    clp.city AS client_city, clp.state AS client_state, clp.avatar_path AS client_avatar,
                    clp.company_name AS client_company,
                    fl.name AS freelancer_name, fl.email AS freelancer_email, fl.status AS freelancer_status,
                    flp.city AS freelancer_city, flp.state AS freelancer_state, flp.avatar_path AS freelancer_avatar,
                    flp.freelancer_title, c.name AS category_name
             FROM `contracts` ct
             INNER JOIN `projects` p ON p.id = ct.project_id
             INNER JOIN `users` cl ON cl.id = ct.client_id
             INNER JOIN `users` fl ON fl.id = ct.freelancer_id
             LEFT JOIN `user_profiles` clp ON clp.user_id = cl.id
             LEFT JOIN `user_profiles` flp ON flp.user_id = fl.id
             LEFT JOIN `categories` c ON c.id = p.category_id
             WHERE ct.id = ? LIMIT 1',
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
     * Contracts the user participates in.
     *
     * @return array<int,array<string,mixed>>
     */
    public static function forUser(int $userId, ?string $role = null, ?string $status = null): array
    {
        $sql = 'SELECT ct.*, p.title AS project_title, p.reference AS project_reference,
                       cl.name AS client_name, fl.name AS freelancer_name,
                       (SELECT COUNT(*) FROM deliverables d WHERE d.contract_id = ct.id) AS deliverable_count,
                       (SELECT COUNT(*) FROM messages m WHERE m.contract_id = ct.id AND m.recipient_id = ? AND m.read_at IS NULL) AS unread_messages
                FROM `contracts` ct
                INNER JOIN `projects` p ON p.id = ct.project_id
                INNER JOIN `users` cl ON cl.id = ct.client_id
                INNER JOIN `users` fl ON fl.id = ct.freelancer_id
                WHERE (ct.client_id = ? OR ct.freelancer_id = ?)';
        $params = [$userId, $userId, $userId];

        if ($role === 'client') {
            $sql .= ' AND ct.client_id = ?';
            $params[] = $userId;
        } elseif ($role === 'freelancer') {
            $sql .= ' AND ct.freelancer_id = ?';
            $params[] = $userId;
        }

        if ($status !== null) {
            $sql .= ' AND ct.status = ?';
            $params[] = $status;
        }

        $sql .= ' ORDER BY FIELD(ct.status, \'deliverable_submitted\', \'revision_requested\', \'awaiting_acceptance\', \'active\', \'in_progress\', \'disputed\', \'completed\', \'cancelled\'), ct.updated_at DESC';

        return Database::select($sql, $params);
    }

    public static function isParticipant(array $contract, int $userId): bool
    {
        return (int) $contract['client_id'] === $userId || (int) $contract['freelancer_id'] === $userId;
    }

    public static function roleOf(array $contract, int $userId): ?string
    {
        if ((int) $contract['client_id'] === $userId) {
            return 'client';
        }
        if ((int) $contract['freelancer_id'] === $userId) {
            return 'freelancer';
        }

        return null;
    }

    public static function counterpartyId(array $contract, int $userId): int
    {
        return (int) $contract['client_id'] === $userId
            ? (int) $contract['freelancer_id']
            : (int) $contract['client_id'];
    }
}
