<?php
/** @var array<int,array<string,mixed>> $freelancers */
/** @var array{page:int,per_page:int,total:int,last_page:int} $meta */
/** @var array<string,mixed> $filters */
/** @var array<int,array<string,mixed>> $skills */
$heading = 'Find freelancers';
$lead = 'Public freelancer profiles. Only freelancers who chose a public profile appear here.';
$crumbs = [['label' => 'Home', 'url' => route('home')], ['label' => 'Find freelancers']];
require CIRCLEWORK_ROOT . '/resources/views/components/page-header.php';
?>
<div class="container py-4">
    <div class="cw-card mb-4"><div class="cw-card-body">
        <form method="get" action="<?= e(route('freelancers.index')) ?>" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label" for="q">Keyword</label>
                <input type="search" class="form-control" id="q" name="q" value="<?= e($filters['q'] ?? '') ?>"
                       placeholder="Name, title or skill">
            </div>
            <div class="col-md-3">
                <label class="form-label" for="skill">Skill</label>
                <select class="form-select" id="skill" name="skill">
                    <option value="">Any skill</option>
                    <?php foreach ($skills as $skill): ?>
                        <option value="<?= (int) $skill['id'] ?>" <?= (int) ($filters['skill_id'] ?? 0) === (int) $skill['id'] ? 'selected' : '' ?>>
                            <?= e($skill['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label" for="state">State</label>
                <select class="form-select" id="state" name="state">
                    <option value="">Any state</option>
                    <?php foreach (App\Controllers\AuthController::nigerianStates() as $state): ?>
                        <option value="<?= e($state) ?>" <?= ($filters['state'] ?? '') === $state ? 'selected' : '' ?>><?= e($state) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label" for="city">City</label>
                <input type="text" class="form-control" id="city" name="city" value="<?= e($filters['city'] ?? '') ?>">
            </div>
            <div class="col-md-1">
                <button type="submit" class="btn btn-primary w-100" aria-label="Search"><i class="bi bi-search"></i></button>
            </div>
        </form>
    </div></div>

    <p class="text-muted"><strong><?= (int) $meta['total'] ?></strong> freelancer profile(s) found.</p>

    <?php if ($freelancers === []): ?>
        <div class="cw-empty">
            <i class="bi bi-people"></i>
            <h2 class="h6 mt-3">No freelancer profiles match your search</h2>
            <p class="text-muted mb-3">Try a broader search, or browse projects instead.</p>
            <a href="<?= e(route('freelancers.index')) ?>" class="btn btn-outline-primary">Reset search</a>
        </div>
    <?php else: ?>
        <div class="row g-4">
            <?php foreach ($freelancers as $freelancer): ?>
                <div class="col-md-6 col-lg-4">
                    <?php require CIRCLEWORK_ROOT . '/resources/views/components/freelancer-card.php'; ?>
                </div>
            <?php endforeach; ?>
        </div>
        <?php require CIRCLEWORK_ROOT . '/resources/views/components/pagination.php'; ?>
    <?php endif; ?>
</div>
