<?php
/** @var array<int,array<string,mixed>> $users */
/** @var array{page:int,last_page:int,total:int} $meta */
/** @var array<string,mixed> $filters */
$action = route('admin.users');
$fields = [
    'q' => ['label' => 'Search name or email', 'type' => 'text', 'value' => $filters['q'] ?? ''],
    'role' => ['label' => 'Role', 'type' => 'select', 'value' => $filters['role'] ?? '', 'options' => ['user' => 'User', 'admin' => 'Admin', 'super_admin' => 'Super admin']],
    'status' => ['label' => 'Status', 'type' => 'select', 'value' => $filters['status'] ?? '', 'options' => ['active' => 'Active', 'pending' => 'Pending', 'suspended' => 'Suspended', 'closed' => 'Closed']],
    'state' => ['label' => 'State', 'type' => 'text', 'value' => $filters['state'] ?? ''],
];
?>
<?php require CIRCLEWORK_ROOT . '/resources/views/components/admin-filter-form.php'; ?>

<div class="cw-card">
    <div class="table-responsive">
        <table class="table cw-table mb-0 align-middle">
            <thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Status</th><th>Location</th><th>Registered</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($users as $user): ?>
                <tr>
                    <td><strong><?= e($user['name']) ?></strong></td>
                    <td class="small"><?= e($user['email']) ?></td>
                    <td><span class="badge text-bg-secondary"><?= e(humanize((string) $user['role'])) ?></span></td>
                    <td><span class="badge <?= e(status_badge_class((string) $user['status'])) ?>"><?= e(humanize((string) $user['status'])) ?></span></td>
                    <td class="small"><?= e(trim(($user['city'] ?? '') . ', ' . ($user['state'] ?? ''), ', ') ?: '—') ?></td>
                    <td class="small text-muted"><?= e(dt((string) $user['created_at'], 'd M Y')) ?></td>
                    <td class="text-end">
                        <a class="btn btn-sm btn-outline-primary" href="<?= e(route('admin.users.show', ['id' => (int) $user['id']])) ?>">Open</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if ($users === []): ?>
                <tr><td colspan="7" class="text-center text-muted py-4">No users match these filters.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require CIRCLEWORK_ROOT . '/resources/views/components/pagination.php'; ?>
