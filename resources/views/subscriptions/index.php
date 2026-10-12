<?php
/** @var array<int,array<string,mixed>> $plans */
/** @var array<int,array<string,mixed>> $subscriptions */
/** @var array<int,array<string,mixed>> $payments */
/** @var array<string,mixed>|null $activePlan */
/** @var array<string,int> $limits */
/** @var array<int,string> $features */
?>
<div class="cw-notice mb-4">
    <strong>No payment gateway is required for the core service.</strong>
    Until an approved payment provider is integrated, paid plans and featured placements are activated only after
    an administrator records and verifies a manual payment. Verification is always labelled as manual, and
    premium features are resolved from the database on the server — never from a URL or form field.
</div>

<div class="row g-4 mb-4">
    <div class="col-lg-4">
        <div class="cw-card h-100">
            <div class="cw-card-body">
                <div class="text-muted small text-uppercase">Current plan</div>
                <div class="h4 mb-1"><?= e((string) ($activePlan['plan_name'] ?? $activePlan['name'] ?? 'Free')) ?></div>
                <?php if (($activePlan['expires_at'] ?? null) !== null): ?>
                    <div class="text-muted small">Expires <?= e(dt((string) $activePlan['expires_at'], 'd M Y')) ?></div>
                <?php else: ?>
                    <div class="text-muted small">No expiry</div>
                <?php endif; ?>
                <?php if (($activePlan['plan_code'] ?? '') === 'free' || ($activePlan['is_free'] ?? 0) == 1): ?>
                    <span class="badge text-bg-secondary mt-2">Free plan</span>
                <?php else: ?>
                    <span class="badge text-bg-success mt-2">Premium — manually verified</span>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="cw-card h-100">
            <div class="cw-card-body">
                <div class="text-muted small text-uppercase mb-2">Your current limits</div>
                <div class="row row-cols-2 row-cols-md-4 g-3 small">
                    <div><strong><?= (int) $limits['active_projects'] ?></strong><div class="text-muted">Active projects</div></div>
                    <div><strong><?= (int) $limits['proposals_per_day'] ?></strong><div class="text-muted">Proposals per day</div></div>
                    <div><strong><?= (int) $limits['portfolio_items'] ?></strong><div class="text-muted">Portfolio items</div></div>
                    <div><strong><?= (int) $limits['featured_projects'] ?></strong><div class="text-muted">Featured projects</div></div>
                </div>
                <?php if ($features !== []): ?>
                    <hr>
                    <div class="text-muted small text-uppercase mb-1">Included features</div>
                    <ul class="list-unstyled small mb-0">
                        <?php foreach ($features as $feature): ?>
                            <li><i class="bi bi-check2 text-success me-1"></i><?= e($feature) ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<h2 class="h5 mb-3">Available plans for your <?= e($mode) ?> mode</h2>
