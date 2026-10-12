<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Models\Contract;
use App\Models\Dispute;
use App\Models\DisputeMessage;

/**
 * Dispute tracking.
 *
 * CircleWork Africa records and reviews disputes between users. It does not
 * hold funds and cannot recover money paid directly between users.
 */
final class DisputeService
{
    /**
     * @return array{ok:bool,message:string,dispute_id?:int}
     */
    public static function open(int $contractId, int $userId, string $subject, string $reason): array
    {
        $contract = Contract::find($contractId);

        if ($contract === null) {
            return ['ok' => false, 'message' => 'Contract not found.'];
        }

        if (Contract::roleOf($contract, $userId) === null) {
            return ['ok' => false, 'message' => 'You are not a party to this contract.'];
        }

        if (trim($subject) === '' || mb_strlen(trim($reason)) < 20) {
            return ['ok' => false, 'message' => 'Please provide a subject and at least 20 characters describing the issue.'];
        }

        $open = Database::scalar(
            'SELECT COUNT(*) FROM `disputes` WHERE `contract_id` = ? AND `status` IN (\'open\', \'in_review\')',
            [$contractId]
        );

        if ((int) $open > 0) {
            return ['ok' => false, 'message' => 'There is already an open dispute for this contract.'];
        }

        $disputeId = Database::transaction(static function () use ($contractId, $userId, $subject, $reason, $contract): int {
            $id = Dispute::create([
                'reference'       => ProjectService::generateReference('DSP'),
                'contract_id'     => $contractId,
                'opened_by'       => $userId,
                'against_user_id' => Contract::counterpartyId($contract, $userId),
                'subject'         => mb_substr(trim($subject), 0, 200),
                'reason'          => mb_substr(trim($reason), 0, 2000),
                'status'          => 'open',
            ]);

            \App\Models\ContractEvent::record($contractId, $userId, 'dispute.opened', 'Dispute opened: ' . $subject);

            return $id;
        });

        AuditService::record('dispute.opened', 'Dispute opened', 'dispute', $disputeId, ['contract_id' => $contractId]);

        NotificationService::notify(
            Contract::counterpartyId($contract, $userId),
            'dispute_opened',
            'A dispute was opened',
            'A dispute was opened on contract ' . $contract['reference'] . ': ' . $subject,
            route('contracts.show', ['id' => $contractId])
        );

        return ['ok' => true, 'message' => 'Your dispute has been recorded. An administrator can review it.', 'dispute_id' => $disputeId];
    }

    /**
     * @return array{ok:bool,message:string}
     */
    public static function addMessage(int $disputeId, int $userId, string $body, bool $internal = false): array
    {
        $dispute = Dispute::detailed($disputeId);

        if ($dispute === null) {
            return ['ok' => false, 'message' => 'Dispute not found.'];
        }

        $isParty = in_array($userId, [(int) $dispute['client_id'], (int) $dispute['freelancer_id']], true);
        $isAdmin = \App\Core\Auth::isAdmin();

        if (!$isParty && !$isAdmin) {
            return ['ok' => false, 'message' => 'You do not have access to this dispute.'];
        }

        if (trim($body) === '') {
            return ['ok' => false, 'message' => 'Please enter a message.'];
        }

        DisputeMessage::create([
            'dispute_id'  => $disputeId,
            'sender_id'   => $userId,
            'body'        => mb_substr(trim($body), 0, 2000),
            'is_internal' => $isAdmin && $internal ? 1 : 0,
        ]);

        return ['ok' => true, 'message' => 'Message added to the dispute.'];
    }

    /**
     * @return array{ok:bool,message:string}
     */
    public static function resolve(int $disputeId, int $adminId, string $status, string $note): array
    {
        if (!in_array($status, ['open', 'in_review', 'resolved', 'closed'], true)) {
            return ['ok' => false, 'message' => 'Invalid status.'];
        }

        $dispute = Dispute::find($disputeId);

        if ($dispute === null) {
            return ['ok' => false, 'message' => 'Dispute not found.'];
        }

        $isClosed = in_array($status, ['resolved', 'closed'], true);

        Dispute::update($disputeId, [
            'status'          => $status,
            'resolution_note' => $note !== '' ? mb_substr($note, 0, 1500) : null,
            'resolved_by'     => $isClosed ? $adminId : null,
            'resolved_at'     => $isClosed ? gmdate('Y-m-d H:i:s') : null,
        ]);

        AuditService::record('dispute.' . $status, 'Dispute status changed to ' . $status, 'dispute', $disputeId, ['note' => $note]);

        NotificationService::notifyMany(
            [(int) $dispute['opened_by']],
            'dispute_update',
            'Update on your dispute',
            'Dispute ' . $dispute['reference'] . ' is now ' . str_replace('_', ' ', $status) . '.' . ($note !== '' ? ' Note: ' . $note : ''),
            route('contracts.show', ['id' => (int) $dispute['contract_id']])
        );

        return ['ok' => true, 'message' => 'Dispute updated.'];
    }
}
