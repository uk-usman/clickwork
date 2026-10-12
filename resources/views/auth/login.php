<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-5">
            <div class="cw-card">
                <div class="cw-card-body p-4 p-lg-5">
                    <h1 class="h3 mb-1">Welcome back</h1>
                    <p class="text-muted mb-4">Sign in to manage your projects, proposals and contracts.</p>

                    <form method="post" action="<?= e(route('login.attempt')) ?>" data-lock-submit data-loading-text="Signing in…">
                        <?= csrf_field() ?>

                        <div class="mb-3">
                            <label class="form-label" for="email">Email address</label>
                            <input type="email" class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>"
                                   id="email" name="email" value="<?= old('email', $email ?? '') ?>"
                                   required autocomplete="email" autofocus>
                            <?php $field = 'email'; require CIRCLEWORK_ROOT . '/resources/views/components/field-error.php'; ?>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="password">Password</label>
                            <input type="password" class="form-control <?= isset($errors['password']) ? 'is-invalid' : '' ?>"
                                   id="password" name="password" required autocomplete="current-password">
                            <?php $field = 'password'; require CIRCLEWORK_ROOT . '/resources/views/components/field-error.php'; ?>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 btn-lg">Sign in</button>
                    </form>

                    <div class="d-flex justify-content-between mt-3 small">
                        <a href="<?= e(route('password.request')) ?>">Forgot your password?</a>
                        <a href="<?= e(route('register')) ?>">Create an account</a>
                    </div>
                </div>
            </div>

            <p class="text-center text-muted small mt-3 mb-0">
                Accounts are rate limited after repeated failed sign-in attempts.
            </p>
        </div>
    </div>
</div>
