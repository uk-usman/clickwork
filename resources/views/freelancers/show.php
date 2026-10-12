<?php
/** @var array<string,mixed> $freelancer */
/** @var array<int,array<string,mixed>> $skills */
/** @var array<int,array<string,mixed>> $portfolio */
/** @var array<int,array<string,mixed>> $reviews */
$heading = (string) $freelancer['name'];
$lead = (string) ($freelancer['freelancer_title'] ?? $freelancer['headline'] ?? 'Freelancer');
$crumbs = [
    ['label' => 'Home', 'url' => route('home')],
    ['label' => 'Find freelancers', 'url' => route('freelancers.index')],
    ['label' => str_excerpt((string) $freelancer['name'], 30)],
];
require CIRCLEWORK_ROOT . '/resources/views/components/page-header.php';

$avatar = avatar_url($freelancer['avatar_path'] ?? null);
$rating = (float) ($freelancer['rating_average'] ?? 0);
?>
<div class="container py-4">
    <div class="row g-4">
        <div class="col-lg-4">
            <div class="cw-card mb-4"><div class="cw-card-body text-center">
                <?php if ($avatar !== null): ?>
                    <img src="<?= e($avatar) ?>" alt="" class="cw-avatar cw-avatar--xl mb-3">
                <?php else: ?>
                    <span class="cw-avatar cw-avatar--xl mb-3"><?= e(initials((string) $freelancer['name'])) ?></span>
                <?php endif; ?>

                <h2 class="h5 mb-1"><?= e($freelancer['name']) ?></h2>
                <p class="text-muted small"><?= e((string) ($freelancer['headline'] ?? '')) ?></p>

                <div class="text-warning mb-2">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                        <i class="bi <?= $i <= round($rating) ? 'bi-star-fill' : 'bi-star' ?>"></i>
                    <?php endfor; ?>
                    <span class="text-muted ms-1 small">
                        <?= $rating > 0 ? number_format($rating, 1) : 'No ratings yet' ?>
                        <?php if ((int) ($freelancer['rating_count'] ?? 0) > 0): ?>
                            (<?= (int) $freelancer['rating_count'] ?> review(s))
                        <?php endif; ?>
                    </span>
                </div>

                <dl class="row g-2 small text-start mt-3 mb-0">
                    <dt class="col-6 text-muted fw-normal">Location</dt>
                    <dd class="col-6 text-end"><?= e(trim(($freelancer['city'] ?? '') . ', ' . ($freelancer['state'] ?? ''), ', ') ?: 'Nigeria') ?></dd>
                    <dt class="col-6 text-muted fw-normal">Availability</dt>
                    <dd class="col-6 text-end"><?= e(humanize((string) ($freelancer['availability'] ?? 'available'))) ?></dd>
                    <dt class="col-6 text-muted fw-normal">Rate</dt>
                    <dd class="col-6 text-end">
                        <?= ($freelancer['hourly_rate'] ?? null) !== null
                            ? e(money($freelancer['hourly_rate'], (string) ($freelancer['rate_currency'] ?? 'NGN'))) . ' / hr'
                            : 'Negotiable' ?>
                    </dd>
                    <dt class="col-6 text-muted fw-normal">Completed</dt>
                    <dd class="col-6 text-end"><?= (int) ($freelancer['completed_contracts'] ?? 0) ?> contract(s)</dd>
                    <dt class="col-6 text-muted fw-normal">Member since</dt>
                    <dd class="col-6 text-end"><?= e(dt($freelancer['created_at'] ?? null, 'M Y')) ?></dd>
                </dl>
            </div></div>

            <?php if (!empty($freelancer['website'])): ?>
                <div class="cw-card mb-4"><div class="cw-card-body">
                    <h2 class="h6">Links</h2>
                    <a href="<?= e($freelancer['website']) ?>" rel="nofollow noopener" target="_blank">
                        <i class="bi bi-link-45deg"></i> Personal website
                    </a>
                </div></div>
            <?php endif; ?>

            <?php if (App\Core\Auth::check()): ?>
                <div class="cw-card"><div class="cw-card-body">
                    <h2 class="h6">Work with <?= e(str_excerpt((string) $freelancer['name'], 18)) ?></h2>
                    <p class="small text-muted">
                        Post a project and this freelancer can submit a proposal, or browse open projects to find work.
                    </p>
                    <a href="<?= e(route('client.projects.create')) ?>" class="btn btn-primary w-100 mb-2">Post a project</a>
                    <a href="<?= e(route('report.create', ['type' => 'user', 'target' => (int) $freelancer['id']])) ?>"
                       class="btn btn-sm btn-outline-danger w-100"><i class="bi bi-flag me-1"></i>Report this profile</a>
                </div></div>
            <?php endif; ?>
        </div>

        <div class="col-lg-8">
            <div class="cw-card mb-4"><div class="cw-card-body">
                <h2 class="h5">About</h2>
                <div class="text-muted" style="white-space: pre-wrap;">
                    <?= e((string) ($freelancer['freelancer_overview'] ?? $freelancer['bio'] ?? 'This freelancer has not written an overview yet.')) ?>
                </div>

                <?php if ($skills !== []): ?>
                    <h3 class="h6 mt-4">Skills</h3>
                    <?php foreach ($skills as $skill): ?>
                        <span class="cw-chip cw-chip--royal">
                            <?= e($skill['name']) ?>
                            <span class="text-muted">· <?= e(humanize((string) $skill['proficiency'])) ?></span>
                        </span>
                    <?php endforeach; ?>
                <?php endif; ?>

                <?php if (!empty($freelancer['languages'])): ?>
                    <h3 class="h6 mt-4">Languages</h3>
                    <p class="text-muted mb-0"><?= e((string) $freelancer['languages']) ?></p>
                <?php endif; ?>
            </div></div>

            <div class="cw-card mb-4"><div class="cw-card-body">
                <h2 class="h5 mb-3">Portfolio</h2>
                <?php if ($portfolio === []): ?>
                    <p class="text-muted mb-0">No portfolio items have been published.</p>
                <?php else: ?>
                    <div class="row g-3">
                        <?php foreach ($portfolio as $item): ?>
                            <div class="col-md-6">
                                <div class="border rounded h-100 p-3">
                                    <?php $image = portfolio_image_url($item['image_path'] ?? null); ?>
                                    <?php if ($image !== null): ?>
                                        <img src="<?= e($image) ?>" alt="" class="img-fluid rounded mb-2" style="max-height:180px;object-fit:cover;width:100%;">
                                    <?php endif; ?>
                                    <h3 class="h6 mb-1"><?= e($item['title']) ?></h3>
                                    <p class="small text-muted mb-2"><?= e(str_excerpt((string) ($item['description'] ?? ''), 120)) ?></p>
                                    <?php if (!empty($item['project_url'])): ?>
                                        <a class="small" href="<?= e($item['project_url']) ?>" rel="nofollow noopener" target="_blank">
                                            <i class="bi bi-box-arrow-up-right"></i> View project
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div></div>

            <div class="cw-card"><div class="cw-card-body">
                <h2 class="h5 mb-3">Reviews</h2>
                <?php if ($reviews === []): ?>
                    <p class="text-muted mb-0">No reviews yet. Reviews appear after a completed contract.</p>
                <?php else: ?>
                    <?php foreach ($reviews as $review): ?>
                        <div class="border-bottom pb-3 mb-3">
                            <div class="d-flex justify-content-between align-items-start gap-2">
                                <div>
                                    <strong><?= e($review['reviewer_name']) ?></strong>
                                    <span class="text-muted small">as <?= e(humanize((string) $review['reviewer_role'])) ?></span>
                                </div>
                                <div class="text-warning">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <i class="bi <?= $i <= (int) $review['rating'] ? 'bi-star-fill' : 'bi-star' ?>"></i>
                                    <?php endfor; ?>
                                </div>
                            </div>
                            <?php if (!empty($review['comment'])): ?>
                                <p class="mb-1 mt-2"><?= e($review['comment']) ?></p>
                            <?php endif; ?>
                            <small class="text-muted">
                                <?= e($review['project_title']) ?> · <?= e(dt((string) $review['created_at'], 'd M Y')) ?>
                            </small>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div></div>
        </div>
    </div>
</div>
