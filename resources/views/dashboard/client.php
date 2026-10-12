<?php
/** @var array<string,int> $stats */
/** @var array<int,array<string,mixed>> $recentProjects */
/** @var array<int,array<string,mixed>> $recentProposals */
/** @var array<int,array<string,mixed>> $contracts */
/** @var array<string,mixed>|null $subscription */
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
        ['label' => 'Active projects', 'value' => $stats['active_projects'], 'icon' => 'bi-kanban'],
        ['label' => 'Awaiting approval', 'value' => $stats['pending_projects'], 'icon' => 'bi-hourglass-split'],
        ['label' => 'New proposals', 'value' => $stats['proposals_received'], 'icon' => 'bi-send'],
        ['label' => 'Active contracts', 'value' => $stats['active_contracts'], 'icon' => 'bi-file-earmark-text'],
        ['label' => 'Completed', 'value' => $stats['completed_contracts'], 'icon' => 'bi-check2-circle'],
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

<div class="row g-4">
    <div class="col-lg-7">
        <div class="cw-card mb-4">
            <div class="cw-card-header d-flex justify-content-between align-items-center">
                <span>Recent projects</span>
                <a class="small" href="<?= e(route('client.projects')) ?>">View all</a>
            </div>
            <?php if ($recentProjects === []): ?>
                <div class="cw-card-body text-center py-4">
                    <p class="text-muted mb-3">You have not posted a project yet.</p>
                    <a href="<?= e(route('client.projects.create')) ?>" class="btn btn-primary">Post your first project</a>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table cw-table mb-0 align-middle">
                        <thead><tr><th>Project</th><th>Status</th><th class="text-end">Proposals</th><th></th></tr></thead>
                        <tbody>
                        <?php foreach ($recentProjects as $project): ?>
                            <tr>
                                <td>
                                    <a href="<?= e(route('client.projects.show', ['id' => (int) $project['id']])) ?>">
                                        <?= e(str_excerpt((string) $project['title'], 46)) ?>
                                    </a>
                                    <div class="small text-muted"><?= e($project['reference']) ?> · <?= e(dt((string) $project['created_at'], 'd M Y')) ?></div>
                                </td>
                                <td><span class="badge <?= e(status_badge_class((string) $project['status'])) ?>"><?= e(humanize((string) $project['status'])) ?></span></td>
                                <td class="text-end"><?= (int) $project['proposal_count'] ?></td>
                                <td class="text-end">
                                    <a class="btn btn-sm btn-outline-primary" href="<?= e(route('client.projects.proposals', ['id' => (int) $project['id']])) ?>">Proposals</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <div class="cw-card">
            <div class="cw-card-header">Contracts needing attention</div>
            <?php if ($contracts === []): ?>
                <div class="cw-card-body text-muted">No contracts yet. Contracts are created when you accept a proposal.</div>
            <?php else: ?>
                <div class="list-group list-group-flush">
                    <?php foreach ($contracts as $contract): ?>
                        <a class="list-group-item list-group-item-action d-flex justify-content-between align-items-center gap-2"
                           href="<?= e(route('contracts.show', ['id' => (int) $contract['id']])) ?>">
                            <span>
                                <strong><?= e(str_excerpt((string) $contract['title'], 44)) ?></strong>
                                <div class="small text-muted">
                                    <?= e($contract['reference']) ?> · with <?= e($contract['freelancer_name']) ?>
                                </div>
                            </span>
                            <span class="badge <?= e(status_badge_class((string) $contract['status'])) ?>"><?= e(humanize((string) $contract['status'])) ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="cw-card mb-4">
            <div class="cw-card-header">New proposals</div>
            <?php if ($recentProposals === []): ?>
                <div class="cw-card-body text-muted">No new proposals right now.</div>
            <?php else: ?>
                <div class="list-group list-group-flush">
                    <?php foreach ($recentProposals as $proposal): ?>
                        <div class="list-group-item">
                            <div class="d-flex justify-content-between align-items-start gap-2">
                                <div>
                                    <strong><?= e($proposal['freelancer_name']) ?></strong>
                                    <div class="small text-muted"><?= e(str_excerpt((string) $proposal['project_title'], 34)) ?></div>
                                </div>
                                <span class="badge <?= e(status_badge_class((string) $proposal['status'])) ?>"><?= e(humanize((string) $proposal['status'])) ?></span>
                            </div>
                            <div class="small mt-2">
                                Bid <strong><?= e(money($proposal['bid_amount'], (string) $proposal['currency'])) ?></strong>
                                · <?= (int) $proposal['estimated_days'] ?> day(s)
                            </div>
                            <a class="btn btn-sm btn-outline-primary mt-2"
                               href="<?= e(route('client.projects.proposals', ['id' => (int) $proposal['project_id']])) ?>">Review proposals</a>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="cw-card">
            <div class="cw-card-header">Your plan</div>
            <div class="cw-card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="h5 mb-0"><?= e((string) ($subscription['plan_name'] ?? $subscription['name'] ?? 'Free')) ?></div>
                        <div class="text-muted small">
                            <?php if (($subscription['expires_at'] ?? null) !== null): ?>
                                Renews/expires <?= e(dt((string) $subscription['expires_at'], 'd M Y')) ?>
                            <?php else: ?>
                                No expiry
                            <?php endif; ?>
                        </div>
                    </div>
                    <i class="bi bi-stars fs-3 text-primary"></i>
                </div>
                <a class="btn btn-outline-primary w-100 mt-3" href="<?= e(route('subscriptions.index')) ?>">Manage subscription</a>
            </div>
        </div>
    </div>
</div>
