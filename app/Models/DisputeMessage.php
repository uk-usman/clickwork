<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class DisputeMessage extends Model
{
    protected static string $table = 'dispute_messages';

    /**
     * @return array<int,array<string,mixed>>
     */
    public static function forDispute(int $disputeId, bool $includeInternal = false): array
    {
        $sql = 'SELECT dm.*, u.name AS sender_name, u.role AS sender_role
                FROM `dispute_messages` dm
                INNER JOIN `users` u ON u.id = dm.sender_id
                WHERE dm.dispute_id = ?';
        if (!$includeInternal) {
            $sql .= ' AND dm.is_internal = 0';
        }
        $sql .= ' ORDER BY dm.created_at ASC, dm.id ASC';

        return Database::select($sql, [$disputeId]);
    }
}
