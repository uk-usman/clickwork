<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-5">
            <div class="cw-card"><div class="cw-card-body p-4 p-lg-5">
                <h1 class="h3 mb-1">Forgot your password?</h1>
                <p class="text-muted mb-4">Enter your email address and we will send a reset link if an account exists.</p>

                <form method="post" action="<?= e(route('password.email')) ?>" data-lock-submit data-loading-text="Sending…">
                    <?= csrf_field() ?>
                    <div class="mb-3">
                        <label class="form-label" for="email">Email address</label>
                        <input type="email" class="form-control" id="email" name="email" value="<?= old('email') ?>" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Send reset link</button>
                </form>

                <p class="text-center small mt-3 mb-0">
                    <a href="<?= e(route('login')) ?>">Back to sign in</a>
                </p>
            </div></div>
        </div>
    </div>
</div>
