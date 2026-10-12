<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\App;
use App\Core\Database;
use App\Models\Contract;
use App\Models\PaymentEvidence;
use App\Models\PaymentRecord;

/**
 * Direct payment recording.
 *
 * CircleWork Africa does not hold funds, does not operate escrow and does not
 * move money between users. This service only records what the parties report
 * and lets the counterparty confirm or dispute it. A record whose status is
 * still Reported is never presented as independently verified.
 */
final class PaymentService
{
    public const METHODS = ['bank_transfer', 'cash', 'mobile_money', 'card_transfer', 'other'];

    /**
     * @param  array<string,mixed>           $input
     * @param  array<int,array<string,mixed>> $files
     * @return array{ok:bool,message:string,errors:array<string,string>,payment_id?:int}
     */
    public static function report(int $contractId, int $userId, array $input, array $files = []): array
    {
        $contract = Contract::find($contractId);

        if ($contract === null) {
            return ['ok' => false, 'message' => 'Contract not found.', 'errors' => []];
        }

        if (Contract::roleOf($contract, $userId) === null) {
            return ['ok' => false, 'message' => 'You are not a party to this contract.', 'errors' => []];
        }

        $errors = [];

        $amountRaw = trim((string) ($input['amount'] ?? ''));
        $method = (string) ($input['method'] ?? 'bank_transfer');
        $payerReference = trim((string) ($input['payer_reference'] ?? ''));
        $paidOn = trim((string) ($input['paid_on'] ?? ''));
        $note = trim((string) ($input['note'] ?? ''));

        if ($amountRaw === '' || !is_numeric($amountRaw) || (float) $amountRaw <= 0) {
            $errors['amount'] = 'Please enter the amount that was paid.';
        }

        if (!in_array($method, self::METHODS, true)) {
            $errors['method'] = 'Please choose a valid payment method.';
        }

        if ($paidOn !== '') {
            $parsed = \DateTimeImmutable::createFromFormat('Y-m-d', $paidOn);
            if ($parsed === false || $parsed->format('Y-m-d') !== $paidOn) {
                $errors['paid_on'] = 'Please enter a valid payment date.';
            } elseif ($paidOn > gmdate('Y-m-d')) {
                $errors['paid_on'] = 'The payment date cannot be in the future.';
            }
        }

        if ($errors !== []) {
            return ['ok' => false, 'message' => 'Please correct the highlighted fields.', 'errors' => $errors];
        }

        // Payer and payee are always derived from the contract, never from the browser.
        $paymentId = PaymentRecord::create([
            'reference'       => ProjectService::generateReference('PAY'),
            'contract_id'     => $contractId,
            'payer_id'        => (int) $contract['client_id'],
            'payee_id'        => (int) $contract['freelancer_id'],
            'amount'          => number_format((float) $amountRaw, 2, '.', ''),
            'currency'        => (string) $contract['currency'],
            'method'          => $method,
            'payer_reference' => $payerReference !== '' ? mb_substr($payerReference, 0, 120) : null,
            'paid_on'         => $paidOn !== '' ? $paidOn : null,
            'status'          => 'reported',
            'note'            => $note !== '' ? mb_substr($note, 0, 500) : null,
            'reported_by'     => $userId,
        ]);

        foreach ($files as $file) {
            if (!is_array($file) || (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
                continue;
            }

            $result = UploadService::store(
                $file,
                (string) App::configValue('storage.documents', sys_get_temp_dir()),
                UploadService::DOCUMENT_EXTENSIONS,
                (int) App::configValue('security.upload_max_bytes', 5242880)
            );

            if (!$result['ok']) {
                continue;
            }

            PaymentEvidence::create([
                'payment_record_id' => $paymentId,
                'uploaded_by'       => $userId,
                'original_name'     => $result['data']['original_name'],
                'stored_name'       => $result['data']['stored_name'],
                'mime_type'         => $result['data']['mime_type'],
                'size_bytes'        => $result['data']['size_bytes'],
                'checksum_sha256'   => $result['data']['checksum_sha256'],
                'note'              => 'Evidence uploaded with payment report',
            ]);
        }

        AuditService::record('payment.reported', 'Payment reported', 'payment_record', $paymentId, [
            'contract_id' => $contractId,
            'amount'      => number_format((float) $amountRaw, 2, '.', ''),
        ]);

        NotificationService::notify(
            Contract::counterpartyId($contract, $userId),
            'payment_reported',
            'Payment recorded as reported',
            'A payment of ' . money($amountRaw, (string) $contract['currency']) . ' was recorded as reported on contract ' . $contract['reference'] . '. Please confirm or dispute it.',
            route('contracts.payments', ['id' => $contractId])
        );

        return [
            'ok'         => true,
            'message'    => 'The payment has been recorded with status Reported. It is not verified until the other party confirms it.',
            'errors'     => [],
            'payment_id' => $paymentId,
        ];
    }

    /**
     * @return array{ok:bool,message:string}
     */
    public static function confirm(int $paymentId, int $userId): array
    {
        $payment = PaymentRecord::detailed($paymentId);

        if ($payment === null) {
            return ['ok' => false, 'message' => 'Payment record not found.'];
        }

        if (!in_array($userId, [(int) $payment['payer_id'], (int) $payment['payee_id']], true)) {
            return ['ok' => false, 'message' => 'You are not a party to this payment.'];
        }

        if ((string) $payment['status'] !== 'reported') {
            return ['ok' => false, 'message' => 'Only a payment with status Reported can be confirmed.'];
        }

        if ((int) $payment['reported_by'] === $userId) {
            return ['ok' => false, 'message' => 'The party who reported the payment cannot confirm it. The other party must confirm.'];
        }

        PaymentRecord::update($paymentId, [
            'status'       => 'confirmed',
            'confirmed_by' => $userId,
            'confirmed_at' => gmdate('Y-m-d H:i:s'),
        ]);

        AuditService::record('payment.confirmed', 'Payment confirmed by counterparty', 'payment_record', $paymentId);

        NotificationService::notify(
            (int) $payment['reported_by'],
            'payment_confirmed',
            'Payment confirmed',
            'The other party confirmed the payment of ' . money($payment['amount'], (string) $payment['currency']) . '.',
            route('contracts.payments', ['id' => (int) $payment['contract_id']])
        );

        return ['ok' => true, 'message' => 'Thank you. The payment is now marked as confirmed by the other party.'];
    }

    /**
     * @return array{ok:bool,message:string}
     */
    public static function dispute(int $paymentId, int $userId, string $reason): array
    {
        $payment = PaymentRecord::detailed($paymentId);

        if ($payment === null) {
            return ['ok' => false, 'message' => 'Payment record not found.'];
        }

        if (!in_array($userId, [(int) $payment['payer_id'], (int) $payment['payee_id']], true)) {
            return ['ok' => false, 'message' => 'You are not a party to this payment.'];
        }

        if ((string) $payment['status'] === 'reversed') {
            return ['ok' => false, 'message' => 'A reversed payment cannot be disputed.'];
        }

        if (mb_strlen(trim($reason)) < 10) {
            return ['ok' => false, 'message' => 'Please explain the issue (at least 10 characters).'];
        }

        PaymentRecord::update($paymentId, [
            'status'         => 'disputed',
            'disputed_by'    => $userId,
            'disputed_at'    => gmdate('Y-m-d H:i:s'),
            'dispute_reason' => mb_substr(trim($reason), 0, 500),
        ]);

        AuditService::record('payment.disputed', 'Payment record disputed', 'payment_record', $paymentId, ['reason' => $reason]);

        NotificationService::notify(
            Contract::counterpartyId($payment, $userId),
            'payment_disputed',
            'Payment record disputed',
            'A payment record was disputed: ' . $reason . ' An administrator can review this record. CircleWork cannot recover money transferred directly between users.',
            route('contracts.payments', ['id' => (int) $payment['contract_id']])
        );

        return ['ok' => true, 'message' => 'The payment record has been flagged as disputed and is available for administrative review. CircleWork cannot recover money transferred directly between users.'];
    }
}
