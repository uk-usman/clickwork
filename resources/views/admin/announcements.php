<?php
/** @var array<int,array<string,mixed>> $announcements */
/** @var array{page:int,last_page:int,total:int} $meta */
?>
<div class="row g-4">
    <div class="col-lg-7">
        <div class="cw-card">
            <div class="cw-card-header">Published and draft announcements</div>
            <div class="cw-card-body">
                <?php foreach ($announcements as $announcement): ?>
                    <div class="border rounded p-3 mb-3">
                        <div class="d-flex justify-content-between align-items-start gap-2">
                            <div>
                                <strong><?= e((string) $announcement['title']) ?></strong>
                                <span class="badge text-bg-secondary"><?= e(humanize((string) $announcement['audience'])) ?></span>
                                <?php if ((int) $announcement['is_published'] === 1): ?>
                                    <span class="badge text-bg-success">Published</span>
                                <?php else: ?>
                                    <span class="badge text-bg-secondary">Draft</span>
                                <?php endif; ?>
                            </div>
                            <div class="small text-muted text-end">
                                <?= e((string) $announcement['created_by_name']) ?><br>
                                <?= e(dt((string) $announcement['created_at'], 'd M Y')) ?>
                            </div>
                        </div>
                        <p class="small text-muted mt-2 mb-0" style="white-space: pre-wrap;"><?= e(str_excerpt((string) $announcement['body'], 320)) ?></p>
                    </div>
                <?php endforeach; ?>
                <?php if ($announcements === []): ?>
                    <p class="text-muted mb-0">No announcements yet.</p>
                <?php endif; ?>
            </div>
        </div>
        <?php require CIRCLEWORK_ROOT . '/resources/views/components/pagination.php'; ?>
    </div>

    <div class="col-lg-5">
        <div class="cw-card">
            <div class="cw-card-header">New announcement</div>
            <div class="cw-card-body">
                <form method="post" action="<?= e(route('admin.announcements.store')) ?>" data-lock-submit data-loading-text="Saving…">
                    <?= csrf_field() ?>
                    <div class="mb-2">
                        <label class="form-label small" for="title">Title</label>
                        <input type="text" class="form-control form-control-sm" id="title" name="title" required maxlength="180">
                    </div>
                    <div class="mb-2">
                        <label class="form-label small" for="body">Body</label>
                        <textarea class="form-control form-control-sm" id="body" name="body" rows="6" required></textarea>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small" for="audience">Audience</label>
                        <select class="form-select form-select-sm" id="audience" name="audience">
                            <option value="all">Everyone</option>
                            <option value="clients">Clients</option>
                            <option value="freelancers">Freelancers</option>
                            <option value="admins">Administrators only</option>
                        </select>
                    </div>
                    <div class="form-check my-3">
                        <input class="form-check-input" type="checkbox" id="is_published" name="is_published" value="1">
                        <label class="form-check-label small" for="is_published">
                            Publish now and notify affected users in-app
                        </label>
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm w-100">Save announcement</button>
                </form>
            </div>
        </div>
    </div>
</div>
