<?php /** @var array<string,mixed> $currentUser */ ?>
<div class="row g-4">
    <div class="col-lg-6">
        <div class="cw-card mb-4">
            <div class="cw-card-header">Account details</div>
            <div class="cw-card-body">
                <form method="post" action="<?= e(route('account.profile')) ?>" data-lock-submit data-loading-text="Saving…">
                    <?= csrf_field() ?>
                    <div class="mb-3">
                        <label class="form-label" for="name">Full name</label>
                        <input type="text" class="form-control <?= isset($errors['name']) ? 'is-invalid' : '' ?>"
                               id="name" name="name" value="<?= old('name', (string) ($currentUser['name'] ?? '')) ?>" required>
                        <?php $field = 'name'; require CIRCLEWORK_ROOT . '/resources/views/components/field-error.php'; ?>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="email-display">Email address</label>
                        <input type="email" class="form-control" id="email-display" value="<?= e((string) ($currentUser['email'] ?? '')) ?>" disabled>
                        <div class="form-text">
                            Contact support if you need to change the email address on your account.
                            <?php if (($currentUser['email_verified_at'] ?? null) === null): ?>
                                <span class="text-warning d-block">Your email address is not verified yet.</span>
                            <?php else: ?>
                                <span class="text-success d-block">Verified.</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label" for="phone">Phone</label>
                            <input type="text" class="form-control <?= isset($errors['phone']) ? 'is-invalid' : '' ?>"
                                   id="phone" name="phone" value="<?= old('phone', (string) ($currentUser['phone'] ?? '')) ?>">
                            <?php $field = 'phone'; require CIRCLEWORK_ROOT . '/resources/views/components/field-error.php'; ?>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="state">State</label>
                            <select class="form-select" id="state" name="state">
                                <option value="">Select…</option>
                                <?php foreach (App\Controllers\AuthController::nigerianStates() as $state): ?>
                                    <option value="<?= e($state) ?>" <?= old('state', (string) ($currentUser['state'] ?? '')) === $state ? 'selected' : '' ?>><?= e($state) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="city">City</label>
                            <input type="text" class="form-control" id="city" name="city" value="<?= old('city', (string) ($currentUser['city'] ?? '')) ?>">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary">Save changes</button>
                </form>
            </div>
        </div>

        <div class="cw-card">
            <div class="cw-card-header">Change password</div>
            <div class="cw-card-body">
                <form method="post" action="<?= e(route('account.password')) ?>" data-lock-submit data-loading-text="Updating…">
                    <?= csrf_field() ?>
                    <div class="mb-3">
                        <label class="form-label" for="current_password">Current password</label>
                        <input type="password" class="form-control <?= isset($errors['current_password']) ? 'is-invalid' : '' ?>"
                               id="current_password" name="current_password" required autocomplete="current-password">
                        <?php $field = 'current_password'; require CIRCLEWORK_ROOT . '/resources/views/components/field-error.php'; ?>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="password">New password</label>
                        <input type="password" class="form-control <?= isset($errors['password']) ? 'is-invalid' : '' ?>"
                               id="password" name="password" required autocomplete="new-password">
                        <div class="form-text">At least 8 characters, including a letter and a number.</div>
                        <?php $field = 'password'; require CIRCLEWORK_ROOT . '/resources/views/components/field-error.php'; ?>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="password_confirmation">Confirm new password</label>
                        <input type="password" class="form-control <?= isset($errors['password_confirmation']) ? 'is-invalid' : '' ?>"
                               id="password_confirmation" name="password_confirmation" required autocomplete="new-password">
                        <?php $field = 'password_confirmation'; require CIRCLEWORK_ROOT . '/resources/views/components/field-error.php'; ?>
                    </div>
                    <button type="submit" class="btn btn-navy">Update password</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="cw-card mb-4">
            <div class="cw-card-header">Account information</div>
            <div class="cw-card-body">
                <dl class="row g-2 small mb-0">
                    <dt class="col-5 text-muted fw-normal">Account role</dt>
                    <dd class="col-7 text-end"><?= e(humanize((string) ($currentUser['role'] ?? 'user'))) ?></dd>
                    <dt class="col-5 text-muted fw-normal">Account status</dt>
                    <dd class="col-7 text-end"><span class="badge <?= e(status_badge_class((string) ($currentUser['status'] ?? 'active'))) ?>"><?= e(humanize((string) ($currentUser['status'] ?? 'active'))) ?></span></dd>
                    <dt class="col-5 text-muted fw-normal">Member since</dt>
                    <dd class="col-7 text-end"><?= e(dt($currentUser['created_at'] ?? null, 'd M Y')) ?></dd>
                    <dt class="col-5 text-muted fw-normal">Last sign in</dt>
                    <dd class="col-7 text-end"><?= e(dt($currentUser['last_login_at'] ?? null)) ?></dd>
                </dl>
            </div>
        </div>

        <div class="cw-card">
            <div class="cw-card-header">Your data</div>
            <div class="cw-card-body small text-muted">
                <p>
                    Closing your account does not delete contracts, contract acceptances, payment records,
                    disputes or audit logs. Those records are retained because they may be needed for legal,
                    accounting and dispute-resolution purposes.
                </p>
                <p class="mb-0">
                    To request account closure or a copy of your data, contact support through the
                    <a href="<?= e(route('contact')) ?>">contact page</a>. You can also
                    <a href="<?= e(route('report.create')) ?>">report a privacy concern</a>.
                </p>
            </div>
        </div>
    </div>
</div>
