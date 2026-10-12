<?php
/** @var array<int,array<string,mixed>> $contracts */
/** @var array{page:int,last_page:int,total:int} $meta */
/** @var array<string,mixed> $filters */
/** @var array<int,string> $statuses */
$options = [];
foreach ($statuses as $status) { $options[$status] = humanize($status); }
$action = route('admin.contracts');
$fields = ['status' => ['label' => 'Status', 'type' => 'select', 'value' => $filters['status'] ?? '', 'options' => $options]];
?>
<?php require CIRCLEWORK_ROOT . '/resources/views/components/admin-filter-form.php'; ?>
<div class="cw-card">
    <div class="table-responsive">
        <table class="table cw-table mb-0 align-middle">
            <thead><tr><th>Reference</th><th>Project</th><th>Client</th><th>Freelancer</th><th>Amount</th><th>Status</th><th>Version</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($contracts as $contract): ?>
                <tr>
                    <td class="small"><?= e((string) $contract['reference']) ?></td>
                    <td class="small"><?= e(str_excerpt((string) $contract['project_title'], 34)) ?></td>
                    <td class="small"><?= e((string) $contract['client_name']) ?></td>
                    <td class="small"><?= e((string) $contract['freelancer_name']) ?></td>
                    <td><?= e(money($contract['amount'], (string) $contract['currency'])) ?></td>
                    <td><span class="badge <?= e(status_badge_class((string) $contract['status'])) ?>"><?= e(humanize((string) $contract['status'])) ?></span></td>
                    <td><?= (int) $contract['current_version'] ?></td>
                    <td class="text-end"><a class="btn btn-sm btn-outline-primary" href="<?= e(route('admin.contracts.show', ['id' => (int) $contract['id']])) ?>">Open</a></td>
                </tr>
            <?php endforeach; ?>
            <?php if ($contracts === []): ?>
                <tr><td colspan="8" class="text-center text-muted py-4">No contracts found.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require CIRCLEWORK_ROOT . '/resources/views/components/pagination.php'; ?>
