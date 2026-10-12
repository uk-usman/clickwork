<?php /** @var array<string,mixed> $currentUser */ ?>
<div class="row g-4">
    <div class="col-lg-6">
        <div class="cw-card mb-4">
            <div class="cw-card-header">Administrator profile</div>
            <div class="cw-card-body">
                <form method="post" action="<?= e(route('admin.profile.update')) ?>" data-lock-submit data-loading-text="Saving…">
                    <?= csrf_field() ?>
                    <div class="mb-3">
                        <label class="form-label" for="name">Display name</label>
                        <input type="text" class="form-control" id="name" name="name"
                               value="<?= e((string) ($currentUser['name'] ?? '')) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="email">Email address</label>
                        <input type="email" class="form-control" id="email" value="<?= e((string) ($currentUser['email'] ?? '')) ?>" disabled>
                    </div>
                    <button type="submit" class="btn btn-primary">Save profile</button>
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
                        <input type="password" class="form-control" id="current_password" name="current_password" required autocomplete="current-password">
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="password">New password</label>
                        <input type="password" class="form-control" id="password" name="password" required autocomplete="new-password">
                        <div class="form-text">At least 8 characters, including a letter and a number.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="password_confirmation">Confirm new password</label>
                        <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" required autocomplete="new-password">
                    </div>
                    <button type="submit" class="btn btn-navy">Update password</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="cw-card mb-4">
            <div class="cw-card-header">Administrative responsibilities</div>
            <div class="cw-card-body small text-muted">
                <p>As an administrator you can moderate projects, review reports and disputes, verify manual payments, manage categories and skills, and configure platform settings.</p>
                <p class="mb-0">
                    You cannot silently rewrite accepted contract terms or impersonate a user. Sensitive actions are
                    written to the audit log with your account, the timestamp and your IP address.
                </p>
            </div>
        </div>

        <div class="cw-card">
            <div class="cw-card-header">Security reminders</div>
            <div class="cw-card-body small text-muted">
                <ul class="mb-0 ps-3">
                    <li class="mb-2">Use a unique password that you do not reuse on other services.</li>
                    <li class="mb-2">Do not share your credentials. Every action is attributed to your account in the audit log.</li>
                    <li>Review the audit log periodically for unexpected administrative activity.</li>
                </ul>
            </div>
        </div>
    </div>
</div>
