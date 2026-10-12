<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Request;
use App\Models\Category;
use App\Models\Project;
use App\Models\Proposal;
use App\Models\Skill;

/**
 * Public project marketplace: browse, filter, and view published projects.
 */
final class ProjectController extends Controller
{
    public function index(Request $request): string
    {
        Project::expireFeatured();

        $filters = [
            'q'             => $request->string('q'),
            'category_id'   => $request->int('category'),
            'category_slug' => $request->string('category_slug'),
            'budget_type'   => $request->string('budget_type'),
            'budget_min'    => $request->string('budget_min'),
            'budget_max'    => $request->string('budget_max'),
            'deadline_before' => $request->string('deadline_before'),
            'skill_id'      => $request->int('skill'),
        ];

        if ($filters['budget_type'] !== '' && !in_array($filters['budget_type'], ['fixed', 'hourly'], true)) {
            $filters['budget_type'] = '';
        }

        $sort = $request->string('sort', 'newest');
        if (!in_array($sort, ['newest', 'oldest', 'budget_high', 'budget_low', 'deadline'], true)) {
            $sort = 'newest';
        }

        $total = Project::publicCount($filters);
        $meta = Project::paginateMeta($total, 12, $request->int('page', 1));

        $projects = Project::publicList($filters, $sort, $meta['per_page'], $meta['offset']);

        return $this->view('projects/index', [
            'title'      => 'Browse freelance projects',
            'projects'   => $projects,
            'meta'       => $meta,
            'filters'    => $filters,
            'sort'       => $sort,
            'categories' => Category::activeList(),
            'skills'     => Skill::activeList(),
        ], 'layouts/public');
    }

    public function show(Request $request): string
    {
        $id = (int) ($request->route('id') ?? 0);
        $project = Project::detailed($id);

        if ($project === null) {
            return $this->notFound('That project could not be found.');
        }

        $userId = Auth::id();
        $isOwner = $userId !== null && (int) $project['client_id'] === $userId;

        // Only published projects are public; drafts and pending items are private.
        if (!in_array((string) $project['status'], ['published', 'in_progress', 'completed'], true)
            || (string) $project['visibility'] !== 'public') {
            if (!$isOwner && !Auth::isAdmin()) {
                return $this->notFound('That project is not publicly available.');
            }
        }

        if (!$isOwner) {
            Project::incrementViews($id);
        }

        $existingProposal = null;
        if ($userId !== null) {
            $existingProposal = Proposal::findFor($id, (int) $userId);
        }

        return $this->view('projects/show', [
            'title'            => $project['title'],
            'metaDescription'  => str_excerpt($project['description'], 155),
            'project'          => $project,
            'skills'           => Project::skillsFor($id),
            'isOwner'          => $isOwner,
            'existingProposal' => $existingProposal,
            'proposalCount'    => (int) $project['proposal_count'],
        ], 'layouts/public');
    }
}
