<?php
/** @var array<int,array<string,mixed>> $disputes */
/** @var array{page:int,last_page:int,total:int} $meta */
/** @var array<string,mixed> $filters */
$action = route('admin.disputes');
$fields = ['status' => ['label' => 'Status', 'type' => 'select', 'value' => $filters['status'] ?? '', 'options' => ['open' => 'Open', 'in_review' => 'In review', 'resolved' => 'Resolved', 'closed' => 'Closed']]];
?>
<?php require CIRCLEWORK_ROOT . '/resources/views/components/admin-filter-form.php'; ?>
<div class="cw-card">
    <div class="table-responsive">
        <table class="table cw-table mb-0 align-middle">
            <thead><tr><th>Reference</th><th>Contract</th><th>Opened by</th><th>Against</th><th>Subject</th><th>Status</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($disputes as $dispute): ?>
                <tr>
                    <td class="small"><?= e((string) $dispute['reference']) ?></td>
                    <td class="small"><?= e((string) $dispute['contract_reference']) ?></td>
                    <td class="small"><?= e((string) $dispute['opened_by_name']) ?></td>
                    <td class="small"><?= e((string) ($dispute['against_name'] ?? '—')) ?></td>
                    <td class="small"><?= e(str_excerpt((string) $dispute['subject'], 46)) ?></td>
                    <td><span class="badge <?= e(status_badge_class((string) $dispute['status'])) ?>"><?= e(humanize((string) $dispute['status'])) ?></span></td>
                    <td class="text-end"><a class="btn btn-sm btn-outline-primary" href="<?= e(route('admin.disputes.show', ['id' => (int) $dispute['id']])) ?>">Open</a></td>
                </tr>
            <?php endforeach; ?>
            <?php if ($disputes === []): ?>
                <tr><td colspan="7" class="text-center text-muted py-4">No disputes found.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require CIRCLEWORK_ROOT . '/resources/views/components/pagination.php'; ?>
