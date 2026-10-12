<?php
/** @var string|null $message */
$heading = 'Page not found';
$detail = $message ?? 'We could not find the page you were looking for.';
?>
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-7 text-center">
            <div class="display-1 fw-bold text-primary">404</div>
            <h1 class="h3 mb-3"><?= e($heading) ?></h1>
            <p class="text-muted mb-4"><?= e($detail) ?></p>
            <div class="d-flex justify-content-center gap-2 flex-wrap">
                <a href="<?= e(route('home')) ?>" class="btn btn-primary">
                    <i class="bi bi-house me-1"></i> Back to homepage
                </a>
                <a href="<?= e(route('projects.index')) ?>" class="btn btn-outline-primary">Browse projects</a>
                <a href="<?= e(route('contact')) ?>" class="btn btn-outline-secondary">Contact support</a>
            </div>
        </div>
    </div>
</div>
