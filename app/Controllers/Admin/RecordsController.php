<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Request;
use App\Models\Deliverable;
use App\Models\Proposal;
use App\Models\User;
use App\Services\AuditService;
use App\Services\NotificationService;

/**
 * Read-only administrative listings plus administrator account management.
 */
final class RecordsController extends AdminController
{
    public function proposals(Request $request): string
    {
        $status = $request->string('status');
        $page = max(1, $request->int('page', 1));
        $perPage = 20;

        $conditions = ['1=1'];
        $params = [];

        if ($status !== '' && in_array($status, Proposal::STATUSES, true)) {
            $conditions[] = 'pr.status = ?';
            $params[] = $status;
        }

        $where = implode(' AND ', $conditions);
        $total = (int) Database::scalar("SELECT COUNT(*) FROM `proposals` pr WHERE $where", $params);
        $meta = Proposal::paginateMeta($total, $perPage, $page);

        $proposals = Database::select(
            "SELECT pr.*, p.title AS project_title, u.name AS freelancer_name, u.email AS freelancer_email
             FROM `proposals` pr
             INNER JOIN `projects` p ON p.id = pr.project_id
             INNER JOIN `users` u ON u.id = pr.freelancer_id
             WHERE $where
             ORDER BY pr.created_at DESC
             LIMIT " . $meta['per_page'] . ' OFFSET ' . $meta['offset'],
            $params
        );

        return $this->adminView('admin/proposals/index', [
            'title'          => 'Proposals',
            'proposals'      => $proposals,
            'meta'           => $meta,
            'status'         => $status,
            'adminNavActive' => 'proposals',
        ]);
    }

    public function deliverables(Request $request): string
    {
        $page = max(1, $request->int('page', 1));
        $perPage = 20;

        $total = (int) Database::scalar('SELECT COUNT(*) FROM `deliverables`');
        $meta = Deliverable::paginateMeta($total, $perPage, $page);

        $deliverables = Database::select(
            'SELECT d.*, ct.reference AS contract_reference, ct.id AS contract_id, u.name AS submitted_by_name,
                    (SELECT COUNT(*) FROM deliverable_files df WHERE df.deliverable_id = d.id) AS file_count
             FROM `deliverables` d
             INNER JOIN `contracts` ct ON ct.id = d.contract_id
             INNER JOIN `users` u ON u.id = d.submitted_by
             ORDER BY d.created_at DESC
             LIMIT ' . $meta['per_page'] . ' OFFSET ' . $meta['offset']
        );

        return $this->adminView('admin/deliverables/index', [
            'title'          => 'Deliverables',
            'deliverables'   => $deliverables,
            'meta'           => $meta,
            'adminNavActive' => 'deliverables',
        ]);
    }

    public function clients(Request $request): string
    {
        return $this->accountListing($request, 'client', 'Client accounts');
    }

    public function freelancers(Request $request): string
    {
        return $this->accountListing($request, 'freelancer', 'Freelancer profiles');
    }

    private function accountListing(Request $request, string $kind, string $title): string
    {
        $page = max(1, $request->int('page', 1));
        $perPage = 20;
        $search = $request->string('q');

        if ($kind === 'freelancer') {
            $base = "FROM `users` u INNER JOIN `user_profiles` p ON p.user_id = u.id
                     WHERE u.role = 'user' AND (p.freelancer_title IS NOT NULL OR EXISTS (
                        SELECT 1 FROM `proposals` pr WHERE pr.freelancer_id = u.id)) ";
        } else {
            $base = "FROM `users` u LEFT JOIN `user_profiles` p ON p.user_id = u.id
                     WHERE u.role = 'user' AND EXISTS (
                        SELECT 1 FROM `projects` pj WHERE pj.client_id = u.id) ";
        }

        $params = [];
        if ($search !== '') {
            $base .= ' AND (u.name LIKE ? OR u.email LIKE ?)';
            $params[] = '%' . $search . '%';
            $params[] = '%' . $search . '%';
        }

        $total = (int) Database::scalar('SELECT COUNT(*) ' . $base, $params);
        $meta = User::paginateMeta($total, $perPage, $page);

        $rows = Database::select(
            'SELECT u.id, u.name, u.email, u.status, u.created_at, p.city, p.state, p.freelancer_title,
                    p.rating_average, p.rating_count, p.completed_contracts, p.profile_visibility ' . $base .
            ' ORDER BY u.created_at DESC LIMIT ' . $meta['per_page'] . ' OFFSET ' . $meta['offset'],
            $params
        );

        return $this->adminView('admin/accounts/index', [
            'title'          => $title,
            'accounts'       => $rows,
            'meta'           => $meta,
            'kind'           => $kind,
            'search'         => $search,
            'adminNavActive' => $kind === 'freelancer' ? 'freelancers' : 'clients',
        ]);
    }

    public function administrators(Request $request): string
    {
        $admins = Database::select(
            "SELECT id, name, email, role, status, last_login_at, created_at FROM `users`
             WHERE role IN ('admin','super_admin') ORDER BY created_at ASC"
        );

        return $this->adminView('admin/administrators', [
            'title'          => 'Administrator accounts',
            'admins'         => $admins,
            'adminNavActive' => 'administrators',
        ]);
    }

    /**
     * Super administrators may promote an existing user to administrator.
     * Administrators are never created through public registration.
     */
    public function promoteAdmin(Request $request): mixed
    {
        $email = mb_strtolower($request->string('email'));
        $role = $request->string('role', 'admin');

        if (!in_array($role, ['admin', 'super_admin'], true)) {
            $this->err('Please choose a valid administrator role.', 'admin.administrators');
        }

        $user = User::findByEmail($email);

        if ($user === null) {
            $this->err('No account exists with that email address. The person must register normally first.', 'admin.administrators');
        }

        if ($user['role'] !== 'user') {
            $this->err('That account already has an administrative role.', 'admin.administrators');
        }

        User::update((int) $user['id'], ['role' => $role]);

        AuditService::record('admin.role_granted', 'Administrative role granted: ' . $role, 'user', (int) $user['id'], ['email' => $email]);

        NotificationService::notify(
            (int) $user['id'],
            'account',
            'Your account role has changed',
            'You have been granted the ' . $role . ' role on CircleWork Africa.',
            route('admin.dashboard')
        );

        $this->ok('The account has been granted the ' . $role . ' role.', 'admin.administrators');
    }

    public function demoteAdmin(Request $request): never
    {
        $userId = (int) ($request->route('id') ?? 0);
        $user = User::find($userId);

        if ($user === null) {
            $this->err('That account could not be found.', 'admin.administrators');
        }

        if ($userId === Auth::id()) {
            $this->err('You cannot remove your own administrator role.', 'admin.administrators');
        }

        User::update($userId, ['role' => 'user']);
        AuditService::record('admin.role_revoked', 'Administrative role removed', 'user', $userId);

        $this->ok('The administrative role has been removed.', 'admin.administrators');
    }
}
