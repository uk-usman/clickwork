<?php
/** @var array<string,mixed> $project */
/** @var array<int,array<string,mixed>> $skills */
/** @var array<int,array<string,mixed>> $proposals */
/** @var array<string,mixed>|null $contract */
/** @var array<int,array<string,mixed>> $featuredListings */
$canEdit = in_array((string) $project['status'], ['draft', 'rejected', 'pending'], true);
?>
<div class="row g-4">
    <div class="col-lg-8">
        <?php if ((string) $project['status'] === 'rejected' && !empty($project['moderation_note'])): ?>
            <div class="alert alert-danger">
                <strong>This project was not approved.</strong>
                <div class="small mt-1"><?= e((string) $project['moderation_note']) ?></div>
                <div class="small mt-1">Review the Community Guidelines, update the project and resubmit it for moderation.</div>
            </div>
        <?php endif; ?>

        <?php if ((string) $project['status'] === 'pending'): ?>
            <div class="alert alert-warning">
                <i class="bi bi-hourglass-split me-1"></i>
                This project is awaiting moderation. It becomes publicly visible once approved.
            </div>
        <?php endif; ?>

        <div class="cw-card mb-4">
            <div class="cw-card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <span>Project overview</span>
                <div class="d-flex gap-1">
                    <?php if ($canEdit): ?>
                        <a class="btn btn-sm btn-outline-primary" href="<?= e(route('client.projects.edit', ['id' => (int) $project['id']])) ?>">Edit</a>
                    <?php endif; ?>
                    <?php if (in_array((string) $project['status'], ['published', 'in_progress'], true)): ?>
                        <a class="btn btn-sm btn-outline-secondary" href="<?= e(route('projects.show', ['id' => (int) $project['id']])) ?>">Public view</a>
                    <?php endif; ?>
                </div>
            </div>
            <div class="cw-card-body">
                <div class="d-flex flex-wrap gap-2 mb-3">
                    <span class="badge <?= e(status_badge_class((string) $project['status'])) ?>"><?= e(humanize((string) $project['status'])) ?></span>
                    <span class="cw-chip cw-chip--royal"><?= e($project['category_name'] ?? 'General') ?></span>
                    <?php if ((int) $project['is_featured'] === 1): ?>
                        <span class="badge text-bg-primary">Featured until <?= e(dt($project['featured_until'] ?? null, 'd M Y')) ?></span>
                    <?php endif; ?>
                </div>

                <h2 class="h5"><?= e($project['title']) ?></h2>
                <div class="text-muted" style="white-space: pre-wrap;"><?= e($project['description']) ?></div>

                <?php if ($skills !== []): ?>
                    <div class="mt-3">
                        <?php foreach ($skills as $skill): ?>
                            <span class="cw-chip"><?= e($skill['name']) ?></span>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="cw-card">
            <div class="cw-card-header d-flex justify-content-between align-items-center">
                <span>Proposals received (<?= count($proposals) ?>)</span>
                <a class="small" href="<?= e(route('client.projects.proposals', ['id' => (int) $project['id']])) ?>">Full comparison</a>
            </div>
            <?php if ($proposals === []): ?>
                <div class="cw-card-body text-muted">No proposals yet.</div>
            <?php else: ?>
                <div class="list-group list-group-flush">
                    <?php foreach (array_slice($proposals, 0, 5) as $proposal): ?>
                        <div class="list-group-item d-flex justify-content-between align-items-center gap-2">
                            <div>
                                <strong><?= e($proposal['freelancer_name']) ?></strong>
                                <div class="small text-muted">
                                    Bid <?= e(money($proposal['bid_amount'], (string) $proposal['currency'])) ?>
                                    · <?= (int) $proposal['estimated_days'] ?> day(s)
                                    · <?= e(dt((string) $proposal['created_at'], 'd M Y')) ?>
                                </div>
                            </div>
                            <span class="badge <?= e(status_badge_class((string) $proposal['status'])) ?>"><?= e(humanize((string) $proposal['status'])) ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="cw-card mb-4">
            <div class="cw-card-header">Summary</div>
            <div class="cw-card-body">
                <dl class="row g-2 small mb-0">
                    <dt class="col-6 text-muted fw-normal">Reference</dt><dd class="col-6 text-end"><?= e($project['reference']) ?></dd>
                    <dt class="col-6 text-muted fw-normal">Budget</dt>
                    <dd class="col-6 text-end">
                        <?php if (($project['budget_min'] ?? null) !== null): ?>
                            <?= e(money($project['budget_min'], (string) $project['currency'])) ?> –
                            <?= e(money($project['budget_max'], (string) $project['currency'])) ?>
                        <?php else: ?>Not set<?php endif; ?>
                    </dd>
                    <dt class="col-6 text-muted fw-normal">Deadline</dt>
                    <dd class="col-6 text-end"><?= ($project['deadline'] ?? null) ? e(date('d M Y', strtotime((string) $project['deadline']))) : 'Flexible' ?></dd>
                    <dt class="col-6 text-muted fw-normal">Visibility</dt><dd class="col-6 text-end"><?= e(humanize((string) $project['visibility'])) ?></dd>
                    <dt class="col-6 text-muted fw-normal">Views</dt><dd class="col-6 text-end"><?= (int) $project['views_count'] ?></dd>
                </dl>
            </div>
        </div>

        <?php if ($contract !== null): ?>
            <div class="cw-card mb-4">
                <div class="cw-card-header">Contract</div>
                <div class="cw-card-body">
                    <p class="small mb-2">
                        Contract <strong><?= e($contract['reference']) ?></strong> is
                        <span class="badge <?= e(status_badge_class((string) $contract['status'])) ?>"><?= e(humanize((string) $contract['status'])) ?></span>
                    </p>
                    <a class="btn btn-primary w-100" href="<?= e(route('contracts.show', ['id' => (int) $contract['id']])) ?>">Open contract</a>
                </div>
            </div>
        <?php endif; ?>

        <?php if (in_array((string) $project['status'], ['published', 'in_progress'], true)): ?>
            <div class="cw-card mb-4">
                <div class="cw-card-header">Featured placement</div>
                <div class="cw-card-body">
                    <?php $pendingListing = null; foreach ($featuredListings as $l) { if ((string) $l['status'] === 'pending') { $pendingListing = $l; } } ?>
                    <?php if ($pendingListing !== null): ?>
                        <div class="cw-notice cw-notice--warning mb-0">
                            A featured placement request for this project is awaiting manual payment verification by an administrator.
                        </div>
                    <?php else: ?>
                        <p class="small text-muted">
                            Feature this project for <strong><?= e(money($featuredPrice)) ?></strong>
                            for <strong><?= (int) $featuredDays ?> days</strong>. It appears above standard listings
                            with a clear “Featured” label.
                        </p>
                        <form method="post" action="<?= e(route('client.projects.feature', ['id' => (int) $project['id']])) ?>"
                              data-confirm="Request a featured placement for this project? An administrator must verify your manual payment before it activates.">
                            <?= csrf_field() ?>
                            <div class="mb-2">
                                <label class="form-label small" for="payment_method">Payment method</label>
                                <select class="form-select form-select-sm" id="payment_method" name="payment_method">
                                    <option value="bank_transfer">Bank transfer</option>
                                    <option value="cash">Cash</option>
                                    <option value="mobile_money">Mobile money</option>
                                    <option value="card_transfer">Card transfer</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                            <div class="mb-2">
                                <label class="form-label small" for="payer_reference">Payment reference (optional)</label>
                                <input type="text" class="form-control form-control-sm" id="payer_reference" name="payer_reference">
                            </div>
                            <button type="submit" class="btn btn-navy btn-sm w-100">Request featured placement</button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <div class="cw-card">
            <div class="cw-card-header">Recent activity</div>
            <div class="cw-card-body small text-muted">
                Created <?= e(dt((string) $project['created_at'])) ?><br>
                <?php if (($project['published_at'] ?? null) !== null): ?>
                    Published <?= e(dt((string) $project['published_at'])) ?><br>
                <?php endif; ?>
                <?php if (($project['moderated_at'] ?? null) !== null): ?>
                    Moderated <?= e(dt((string) $project['moderated_at'])) ?><br>
                <?php endif; ?>
                Last updated <?= e(dt((string) $project['updated_at'])) ?>
            </div>
        </div>
    </div>
</div>
