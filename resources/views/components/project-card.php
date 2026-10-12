<?php
/** @var array<string,mixed> $project */
$isFeatured = (int) ($project['is_featured'] ?? 0) === 1
    && (($project['featured_until'] ?? null) === null || strtotime((string) $project['featured_until']) > time());

$budget = match ($project['budget_type'] ?? 'fixed') {
    'hourly' => (($project['budget_min'] ?? null) !== null && ($project['budget_max'] ?? null) !== null)
        ? money($project['budget_min'], (string) $project['currency']) . ' – ' . money($project['budget_max'], (string) $project['currency']) . ' / hr'
        : 'Hourly rate negotiable',
    default  => (($project['budget_min'] ?? null) !== null && ($project['budget_max'] ?? null) !== null)
        ? money($project['budget_min'], (string) $project['currency']) . ' – ' . money($project['budget_max'], (string) $project['currency'])
        : 'Budget not specified',
};
?>
<article class="cw-project-card <?= $isFeatured ? 'cw-project-card--featured' : '' ?>">
    <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
        <span class="cw-chip cw-chip--royal"><?= e($project['category_name'] ?? 'General') ?></span>
        <?php if ($isFeatured): ?>
            <span class="badge text-bg-primary"><i class="bi bi-star-fill me-1"></i>Featured</span>
        <?php endif; ?>
    </div>

    <h3><a href="<?= e(route('projects.show', ['id' => (int) $project['id']])) ?>"><?= e(str_excerpt((string) $project['title'], 90)) ?></a></h3>

    <p class="text-muted small flex-grow-1 mb-3"><?= e(str_excerpt((string) $project['description'], 150)) ?></p>

    <dl class="row row-cols-2 g-2 small mb-3">
        <div class="col">
            <dt class="text-muted fw-normal mb-0">Budget</dt>
            <dd class="fw-semibold mb-0"><?= e($budget) ?></dd>
        </div>
        <div class="col">
            <dt class="text-muted fw-normal mb-0">Deadline</dt>
            <dd class="fw-semibold mb-0">
                <?= ($project['deadline'] ?? null) ? e(date('d M Y', strtotime((string) $project['deadline']))) : 'Flexible' ?>
            </dd>
        </div>
    </dl>

    <div class="d-flex justify-content-between align-items-center small text-muted border-top pt-3 mt-auto">
        <span><i class="bi bi-geo-alt"></i> <?= e($project['client_city'] ?? 'Nigeria') ?></span>
        <span><i class="bi bi-people"></i> <?= (int) ($project['proposal_count'] ?? 0) ?> proposal(s)</span>
    </div>
</article>
