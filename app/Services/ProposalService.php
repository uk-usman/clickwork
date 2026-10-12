<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Models\Project;
use App\Models\Proposal;
use App\Models\User;

/**
 * Freelancer proposals and client hiring decisions.
 */
final class ProposalService
{
    /**
     * @param  array<string,mixed> $input
     * @return array{ok:bool,errors:array<string,string>,proposal_id?:int}
     */
    public static function submit(int $freelancerId, int $projectId, array $input): array
    {
        $errors = [];

        $project = Project::find($projectId);

        if ($project === null) {
            return ['ok' => false, 'errors' => ['project' => 'Project not found.']];
        }

        if (!in_array((string) $project['status'], ['published', 'in_progress'], true)) {
            return ['ok' => false, 'errors' => ['project' => 'This project is not open for proposals.']];
        }

        if ((int) $project['client_id'] === $freelancerId) {
            return ['ok' => false, 'errors' => ['project' => 'You cannot submit a proposal on your own project.']];
        }

        if (Proposal::existsFor($projectId, $freelancerId)) {
            return ['ok' => false, 'errors' => ['project' => 'You have already submitted a proposal for this project.']];
        }

        if (Proposal::acceptedForProject($projectId) !== null) {
            return ['ok' => false, 'errors' => ['project' => 'This project has already been awarded.']];
        }

        $coverLetter = trim((string) ($input['cover_letter'] ?? ''));
        $bidAmountRaw = trim((string) ($input['bid_amount'] ?? ''));
        $estimatedDaysRaw = trim((string) ($input['estimated_days'] ?? ''));

        if (mb_strlen($coverLetter) < 40) {
            $errors['cover_letter'] = 'Please write at least 40 characters explaining your approach.';
        } elseif (mb_strlen($coverLetter) > 5000) {
            $errors['cover_letter'] = 'Your proposal must not exceed 5000 characters.';
        }

        if ($bidAmountRaw === '' || !is_numeric($bidAmountRaw) || (float) $bidAmountRaw <= 0) {
            $errors['bid_amount'] = 'Please enter a valid bid amount greater than zero.';
        }

        if ($estimatedDaysRaw === '' || filter_var($estimatedDaysRaw, FILTER_VALIDATE_INT) === false || (int) $estimatedDaysRaw <= 0) {
            $errors['estimated_days'] = 'Please enter the estimated delivery time in days.';
        } elseif ((int) $estimatedDaysRaw > 3650) {
            $errors['estimated_days'] = 'Estimated delivery time is unrealistically long.';
        }

        if ($errors !== []) {
            return ['ok' => false, 'errors' => $errors];
        }

        $proposalId = Database::transaction(static function () use ($projectId, $freelancerId, $coverLetter, $bidAmountRaw, $estimatedDaysRaw, $project): int {
            $id = Proposal::create([
                'reference'      => ProjectService::generateReference('PRO'),
                'project_id'     => $projectId,
                'freelancer_id'  => $freelancerId,
                'cover_letter'   => $coverLetter,
                'bid_amount'     => number_format((float) $bidAmountRaw, 2, '.', ''),
                'currency'       => (string) $project['currency'],
                'estimated_days' => (int) $estimatedDaysRaw,
                'status'         => 'submitted',
            ]);

            Project::refreshProposalCount($projectId);

            return $id;
        });

        $freelancer = User::find($freelancerId);

        AuditService::record('proposal.submitted', 'Proposal submitted', 'proposal', $proposalId, ['project_id' => $projectId]);

        NotificationService::notify(
            (int) $project['client_id'],
            'proposal_received',
            'New proposal received',
            ($freelancer['name'] ?? 'A freelancer') . ' submitted a proposal for "' . $project['title'] . '".',
            route('client.projects.proposals', ['id' => $projectId])
        );

        return ['ok' => true, 'errors' => [], 'proposal_id' => $proposalId];
    }

    /**
     * @return array{ok:bool,message:string}
     */
    public static function withdraw(int $proposalId, int $freelancerId): array
    {
        $proposal = Proposal::find($proposalId);

        if ($proposal === null || (int) $proposal['freelancer_id'] !== $freelancerId) {
            return ['ok' => false, 'message' => 'Proposal not found.'];
        }

        if (!in_array((string) $proposal['status'], ['submitted', 'shortlisted'], true)) {
            return ['ok' => false, 'message' => 'This proposal can no longer be withdrawn.'];
        }

        Database::transaction(static function () use ($proposalId, $proposal): void {
            Proposal::update($proposalId, ['status' => 'withdrawn', 'decided_at' => gmdate('Y-m-d H:i:s')]);
            Project::refreshProposalCount((int) $proposal['project_id']);
        });

        AuditService::record('proposal.withdrawn', 'Proposal withdrawn by freelancer', 'proposal', $proposalId);

        return ['ok' => true, 'message' => 'Your proposal has been withdrawn.'];
    }

    /**
     * Client shortlists or rejects a proposal (awarding is handled by ContractService).
     *
     * @return array{ok:bool,message:string}
     */
    public static function decide(int $proposalId, int $clientId, string $decision, string $note = ''): array
    {
        $proposal = Proposal::detailed($proposalId);

        if ($proposal === null || (int) $proposal['client_id'] !== $clientId) {
            return ['ok' => false, 'message' => 'Proposal not found for your project.'];
        }

        if (!in_array($decision, ['shortlist', 'reject', 'unshortlist'], true)) {
            return ['ok' => false, 'message' => 'Invalid decision.'];
        }

        $current = (string) $proposal['status'];

        if ($decision === 'shortlist' && $current !== 'submitted') {
            return ['ok' => false, 'message' => 'Only submitted proposals can be shortlisted.'];
        }
        if ($decision === 'unshortlist' && $current !== 'shortlisted') {
            return ['ok' => false, 'message' => 'Only shortlisted proposals can be removed from the shortlist.'];
        }
        if ($decision === 'reject' && !in_array($current, ['submitted', 'shortlisted'], true)) {
            return ['ok' => false, 'message' => 'This proposal can no longer be rejected.'];
        }

        $newStatus = match ($decision) {
            'shortlist'   => 'shortlisted',
            'unshortlist' => 'submitted',
            default       => 'rejected',
        };

        Proposal::update($proposalId, [
            'status'      => $newStatus,
            'client_note' => $note !== '' ? mb_substr($note, 0, 500) : null,
            'decided_at'  => $newStatus === 'rejected' ? gmdate('Y-m-d H:i:s') : null,
        ]);

        AuditService::record('proposal.' . $decision, 'Client ' . $decision . 'ed a proposal', 'proposal', $proposalId);

        if ($newStatus === 'rejected') {
            NotificationService::notify(
                (int) $proposal['freelancer_id'],
                'proposal_rejected',
                'Proposal not selected',
                'Your proposal for "' . $proposal['project_title'] . '" was not selected this time.',
                route('freelancer.proposals')
            );
        }

        return ['ok' => true, 'message' => 'Proposal updated.'];
    }
}
