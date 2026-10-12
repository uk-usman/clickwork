<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Database;
use App\Core\Request;
use App\Models\Dispute;
use App\Models\Report;
use App\Models\Subscription;
use App\Models\SubscriptionPayment;

/**
 * Administration dashboard. Every figure comes from a real database query.
 */
final class DashboardController extends AdminController
{
    public function index(Request $request): string
    {
        \App\Models\Project::expireFeatured();
        Subscription::expireOverdue();
        \App\Models\FeaturedListing::expireOverdue();

        $stats = [
            'total_users'        => (int) Database::scalar('SELECT COUNT(*) FROM `users`'),
            'active_freelancers' => (int) Database::scalar(
                "SELECT COUNT(*) FROM `users` u INNER JOIN `user_profiles` p ON p.user_id = u.id
                 WHERE u.role = 'user' AND u.status = 'active' AND p.freelancer_title IS NOT NULL"
            ),
            'active_clients'     => (int) Database::scalar(
                "SELECT COUNT(DISTINCT p.client_id) FROM `projects` p
                 INNER JOIN `users` u ON u.id = p.client_id WHERE u.status = 'active'"
            ),
            'pending_projects'   => (int) Database::scalar("SELECT COUNT(*) FROM `projects` WHERE `status` = 'pending'"),
            'published_projects' => (int) Database::scalar("SELECT COUNT(*) FROM `projects` WHERE `status` IN ('published','in_progress')"),
            'active_contracts'   => (int) Database::scalar("SELECT COUNT(*) FROM `contracts` WHERE `status` NOT IN ('completed','cancelled')"),
            'completed_contracts' => (int) Database::scalar("SELECT COUNT(*) FROM `contracts` WHERE `status` = 'completed'"),
            'reported_payments'  => (int) Database::scalar("SELECT COUNT(*) FROM `payment_records` WHERE `status` = 'reported'"),
            'disputed_payments'  => (int) Database::scalar("SELECT COUNT(*) FROM `payment_records` WHERE `status` = 'disputed'"),
            'open_disputes'      => Dispute::openCount(),
            'open_reports'       => Report::openCount(),
            'active_subscriptions' => (int) Database::scalar(
                "SELECT COUNT(*) FROM `subscriptions` WHERE `status` = 'active' AND (`expires_at` IS NULL OR `expires_at` > UTC_TIMESTAMP())"
            ),
            'pending_subscription_payments' => (int) Database::scalar(
                "SELECT COUNT(*) FROM `subscription_payments` WHERE `status` = 'pending'"
            ),
            'pending_featured'   => (int) Database::scalar("SELECT COUNT(*) FROM `featured_listings` WHERE `status` = 'pending'"),
        ];

        $pendingProjects = Database::select(
            "SELECT p.id, p.reference, p.title, p.created_at, p.budget_type, p.budget_min, p.budget_max, p.currency,
                    u.name AS client_name, c.name AS category_name
             FROM `projects` p
             INNER JOIN `users` u ON u.id = p.client_id
             LEFT JOIN `categories` c ON c.id = p.category_id
             WHERE p.status = 'pending' ORDER BY p.created_at ASC LIMIT 5"
        );

        $recentUsers = Database::select(
            'SELECT id, name, email, role, status, created_at FROM `users` ORDER BY created_at DESC LIMIT 5'
        );

        $recentAudit = Database::select(
            'SELECT al.*, u.name AS actor_name FROM `audit_logs` al
             LEFT JOIN `users` u ON u.id = al.actor_id
             ORDER BY al.created_at DESC, al.id DESC LIMIT 8'
        );

        $revenue = [
            'verified_subscription_total' => (string) Database::scalar(
                "SELECT COALESCE(SUM(amount), 0) FROM `subscription_payments` WHERE `status` = 'verified'"
            ),
            'featured_total' => (string) Database::scalar(
                "SELECT COALESCE(SUM(amount), 0) FROM `featured_listings` WHERE `status` IN ('active','expired')"
            ),
        ];

        return $this->adminView('admin/dashboard', [
            'title'          => 'Administration dashboard',
            'stats'          => $stats,
            'pendingProjects' => $pendingProjects,
            'recentUsers'    => $recentUsers,
            'recentAudit'    => $recentAudit,
            'revenue'        => $revenue,
            'adminNavActive' => 'dashboard',
        ]);
    }
}
