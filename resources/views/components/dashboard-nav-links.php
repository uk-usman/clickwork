<?php
/**
 * Shared dashboard navigation body, rendered inside both the desktop sidebar
 * and the mobile offcanvas so small screens keep a usable navigation.
 *
 * @var array<string,mixed>|null $currentUser
 * @var string $activeMode
 */
$currentUser = $currentUser ?? null;
$activeMode = $activeMode ?? 'client';
$path = App\Core\Request::capture()->path();
$isAdminArea = str_starts_with($path, '/admin');
$user = $currentUser;
$isFreelancerMode = $activeMode === 'freelancer';
?>
<div class="px-4 pb-3">
    <div class="text-white-50 small mb-2">Signed in as</div>
    <div class="text-white fw-semibold"><?= e(str_excerpt($user['name'] ?? '', 26)) ?></div>
    <div class="text-white-50 small"><?= e(humanize($user['role'] ?? 'user')) ?></div>
</div>

<form method="post" action="<?= e(route('dashboard.mode')) ?>" class="px-4 pb-3">
    <?= csrf_field() ?>
    <input type="hidden" name="mode" value="<?= $isFreelancerMode ? 'client' : 'freelancer' ?>">
    <button type="submit" class="btn btn-sm btn-outline-light w-100">
        <i class="bi bi-arrow-left-right me-1"></i>
        Switch to <?= $isFreelancerMode ? 'client' : 'freelancer' ?> mode
    </button>
</form>

<nav aria-label="Dashboard navigation">
    <a href="<?= e(route('dashboard')) ?>" class="<?= $path === '/dashboard' ? 'active' : '' ?>">
        <i class="bi bi-speedometer2"></i> Overview
    </a>

    <?php if ($isFreelancerMode): ?>
        <div class="cw-sidebar__section">Freelancer</div>
        <a href="<?= e(route('freelancer.profile')) ?>" class="<?= str_starts_with($path, '/freelancer/profile') ? 'active' : '' ?>">
            <i class="bi bi-person-badge"></i> My profile
        </a>
        <a href="<?= e(route('freelancer.portfolio')) ?>" class="<?= str_starts_with($path, '/freelancer/portfolio') ? 'active' : '' ?>">
            <i class="bi bi-images"></i> Portfolio
        </a>
        <a href="<?= e(route('projects.index')) ?>" class="<?= str_starts_with($path, '/projects') ? 'active' : '' ?>">
            <i class="bi bi-search"></i> Browse projects
        </a>
        <a href="<?= e(route('freelancer.proposals')) ?>" class="<?= str_starts_with($path, '/freelancer/proposals') ? 'active' : '' ?>">
            <i class="bi bi-send"></i> My proposals
        </a>
        <a href="<?= e(route('freelancer.contracts')) ?>" class="<?= str_starts_with($path, '/freelancer/contracts') ? 'active' : '' ?>">
            <i class="bi bi-file-earmark-text"></i> My contracts
        </a>
    <?php else: ?>
        <div class="cw-sidebar__section">Client</div>
        <a href="<?= e(route('client.projects')) ?>" class="<?= $path === '/client/projects' ? 'active' : '' ?>">
            <i class="bi bi-kanban"></i> My projects
        </a>
        <a href="<?= e(route('client.projects.create')) ?>" class="<?= str_starts_with($path, '/client/projects/create') ? 'active' : '' ?>">
            <i class="bi bi-plus-square"></i> Post a project
        </a>
        <a href="<?= e(route('client.contracts')) ?>" class="<?= str_starts_with($path, '/client/contracts') ? 'active' : '' ?>">
            <i class="bi bi-file-earmark-text"></i> My contracts
        </a>
    <?php endif; ?>

    <div class="cw-sidebar__section">Communication</div>
    <a href="<?= e(route('messages.index')) ?>" class="<?= str_starts_with($path, '/messages') ? 'active' : '' ?>">
        <i class="bi bi-chat-dots"></i> Messages
        <?php if (($unreadMessages ?? 0) > 0): ?>
            <span class="badge rounded-pill bg-danger ms-auto"><?= (int) $unreadMessages ?></span>
        <?php endif; ?>
    </a>
    <a href="<?= e(route('account.notifications')) ?>" class="<?= str_starts_with($path, '/account/notifications') ? 'active' : '' ?>">
        <i class="bi bi-bell"></i> Notifications
        <?php if (($unreadNotifications ?? 0) > 0): ?>
            <span class="badge rounded-pill bg-danger ms-auto"><?= (int) $unreadNotifications ?></span>
        <?php endif; ?>
    </a>

    <div class="cw-sidebar__section">Account</div>
    <a href="<?= e(route('payments.index')) ?>" class="<?= str_starts_with($path, '/payments') ? 'active' : '' ?>">
        <i class="bi bi-cash-coin"></i> Payment records
    </a>
    <a href="<?= e(route('subscriptions.index')) ?>" class="<?= str_starts_with($path, '/subscriptions') ? 'active' : '' ?>">
        <i class="bi bi-stars"></i> Subscription
    </a>
    <a href="<?= e(route('account.settings')) ?>" class="<?= str_starts_with($path, '/account/settings') ? 'active' : '' ?>">
        <i class="bi bi-gear"></i> Settings
    </a>
    <a href="<?= e(route('report.create')) ?>" class="<?= str_starts_with($path, '/report') ? 'active' : '' ?>">
        <i class="bi bi-flag"></i> Report a problem
    </a>
</nav>
