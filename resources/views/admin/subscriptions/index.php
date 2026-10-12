<?php
/** @var array<int,array<string,mixed>> $subscriptions */
/** @var array{page:int,last_page:int,total:int} $meta */
/** @var array<string,mixed> $filters */
$action = route('admin.subscriptions');
$fields = ['status' => ['label' => 'Status', 'type' => 'select', 'value' => $filters['status'] ?? '', 'options' => ['pending' => 'Pending', 'active' => 'Active', 'expired' => 'Expired', 'cancelled' => 'Cancelled']]];
?>
<?php require CIRCLEWORK_ROOT . '/resources/views/components/admin-filter-form.php'; ?>
<div class="cw-card">
    <div class="table-responsive">
        <table class="table cw-table mb-0 align-middle">
            <thead><tr><th>User</th><th>Plan</th><th>Status</th><th>Started</th><th>Expires</th><th>Verified by</th></tr></thead>
            <tbody>
            <?php foreach ($subscriptions as $subscription): ?>
                <tr>
                    <td>
                        <strong><?= e((string) $subscription['user_name']) ?></strong>
                        <div class="small text-muted"><?= e((string) $subscription['user_email']) ?></div>
                    </td>
                    <td><?= e((string) $subscription['plan_name']) ?></td>
                    <td><span class="badge <?= e(status_badge_class((string) $subscription['status'])) ?>"><?= e(humanize((string) $subscription['status'])) ?></span></td>
                    <td class="small"><?= e(dt($subscription['started_at'] ?? null, 'd M Y')) ?></td>
                    <td class="small"><?= e(dt($subscription['expires_at'] ?? null, 'd M Y')) ?></td>
                    <td class="small text-muted"><?= e((string) ($subscription['verification_note'] ?? '—')) ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if ($subscriptions === []): ?>
                <tr><td colspan="6" class="text-center text-muted py-4">No subscriptions found.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require CIRCLEWORK_ROOT . '/resources/views/components/pagination.php'; ?>
