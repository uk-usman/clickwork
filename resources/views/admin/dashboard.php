<?php
/** @var array<string,int> $stats */
/** @var array<int,array<string,mixed>> $pendingProjects */
/** @var array<int,array<string,mixed>> $recentUsers */
/** @var array<int,array<string,mixed>> $recentAudit */
/** @var array<string,string> $revenue */
?>

<?php if ($stats['pending_projects'] > 0 || $stats['open_reports'] > 0 || $stats['open_disputes'] > 0 || $stats['pending_subscription_payments'] > 0): ?>
    <div class="alert alert-warning d-flex gap-2 align-items-start">
        <i class="bi bi-exclamation-triangle-fill mt-1"></i>
        <div>
            <strong>Items needing attention</strong>
            <ul class="mb-0 small">
                <?php if ($stats['pending_projects'] > 0): ?>
                    <li><a href="<?= e(route('admin.projects', ['status' => 'pending'])) ?>"><?= (int) $stats['pending_projects'] ?> project(s) awaiting moderation</a></li>
                <?php endif; ?>
                <?php if ($stats['open_reports'] > 0): ?>
                    <li><a href="<?= e(route('admin.reports')) ?>"><?= (int) $stats['open_reports'] ?> open report(s)</a></li>
                <?php endif; ?>
                <?php if ($stats['open_disputes'] > 0): ?>
                    <li><a href="<?= e(route('admin.disputes')) ?>"><?= (int) $stats['open_disputes'] ?> open dispute(s)</a></li>
                <?php endif; ?>
                <?php if ($stats['pending_subscription_payments'] > 0): ?>
                    <li><a href="<?= e(route('admin.subscription.payments')) ?>"><?= (int) $stats['pending_subscription_payments'] ?> subscription payment(s) awaiting manual verification</a></li>
                <?php endif; ?>
                <?php if ($stats['pending_featured'] > 0): ?>
                    <li><a href="<?= e(route('admin.subscriptions.featured')) ?>"><?= (int) $stats['pending_featured'] ?> featured listing(s) awaiting verification</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
<?php endif; ?>

<div class="row row-cols-2 row-cols-md-4 g-3 mb-4">
    <?php
    $cards = [
        ['label' => 'Registered users', 'value' => $stats['total_users'], 'icon' => 'bi-people'],
        ['label' => 'Active freelancers', 'value' => $stats['active_freelancers'], 'icon' => 'bi-person-badge'],
        ['label' => 'Active clients', 'value' => $stats['active_clients'], 'icon' => 'bi-briefcase'],
        ['label' => 'Pending approvals', 'value' => $stats['pending_projects'], 'icon' => 'bi-hourglass-split'],
        ['label' => 'Published projects', 'value' => $stats['published_projects'], 'icon' => 'bi-kanban'],
        ['label' => 'Active contracts', 'value' => $stats['active_contracts'], 'icon' => 'bi-file-earmark-text'],
        ['label' => 'Completed contracts', 'value' => $stats['completed_contracts'], 'icon' => 'bi-check2-circle'],
        ['label' => 'Reported payments', 'value' => $stats['reported_payments'], 'icon' => 'bi-cash-coin'],
        ['label' => 'Disputed payments', 'value' => $stats['disputed_payments'], 'icon' => 'bi-exclamation-octagon'],
        ['label' => 'Open disputes', 'value' => $stats['open_disputes'], 'icon' => 'bi-exclamation-diamond'],
        ['label' => 'Active subscriptions', 'value' => $stats['active_subscriptions'], 'icon' => 'bi-credit-card'],
        ['label' => 'Open reports', 'value' => $stats['open_reports'], 'icon' => 'bi-flag'],
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
    <div class="col-lg-6">
        <div class="cw-card mb-4">
            <div class="cw-card-header d-flex justify-content-between align-items-center">
                <span>Projects awaiting moderation</span>
                <a class="small" href="<?= e(route('admin.projects', ['status' => 'pending'])) ?>">View all</a>
            </div>
            <?php if ($pendingProjects === []): ?>
                <div class="cw-card-body text-muted">Nothing waiting for review.</div>
            <?php else: ?>
                <div class="list-group list-group-flush">
                    <?php foreach ($pendingProjects as $project): ?>
                        <a class="list-group-item list-group-item-action" href="<?= e(route('admin.projects.show', ['id' => (int) $project['id']])) ?>">
                            <div class="d-flex justify-content-between align-items-start gap-2">
                                <div>
                                    <strong><?= e(str_excerpt((string) $project['title'], 46)) ?></strong>
                                    <div class="small text-muted">
                                        <?= e($project['client_name']) ?> · <?= e($project['category_name'] ?? 'General') ?>
                                    </div>
                                </div>
                                <span class="small text-muted"><?= e(dt((string) $project['created_at'], 'd M Y')) ?></span>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="cw-card">
            <div class="cw-card-header">Recently registered users</div>
            <div class="list-group list-group-flush">
                <?php foreach ($recentUsers as $user): ?>
                    <a class="list-group-item list-group-item-action d-flex justify-content-between align-items-center"
                       href="<?= e(route('admin.users.show', ['id' => (int) $user['id']])) ?>">
                        <span>
                            <strong><?= e($user['name']) ?></strong>
                            <div class="small text-muted"><?= e($user['email']) ?></div>
                        </span>
                        <span class="badge <?= e(status_badge_class((string) $user['status'])) ?>"><?= e(humanize((string) $user['status'])) ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="cw-card mb-4">
            <div class="cw-card-header">Recorded revenue (manually verified)</div>
            <div class="cw-card-body">
                <dl class="row g-3 mb-2">
                    <dt class="col-7 text-muted fw-normal">Verified subscription payments</dt>
                    <dd class="col-5 text-end fw-semibold"><?= e(money($revenue['verified_subscription_total'])) ?></dd>
                    <dt class="col-7 text-muted fw-normal">Featured listing payments</dt>
                    <dd class="col-5 text-end fw-semibold"><?= e(money($revenue['featured_total'])) ?></dd>
                </dl>
                <p class="small text-muted mb-0">
                    These totals come from records administrators have verified. No payment gateway is integrated, so
                    no automatic processing is claimed.
                </p>
            </div>
        </div>

        <div class="cw-card">
            <div class="cw-card-header d-flex justify-content-between align-items-center">
                <span>Recent audit activity</span>
                <a class="small" href="<?= e(route('admin.audit')) ?>">Full log</a>
            </div>
            <div class="cw-card-body">
                <ul class="cw-timeline mb-0">
                    <?php foreach ($recentAudit as $entry): ?>
                        <li>
                            <div class="small fw-semibold"><?= e(humanize((string) $entry['action'])) ?></div>
                            <div class="small text-muted"><?= e(str_excerpt((string) $entry['description'], 90)) ?></div>
                            <div class="small text-muted">
                                <?= e($entry['actor_name'] ?? 'System') ?> · <?= e(dt((string) $entry['created_at'])) ?>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>
</div>
