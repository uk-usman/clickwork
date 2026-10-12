<?php
$siteName = setting('site_name', 'CircleWork Africa') ?? 'CircleWork Africa';
$supportEmail = setting('support_email', 'support@example.com');
$city = setting('marketing_city', 'Kano');
$state = setting('marketing_state', 'Kano');
?>
<footer class="cw-footer mt-5">
    <div class="container">
        <div class="row g-4">
            <div class="col-lg-4">
                <h5 class="text-white d-flex align-items-center gap-2">
                    <i class="bi bi-circle-square"></i><?= e($siteName) ?>
                </h5>
                <p class="small">
                    <?= e(setting('site_tagline', 'Find Work. Hire Talent. Build Trust.') ?? '') ?>
                </p>
                <p class="small mb-1">
                    A product of Circle World Technologies Africa.
                </p>
                <p class="small mb-0">
                    Serving clients and freelancers across Nigeria, with our initial focus on
                    <?= e($city . ', ' . $state) ?>.
                </p>
            </div>

            <div class="col-6 col-lg-2">
                <h6>Platform</h6>
                <a href="<?= e(route('projects.index')) ?>">Browse projects</a>
                <a href="<?= e(route('freelancers.index')) ?>">Find freelancers</a>
                <a href="<?= e(route('how-it-works')) ?>">How it works</a>
                <a href="<?= e(route('pricing')) ?>">Pricing</a>
            </div>

            <div class="col-6 col-lg-2">
                <h6>Support</h6>
                <a href="<?= e(route('contact')) ?>">Contact and help</a>
                <a href="<?= e(route('faq')) ?>">FAQ</a>
                <a href="<?= e(route('about')) ?>">About CircleWork</a>
                <?php if ($supportEmail): ?>
                    <a href="mailto:<?= e($supportEmail) ?>"><?= e($supportEmail) ?></a>
                <?php endif; ?>
            </div>

            <div class="col-6 col-lg-2">
                <h6>Legal</h6>
                <a href="<?= e(route('terms')) ?>">Terms of Service</a>
                <a href="<?= e(route('privacy')) ?>">Privacy Policy</a>
                <a href="<?= e(route('guidelines')) ?>">Community Guidelines</a>
                <a href="<?= e(route('dispute-policy')) ?>">Dispute and Cancellation</a>
            </div>

            <div class="col-6 col-lg-2">
                <h6>Account</h6>
                <?php if (App\Core\Auth::check()): ?>
                    <a href="<?= e(route('dashboard')) ?>">Dashboard</a>
                    <a href="<?= e(route('account.settings')) ?>">Settings</a>
                <?php else: ?>
                    <a href="<?= e(route('login')) ?>">Sign in</a>
                    <a href="<?= e(route('register')) ?>">Create account</a>
                <?php endif; ?>
                <a href="<?= e(route('report.create')) ?>">Report a problem</a>
            </div>
        </div>

        <hr class="border-secondary mt-4">

        <div class="d-flex flex-wrap justify-content-between gap-2 small">
            <span>&copy; <?= date('Y') ?> Circle World Technologies Africa. All rights reserved.</span>
            <span>
                Payments are made directly between clients and freelancers.
                <?= e($siteName) ?> does not hold funds or operate escrow.
            </span>
        </div>
    </div>
</footer>
