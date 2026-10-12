<?php
/** @var array<string,int> $stats */
/** @var array<int,array<string,mixed>> $proposals */
/** @var array<int,array<string,mixed>> $contracts */
/** @var array<int,array<string,mixed>> $openProjects */
/** @var array<string,mixed>|null $subscription */
/** @var array<string,int> $limits */
/** @var array<int,array<string,mixed>> $announcements */
?>

<?php foreach ($announcements as $announcement): ?>
    <div class="alert alert-info d-flex gap-2 align-items-start">
        <i class="bi bi-megaphone mt-1"></i>
        <div>
            <strong><?= e($announcement['title']) ?></strong>
            <div class="small"><?= e(str_excerpt((string) $announcement['body'], 220)) ?></div>
        </div>
    </div>
<?php endforeach; ?>

<div class="row row-cols-2 row-cols-lg-5 g-3 mb-4">
    <?php
    $cards = [
        ['label' => 'Active proposals', 'value' => $stats['active_proposals'], 'icon' => 'bi-send'],
        ['label' => 'Won proposals', 'value' => $stats['won_proposals'], 'icon' => 'bi-trophy'],
        ['label' => 'Active contracts', 'value' => $stats['active_contracts'], 'icon' => 'bi-file-earmark-text'],
        ['label' => 'Completed', 'value' => $stats['completed_contracts'], 'icon' => 'bi-check2-circle'],
        ['label' => 'Portfolio items', 'value' => $stats['portfolio_items'], 'icon' => 'bi-images'],
    ];
    foreach ($cards as $card): ?>
        <div class="col">
            <div class="cw-stat">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="cw-stat__value"><?= (int) $card['value'] ?></div>
                        <div class="cw-stat__label"><?= e($card['label']) ?></div>
                    </div>
                    <span class="cw-stat__icon"><i class="bi <?= e($card['icon']) ?>"></i></span>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<?php if ($stats['portfolio_items'] === 0): ?>
    <div class="cw-notice mb-4 d-flex justify-content-between align-items-center flex-wrap gap-2">
        <span><strong>Tip:</strong> profiles with portfolio items and listed skills receive more client interest.</span>
        <a href="<?= e(route('freelancer.portfolio')) ?>" class="btn btn-sm btn-primary">Add portfolio items</a>
    </div>
<?php endif; ?>

<div class="row g-4">
    <div class="col-lg-7">
        <div class="cw-card mb-4">
            <div class="cw-card-header d-flex justify-content-between align-items-center">
                <span>My recent proposals</span>
                <a class="small" href="<?= e(route('freelancer.proposals')) ?>">View all</a>
            </div>
            <?php if ($proposals === []): ?>
                <div class="cw-card-body text-center py-4">
                    <p class="text-muted mb-3">You have not submitted any proposals yet.</p>
                    <a href="<?= e(route('projects.index')) ?>" class="btn btn-primary">Browse open projects</a>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table cw-table mb-0 align-middle">
                        <thead><tr><th>Project</th><th>Bid</th><th>Status</th><th></th></tr></thead>
                        <tbody>
                        <?php foreach ($proposals as $proposal): ?>
                            <tr>
                                <td>
                                    <a href="<?= e(route('freelancer.proposals.show', ['id' => (int) $proposal['id']])) ?>">
                                        <?= e(str_excerpt((string) $proposal['project_title'], 40)) ?>
                                    </a>
                                    <div class="small text-muted"><?= e($proposal['client_name']) ?> · <?= e(dt((string) $proposal['created_at'], 'd M Y')) ?></div>
                                </td>
                                <td><?= e(money($proposal['bid_amount'], (string) $proposal['currency'])) ?></td>
                                <td><span class="badge <?= e(status_badge_class((string) $proposal['status'])) ?>"><?= e(humanize((string) $proposal['status'])) ?></span></td>
                                <td class="text-end">
                                    <a class="btn btn-sm btn-outline-primary" href="<?= e(route('projects.show', ['id' => (int) $proposal['project_id']])) ?>">Project</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <div class="cw-card">
            <div class="cw-card-header d-flex justify-content-between align-items-center">
                <span>Newest open projects</span>
                <a class="small" href="<?= e(route('projects.index')) ?>">Browse all</a>
            </div>
            <div class="list-group list-group-flush">
                <?php if ($openProjects === []): ?>
                    <div class="list-group-item text-muted">No published projects yet.</div>
                <?php else: ?>
                    <?php foreach ($openProjects as $project): ?>
                        <a class="list-group-item list-group-item-action" href="<?= e(route('projects.show', ['id' => (int) $project['id']])) ?>">
                            <div class="d-flex justify-content-between align-items-start gap-2">
                                <div>
                                    <strong><?= e(str_excerpt((string) $project['title'], 46)) ?></strong>
                                    <div class="small text-muted">
                                        <?= e($project['category_name'] ?? 'General') ?>
                                        <?php if (($project['budget_min'] ?? null) !== null): ?>
                                            · <?= e(money($project['budget_min'], (string) $project['currency'])) ?>
                                            – <?= e(money($project['budget_max'], (string) $project['currency'])) ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <span class="small text-muted"><?= (int) $project['proposal_count'] ?> bids</span>
                            </div>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="cw-card mb-4">
            <div class="cw-card-header">My contracts</div>
            <?php if ($contracts === []): ?>
                <div class="cw-card-body text-muted">No contracts yet.</div>
            <?php else: ?>
                <div class="list-group list-group-flush">
                    <?php foreach ($contracts as $contract): ?>
                        <a class="list-group-item list-group-item-action d-flex justify-content-between align-items-center gap-2"
                           href="<?= e(route('contracts.show', ['id' => (int) $contract['id']])) ?>">
                            <span>
                                <strong><?= e(str_excerpt((string) $contract['title'], 40)) ?></strong>
                                <div class="small text-muted"><?= e($contract['reference']) ?> · <?= e($contract['client_name']) ?></div>
                            </span>
                            <span class="badge <?= e(status_badge_class((string) $contract['status'])) ?>"><?= e(humanize((string) $contract['status'])) ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="cw-card">
            <div class="cw-card-header">Your plan and limits</div>
            <div class="cw-card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <div class="h5 mb-0"><?= e((string) ($subscription['plan_name'] ?? $subscription['name'] ?? 'Free')) ?></div>
                        <div class="text-muted small">
                            <?php if (($subscription['expires_at'] ?? null) !== null): ?>
                                Expires <?= e(dt((string) $subscription['expires_at'], 'd M Y')) ?>
                            <?php else: ?>
                                No expiry
                            <?php endif; ?>
                        </div>
                    </div>
                    <i class="bi bi-stars fs-3 text-primary"></i>
                </div>
                <ul class="list-unstyled small mb-3">
                    <li><i class="bi bi-check2 text-success me-1"></i>Portfolio items: <?= (int) $limits['portfolio_items'] ?></li>
                    <li><i class="bi bi-check2 text-success me-1"></i>Proposals per day: <?= (int) $limits['proposals_per_day'] ?></li>
                    <li><i class="bi bi-check2 text-success me-1"></i>Profile analytics: <?= ((int) $limits['profile_analytics'] === 1) ? 'Included' : 'Not included' ?></li>
                </ul>
                <a class="btn btn-outline-primary w-100" href="<?= e(route('subscriptions.index')) ?>">Compare plans</a>
            </div>
        </div>
    </div>
</div>
