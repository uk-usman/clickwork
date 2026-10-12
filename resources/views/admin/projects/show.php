<?php
/** @var array<string,mixed> $project */
/** @var array<int,array<string,mixed>> $skills */
/** @var array<int,array<string,mixed>> $proposals */
$canModerate = in_array((string) $project['status'], ['pending', 'rejected'], true);
?>
<div class="row g-4">
    <div class="col-lg-8">
        <div class="cw-card mb-4">
            <div class="cw-card-header d-flex justify-content-between align-items-center">
                <span>Project <?= e((string) $project['reference']) ?></span>
                <span class="badge <?= e(status_badge_class((string) $project['status'])) ?>"><?= e(humanize((string) $project['status'])) ?></span>
            </div>
            <div class="cw-card-body">
                <h2 class="h5"><?= e($project['title']) ?></h2>
                <p class="text-muted small">
                    Client <?= e($project['client_name']) ?> (<?= e((string) $project['client_email']) ?>) ·
                    <?= e((string) ($project['category_name'] ?? 'General')) ?> ·
                    submitted <?= e(dt((string) $project['created_at'])) ?>
                </p>

                <div class="text-muted" style="white-space: pre-wrap;"><?= e($project['description']) ?></div>

                <?php if (!empty($project['required_skills_text'])): ?>
                    <h3 class="h6 mt-4">Required skills</h3>
                    <p class="text-muted"><?= e((string) $project['required_skills_text']) ?></p>
                <?php endif; ?>

                <?php if ($skills !== []): ?>
                    <div class="mt-2">
                        <?php foreach ($skills as $skill): ?>
                            <span class="cw-chip"><?= e($skill['name']) ?></span>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($project['expected_deliverables'])): ?>
                    <h3 class="h6 mt-4">Expected deliverables</h3>
                    <div class="text-muted" style="white-space: pre-wrap;"><?= e((string) $project['expected_deliverables']) ?></div>
                <?php endif; ?>
            </div>
        </div>

        <div class="cw-card">
            <div class="cw-card-header">Proposals (<?= count($proposals) ?>)</div>
            <?php if ($proposals === []): ?>
                <div class="cw-card-body text-muted">No proposals submitted.</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table cw-table mb-0">
                        <thead><tr><th>Freelancer</th><th>Bid</th><th>Days</th><th>Status</th></tr></thead>
                        <tbody>
                        <?php foreach ($proposals as $proposal): ?>
                            <tr>
                                <td><?= e((string) $proposal['freelancer_name']) ?></td>
                                <td><?= e(money($proposal['bid_amount'], (string) $proposal['currency'])) ?></td>
                                <td><?= (int) $proposal['estimated_days'] ?></td>
                                <td><span class="badge <?= e(status_badge_class((string) $proposal['status'])) ?>"><?= e(humanize((string) $proposal['status'])) ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="cw-card mb-4">
            <div class="cw-card-header">Summary</div>
            <div class="cw-card-body">
                <dl class="row g-2 small mb-0">
                    <dt class="col-6 text-muted fw-normal">Budget type</dt><dd class="col-6 text-end"><?= e(humanize((string) $project['budget_type'])) ?></dd>
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
                    <?php if (($project['moderated_at'] ?? null) !== null): ?>
                        <dt class="col-6 text-muted fw-normal">Moderated</dt><dd class="col-6 text-end"><?= e(dt((string) $project['moderated_at'], 'd M Y')) ?></dd>
                    <?php endif; ?>
                </dl>
                <?php if (!empty($project['moderation_note'])): ?>
                    <div class="alert alert-secondary small mt-3 mb-0">
                        <strong>Previous moderation note:</strong> <?= e((string) $project['moderation_note']) ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($canModerate): ?>
            <div class="cw-card mb-4">
                <div class="cw-card-header">Moderation decision</div>
                <div class="cw-card-body">
                    <form method="post" action="<?= e(route('admin.projects.moderate', ['id' => (int) $project['id']])) ?>">
                        <?= csrf_field() ?>
                        <div class="mb-3">
                            <label class="form-label small" for="decision">Decision</label>
                            <select class="form-select form-select-sm" id="decision" name="decision" required>
                                <option value="approve">Approve and publish</option>
                                <option value="reject">Reject</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small" for="note">Note to the client</label>
                            <textarea class="form-control form-control-sm" id="note" name="note" rows="3"
                                      placeholder="Required when rejecting. Shared with the client."></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary btn-sm w-100"
                                data-confirm="Apply this moderation decision?">
                            Submit decision
                        </button>
                    </form>
                </div>
            </div>
        <?php endif; ?>

        <div class="cw-card">
            <div class="cw-card-header">Featured placement</div>
            <div class="cw-card-body">
                <?php if ((int) $project['is_featured'] === 1): ?>
                    <p class="small">Featured until <?= e(dt($project['featured_until'] ?? null, 'd M Y')) ?>.</p>
                    <form method="post" action="<?= e(route('admin.projects.feature', ['id' => (int) $project['id']])) ?>"
                          data-confirm="Remove featured placement from this project?">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="unfeature">
                        <button type="submit" class="btn btn-outline-danger btn-sm w-100">Remove featured placement</button>
                    </form>
                <?php else: ?>
                    <form method="post" action="<?= e(route('admin.projects.feature', ['id' => (int) $project['id']])) ?>"
                          data-confirm="Feature this project?">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="feature">
                        <div class="mb-2">
                            <label class="form-label small" for="days">Duration (days)</label>
                            <input type="number" min="1" max="365" class="form-control form-control-sm" id="days" name="days"
                                   value="<?= (int) App\Services\FeaturedService::durationDays() ?>">
                        </div>
                        <button type="submit" class="btn btn-navy btn-sm w-100">Feature project</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
