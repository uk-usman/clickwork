<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class AuditLog extends Model
{
    protected static string $table = 'audit_logs';

    /**
     * @param array<string,mixed> $metadata
     */
    public static function record(
        ?int $actorId,
        ?string $actorRole,
        string $action,
        string $description,
        ?string $entityType = null,
        ?int $entityId = null,
        array $metadata = [],
        ?string $ip = null,
        ?string $userAgent = null
    ): int {
        return self::create([
            'actor_id'    => $actorId,
            'actor_role'  => $actorRole,
            'action'      => $action,
            'entity_type' => $entityType,
            'entity_id'   => $entityId,
            'description' => $description,
            'metadata'    => $metadata === [] ? null : json_encode($metadata, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            'ip_address'  => $ip,
            'user_agent'  => $userAgent,
        ]);
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public static function listing(array $filters, int $limit, int $offset): array
    {
        $conditions = ['1=1'];
        $params = [];

        if (!empty($filters['action'])) {
            $conditions[] = 'al.action = ?';
            $params[] = $filters['action'];
        }
        if (!empty($filters['actor_id'])) {
            $conditions[] = 'al.actor_id = ?';
            $params[] = (int) $filters['actor_id'];
        }
        if (!empty($filters['q'])) {
            $conditions[] = '(al.description LIKE ? OR u.email LIKE ?)';
            $like = '%' . $filters['q'] . '%';
            array_push($params, $like, $like);
        }

        return Database::select(
            'SELECT al.*, u.name AS actor_name, u.email AS actor_email
             FROM `audit_logs` al
             LEFT JOIN `users` u ON u.id = al.actor_id
             WHERE ' . implode(' AND ', $conditions) . '
             ORDER BY al.created_at DESC, al.id DESC
             LIMIT ' . max(1, min($limit, 200)) . ' OFFSET ' . max(0, $offset),
            $params
        );
    }

    public static function listingCount(array $filters): int
    {
        $conditions = ['1=1'];
        $params = [];

        if (!empty($filters['action'])) {
            $conditions[] = 'al.action = ?';
            $params[] = $filters['action'];
        }
        if (!empty($filters['actor_id'])) {
            $conditions[] = 'al.actor_id = ?';
            $params[] = (int) $filters['actor_id'];
        }
        if (!empty($filters['q'])) {
            $conditions[] = '(al.description LIKE ? OR u.email LIKE ?)';
            $like = '%' . $filters['q'] . '%';
            array_push($params, $like, $like);
        }

        return (int) Database::scalar(
            'SELECT COUNT(*) FROM `audit_logs` al LEFT JOIN `users` u ON u.id = al.actor_id WHERE ' . implode(' AND ', $conditions),
            $params
        );
    }
}
