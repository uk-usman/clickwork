<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-5">
            <div class="cw-card"><div class="cw-card-body p-4 p-lg-5">
                <h1 class="h3 mb-1">Choose a new password</h1>
                <p class="text-muted mb-4">Reset links expire after 60 minutes and can only be used once.</p>

                <form method="post" action="<?= e(route('password.reset', ['token' => $token])) ?>" data-lock-submit data-loading-text="Updating…">
                    <?= csrf_field() ?>
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
                    <button type="submit" class="btn btn-primary w-100">Reset password</button>
                </form>
            </div></div>
        </div>
    </div>
</div>
