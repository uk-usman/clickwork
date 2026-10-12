<?php
/** @var array<int,array<string,mixed>> $projects */
/** @var array{page:int,per_page:int,total:int,last_page:int} $meta */
/** @var array<string,mixed> $filters */
/** @var array<int,array<string,mixed>> $categories */
/** @var array<int,array<string,mixed>> $skills */
$heading = 'Browse projects';
$lead = 'Approved, publicly visible projects from clients across Nigeria.';
$crumbs = [['label' => 'Home', 'url' => route('home')], ['label' => 'Browse projects']];
require CIRCLEWORK_ROOT . '/resources/views/components/page-header.php';
?>
<div class="container py-4">
    <div class="row g-4">
        <div class="col-lg-3">
            <div class="cw-card"><div class="cw-card-body">
                <h2 class="h6 mb-3"><i class="bi bi-funnel me-1"></i>Filters</h2>
                <form method="get" action="<?= e(route('projects.index')) ?>">
                    <div class="mb-3">
                        <label class="form-label" for="f-q">Keyword</label>
                        <input type="search" class="form-control" id="f-q" name="q" value="<?= e($filters['q'] ?? '') ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="f-category">Category</label>
                        <select class="form-select" id="f-category" name="category">
                            <option value="">All categories</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?= (int) $category['id'] ?>"
                                    <?= (int) ($filters['category_id'] ?? 0) === (int) $category['id'] ? 'selected' : '' ?>>
                                    <?= e($category['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="f-skill">Skill</label>
                        <select class="form-select" id="f-skill" name="skill">
                            <option value="">Any skill</option>
                            <?php foreach ($skills as $skill): ?>
                                <option value="<?= (int) $skill['id'] ?>"
                                    <?= (int) ($filters['skill_id'] ?? 0) === (int) $skill['id'] ? 'selected' : '' ?>>
                                    <?= e($skill['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="f-budget-type">Project type</label>
                        <select class="form-select" id="f-budget-type" name="budget_type">
                            <option value="">Any type</option>
                            <option value="fixed" <?= ($filters['budget_type'] ?? '') === 'fixed' ? 'selected' : '' ?>>Fixed price</option>
                            <option value="hourly" <?= ($filters['budget_type'] ?? '') === 'hourly' ? 'selected' : '' ?>>Hourly</option>
                        </select>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label" for="f-budget-min">Min ₦</label>
                            <input type="number" min="0" step="1000" class="form-control" id="f-budget-min" name="budget_min"
                                   value="<?= e($filters['budget_min'] ?? '') ?>">
                        </div>
                        <div class="col-6">
                            <label class="form-label" for="f-budget-max">Max ₦</label>
                            <input type="number" min="0" step="1000" class="form-control" id="f-budget-max" name="budget_max"
                                   value="<?= e($filters['budget_max'] ?? '') ?>">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="f-deadline">Deadline before</label>
                        <input type="date" class="form-control" id="f-deadline" name="deadline_before"
                               value="<?= e($filters['deadline_before'] ?? '') ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="f-sort">Sort by</label>
                        <select class="form-select" id="f-sort" name="sort">
                            <?php foreach (['newest' => 'Newest first', 'oldest' => 'Oldest first', 'budget_high' => 'Budget: high to low', 'budget_low' => 'Budget: low to high', 'deadline' => 'Soonest deadline'] as $value => $label): ?>
                                <option value="<?= e($value) ?>" <?= $sort === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Apply filters</button>
                    <a href="<?= e(route('projects.index')) ?>" class="btn btn-link w-100 mt-1">Clear all</a>
                </form>
            </div></div>
        </div>

        <div class="col-lg-9">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <p class="text-muted mb-0">
                    <strong><?= (int) $meta['total'] ?></strong> project(s) match your filters.
                </p>
            </div>

            <?php if ($projects === []): ?>
                <div class="cw-empty">
                    <i class="bi bi-search"></i>
                    <h2 class="h6 mt-3">No projects match your filters</h2>
                    <p class="text-muted mb-3">Try removing a filter, or check back soon — new projects are published regularly.</p>
                    <a href="<?= e(route('projects.index')) ?>" class="btn btn-outline-primary">Reset filters</a>
                </div>
            <?php else: ?>
                <div class="row g-4">
                    <?php foreach ($projects as $project): ?>
                        <div class="col-md-6">
                            <?php require CIRCLEWORK_ROOT . '/resources/views/components/project-card.php'; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php require CIRCLEWORK_ROOT . '/resources/views/components/pagination.php'; ?>
            <?php endif; ?>
        </div>
    </div>
</div>
