<?php
/** @var array<string,mixed> $contract */
/** @var array<int,array<string,mixed>> $versions */
/** @var array<int,array<string,mixed>> $acceptances */
/** @var array<int,array<string,mixed>> $events */
/** @var array<int,array<string,mixed>> $deliverables */
/** @var array<int,array<string,mixed>> $payments */
/** @var array<int,array<string,mixed>> $disputes */
?>
<div class="cw-notice mb-4">
    <strong>Read-only review.</strong>
    Administrators can inspect contracts, versions, acceptances and events for dispute review. Accepted terms are
    never edited in place and administrators never impersonate a party.
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="cw-card mb-4">
            <div class="cw-card-header d-flex justify-content-between align-items-center">
                <span><?= e((string) $contract['reference']) ?> · version <?= (int) $contract['current_version'] ?></span>
                <span class="badge <?= e(status_badge_class((string) $contract['status'])) ?>"><?= e(humanize((string) $contract['status'])) ?></span>
            </div>
            <div class="cw-card-body cw-contract-doc">
                <h2 class="h5"><?= e($contract['title']) ?></h2>
                <p class="text-muted small">
                    Project <?= e((string) $contract['project_reference']) ?> ·
                    Client <?= e($contract['client_name']) ?> · Freelancer <?= e($contract['freelancer_name']) ?>
                </p>
                <dl>
                    <dt>Agreed amount</dt><dd><?= e(money($contract['amount'], (string) $contract['currency'])) ?></dd>
                    <dt>Deadline</dt><dd><?= ($contract['deadline'] ?? null) ? e(date('d M Y', strtotime((string) $contract['deadline']))) : 'Flexible' ?></dd>
                    <dt>Scope</dt><dd><?= e($contract['scope']) ?></dd>
                    <dt>Deliverables</dt><dd><?= e($contract['deliverables']) ?></dd>
                    <dt>Revision terms</dt><dd><?= e($contract['revision_terms']) ?></dd>
                    <dt>Payment arrangement</dt><dd><?= e($contract['payment_terms']) ?></dd>
                    <dt>Cancellation conditions</dt><dd><?= e($contract['cancellation_terms']) ?></dd>
                    <dt>Dispute process</dt><dd><?= e($contract['dispute_terms']) ?></dd>
                </dl>
            </div>
        </div>

        <div class="cw-card mb-4">
            <div class="cw-card-header">Version history</div>
            <div class="cw-card-body">
                <div class="table-responsive">
                    <table class="table table-sm cw-table mb-0">
                        <thead><tr><th>Version</th><th>Created</th><th>By</th><th>Summary</th><th>Terms hash</th></tr></thead>
                        <tbody>
                        <?php foreach ($versions as $version): ?>
                            <tr>
                                <td><?= (int) $version['version'] ?></td>
                                <td class="small"><?= e(dt((string) $version['created_at'])) ?></td>
                                <td class="small"><?= e((string) ($version['created_by_name'] ?? 'System')) ?></td>
                                <td class="small"><?= e((string) ($version['change_summary'] ?? '—')) ?></td>
                                <td><span class="cw-hash"><?= e(substr((string) $version['terms_hash'], 0, 24)) ?>…</span></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="cw-card">
            <div class="cw-card-header">Event history</div>
            <div class="cw-card-body">
                <ul class="cw-timeline mb-0">
                    <?php foreach ($events as $event): ?>
                        <li>
                            <div class="small fw-semibold"><?= e(humanize((string) $event['event_type'])) ?></div>
                            <div class="small text-muted"><?= e((string) $event['description']) ?></div>
                            <div class="small text-muted"><?= e((string) ($event['actor_name'] ?? 'System')) ?> · <?= e(dt((string) $event['created_at'])) ?></div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="cw-card mb-4">
            <div class="cw-card-header">Current version acceptances</div>
            <div class="cw-card-body">
                <?php if ($acceptances === []): ?>
                    <p class="small text-muted mb-0">No acceptances recorded for the current version.</p>
                <?php else: ?>
                    <ul class="list-unstyled small mb-0">
                        <?php foreach ($acceptances as $acceptance): ?>
                            <li class="mb-2">
                                <strong><?= e($acceptance['user_name']) ?></strong> as <?= e(humanize((string) $acceptance['party_role'])) ?><br>
                                <span class="text-muted"><?= e(dt((string) $acceptance['accepted_at'])) ?> · IP <?= e((string) $acceptance['ip_address']) ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>

        <div class="cw-card mb-4">
            <div class="cw-card-header">Deliverables</div>
            <div class="cw-card-body small">
                <?php if ($deliverables === []): ?>
                    <p class="text-muted mb-0">None submitted.</p>
                <?php else: ?>
                    <?php foreach ($deliverables as $deliverable): ?>
                        <div class="border-bottom pb-2 mb-2">
                            <strong>Version <?= (int) $deliverable['version'] ?></strong>
                            <span class="badge <?= e(status_badge_class((string) $deliverable['status'])) ?>"><?= e(humanize((string) $deliverable['status'])) ?></span>
                            <div class="text-muted"><?= e(dt((string) $deliverable['created_at'])) ?></div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <div class="cw-card mb-4">
            <div class="cw-card-header">Payment records</div>
            <div class="cw-card-body small">
                <?php if ($payments === []): ?>
                    <p class="text-muted mb-0">No payment records.</p>
                <?php else: ?>
                    <?php foreach ($payments as $payment): ?>
                        <div class="border-bottom pb-2 mb-2">
                            <strong><?= e(money($payment['amount'], (string) $payment['currency'])) ?></strong>
                            <span class="badge <?= e(status_badge_class((string) $payment['status'])) ?>"><?= e(humanize((string) $payment['status'])) ?></span>
                            <div class="text-muted"><?= e((string) $payment['reference']) ?></div>
                        </div>
                    <?php endforeach; ?>
                    <a class="btn btn-sm btn-outline-primary w-100" href="<?= e(route('admin.payments')) ?>">Open payment records</a>
                <?php endif; ?>
            </div>
        </div>

        <div class="cw-card">
            <div class="cw-card-header">Disputes</div>
            <div class="cw-card-body small">
                <?php if ($disputes === []): ?>
                    <p class="text-muted mb-0">No disputes on this contract.</p>
                <?php else: ?>
                    <?php foreach ($disputes as $dispute): ?>
                        <div class="border-bottom pb-2 mb-2">
                            <a href="<?= e(route('admin.disputes.show', ['id' => (int) $dispute['id']])) ?>"><?= e((string) $dispute['reference']) ?></a>
                            <span class="badge <?= e(status_badge_class((string) $dispute['status'])) ?>"><?= e(humanize((string) $dispute['status'])) ?></span>
                            <div class="text-muted"><?= e(str_excerpt((string) $dispute['subject'], 60)) ?></div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
