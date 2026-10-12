<?php
/**
 * @var string $action
 * @var array<string,array{label:string,type:string,options?:array<string,string>,value?:string}> $fields
 */
?>
<form method="get" action="<?= e($action) ?>" class="row g-2 align-items-end mb-4">
    <?php foreach ($fields as $name => $field): ?>
        <div class="col-md-3">
            <label class="form-label small" for="filter-<?= e($name) ?>"><?= e($field['label']) ?></label>
            <?php if ($field['type'] === 'select'): ?>
                <select class="form-select form-select-sm" id="filter-<?= e($name) ?>" name="<?= e($name) ?>">
                    <option value="">All</option>
                    <?php foreach (($field['options'] ?? []) as $value => $label): ?>
                        <option value="<?= e((string) $value) ?>" <?= ($field['value'] ?? '') === (string) $value ? 'selected' : '' ?>>
                            <?= e($label) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            <?php else: ?>
                <input type="search" class="form-control form-control-sm" id="filter-<?= e($name) ?>"
                       name="<?= e($name) ?>" value="<?= e((string) ($field['value'] ?? '')) ?>">
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
    <div class="col-md-3">
        <button type="submit" class="btn btn-sm btn-primary">Apply</button>
        <a class="btn btn-sm btn-link" href="<?= e($action) ?>">Reset</a>
    </div>
</form>
