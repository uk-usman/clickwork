<?php
/** @var array<string,mixed> $contract */
/** @var array<int,array<string,mixed>> $payments */
/** @var array<int,string> $methods */
$userId = (int) App\Core\Auth::id();
$isClient = (int) $contract['client_id'] === $userId;
?>
<div class="cw-card mb-4">
    <div class="cw-card-body d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div>
            <h2 class="h6 mb-1"><?= e($contract['title']) ?></h2>
            <p class="text-muted small mb-0">
                Contract <?= e($contract['reference']) ?> ·
                agreed amount <?= e(money($contract['amount'], (string) $contract['currency'])) ?>
            </p>
        </div>
        <a class="btn btn-sm btn-outline-secondary" href="<?= e(route('contracts.show', ['id' => (int) $contract['id']])) ?>">Open contract</a>
    </div>
</div>

<div class="cw-notice cw-notice--warning mb-4">
    <strong>How payment records work.</strong>
    Payments are made directly between client and freelancer. Recording a payment here does not move money and
    does not verify it. A record becomes “Confirmed” only when the other party confirms it. Either party may
    mark a record as disputed for administrative review. CircleWork Africa cannot recover funds transferred
    directly between users.
</div>

<div class="row g-4">
    <div class="col-lg-7">
        <div class="cw-card">
            <div class="cw-card-header">Payment records</div>
            <?php if ($payments === []): ?>
                <div class="cw-card-body text-muted">No payments recorded on this contract yet.</div>
            <?php else: ?>
                <div class="cw-card-body">
                    <?php foreach ($payments as $payment): ?>
                        <div class="border rounded p-3 mb-3">
                            <div class="d-flex justify-content-between align-items-start gap-2 flex-wrap">
                                <div>
                                    <strong><?= e(money($payment['amount'], (string) $payment['currency'])) ?></strong>
                                    <div class="small text-muted">
                                        <?= e($payment['reference']) ?> · <?= e(humanize((string) $payment['method'])) ?>
                                        <?php if (($payment['paid_on'] ?? null) !== null): ?>
                                            · paid <?= e(date('d M Y', strtotime((string) $payment['paid_on']))) ?>
                                        <?php endif; ?>
                                    </div>
                                    <div class="small text-muted">
                                        Payer: <?= e($payment['payer_name']) ?> · Payee: <?= e($payment['payee_name']) ?>
                                    </div>
                                    <div class="small text-muted">
                                        Reported by <?= e($payment['reported_by_name']) ?> on <?= e(dt((string) $payment['created_at'])) ?>
                                    </div>
                                </div>
                                <span class="badge <?= e(status_badge_class((string) $payment['status'])) ?>"><?= e(humanize((string) $payment['status'])) ?></span>
                            </div>

                            <?php if ((string) $payment['status'] === 'reported'): ?>
                                <div class="alert alert-warning py-2 px-3 small mt-2 mb-2">
                                    Status <strong>Reported</strong> — this has not been independently verified by CircleWork Africa.
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($payment['note'])): ?>
                                <p class="small mb-2"><strong>Note:</strong> <?= e((string) $payment['note']) ?></p>
                            <?php endif; ?>

                            <?php if (!empty($payment['dispute_reason'])): ?>
                                <p class="small text-danger mb-2"><strong>Dispute:</strong> <?= e((string) $payment['dispute_reason']) ?></p>
                            <?php endif; ?>

                            <?php $evidence = App\Models\PaymentEvidence::forRecord((int) $payment['id']); ?>
                            <?php if ($evidence !== []): ?>
                                <ul class="list-unstyled small mb-2">
                                    <?php foreach ($evidence as $item): ?>
                                        <li>
                                            <i class="bi bi-paperclip"></i>
                                            <a href="<?= e(route('payment_evidence.download', ['id' => (int) $item['id']])) ?>">
                                                <?= e($item['original_name']) ?>
                                            </a>
                                            <span class="text-muted">uploaded by <?= e($item['uploaded_by_name']) ?></span>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>

                            <?php $isParty = in_array($userId, [(int) $payment['payer_id'], (int) $payment['payee_id']], true); ?>
                            <?php if ($isParty && (string) $payment['status'] === 'reported'): ?>
                                <div class="d-flex flex-wrap gap-2">
                                    <?php if ((int) $payment['reported_by'] !== $userId): ?>
                                        <form method="post"
                                              action="<?= e(route('contracts.payments.confirm', ['contractId' => (int) $contract['id'], 'id' => (int) $payment['id']])) ?>"
                                              data-confirm="Confirm that this payment was received or made as recorded?">
                                            <?= csrf_field() ?>
                                            <button type="submit" class="btn btn-sm btn-success">Confirm payment</button>
                                        </form>
                                    <?php else: ?>
                                        <span class="small text-muted">Waiting for the other party to confirm this record.</span>
                                    <?php endif; ?>
                                    <button class="btn btn-sm btn-outline-danger" type="button" data-bs-toggle="collapse"
                                            data-bs-target="#dispute-<?= (int) $payment['id'] ?>">Flag as disputed</button>
                                </div>
                                <div class="collapse mt-2" id="dispute-<?= (int) $payment['id'] ?>">
                                    <form method="post"
                                          action="<?= e(route('contracts.payments.dispute', ['contractId' => (int) $contract['id'], 'id' => (int) $payment['id']])) ?>">
                                        <?= csrf_field() ?>
                                        <label class="form-label small" for="reason-<?= (int) $payment['id'] ?>">Why is this record wrong?</label>
                                        <textarea class="form-control form-control-sm" id="reason-<?= (int) $payment['id'] ?>"
                                                  name="reason" rows="3" required minlength="10"></textarea>
                                        <button type="submit" class="btn btn-warning btn-sm mt-2">Flag as disputed</button>
                                    </form>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="cw-card">
            <div class="cw-card-header">Record a payment</div>
            <div class="cw-card-body">
                <p class="small text-muted">
                    Use this to document a payment you have made or received outside the platform. Payer and payee
                    are taken from the contract, not from this form.
                </p>
                <form method="post" action="<?= e(route('contracts.payments.report', ['id' => (int) $contract['id']])) ?>"
                      enctype="multipart/form-data" data-lock-submit data-loading-text="Recording…">
                    <?= csrf_field() ?>
                    <div class="mb-3">
                        <label class="form-label" for="amount">Amount (<?= e((string) $contract['currency']) ?>)</label>
                        <input type="number" step="0.01" min="1" class="form-control <?= isset($errors['amount']) ? 'is-invalid' : '' ?>"
                               id="amount" name="amount" required>
                        <?php $field = 'amount'; require CIRCLEWORK_ROOT . '/resources/views/components/field-error.php'; ?>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="method">Payment method</label>
                        <select class="form-select" id="method" name="method" required>
                            <?php foreach ($methods as $method): ?>
                                <option value="<?= e($method) ?>"><?= e(humanize($method)) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="payer_reference">Payment reference</label>
                        <input type="text" class="form-control" id="payer_reference" name="payer_reference"
                               placeholder="e.g. bank transfer reference">
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="paid_on">Date paid</label>
                        <input type="date" class="form-control" id="paid_on" name="paid_on" max="<?= date('Y-m-d') ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="note">Note</label>
                        <textarea class="form-control" id="note" name="note" rows="3" maxlength="500"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="evidence_1">Supporting evidence</label>
                        <input type="file" class="form-control" id="evidence_1" name="evidence_1">
                        <div class="form-text">Stored privately. Only the parties on this contract and administrators can open it.</div>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Record payment</button>
                    <p class="form-text mb-0 mt-2">
                        The record will be created with status <strong>Reported</strong>, not “verified”.
                    </p>
                </form>
            </div>
        </div>
    </div>
</div>
