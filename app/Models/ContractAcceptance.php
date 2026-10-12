<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class ContractAcceptance extends Model
{
    protected static string $table = 'contract_acceptances';

    /**
     * @return array<int,array<string,mixed>>
     */
    public static function forVersion(int $contractId, int $version): array
    {
        return Database::select(
            'SELECT ca.*, u.name AS user_name, u.role AS user_role
             FROM `contract_acceptances` ca
             INNER JOIN `users` u ON u.id = ca.user_id
             WHERE ca.contract_id = ? AND ca.version = ? ORDER BY ca.accepted_at ASC',
            [$contractId, $version]
        );
    }

    public static function hasAccepted(int $contractId, int $version, int $userId): bool
    {
        return (int) Database::scalar(
            'SELECT COUNT(*) FROM `contract_acceptances` WHERE `contract_id` = ? AND `version` = ? AND `user_id` = ?',
            [$contractId, $version, $userId]
        ) > 0;
    }
}
