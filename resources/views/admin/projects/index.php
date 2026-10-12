<?php
/** @var array<int,array<string,mixed>> $projects */
/** @var array{page:int,last_page:int,total:int} $meta */
/** @var array<string,mixed> $filters */
/** @var array<int,string> $statuses */
$action = route('admin.projects');
$options = [];
foreach ($statuses as $status) { $options[$status] = humanize($status); }
$fields = [
    'q' => ['label' => 'Search title or reference', 'type' => 'text', 'value' => $filters['q'] ?? ''],
    'status' => ['label' => 'Status', 'type' => 'select', 'value' => $filters['status'] ?? '', 'options' => $options],
];
?>
<?php require CIRCLEWORK_ROOT . '/resources/views/components/admin-filter-form.php'; ?>

<div class="cw-card">
    <div class="table-responsive">
        <table class="table cw-table mb-0 align-middle">
            <thead><tr><th>Project</th><th>Client</th><th>Category</th><th>Status</th><th>Proposals</th><th>Created</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($projects as $project): ?>
                <tr>
                    <td>
                        <a href="<?= e(route('admin.projects.show', ['id' => (int) $project['id']])) ?>"><?= e(str_excerpt((string) $project['title'], 46)) ?></a>
                        <div class="small text-muted">
                            <?= e((string) $project['reference']) ?>
                            <?php if ((int) $project['is_featured'] === 1): ?>
                                <span class="badge text-bg-primary ms-1">Featured</span>
                            <?php endif; ?>
                        </div>
                    </td>
                    <td class="small"><?= e((string) $project['client_name']) ?></td>
                    <td class="small"><?= e((string) ($project['category_name'] ?? '—')) ?></td>
                    <td><span class="badge <?= e(status_badge_class((string) $project['status'])) ?>"><?= e(humanize((string) $project['status'])) ?></span></td>
                    <td><?= (int) $project['proposal_count'] ?></td>
                    <td class="small text-muted"><?= e(dt((string) $project['created_at'], 'd M Y')) ?></td>
                    <td class="text-end"><a class="btn btn-sm btn-outline-primary" href="<?= e(route('admin.projects.show', ['id' => (int) $project['id']])) ?>">Review</a></td>
                </tr>
            <?php endforeach; ?>
            <?php if ($projects === []): ?>
                <tr><td colspan="7" class="text-center text-muted py-4">No projects match these filters.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require CIRCLEWORK_ROOT . '/resources/views/components/pagination.php'; ?>
