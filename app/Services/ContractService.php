<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Core\Logger;
use App\Models\Contract;
use App\Models\ContractAcceptance;
use App\Models\ContractEvent;
use App\Models\ContractVersion;
use App\Models\Project;
use App\Models\Proposal;

/**
 * Contract creation, versioning and dual acceptance.
 *
 * Legal note: a recorded acceptance (authenticated account, timestamp, version
 * and SHA-256 hash of the terms) is evidence of what was agreed on this
 * platform. Whether that is legally enforceable depends on applicable law and
 * the circumstances; CircleWork Africa does not claim to guarantee it.
 */
final class ContractService
{
    /**
     * Award a proposal and create the contract. Runs in a transaction and
     * guards against two proposals being accepted for the same project.
     *
     * @return array{ok:bool,message:string,contract_id?:int}
     */
    public static function awardProposal(int $proposalId, int $clientId, array $terms = []): array
    {
        try {
            return Database::transaction(static function () use ($proposalId, $clientId, $terms): array {
                // Lock the project row so concurrent awards cannot both succeed.
                $proposal = Database::selectOne(
                    'SELECT pr.*, p.client_id, p.status AS project_status, p.title AS project_title,
                            p.description AS project_description, p.expected_deliverables, p.deadline AS project_deadline,
                            p.currency AS project_currency, p.reference AS project_reference
                     FROM `proposals` pr
                     INNER JOIN `projects` p ON p.id = pr.project_id
                     WHERE pr.id = ? FOR UPDATE',
                    [$proposalId]
                );

                if ($proposal === null) {
                    return ['ok' => false, 'message' => 'Proposal not found.'];
                }

                if ((int) $proposal['client_id'] !== $clientId) {
                    return ['ok' => false, 'message' => 'You do not have permission to award this proposal.'];
                }

                if ((string) $proposal['status'] === 'withdrawn') {
                    return ['ok' => false, 'message' => 'This proposal was withdrawn by the freelancer.'];
                }

                if ((string) $proposal['status'] === 'accepted') {
                    return ['ok' => false, 'message' => 'This proposal has already been accepted.'];
                }

                $projectId = (int) $proposal['project_id'];

                // Serialise awards for this project.
                Database::run('SELECT `id` FROM `projects` WHERE `id` = ? FOR UPDATE', [$projectId]);

                $alreadyAccepted = Database::selectOne(
                    'SELECT `id` FROM `proposals` WHERE `project_id` = ? AND `status` = \'accepted\' LIMIT 1',
                    [$projectId]
                );

                if ($alreadyAccepted !== null) {
                    return ['ok' => false, 'message' => 'Another proposal has already been accepted for this project.'];
                }

                $existingContract = Database::selectOne(
                    'SELECT `id` FROM `contracts` WHERE `project_id` = ? LIMIT 1',
                    [$projectId]
                );

                if ($existingContract !== null) {
                    return ['ok' => false, 'message' => 'A contract already exists for this project.'];
                }

                $reference = ProjectService::generateReference('CTR');
                $amount = number_format((float) $proposal['bid_amount'], 2, '.', '');

                $contractId = Contract::create([
                    'reference'          => $reference,
                    'project_id'         => $projectId,
                    'proposal_id'        => $proposalId,
                    'client_id'          => $clientId,
                    'freelancer_id'      => (int) $proposal['freelancer_id'],
                    'title'              => (string) $proposal['project_title'],
                    'scope'              => (string) ($terms['scope'] ?? $proposal['project_description']),
                    'deliverables'       => (string) ($terms['deliverables'] ?? ($proposal['expected_deliverables'] ?? 'As described in the project brief.')),
                    'amount'             => $amount,
                    'currency'           => (string) $proposal['project_currency'],
                    'deadline'           => $terms['deadline'] ?? $proposal['project_deadline'],
                    'revision_terms'     => (string) ($terms['revision_terms'] ?? 'Two rounds of reasonable revisions included.'),
                    'payment_terms'      => (string) ($terms['payment_terms'] ?? 'Direct payment agreed between client and freelancer outside the platform.'),
                    'cancellation_terms' => (string) ($terms['cancellation_terms'] ?? 'Either party may request cancellation before work begins; recorded on the platform.'),
                    'dispute_terms'      => (string) ($terms['dispute_terms'] ?? 'Disputes are recorded here and reviewed by an administrator. CircleWork cannot recover funds paid directly between users.'),
                    'current_version'    => 1,
                    'status'             => 'awaiting_acceptance',
                ]);

                Proposal::update($proposalId, ['status' => 'accepted', 'decided_at' => gmdate('Y-m-d H:i:s')]);

                // Reject all other pending proposals for this single-award project.
                Database::affected(
                    'UPDATE `proposals` SET `status` = \'rejected\', `decided_at` = UTC_TIMESTAMP()
                     WHERE `project_id` = ? AND `id` <> ? AND `status` IN (\'submitted\', \'shortlisted\')',
                    [$projectId, $proposalId]
                );

                Project::update($projectId, ['status' => 'in_progress']);

                $versionId = self::createVersion($contractId, $clientId, 'Initial contract terms created from the accepted proposal.');

                ContractEvent::record($contractId, $clientId, 'contract.created', 'Contract created from accepted proposal ' . $proposal['reference']);
                ContractEvent::record($contractId, $clientId, 'contract.version_created', 'Contract version 1 created', ['version_id' => $versionId]);

                return ['ok' => true, 'message' => 'Proposal accepted. A contract has been created.', 'contract_id' => $contractId];
            });
        } catch (\Throwable $e) {
            Logger::error('Contract award failed: ' . $e->getMessage());
            return ['ok' => false, 'message' => 'The contract could not be created. Please try again.'];
        }
    }

    /**
     * Create a new immutable version snapshot of the current contract terms.
     */
    public static function createVersion(int $contractId, ?int $actorId, string $changeSummary = ''): int
    {
        $contract = Contract::find($contractId);

        if ($contract === null) {
            throw new \RuntimeException('Contract not found while creating version.');
        }

        $version = ContractVersion::latestVersionNumber($contractId) + 1;
        $terms = self::canonicalTerms($contract);

        $versionId = ContractVersion::create([
            'contract_id'    => $contractId,
            'version'        => $version,
            'terms_snapshot' => json_encode($terms, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
            'terms_hash'     => self::hashTerms($terms),
            'change_summary' => $changeSummary !== '' ? mb_substr($changeSummary, 0, 500) : null,
            'created_by'     => $actorId,
        ]);

        Contract::update($contractId, ['current_version' => $version]);

        return $versionId;
    }

    /**
     * Canonical representation used for hashing. Key order is fixed so the same
     * terms always produce the same hash.
     *
     * @param  array<string,mixed> $contract
     * @return array<string,string|null>
     */
    public static function canonicalTerms(array $contract): array
    {
        return [
            'contract_reference'  => (string) $contract['reference'],
            'project_reference'   => (string) ($contract['project_reference'] ?? ''),
            'client_id'           => (string) $contract['client_id'],
            'freelancer_id'       => (string) $contract['freelancer_id'],
            'title'               => (string) $contract['title'],
            'scope'               => (string) $contract['scope'],
            'deliverables'        => (string) $contract['deliverables'],
            'amount'              => number_format((float) $contract['amount'], 2, '.', ''),
            'currency'            => (string) $contract['currency'],
            'deadline'            => $contract['deadline'] !== null ? (string) $contract['deadline'] : null,
            'revision_terms'      => (string) $contract['revision_terms'],
            'payment_terms'       => (string) $contract['payment_terms'],
            'cancellation_terms'  => (string) $contract['cancellation_terms'],
            'dispute_terms'       => (string) $contract['dispute_terms'],
        ];
    }

    /**
     * @param array<string,mixed> $terms
     */
    public static function hashTerms(array $terms): string
    {
        return hash('sha256', json_encode($terms, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }

    /**
     * Record an authenticated acceptance of a specific contract version.
     *
     * @return array{ok:bool,message:string}
     */
    public static function accept(int $contractId, int $userId, string $ip, string $userAgent): array
    {
        return Database::transaction(static function () use ($contractId, $userId, $ip, $userAgent): array {
            $contract = Database::selectOne('SELECT * FROM `contracts` WHERE `id` = ? FOR UPDATE', [$contractId]);

            if ($contract === null) {
                return ['ok' => false, 'message' => 'Contract not found.'];
            }

            $role = Contract::roleOf($contract, $userId);

            if ($role === null) {
                Logger::security('Unauthorised contract acceptance attempt', ['contract_id' => $contractId, 'user_id' => $userId]);
                return ['ok' => false, 'message' => 'You are not a party to this contract.'];
            }

            if (!in_array((string) $contract['status'], ['awaiting_acceptance'], true)) {
                return ['ok' => false, 'message' => 'This contract is not awaiting acceptance.'];
            }

            $version = (int) $contract['current_version'];
            $versionRow = ContractVersion::forVersion($contractId, $version);

            if ($versionRow === null) {
                return ['ok' => false, 'message' => 'The contract terms version could not be located.'];
            }

            if (ContractAcceptance::hasAccepted($contractId, $version, $userId)) {
                return ['ok' => false, 'message' => 'You have already accepted this contract version.'];
            }

            ContractAcceptance::create([
                'contract_id'         => $contractId,
                'contract_version_id' => (int) $versionRow['id'],
                'version'             => $version,
                'user_id'             => $userId,
                'party_role'          => $role,
                'terms_hash'          => (string) $versionRow['terms_hash'],
                'ip_address'          => $ip,
                'user_agent'          => mb_substr($userAgent, 0, 255),
            ]);

            ContractEvent::record($contractId, $userId, 'contract.accepted', ucfirst($role) . ' accepted contract version ' . $version, ['version' => $version]);

            $column = $role === 'client' ? 'client_accepted_at' : 'freelancer_accepted_at';
            Contract::update($contractId, [$column => gmdate('Y-m-d H:i:s')]);

            $acceptances = ContractAcceptance::forVersion($contractId, $version);
            $acceptedRoles = array_map(static fn (array $a): string => (string) $a['party_role'], $acceptances);

            $bothAccepted = in_array('client', $acceptedRoles, true) && in_array('freelancer', $acceptedRoles, true);

            if ($bothAccepted) {
                Contract::update($contractId, [
                    'status'       => 'active',
                    'activated_at' => gmdate('Y-m-d H:i:s'),
                ]);

                ContractEvent::record($contractId, null, 'contract.activated', 'Both parties accepted version ' . $version . '. Contract is now active.');

                NotificationService::notifyMany(
                    [(int) $contract['client_id'], (int) $contract['freelancer_id']],
                    'contract_activated',
                    'Contract is now active',
                    'Both parties accepted contract ' . $contract['reference'] . '. Work can begin.',
                    route('contracts.show', ['id' => $contractId])
                );
            } else {
                $otherId = Contract::counterpartyId($contract, $userId);
                NotificationService::notify(
                    $otherId,
                    'contract_acceptance',
                    'Contract awaiting your acceptance',
                    ucfirst($role) . ' accepted contract ' . $contract['reference'] . '. Please review and accept to activate it.',
                    route('contracts.show', ['id' => $contractId])
                );
            }

            AuditService::record('contract.accepted', 'Contract version ' . $version . ' accepted', 'contract', $contractId, ['version' => $version, 'role' => $role]);

            return [
                'ok'      => true,
                'message' => $bothAccepted
                    ? 'Contract accepted. Both parties have agreed, so the contract is now active.'
                    : 'Thank you. Your acceptance has been recorded. The contract becomes active when both parties accept.',
            ];
        });
    }

    /**
     * Amend an active contract. The accepted terms are never edited in place:
     * a new version is created and both parties must accept it again.
     *
     * @param  array<string,mixed> $changes
     * @return array{ok:bool,message:string}
     */
    public static function amend(int $contractId, int $actorId, array $changes, string $summary): array
    {
        $contract = Contract::find($contractId);

        if ($contract === null) {
            return ['ok' => false, 'message' => 'Contract not found.'];
        }

        if (Contract::roleOf($contract, $actorId) === null) {
            return ['ok' => false, 'message' => 'You are not a party to this contract.'];
        }

        if (in_array((string) $contract['status'], ['completed', 'cancelled'], true)) {
            return ['ok' => false, 'message' => 'A completed or cancelled contract cannot be amended.'];
        }

        $allowed = ['scope', 'deliverables', 'amount', 'deadline', 'revision_terms', 'payment_terms', 'cancellation_terms', 'dispute_terms'];
        $update = [];

        foreach ($allowed as $field) {
            if (!array_key_exists($field, $changes)) {
                continue;
            }
            $value = $changes[$field];
            if ($field === 'amount') {
                if (!is_numeric($value) || (float) $value <= 0) {
                    return ['ok' => false, 'message' => 'The amended amount must be a positive number.'];
                }
                $update[$field] = number_format((float) $value, 2, '.', '');
            } elseif ($field === 'deadline') {
                $value = trim((string) $value);
                $update[$field] = $value !== '' ? $value : null;
            } else {
                $update[$field] = trim((string) $value);
            }
        }

        if ($update === []) {
            return ['ok' => false, 'message' => 'No changes were provided.'];
        }

        Database::transaction(static function () use ($contractId, $actorId, $update, $summary): void {
            Contract::update($contractId, $update);

            self::createVersion($contractId, $actorId, $summary);

            Contract::update($contractId, [
                'status'                 => 'awaiting_acceptance',
                'client_accepted_at'     => null,
                'freelancer_accepted_at' => null,
            ]);
        });

        $fresh = Contract::find($contractId);
        $version = $fresh !== null ? (int) $fresh['current_version'] : 0;

        ContractEvent::record($contractId, $actorId, 'contract.amended', 'Contract amended: ' . ($summary !== '' ? $summary : 'terms updated'), ['version' => $version]);

        NotificationService::notifyMany(
            [(int) $contract['client_id'], (int) $contract['freelancer_id']],
            'contract_amended',
            'Contract terms updated',
            'Contract ' . $contract['reference'] . ' has a new version (' . $version . '). Both parties must accept the updated terms.',
            route('contracts.show', ['id' => $contractId])
        );

        AuditService::record('contract.amended', 'Contract amended to version ' . $version, 'contract', $contractId);

        return ['ok' => true, 'message' => 'A new contract version has been created. Both parties must accept the updated terms.'];
    }

    /**
     * @return array{ok:bool,message:string}
     */
    public static function cancel(int $contractId, int $actorId, string $reason): array
    {
        $contract = Contract::find($contractId);

        if ($contract === null) {
            return ['ok' => false, 'message' => 'Contract not found.'];
        }

        if (Contract::roleOf($contract, $actorId) === null) {
            return ['ok' => false, 'message' => 'You are not a party to this contract.'];
        }

        if (in_array((string) $contract['status'], ['completed', 'cancelled'], true)) {
            return ['ok' => false, 'message' => 'This contract is already ' . $contract['status'] . '.'];
        }

        if (trim($reason) === '') {
            return ['ok' => false, 'message' => 'Please provide a reason for cancelling the contract.'];
        }

        Database::transaction(static function () use ($contractId, $actorId, $reason, $contract): void {
            Contract::update($contractId, [
                'status'              => 'cancelled',
                'cancelled_at'        => gmdate('Y-m-d H:i:s'),
                'cancelled_by'        => $actorId,
                'cancellation_reason' => mb_substr($reason, 0, 500),
            ]);

            Project::update((int) $contract['project_id'], ['status' => 'cancelled']);

            ContractEvent::record($contractId, $actorId, 'contract.cancelled', 'Contract cancelled: ' . $reason);
        });

        NotificationService::notify(
            Contract::counterpartyId($contract, $actorId),
            'contract_cancelled',
            'Contract cancelled',
            'Contract ' . $contract['reference'] . ' was cancelled. Reason: ' . $reason,
            route('contracts.show', ['id' => $contractId])
        );

        AuditService::record('contract.cancelled', 'Contract cancelled', 'contract', $contractId, ['reason' => $reason]);

        return ['ok' => true, 'message' => 'The contract has been cancelled.'];
    }
}
