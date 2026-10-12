<?php
$heading = 'Contact and help';
$lead = 'Get in touch with the CircleWork Africa team.';
$crumbs = [['label' => 'Home', 'url' => route('home')], ['label' => 'Contact']];
require CIRCLEWORK_ROOT . '/resources/views/components/page-header.php';
?>
<div class="container py-5">
    <div class="row g-4">
        <div class="col-lg-7">
            <div class="cw-card"><div class="cw-card-body">
                <h2 class="h5 mb-3">How can we help?</h2>
                <p class="text-muted">
                    Before writing to us, these pages answer most questions:
                </p>
                <ul>
                    <li><a href="<?= e(route('faq')) ?>">Frequently asked questions</a></li>
                    <li><a href="<?= e(route('how-it-works')) ?>">How CircleWork Africa works</a></li>
                    <li><a href="<?= e(route('pricing')) ?>">Pricing and plans</a></li>
                    <li><a href="<?= e(route('dispute-policy')) ?>">Dispute and cancellation policy</a></li>
                </ul>

                <h3 class="h6 mt-4">Report a problem</h3>
                <p class="text-muted mb-3">
                    If you need to report a user, project, review, message or payment record, use the reporting
                    form so the issue is tracked with a reference an administrator can review.
                </p>
                <a href="<?= e(route('report.create')) ?>" class="btn btn-primary">Open the report form</a>
            </div></div>
        </div>

        <div class="col-lg-5">
            <div class="cw-card"><div class="cw-card-body">
                <h2 class="h5 mb-3">Contact details</h2>
                <dl class="mb-0">
                    <dt class="text-muted small">Email</dt>
                    <dd><?= e(setting('support_email', 'Not configured yet') ?? '') ?></dd>
                    <dt class="text-muted small">Phone</dt>
                    <dd><?= e(setting('support_phone', 'Not configured yet') ?? '') ?></dd>
                    <dt class="text-muted small">Address</dt>
                    <dd><?= e(setting('contact_address', 'Not configured yet') ?? '') ?></dd>
                    <dt class="text-muted small">Operating hours</dt>
                    <dd>Monday to Friday, 9:00 – 17:00 (Africa/Lagos)</dd>
                </dl>
            </div></div>

            <div class="cw-notice mt-4">
                Contact details are managed by administrators in
                <strong>Administration &rarr; Platform Settings</strong>, so they always reflect the live configuration.
            </div>
        </div>
    </div>
</div>
