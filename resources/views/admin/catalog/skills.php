<?php
/** @var array<int,array<string,mixed>> $skills */
/** @var array<int,array<string,mixed>> $categories */
?>
<div class="row g-4">
    <div class="col-lg-7">
        <div class="cw-card">
            <div class="cw-card-header">Skills (<?= count($skills) ?>)</div>
            <div class="table-responsive" style="max-height:640px;overflow-y:auto;">
                <table class="table cw-table mb-0 align-middle">
                    <thead><tr><th>Name</th><th>Category</th><th>Active</th><th></th></tr></thead>
                    <tbody>
                    <?php foreach ($skills as $skill): ?>
                        <tr>
                            <td><?= e((string) $skill['name']) ?></td>
                            <td class="small"><?= e((string) ($skill['category_name'] ?? '—')) ?></td>
                            <td>
                                <span class="badge <?= (int) $skill['is_active'] === 1 ? 'text-bg-success' : 'text-bg-secondary' ?>">
                                    <?= (int) $skill['is_active'] === 1 ? 'Active' : 'Hidden' ?>
                                </span>
                            </td>
                            <td class="text-end">
                                <form method="post" action="<?= e(route('admin.skills.toggle', ['id' => (int) $skill['id']])) ?>">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="btn btn-sm btn-outline-secondary">
                                        <?= (int) $skill['is_active'] === 1 ? 'Hide' : 'Show' ?>
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
            <div class="cw-card-header">Add or update a skill</div>
            <div class="cw-card-body">
                <form method="post" action="<?= e(route('admin.skills.save')) ?>" data-lock-submit data-loading-text="Saving…">
                    <?= csrf_field() ?>
                    <div class="mb-2">
                        <label class="form-label small" for="name">Skill name</label>
                        <input type="text" class="form-control form-control-sm" id="name" name="name" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small" for="category_id">Category</label>
                        <select class="form-select form-select-sm" id="category_id" name="category_id">
                            <option value="">No category</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?= (int) $category['id'] ?>"><?= e((string) $category['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-check my-3">
                        <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" checked>
                        <label class="form-check-label small" for="is_active">Active</label>
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm w-100">Create skill</button>
                    <input type="number" class="form-control form-control-sm mt-2" name="skill_id" placeholder="Skill ID to update (optional)" min="0">
                </form>
            </div>
        </div>
    </div>
</div>
