<?php
/** @var array<string,mixed>|null $currentUser */
/** @var string $activeMode */
$currentUser = $currentUser ?? null;
$activeMode = $activeMode ?? 'client';
$path = App\Core\Request::capture()->path();
$isAdminArea = str_starts_with($path, '/admin');
$linksPartial = CIRCLEWORK_ROOT . '/resources/views/components/dashboard-nav-links.php';
?>

<?php if ($isAdminArea): ?>
    <?php require CIRCLEWORK_ROOT . '/resources/views/components/admin-nav.php'; ?>

    <div class="offcanvas offcanvas-start cw-offcanvas" tabindex="-1" id="cwMobileNav"
         aria-labelledby="cwMobileNavLabel" style="width: 290px;">
        <div class="offcanvas-header">
            <h2 class="offcanvas-title h6 text-white" id="cwMobileNavLabel">Administration</h2>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body p-0 cw-nav-list">
            <?php require CIRCLEWORK_ROOT . '/resources/views/components/admin-nav-links.php'; ?>
        </div>
    </div>
<?php else: ?>
    <aside class="cw-sidebar d-none d-lg-block">
        <?php require $linksPartial; ?>
    </aside>

    <div class="offcanvas offcanvas-start cw-offcanvas" tabindex="-1" id="cwMobileNav"
         aria-labelledby="cwMobileNavLabel" style="width: 290px;">
        <div class="offcanvas-header">
            <h2 class="offcanvas-title h6 text-white" id="cwMobileNavLabel">Menu</h2>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body p-0 cw-nav-list">
            <?php require $linksPartial; ?>
        </div>
    </div>
<?php endif; ?>
