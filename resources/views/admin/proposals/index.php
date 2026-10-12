<?php
/** @var array<int,array<string,mixed>> $proposals */
/** @var array{page:int,last_page:int,total:int} $meta */
/** @var string $status */
?>
<div class="d-flex flex-wrap gap-2 mb-3">
    <a class="btn btn-sm <?= $status === '' ? 'btn-primary' : 'btn-outline-primary' ?>" href="<?= e(route('admin.proposals')) ?>">All</a>
    <?php foreach (['submitted', 'shortlisted', 'accepted', 'rejected', 'withdrawn'] as $option): ?>
        <a class="btn btn-sm <?= $status === $option ? 'btn-primary' : 'btn-outline-primary' ?>"
           href="<?= e(route('admin.proposals', ['status' => $option])) ?>"><?= e(humanize($option)) ?></a>
    <?php endforeach; ?>
</div>
<div class="cw-card">
    <div class="table-responsive">
        <table class="table cw-table mb-0 align-middle">
            <thead><tr><th>Reference</th><th>Project</th><th>Freelancer</th><th>Bid</th><th>Days</th><th>Status</th><th>Submitted</th></tr></thead>
            <tbody>
            <?php foreach ($proposals as $proposal): ?>
                <tr>
                    <td class="small"><?= e((string) $proposal['reference']) ?></td>
                    <td class="small"><?= e(str_excerpt((string) $proposal['project_title'], 36)) ?></td>
                    <td class="small">
                        <?= e((string) $proposal['freelancer_name']) ?>
                        <div class="text-muted"><?= e((string) $proposal['freelancer_email']) ?></div>
                    </td>
                    <td><?= e(money($proposal['bid_amount'], (string) $proposal['currency'])) ?></td>
                    <td><?= (int) $proposal['estimated_days'] ?></td>
                    <td><span class="badge <?= e(status_badge_class((string) $proposal['status'])) ?>"><?= e(humanize((string) $proposal['status'])) ?></span></td>
                    <td class="small text-muted"><?= e(dt((string) $proposal['created_at'], 'd M Y')) ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if ($proposals === []): ?>
                <tr><td colspan="7" class="text-center text-muted py-4">No proposals found.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require CIRCLEWORK_ROOT . '/resources/views/components/pagination.php'; ?>
