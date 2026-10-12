<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Session;
use App\Models\Category;
use App\Models\Contract;
use App\Models\Project;
use App\Models\Proposal;
use App\Models\Skill;
use App\Services\AuditService;
use App\Services\ContractService;
use App\Services\FeaturedService;
use App\Services\ProjectService;
use App\Services\ProposalService;
use App\Services\SubscriptionService;

/**
 * Client-side project management: create, edit, review proposals, award work.
 */
final class ClientProjectController extends Controller
{
    public function index(Request $request): string
    {
        $userId = (int) Auth::id();
        $status = $request->string('status');

        $conditions = ['client_id' => $userId];
        if ($status !== '' && in_array($status, Project::STATUSES, true)) {
            $conditions['status'] = $status;
        }

        return $this->dashboardView('projects/client-index', [
            'title'    => 'My projects',
            'projects' => Project::allBy($conditions, 'created_at DESC', 100),
            'status'   => $status,
        ]);
    }

    public function create(Request $request): string
    {
        return $this->dashboardView('projects/form', [
            'title'      => 'Post a new project',
            'project'    => null,
            'categories' => Category::activeList(),
            'skills'     => Skill::activeList(),
            'selectedSkills' => [],
        ]);
    }

    public function store(Request $request): mixed
    {
        $userId = (int) Auth::id();
        $submit = $request->string('action', 'draft') === 'submit';

        if ($submit) {
            $gate = SubscriptionService::canPublishAnotherProject($userId);
            if (!$gate['ok']) {
                Session::flash('warning', $gate['message']);
                $this->redirectToRoute('subscriptions.index');
            }
        }

        $payload = $this->payload($request);
        $result = ProjectService::create($userId, $payload, $submit);

        if (!$result['ok']) {
            Session::flash('danger', 'Please correct the highlighted fields.');
            Session::flashErrors($result['errors']);
            Session::flashInput($request->all());
            $this->redirectToRoute('client.projects.create');
        }

        $message = $submit
            ? 'Your project has been submitted for moderation. It will appear publicly once approved.'
            : 'Your project has been saved as a draft.';

        $this->success($message, 'client.projects.show', ['id' => $result['project_id']]);
    }

    public function show(Request $request): string
    {
        $userId = (int) Auth::id();
        $projectId = (int) ($request->route('id') ?? 0);
        $project = Project::detailed($projectId);

        if ($project === null || (int) $project['client_id'] !== $userId) {
            return $this->notFound('That project could not be found in your account.');
        }

        $contract = Contract::findBy(['project_id' => $projectId]);

        return $this->dashboardView('projects/client-show', [
            'title'    => $project['title'],
            'project'  => $project,
            'skills'   => Project::skillsFor($projectId),
            'proposals' => Proposal::forProject($projectId),
            'contract' => $contract,
            'featuredListings' => \App\Models\FeaturedListing::forProject($projectId),
            'featuredPrice' => FeaturedService::price(),
            'featuredDays' => FeaturedService::durationDays(),
        ]);
    }

    public function edit(Request $request): string
    {
        $userId = (int) Auth::id();
        $projectId = (int) ($request->route('id') ?? 0);
        $project = Project::find($projectId);

        if ($project === null || (int) $project['client_id'] !== $userId) {
            return $this->notFound('That project could not be found in your account.');
        }

        if (!in_array((string) $project['status'], ['draft', 'rejected', 'pending'], true)) {
            Session::flash('warning', 'This project can no longer be edited because it is already published or in progress.');
            $this->redirectToRoute('client.projects.show', ['id' => $projectId]);
        }

        return $this->dashboardView('projects/form', [
            'title'      => 'Edit project',
            'project'    => $project,
            'categories' => Category::activeList(),
            'skills'     => Skill::activeList(),
            'selectedSkills' => array_map(
                static fn (array $s): int => (int) $s['id'],
                Project::skillsFor($projectId)
            ),
        ]);
    }

    public function update(Request $request): mixed
    {
        $userId = (int) Auth::id();
        $projectId = (int) ($request->route('id') ?? 0);
        $submit = $request->string('action', 'draft') === 'submit';

        $result = ProjectService::update($projectId, $userId, $this->payload($request), $submit);

        if (!$result['ok']) {
            Session::flash('danger', 'Please correct the highlighted fields.');
            Session::flashErrors($result['errors']);
            Session::flashInput($request->all());
            $this->redirect('/client/projects/' . $projectId . '/edit');
        }

        $this->success($submit ? 'Your project has been resubmitted for moderation.' : 'Your draft has been saved.', 'client.projects.show', ['id' => $projectId]);
    }

    public function proposals(Request $request): string
    {
        $userId = (int) Auth::id();
        $projectId = (int) ($request->route('id') ?? 0);
        $project = Project::detailed($projectId);

        if ($project === null || (int) $project['client_id'] !== $userId) {
            return $this->notFound('That project could not be found in your account.');
        }

        $proposals = Proposal::forProject($projectId);

        usort($proposals, static function (array $a, array $b): int {
            return (float) $a['bid_amount'] <=> (float) $b['bid_amount'];
        });

        return $this->dashboardView('proposals/client-index', [
            'title'     => 'Proposals for ' . $project['title'],
            'project'   => $project,
            'proposals' => $proposals,
            'contract'  => Contract::findBy(['project_id' => $projectId]),
        ]);
    }

    public function decideProposal(Request $request): never
    {
        $result = ProposalService::decide(
            (int) ($request->route('id') ?? 0),
            (int) Auth::id(),
            $request->string('decision'),
            $request->string('note')
        );

        Session::flash($result['ok'] ? 'success' : 'danger', $result['message']);
        $this->back('/client/projects');
    }

    public function awardProposal(Request $request): mixed
    {
        $proposalId = (int) ($request->route('id') ?? 0);
        $userId = (int) Auth::id();

        $result = ContractService::awardProposal($proposalId, $userId);

        if (!$result['ok']) {
            Session::flash('danger', $result['message']);
            $this->back('/client/projects');
        }

        Session::flash('success', $result['message'] . ' Both parties must now accept the contract terms.');
        $this->redirectToRoute('contracts.show', ['id' => $result['contract_id']]);
    }

    public function contracts(Request $request): string
    {
        return $this->dashboardView('contracts/index', [
            'title'     => 'My contracts',
            'contracts' => Contract::forUser((int) Auth::id(), 'client'),
            'role'      => 'client',
        ]);
    }

    public function requestFeatured(Request $request): never
    {
        $projectId = (int) ($request->route('id') ?? 0);

        $result = FeaturedService::request(
            $projectId,
            (int) Auth::id(),
            $request->string('payment_method', 'bank_transfer'),
            $request->string('payer_reference')
        );

        Session::flash($result['ok'] ? 'success' : 'danger', $result['message']);
        $this->redirectToRoute('client.projects.show', ['id' => $projectId]);
    }

    /**
     * @return array<string,mixed>
     */
    private function payload(Request $request): array
    {
        return [
            'title'                 => $request->string('title'),
            'category_id'           => $request->int('category_id'),
            'description'           => $request->string('description'),
            'required_skills_text'  => $request->string('required_skills_text'),
            'budget_type'           => $request->string('budget_type', 'fixed'),
            'budget_min'            => $request->string('budget_min'),
            'budget_max'            => $request->string('budget_max'),
            'currency'              => $request->string('currency', 'NGN'),
            'deadline'              => $request->string('deadline'),
            'expected_deliverables' => $request->string('expected_deliverables'),
            'visibility'            => $request->string('visibility', 'public'),
            'skill_ids'             => (array) $request->input('skill_ids', []),
        ];
    }
}
