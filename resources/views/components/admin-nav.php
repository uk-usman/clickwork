<?php
/**
 * Desktop administration sidebar.
 *
 * The navigation links themselves live in admin-nav-links.php so the mobile
 * offcanvas can render exactly the same menu.
 *
 * @var string $adminNavActive
 * @var array<string,mixed>|null $currentUser
 */
$adminNavActive = $adminNavActive ?? '';
$currentUser = $currentUser ?? null;
?>
<aside class="cw-sidebar d-none d-lg-block">
    <div class="px-4 pb-3">
        <div class="text-white fw-bold"><i class="bi bi-shield-lock me-1"></i> Administration</div>
        <div class="text-white-50 small"><?= e(str_excerpt((string) ($currentUser['name'] ?? ''), 24)) ?></div>
    </div>

    <?php require CIRCLEWORK_ROOT . '/resources/views/components/admin-nav-links.php'; ?>
</aside>
