<?php
/** @var array<string,mixed>|null $planForm */
$isEdit = $planForm !== null;
$suffix = $isEdit ? (string) (int) $planForm['id'] : 'new';
$featureText = '';
$limits = [];
if ($isEdit) {
    if (!empty($planForm['features'])) {
        $decoded = json_decode((string) $planForm['features'], true);
        $featureText = is_array($decoded) ? implode("\n", array_map('strval', $decoded)) : '';
    }
    if (!empty($planForm['limits'])) {
        $decoded = json_decode((string) $planForm['limits'], true);
        $limits = is_array($decoded) ? $decoded : [];
    }
}
?>
<form method="post" action="<?= e(route('admin.subscription.plans.save')) ?>" data-lock-submit data-loading-text="Saving…">
    <?= csrf_field() ?>
    <?php if ($isEdit): ?>
        <input type="hidden" name="plan_id" value="<?= (int) $planForm['id'] ?>">
    <?php endif; ?>

    <div class="row g-2">
        <div class="col-md-6">
            <label class="form-label small" for="code-<?= e($suffix) ?>">Code</label>
            <input type="text" class="form-control form-control-sm" id="code-<?= e($suffix) ?>" name="code"
                   value="<?= e((string) ($planForm['code'] ?? '')) ?>" required pattern="[a-zA-Z0-9_]+">
        </div>
        <div class="col-md-6">
            <label class="form-label small" for="name-<?= e($suffix) ?>">Name</label>
            <input type="text" class="form-control form-control-sm" id="name-<?= e($suffix) ?>" name="name"
                   value="<?= e((string) ($planForm['name'] ?? '')) ?>" required>
        </div>
        <div class="col-md-4">
            <label class="form-label small" for="audience-<?= e($suffix) ?>">Audience</label>
            <select class="form-select form-select-sm" id="audience-<?= e($suffix) ?>" name="audience">
                <?php foreach (['both' => 'Both', 'client' => 'Client', 'freelancer' => 'Freelancer'] as $value => $label): ?>
                    <option value="<?= e($value) ?>" <?= (string) ($planForm['audience'] ?? 'both') === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label small" for="price-<?= e($suffix) ?>">Price (₦)</label>
            <input type="number" step="0.01" min="0" class="form-control form-control-sm" id="price-<?= e($suffix) ?>"
                   name="price" value="<?= e((string) ($planForm['price'] ?? '0.00')) ?>" required>
        </div>
        <div class="col-md-4">
            <label class="form-label small" for="billing-<?= e($suffix) ?>">Billing period</label>
            <select class="form-select form-select-sm" id="billing-<?= e($suffix) ?>" name="billing_period">
                <?php foreach (['monthly' => 'Monthly', 'yearly' => 'Yearly'] as $value => $label): ?>
                    <option value="<?= e($value) ?>" <?= (string) ($planForm['billing_period'] ?? 'monthly') === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-12">
            <label class="form-label small" for="description-<?= e($suffix) ?>">Description</label>
            <input type="text" class="form-control form-control-sm" id="description-<?= e($suffix) ?>" name="description"
                   value="<?= e((string) ($planForm['description'] ?? '')) ?>" maxlength="500">
        </div>
        <div class="col-12">
            <label class="form-label small" for="features-<?= e($suffix) ?>">Features (one per line)</label>
            <textarea class="form-control form-control-sm" id="features-<?= e($suffix) ?>" name="features" rows="4"><?= e($featureText) ?></textarea>
        </div>
        <div class="col-md-4">
            <label class="form-label small" for="lap-<?= e($suffix) ?>">Active projects limit</label>
            <input type="number" min="0" class="form-control form-control-sm" id="lap-<?= e($suffix) ?>" name="limit_active_projects"
                   value="<?= (int) ($limits['active_projects'] ?? 3) ?>">
        </div>
        <div class="col-md-4">
            <label class="form-label small" for="lpp-<?= e($suffix) ?>">Proposals per day</label>
            <input type="number" min="0" class="form-control form-control-sm" id="lpp-<?= e($suffix) ?>" name="limit_proposals_per_day"
                   value="<?= (int) ($limits['proposals_per_day'] ?? 10) ?>">
        </div>
        <div class="col-md-4">
            <label class="form-label small" for="lpi-<?= e($suffix) ?>">Portfolio items</label>
            <input type="number" min="0" class="form-control form-control-sm" id="lpi-<?= e($suffix) ?>" name="limit_portfolio_items"
                   value="<?= (int) ($limits['portfolio_items'] ?? 5) ?>">
        </div>
        <div class="col-md-4">
            <label class="form-label small" for="lfp-<?= e($suffix) ?>">Featured projects</label>
            <input type="number" min="0" class="form-control form-control-sm" id="lfp-<?= e($suffix) ?>" name="limit_featured_projects"
                   value="<?= (int) ($limits['featured_projects'] ?? 0) ?>">
        </div>
        <div class="col-md-4">
            <label class="form-label small" for="laf-<?= e($suffix) ?>">Applicant filters (0/1)</label>
            <input type="number" min="0" max="1" class="form-control form-control-sm" id="laf-<?= e($suffix) ?>" name="limit_applicant_filters"
                   value="<?= (int) ($limits['applicant_filters'] ?? 0) ?>">
        </div>
        <div class="col-md-4">
            <label class="form-label small" for="lpa-<?= e($suffix) ?>">Profile analytics (0/1)</label>
            <input type="number" min="0" max="1" class="form-control form-control-sm" id="lpa-<?= e($suffix) ?>" name="limit_profile_analytics"
                   value="<?= (int) ($limits['profile_analytics'] ?? 0) ?>">
        </div>
        <div class="col-md-6">
            <label class="form-label small" for="sort-<?= e($suffix) ?>">Sort order</label>
            <input type="number" class="form-control form-control-sm" id="sort-<?= e($suffix) ?>" name="sort_order"
                   value="<?= (int) ($planForm['sort_order'] ?? 0) ?>">
        </div>
        <div class="col-md-6 d-flex align-items-end">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" id="active-<?= e($suffix) ?>" name="is_active" value="1"
                       <?= !$isEdit || (int) ($planForm['is_active'] ?? 0) === 1 ? 'checked' : '' ?>>
                <label class="form-check-label small" for="active-<?= e($suffix) ?>">Plan is active</label>
            </div>
        </div>
    </div>

    <button type="submit" class="btn btn-primary btn-sm w-100 mt-3">
        <?= $isEdit ? 'Save plan changes' : 'Create plan' ?>
    </button>
</form>
