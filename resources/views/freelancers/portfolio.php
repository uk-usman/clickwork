<?php
/** @var array<int,array<string,mixed>> $items */
/** @var array<int,array<string,mixed>> $categories */
/** @var array<string,int> $limits */
$atLimit = count($items) >= (int) $limits['portfolio_items'];
?>
<div class="row g-4">
    <div class="col-lg-8">
        <div class="cw-card mb-4">
            <div class="cw-card-header d-flex justify-content-between align-items-center">
                <span>Portfolio items (<?= count($items) ?> / <?= (int) $limits['portfolio_items'] ?>)</span>
                <?php if ($atLimit): ?>
                    <a class="small" href="<?= e(route('subscriptions.index')) ?>">Upgrade for more</a>
                <?php endif; ?>
            </div>
            <?php if ($items === []): ?>
                <div class="cw-card-body text-center py-4">
                    <i class="bi bi-images fs-2 text-muted"></i>
                    <p class="text-muted mt-2 mb-0">No portfolio items yet. Add work you are proud of to build client confidence.</p>
                </div>
            <?php else: ?>
                <div class="cw-card-body">
                    <div class="row g-3">
                        <?php foreach ($items as $item): ?>
                            <div class="col-md-6">
                                <div class="border rounded h-100 d-flex flex-column">
                                    <?php $image = portfolio_image_url($item['image_path'] ?? null); ?>
                                    <?php if ($image !== null): ?>
                                        <img src="<?= e($image) ?>" alt="" class="rounded-top" style="height:140px;object-fit:cover;width:100%;">
                                    <?php endif; ?>
                                    <div class="p-3 flex-grow-1 d-flex flex-column">
                                        <div class="d-flex justify-content-between align-items-start gap-2">
                                            <strong><?= e($item['title']) ?></strong>
                                            <span class="badge <?= (int) $item['is_visible'] === 1 ? 'text-bg-success' : 'text-bg-secondary' ?>">
                                                <?= (int) $item['is_visible'] === 1 ? 'Visible' : 'Hidden' ?>
                                            </span>
                                        </div>
                                        <?php if (!empty($item['category_name'])): ?>
                                            <div class="small text-muted"><?= e($item['category_name']) ?></div>
                                        <?php endif; ?>
                                        <p class="small text-muted flex-grow-1 mt-2"><?= e(str_excerpt((string) ($item['description'] ?? ''), 120)) ?></p>
                                        <?php if (!empty($item['project_url'])): ?>
                                            <a class="small" href="<?= e($item['project_url']) ?>" rel="nofollow noopener" target="_blank">
                                                <i class="bi bi-box-arrow-up-right"></i> View project
                                            </a>
                                        <?php endif; ?>
                                        <div class="d-flex gap-2 mt-3">
                                            <form method="post" class="flex-grow-1" action="<?= e(route('freelancer.portfolio.visibility', ['id' => (int) $item['id']])) ?>">
                                                <?= csrf_field() ?>
                                                <button type="submit" class="btn btn-sm btn-outline-secondary w-100">
                                                    <?= (int) $item['is_visible'] === 1 ? 'Hide' : 'Show' ?>
                                                </button>
                                            </form>
                                            <form method="post" class="flex-grow-1"
                                                  action="<?= e(route('freelancer.portfolio.delete', ['id' => (int) $item['id']])) ?>"
                                                  data-confirm="Remove this portfolio item permanently?">
                                                <?= csrf_field() ?>
                                                <button type="submit" class="btn btn-sm btn-outline-danger w-100">Remove</button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="cw-card">
            <div class="cw-card-header">Add a portfolio item</div>
            <div class="cw-card-body">
                <?php if ($atLimit): ?>
                    <div class="cw-notice cw-notice--warning">
                        Your current plan allows <?= (int) $limits['portfolio_items'] ?> portfolio items.
                        <a href="<?= e(route('subscriptions.index')) ?>">Compare plans</a> to add more.
                    </div>
                <?php else: ?>
                    <form method="post" action="<?= e(route('freelancer.portfolio.store')) ?>" enctype="multipart/form-data"
                          data-lock-submit data-loading-text="Saving…">
                        <?= csrf_field() ?>
                        <div class="mb-3">
                            <label class="form-label" for="title">Title</label>
                            <input type="text" class="form-control <?= isset($errors['title']) ? 'is-invalid' : '' ?>"
                                   id="title" name="title" value="<?= old('title') ?>" required maxlength="180">
                            <?php $field = 'title'; require CIRCLEWORK_ROOT . '/resources/views/components/field-error.php'; ?>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="description">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="4"><?= old('description') ?></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="project_url">Project link (optional)</label>
                            <input type="url" class="form-control <?= isset($errors['project_url']) ? 'is-invalid' : '' ?>"
                                   id="project_url" name="project_url" value="<?= old('project_url') ?>" placeholder="https://">
                            <?php $field = 'project_url'; require CIRCLEWORK_ROOT . '/resources/views/components/field-error.php'; ?>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="category_id">Category</label>
                            <select class="form-select" id="category_id" name="category_id">
                                <option value="">Not specified</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?= (int) $category['id'] ?>"><?= e($category['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="image">Image (optional)</label>
                            <input type="file" class="form-control <?= isset($errors['image']) ? 'is-invalid' : '' ?>"
                                   id="image" name="image" accept="image/jpeg,image/png,image/gif,image/webp">
                            <div class="form-text">JPG, PNG, GIF or WebP. Maximum 2 MB. SVG is not accepted.</div>
                            <?php $field = 'image'; require CIRCLEWORK_ROOT . '/resources/views/components/field-error.php'; ?>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Add portfolio item</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
