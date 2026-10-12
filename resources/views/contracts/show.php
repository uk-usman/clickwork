<?php
/** @var array<string,mixed> $contract */
/** @var string $role */
/** @var array<string,mixed>|null $versionRow */
/** @var array<int,array<string,mixed>> $acceptances */
/** @var bool $hasAccepted */
/** @var array<int,array<string,mixed>> $events */
/** @var array<int,array<string,mixed>> $deliverables */
/** @var array<int,array<string,mixed>> $payments */
/** @var array<int,array<string,mixed>> $disputes */
/** @var array<string,mixed>|null $proposal */
/** @var bool $canReview */
/** @var array<string,mixed>|null $myReview */

$isClient = $role === 'client';
$counterpartyName = $isClient ? $contract['freelancer_name'] : $contract['client_name'];
$awaiting = (string) $contract['status'] === 'awaiting_acceptance';
$canCancel = !in_array((string) $contract['status'], ['completed', 'cancelled'], true);
$latestDeliverable = $deliverables !== [] ? $deliverables[0] : null;
$acceptedRoles = array_map(static fn (array $a): string => (string) $a['party_role'], $acceptances);
?>

<?php if ($awaiting): ?>
    <div class="alert alert-warning d-flex gap-2 align-items-start">
        <i class="bi bi-hourglass-split mt-1"></i>
        <div>
            <strong>This contract is not active yet.</strong>
            <div class="small">
                It becomes active when both parties accept the same version.
                Accepted so far:
                <?= $acceptedRoles === [] ? 'nobody yet' : e(implode(' and ', array_map('humanize', $acceptedRoles))) ?>.
            </div>
        </div>
    </div>
