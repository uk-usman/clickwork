<?php
/** @var array<int,array<string,mixed>> $payments */
/** @var array{page:int,last_page:int,total:int} $meta */
/** @var array<string,mixed> $filters */
$action = route('admin.subscription.payments');
$fields = ['status' => ['label' => 'Status', 'type' => 'select', 'value' => $filters['status'] ?? '', 'options' => ['pending' => 'Pending', 'verified' => 'Verified', 'rejected' => 'Rejected']]];
?>
<div class="cw-notice cw-notice--warning mb-4">
    <strong>Manual verification only.</strong>
    No payment gateway is integrated. Verifying a payment here activates the plan for one billing period and
    records who verified it and when. Nothing is processed automatically.
</div>
<?php require CIRCLEWORK_ROOT . '/resources/views/components/admin-filter-form.php'; ?>
<div class="cw-card">
    <div class="table-responsive">
        <table class="table cw-table mb-0 align-middle">
            <thead><tr><th>User</th><th>Plan</th><th>Amount</th><th>Method</th><th>Reference</th><th>Paid</th><th>Status</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($payments as $payment): ?>
                <tr>
                    <td>
                        <strong><?= e((string) $payment['user_name']) ?></strong>
                        <div class="small text-muted"><?= e((string) $payment['user_email']) ?></div>
                    </td>
                    <td><?= e((string) $payment['plan_name']) ?></td>
                    <td><?= e(money($payment['amount'], (string) $payment['currency'])) ?></td>
                    <td class="small"><?= e(humanize((string) $payment['method'])) ?></td>
                    <td class="small"><?= e((string) ($payment['payer_reference'] ?? '—')) ?></td>
                    <td class="small"><?= e(dt($payment['paid_at'] ?? null, 'd M Y')) ?></td>
                    <td><span class="badge <?= e(status_badge_class((string) $payment['status'])) ?>"><?= e(humanize((string) $payment['status'])) ?></span></td>
                    <td class="text-end">
                        <?php if ((string) $payment['status'] === 'pending'): ?>
                            <div class="d-flex gap-1 justify-content-end">
                                <form method="post" action="<?= e(route('admin.subscription.payments.verify', ['id' => (int) $payment['id']])) ?>"
                                      data-confirm="Verify this manual payment and activate the plan?">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="decision" value="verify">
                                    <button type="submit" class="btn btn-sm btn-success">Verify</button>
                                </form>
                                <form method="post" action="<?= e(route('admin.subscription.payments.verify', ['id' => (int) $payment['id']])) ?>"
                                      data-confirm="Reject this recorded payment?">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="decision" value="reject">
                                    <button type="submit" class="btn btn-sm btn-outline-danger">Reject</button>
                                </form>
                            </div>
                        <?php else: ?>
                            <span class="small text-muted">
                                <?= e(humanize((string) $payment['status'])) ?>
                                <?php if (($payment['verified_at'] ?? null) !== null): ?>
                                    on <?= e(dt((string) $payment['verified_at'], 'd M Y')) ?>
                                <?php endif; ?>
                            </span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if ($payments === []): ?>
                <tr><td colspan="8" class="text-center text-muted py-4">No subscription payments found.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require CIRCLEWORK_ROOT . '/resources/views/components/pagination.php'; ?>