<div class="row g-4 mb-4">
    <?php foreach ($plans as $plan): ?>
        <?php $isFree = (float) $plan['price'] === 0.0; ?>
        <div class="col-md-6 col-lg-4">
            <div class="cw-card h-100">
                <div class="cw-card-body d-flex flex-column">
                    <h3 class="h5"><?= e($plan['name']) ?></h3>
                    <div class="display-6 fw-bold mb-1"><?= $isFree ? 'Free' : e(money($plan['price'], (string) $plan['currency'])) ?></div>
                    <p class="text-muted small">
                        <?= $isFree ? 'No payment required' : 'per ' . e(rtrim((string) $plan['billing_period'], 'ly')) . ', manually verified' ?>
                    </p>
                    <?php if (!empty($plan['description'])): ?>
                        <p class="small text-muted"><?= e($plan['description']) ?></p>
                    <?php endif; ?>
                    <?php
                    $planFeatures = [];
                    if (!empty($plan['features'])) {
                        $decoded = json_decode((string) $plan['features'], true);
                        $planFeatures = is_array($decoded) ? $decoded : [];
                    }
                    ?>
                    <ul class="list-unstyled small flex-grow-1">
                        <?php foreach ($planFeatures as $feature): ?>
                            <li class="mb-1"><i class="bi bi-check-circle-fill text-success me-2"></i><?= e((string) $feature) ?></li>
                        <?php endforeach; ?>
                    </ul>

                    <?php if ($isFree): ?>
                        <button class="btn btn-outline-secondary w-100" disabled>Included by default</button>
                    <?php else: ?>
                        <form method="post" action="<?= e(route('subscriptions.request')) ?>"
                              data-confirm="Request this plan? Nothing is activated until an administrator verifies your manual payment.">
                            <?= csrf_field() ?>
                            <input type="hidden" name="plan_id" value="<?= (int) $plan['id'] ?>">
                            <button type="submit" class="btn btn-primary w-100">Request this plan</button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<div class="row g-4">
    <div class="col-lg-6">
        <div class="cw-card">
            <div class="cw-card-header">Your subscription history</div>
            <?php if ($subscriptions === []): ?>
                <div class="cw-card-body text-muted">No subscription requests yet.</div>
            <?php else: ?>
                <div class="cw-card-body">
                    <?php foreach ($subscriptions as $subscription): ?>
                        <div class="d-flex justify-content-between align-items-start gap-2 border-bottom pb-2 mb-2">
                            <div>
                                <strong><?= e($subscription['plan_name']) ?></strong>
                                <div class="small text-muted">
                                    Requested <?= e(dt((string) $subscription['created_at'], 'd M Y')) ?>
                                    <?php if (($subscription['expires_at'] ?? null) !== null): ?>
                                        · expires <?= e(dt((string) $subscription['expires_at'], 'd M Y')) ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <span class="badge <?= e(status_badge_class((string) $subscription['status'])) ?>"><?= e(humanize((string) $subscription['status'])) ?></span>
                        </div>

                        <?php if ((string) $subscription['status'] === 'pending'): ?>
                            <form method="post" action="<?= e(route('subscriptions.payment')) ?>" class="border rounded p-2 mb-3">
                                <?= csrf_field() ?>
                                <input type="hidden" name="subscription_id" value="<?= (int) $subscription['id'] ?>">
                                <div class="small text-muted mb-2">
                                    Record the manual payment you made for this plan. An administrator will verify it
                                    before the plan is activated.
                                </div>
                                <div class="row g-2">
                                    <div class="col-6">
                                        <label class="form-label small" for="amount-<?= (int) $subscription['id'] ?>">Amount paid</label>
                                        <input type="number" step="0.01" min="1" class="form-control form-control-sm"
                                               id="amount-<?= (int) $subscription['id'] ?>" name="amount"
                                               value="<?= e((string) $subscription['price']) ?>" required>
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label small" for="method-<?= (int) $subscription['id'] ?>">Method</label>
                                        <select class="form-select form-select-sm" id="method-<?= (int) $subscription['id'] ?>" name="method">
                                            <option value="bank_transfer">Bank transfer</option>
                                            <option value="cash">Cash</option>
                                            <option value="mobile_money">Mobile money</option>
                                            <option value="card_transfer">Card transfer</option>
                                            <option value="other">Other</option>
                                        </select>
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label small" for="ref-<?= (int) $subscription['id'] ?>">Reference</label>
                                        <input type="text" class="form-control form-control-sm" id="ref-<?= (int) $subscription['id'] ?>" name="payer_reference">
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label small" for="paid-<?= (int) $subscription['id'] ?>">Date paid</label>
                                        <input type="date" class="form-control form-control-sm" id="paid-<?= (int) $subscription['id'] ?>"
                                               name="paid_at" max="<?= date('Y-m-d') ?>">
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-sm btn-navy w-100 mt-2">Record manual payment</button>
                            </form>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="cw-card mb-4">
            <div class="cw-card-header">Your recorded payments</div>
            <?php if ($payments === []): ?>
                <div class="cw-card-body text-muted">No subscription payments recorded.</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table cw-table mb-0">
                        <thead><tr><th>Amount</th><th>Method</th><th>Status</th><th>Recorded</th></tr></thead>
                        <tbody>
                        <?php foreach ($payments as $payment): ?>
                            <tr>
                                <td><?= e(money($payment['amount'], (string) $payment['currency'])) ?></td>
                                <td class="small"><?= e(humanize((string) $payment['method'])) ?></td>
                                <td><span class="badge <?= e(status_badge_class((string) $payment['status'])) ?>"><?= e(humanize((string) $payment['status'])) ?></span></td>
                                <td class="small text-muted"><?= e(dt((string) $payment['created_at'], 'd M Y')) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <div class="cw-card">
            <div class="cw-card-header">Featured project listings</div>
            <div class="cw-card-body">
                <p class="small text-muted mb-2">
                    Feature a published project for <strong><?= e(money($featuredPrice)) ?></strong>
                    for <strong><?= (int) $featuredDays ?> days</strong>. Featured placement is clearly labelled and
                    activated after manual payment verification.
                </p>
                <a class="btn btn-outline-primary w-100" href="<?= e(route('client.projects')) ?>">Choose a project to feature</a>
            </div>
        </div>
    </div>
</div>
