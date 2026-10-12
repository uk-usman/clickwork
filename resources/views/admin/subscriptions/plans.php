<?php /** @var array<int,array<string,mixed>> $plans */ ?>
<div class="cw-notice mb-4">
    Prices, plan features, limits and featured duration are configurable here. Changes take effect for the
    server-side checks immediately — premium access is always resolved from these records.
</div>

<div class="row g-4">
    <div class="col-lg-7">
        <div class="cw-card">
            <div class="cw-card-header">Existing plans</div>
            <div class="cw-card-body">
                <?php foreach ($plans as $plan): ?>
                    <?php
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
                    ?>
                    <div class="border rounded p-3 mb-3">
                        <div class="d-flex justify-content-between align-items-start gap-2">
                            <div>
                                <strong><?= e((string) $plan['name']) ?></strong>
                                <span class="cw-chip"><?= e((string) $plan['code']) ?></span>
                                <span class="badge text-bg-secondary"><?= e(humanize((string) $plan['audience'])) ?></span>
                                <?php if ((int) $plan['is_active'] === 1): ?>
                                    <span class="badge text-bg-success">Active</span>
                                <?php else: ?>
                                    <span class="badge text-bg-danger">Inactive</span>
                                <?php endif; ?>
                            </div>
                            <div class="text-end">
                                <div class="h5 mb-0"><?= (float) $plan['price'] === 0.0 ? 'Free' : e(money($plan['price'], (string) $plan['currency'])) ?></div>
                                <div class="small text-muted">per <?= e(rtrim((string) $plan['billing_period'], 'ly')) ?></div>
                            </div>
                        </div>
                        <?php if ($features !== []): ?>
                            <div class="small text-muted mt-2"><?= e(implode(' · ', array_map('strval', $features))) ?></div>
                        <?php endif; ?>
                        <div class="small text-muted mt-1">
                            Limits: <?= (int) ($limits['active_projects'] ?? 0) ?> projects,
                            <?= (int) ($limits['proposals_per_day'] ?? 0) ?> proposals/day,
                            <?= (int) ($limits['portfolio_items'] ?? 0) ?> portfolio items
                        </div>
                        <button class="btn btn-sm btn-outline-primary mt-2" type="button" data-bs-toggle="collapse"
                                data-bs-target="#plan-<?= (int) $plan['id'] ?>">Edit plan</button>

                        <div class="collapse mt-3" id="plan-<?= (int) $plan['id'] ?>">
                            <?php $planForm = $plan; require CIRCLEWORK_ROOT . '/resources/views/admin/subscriptions/plan-form.php'; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="cw-card">
            <div class="cw-card-header">Create a new plan</div>
            <div class="cw-card-body">
                <?php $planForm = null; require CIRCLEWORK_ROOT . '/resources/views/admin/subscriptions/plan-form.php'; ?>
            </div>
        </div>
    </div>
</div>
