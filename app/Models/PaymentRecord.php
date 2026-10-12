<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class PaymentRecord extends Model
{
    protected static string $table = 'payment_records';

    public const STATUSES = ['reported','confirmed','disputed','reversed'];

    /**
     * @return array<string,mixed>|null
     */
    public static function detailed(int $id): ?array
    {
        return Database::selectOne(
            'SELECT pr.*, ct.reference AS contract_reference, ct.client_id, ct.freelancer_id,
                    ct.status AS contract_status, ct.currency AS contract_currency,
                    payer.name AS payer_name, payee.name AS payee_name,
                    reporter.name AS reported_by_name, confirmer.name AS confirmed_by_name
             FROM `payment_records` pr
             INNER JOIN `contracts` ct ON ct.id = pr.contract_id
             INNER JOIN `users` payer ON payer.id = pr.payer_id
             INNER JOIN `users` payee ON payee.id = pr.payee_id
             INNER JOIN `users` reporter ON reporter.id = pr.reported_by
             LEFT JOIN `users` confirmer ON confirmer.id = pr.confirmed_by
             WHERE pr.id = ? LIMIT 1',
            [$id]
        );
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public static function forContract(int $contractId): array
    {
        return Database::select(
            'SELECT pr.*, payer.name AS payer_name, payee.name AS payee_name,
                    (SELECT COUNT(*) FROM payment_evidence pe WHERE pe.payment_record_id = pr.id) AS evidence_count
             FROM `payment_records` pr
             INNER JOIN `users` payer ON payer.id = pr.payer_id
             INNER JOIN `users` payee ON payee.id = pr.payee_id
             WHERE pr.contract_id = ? ORDER BY pr.created_at DESC',
            [$contractId]
        );
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public static function forUser(int $userId): array
    {
        return Database::select(
            'SELECT pr.*, ct.reference AS contract_reference, p.title AS project_title,
                    payer.name AS payer_name, payee.name AS payee_name
             FROM `payment_records` pr
             INNER JOIN `contracts` ct ON ct.id = pr.contract_id
             INNER JOIN `projects` p ON p.id = ct.project_id
             INNER JOIN `users` payer ON payer.id = pr.payer_id
             INNER JOIN `users` payee ON payee.id = pr.payee_id
             WHERE pr.payer_id = ? OR pr.payee_id = ?
             ORDER BY pr.created_at DESC',
            [$userId, $userId]
        );
    }

    /**
     * @param array<string,mixed> $filters
     * @return array<int,array<string,mixed>>
     */
    public static function adminList(array $filters, int $limit, int $offset): array
    {
        $conditions = ['1=1'];
        $params = [];

        if (!empty($filters['status'])) {
            $conditions[] = 'pr.status = ?';
            $params[] = $filters['status'];
        }
        if (!empty($filters['q'])) {
            $conditions[] = '(pr.reference LIKE ? OR ct.reference LIKE ? OR payer.email LIKE ? OR payee.email LIKE ?)';
            $like = '%' . $filters['q'] . '%';
            array_push($params, $like, $like, $like, $like);
        }

        return Database::select(
            'SELECT pr.*, ct.reference AS contract_reference, payer.name AS payer_name, payee.name AS payee_name
             FROM `payment_records` pr
             INNER JOIN `contracts` ct ON ct.id = pr.contract_id
             INNER JOIN `users` payer ON payer.id = pr.payer_id
             INNER JOIN `users` payee ON payee.id = pr.payee_id
             WHERE ' . implode(' AND ', $conditions) . '
             ORDER BY FIELD(pr.status, \'disputed\', \'reported\', \'confirmed\', \'reversed\'), pr.created_at DESC
             LIMIT ' . max(1, min($limit, 100)) . ' OFFSET ' . max(0, $offset),
            $params
        );
    }

    /**
     * @param array<string,mixed> $filters
     */
    public static function adminCount(array $filters): int
    {
        $conditions = ['1=1'];
        $params = [];

        if (!empty($filters['status'])) {
            $conditions[] = 'pr.status = ?';
            $params[] = $filters['status'];
        }
        if (!empty($filters['q'])) {
            $conditions[] = '(pr.reference LIKE ? OR ct.reference LIKE ?)';
            $like = '%' . $filters['q'] . '%';
            array_push($params, $like, $like);
        }

        return (int) Database::scalar(
            'SELECT COUNT(*) FROM `payment_records` pr INNER JOIN `contracts` ct ON ct.id = pr.contract_id
             WHERE ' . implode(' AND ', $conditions),
            $params
        );
    }

    public static function totalForContract(int $contractId, array $statuses = ['confirmed']): string
    {
        if ($statuses === []) {
            return '0.00';
        }
        $placeholders = implode(',', array_fill(0, count($statuses), '?'));
        $params = array_merge([$contractId], $statuses);

        $total = Database::scalar(
            'SELECT COALESCE(SUM(`amount`), 0) FROM `payment_records`
             WHERE `contract_id` = ? AND `status` IN (' . $placeholders . ')',
            $params
        );

        return number_format((float) $total, 2, '.', '');
    }
}
