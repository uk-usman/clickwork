<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\App;
use App\Core\Database;
use App\Models\Contract;
use App\Models\Deliverable;
use App\Models\DeliverableFile;
use App\Models\Project;

/**
 * Deliverable submission, revision requests and approval.
 */
final class DeliverableService
{
    /**
     * @param  array<string,mixed>           $input
     * @param  array<int,array<string,mixed>> $files  $_FILES entries keyed by input name
     * @return array{ok:bool,message:string,errors:array<string,string>,deliverable_id?:int}
     */
    public static function submit(int $contractId, int $userId, array $input, array $files): array
    {
        $contract = Contract::find($contractId);

        if ($contract === null) {
            return ['ok' => false, 'message' => 'Contract not found.', 'errors' => []];
        }

        if ((int) $contract['freelancer_id'] !== $userId) {
            return ['ok' => false, 'message' => 'Only the assigned freelancer can submit deliverables.', 'errors' => []];
        }

        if (!in_array((string) $contract['status'], ['active', 'in_progress', 'revision_requested'], true)) {
            return ['ok' => false, 'message' => 'Deliverables cannot be submitted in the current contract state.', 'errors' => []];
        }

        $message = trim((string) ($input['message'] ?? ''));

        if (mb_strlen($message) < 10) {
            return ['ok' => false, 'message' => 'Please add a short note describing what you are submitting.', 'errors' => ['message' => 'Please describe your submission (at least 10 characters).']];
        }

        $stored = [];
        $errors = [];

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
                $errors[] = ($file['name'] ?? 'file') . ': ' . ($result['error'] ?? 'Upload failed.');
                continue;
            }

