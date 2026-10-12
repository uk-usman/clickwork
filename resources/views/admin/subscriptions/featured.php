<?php
/** @var array<int,array<string,mixed>> $listings */
/** @var array{page:int,last_page:int,total:int} $meta */
/** @var array<string,mixed> $filters */
$action = route('admin.subscriptions.featured');
$fields = ['status' => ['label' => 'Status', 'type' => 'select', 'value' => $filters['status'] ?? '', 'options' => ['pending' => 'Pending', 'active' => 'Active', 'expired' => 'Expired', 'rejected' => 'Rejected']]];
?>
<?php require CIRCLEWORK_ROOT . '/resources/views/components/admin-filter-form.php'; ?>
<div class="cw-card">
    <div class="table-responsive">
        <table class="table cw-table mb-0 align-middle">
            <thead><tr><th>Project</th><th>Purchased by</th><th>Amount</th><th>Duration</th><th>Window</th><th>Status</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($listings as $listing): ?>
                <tr>
                    <td>
                        <a href="<?= e(route('admin.projects.show', ['id' => (int) $listing['project_id']])) ?>">
                            <?= e(str_excerpt((string) $listing['project_title'], 38)) ?>
                        </a>
                        <div class="small text-muted"><?= e((string) $listing['project_reference']) ?></div>
                    </td>
                    <td class="small"><?= e((string) $listing['purchased_by_name']) ?></td>
                    <td><?= e(money($listing['amount'], (string) $listing['currency'])) ?></td>
                    <td class="small"><?= (int) $listing['duration_days'] ?> day(s)</td>
                    <td class="small">
                        <?= e(dt($listing['starts_at'] ?? null, 'd M Y')) ?> – <?= e(dt($listing['ends_at'] ?? null, 'd M Y')) ?>
                    </td>
                    <td><span class="badge <?= e(status_badge_class((string) $listing['status'])) ?>"><?= e(humanize((string) $listing['status'])) ?></span></td>
                    <td class="text-end">
                        <?php if ((string) $listing['status'] === 'pending'): ?>
                            <div class="d-flex gap-1 justify-content-end">
                                <form method="post" action="<?= e(route('admin.subscriptions.featured.verify', ['id' => (int) $listing['id']])) ?>"
                                      data-confirm="Verify this manual payment and activate the featured placement?">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="decision" value="verify">
                                    <button type="submit" class="btn btn-sm btn-success">Verify</button>
                                </form>
                                <form method="post" action="<?= e(route('admin.subscriptions.featured.verify', ['id' => (int) $listing['id']])) ?>"
                                      data-confirm="Reject this featured listing request?">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="decision" value="reject">
                                    <button type="submit" class="btn btn-sm btn-outline-danger">Reject</button>
                                </form>
                            </div>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if ($listings === []): ?>
                <tr><td colspan="7" class="text-center text-muted py-4">No featured listings found.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require CIRCLEWORK_ROOT . '/resources/views/components/pagination.php'; ?>
