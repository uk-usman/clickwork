<?php
/** @var string $content */
/** @var array<string,mixed>|null $currentUser */
$siteName = setting('site_name', 'CircleWork Africa') ?? 'CircleWork Africa';
$currentUser = $currentUser ?? null;
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e(($title ?? 'Welcome') . ' — ' . $siteName) ?></title>
    <?php if (!empty($metaDescription)): ?>
        <meta name="description" content="<?= e($metaDescription) ?>">
    <?php else: ?>
        <meta name="description" content="CircleWork Africa connects Nigerian clients with skilled freelancers. Post projects, receive proposals, manage contracts and record direct payments.">
    <?php endif; ?>
    <meta name="theme-color" content="#0a1633">
    <?= bootstrap_css() ?>
    <?= bootstrap_icons() ?>
    <link rel="stylesheet" href="<?= e(asset('/assets/css/circlework.css')) ?>">
</head>
<body>
<a class="visually-hidden-focusable" href="#main">Skip to main content</a>

<?php require CIRCLEWORK_ROOT . '/resources/views/components/navbar.php'; ?>

<main id="main">
    <?php require CIRCLEWORK_ROOT . '/resources/views/components/flash.php'; ?>
    <?= $content ?>
</main>

<?php require CIRCLEWORK_ROOT . '/resources/views/components/footer.php'; ?>

<?= bootstrap_js() ?>
<script src="<?= e(asset('/assets/js/circlework.js')) ?>" defer></script>
</body>
</html>
