<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Request;
use App\Core\Session;
use App\Models\AdminAnnouncement;
use App\Models\Contract;
use App\Models\Message;
use App\Models\Notification;
use App\Models\Proposal;
use App\Models\Project;
use App\Models\Subscription;
use App\Services\SubscriptionService;

/**
 * Shared authenticated dashboard.
 */
final class DashboardController extends Controller
{
    public function index(Request $request): string
    {
        $userId = (int) Auth::id();
        $mode = Auth::activeMode();

        return $mode === 'freelancer'
            ? $this->freelancerOverview($userId)
            : $this->clientOverview($userId);
    }

    private function clientOverview(int $userId): string
    {
        $stats = [
            'active_projects' => (int) Database::scalar(
                "SELECT COUNT(*) FROM `projects` WHERE `client_id` = ? AND `status` IN ('published','in_progress')",
                [$userId]
            ),
            'pending_projects' => (int) Database::scalar(
                "SELECT COUNT(*) FROM `projects` WHERE `client_id` = ? AND `status` = 'pending'",
                [$userId]
            ),
            'proposals_received' => (int) Database::scalar(
                "SELECT COUNT(*) FROM `proposals` pr INNER JOIN `projects` p ON p.id = pr.project_id
                 WHERE p.client_id = ? AND pr.status IN ('submitted','shortlisted')",
                [$userId]
            ),
            'active_contracts' => (int) Database::scalar(
                "SELECT COUNT(*) FROM `contracts` WHERE `client_id` = ? AND `status` NOT IN ('completed','cancelled')",
                [$userId]
            ),
            'completed_contracts' => (int) Database::scalar(
                "SELECT COUNT(*) FROM `contracts` WHERE `client_id` = ? AND `status` = 'completed'",
                [$userId]
            ),
        ];

        $recentProjects = Database::select(
            'SELECT id, reference, title, status, proposal_count, budget_type, budget_min, budget_max, currency, created_at
             FROM `projects` WHERE `client_id` = ? ORDER BY created_at DESC LIMIT 5',
            [$userId]
        );

        $recentProposals = Database::select(
            "SELECT pr.id, pr.bid_amount, pr.currency, pr.estimated_days, pr.status, pr.created_at,
                    p.title AS project_title, p.id AS project_id, u.name AS freelancer_name, u.id AS freelancer_id
             FROM `proposals` pr
             INNER JOIN `projects` p ON p.id = pr.project_id
             INNER JOIN `users` u ON u.id = pr.freelancer_id
             WHERE p.client_id = ? AND pr.status IN ('submitted','shortlisted')
             ORDER BY pr.created_at DESC LIMIT 5",
            [$userId]
        );

        $contracts = Contract::forUser($userId, 'client');

        return $this->dashboardView('dashboard/client', [
            'title'           => 'Client dashboard',
            'stats'           => $stats,
            'recentProjects'  => $recentProjects,
            'recentProposals' => $recentProposals,
            'contracts'       => array_slice($contracts, 0, 5),
            'subscription'    => SubscriptionService::activePlanFor($userId),
            'announcements'   => AdminAnnouncement::published('clients', 3),
        ]);
    }

    private function freelancerOverview(int $userId): string
    {
        $stats = [
            'active_proposals' => (int) Database::scalar(
                "SELECT COUNT(*) FROM `proposals` WHERE `freelancer_id` = ? AND `status` IN ('submitted','shortlisted')",
                [$userId]
            ),
            'won_proposals' => (int) Database::scalar(
                "SELECT COUNT(*) FROM `proposals` WHERE `freelancer_id` = ? AND `status` = 'accepted'",
                [$userId]
            ),
            'active_contracts' => (int) Database::scalar(
                "SELECT COUNT(*) FROM `contracts` WHERE `freelancer_id` = ? AND `status` NOT IN ('completed','cancelled')",
                [$userId]
            ),
            'completed_contracts' => (int) Database::scalar(
                "SELECT COUNT(*) FROM `contracts` WHERE `freelancer_id` = ? AND `status` = 'completed'",
                [$userId]
            ),
            'portfolio_items' => (int) Database::scalar(
                'SELECT COUNT(*) FROM `portfolio_items` WHERE `user_id` = ?',
                [$userId]
            ),
        ];

        $proposals = Proposal::forFreelancer($userId);

        $openProjects = Project::publicList([], 'newest', 5, 0);

        return $this->dashboardView('dashboard/freelancer', [
            'title'        => 'Freelancer dashboard',
            'stats'        => $stats,
            'proposals'    => array_slice($proposals, 0, 5),
            'contracts'    => array_slice(Contract::forUser($userId, 'freelancer'), 0, 5),
            'openProjects' => $openProjects,
            'subscription' => SubscriptionService::activePlanFor($userId),
            'limits'       => SubscriptionService::limitsFor($userId),
            'announcements' => AdminAnnouncement::published('freelancers', 3),
        ]);
    }

    public function switchMode(Request $request): never
    {
        $mode = $request->string('mode', 'client');

        if (!in_array($mode, ['client', 'freelancer'], true)) {
            $mode = 'client';
        }

        Auth::setActiveMode($mode);
        \App\Models\User::update((int) Auth::id(), ['default_mode' => $mode]);

        Session::flash('success', 'You are now working as a ' . $mode . '.');
        $this->redirectToRoute('dashboard');
    }
}
