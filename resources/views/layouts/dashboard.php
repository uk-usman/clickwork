<?php
/** @var string $content */
/** @var array<string,mixed>|null $currentUser */
$siteName = setting('site_name', 'CircleWork Africa') ?? 'CircleWork Africa';
$currentUser = $currentUser ?? null;
$activeMode = $activeMode ?? 'client';
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e(($title ?? 'Dashboard') . ' — ' . $siteName) ?></title>
    <meta name="robots" content="noindex, nofollow">
    <?= bootstrap_css() ?>
    <?= bootstrap_icons() ?>
    <link rel="stylesheet" href="<?= e(asset('/assets/css/circlework.css')) ?>">
</head>
<body>
<a class="visually-hidden-focusable" href="#main">Skip to main content</a>

<?php require CIRCLEWORK_ROOT . '/resources/views/components/navbar.php'; ?>

<div class="cw-shell">
    <?php require CIRCLEWORK_ROOT . '/resources/views/components/dashboard-nav.php'; ?>

    <div class="cw-main">
        <?php require CIRCLEWORK_ROOT . '/resources/views/components/flash.php'; ?>

        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
            <div>
                <h1 class="h3 mb-0"><?= e($title ?? 'Dashboard') ?></h1>
                <?php if (!empty($subtitle)): ?>
                    <p class="text-muted mb-0"><?= e($subtitle) ?></p>
                <?php endif; ?>
            </div>
            <?php if (!empty($pageActions)): ?>
                <div class="d-flex gap-2 flex-wrap"><?= $pageActions ?></div>
            <?php endif; ?>
        </div>

        <?= $content ?>
    </div>
</div>

<?= bootstrap_js() ?>
<script src="<?= e(asset('/assets/js/circlework.js')) ?>" defer></script>
</body>
</html>
