<?php
/** @var array<string,mixed> $payment */
/** @var array<int,array<string,mixed>> $evidence */
?>
<div class="row g-4">
    <div class="col-lg-7">
        <div class="cw-card">
            <div class="cw-card-header d-flex justify-content-between align-items-center">
                <span>Payment <?= e((string) $payment['reference']) ?></span>
                <span class="badge <?= e(status_badge_class((string) $payment['status'])) ?>"><?= e(humanize((string) $payment['status'])) ?></span>
            </div>
            <div class="cw-card-body">
                <dl class="row g-2 small">
                    <dt class="col-5 text-muted fw-normal">Contract</dt><dd class="col-7 text-end"><?= e((string) $payment['contract_reference']) ?></dd>
                    <dt class="col-5 text-muted fw-normal">Amount</dt><dd class="col-7 text-end fw-semibold"><?= e(money($payment['amount'], (string) $payment['currency'])) ?></dd>
                    <dt class="col-5 text-muted fw-normal">Method</dt><dd class="col-7 text-end"><?= e(humanize((string) $payment['method'])) ?></dd>
                    <dt class="col-5 text-muted fw-normal">Payer reference</dt><dd class="col-7 text-end"><?= e((string) ($payment['payer_reference'] ?? '—')) ?></dd>
                    <dt class="col-5 text-muted fw-normal">Date paid</dt><dd class="col-7 text-end"><?= ($payment['paid_on'] ?? null) ? e(date('d M Y', strtotime((string) $payment['paid_on']))) : '—' ?></dd>
                    <dt class="col-5 text-muted fw-normal">Payer</dt><dd class="col-7 text-end"><?= e((string) $payment['payer_name']) ?></dd>
                    <dt class="col-5 text-muted fw-normal">Payee</dt><dd class="col-7 text-end"><?= e((string) $payment['payee_name']) ?></dd>
                    <dt class="col-5 text-muted fw-normal">Reported by</dt><dd class="col-7 text-end"><?= e((string) $payment['reported_by_name']) ?></dd>
                    <dt class="col-5 text-muted fw-normal">Reported at</dt><dd class="col-7 text-end"><?= e(dt((string) $payment['created_at'])) ?></dd>
                    <?php if (($payment['confirmed_at'] ?? null) !== null): ?>
                        <dt class="col-5 text-muted fw-normal">Confirmed by</dt><dd class="col-7 text-end"><?= e((string) ($payment['confirmed_by_name'] ?? '—')) ?> on <?= e(dt((string) $payment['confirmed_at'])) ?></dd>
                    <?php endif; ?>
                    <?php if (($payment['disputed_at'] ?? null) !== null): ?>
                        <dt class="col-5 text-muted fw-normal">Disputed at</dt><dd class="col-7 text-end"><?= e(dt((string) $payment['disputed_at'])) ?></dd>
                    <?php endif; ?>
                </dl>

                <?php if (!empty($payment['note'])): ?>
                    <p class="small"><strong>Reporter note:</strong> <?= e((string) $payment['note']) ?></p>
                <?php endif; ?>
                <?php if (!empty($payment['dispute_reason'])): ?>
                    <div class="alert alert-danger small"><strong>Dispute reason:</strong> <?= e((string) $payment['dispute_reason']) ?></div>
                <?php endif; ?>

                <?php if ((string) $payment['status'] === 'reported'): ?>
                    <div class="alert alert-warning small">
                        Status <strong>Reported</strong>: the parties have not both confirmed this payment, and
                        CircleWork Africa has not independently verified any transfer.
                    </div>
                <?php endif; ?>

                <h2 class="h6 mt-4">Uploaded evidence</h2>
                <?php if ($evidence === []): ?>
                    <p class="small text-muted mb-0">No evidence uploaded.</p>
                <?php else: ?>
                    <ul class="list-unstyled small mb-0">
                        <?php foreach ($evidence as $item): ?>
                            <li class="mb-1">
                                <i class="bi bi-paperclip"></i>
                                <a href="<?= e(route('payment_evidence.download', ['id' => (int) $item['id']])) ?>"><?= e($item['original_name']) ?></a>
                                <span class="text-muted">· uploaded by <?= e($item['uploaded_by_name']) ?> on <?= e(dt((string) $item['created_at'])) ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="cw-card">
            <div class="cw-card-header">Record a review outcome</div>
            <div class="cw-card-body">
                <p class="small text-muted">
                    This records an administrative review decision only. It does not move money, reverse a bank
                    transfer, or verify that a payment occurred.
                </p>
                <form method="post" action="<?= e(route('admin.payments.review', ['id' => (int) $payment['id']])) ?>"
                      data-confirm="Record this review outcome?">
                    <?= csrf_field() ?>
                    <div class="mb-3">
                        <label class="form-label small" for="decision">Outcome</label>
                        <select class="form-select form-select-sm" id="decision" name="decision" required>
                            <?php foreach (['reported', 'confirmed', 'disputed', 'reversed'] as $option): ?>
                                <option value="<?= e($option) ?>" <?= (string) $payment['status'] === $option ? 'selected' : '' ?>><?= e(humanize($option)) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small" for="admin_note">Administrative note</label>
                        <textarea class="form-control form-control-sm" id="admin_note" name="admin_note" rows="4"></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm w-100">Save review outcome</button>
                </form>
                <p class="small text-muted mt-3 mb-0">
                    Both parties are notified of the outcome and the action is written to the audit log.
                </p>
            </div>
        </div>
    </div>
</div>
