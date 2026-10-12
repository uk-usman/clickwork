<?php
/** @var array<string,mixed>|null $project */
/** @var array<int,array<string,mixed>> $categories */
/** @var array<int,array<string,mixed>> $skills */
/** @var array<int,int> $selectedSkills */
$isEdit = $project !== null;
$action = $isEdit
    ? route('client.projects.update', ['id' => (int) $project['id']])
    : route('client.projects.store');
$value = static function (string $field, string $default = '') use ($project): string {
    $old = old($field);
    if ($old !== '') {
        return $old;
    }
    if ($project === null) {
        return $default;
    }
    return (string) ($project[$field] ?? $default);
};
?>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="cw-card">
            <div class="cw-card-header">Project details</div>
            <div class="cw-card-body">
                <form method="post" action="<?= e($action) ?>" data-lock-submit data-loading-text="Saving…">
                    <?= csrf_field() ?>

                    <div class="mb-3">
                        <label class="form-label" for="title">Project title</label>
                        <input type="text" class="form-control <?= isset($errors['title']) ? 'is-invalid' : '' ?>"
                               id="title" name="title" value="<?= e($value('title')) ?>" required maxlength="200"
                               placeholder="e.g. Build a responsive business website for a Kano bakery">
                        <?php $field = 'title'; require CIRCLEWORK_ROOT . '/resources/views/components/field-error.php'; ?>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label" for="category_id">Category</label>
                            <select class="form-select <?= isset($errors['category_id']) ? 'is-invalid' : '' ?>" id="category_id" name="category_id" required>
                                <option value="">Choose a category…</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?= (int) $category['id'] ?>"
                                        <?= (int) $value('category_id', '0') === (int) $category['id'] ? 'selected' : '' ?>>
                                        <?= e($category['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php $field = 'category_id'; require CIRCLEWORK_ROOT . '/resources/views/components/field-error.php'; ?>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="visibility">Visibility</label>
                            <select class="form-select" id="visibility" name="visibility">
                                <option value="public" <?= $value('visibility', 'public') === 'public' ? 'selected' : '' ?>>Public — visible to all approved freelancers</option>
                                <option value="private" <?= $value('visibility', 'public') === 'private' ? 'selected' : '' ?>>Private — invite-only (not listed publicly)</option>
                            </select>
                            <div class="form-text">Private projects are approved by moderation but are not shown in public listings.</div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="description">Description</label>
                        <textarea class="form-control <?= isset($errors['description']) ? 'is-invalid' : '' ?>"
                                  id="description" name="description" rows="8" required
                                  data-counter="#description-count"
                                  placeholder="Describe the work, the context, and what success looks like. Be specific — clear briefs attract better proposals."><?= e($value('description')) ?></textarea>
                        <div class="form-text d-flex justify-content-between">
                            <span>Minimum 40 characters.</span>
                            <span id="description-count"></span>
                        </div>
                        <?php $field = 'description'; require CIRCLEWORK_ROOT . '/resources/views/components/field-error.php'; ?>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="required_skills_text">Required skills</label>
                        <input type="text" class="form-control" id="required_skills_text" name="required_skills_text"
                               value="<?= e($value('required_skills_text')) ?>" maxlength="500"
                               placeholder="e.g. PHP, MySQL, Bootstrap, responsive design">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Tag matching skills</label>
                        <div class="border rounded p-2" style="max-height:200px;overflow-y:auto;">
                            <div class="row g-2">
                                <?php foreach ($skills as $skill): ?>
                                    <?php $skillId = (int) $skill['id']; ?>
                                    <div class="col-md-4">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="skill_ids[]"
                                                   id="pskill-<?= $skillId ?>" value="<?= $skillId ?>"
                                                   <?= in_array($skillId, $selectedSkills, true) ? 'checked' : '' ?>>
                                            <label class="form-check-label small" for="pskill-<?= $skillId ?>"><?= e($skill['name']) ?></label>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label" for="budget_type">Budget type</label>
                            <select class="form-select" id="budget_type" name="budget_type"
                                    data-toggles="#fixed-budget-fields" data-toggles-value="fixed">
                                <option value="fixed" <?= $value('budget_type', 'fixed') === 'fixed' ? 'selected' : '' ?>>Fixed price</option>
                                <option value="hourly" <?= $value('budget_type', 'fixed') === 'hourly' ? 'selected' : '' ?>>Hourly</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="currency">Currency</label>
                            <select class="form-select" id="currency" name="currency">
                                <?php foreach (['NGN' => 'NGN — Nigerian Naira (₦)', 'USD' => 'USD — US Dollar', 'GBP' => 'GBP — Pound Sterling', 'EUR' => 'EUR — Euro'] as $code => $label): ?>
                                    <option value="<?= e($code) ?>"><?= e($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="deadline">Deadline</label>
                            <input type="date" class="form-control <?= isset($errors['deadline']) ? 'is-invalid' : '' ?>"
                                   id="deadline" name="deadline" value="<?= e($value('deadline')) ?>" min="<?= date('Y-m-d') ?>">
                            <?php $field = 'deadline'; require CIRCLEWORK_ROOT . '/resources/views/components/field-error.php'; ?>
                        </div>
                    </div>

                    <div class="row g-3 mb-3" id="fixed-budget-fields">
                        <div class="col-md-6">
                            <label class="form-label" for="budget_min">Minimum budget</label>
                            <input type="number" step="0.01" min="1" class="form-control <?= isset($errors['budget_min']) ? 'is-invalid' : '' ?>"
                                   id="budget_min" name="budget_min" value="<?= e($value('budget_min')) ?>">
                            <?php $field = 'budget_min'; require CIRCLEWORK_ROOT . '/resources/views/components/field-error.php'; ?>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="budget_max">Maximum budget</label>
                            <input type="number" step="0.01" min="1" class="form-control <?= isset($errors['budget_max']) ? 'is-invalid' : '' ?>"
                                   id="budget_max" name="budget_max" value="<?= e($value('budget_max')) ?>">
                            <div class="form-text">The maximum cannot be lower than the minimum.</div>
                            <?php $field = 'budget_max'; require CIRCLEWORK_ROOT . '/resources/views/components/field-error.php'; ?>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="expected_deliverables">Expected deliverables</label>
                        <textarea class="form-control" id="expected_deliverables" name="expected_deliverables" rows="4"
                                  placeholder="List what you expect to receive, e.g. source files, documentation, a deployed site."><?= e($value('expected_deliverables')) ?></textarea>
                    </div>

                    <div class="d-flex flex-wrap gap-2">
                        <button type="submit" name="action" value="submit" class="btn btn-primary">
                            <i class="bi bi-send-check me-1"></i>Submit for moderation
                        </button>
                        <button type="submit" name="action" value="draft" class="btn btn-outline-secondary">
                            <i class="bi bi-save me-1"></i>Save as draft
                        </button>
                        <a href="<?= e(route('client.projects')) ?>" class="btn btn-link">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="cw-card">
            <div class="cw-card-header">Writing a good brief</div>
            <div class="cw-card-body small text-muted">
                <ul class="mb-0 ps-3">
                    <li class="mb-2">State the outcome, not just the tasks. “A 5-page site with a booking form” beats “some web work”.</li>
                    <li class="mb-2">Set a realistic budget range. Wide ranges attract more proposals.</li>
                    <li class="mb-2">List the deliverables you expect to receive, including source files.</li>
                    <li class="mb-2">Say how you will communicate and how quickly you will review work.</li>
                    <li>Remember that every project is reviewed by an administrator before it becomes public.</li>
                </ul>
            </div>
        </div>
    </div>
</div>
