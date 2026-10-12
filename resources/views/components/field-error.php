<?php
/** @var array<string,string> $errors */
/** @var string $field */
$errors = $errors ?? [];
if (isset($errors[$field])): ?>
    <div class="invalid-feedback d-block"><?= e($errors[$field]) ?></div>
<?php endif; ?>
