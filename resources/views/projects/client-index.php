<?php
/** @var array<int,array<string,mixed>> $projects */
/** @var string $status */
?>
<div class="d-flex flex-wrap gap-2 mb-3">
    <a class="btn btn-sm <?= $status === '' ? 'btn-primary' : 'btn-outline-primary' ?>" href="<?= e(route('client.projects')) ?>">All</a>
    <?php foreach (['draft', 'pending', 'published', 'in_progress', 'completed', 'rejected', 'cancelled'] as $option): ?>
        <a class="btn btn-sm <?= $status === $option ? 'btn-primary' : 'btn-outline-primary' ?>"
           href="<?= e(route('client.projects', ['status' => $option])) ?>"><?= e(humanize($option)) ?></a>
    <?php endforeach; ?>
</div>

<?php if ($projects === []): ?>
    <div class="cw-empty">
        <i class="bi bi-kanban"></i>
        <h2 class="h6 mt-3">No projects in this view</h2>
        <p class="text-muted mb-3">Post a project to start receiving proposals from freelancers.</p>
        <a href="<?= e(route('client.projects.create')) ?>" class="btn btn-primary">Post a project</a>
    </div>
<?php else: ?>
    <div class="cw-card">
        <div class="table-responsive">
            <table class="table cw-table mb-0 align-middle">
                <thead>
                    <tr>
                        <th>Project</th>
                        <th>Status</th>
                        <th>Budget</th>
                        <th class="text-end">Proposals</th>
                        <th>Created</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($projects as $project): ?>
                    <tr>
                        <td>
                            <a href="<?= e(route('client.projects.show', ['id' => (int) $project['id']])) ?>">
                                <?= e(str_excerpt((string) $project['title'], 48)) ?>
                            </a>
                            <div class="small text-muted">
                                <?= e($project['reference']) ?>
                                <?php if ((int) $project['is_featured'] === 1): ?>
                                    <span class="badge text-bg-primary ms-1">Featured</span>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td><span class="badge <?= e(status_badge_class((string) $project['status'])) ?>"><?= e(humanize((string) $project['status'])) ?></span></td>
                        <td class="small">
                            <?php if (($project['budget_min'] ?? null) !== null): ?>
                                <?= e(money($project['budget_min'], (string) $project['currency'])) ?>
                                – <?= e(money($project['budget_max'], (string) $project['currency'])) ?>
                            <?php else: ?>
                                <span class="text-muted">Not set</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end"><?= (int) $project['proposal_count'] ?></td>
                        <td class="small text-muted"><?= e(dt((string) $project['created_at'], 'd M Y')) ?></td>
                        <td class="text-end">
                            <div class="d-flex gap-1 justify-content-end">
                                <a class="btn btn-sm btn-outline-secondary" href="<?= e(route('client.projects.show', ['id' => (int) $project['id']])) ?>">Open</a>
                                <?php if (in_array((string) $project['status'], ['draft', 'rejected', 'pending'], true)): ?>
                                    <a class="btn btn-sm btn-outline-primary" href="<?= e(route('client.projects.edit', ['id' => (int) $project['id']])) ?>">Edit</a>
                                <?php endif; ?>
                                <a class="btn btn-sm btn-primary" href="<?= e(route('client.projects.proposals', ['id' => (int) $project['id']])) ?>">Proposals</a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>
