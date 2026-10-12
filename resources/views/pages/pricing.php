<?php
/** @var array<int,array<string,mixed>> $clientPlans */
/** @var array<int,array<string,mixed>> $freelancerPlans */
$heading = 'Pricing and plans';
$lead = 'Start free. Upgrade only when you need more capacity or visibility.';
$crumbs = [['label' => 'Home', 'url' => route('home')], ['label' => 'Pricing']];
require CIRCLEWORK_ROOT . '/resources/views/components/page-header.php';

$renderPlans = static function (array $plans, string $audience): void {
    if ($plans === []) {
        echo '<p class="text-muted">No plans have been published for this audience yet.</p>';
        return;
    }

    echo '<div class="row g-4">';
    foreach ($plans as $plan) {
        $features = [];
        if (!empty($plan['features'])) {
            $decoded = json_decode((string) $plan['features'], true);
            $features = is_array($decoded) ? $decoded : [];
        }
        $limits = [];
        if (!empty($plan['limits'])) {
            $decoded = json_decode((string) $plan['limits'], true);
            $limits = is_array($decoded) ? $decoded : [];
        }
        $isFree = (float) $plan['price'] === 0.0;
        echo '<div class="col-md-6 col-lg-4">';
        echo '<div class="cw-card h-100"><div class="cw-card-body d-flex flex-column">';
        echo '<h3 class="h5">' . e($plan['name']) . '</h3>';
        echo '<div class="display-6 fw-bold mb-1">' . ($isFree ? 'Free' : e(money($plan['price'], (string) $plan['currency']))) . '</div>';
        echo '<p class="text-muted small">' . ($isFree ? 'No payment required' : 'per ' . e(rtrim((string) $plan['billing_period'], 'ly')) . ', verified manually by an administrator') . '</p>';
        if (!empty($plan['description'])) {
            echo '<p class="text-muted small">' . e($plan['description']) . '</p>';
        }
        echo '<ul class="list-unstyled small flex-grow-1">';
        foreach ($features as $feature) {
            echo '<li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i>' . e((string) $feature) . '</li>';
        }
        if (isset($limits['active_projects'])) {
            echo '<li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i>' . (int) $limits['active_projects'] . ' active project(s)</li>';
        }
        if (isset($limits['proposals_per_day'])) {
            echo '<li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i>' . (int) $limits['proposals_per_day'] . ' proposals per day</li>';
        }
        if (isset($limits['portfolio_items'])) {
            echo '<li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i>Up to ' . (int) $limits['portfolio_items'] . ' portfolio items</li>';
        }
        echo '</ul>';
        echo '<a href="' . e(route('subscriptions.index')) . '" class="btn ' . ($isFree ? 'btn-outline-primary' : 'btn-primary') . ' w-100">Choose ' . e($plan['name']) . '</a>';
        echo '</div></div></div>';
    }
    echo '</div>';
};
?>

<div class="container py-5">
    <h2 class="h4 mb-3">For clients</h2>
    <?php $renderPlans($clientPlans, 'client'); ?>

    <h2 class="h4 mb-3 mt-5">For freelancers</h2>
    <?php $renderPlans($freelancerPlans, 'freelancer'); ?>

    <div class="cw-card mt-5"><div class="cw-card-body">
        <div class="row g-4 align-items-center">
            <div class="col-lg-8">
                <h2 class="h5 mb-2"><i class="bi bi-star-fill text-primary me-2"></i>Featured project listing</h2>
                <p class="text-muted mb-0">
                    Feature a published project for <strong><?= e(money($featuredPrice)) ?></strong>
                    per listing for <strong><?= (int) $featuredDays ?> days</strong>. Featured projects are clearly
                    labelled and appear above standard listings. Featured placement is activated after an
                    administrator verifies your manual payment.
                </p>
            </div>
            <div class="col-lg-4 text-lg-end">
                <a href="<?= e(route('client.projects')) ?>" class="btn btn-navy">Feature a project</a>
            </div>
        </div>
    </div></div>

    <div class="cw-notice cw-notice--warning mt-4">
        <strong>How paid plans are activated.</strong>
        CircleWork Africa does not require a payment gateway for the core service. Until an approved payment
        provider is integrated, subscription payments are recorded and verified manually by an administrator,
        with a full audit trail. A plan is never activated because a user changed a form field or a URL —
        premium features are always resolved from the database on the server.
    </div>
</div>
