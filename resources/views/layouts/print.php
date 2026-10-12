<?php
/** @var string $content */
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title ?? 'Document') ?></title>
    <meta name="robots" content="noindex, nofollow">
    <?= bootstrap_css() ?>
    <link rel="stylesheet" href="<?= e(asset('/assets/css/circlework.css')) ?>">
</head>
<body class="bg-white">
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4 no-print">
        <a href="javascript:history.back()" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left"></i> Back
        </a>
        <button type="button" class="btn btn-primary btn-sm" onclick="window.print()">
            <i class="bi bi-printer"></i> Print or save as PDF
        </button>
    </div>
    <?= $content ?>
</div>
</body>
</html>
