<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Session;
use App\Models\Contract;
use App\Models\Project;
use App\Models\Proposal;
use App\Services\ProposalService;

/**
 * Freelancer-side proposal management.
 */
final class FreelancerProposalController extends Controller
{
    public function index(Request $request): string
    {
        $userId = (int) Auth::id();
        $status = $request->string('status');

        if ($status !== '' && !in_array($status, Proposal::STATUSES, true)) {
            $status = '';
        }

        return $this->dashboardView('proposals/index', [
            'title'     => 'My proposals',
            'proposals' => Proposal::forFreelancer($userId, $status !== '' ? $status : null),
            'status'    => $status,
        ]);
    }

    public function show(Request $request): string
    {
        $userId = (int) Auth::id();
        $proposalId = (int) ($request->route('id') ?? 0);
        $proposal = Proposal::detailed($proposalId);

        if ($proposal === null || (int) $proposal['freelancer_id'] !== $userId) {
            return $this->notFound('That proposal could not be found.');
        }

        $contract = Contract::findBy(['proposal_id' => $proposalId]);

        return $this->dashboardView('proposals/show', [
            'title'    => 'Proposal ' . $proposal['reference'],
            'proposal' => $proposal,
            'contract' => $contract,
        ]);
    }

    public function store(Request $request): mixed
    {
        $userId = (int) Auth::id();
        $projectId = (int) ($request->route('id') ?? 0);

        $result = ProposalService::submit($userId, $projectId, [
            'cover_letter'   => $request->input('cover_letter', ''),
            'bid_amount'     => $request->input('bid_amount', ''),
            'estimated_days' => $request->input('estimated_days', ''),
        ]);

        if (!$result['ok']) {
            Session::flash('danger', 'Your proposal could not be submitted.');
            Session::flashErrors($result['errors']);
            Session::flashInput($request->only(['cover_letter', 'bid_amount', 'estimated_days']));

            $this->redirect('/projects/' . $projectId . '#proposal');
        }

        $this->success('Your proposal has been submitted. You will be notified if the client responds.', 'freelancer.proposals');
    }

    public function withdraw(Request $request): never
    {
        $result = ProposalService::withdraw((int) ($request->route('id') ?? 0), (int) Auth::id());

        Session::flash($result['ok'] ? 'success' : 'danger', $result['message']);
        $this->redirectToRoute('freelancer.proposals');
    }

    public function contracts(Request $request): string
    {
        return $this->dashboardView('contracts/index', [
            'title'     => 'My contracts',
            'contracts' => Contract::forUser((int) Auth::id(), 'freelancer'),
            'role'      => 'freelancer',
        ]);
    }
}
