<?php
/** @var array<int,array<string,mixed>> $deliverables */
/** @var array{page:int,last_page:int,total:int} $meta */
?>
<div class="cw-card">
    <div class="table-responsive">
        <table class="table cw-table mb-0 align-middle">
            <thead><tr><th>Contract</th><th>Version</th><th>Submitted by</th><th>Files</th><th>Status</th><th>Submitted</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($deliverables as $deliverable): ?>
                <tr>
                    <td class="small"><?= e((string) $deliverable['contract_reference']) ?></td>
                    <td><?= (int) $deliverable['version'] ?></td>
                    <td class="small"><?= e((string) $deliverable['submitted_by_name']) ?></td>
                    <td><?= (int) $deliverable['file_count'] ?></td>
                    <td><span class="badge <?= e(status_badge_class((string) $deliverable['status'])) ?>"><?= e(humanize((string) $deliverable['status'])) ?></span></td>
                    <td class="small text-muted"><?= e(dt((string) $deliverable['created_at'], 'd M Y')) ?></td>
                    <td class="text-end">
                        <a class="btn btn-sm btn-outline-primary" href="<?= e(route('admin.contracts.show', ['id' => (int) $deliverable['contract_id']])) ?>">Contract</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if ($deliverables === []): ?>
                <tr><td colspan="7" class="text-center text-muted py-4">No deliverables found.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require CIRCLEWORK_ROOT . '/resources/views/components/pagination.php'; ?>
