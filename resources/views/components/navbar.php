<?php
/** @var array<string,mixed>|null $currentUser */
$currentUser = $currentUser ?? null;
$siteName = setting('site_name', 'CircleWork Africa') ?? 'CircleWork Africa';
$path = App\Core\Request::capture()->path();
?>
<nav class="navbar navbar-expand-lg cw-navbar sticky-top">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center gap-2" href="<?= e(route('home')) ?>">
            <i class="bi bi-circle-square"></i>
            <span><?= e($siteName) ?></span>
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#cwMainNav"
                aria-controls="cwMainNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="cwMainNav">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item"><a class="nav-link <?= str_starts_with($path, '/projects') ? 'active' : '' ?>" href="<?= e(route('projects.index')) ?>">Browse Projects</a></li>
                <li class="nav-item"><a class="nav-link <?= str_starts_with($path, '/freelancers') ? 'active' : '' ?>" href="<?= e(route('freelancers.index')) ?>">Find Freelancers</a></li>
                <li class="nav-item"><a class="nav-link <?= $path === '/how-it-works' ? 'active' : '' ?>" href="<?= e(route('how-it-works')) ?>">How It Works</a></li>
                <li class="nav-item"><a class="nav-link <?= $path === '/pricing' ? 'active' : '' ?>" href="<?= e(route('pricing')) ?>">Pricing</a></li>
            </ul>

            <ul class="navbar-nav align-items-lg-center gap-lg-1">
                <?php if ($currentUser === null): ?>
                    <li class="nav-item"><a class="nav-link" href="<?= e(route('login')) ?>">Sign in</a></li>
                    <li class="nav-item">
                        <a class="btn btn-primary btn-sm px-3" href="<?= e(route('register')) ?>">Get started</a>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link position-relative" href="<?= e(route('account.notifications')) ?>" aria-label="Notifications">
                            <i class="bi bi-bell"></i>
                            <?php if (($unreadNotifications ?? 0) > 0): ?>
                                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                    <?= (int) $unreadNotifications ?>
                                </span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link position-relative" href="<?= e(route('messages.index')) ?>" aria-label="Messages">
                            <i class="bi bi-chat-dots"></i>
                            <?php if (($unreadMessages ?? 0) > 0): ?>
                                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                    <?= (int) $unreadMessages ?>
                                </span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle d-flex align-items-center gap-2" href="#" role="button"
                           data-bs-toggle="dropdown" aria-expanded="false">
                            <?php $avatar = avatar_url($currentUser['avatar_path'] ?? null); ?>
                            <?php if ($avatar !== null): ?>
                                <img src="<?= e($avatar) ?>" alt="" class="cw-avatar">
                            <?php else: ?>
                                <span class="cw-avatar"><?= e(initials($currentUser['name'] ?? '')) ?></span>
                            <?php endif; ?>
                            <span class="d-none d-lg-inline"><?= e(str_excerpt($currentUser['name'] ?? '', 18)) ?></span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end shadow">
                            <li><a class="dropdown-item" href="<?= e(route('dashboard')) ?>"><i class="bi bi-speedometer2 me-2"></i>Dashboard</a></li>
                            <li><a class="dropdown-item" href="<?= e(route('account.settings')) ?>"><i class="bi bi-gear me-2"></i>Account settings</a></li>
                            <li><a class="dropdown-item" href="<?= e(route('payments.index')) ?>"><i class="bi bi-cash-coin me-2"></i>Payment records</a></li>
                            <li><a class="dropdown-item" href="<?= e(route('subscriptions.index')) ?>"><i class="bi bi-stars me-2"></i>Subscription</a></li>
                            <?php if (App\Core\Auth::isAdmin()): ?>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="<?= e(route('admin.dashboard')) ?>"><i class="bi bi-shield-lock me-2"></i>Administration</a></li>
                            <?php endif; ?>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <form method="post" action="<?= e(route('logout')) ?>" class="px-3 py-1">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="btn btn-sm btn-outline-secondary w-100">
                                        <i class="bi bi-box-arrow-right me-1"></i>Sign out
                                    </button>
                                </form>
                            </li>
                        </ul>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>
