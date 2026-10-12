<?php /** @var array<int,array<string,mixed>> $payments */ ?>
<div class="cw-notice mb-4">
    <strong>Direct payments only.</strong>
    CircleWork Africa does not hold funds, operate escrow or move money between users. Client and freelancer pay
    each other directly, and the platform records what the parties report. A record marked “Reported” has not
    been verified by us.
</div>

<?php if ($payments === []): ?>
    <div class="cw-empty">
        <i class="bi bi-cash-coin"></i>
        <h2 class="h6 mt-3">No payment records yet</h2>
        <p class="text-muted mb-0">Payment records appear here once you record a payment on a contract.</p>
    </div>
<?php else: ?>
    <div class="cw-card">
        <div class="table-responsive">
            <table class="table cw-table mb-0 align-middle">
                <thead><tr><th>Reference</th><th>Contract</th><th>Amount</th><th>Method</th><th>Status</th><th>Recorded</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($payments as $payment): ?>
                    <tr>
                        <td class="small"><?= e($payment['reference']) ?></td>
                        <td>
                            <div class="small"><?= e(str_excerpt((string) $payment['project_title'], 34)) ?></div>
                            <div class="small text-muted"><?= e($payment['contract_reference']) ?></div>
                        </td>
                        <td class="fw-semibold"><?= e(money($payment['amount'], (string) $payment['currency'])) ?></td>
                        <td class="small"><?= e(humanize((string) $payment['method'])) ?></td>
                        <td><span class="badge <?= e(status_badge_class((string) $payment['status'])) ?>"><?= e(humanize((string) $payment['status'])) ?></span></td>
                        <td class="small text-muted"><?= e(dt((string) $payment['created_at'], 'd M Y')) ?></td>
                        <td class="text-end">
                            <a class="btn btn-sm btn-outline-primary" href="<?= e(route('contracts.payments', ['id' => (int) $payment['contract_id']])) ?>">Open</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>
