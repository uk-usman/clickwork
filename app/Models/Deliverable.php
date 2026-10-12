<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class Deliverable extends Model
{
    protected static string $table = 'deliverables';

    /**
     * @return array<int,array<string,mixed>>
     */
    public static function forContract(int $contractId): array
    {
        return Database::select(
            'SELECT d.*, u.name AS submitted_by_name, r.name AS reviewed_by_name
             FROM `deliverables` d
             INNER JOIN `users` u ON u.id = d.submitted_by
             LEFT JOIN `users` r ON r.id = d.reviewed_by
             WHERE d.contract_id = ? ORDER BY d.version DESC',
            [$contractId]
        );
    }

    /**
     * @return array<string,mixed>|null
     */
    public static function latestForContract(int $contractId): ?array
    {
        return Database::selectOne(
            'SELECT * FROM `deliverables` WHERE `contract_id` = ? ORDER BY `version` DESC LIMIT 1',
            [$contractId]
        );
    }

    /**
     * @return array<string,mixed>|null
     */
    public static function detailed(int $id): ?array
    {
        return Database::selectOne(
            'SELECT d.*, u.name AS submitted_by_name, ct.client_id, ct.freelancer_id, ct.reference AS contract_reference
             FROM `deliverables` d
             INNER JOIN `users` u ON u.id = d.submitted_by
             INNER JOIN `contracts` ct ON ct.id = d.contract_id
             WHERE d.id = ? LIMIT 1',
            [$id]
        );
    }

    public static function nextVersion(int $contractId): int
    {
        return (int) Database::scalar(
            'SELECT COALESCE(MAX(`version`), 0) + 1 FROM `deliverables` WHERE `contract_id` = ?',
            [$contractId]
        );
    }
}
