<?php
/** @var array<string,mixed> $project */
/** @var array<int,array<string,mixed>> $skills */
/** @var bool $isOwner */
/** @var array<string,mixed>|null $existingProposal */
$heading = $project['title'];
$lead = 'Project ' . $project['reference'];
$crumbs = [
    ['label' => 'Home', 'url' => route('home')],
    ['label' => 'Browse projects', 'url' => route('projects.index')],
    ['label' => str_excerpt((string) $project['title'], 40)],
];
require CIRCLEWORK_ROOT . '/resources/views/components/page-header.php';

$budget = match ($project['budget_type']) {
    'hourly' => (($project['budget_min'] ?? null) !== null)
        ? money($project['budget_min'], (string) $project['currency']) . ' – ' . money($project['budget_max'], (string) $project['currency']) . ' per hour'
        : 'Hourly rate negotiable',
    default => (($project['budget_min'] ?? null) !== null)
        ? money($project['budget_min'], (string) $project['currency']) . ' – ' . money($project['budget_max'], (string) $project['currency'])
        : 'Budget not specified',
};

$canPropose = App\Core\Auth::check()
    && !$isOwner
    && in_array((string) $project['status'], ['published', 'in_progress'], true)
    && $existingProposal === null;
?>
<div class="container py-4">
    <div class="row g-4">
        <div class="col-lg-8">
            <div class="cw-card mb-4"><div class="cw-card-body">
                <div class="d-flex flex-wrap gap-2 mb-3">
                    <span class="cw-chip cw-chip--royal"><?= e($project['category_name'] ?? 'General') ?></span>
                    <span class="badge <?= e(status_badge_class((string) $project['status'])) ?>"><?= e(humanize((string) $project['status'])) ?></span>
                    <?php if ((int) $project['is_featured'] === 1): ?>
                        <span class="badge text-bg-primary"><i class="bi bi-star-fill me-1"></i>Featured</span>
                    <?php endif; ?>
                    <?php if ((string) $project['visibility'] === 'private'): ?>
                        <span class="badge text-bg-secondary"><i class="bi bi-lock me-1"></i>Private</span>
                    <?php endif; ?>
                </div>

                <h2 class="h5">Project description</h2>
                <div class="text-muted" style="white-space: pre-wrap;"><?= e($project['description']) ?></div>

                <?php if (!empty($project['required_skills_text'])): ?>
                    <h2 class="h5 mt-4">Required skills</h2>
                    <p class="text-muted"><?= e($project['required_skills_text']) ?></p>
                <?php endif; ?>

                <?php if ($skills !== []): ?>
                    <div class="mt-2">
                        <?php foreach ($skills as $skill): ?>
                            <span class="cw-chip"><?= e($skill['name']) ?></span>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($project['expected_deliverables'])): ?>
                    <h2 class="h5 mt-4">Expected deliverables</h2>
                    <div class="text-muted" style="white-space: pre-wrap;"><?= e($project['expected_deliverables']) ?></div>
                <?php endif; ?>
            </div></div>

            <div class="cw-card" id="proposal"><div class="cw-card-body">
                <h2 class="h5 mb-3">Submit a proposal</h2>

                <?php if ($isOwner): ?>
                    <div class="cw-notice cw-notice--warning">
                        This is your own project, so you cannot submit a proposal to it.
                        <a href="<?= e(route('client.projects.proposals', ['id' => (int) $project['id']])) ?>">Review the proposals you have received</a>.
                    </div>
                <?php elseif ($existingProposal !== null): ?>
                    <div class="cw-notice">
                        You submitted a proposal for this project on
                        <?= e(dt((string) $existingProposal['created_at'])) ?>.
                        Status: <strong><?= e(humanize((string) $existingProposal['status'])) ?></strong>.
                        <a href="<?= e(route('freelancer.proposals.show', ['id' => (int) $existingProposal['id']])) ?>">View your proposal</a>.
                    </div>
                <?php elseif (!App\Core\Auth::check()): ?>
                    <p class="text-muted">You need an account to submit a proposal.</p>
                    <a href="<?= e(route('login')) ?>" class="btn btn-primary">Sign in</a>
                    <a href="<?= e(route('register')) ?>" class="btn btn-outline-primary">Create an account</a>
                <?php elseif (!in_array((string) $project['status'], ['published', 'in_progress'], true)): ?>
                    <div class="cw-notice cw-notice--warning">This project is not currently open for proposals.</div>
                <?php else: ?>
                    <form method="post" action="<?= e(route('freelancer.proposals.store', ['id' => (int) $project['id']])) ?>"
                          data-lock-submit data-loading-text="Submitting…">
                        <?= csrf_field() ?>

                        <div class="mb-3">
                            <label class="form-label" for="cover_letter">Cover letter</label>
                            <textarea class="form-control <?= isset($errors['cover_letter']) ? 'is-invalid' : '' ?>"
                                      id="cover_letter" name="cover_letter" rows="7" maxlength="5000"
                                      data-counter="#cover-count" required
                                      placeholder="Explain how you will approach this work, relevant experience, and anything the client should know."><?= old('cover_letter') ?></textarea>
                            <div class="form-text d-flex justify-content-between">
                                <span>Minimum 40 characters.</span>
                                <span id="cover-count"></span>
                            </div>
                            <?php $field = 'cover_letter'; require CIRCLEWORK_ROOT . '/resources/views/components/field-error.php'; ?>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" for="bid_amount">Your bid (<?= e((string) $project['currency']) ?>)</label>
                                <input type="number" step="0.01" min="1" class="form-control <?= isset($errors['bid_amount']) ? 'is-invalid' : '' ?>"
                                       id="bid_amount" name="bid_amount" value="<?= old('bid_amount') ?>
                                <?php $field = 'bid_amount'; require CIRCLEWORK_ROOT . '/resources/views/components/field-error.php'; ?>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="estimated_days">Estimated delivery (days)</label>
                                <input type="number" min="1" max="3650" class="form-control <?= isset($errors['estimated_days']) ? 'is-invalid' : '' ?>"
                                       id="estimated_days" name="estimated_days" value="<?= old('estimated_days') ?>">
                                <?php $field = 'estimated_days'; require CIRCLEWORK_ROOT . '/resources/views/components/field-error.php'; ?>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary mt-3">Submit proposal</button>
                        <p class="form-text mb-0 mt-2">
                            You may submit only one proposal per project, and you can withdraw it while it is still submitted or shortlisted.
                        </p>
                    </form>
                <?php endif; ?>
            </div></div>
        </div>

        <div class="col-lg-4">
            <div class="cw-card mb-4"><div class="cw-card-body">
                <h2 class="h6 mb-3">Project summary</h2>
                <dl class="row g-3 mb-0 small">
                    <dt class="col-6 text-muted fw-normal">Budget</dt>
                    <dd class="col-6 fw-semibold text-end"><?= e($budget) ?></dd>
                    <dt class="col-6 text-muted fw-normal">Project type</dt>
                    <dd class="col-6 text-end"><?= e(humanize((string) $project['budget_type'])) ?></dd>
                    <dt class="col-6 text-muted fw-normal">Deadline</dt>
                    <dd class="col-6 text-end"><?= ($project['deadline'] ?? null) ? e(date('d M Y', strtotime((string) $project['deadline']))) : 'Flexible' ?></dd>
                    <dt class="col-6 text-muted fw-normal">Proposals</dt>
                    <dd class="col-6 text-end"><?= (int) ($project['proposal_count'] ?? 0) ?></dd>
                    <dt class="col-6 text-muted fw-normal">Published</dt>
                    <dd class="col-6 text-end"><?= e(dt($project['published_at'] ?? $project['created_at'], 'd M Y')) ?></dd>
                </dl>
            </div></div>

            <div class="cw-card"><div class="cw-card-body">
                <h2 class="h6 mb-3">About the client</h2>
                <div class="d-flex align-items-center gap-3 mb-3">
                    <?php $clientAvatar = avatar_url($project['client_avatar'] ?? null); ?>
                    <?php if ($clientAvatar !== null): ?>
                        <img src="<?= e($clientAvatar) ?>" alt="" class="cw-avatar cw-avatar--lg">
                    <?php else: ?>
                        <span class="cw-avatar cw-avatar--lg"><?= e(initials((string) $project['client_name'])) ?></span>
                    <?php endif; ?>
                    <div>
                        <div class="fw-semibold"><?= e($project['client_name']) ?></div>
                        <div class="text-muted small">
                            <i class="bi bi-geo-alt"></i>
                            <?= e(trim(($project['client_city'] ?? '') . ' ' . ($project['client_state'] ?? '')) ?: 'Nigeria') ?>
                        </div>
                        <div class="text-muted small">Member since <?= e(dt($project['client_since'] ?? null, 'M Y')) ?></div>
                    </div>
                </div>

                <?php if (!empty($project['client_company'])): ?>
                    <p class="small mb-1">
                        <strong><?= e($project['client_company']) ?></strong>
                        <?php if ((int) ($project['client_company_registered'] ?? 0) === 1): ?>
                            <span class="cw-chip">Registered business</span>
                        <?php endif; ?>
                    </p>
                <?php endif; ?>

                <p class="small text-muted mb-0">
                    Client identity is verified by account ownership only. CircleWork Africa does not
                    certify a client’s business registration.
                </p>
            </div></div>

            <?php if (App\Core\Auth::check() && !$isOwner): ?>
                <div class="cw-card mt-4"><div class="cw-card-body">
                    <h2 class="h6">Something wrong with this project?</h2>
                    <p class="small text-muted">Reports are reviewed by an administrator.</p>
                    <a class="btn btn-sm btn-outline-danger w-100"
                       href="<?= e(route('report.create', ['type' => 'project', 'target' => (int) $project['id']])) ?>">
                        <i class="bi bi-flag me-1"></i>Report this project
                    </a>
                </div></div>
            <?php endif; ?>
        </div>
    </div>
</div>
