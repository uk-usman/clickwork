<?php
/** @var array<int,array<string,mixed>> $proposals */
/** @var string $status */
?>
<div class="d-flex flex-wrap gap-2 mb-3">
    <a class="btn btn-sm <?= $status === '' ? 'btn-primary' : 'btn-outline-primary' ?>" href="<?= e(route('freelancer.proposals')) ?>">All</a>
    <?php foreach (['submitted', 'shortlisted', 'accepted', 'rejected', 'withdrawn'] as $option): ?>
        <a class="btn btn-sm <?= $status === $option ? 'btn-primary' : 'btn-outline-primary' ?>"
           href="<?= e(route('freelancer.proposals', ['status' => $option])) ?>"><?= e(humanize($option)) ?></a>
    <?php endforeach; ?>
</div>

<?php if ($proposals === []): ?>
    <div class="cw-empty">
        <i class="bi bi-send"></i>
        <h2 class="h6 mt-3">No proposals in this view</h2>
        <p class="text-muted mb-3">Browse open projects and submit your first proposal.</p>
        <a href="<?= e(route('projects.index')) ?>" class="btn btn-primary">Browse projects</a>
    </div>
<?php else: ?>
    <div class="cw-card">
        <div class="table-responsive">
            <table class="table cw-table mb-0 align-middle">
                <thead><tr><th>Project</th><th>Client</th><th>Bid</th><th>Delivery</th><th>Status</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($proposals as $proposal): ?>
                    <tr>
                        <td>
                            <a href="<?= e(route('freelancer.proposals.show', ['id' => (int) $proposal['id']])) ?>">
                                <?= e(str_excerpt((string) $proposal['project_title'], 46)) ?>
                            </a>
                            <div class="small text-muted"><?= e($proposal['reference']) ?> · <?= e(dt((string) $proposal['created_at'], 'd M Y')) ?></div>
                        </td>
                        <td class="small"><?= e($proposal['client_name']) ?></td>
                        <td><?= e(money($proposal['bid_amount'], (string) $proposal['currency'])) ?></td>
                        <td class="small"><?= (int) $proposal['estimated_days'] ?> day(s)</td>
                        <td><span class="badge <?= e(status_badge_class((string) $proposal['status'])) ?>"><?= e(humanize((string) $proposal['status'])) ?></span></td>
                        <td class="text-end">
                            <div class="d-flex gap-1 justify-content-end">
                                <a class="btn btn-sm btn-outline-secondary" href="<?= e(route('projects.show', ['id' => (int) $proposal['project_id']])) ?>">Project</a>
                                <?php if (in_array((string) $proposal['status'], ['submitted', 'shortlisted'], true)): ?>
                                    <form method="post" action="<?= e(route('freelancer.proposals.withdraw', ['id' => (int) $proposal['id']])) ?>"
                                          data-confirm="Withdraw this proposal? The client will no longer see it as active.">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Withdraw</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>
