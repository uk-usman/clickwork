<?php /** @var array<int,string> $regions */ ?>
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="cw-card">
                <div class="cw-card-body p-4 p-lg-5">
                    <h1 class="h3 mb-1">Create your CircleWork Africa account</h1>
                    <p class="text-muted mb-4">
                        One account works for both hiring and freelancing. You can switch modes at any time.
                    </p>

                    <form method="post" action="<?= e(route('register.store')) ?>" data-lock-submit data-loading-text="Creating account…">
                        <?= csrf_field() ?>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" for="name">Full name</label>
                                <input type="text" class="form-control <?= isset($errors['name']) ? 'is-invalid' : '' ?>"
                                       id="name" name="name" value="<?= old('name') ?>" required>
                                <?php $field = 'name'; require CIRCLEWORK_ROOT . '/resources/views/components/field-error.php'; ?>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="email">Email address</label>
                                <input type="email" class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>"
                                       id="email" name="email" value="<?= old('email') ?>" required autocomplete="email">
                                <?php $field = 'email'; require CIRCLEWORK_ROOT . '/resources/views/components/field-error.php'; ?>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="password">Password</label>
                                <input type="password" class="form-control <?= isset($errors['password']) ? 'is-invalid' : '' ?>"
                                       id="password" name="password" required autocomplete="new-password">
                                <div class="form-text">At least 8 characters, including a letter and a number.</div>
                                <?php $field = 'password'; require CIRCLEWORK_ROOT . '/resources/views/components/field-error.php'; ?>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="password_confirmation">Confirm password</label>
                                <input type="password" class="form-control <?= isset($errors['password_confirmation']) ? 'is-invalid' : '' ?>"
                                       id="password_confirmation" name="password_confirmation" required autocomplete="new-password">
                                <?php $field = 'password_confirmation'; require CIRCLEWORK_ROOT . '/resources/views/components/field-error.php'; ?>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="phone">Phone number <span class="text-muted fw-normal">(optional)</span></label>
                                <input type="text" class="form-control" id="phone" name="phone" value="<?= old('phone') ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label" for="state">State</label>
                                <select class="form-select" id="state" name="state">
                                    <option value="">Select…</option>
                                    <?php foreach ($regions as $region): ?>
                                        <option value="<?= e($region) ?>" <?= old('state') === $region ? 'selected' : '' ?>><?= e($region) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label" for="city">City</label>
                                <input type="text" class="form-control" id="city" name="city" value="<?= old('city') ?>">
                            </div>
                            <div class="col-12">
                                <label class="form-label">How will you mainly use CircleWork?</label>
                                <div class="d-flex flex-wrap gap-3">
                                    <?php foreach (['both' => 'Both hiring and freelancing', 'client' => 'Mainly hiring', 'freelancer' => 'Mainly freelancing'] as $value => $label): ?>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="account_type"
                                                   id="type-<?= e($value) ?>" value="<?= e($value) ?>"
                                                   <?= old('account_type', 'both') === $value ? 'checked' : '' ?>>
                                            <label class="form-check-label" for="type-<?= e($value) ?>"><?= e($label) ?></label>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>

                        <div class="form-check mt-4">
                            <input class="form-check-input" type="checkbox" id="terms" name="accept_terms" value="1" required>
                            <label class="form-check-label small" for="terms">
                                I agree to the <a href="<?= e(route('terms')) ?>" target="_blank">Terms of Service</a>,
                                <a href="<?= e(route('privacy')) ?>" target="_blank">Privacy Policy</a> and
                                <a href="<?= e(route('guidelines')) ?>" target="_blank">Community Guidelines</a>.
                            </label>
                        </div>

                        <button type="submit" class="btn btn-primary btn-lg w-100 mt-4">Create account</button>
                    </form>

                    <p class="text-center text-muted small mt-3 mb-0">
                        Already registered? <a href="<?= e(route('login')) ?>">Sign in instead</a>.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
