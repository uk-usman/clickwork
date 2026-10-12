<?php
/** @var array<string,mixed> $project */
/** @var array<int,array<string,mixed>> $proposals */
/** @var array<string,mixed>|null $contract */
?>
<div class="cw-card mb-4">
    <div class="cw-card-body d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div>
            <h2 class="h5 mb-1"><?= e($project['title']) ?></h2>
            <p class="text-muted small mb-0">
                <?= e($project['reference']) ?> ·
                Budget
                <?php if (($project['budget_min'] ?? null) !== null): ?>
                    <?= e(money($project['budget_min'], (string) $project['currency'])) ?> –
                    <?= e(money($project['budget_max'], (string) $project['currency'])) ?>
                <?php else: ?>not set<?php endif; ?>
                · Status <?= e(humanize((string) $project['status'])) ?>
            </p>
        </div>
        <div class="d-flex gap-2">
            <a class="btn btn-sm btn-outline-secondary" href="<?= e(route('client.projects.show', ['id' => (int) $project['id']])) ?>">Project page</a>
            <?php if ($contract !== null): ?>
                <a class="btn btn-sm btn-primary" href="<?= e(route('contracts.show', ['id' => (int) $contract['id']])) ?>">Open contract</a>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php if ($proposals === []): ?>
    <div class="cw-empty">
        <i class="bi bi-inbox"></i>
        <h2 class="h6 mt-3">No proposals yet</h2>
        <p class="text-muted mb-0">Freelancers will appear here as soon as they submit proposals.</p>
    </div>
<?php else: ?>
    <p class="text-muted small">Sorted by bid amount (lowest first) so you can compare quickly. Shortlist before awarding.</p>

    <div class="row g-4">
        <?php foreach ($proposals as $proposal): ?>
            <div class="col-lg-6">
                <div class="cw-card h-100">
                    <div class="cw-card-header d-flex justify-content-between align-items-center">
                        <span><?= e($proposal['freelancer_name']) ?></span>
                        <span class="badge <?= e(status_badge_class((string) $proposal['status'])) ?>"><?= e(humanize((string) $proposal['status'])) ?></span>
                    </div>
                    <div class="cw-card-body">
                        <div class="d-flex align-items-center gap-3 mb-3">
                            <?php $avatar = avatar_url($proposal['avatar_path'] ?? null); ?>
                            <?php if ($avatar !== null): ?>
                                <img src="<?= e($avatar) ?>" alt="" class="cw-avatar cw-avatar--lg">
                            <?php else: ?>
                                <span class="cw-avatar cw-avatar--lg"><?= e(initials((string) $proposal['freelancer_name'])) ?></span>
                            <?php endif; ?>
                            <div class="small">
                                <div class="fw-semibold"><?= e((string) ($proposal['freelancer_title'] ?? 'Freelancer')) ?></div>
                                <div class="text-muted">
                                    <i class="bi bi-geo-alt"></i>
                                    <?= e(trim(($proposal['city'] ?? '') . ', ' . ($proposal['state'] ?? ''), ', ') ?: 'Nigeria') ?>
                                </div>
                                <div class="text-warning">
                                    <?php $rating = (float) ($proposal['rating_average'] ?? 0); ?>
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <i class="bi <?= $i <= round($rating) ? 'bi-star-fill' : 'bi-star' ?>"></i>
                                    <?php endfor; ?>
                                    <span class="text-muted ms-1">
                                        <?= $rating > 0 ? number_format($rating, 1) : 'New' ?>
                                        · <?= (int) ($proposal['completed_contracts'] ?? 0) ?> completed
                                    </span>
                                </div>
                            </div>
                        </div>

                        <dl class="row g-2 small mb-3">
                            <dt class="col-6 text-muted fw-normal">Bid amount</dt>
                            <dd class="col-6 text-end fw-semibold"><?= e(money($proposal['bid_amount'], (string) $proposal['currency'])) ?></dd>
                            <dt class="col-6 text-muted fw-normal">Delivery estimate</dt>
                            <dd class="col-6 text-end"><?= (int) $proposal['estimated_days'] ?> day(s)</dd>
                            <dt class="col-6 text-muted fw-normal">Submitted</dt>
                            <dd class="col-6 text-end"><?= e(dt((string) $proposal['created_at'], 'd M Y')) ?></dd>
                        </dl>

                        <h3 class="h6">Cover letter</h3>
                        <p class="small text-muted" style="white-space: pre-wrap;"><?= e(str_excerpt((string) $proposal['cover_letter'], 420)) ?></p>

                        <div class="d-flex flex-wrap gap-2 mt-3">
                            <a class="btn btn-sm btn-outline-secondary" href="<?= e(route('freelancers.show', ['id' => (int) $proposal['freelancer_id']])) ?>">View profile</a>

                            <?php if ((string) $proposal['status'] === 'submitted'): ?>
                                <form method="post" action="<?= e(route('client.proposals.decide', ['id' => (int) $proposal['id']])) ?>">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="decision" value="shortlist">
                                    <button type="submit" class="btn btn-sm btn-outline-primary">Shortlist</button>
                                </form>
                            <?php elseif ((string) $proposal['status'] === 'shortlisted'): ?>
                                <form method="post" action="<?= e(route('client.proposals.decide', ['id' => (int) $proposal['id']])) ?>">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="decision" value="unshortlist">
                                    <button type="submit" class="btn btn-sm btn-outline-secondary">Remove from shortlist</button>
                                </form>
                            <?php endif; ?>

                            <?php if (in_array((string) $proposal['status'], ['submitted', 'shortlisted'], true)): ?>
                                <form method="post" action="<?= e(route('client.proposals.decide', ['id' => (int) $proposal['id']])) ?>"
                                      data-confirm="Reject this proposal?">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="decision" value="reject">
                                    <button type="submit" class="btn btn-sm btn-outline-danger">Reject</button>
                                </form>
                            <?php endif; ?>

                            <?php if ($contract === null && in_array((string) $proposal['status'], ['submitted', 'shortlisted'], true)): ?>
                                <form method="post" action="<?= e(route('client.proposals.award', ['id' => (int) $proposal['id']])) ?>"
                                      data-confirm="Accept this proposal? A contract will be created and all other pending proposals for this project will be rejected. This cannot be undone.">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="btn btn-sm btn-primary">Accept and create contract</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="cw-notice cw-notice--warning mt-4">
        Accepting a proposal creates the contract and rejects every other pending proposal for this project.
        Each project is single-award, and the operation runs in a database transaction so two freelancers can
        never be awarded the same project.
    </div>
<?php endif; ?>