<?php endif; ?>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="cw-card mb-4">
            <div class="cw-card-header d-flex justify-content-between align-items-center gap-2">
                <span>Contract <?= e($contract['reference']) ?> · version <?= (int) $contract['current_version'] ?></span>
                <span class="badge <?= e(status_badge_class((string) $contract['status'])) ?>"><?= e(humanize((string) $contract['status'])) ?></span>
            </div>
            <div class="cw-card-body cw-contract-doc">
                <h2 class="h5"><?= e($contract['title']) ?></h2>
                <p class="text-muted small">
                    Project <?= e((string) $contract['project_reference']) ?> ·
                    Client: <?= e($contract['client_name']) ?> ·
                    Freelancer: <?= e($contract['freelancer_name']) ?>
                </p>

                <div class="row g-4 mt-2">
                    <div class="col-md-6">
                        <dl>
                            <dt>Agreed amount</dt>
                            <dd class="h5"><?= e(money($contract['amount'], (string) $contract['currency'])) ?></dd>
                            <dt>Deadline</dt>
                            <dd><?= ($contract['deadline'] ?? null) ? e(date('d M Y', strtotime((string) $contract['deadline']))) : 'Flexible' ?></dd>
                            <dt>Created</dt>
                            <dd><?= e(dt((string) $contract['created_at'])) ?></dd>
                            <dt>Activated</dt>
                            <dd><?= e(dt($contract['activated_at'] ?? null)) ?></dd>
                        </dl>
                    </div>
                    <div class="col-md-6">
                        <dl>
                            <dt>Revision terms</dt><dd><?= e($contract['revision_terms']) ?></dd>
                            <dt>Payment arrangement</dt><dd><?= e($contract['payment_terms']) ?></dd>
                            <dt>Cancellation conditions</dt><dd><?= e($contract['cancellation_terms']) ?></dd>
                            <dt>Dispute process</dt><dd><?= e($contract['dispute_terms']) ?></dd>
                        </dl>
                    </div>
                </div>

                <dt>Scope</dt>
                <dd><?= e($contract['scope']) ?></dd>
                <dt>Deliverables</dt>
                <dd><?= e($contract['deliverables']) ?></dd>

                <?php if ($versionRow !== null): ?>
                    <div class="border-top pt-3 mt-3">
                        <h3 class="h6">Accepted terms record</h3>
                        <p class="small text-muted mb-1">
                            Version <?= (int) $versionRow['version'] ?> created
                            <?= e(dt((string) $versionRow['created_at'])) ?>
                            <?php if (!empty($versionRow['change_summary'])): ?>
                                — <?= e((string) $versionRow['change_summary']) ?>
                            <?php endif; ?>
                        </p>
                        <p class="small mb-1">SHA-256 of canonical terms:</p>
                        <div class="cw-hash mb-3"><?= e((string) $versionRow['terms_hash']) ?></div>

                        <?php if ($acceptances === []): ?>
                            <p class="small text-muted mb-0">No acceptance recorded for this version yet.</p>
                        <?php else: ?>
                            <ul class="list-unstyled small mb-0">
                                <?php foreach ($acceptances as $acceptance): ?>
                                    <li class="mb-1">
                                        <i class="bi bi-patch-check-fill text-success me-1"></i>
                                        <strong><?= e($acceptance['user_name']) ?></strong>
                                        accepted as <?= e(humanize((string) $acceptance['party_role'])) ?>
                                        on <?= e(dt((string) $acceptance['accepted_at'])) ?>
                                        <span class="text-muted">(IP <?= e((string) $acceptance['ip_address']) ?>)</span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>

                        <p class="small text-muted mt-3 mb-0">
                            A recorded acceptance documents what was agreed on this platform. Whether an agreement
                            is legally enforceable depends on applicable law and the circumstances of each case;
                            CircleWork Africa does not claim government certification or guaranteed legal protection.
                        </p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($awaiting): ?>
            <div class="cw-card mb-4">
                <div class="cw-card-header">Record your acceptance</div>
                <div class="cw-card-body">
                    <?php if ($hasAccepted): ?>
                        <p class="text-success mb-0"><i class="bi bi-check-circle-fill me-1"></i>You have accepted this contract version. Waiting for the other party.</p>
                    <?php else: ?>
                        <form method="post" action="<?= e(route('contracts.accept', ['id' => (int) $contract['id']])) ?>"
                              data-confirm="Accept contract version <?= (int) $contract['current_version'] ?>? Your account, the timestamp and a hash of these exact terms will be recorded.">
                            <?= csrf_field() ?>
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" id="confirm_acceptance" name="confirm_acceptance" value="1" required>
                                <label class="form-check-label" for="confirm_acceptance">
                                    I have read version <?= (int) $contract['current_version'] ?> of these terms and I accept them as the basis for this work.
                                </label>
                            </div>
                            <button type="submit" class="btn btn-primary">Record acceptance</button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <div class="cw-card mb-4">
            <div class="cw-card-header">Deliverables</div>
            <div class="cw-card-body">
                <?php if ($deliverables === []): ?>
                    <p class="text-muted mb-3">No deliverables submitted yet.</p>
                <?php else: ?>
                    <?php foreach ($deliverables as $deliverable): ?>
                        <div class="border rounded p-3 mb-3">
                            <div class="d-flex justify-content-between align-items-start gap-2 flex-wrap">
                                <div>
                                    <strong>Version <?= (int) $deliverable['version'] ?></strong>
                                    <div class="small text-muted">
                                        Submitted by <?= e($deliverable['submitted_by_name']) ?> on <?= e(dt((string) $deliverable['created_at'])) ?>
                                    </div>
                                </div>
                                <span class="badge <?= e(status_badge_class((string) $deliverable['status'])) ?>"><?= e(humanize((string) $deliverable['status'])) ?></span>
                            </div>

                            <p class="mt-2 mb-2" style="white-space: pre-wrap;"><?= e((string) $deliverable['message']) ?></p>

                            <?php $files = App\Models\DeliverableFile::forDeliverable((int) $deliverable['id']); ?>
                            <?php if ($files !== []): ?>
                                <ul class="list-unstyled small mb-2">
                                    <?php foreach ($files as $file): ?>
                                        <li>
                                            <i class="bi bi-paperclip"></i>
                                            <a href="<?= e(route('deliverable_files.download', ['id' => (int) $file['id']])) ?>">
                                                <?= e($file['original_name']) ?>
                                            </a>
                                            <span class="text-muted">(<?= e(App\Services\UploadService::humanSize((int) $file['size_bytes'])) ?>)</span>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>

                            <?php if (!empty($deliverable['review_note'])): ?>
                                <div class="cw-notice cw-notice--warning small">
                                    <strong>Review note:</strong> <?= e((string) $deliverable['review_note']) ?>
                                </div>
                            <?php endif; ?>

                            <?php if ((string) $deliverable['status'] === 'submitted' && $isClient): ?>
                                <div class="row g-2 mt-2">
                                    <div class="col-md-6">
                                        <form method="post" action="<?= e(route('deliverables.approve', ['id' => (int) $contract['id'], 'deliverableId' => (int) $deliverable['id']])) ?>"
                                              data-confirm="Approve this deliverable?">
                                            <?= csrf_field() ?>
                                            <button type="submit" class="btn btn-success w-100">Approve deliverable</button>
                                        </form>
                                    </div>
                                    <div class="col-md-6">
                                        <button class="btn btn-outline-warning w-100" type="button" data-bs-toggle="collapse"
                                                data-bs-target="#revision-<?= (int) $deliverable['id'] ?>">Request revision</button>
                                    </div>
                                </div>
                                <div class="collapse mt-2" id="revision-<?= (int) $deliverable['id'] ?>">
                                    <form method="post" action="<?= e(route('deliverables.revision', ['id' => (int) $contract['id'], 'deliverableId' => (int) $deliverable['id']])) ?>">
                                        <?= csrf_field() ?>
                                        <label class="form-label small" for="note-<?= (int) $deliverable['id'] ?>">What needs to change?</label>
                                        <textarea class="form-control" id="note-<?= (int) $deliverable['id'] ?>" name="note" rows="3" required minlength="10"></textarea>
                                        <button type="submit" class="btn btn-warning mt-2">Send revision request</button>
                                    </form>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>

                <?php if (!$isClient && in_array((string) $contract['status'], ['active', 'in_progress', 'revision_requested'], true)): ?>
                    <hr>
                    <h3 class="h6">Submit a deliverable</h3>
                    <form method="post" action="<?= e(route('deliverables.submit', ['id' => (int) $contract['id']])) ?>"
                          enctype="multipart/form-data" data-lock-submit data-loading-text="Submitting…">
                        <?= csrf_field() ?>
                        <div class="mb-3">
                            <label class="form-label" for="message">Description of what you are submitting</label>
                            <textarea class="form-control <?= isset($errors['message']) ? 'is-invalid' : '' ?>"
                                      id="message" name="message" rows="4" required minlength="10"></textarea>
                            <?php $field = 'message'; require CIRCLEWORK_ROOT . '/resources/views/components/field-error.php'; ?>
                        </div>
                        <div class="row g-2 mb-3">
                            <?php for ($i = 1; $i <= 3; $i++): ?>
                                <div class="col-md-4">
                                    <label class="form-label small" for="file_<?= $i ?>">Attachment <?= $i ?></label>
                                    <input type="file" class="form-control form-control-sm" id="file_<?= $i ?>" name="file_<?= $i ?>">
                                </div>
                            <?php endfor; ?>
                        </div>
                        <p class="form-text">
                            PDF, DOC/DOCX, XLS/XLSX, PPT/PPTX, TXT, CSV, ZIP or images. Executable and SVG files are rejected.
                        </p>
                        <?php if (isset($errors['files'])): ?>
                            <div class="alert alert-danger small"><?= e($errors['files']) ?></div>
                        <?php endif; ?>
                        <button type="submit" class="btn btn-primary">Submit deliverable</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>

        <div class="cw-card mb-4">
            <div class="cw-card-header d-flex justify-content-between align-items-center">
                <span>Payment records</span>
                <a class="small" href="<?= e(route('contracts.payments', ['id' => (int) $contract['id']])) ?>">Manage payments</a>
            </div>
            <div class="cw-card-body">
                <?php if ($payments === []): ?>
                    <p class="text-muted mb-0">No payments recorded yet. Payments are made directly between client and freelancer.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm cw-table mb-0">
                            <thead><tr><th>Reference</th><th>Amount</th><th>Method</th><th>Status</th><th>Date</th></tr></thead>
                            <tbody>
                            <?php foreach ($payments as $payment): ?>
                                <tr>
                                    <td><?= e($payment['reference']) ?></td>
                                    <td><?= e(money($payment['amount'], (string) $payment['currency'])) ?></td>
                                    <td class="small"><?= e(humanize((string) $payment['method'])) ?></td>
                                    <td><span class="badge <?= e(status_badge_class((string) $payment['status'])) ?>"><?= e(humanize((string) $payment['status'])) ?></span></td>
                                    <td class="small"><?= e(dt((string) $payment['created_at'], 'd M Y')) ?></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="cw-card">
            <div class="cw-card-header">Contract history</div>
            <div class="cw-card-body">
                <ul class="cw-timeline mb-0">
                    <?php foreach ($events as $event): ?>
                        <li>
                            <div class="fw-semibold small"><?= e(humanize((string) $event['event_type'])) ?></div>
                            <div class="small text-muted"><?= e((string) $event['description']) ?></div>
                            <div class="small text-muted">
                                <?= e($event['actor_name'] ?? 'System') ?> · <?= e(dt((string) $event['created_at'])) ?>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="cw-card mb-4">
            <div class="cw-card-header">Actions</div>
            <div class="cw-card-body d-grid gap-2">
                <a class="btn btn-outline-primary" href="<?= e(route('messages.show', ['id' => (int) $contract['id']])) ?>">
                    <i class="bi bi-chat-dots me-1"></i>Project messages
                </a>
                <a class="btn btn-outline-secondary" href="<?= e(route('contracts.payments', ['id' => (int) $contract['id']])) ?>">
                    <i class="bi bi-cash-coin me-1"></i>Payment records
                </a>
                <a class="btn btn-outline-secondary" href="<?= e(route('contracts.print', ['id' => (int) $contract['id']])) ?>">
                    <i class="bi bi-printer me-1"></i>Print-friendly copy
                </a>

                <?php if ($isClient && in_array((string) $contract['status'], ['active', 'in_progress', 'deliverable_submitted', 'revision_requested'], true)): ?>
                    <form method="post" action="<?= e(route('contracts.complete', ['id' => (int) $contract['id']])) ?>"
                          data-confirm="Mark this contract as completed? Reviews become available afterwards.">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn-success w-100">
                            <i class="bi bi-check2-circle me-1"></i>Mark contract completed
                        </button>
                    </form>
                <?php endif; ?>

                <?php if ($canReview): ?>
                    <button class="btn btn-navy" type="button" data-bs-toggle="collapse" data-bs-target="#reviewForm">
                        <i class="bi bi-star me-1"></i>Leave a review
                    </button>
                <?php elseif ($myReview !== null): ?>
                    <div class="cw-notice small">You reviewed this contract with <?= (int) $myReview['rating'] ?> star(s).</div>
                <?php endif; ?>
            </div>

            <?php if ($canReview): ?>
                <div class="collapse" id="reviewForm">
                    <div class="cw-card-body border-top">
                        <form method="post" action="<?= e(route('contracts.review', ['id' => (int) $contract['id']])) ?>">
                            <?= csrf_field() ?>
                            <div class="mb-3">
                                <label class="form-label" for="rating">Rating</label>
                                <select class="form-select" id="rating" name="rating" required>
                                    <option value="5">5 — Excellent</option>
                                    <option value="4">4 — Good</option>
                                    <option value="3">3 — Fair</option>
                                    <option value="2">2 — Poor</option>
                                    <option value="1">1 — Very poor</option>
                                </select>
                                <?php $field = 'rating'; require CIRCLEWORK_ROOT . '/resources/views/components/field-error.php'; ?>
                            </div>
                            <div class="mb-3">
                                <label class="form-label" for="comment">Comment</label>
                                <textarea class="form-control" id="comment" name="comment" rows="4" maxlength="2000"></textarea>
                                <?php $field = 'comment'; require CIRCLEWORK_ROOT . '/resources/views/components/field-error.php'; ?>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Publish review</button>
                        </form>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <div class="cw-card mb-4">
            <div class="cw-card-header">Counterparty</div>
            <div class="cw-card-body">
                <strong><?= e($counterpartyName) ?></strong>
                <p class="small text-muted mb-2">
                    <?= e($isClient ? (string) ($contract['freelancer_email'] ?? '') : (string) ($contract['client_email'] ?? '')) ?>
                </p>
                <a class="btn btn-sm btn-outline-primary w-100"
                   href="<?= e(route('freelancers.show', ['id' => $isClient ? (int) $contract['freelancer_id'] : (int) $contract['client_id']])) ?>">
                    View public profile
                </a>
                <a class="btn btn-sm btn-outline-danger w-100 mt-2"
                   href="<?= e(route('report.create', ['type' => 'user', 'target' => $isClient ? (int) $contract['freelancer_id'] : (int) $contract['client_id']])) ?>">
                    <i class="bi bi-flag me-1"></i>Report this user
                </a>
            </div>
        </div>

        <?php if ($canCancel): ?>
            <div class="cw-card mb-4">
                <div class="cw-card-header">Cancellation</div>
                <div class="cw-card-body">
                    <p class="small text-muted">
                        Cancelling records the reason and notifies the other party. Contracts, accepted terms and
                        payment records are never deleted.
                    </p>
                    <form method="post" action="<?= e(route('contracts.cancel', ['id' => (int) $contract['id']])) ?>"
                          data-confirm="Cancel this contract? This is recorded and the other party will be notified.">
                        <?= csrf_field() ?>
                        <label class="form-label small" for="reason">Reason</label>
                        <textarea class="form-control form-control-sm" id="reason" name="reason" rows="3" required></textarea>
                        <button type="submit" class="btn btn-outline-danger btn-sm w-100 mt-2">Request cancellation</button>
                    </form>
                </div>
            </div>
        <?php endif; ?>

        <div class="cw-card mb-4">
            <div class="cw-card-header">Disputes</div>
            <div class="cw-card-body">
                <?php if ($disputes === []): ?>
                    <p class="small text-muted">No disputes on this contract.</p>
                <?php else: ?>
                    <?php foreach ($disputes as $dispute): ?>
                        <div class="border-bottom pb-2 mb-2 small">
                            <strong><?= e($dispute['reference']) ?></strong>
                            <span class="badge <?= e(status_badge_class((string) $dispute['status'])) ?>"><?= e(humanize((string) $dispute['status'])) ?></span>
                            <div class="text-muted"><?= e(str_excerpt((string) $dispute['subject'], 80)) ?></div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>

                <button class="btn btn-outline-warning btn-sm w-100 mt-2" type="button"
                        data-bs-toggle="collapse" data-bs-target="#disputeForm">Open a dispute</button>

                <div class="collapse mt-3" id="disputeForm">
                    <form method="post" action="<?= e(route('contracts.dispute', ['id' => (int) $contract['id']])) ?>">
                        <?= csrf_field() ?>
                        <div class="mb-2">
                            <label class="form-label small" for="subject">Subject</label>
                            <input type="text" class="form-control form-control-sm" id="subject" name="subject" required maxlength="200">
                        </div>
                        <div class="mb-2">
                            <label class="form-label small" for="dispute-reason">What happened?</label>
                            <textarea class="form-control form-control-sm" id="dispute-reason" name="reason" rows="4" required minlength="20"></textarea>
                        </div>
                        <button type="submit" class="btn btn-warning btn-sm w-100">Record dispute</button>
                    </form>
                </div>

                <p class="small text-muted mt-3 mb-0">
                    CircleWork Africa reviews disputes and records an outcome. We cannot recover money transferred
                    directly between users.
                </p>
            </div>
        </div>

        <?php if ($awaiting): ?>
            <div class="cw-card">
                <div class="cw-card-header">Amend terms before activation</div>
                <div class="cw-card-body">
                    <p class="small text-muted">
                        Amending creates a new version. Accepted terms are never edited in place, and both parties
                        must accept the new version.
                    </p>
                    <form method="post" action="<?= e(route('contracts.amend', ['id' => (int) $contract['id']])) ?>"
                          data-confirm="Create a new contract version with these changes?">
                        <?= csrf_field() ?>
                        <div class="mb-2">
                            <label class="form-label small" for="amount">Amount</label>
                            <input type="number" step="0.01" min="1" class="form-control form-control-sm" id="amount" name="amount"
                                   value="<?= e((string) $contract['amount']) ?>">
                        </div>
                        <div class="mb-2">
                            <label class="form-label small" for="deadline">Deadline</label>
                            <input type="date" class="form-control form-control-sm" id="deadline" name="deadline"
                                   value="<?= e((string) ($contract['deadline'] ?? '')) ?>">
                        </div>
                        <div class="mb-2">
                            <label class="form-label small" for="scope">Scope</label>
                            <textarea class="form-control form-control-sm" id="scope" name="scope" rows="3"><?= e((string) $contract['scope']) ?></textarea>
                        </div>
                        <div class="mb-2">
                            <label class="form-label small" for="change_summary">Why is this changing?</label>
                            <input type="text" class="form-control form-control-sm" id="change_summary" name="change_summary" required minlength="5">
                        </div>
                        <button type="submit" class="btn btn-outline-primary btn-sm w-100">Create new version</button>
                    </form>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
