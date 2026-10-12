<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class DeliverableFile extends Model
{
    protected static string $table = 'deliverable_files';

    /**
     * @return array<int,array<string,mixed>>
     */
    public static function forDeliverable(int $deliverableId): array
    {
        return self::allBy(['deliverable_id' => $deliverableId], 'id ASC');
    }

    /**
     * Resolve a file together with its contract, for permission checks.
     *
     * @return array<string,mixed>|null
     */
    public static function withContract(int $id): ?array
    {
        return Database::selectOne(
            'SELECT df.*, d.contract_id, ct.client_id, ct.freelancer_id
             FROM `deliverable_files` df
             INNER JOIN `deliverables` d ON d.id = df.deliverable_id
             INNER JOIN `contracts` ct ON ct.id = d.contract_id
             WHERE df.id = ? LIMIT 1',
            [$id]
        );
    }
}
