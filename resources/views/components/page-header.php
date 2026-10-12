<?php
/** @var string $heading */
/** @var string|null $lead */
/** @var array<int,array{label:string,url?:string}> $crumbs */
$crumbs = $crumbs ?? [];
?>
<section class="cw-page-header">
    <div class="container">
        <?php if ($crumbs !== []): ?>
            <nav aria-label="Breadcrumb">
                <ol class="breadcrumb cw-breadcrumb small">
                    <?php foreach ($crumbs as $index => $crumb): ?>
                        <li class="breadcrumb-item <?= $index === count($crumbs) - 1 ? 'active' : '' ?>">
                            <?php if (!empty($crumb['url']) && $index !== count($crumbs) - 1): ?>
                                <a href="<?= e($crumb['url']) ?>"><?= e($crumb['label']) ?></a>
                            <?php else: ?>
                                <?= e($crumb['label']) ?>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ol>
            </nav>
        <?php endif; ?>
        <h1 class="h2"><?= e($heading) ?></h1>
        <?php if (!empty($lead)): ?>
            <p><?= e($lead) ?></p>
        <?php endif; ?>
    </div>
</section>
