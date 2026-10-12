<?php /** @var array<int,array<string,mixed>> $categories */ ?>
<div class="row g-4">
    <div class="col-lg-7">
        <div class="cw-card">
            <div class="cw-card-header">Categories</div>
            <div class="table-responsive">
                <table class="table cw-table mb-0 align-middle">
                    <thead><tr><th>Name</th><th>Slug</th><th>Icon</th><th>Order</th><th>Active</th><th></th></tr></thead>
                    <tbody>
                    <?php foreach ($categories as $category): ?>
                        <tr>
                            <td>
                                <strong><?= e((string) $category['name']) ?></strong>
                                <?php if (!empty($category['description'])): ?>
                                    <div class="small text-muted"><?= e(str_excerpt((string) $category['description'], 70)) ?></div>
                                <?php endif; ?>
                            </td>
                            <td class="small"><?= e((string) $category['slug']) ?></td>
                            <td><i class="bi <?= e((string) $category['icon']) ?>"></i></td>
                            <td><?= (int) $category['sort_order'] ?></td>
                            <td>
                                <span class="badge <?= (int) $category['is_active'] === 1 ? 'text-bg-success' : 'text-bg-secondary' ?>">
                                    <?= (int) $category['is_active'] === 1 ? 'Active' : 'Hidden' ?>
                                </span>
                            </td>
                            <td class="text-end">
                                <form method="post" action="<?= e(route('admin.categories.toggle', ['id' => (int) $category['id']])) ?>">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="btn btn-sm btn-outline-secondary">
                                        <?= (int) $category['is_active'] === 1 ? 'Hide' : 'Show' ?>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="cw-card">
            <div class="cw-card-header">Add or update a category</div>
            <div class="cw-card-body">
                <form method="post" action="<?= e(route('admin.categories.save')) ?>" data-lock-submit data-loading-text="Saving…">
                    <?= csrf_field() ?>
                    <div class="mb-2">
                        <label class="form-label small" for="name">Name</label>
                        <input type="text" class="form-control form-control-sm" id="name" name="name" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small" for="slug">Slug (optional)</label>
                        <input type="text" class="form-control form-control-sm" id="slug" name="slug" placeholder="auto-generated from the name">
                    </div>
                    <div class="mb-2">
                        <label class="form-label small" for="description">Description</label>
                        <input type="text" class="form-control form-control-sm" id="description" name="description" maxlength="500">
                    </div>
                    <div class="row g-2">
                        <div class="col-6">
                            <label class="form-label small" for="icon">Bootstrap icon class</label>
                            <input type="text" class="form-control form-control-sm" id="icon" name="icon" value="bi-briefcase">
                        </div>
                        <div class="col-6">
                            <label class="form-label small" for="sort_order">Sort order</label>
                            <input type="number" class="form-control form-control-sm" id="sort_order" name="sort_order" value="0">
                        </div>
                    </div>
                    <div class="form-check my-3">
                        <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" checked>
                        <label class="form-check-label small" for="is_active">Active</label>
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm w-100">Create category</button>
                    <p class="form-text mb-0 mt-2">
                        To update an existing category, add its ID in the form field below before submitting.
                    </p>
                    <input type="number" class="form-control form-control-sm mt-2" name="category_id" placeholder="Category ID to update (optional)" min="0">
                </form>
            </div>
        </div>
    </div>
</div>
