<?php /** @var bool $verified */ ?>
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-9 col-lg-6">
            <div class="cw-card"><div class="cw-card-body p-4 p-lg-5 text-center">
                <?php if ($verified): ?>
                    <i class="bi bi-patch-check-fill text-success display-5"></i>
                    <h1 class="h3 mt-3">Your email address is verified</h1>
                    <p class="text-muted">You have full access to your CircleWork Africa account.</p>
                    <a href="<?= e(route('dashboard')) ?>" class="btn btn-primary">Go to dashboard</a>
                <?php else: ?>
                    <i class="bi bi-envelope-check text-primary display-5"></i>
                    <h1 class="h3 mt-3">Verify your email address</h1>
                    <p class="text-muted">
                        We sent a verification link to your email address. Verification confirms you own the
                        address and keeps your account secure. The link expires after 48 hours.
                    </p>
                    <form method="post" action="<?= e(route('verify.resend')) ?>" class="d-inline">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn-primary">Resend verification email</button>
                    </form>
                    <p class="small text-muted mt-3 mb-0">
                        Some features remain limited until your address is verified.
                    </p>
                <?php endif; ?>
            </div></div>
        </div>
    </div>
</div>
