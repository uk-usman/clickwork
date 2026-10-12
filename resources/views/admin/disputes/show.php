<?php
/** @var array<string,mixed> $dispute */
/** @var array<int,array<string,mixed>> $messages */
?>
<div class="row g-4">
    <div class="col-lg-7">
        <div class="cw-card mb-4">
            <div class="cw-card-header d-flex justify-content-between align-items-center">
                <span>Dispute <?= e((string) $dispute['reference']) ?></span>
                <span class="badge <?= e(status_badge_class((string) $dispute['status'])) ?>"><?= e(humanize((string) $dispute['status'])) ?></span>
            </div>
            <div class="cw-card-body">
                <h2 class="h5"><?= e((string) $dispute['subject']) ?></h2>
                <p class="text-muted small">
                    Contract <?= e((string) $dispute['contract_reference']) ?> ·
                    project <?= e((string) $dispute['project_title']) ?> ·
                    opened by <?= e((string) $dispute['opened_by_name']) ?> on <?= e(dt((string) $dispute['created_at'])) ?>
                </p>
                <div style="white-space: pre-wrap;"><?= e((string) $dispute['reason']) ?></div>

                <?php if (!empty($dispute['resolution_note'])): ?>
                    <div class="alert alert-success small mt-3 mb-0">
                        <strong>Resolution:</strong> <?= e((string) $dispute['resolution_note']) ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="cw-card">
            <div class="cw-card-header">Dispute thread</div>
            <div class="cw-card-body">
                <?php if ($messages === []): ?>
                    <p class="text-muted">No messages on this dispute yet.</p>
                <?php else: ?>
                    <?php foreach ($messages as $message): ?>
                        <div class="border rounded p-3 mb-2 <?= (int) $message['is_internal'] === 1 ? 'bg-light' : '' ?>">
                            <div class="d-flex justify-content-between align-items-start gap-2">
                                <strong class="small"><?= e((string) $message['sender_name']) ?></strong>
                                <span class="small text-muted">
                                    <?php if ((int) $message['is_internal'] === 1): ?>
                                        <span class="badge text-bg-secondary">Internal</span>
                                    <?php endif; ?>
                                    <?= e(dt((string) $message['created_at'])) ?>
                                </span>
                            </div>
                            <div class="mt-2" style="white-space: pre-wrap;"><?= e((string) $message['body']) ?></div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>

                <hr>
                <form method="post" action="<?= e(route('admin.disputes.reply', ['id' => (int) $dispute['id']])) ?>">
                    <?= csrf_field() ?>
                    <label class="form-label small" for="body">Add a message</label>
                    <textarea class="form-control" id="body" name="body" rows="3" required maxlength="2000"></textarea>
                    <div class="form-check my-2">
                        <input class="form-check-input" type="checkbox" id="is_internal" name="is_internal" value="1">
                        <label class="form-check-label small" for="is_internal">Internal note (not shown to the parties)</label>
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm">Post message</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="cw-card mb-4">
            <div class="cw-card-header">Parties</div>
            <div class="cw-card-body small">
                <dl class="row g-2 mb-0">
                    <dt class="col-6 text-muted fw-normal">Client</dt>
                    <dd class="col-6 text-end">
                        <a href="<?= e(route('admin.users.show', ['id' => (int) $dispute['client_id']])) ?>">User #<?= (int) $dispute['client_id'] ?></a>
                    </dd>
                    <dt class="col-6 text-muted fw-normal">Freelancer</dt>
                    <dd class="col-6 text-end">
                        <a href="<?= e(route('admin.users.show', ['id' => (int) $dispute['freelancer_id']])) ?>">User #<?= (int) $dispute['freelancer_id'] ?></a>
                    </dd>
                    <dt class="col-6 text-muted fw-normal">Contract</dt>
                    <dd class="col-6 text-end">
                        <a href="<?= e(route('admin.contracts.show', ['id' => (int) $dispute['contract_id']])) ?>">Open contract</a>
                    </dd>
                </dl>
            </div>
        </div>

        <div class="cw-card">
            <div class="cw-card-header">Record an outcome</div>
            <div class="cw-card-body">
                <form method="post" action="<?= e(route('admin.disputes.resolve', ['id' => (int) $dispute['id']])) ?>"
                      data-confirm="Record this dispute outcome?">
                    <?= csrf_field() ?>
                    <div class="mb-3">
                        <label class="form-label small" for="status">Status</label>
                        <select class="form-select form-select-sm" id="status" name="status" required>
                            <?php foreach (['open', 'in_review', 'resolved', 'closed'] as $option): ?>
                                <option value="<?= e($option) ?>" <?= (string) $dispute['status'] === $option ? 'selected' : '' ?>><?= e(humanize($option)) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small" for="note">Resolution note</label>
                        <textarea class="form-control form-control-sm" id="note" name="note" rows="4"></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm w-100">Save outcome</button>
                </form>
                <p class="small text-muted mt-3 mb-0">
                    CircleWork Africa records and reviews disputes. We cannot recover money transferred directly
                    between users.
                </p>
            </div>
        </div>
    </div>
</div>
