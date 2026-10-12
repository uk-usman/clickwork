<?php
/** @var array<int,array<string,mixed>> $logs */
/** @var array{page:int,last_page:int,total:int} $meta */
/** @var array<string,mixed> $filters */
$action = route('admin.audit');
$fields = [
    'q' => ['label' => 'Search description or email', 'type' => 'text', 'value' => $filters['q'] ?? ''],
    'action' => ['label' => 'Action', 'type' => 'text', 'value' => $filters['action'] ?? ''],
];
?>
<?php require CIRCLEWORK_ROOT . '/resources/views/components/admin-filter-form.php'; ?>
<div class="cw-card">
    <div class="table-responsive">
        <table class="table cw-table mb-0 align-middle">
            <thead><tr><th>When</th><th>Actor</th><th>Action</th><th>Entity</th><th>Description</th><th>IP</th></tr></thead>
            <tbody>
            <?php foreach ($logs as $log): ?>
                <tr>
                    <td class="small text-muted"><?= e(dt((string) $log['created_at'])) ?></td>
                    <td class="small">
                        <?= e((string) ($log['actor_name'] ?? 'System')) ?>
                        <?php if (!empty($log['actor_role'])): ?>
                            <div class="text-muted"><?= e(humanize((string) $log['actor_role'])) ?></div>
                        <?php endif; ?>
                    </td>
                    <td class="small"><span class="cw-chip"><?= e((string) $log['action']) ?></span></td>
                    <td class="small text-muted">
                        <?= e((string) ($log['entity_type'] ?? '—')) ?><?= $log['entity_id'] !== null ? ' #' . (int) $log['entity_id'] : '' ?>
                    </td>
                    <td class="small"><?= e(str_excerpt((string) $log['description'], 90)) ?></td>
                    <td class="small text-muted"><?= e((string) ($log['ip_address'] ?? '—')) ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if ($logs === []): ?>
                <tr><td colspan="6" class="text-center text-muted py-4">No audit entries match these filters.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require CIRCLEWORK_ROOT . '/resources/views/components/pagination.php'; ?>
