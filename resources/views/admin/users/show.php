<?php
/** @var array<string,mixed> $user */
/** @var array<string,int> $counts */
/** @var array<int,array<string,mixed>> $audit */
?>
<div class="row g-4">
    <div class="col-lg-4">
        <div class="cw-card mb-4">
            <div class="cw-card-body">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <?php $avatar = avatar_url($user['avatar_path'] ?? null); ?>
                    <?php if ($avatar !== null): ?>
                        <img src="<?= e($avatar) ?>" alt="" class="cw-avatar cw-avatar--lg">
                    <?php else: ?>
                        <span class="cw-avatar cw-avatar--lg"><?= e(initials((string) $user['name'])) ?></span>
                    <?php endif; ?>
                    <div>
                        <h2 class="h6 mb-1"><?= e($user['name']) ?></h2>
                        <div class="small text-muted"><?= e($user['email']) ?></div>
                        <span class="badge <?= e(status_badge_class((string) $user['status'])) ?>"><?= e(humanize((string) $user['status'])) ?></span>
                        <span class="badge text-bg-secondary"><?= e(humanize((string) $user['role'])) ?></span>
                    </div>
                </div>

                <dl class="row g-2 small mb-0">
                    <dt class="col-6 text-muted fw-normal">Registered</dt><dd class="col-6 text-end"><?= e(dt((string) $user['created_at'], 'd M Y')) ?></dd>
                    <dt class="col-6 text-muted fw-normal">Email verified</dt><dd class="col-6 text-end"><?= ($user['email_verified_at'] ?? null) !== null ? 'Yes' : 'No' ?></dd>
                    <dt class="col-6 text-muted fw-normal">Last sign in</dt><dd class="col-6 text-end"><?= e(dt($user['last_login_at'] ?? null)) ?></dd>
                    <dt class="col-6 text-muted fw-normal">Location</dt><dd class="col-6 text-end"><?= e(trim(($user['city'] ?? '') . ', ' . ($user['state'] ?? ''), ', ') ?: '—') ?></dd>
                    <dt class="col-6 text-muted fw-normal">Rating</dt><dd class="col-6 text-end"><?= e(number_format((float) ($user['rating_average'] ?? 0), 1)) ?> (<?= (int) ($user['rating_count'] ?? 0) ?>)</dd>
                </dl>

                <?php if (!empty($user['suspension_reason'])): ?>
                    <div class="alert alert-danger small mt-3 mb-0">
                        <strong>Suspension reason:</strong> <?= e((string) $user['suspension_reason']) ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="cw-card">
            <div class="cw-card-header">Moderation actions</div>
            <div class="cw-card-body">
                <?php if ((string) $user['status'] === 'suspended'): ?>
                    <form method="post" action="<?= e(route('admin.users.activate', ['id' => (int) $user['id']])) ?>"
                          data-confirm="Reactivate this account?">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn-success w-100">Reactivate account</button>
                    </form>
                <?php else: ?>
                    <form method="post" action="<?= e(route('admin.users.suspend', ['id' => (int) $user['id']])) ?>"
                          data-confirm="Suspend this account? The user will be signed out and blocked from signing in.">
                        <?= csrf_field() ?>
                        <label class="form-label small" for="reason">Reason (recorded in the audit log)</label>
                        <textarea class="form-control form-control-sm" id="reason" name="reason" rows="3" required minlength="5"></textarea>
                        <button type="submit" class="btn btn-danger w-100 mt-2">Suspend account</button>
                    </form>
                <?php endif; ?>
                <p class="small text-muted mt-3 mb-0">
                    Financial records and accepted contracts are never deleted through account moderation.
                </p>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="row row-cols-2 row-cols-md-4 g-3 mb-4">
            <?php foreach ([['Projects', $counts['projects'], 'bi-kanban'], ['Proposals', $counts['proposals'], 'bi-send'], ['Contracts', $counts['contracts'], 'bi-file-earmark-text'], ['Reviews received', $counts['reviews'], 'bi-star']] as [$label, $value, $icon]): ?>
                <div class="col">
                    <div class="cw-stat">
                        <div class="cw-stat__value"><?= (int) $value ?></div>
                        <div class="cw-stat__label"><?= e($label) ?></div>
                        <span class="cw-stat__icon mt-2"><i class="bi <?= e($icon) ?>"></i></span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="cw-card">
            <div class="cw-card-header">Account audit trail</div>
            <div class="cw-card-body">
                <?php if ($audit === []): ?>
                    <p class="text-muted mb-0">No audit entries for this account.</p>
                <?php else: ?>
                    <ul class="cw-timeline mb-0">
                        <?php foreach ($audit as $entry): ?>
                            <li>
                                <div class="small fw-semibold"><?= e(humanize((string) $entry['action'])) ?></div>
                                <div class="small text-muted"><?= e(str_excerpt((string) $entry['description'], 110)) ?></div>
                                <div class="small text-muted"><?= e(dt((string) $entry['created_at'])) ?> · <?= e((string) ($entry['ip_address'] ?? '')) ?></div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
