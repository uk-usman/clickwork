<?php /** @var bool $success */ /** @var string $message */ ?>
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-9 col-lg-6">
            <div class="cw-card"><div class="cw-card-body p-4 p-lg-5 text-center">
                <i class="bi <?= $success ? 'bi-patch-check-fill text-success' : 'bi-exclamation-triangle-fill text-warning' ?> display-5"></i>
                <h1 class="h3 mt-3"><?= $success ? 'Email verified' : 'Verification failed' ?></h1>
                <p class="text-muted"><?= e($message) ?></p>
                <div class="d-flex justify-content-center gap-2 flex-wrap">
                    <a href="<?= e(route('dashboard')) ?>" class="btn btn-primary">Go to dashboard</a>
                    <a href="<?= e(route('contact')) ?>" class="btn btn-outline-secondary">Contact support</a>
                </div>
            </div></div>
        </div>
    </div>
</div>
