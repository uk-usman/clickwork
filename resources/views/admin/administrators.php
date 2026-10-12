<?php /** @var array<int,array<string,mixed>> $admins */ ?>
<div class="cw-notice mb-4">
    <strong>Administrator accounts are provisioned deliberately.</strong>
    Public registration never creates an administrator. The first super administrator is created with the protected
    command-line utility <code>database/seeders/create_initial_admin.php</code>. Further administrators are promoted
    from existing standard accounts below, and every change is written to the audit log.
</div>

<div class="row g-4">
    <div class="col-lg-7">
        <div class="cw-card">
            <div class="cw-card-header">Administrator accounts</div>
            <div class="table-responsive">
                <table class="table cw-table mb-0 align-middle">
                    <thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Status</th><th>Last sign in</th><th></th></tr></thead>
                    <tbody>
                    <?php foreach ($admins as $admin): ?>
                        <tr>
                            <td><strong><?= e((string) $admin['name']) ?></strong></td>
                            <td class="small"><?= e((string) $admin['email']) ?></td>
                            <td><span class="badge text-bg-secondary"><?= e(humanize((string) $admin['role'])) ?></span></td>
                            <td><span class="badge <?= e(status_badge_class((string) $admin['status'])) ?>"><?= e(humanize((string) $admin['status'])) ?></span></td>
                            <td class="small text-muted"><?= e(dt($admin['last_login_at'] ?? null)) ?></td>
                            <td class="text-end">
                                <?php if ((int) $admin['id'] !== (int) App\Core\Auth::id()): ?>
                                    <form method="post" action="<?= e(route('admin.administrators.demote', ['id' => (int) $admin['id']])) ?>"
                                          data-confirm="Remove the administrative role from this account?">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Remove admin role</button>
                                    </form>
                                <?php else: ?>
                                    <span class="small text-muted">Your account</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="cw-card">
            <div class="cw-card-header">Grant an administrative role</div>
            <div class="cw-card-body">
                <p class="small text-muted">
                    The person must already have a standard account. Enter the email address used on that account.
                </p>
                <form method="post" action="<?= e(route('admin.administrators.promote')) ?>"
                      data-confirm="Grant this administrative role? The change is written to the audit log.">
                    <?= csrf_field() ?>
                    <div class="mb-3">
                        <label class="form-label small" for="email">Account email address</label>
                        <input type="email" class="form-control form-control-sm" id="email" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small" for="role">Role</label>
                        <select class="form-select form-select-sm" id="role" name="role" required>
                            <option value="admin">Administrator</option>
                            <option value="super_admin">Super administrator</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm w-100">Grant role</button>
                </form>
            </div>
        </div>
    </div>
</div>
