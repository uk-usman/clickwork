<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Session;
use App\Models\Contract;
use App\Models\ContractAcceptance;
use App\Models\ContractEvent;
use App\Models\ContractVersion;
use App\Models\Deliverable;
use App\Models\Dispute;
use App\Models\PaymentRecord;
use App\Models\Proposal;
use App\Models\Review;
use App\Services\ContractService;
use App\Services\DisputeService;

/**
 * Contract details, versioned terms, acceptance, amendment and cancellation.
 */
final class ContractController extends Controller
{
    public function show(Request $request): mixed
    {
        $contractId = (int) ($request->route('id') ?? 0);
        $contract = $this->participantContract($contractId);

        if ($contract === null) {
            return $this->forbidden('That contract is not available to your account.');
        }

        $userId = (int) Auth::id();
        $role = Contract::roleOf($contract, $userId);
        $version = (int) $contract['current_version'];
        $versionRow = ContractVersion::forVersion($contractId, $version);

        $detailed = Contract::detailed($contractId) ?? $contract;

        return $this->dashboardView('contracts/show', [
            'title'          => 'Contract ' . $contract['reference'],
            'contract'       => $detailed,
            'role'           => $role,
            'versionRow'     => $versionRow,
            'acceptances'    => ContractAcceptance::forVersion($contractId, $version),
            'hasAccepted'    => ContractAcceptance::hasAccepted($contractId, $version, $userId),
            'events'         => ContractEvent::timeline($contractId, 30),
            'deliverables'   => Deliverable::forContract($contractId),
            'payments'       => PaymentRecord::forContract($contractId),
            'disputes'       => Dispute::forContract($contractId),
            'proposal'       => Proposal::find((int) $contract['proposal_id']),
            'canReview'      => \App\Services\ReviewService::eligibleFor($contractId, $userId),
            'myReview'       => Review::forContractByReviewer($contractId, $userId),
        ]);
    }

    public function printView(Request $request): mixed
    {
        $contractId = (int) ($request->route('id') ?? 0);
        $contract = $this->participantContract($contractId);

        if ($contract === null) {
            return $this->forbidden('That contract is not available to your account.');
        }

        return $this->view('contracts/print', [
            'title'      => 'Contract ' . $contract['reference'],
            'contract'   => Contract::detailed($contractId),
            'versionRow' => ContractVersion::forVersion($contractId, (int) $contract['current_version']),
            'acceptances' => ContractAcceptance::forVersion($contractId, (int) $contract['current_version']),
        ], 'layouts/print');
    }

    public function accept(Request $request): mixed
    {
        $contractId = (int) ($request->route('id') ?? 0);
        $contract = $this->participantContract($contractId);

        if ($contract === null) {
            return $this->forbidden('That contract is not available to your account.');
        }

        if (!$request->bool('confirm_acceptance')) {
            Session::flash('danger', 'Please tick the confirmation box to record your acceptance.');
            $this->redirectToRoute('contracts.show', ['id' => $contractId]);
        }

        $result = ContractService::accept(
            $contractId,
            (int) Auth::id(),
            $request->ip(),
            $request->userAgent()
        );

        Session::flash($result['ok'] ? 'success' : 'danger', $result['message']);
        $this->redirectToRoute('contracts.show', ['id' => $contractId]);
    }

    public function amend(Request $request): mixed
    {
        $contractId = (int) ($request->route('id') ?? 0);
        $contract = $this->participantContract($contractId);

        if ($contract === null) {
            return $this->forbidden('That contract is not available to your account.');
        }

        $summary = $request->string('change_summary');

        if (mb_strlen($summary) < 5) {
            Session::flash('danger', 'Please describe why the contract terms are changing.');
            $this->redirectToRoute('contracts.show', ['id' => $contractId]);
        }

        $changes = [];
        foreach (['scope', 'deliverables', 'amount', 'deadline', 'revision_terms', 'payment_terms', 'cancellation_terms', 'dispute_terms'] as $field) {
            if ($request->has($field)) {
                $changes[$field] = $request->input($field);
            }
        }

        $result = ContractService::amend($contractId, (int) Auth::id(), $changes, $summary);

        Session::flash($result['ok'] ? 'success' : 'danger', $result['message']);
        $this->redirectToRoute('contracts.show', ['id' => $contractId]);
    }

    public function cancel(Request $request): mixed
    {
        $contractId = (int) ($request->route('id') ?? 0);
        $contract = $this->participantContract($contractId);

        if ($contract === null) {
            return $this->forbidden('That contract is not available to your account.');
        }

        $result = ContractService::cancel($contractId, (int) Auth::id(), $request->string('reason'));

        Session::flash($result['ok'] ? 'success' : 'danger', $result['message']);
        $this->redirectToRoute('contracts.show', ['id' => $contractId]);
    }

    public function openDispute(Request $request): mixed
    {
        $contractId = (int) ($request->route('id') ?? 0);
        $contract = $this->participantContract($contractId);

        if ($contract === null) {
            return $this->forbidden('That contract is not available to your account.');
        }

        $result = DisputeService::open(
            $contractId,
            (int) Auth::id(),
            $request->string('subject'),
            $request->string('reason')
        );

        Session::flash($result['ok'] ? 'success' : 'danger', $result['message']);
        $this->redirectToRoute('contracts.show', ['id' => $contractId]);
    }
}
