<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Models\Category;
use App\Models\PortfolioItem;
use App\Models\Review;
use App\Models\Skill;
use App\Models\User;
use App\Models\UserSkill;

/**
 * Public freelancer directory and freelancer profiles.
 */
final class FreelancerController extends Controller
{
    public function index(Request $request): string
    {
        $filters = [
            'q'        => $request->string('q'),
            'city'     => $request->string('city'),
            'state'    => $request->string('state'),
            'skill_id' => $request->int('skill'),
        ];

        $total = User::countPublicFreelancers($filters);
        $meta = User::paginateMeta($total, 12, $request->int('page', 1));

        $freelancers = User::publicFreelancers($meta['per_page'], $meta['offset'], $filters);

        return $this->view('freelancers/index', [
            'title'       => 'Find freelancers',
            'freelancers' => $freelancers,
            'meta'        => $meta,
            'filters'     => $filters,
            'skills'      => Skill::activeList(),
            'categories'  => Category::activeList(),
        ], 'layouts/public');
    }

    public function show(Request $request): string
    {
        $id = (int) ($request->route('id') ?? 0);
        $user = User::withProfile($id);

        if ($user === null || $user['role'] !== 'user' || $user['status'] !== 'active') {
            return $this->notFound('That freelancer profile could not be found.');
        }

        if (($user['profile_visibility'] ?? 'public') !== 'public' && (int) \App\Core\Auth::id() !== $id && !\App\Core\Auth::isAdmin()) {
            return $this->notFound('That freelancer profile is not publicly available.');
        }

        return $this->view('freelancers/show', [
            'title'         => $user['name'] . ' — Freelancer profile',
            'metaDescription' => str_excerpt((string) ($user['headline'] ?? $user['freelancer_overview'] ?? ''), 155),
            'freelancer'    => $user,
            'skills'        => UserSkill::forUser($id),
            'portfolio'     => PortfolioItem::forUser($id, true),
            'reviews'       => Review::forUser($id, 10, 0),
            'reviewCount'   => Review::countForUser($id),
        ], 'layouts/public');
    }
}
