<?php
/** @var array<int,array<string,mixed>> $payments */
/** @var array{page:int,last_page:int,total:int} $meta */
/** @var array<string,mixed> $filters */
/** @var array<int,string> $statuses */
$options = [];
foreach ($statuses as $status) { $options[$status] = humanize($status); }
$action = route('admin.payments');
$fields = [
    'q' => ['label' => 'Search reference or email', 'type' => 'text', 'value' => $filters['q'] ?? ''],
    'status' => ['label' => 'Status', 'type' => 'select', 'value' => $filters['status'] ?? '', 'options' => $options],
];
?>
<?php require CIRCLEWORK_ROOT . '/resources/views/components/admin-filter-form.php'; ?>

<div class="cw-notice cw-notice--warning mb-4">
    CircleWork Africa does not hold funds and cannot recover money transferred directly between users.
    Administrative review records an outcome; it does not move money or verify a bank transfer independently.
</div>

<div class="cw-card">
    <div class="table-responsive">
        <table class="table cw-table mb-0 align-middle">
            <thead><tr><th>Reference</th><th>Contract</th><th>Payer</th><th>Payee</th><th>Amount</th><th>Method</th><th>Status</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($payments as $payment): ?>
                <tr>
                    <td class="small"><?= e((string) $payment['reference']) ?></td>
                    <td class="small"><?= e((string) $payment['contract_reference']) ?></td>
                    <td class="small"><?= e((string) $payment['payer_name']) ?></td>
                    <td class="small"><?= e((string) $payment['payee_name']) ?></td>
                    <td><?= e(money($payment['amount'], (string) $payment['currency'])) ?></td>
                    <td class="small"><?= e(humanize((string) $payment['method'])) ?></td>
                    <td><span class="badge <?= e(status_badge_class((string) $payment['status'])) ?>"><?= e(humanize((string) $payment['status'])) ?></span></td>
                    <td class="text-end"><a class="btn btn-sm btn-outline-primary" href="<?= e(route('admin.payments.show', ['id' => (int) $payment['id']])) ?>">Review</a></td>
                </tr>
            <?php endforeach; ?>
            <?php if ($payments === []): ?>
                <tr><td colspan="8" class="text-center text-muted py-4">No payment records found.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require CIRCLEWORK_ROOT . '/resources/views/components/pagination.php'; ?>
