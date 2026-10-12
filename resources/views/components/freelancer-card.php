<?php
/** @var array<string,mixed> $freelancer */
$avatar = avatar_url($freelancer['avatar_path'] ?? null);
?>
<article class="cw-project-card">
    <div class="d-flex align-items-center gap-3 mb-3">
        <?php if ($avatar !== null): ?>
            <img src="<?= e($avatar) ?>" alt="" class="cw-avatar cw-avatar--lg">
        <?php else: ?>
            <span class="cw-avatar cw-avatar--lg"><?= e(initials((string) $freelancer['name'])) ?></span>
        <?php endif; ?>
        <div class="min-w-0">
            <h3 class="h6 mb-1">
                <a href="<?= e(route('freelancers.show', ['id' => (int) $freelancer['id']])) ?>">
                    <?= e($freelancer['name']) ?>
                </a>
            </h3>
            <p class="text-muted small mb-1"><?= e(str_excerpt((string) ($freelancer['freelancer_title'] ?? $freelancer['headline'] ?? 'Freelancer'), 60)) ?></p>
            <div class="small text-warning">
                <?php $rating = (float) ($freelancer['rating_average'] ?? 0); ?>
                <?php for ($i = 1; $i <= 5; $i++): ?>
                    <i class="bi <?= $i <= round($rating) ? 'bi-star-fill' : 'bi-star' ?>"></i>
                <?php endfor; ?>
                <span class="text-muted ms-1">
                    <?= $rating > 0 ? number_format($rating, 1) : 'New' ?>
                    <?php if ((int) ($freelancer['rating_count'] ?? 0) > 0): ?>
                        (<?= (int) $freelancer['rating_count'] ?>)
                    <?php endif; ?>
                </span>
            </div>
        </div>
    </div>

    <p class="text-muted small flex-grow-1"><?= e(str_excerpt((string) ($freelancer['freelancer_overview'] ?? ''), 130)) ?></p>

    <div class="d-flex justify-content-between align-items-center small text-muted border-top pt-3 mt-auto">
        <span><i class="bi bi-geo-alt"></i> <?= e($freelancer['city'] ?? $freelancer['state'] ?? 'Nigeria') ?></span>
        <span>
            <?php if (($freelancer['hourly_rate'] ?? null) !== null): ?>
                <?= e(money($freelancer['hourly_rate'], (string) ($freelancer['rate_currency'] ?? 'NGN'))) ?>/hr
            <?php else: ?>
                <?= (int) ($freelancer['completed_contracts'] ?? 0) ?> completed
            <?php endif; ?>
        </span>
    </div>
</article>
