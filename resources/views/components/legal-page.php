<?php
/**
 * Renders a legal page from a list of sections.
 *
 * @var string $heading
 * @var string $lead
 * @var string $updated
 * @var array<int,array{title:string,body:string|array<int,string>}> $sections
 */
$crumbs = [['label' => 'Home', 'url' => route('home')], ['label' => $heading]];
require CIRCLEWORK_ROOT . '/resources/views/components/page-header.php';
?>
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-9">
            <p class="text-muted small">Last updated: <?= e($updated) ?></p>

            <div class="cw-legal">
                <p class="lead"><?= e($lead) ?></p>

                <?php foreach ($sections as $index => $section): ?>
                    <h2 id="section-<?= $index + 1 ?>"><?= ($index + 1) . '. ' . e($section['title']) ?></h2>
                    <?php if (is_array($section['body'])): ?>
                        <?php foreach ($section['body'] as $paragraph): ?>
                            <p><?= e($paragraph) ?></p>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p><?= e($section['body']) ?></p>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>

            <div class="cw-notice cw-notice--warning mt-4">
                This document is provided for clarity about how the platform operates. It is not legal advice.
                If you need advice about your rights or obligations, consult a qualified legal practitioner in
                your jurisdiction.
            </div>

            <div class="cw-card mt-4"><div class="cw-card-body">
                <h2 class="h6">Related documents</h2>
                <div class="d-flex flex-wrap gap-2">
                    <a class="btn btn-sm btn-outline-primary" href="<?= e(route('terms')) ?>">Terms of Service</a>
                    <a class="btn btn-sm btn-outline-primary" href="<?= e(route('privacy')) ?>">Privacy Policy</a>
                    <a class="btn btn-sm btn-outline-primary" href="<?= e(route('guidelines')) ?>">Community Guidelines</a>
                    <a class="btn btn-sm btn-outline-primary" href="<?= e(route('dispute-policy')) ?>">Dispute and Cancellation Policy</a>
                </div>
            </div></div>
        </div>
    </div>
</div>