            $stored[] = $result['data'];
        }

        if ($errors !== []) {
            foreach ($stored as $item) {
                UploadService::delete((string) App::configValue('storage.documents', ''), (string) $item['stored_name']);
            }

            return ['ok' => false, 'message' => 'Some files could not be uploaded.', 'errors' => ['files' => implode(' ', $errors)]];
        }

        $deliverableId = Database::transaction(static function () use ($contractId, $userId, $message, $stored, $contract): int {
            $version = Deliverable::nextVersion($contractId);

            // Any earlier submitted deliverable is superseded by this submission.
            Database::affected(
                'UPDATE `deliverables` SET `status` = \'superseded\'
                 WHERE `contract_id` = ? AND `status` IN (\'submitted\', \'revision_requested\')',
                [$contractId]
            );

            $id = Deliverable::create([
                'contract_id'  => $contractId,
                'submitted_by' => $userId,
                'version'      => $version,
                'message'      => $message,
                'status'       => 'submitted',
            ]);

            foreach ($stored as $item) {
                DeliverableFile::create([
                    'deliverable_id'  => $id,
                    'uploaded_by'     => $userId,
                    'original_name'   => $item['original_name'],
                    'stored_name'     => $item['stored_name'],
                    'mime_type'       => $item['mime_type'],
                    'size_bytes'      => $item['size_bytes'],
                    'checksum_sha256' => $item['checksum_sha256'],
                ]);
            }

            Contract::update($contractId, ['status' => 'deliverable_submitted']);

            \App\Models\ContractEvent::record(
                $contractId,
                $userId,
                'deliverable.submitted',
                'Deliverable version ' . $version . ' submitted' . ($stored !== [] ? ' with ' . count($stored) . ' file(s)' : '')
            );

            return $id;
        });

        AuditService::record('deliverable.submitted', 'Deliverable submitted', 'deliverable', $deliverableId, ['contract_id' => $contractId]);

        NotificationService::notify(
            (int) $contract['client_id'],
            'deliverable_submitted',
            'New deliverable submitted',
            'A deliverable has been submitted for contract ' . $contract['reference'] . '. Please review it.',
            route('contracts.show', ['id' => $contractId])
        );

        return ['ok' => true, 'message' => 'Your deliverable has been submitted for review.', 'errors' => [], 'deliverable_id' => $deliverableId];
    }

    /**
     * @return array{ok:bool,message:string}
     */
    public static function requestRevision(int $deliverableId, int $clientId, string $note): array
    {
        $deliverable = Deliverable::detailed($deliverableId);

        if ($deliverable === null || (int) $deliverable['client_id'] !== $clientId) {
            return ['ok' => false, 'message' => 'Deliverable not found for your contract.'];
        }

        if ((string) $deliverable['status'] !== 'submitted') {
            return ['ok' => false, 'message' => 'Only a submitted deliverable can be sent back for revision.'];
        }

        if (mb_strlen(trim($note)) < 10) {
            return ['ok' => false, 'message' => 'Please explain what needs to be revised (at least 10 characters).'];
        }

        $contractId = (int) $deliverable['contract_id'];

        Database::transaction(static function () use ($deliverableId, $clientId, $note, $contractId): void {
            Deliverable::update($deliverableId, [
                'status'      => 'revision_requested',
                'reviewed_by' => $clientId,
                'reviewed_at' => gmdate('Y-m-d H:i:s'),
                'review_note' => mb_substr($note, 0, 1000),
            ]);

            Contract::update($contractId, ['status' => 'revision_requested']);

            \App\Models\ContractEvent::record($contractId, $clientId, 'deliverable.revision_requested', 'Revision requested: ' . $note);
        });

        $contract = Contract::find($contractId);

        if ($contract !== null) {
            NotificationService::notify(
                (int) $contract['freelancer_id'],
                'revision_requested',
                'Revision requested',
                'The client requested a revision: ' . $note,
                route('contracts.show', ['id' => $contractId])
            );
        }

        AuditService::record('deliverable.revision_requested', 'Revision requested by client', 'deliverable', $deliverableId);

        return ['ok' => true, 'message' => 'A revision request has been sent to the freelancer.'];
    }

    /**
     * @return array{ok:bool,message:string}
     */
    public static function approve(int $deliverableId, int $clientId): array
    {
        $deliverable = Deliverable::detailed($deliverableId);

        if ($deliverable === null || (int) $deliverable['client_id'] !== $clientId) {
            return ['ok' => false, 'message' => 'Deliverable not found for your contract.'];
        }

        if ((string) $deliverable['status'] !== 'submitted') {
            return ['ok' => false, 'message' => 'Only a submitted deliverable can be approved.'];
        }

        $contractId = (int) $deliverable['contract_id'];

        Database::transaction(static function () use ($deliverableId, $clientId, $contractId, $deliverable): void {
            Deliverable::update($deliverableId, [
                'status'      => 'approved',
                'reviewed_by' => $clientId,
                'reviewed_at' => gmdate('Y-m-d H:i:s'),
            ]);

            Contract::update($contractId, ['status' => 'active']);

            \App\Models\ContractEvent::record($contractId, $clientId, 'deliverable.approved', 'Deliverable version ' . $deliverable['version'] . ' approved by client');
        });

        $contract = Contract::find($contractId);

        if ($contract !== null) {
            NotificationService::notify(
                (int) $contract['freelancer_id'],
                'deliverable_approved',
                'Deliverable approved',
                'Your deliverable was approved. The client can now mark the project complete.',
                route('contracts.show', ['id' => $contractId])
            );
        }

        AuditService::record('deliverable.approved', 'Deliverable approved by client', 'deliverable', $deliverableId);

        return ['ok' => true, 'message' => 'Deliverable approved. You can now mark the contract as completed.'];
    }

    /**
     * @return array{ok:bool,message:string}
     */
    public static function completeContract(int $contractId, int $clientId): array
    {
        $contract = Contract::find($contractId);

        if ($contract === null || (int) $contract['client_id'] !== $clientId) {
            return ['ok' => false, 'message' => 'Contract not found.'];
        }

        if ((string) $contract['status'] === 'completed') {
            return ['ok' => false, 'message' => 'This contract is already completed.'];
        }

        if (in_array((string) $contract['status'], ['cancelled', 'disputed'], true)) {
            return ['ok' => false, 'message' => 'This contract cannot be completed in its current state.'];
        }

        $approved = Database::scalar(
            'SELECT COUNT(*) FROM `deliverables` WHERE `contract_id` = ? AND `status` = \'approved\'',
            [$contractId]
        );

        if ((int) $approved === 0) {
            return ['ok' => false, 'message' => 'Please approve at least one deliverable before marking the contract complete.'];
        }

        Database::transaction(static function () use ($contractId, $clientId, $contract): void {
            Contract::update($contractId, [
                'status'       => 'completed',
                'completed_at' => gmdate('Y-m-d H:i:s'),
            ]);

            Project::update((int) $contract['project_id'], ['status' => 'completed']);

            \App\Models\ContractEvent::record($contractId, $clientId, 'contract.completed', 'Contract marked complete by client');

            \App\Models\User::recalculateCompletedContracts((int) $contract['client_id']);
            \App\Models\User::recalculateCompletedContracts((int) $contract['freelancer_id']);
        });

        NotificationService::notify(
            (int) $contract['freelancer_id'],
            'contract_completed',
            'Contract completed',
            'Contract ' . $contract['reference'] . ' has been marked complete. You can now leave a review.',
            route('contracts.show', ['id' => $contractId])
        );

        AuditService::record('contract.completed', 'Contract marked complete by client', 'contract', $contractId);

        return ['ok' => true, 'message' => 'The contract has been marked as completed. Reviews are now available.'];
    }
}
