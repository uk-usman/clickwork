<?php
$heading = 'About CircleWork Africa';
$lead = 'A Nigerian freelance marketplace built for clear, documented working relationships.';
$crumbs = [['label' => 'Home', 'url' => route('home')], ['label' => 'About']];
require CIRCLEWORK_ROOT . '/resources/views/components/page-header.php';
?>
<div class="container py-5">
    <div class="row g-5">
        <div class="col-lg-8 cw-legal">
            <p class="lead">
                CircleWork Africa is a product of Circle World Technologies Africa. We build tools that help
                clients and freelancers across Nigeria agree on work, document what was agreed, and keep a
                clear record of delivery and payment.
            </p>

            <h2>What we do</h2>
            <p>
                We operate a marketplace where clients publish projects and freelancers submit proposals.
                When a proposal is accepted, we generate a contract from the project and proposal details so
                both parties are working from the same written terms. Deliverables are submitted and reviewed
                on the platform, and payment records are logged with an honest status.
            </p>

            <h2>What we do not do</h2>
            <ul>
                <li>We do not hold client funds, operate escrow or move money between users.</li>
                <li>We do not employ freelancers or act as an employment agency.</li>
                <li>We do not automatically deduct commission from freelance earnings.</li>
                <li>We do not verify bank transfers, and we never claim to have done so.</li>
                <li>We do not provide government certification or guaranteed legal protection.</li>
            </ul>

            <h2>Our approach to trust</h2>
            <p>
                Trust is built from transparency, not from decoration. Every project is reviewed before it is
                published. Every contract acceptance records who accepted, when, which version, and a hash of
                the exact terms. Payment records stay labelled “Reported” until the other party confirms them.
                Reports and disputes are tracked to resolution and moderation actions are logged.
            </p>

            <h2>Where we operate</h2>
            <p>
                Our initial marketing focus is <?= e(setting('marketing_city', 'Kano') . ', ' . setting('marketing_state', 'Kano')) ?>,
                and the platform supports online work between clients and freelancers in different Nigerian cities.
                The platform is priced in Nigerian Naira and operates on Africa/Lagos time for display, while
                timestamps are stored in UTC.
            </p>

            <h2>Contact</h2>
            <p>
                Questions, feedback or partnership enquiries are welcome through our
                <a href="<?= e(route('contact')) ?>">contact page</a>.
            </p>
        </div>

        <div class="col-lg-4">
            <div class="cw-card mb-4"><div class="cw-card-body">
                <h2 class="h5">At a glance</h2>
                <dl class="mb-0 small">
                    <dt class="text-muted">Product</dt><dd>CircleWork Africa</dd>
                    <dt class="text-muted">Parent company</dt><dd>Circle World Technologies Africa</dd>
                    <dt class="text-muted">Primary market</dt><dd>Nigeria</dd>
                    <dt class="text-muted">Currency</dt><dd>Nigerian Naira (₦)</dd>
                    <dt class="text-muted">Primary language</dt><dd>English</dd>
                    <dt class="text-muted">Timezone</dt><dd>Africa/Lagos</dd>
                </dl>
            </div></div>

            <div class="cw-card"><div class="cw-card-body">
                <h2 class="h5">Business model</h2>
                <ul class="small text-muted mb-0">
                    <li>Free basic freelancer accounts</li>
                    <li>Free basic client accounts</li>
                    <li>Optional paid subscriptions</li>
                    <li>Featured project listings</li>
                    <li>Future premium business features</li>
                </ul>
            </div></div>
        </div>
    </div>
</div>
