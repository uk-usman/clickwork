<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Request;
use App\Models\User;
use App\Services\AuditService;
use App\Services\NotificationService;

/**
 * User administration: search, inspect, activate and suspend accounts.
 */
final class UserController extends AdminController
{
    public function index(Request $request): string
    {
        $filters = $this->filters($request, ['q', 'role', 'status', 'state']);
        $total = User::adminCount($filters);
        $meta = User::paginateMeta($total, 20, $request->int('page', 1));

        return $this->adminView('admin/users/index', [
            'title'          => 'Users',
            'users'          => User::adminList($filters, $meta['per_page'], $meta['offset']),
            'meta'           => $meta,
            'filters'        => $filters,
            'adminNavActive' => 'users',
        ]);
    }

    public function show(Request $request): mixed
    {
        $userId = (int) ($request->route('id') ?? 0);
        $user = User::withProfile($userId);

        if ($user === null) {
            return $this->notFound('That user could not be found.');
        }

        $counts = [
            'projects'  => (int) \App\Core\Database::scalar('SELECT COUNT(*) FROM `projects` WHERE `client_id` = ?', [$userId]),
            'proposals' => (int) \App\Core\Database::scalar('SELECT COUNT(*) FROM `proposals` WHERE `freelancer_id` = ?', [$userId]),
            'contracts' => (int) \App\Core\Database::scalar('SELECT COUNT(*) FROM `contracts` WHERE `client_id` = ? OR `freelancer_id` = ?', [$userId, $userId]),
            'reviews'   => (int) \App\Core\Database::scalar('SELECT COUNT(*) FROM `reviews` WHERE `reviewee_id` = ?', [$userId]),
        ];

        $audit = \App\Core\Database::select(
            'SELECT * FROM `audit_logs` WHERE `actor_id` = ? OR (`entity_type` = \'user\' AND `entity_id` = ?)
             ORDER BY `created_at` DESC LIMIT 20',
            [$userId, $userId]
        );

        return $this->adminView('admin/users/show', [
            'title'          => $user['name'],
            'user'           => $user,
            'counts'         => $counts,
            'audit'          => $audit,
            'adminNavActive' => 'users',
        ]);
    }

    public function suspend(Request $request): mixed
    {
        $userId = (int) ($request->route('id') ?? 0);
        $reason = $request->string('reason');

        $user = User::find($userId);

        if ($user === null) {
            $this->err('That user could not be found.', 'admin.users');
        }

        if ($userId === Auth::id()) {
            $this->err('You cannot suspend your own administrator account.', 'admin.users.show', ['id' => $userId]);
        }

        if ((string) $user['role'] === Auth::ROLE_SUPER_ADMIN && !Auth::isSuperAdmin()) {
            $this->err('Only a super administrator can suspend another super administrator.', 'admin.users.show', ['id' => $userId]);
        }

        if (mb_strlen($reason) < 5) {
            $this->err('Please provide a reason for suspending this account.', 'admin.users.show', ['id' => $userId]);
        }

        User::update($userId, [
            'status'            => 'suspended',
            'suspended_at'      => gmdate('Y-m-d H:i:s'),
            'suspension_reason' => mb_substr($reason, 0, 500),
        ]);

        AuditService::record('admin.user_suspended', 'Account suspended', 'user', $userId, ['reason' => $reason]);

        NotificationService::notify(
            $userId,
            'account',
            'Your account has been suspended',
            'Reason: ' . $reason,
            null,
            [],
            true
        );

        $this->ok('The account has been suspended.', 'admin.users.show', ['id' => $userId]);
    }

    public function activate(Request $request): never
    {
        $userId = (int) ($request->route('id') ?? 0);
        $user = User::find($userId);

        if ($user === null) {
            $this->err('That user could not be found.', 'admin.users');
        }

        User::update($userId, [
            'status'            => 'active',
            'suspended_at'      => null,
            'suspension_reason' => null,
        ]);

        AuditService::record('admin.user_activated', 'Account reactivated', 'user', $userId);

        NotificationService::notify($userId, 'account', 'Your account has been reactivated', 'You can sign in and use CircleWork Africa again.');

        $this->ok('The account has been reactivated.', 'admin.users.show', ['id' => $userId]);
    }
}
