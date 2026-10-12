<?php
/** @var array<int,array<string,mixed>> $reports */
/** @var array{page:int,last_page:int,total:int} $meta */
/** @var array<string,mixed> $filters */
$action = route('admin.reports');
$fields = [
    'status' => ['label' => 'Status', 'type' => 'select', 'value' => $filters['status'] ?? '', 'options' => ['open' => 'Open', 'in_review' => 'In review', 'resolved' => 'Resolved', 'dismissed' => 'Dismissed']],
    'type' => ['label' => 'Type', 'type' => 'select', 'value' => $filters['type'] ?? '', 'options' => ['user' => 'User', 'project' => 'Project', 'review' => 'Review', 'message' => 'Message', 'payment' => 'Payment', 'contract' => 'Contract']],
];
?>
<?php require CIRCLEWORK_ROOT . '/resources/views/components/admin-filter-form.php'; ?>
<div class="cw-card">
    <div class="cw-card-body">
        <?php foreach ($reports as $report): ?>
            <div class="border rounded p-3 mb-3">
                <div class="d-flex justify-content-between align-items-start gap-2 flex-wrap">
                    <div>
                        <span class="badge text-bg-secondary"><?= e(humanize((string) $report['reportable_type'])) ?> #<?= (int) $report['reportable_id'] ?></span>
                        <span class="badge <?= e(status_badge_class((string) $report['status'])) ?>"><?= e(humanize((string) $report['status'])) ?></span>
                        <div class="small text-muted mt-1">
                            Reported by <?= e((string) $report['reporter_name']) ?> (<?= e((string) $report['reporter_email']) ?>)
                            on <?= e(dt((string) $report['created_at'])) ?>
                        </div>
                    </div>
                    <div class="small text-muted">Reason: <?= e(humanize((string) $report['reason'])) ?></div>
                </div>

                <?php if (!empty($report['details'])): ?>
                    <p class="mt-2 mb-2" style="white-space: pre-wrap;"><?= e((string) $report['details']) ?></p>
                <?php endif; ?>

                <?php if (!empty($report['resolution_note'])): ?>
                    <p class="small text-muted mb-2"><strong>Resolution:</strong> <?= e((string) $report['resolution_note']) ?></p>
                <?php endif; ?>

                <form method="post" action="<?= e(route('admin.reports.resolve', ['id' => (int) $report['id']])) ?>"
                      class="row g-2 align-items-end" data-confirm="Update this report?">
                    <?= csrf_field() ?>
                    <div class="col-md-3">
                        <label class="form-label small" for="rstatus-<?= (int) $report['id'] ?>">Status</label>
                        <select class="form-select form-select-sm" id="rstatus-<?= (int) $report['id'] ?>" name="status">
                            <?php foreach (['open', 'in_review', 'resolved', 'dismissed'] as $option): ?>
                                <option value="<?= e($option) ?>" <?= (string) $report['status'] === $option ? 'selected' : '' ?>><?= e(humanize($option)) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small" for="rnote-<?= (int) $report['id'] ?>">Resolution note</label>
                        <input type="text" class="form-control form-control-sm" id="rnote-<?= (int) $report['id'] ?>" name="note" maxlength="1000">
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-sm btn-primary w-100">Update report</button>
                    </div>
                </form>
            </div>
        <?php endforeach; ?>
        <?php if ($reports === []): ?>
            <p class="text-muted mb-0">No reports found.</p>
        <?php endif; ?>
    </div>
</div>
<?php require CIRCLEWORK_ROOT . '/resources/views/components/pagination.php'; ?>
