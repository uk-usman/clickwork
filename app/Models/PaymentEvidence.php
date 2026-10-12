<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class PaymentEvidence extends Model
{
    protected static string $table = 'payment_evidence';

    /**
     * @return array<int,array<string,mixed>>
     */
    public static function forRecord(int $paymentRecordId): array
    {
        return Database::select(
            'SELECT pe.*, u.name AS uploaded_by_name
             FROM `payment_evidence` pe
             INNER JOIN `users` u ON u.id = pe.uploaded_by
             WHERE pe.payment_record_id = ? ORDER BY pe.created_at ASC',
            [$paymentRecordId]
        );
    }

    /**
     * @return array<string,mixed>|null
     */
    public static function withContract(int $id): ?array
    {
        return Database::selectOne(
            'SELECT pe.*, pr.contract_id, pr.payer_id, pr.payee_id, ct.client_id, ct.freelancer_id
             FROM `payment_evidence` pe
             INNER JOIN `payment_records` pr ON pr.id = pe.payment_record_id
             INNER JOIN `contracts` ct ON ct.id = pr.contract_id
             WHERE pe.id = ? LIMIT 1',
            [$id]
        );
    }
}
