<?php
/**
 * Shared administration navigation links, rendered inside the desktop sidebar
 * and inside the mobile offcanvas.
 *
 * @var string $adminNavActive
 * @var array<string,mixed>|null $currentUser
 */
$adminNavActive = $adminNavActive ?? '';
$currentUser = $currentUser ?? null;
$items = [
    ['key' => 'dashboard', 'label' => 'Dashboard', 'icon' => 'bi-speedometer2', 'route' => 'admin.dashboard'],
    ['key' => 'users', 'label' => 'Users', 'icon' => 'bi-people', 'route' => 'admin.users'],
    ['key' => 'freelancers', 'label' => 'Freelancer Profiles', 'icon' => 'bi-person-badge', 'route' => 'admin.freelancers'],
    ['key' => 'clients', 'label' => 'Client Accounts', 'icon' => 'bi-briefcase', 'route' => 'admin.clients'],
    ['key' => 'projects', 'label' => 'Projects', 'icon' => 'bi-kanban', 'route' => 'admin.projects'],
    ['key' => 'proposals', 'label' => 'Proposals', 'icon' => 'bi-send', 'route' => 'admin.proposals'],
    ['key' => 'contracts', 'label' => 'Contracts', 'icon' => 'bi-file-earmark-text', 'route' => 'admin.contracts'],
    ['key' => 'deliverables', 'label' => 'Deliverables', 'icon' => 'bi-box-seam', 'route' => 'admin.deliverables'],
    ['key' => 'payments', 'label' => 'Payment Records', 'icon' => 'bi-cash-coin', 'route' => 'admin.payments'],
    ['key' => 'subscriptions', 'label' => 'Subscriptions', 'icon' => 'bi-credit-card', 'route' => 'admin.subscriptions'],
    ['key' => 'subscription-payments', 'label' => 'Subscription Payments', 'icon' => 'bi-receipt', 'route' => 'admin.subscription.payments'],
    ['key' => 'plans', 'label' => 'Subscription Plans', 'icon' => 'bi-list-check', 'route' => 'admin.subscription.plans'],
    ['key' => 'featured', 'label' => 'Featured Listings', 'icon' => 'bi-star', 'route' => 'admin.subscriptions.featured'],
    ['key' => 'categories', 'label' => 'Categories', 'icon' => 'bi-diagram-3', 'route' => 'admin.categories'],
    ['key' => 'skills', 'label' => 'Skills', 'icon' => 'bi-tags', 'route' => 'admin.skills'],
    ['key' => 'reviews', 'label' => 'Reviews', 'icon' => 'bi-star-half', 'route' => 'admin.reviews'],
    ['key' => 'reports', 'label' => 'Reports', 'icon' => 'bi-flag', 'route' => 'admin.reports'],
    ['key' => 'disputes', 'label' => 'Disputes', 'icon' => 'bi-exclamation-diamond', 'route' => 'admin.disputes'],
    ['key' => 'announcements', 'label' => 'Announcements', 'icon' => 'bi-megaphone', 'route' => 'admin.announcements'],
    ['key' => 'audit', 'label' => 'Audit Logs', 'icon' => 'bi-journal-text', 'route' => 'admin.audit'],
    ['key' => 'settings', 'label' => 'Platform Settings', 'icon' => 'bi-sliders', 'route' => 'admin.settings'],
    ['key' => 'profile', 'label' => 'Admin Profile', 'icon' => 'bi-person-gear', 'route' => 'admin.profile'],
];?>
<nav aria-label="Administration navigation">
        <?php foreach ($items as $item): ?>
            <a href="<?= e(route($item['route'])) ?>" class="<?= $adminNavActive === $item['key'] ? 'active' : '' ?>">
                <i class="bi <?= e($item['icon']) ?>"></i> <?= e($item['label']) ?>
            </a>
        <?php endforeach; ?>

        <?php if (App\Core\Auth::isSuperAdmin()): ?>
            <div class="cw-sidebar__section">Super admin</div>
            <a href="<?= e(route('admin.administrators')) ?>" class="<?= $adminNavActive === 'administrators' ? 'active' : '' ?>">
                <i class="bi bi-person-lock"></i> Administrator accounts
            </a>
        <?php endif; ?>

        <div class="cw-sidebar__section">Exit</div>
        <a href="<?= e(route('dashboard')) ?>"><i class="bi bi-arrow-left"></i> Back to my dashboard</a>
        <form method="post" action="<?= e(route('logout')) ?>" class="px-4 pt-2">
            <?= csrf_field() ?>
            <button type="submit" class="btn btn-sm btn-outline-light w-100">
                <i class="bi bi-box-arrow-right me-1"></i> Logout
            </button>
        </form>
    </nav>
