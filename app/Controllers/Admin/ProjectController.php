<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Request;
use App\Models\Category;
use App\Models\Project;
use App\Models\Proposal;
use App\Services\ProjectService;

/**
 * Project moderation.
 */
final class ProjectController extends AdminController
{
    public function index(Request $request): string
    {
        $filters = $this->filters($request, ['q', 'status']);
        $total = Project::adminCount($filters);
        $meta = Project::paginateMeta($total, 20, $request->int('page', 1));

        return $this->adminView('admin/projects/index', [
            'title'          => 'Projects',
            'projects'       => Project::adminList($filters, $meta['per_page'], $meta['offset']),
            'meta'           => $meta,
            'filters'        => $filters,
            'statuses'       => Project::STATUSES,
            'categories'     => Category::activeList(),
            'adminNavActive' => 'projects',
        ]);
    }

    public function show(Request $request): mixed
    {
        $projectId = (int) ($request->route('id') ?? 0);
        $project = Project::detailed($projectId);

        if ($project === null) {
            return $this->notFound('That project could not be found.');
        }

        return $this->adminView('admin/projects/show', [
            'title'          => $project['title'],
            'project'        => $project,
            'skills'         => Project::skillsFor($projectId),
            'proposals'      => Proposal::forProject($projectId),
            'adminNavActive' => 'projects',
        ]);
    }

    public function moderate(Request $request): mixed
    {
        $projectId = (int) ($request->route('id') ?? 0);

        $result = ProjectService::moderate(
            $projectId,
            (int) Auth::id(),
            $request->string('decision'),
            $request->string('note')
        );

        $this->ok($result['message'], 'admin.projects.show', ['id' => $projectId]);
    }

    public function feature(Request $request): mixed
    {
        $projectId = (int) ($request->route('id') ?? 0);
        $project = Project::find($projectId);

        if ($project === null) {
            return $this->notFound('That project could not be found.');
        }

        $action = $request->string('action', 'feature');

        if ($action === 'unfeature') {
            Project::update($projectId, ['is_featured' => 0, 'featured_until' => null]);
            \App\Services\AuditService::record('admin.project_unfeatured', 'Project removed from featured placement', 'project', $projectId);
            $this->ok('The project is no longer featured.', 'admin.projects.show', ['id' => $projectId]);
        }

        $days = max(1, min(365, $request->int('days', \App\Services\FeaturedService::durationDays())));
        $until = gmdate('Y-m-d H:i:s', time() + ($days * 86400));

        Project::update($projectId, ['is_featured' => 1, 'featured_until' => $until]);
        \App\Services\AuditService::record('admin.project_featured', 'Project featured for ' . $days . ' days', 'project', $projectId, ['days' => $days]);

        $this->ok('The project is now featured for ' . $days . ' days.', 'admin.projects.show', ['id' => $projectId]);
    }
}
