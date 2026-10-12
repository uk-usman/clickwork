<?php
/** @var array<int,array<string,mixed>> $reviews */
/** @var array{page:int,last_page:int,total:int} $meta */
/** @var array<string,mixed> $filters */
$action = route('admin.reviews');
$fields = ['status' => ['label' => 'Status', 'type' => 'select', 'value' => $filters['status'] ?? '', 'options' => ['published' => 'Published', 'hidden' => 'Hidden', 'removed' => 'Removed']]];
?>
<?php require CIRCLEWORK_ROOT . '/resources/views/components/admin-filter-form.php'; ?>
<div class="cw-card">
    <div class="cw-card-body">
        <?php foreach ($reviews as $review): ?>
            <div class="border rounded p-3 mb-3">
                <div class="d-flex justify-content-between align-items-start gap-2 flex-wrap">
                    <div>
                        <strong><?= e((string) $review['reviewer_name']) ?></strong>
                        <span class="text-muted small">reviewed</span>
                        <strong><?= e((string) $review['reviewee_name']) ?></strong>
                        <div class="small text-muted">
                            Contract <?= e((string) $review['contract_reference']) ?> · <?= e(dt((string) $review['created_at'], 'd M Y')) ?>
                        </div>
                    </div>
                    <div class="text-end">
                        <div class="text-warning">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <i class="bi <?= $i <= (int) $review['rating'] ? 'bi-star-fill' : 'bi-star' ?>"></i>
                            <?php endfor; ?>
                        </div>
                        <span class="badge <?= e(status_badge_class((string) $review['status'])) ?>"><?= e(humanize((string) $review['status'])) ?></span>
                    </div>
                </div>

                <?php if (!empty($review['comment'])): ?>
                    <p class="mt-2 mb-2"><?= e((string) $review['comment']) ?></p>
                <?php endif; ?>

                <?php if (!empty($review['moderation_note'])): ?>
                    <p class="small text-muted mb-2"><strong>Moderation note:</strong> <?= e((string) $review['moderation_note']) ?></p>
                <?php endif; ?>

                <form method="post" action="<?= e(route('admin.reviews.moderate', ['id' => (int) $review['id']])) ?>"
                      class="row g-2 align-items-end" data-confirm="Apply this moderation action to the review?">
                    <?= csrf_field() ?>
                    <div class="col-md-3">
                        <label class="form-label small" for="status-<?= (int) $review['id'] ?>">Status</label>
                        <select class="form-select form-select-sm" id="status-<?= (int) $review['id'] ?>" name="status">
                            <?php foreach (['published', 'hidden', 'removed'] as $option): ?>
                                <option value="<?= e($option) ?>" <?= (string) $review['status'] === $option ? 'selected' : '' ?>><?= e(humanize($option)) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small" for="note-<?= (int) $review['id'] ?>">Moderation note</label>
                        <input type="text" class="form-control form-control-sm" id="note-<?= (int) $review['id'] ?>" name="note" maxlength="500">
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-sm btn-primary w-100">Apply</button>
                    </div>
                </form>
            </div>
        <?php endforeach; ?>
        <?php if ($reviews === []): ?>
            <p class="text-muted mb-0">No reviews found.</p>
        <?php endif; ?>
    </div>
</div>
<?php require CIRCLEWORK_ROOT . '/resources/views/components/pagination.php'; ?>
