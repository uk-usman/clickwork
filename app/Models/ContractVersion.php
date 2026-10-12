<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class ContractVersion extends Model
{
    protected static string $table = 'contract_versions';

    /**
     * @return array<string,mixed>|null
     */
    public static function forVersion(int $contractId, int $version): ?array
    {
        return self::findBy(['contract_id' => $contractId, 'version' => $version]);
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public static function history(int $contractId): array
    {
        return Database::select(
            'SELECT cv.*, u.name AS created_by_name
             FROM `contract_versions` cv
             LEFT JOIN `users` u ON u.id = cv.created_by
             WHERE cv.contract_id = ? ORDER BY cv.version DESC',
            [$contractId]
        );
    }

    public static function latestVersionNumber(int $contractId): int
    {
        return (int) Database::scalar(
            'SELECT COALESCE(MAX(`version`), 0) FROM `contract_versions` WHERE `contract_id` = ?',
            [$contractId]
        );
    }
}
