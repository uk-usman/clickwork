<?php
/** @var array<int,array{type:string,message:string}> $flashMessages */
$flashMessages = $flashMessages ?? [];
?>
<?php if ($flashMessages !== []): ?>
    <div class="container pt-3">
        <?php foreach ($flashMessages as $flash): ?>
            <?php
            $type = in_array($flash['type'], ['success', 'danger', 'warning', 'info'], true) ? $flash['type'] : 'info';
            $icon = match ($type) {
                'success' => 'bi-check-circle-fill',
                'danger'  => 'bi-exclamation-octagon-fill',
                'warning' => 'bi-exclamation-triangle-fill',
                default   => 'bi-info-circle-fill',
            };
            ?>
            <div class="alert alert-<?= e($type) ?> alert-dismissible fade show d-flex align-items-start gap-2"
                 role="alert" data-auto-dismiss="9000">
                <i class="bi <?= e($icon) ?> mt-1"></i>
                <div class="flex-grow-1"><?= e($flash['message']) ?></div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
