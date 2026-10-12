<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Models\Category;
use App\Models\Project;
use App\Models\Skill;
use App\Models\User;

final class HomeController extends Controller
{
    public function index(Request $request): string
    {
        // Expire any featured placements that have ended (cheap, idempotent).
        Project::expireFeatured();

        $categories = Category::popularWithCounts(8);

        $recentProjects = Project::publicList([], 'newest', 6, 0);

        $featuredProjects = Project::publicList(['featured_only' => true], 'newest', 3, 0);

        $featuredFreelancers = User::publicFreelancers(4, 0);

        $stats = [
            'published_projects' => Project::publicCount([]),
            'freelancers'        => User::countPublicFreelancers([]),
            'categories'         => count(Category::activeList()),
        ];

        return $this->view('pages/home', [
            'title'             => 'Find Work. Hire Talent. Build Trust.',
            'metaDescription'   => 'CircleWork Africa connects Nigerian clients with skilled freelancers. Post a project, receive proposals and manage work with clear contracts and direct payments.',
            'categories'        => $categories,
            'recentProjects'    => $recentProjects,
            'featuredProjects'  => $featuredProjects,
            'featuredFreelancers' => $featuredFreelancers,
            'stats'             => $stats,
            'topSkills'         => array_slice(Skill::activeList(), 0, 12),
            'searchQuery'       => $request->string('q'),
            'searchCategory'    => $request->string('category'),
        ], 'layouts/public');
    }
}
