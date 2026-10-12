<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class ContractEvent extends Model
{
    protected static string $table = 'contract_events';

    /**
     * @param array<string,mixed> $metadata
     */
    public static function record(int $contractId, ?int $actorId, string $type, string $description, array $metadata = []): int
    {
        return self::create([
            'contract_id' => $contractId,
            'actor_id'    => $actorId,
            'event_type'  => $type,
            'description' => $description,
            'metadata'    => $metadata === [] ? null : json_encode($metadata, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
        ]);
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public static function timeline(int $contractId, int $limit = 100): array
    {
        return Database::select(
            'SELECT ce.*, u.name AS actor_name
             FROM `contract_events` ce
             LEFT JOIN `users` u ON u.id = ce.actor_id
             WHERE ce.contract_id = ? ORDER BY ce.created_at DESC, ce.id DESC LIMIT ' . max(1, min($limit, 200)),
            [$contractId]
        );
    }
}
