<?php

/**
 * CircleWork Africa — route definitions.
 *
 * @var App\Core\Router $router
 */

use App\Controllers\AccountController;
use App\Controllers\AuthController;
use App\Controllers\ClientProjectController;
use App\Controllers\ContractController;
use App\Controllers\DashboardController;
use App\Controllers\DeliverableController;
use App\Controllers\FreelancerController;
use App\Controllers\FreelancerProfileController;
use App\Controllers\FreelancerProposalController;
use App\Controllers\HomeController;
use App\Controllers\MessageController;
use App\Controllers\PageController;
use App\Controllers\PaymentController;
use App\Controllers\ProjectController;
use App\Controllers\ReportController;
use App\Controllers\ReviewController;
use App\Controllers\SubscriptionController;
use App\Middleware\AdminMiddleware;
use App\Middleware\AuthMiddleware;
use App\Middleware\CsrfMiddleware;
use App\Middleware\GuestMiddleware;
use App\Middleware\SuperAdminMiddleware;

// ---------------------------------------------------------------------------
// Public website
// ---------------------------------------------------------------------------
$router->get('/', [HomeController::class, 'index'], 'home');
$router->get('/projects', [ProjectController::class, 'index'], 'projects.index');
$router->get('/projects/{id}', [ProjectController::class, 'show'], 'projects.show');
$router->get('/freelancers', [FreelancerController::class, 'index'], 'freelancers.index');
$router->get('/freelancers/{id}', [FreelancerController::class, 'show'], 'freelancers.show');

$router->get('/media/avatar/{path}', [\App\Controllers\MediaController::class, 'avatar'], 'media.avatar');
$router->get('/media/portfolio/{path}', [\App\Controllers\MediaController::class, 'portfolio'], 'media.portfolio');

$router->get('/how-it-works', [PageController::class, 'howItWorks'], 'how-it-works');
$router->get('/pricing', [PageController::class, 'pricing'], 'pricing');
$router->get('/about', [PageController::class, 'about'], 'about');
$router->get('/contact', [PageController::class, 'contact'], 'contact');
$router->get('/faq', [PageController::class, 'faq'], 'faq');
$router->get('/terms', [PageController::class, 'terms'], 'terms');
$router->get('/privacy', [PageController::class, 'privacy'], 'privacy');
$router->get('/community-guidelines', [PageController::class, 'guidelines'], 'guidelines');
$router->get('/dispute-policy', [PageController::class, 'disputePolicy'], 'dispute-policy');

// ---------------------------------------------------------------------------
// Authentication (guests only)
// ---------------------------------------------------------------------------
$router->group([CsrfMiddleware::class, GuestMiddleware::class], function ($router): void {
    $router->get('/login', [AuthController::class, 'showLogin'], 'login');
    $router->post('/login', [AuthController::class, 'login'], 'login.attempt');
    $router->get('/register', [AuthController::class, 'showRegister'], 'register');
    $router->post('/register', [AuthController::class, 'register'], 'register.store');
    $router->get('/forgot-password', [AuthController::class, 'showForgot'], 'password.request');
    $router->post('/forgot-password', [AuthController::class, 'sendReset'], 'password.email');
    $router->get('/reset-password/{token}', [AuthController::class, 'showReset'], 'password.reset.form');
    $router->post('/reset-password/{token}', [AuthController::class, 'reset'], 'password.reset');
});

// Verification link is reachable while signed in or out.
$router->get('/verify-email/{token}', [AuthController::class, 'verifyEmail'], 'verify.email');

// ---------------------------------------------------------------------------
// Authenticated area
// ---------------------------------------------------------------------------
$router->group([CsrfMiddleware::class, AuthMiddleware::class], function ($router): void {
    $router->post('/logout', [AuthController::class, 'logout'], 'logout');
    $router->get('/verify-email', [AuthController::class, 'verifyNotice'], 'verify.notice');
    $router->post('/verify-email/resend', [AuthController::class, 'resendVerification'], 'verify.resend');

    $router->get('/dashboard', [DashboardController::class, 'index'], 'dashboard');
    $router->post('/dashboard/mode', [DashboardController::class, 'switchMode'], 'dashboard.mode');

    // Account
    $router->get('/account/settings', [AccountController::class, 'settings'], 'account.settings');
    $router->post('/account/profile', [AccountController::class, 'updateProfile'], 'account.profile');
    $router->post('/account/password', [AccountController::class, 'updatePassword'], 'account.password');
    $router->get('/account/notifications', [AccountController::class, 'notifications'], 'account.notifications');
    $router->post('/account/notifications/{id}/read', [AccountController::class, 'markNotificationRead'], 'account.notifications.read');
    $router->post('/account/notifications/read-all', [AccountController::class, 'markAllNotificationsRead'], 'account.notifications.read_all');

    // Reports
    $router->get('/report', [ReportController::class, 'create'], 'report.create');
    $router->post('/report', [ReportController::class, 'store'], 'report.store');

    // Subscriptions
    $router->get('/subscriptions', [SubscriptionController::class, 'index'], 'subscriptions.index');
    $router->post('/subscriptions/request', [SubscriptionController::class, 'request'], 'subscriptions.request');
    $router->post('/subscriptions/payment', [SubscriptionController::class, 'recordPayment'], 'subscriptions.payment');

    // Freelancer profile
    $router->get('/freelancer/profile', [FreelancerProfileController::class, 'edit'], 'freelancer.profile');
    $router->post('/freelancer/profile', [FreelancerProfileController::class, 'update'], 'freelancer.profile.update');
    $router->post('/freelancer/profile/skills', [FreelancerProfileController::class, 'updateSkills'], 'freelancer.profile.skills');
    $router->post('/freelancer/profile/avatar', [FreelancerProfileController::class, 'updateAvatar'], 'freelancer.profile.avatar');
    $router->get('/freelancer/portfolio', [FreelancerProfileController::class, 'portfolio'], 'freelancer.portfolio');
    $router->post('/freelancer/portfolio', [FreelancerProfileController::class, 'storePortfolio'], 'freelancer.portfolio.store');
    $router->post('/freelancer/portfolio/{id}/delete', [FreelancerProfileController::class, 'deletePortfolio'], 'freelancer.portfolio.delete');
    $router->post('/freelancer/portfolio/{id}/visibility', [FreelancerProfileController::class, 'togglePortfolioVisibility'], 'freelancer.portfolio.visibility');

    // Freelancer proposals
    $router->get('/freelancer/proposals', [FreelancerProposalController::class, 'index'], 'freelancer.proposals');
    $router->get('/freelancer/proposals/{id}', [FreelancerProposalController::class, 'show'], 'freelancer.proposals.show');
    $router->post('/freelancer/proposals/{id}/withdraw', [FreelancerProposalController::class, 'withdraw'], 'freelancer.proposals.withdraw');
    $router->get('/freelancer/contracts', [FreelancerProposalController::class, 'contracts'], 'freelancer.contracts');
    $router->post('/projects/{id}/proposals', [FreelancerProposalController::class, 'store'], 'freelancer.proposals.store');

    // Client projects
    $router->get('/client/projects', [ClientProjectController::class, 'index'], 'client.projects');
    $router->get('/client/projects/create', [ClientProjectController::class, 'create'], 'client.projects.create');
    $router->post('/client/projects', [ClientProjectController::class, 'store'], 'client.projects.store');
    $router->get('/client/projects/{id}', [ClientProjectController::class, 'show'], 'client.projects.show');
    $router->get('/client/projects/{id}/edit', [ClientProjectController::class, 'edit'], 'client.projects.edit');
    $router->post('/client/projects/{id}', [ClientProjectController::class, 'update'], 'client.projects.update');
    $router->get('/client/projects/{id}/proposals', [ClientProjectController::class, 'proposals'], 'client.projects.proposals');
    $router->post('/client/projects/{id}/feature', [ClientProjectController::class, 'requestFeatured'], 'client.projects.feature');
    $router->get('/client/contracts', [ClientProjectController::class, 'contracts'], 'client.contracts');
    $router->post('/client/proposals/{id}/decide', [ClientProjectController::class, 'decideProposal'], 'client.proposals.decide');
    $router->post('/client/proposals/{id}/award', [ClientProjectController::class, 'awardProposal'], 'client.proposals.award');

    // Contracts
    $router->get('/contracts/{id}', [ContractController::class, 'show'], 'contracts.show');
    $router->get('/contracts/{id}/print', [ContractController::class, 'printView'], 'contracts.print');
    $router->post('/contracts/{id}/accept', [ContractController::class, 'accept'], 'contracts.accept');
    $router->post('/contracts/{id}/amend', [ContractController::class, 'amend'], 'contracts.amend');
    $router->post('/contracts/{id}/cancel', [ContractController::class, 'cancel'], 'contracts.cancel');
    $router->post('/contracts/{id}/dispute', [ContractController::class, 'openDispute'], 'contracts.dispute');
    $router->post('/contracts/{id}/review', [ReviewController::class, 'store'], 'contracts.review');

    // Deliverables
    $router->post('/contracts/{id}/deliverables', [DeliverableController::class, 'submit'], 'deliverables.submit');
    $router->post('/contracts/{id}/deliverables/{deliverableId}/approve', [DeliverableController::class, 'approve'], 'deliverables.approve');
    $router->post('/contracts/{id}/deliverables/{deliverableId}/revision', [DeliverableController::class, 'requestRevision'], 'deliverables.revision');
    $router->post('/contracts/{id}/complete', [DeliverableController::class, 'complete'], 'contracts.complete');
    $router->get('/deliverable-files/{id}', [DeliverableController::class, 'downloadFile'], 'deliverable_files.download');

    // Messages
    $router->get('/messages', [MessageController::class, 'index'], 'messages.index');
    $router->get('/messages/{id}', [MessageController::class, 'show'], 'messages.show');
    $router->post('/messages/{id}', [MessageController::class, 'send'], 'messages.send');

    // Payments
    $router->get('/payments', [PaymentController::class, 'index'], 'payments.index');
    $router->get('/contracts/{id}/payments', [PaymentController::class, 'forContract'], 'contracts.payments');
    $router->post('/contracts/{id}/payments', [PaymentController::class, 'report'], 'contracts.payments.report');
    $router->post('/contracts/{contractId}/payments/{id}/confirm', [PaymentController::class, 'confirm'], 'contracts.payments.confirm');
    $router->post('/contracts/{contractId}/payments/{id}/dispute', [PaymentController::class, 'dispute'], 'contracts.payments.dispute');
    $router->get('/payment-evidence/{id}', [PaymentController::class, 'downloadEvidence'], 'payment_evidence.download');
});

// ---------------------------------------------------------------------------
// Administration
// ---------------------------------------------------------------------------
$router->group([CsrfMiddleware::class, AuthMiddleware::class, AdminMiddleware::class], function ($router): void {
    $router->get('/admin', [\App\Controllers\Admin\DashboardController::class, 'index'], 'admin.dashboard');

    $router->get('/admin/users', [\App\Controllers\Admin\UserController::class, 'index'], 'admin.users');
    $router->get('/admin/users/{id}', [\App\Controllers\Admin\UserController::class, 'show'], 'admin.users.show');
    $router->post('/admin/users/{id}/suspend', [\App\Controllers\Admin\UserController::class, 'suspend'], 'admin.users.suspend');
    $router->post('/admin/users/{id}/activate', [\App\Controllers\Admin\UserController::class, 'activate'], 'admin.users.activate');

    $router->get('/admin/clients', [\App\Controllers\Admin\RecordsController::class, 'clients'], 'admin.clients');
    $router->get('/admin/freelancers', [\App\Controllers\Admin\RecordsController::class, 'freelancers'], 'admin.freelancers');
    $router->get('/admin/proposals', [\App\Controllers\Admin\RecordsController::class, 'proposals'], 'admin.proposals');
    $router->get('/admin/deliverables', [\App\Controllers\Admin\RecordsController::class, 'deliverables'], 'admin.deliverables');

    $router->get('/admin/projects', [\App\Controllers\Admin\ProjectController::class, 'index'], 'admin.projects');
    $router->get('/admin/projects/{id}', [\App\Controllers\Admin\ProjectController::class, 'show'], 'admin.projects.show');
    $router->post('/admin/projects/{id}/moderate', [\App\Controllers\Admin\ProjectController::class, 'moderate'], 'admin.projects.moderate');
    $router->post('/admin/projects/{id}/feature', [\App\Controllers\Admin\ProjectController::class, 'feature'], 'admin.projects.feature');

    $router->get('/admin/contracts', [\App\Controllers\Admin\ContractController::class, 'index'], 'admin.contracts');
    $router->get('/admin/contracts/{id}', [\App\Controllers\Admin\ContractController::class, 'show'], 'admin.contracts.show');

    $router->get('/admin/payments', [\App\Controllers\Admin\PaymentController::class, 'index'], 'admin.payments');
    $router->get('/admin/payments/{id}', [\App\Controllers\Admin\PaymentController::class, 'show'], 'admin.payments.show');
    $router->post('/admin/payments/{id}/review', [\App\Controllers\Admin\PaymentController::class, 'review'], 'admin.payments.review');

    $router->get('/admin/subscriptions', [\App\Controllers\Admin\SubscriptionController::class, 'index'], 'admin.subscriptions');
    $router->get('/admin/subscription-payments', [\App\Controllers\Admin\SubscriptionController::class, 'payments'], 'admin.subscription.payments');
    $router->post('/admin/subscription-payments/{id}/verify', [\App\Controllers\Admin\SubscriptionController::class, 'verifyPayment'], 'admin.subscription.payments.verify');
    $router->get('/admin/featured-listings', [\App\Controllers\Admin\SubscriptionController::class, 'featured'], 'admin.subscriptions.featured');
    $router->post('/admin/featured-listings/{id}/verify', [\App\Controllers\Admin\SubscriptionController::class, 'verifyFeatured'], 'admin.subscriptions.featured.verify');
    $router->get('/admin/plans', [\App\Controllers\Admin\SubscriptionController::class, 'plans'], 'admin.subscription.plans');
    $router->post('/admin/plans', [\App\Controllers\Admin\SubscriptionController::class, 'storePlan'], 'admin.subscription.plans.save');

    $router->get('/admin/categories', [\App\Controllers\Admin\CatalogController::class, 'categories'], 'admin.categories');
    $router->post('/admin/categories', [\App\Controllers\Admin\CatalogController::class, 'saveCategory'], 'admin.categories.save');
    $router->post('/admin/categories/{id}/toggle', [\App\Controllers\Admin\CatalogController::class, 'toggleCategory'], 'admin.categories.toggle');
    $router->get('/admin/skills', [\App\Controllers\Admin\CatalogController::class, 'skills'], 'admin.skills');
    $router->post('/admin/skills', [\App\Controllers\Admin\CatalogController::class, 'saveSkill'], 'admin.skills.save');
    $router->post('/admin/skills/{id}/toggle', [\App\Controllers\Admin\CatalogController::class, 'toggleSkill'], 'admin.skills.toggle');

    $router->get('/admin/reviews', [\App\Controllers\Admin\ModerationController::class, 'reviews'], 'admin.reviews');
    $router->post('/admin/reviews/{id}/moderate', [\App\Controllers\Admin\ModerationController::class, 'moderateReview'], 'admin.reviews.moderate');
    $router->get('/admin/reports', [\App\Controllers\Admin\ModerationController::class, 'reports'], 'admin.reports');
    $router->post('/admin/reports/{id}/resolve', [\App\Controllers\Admin\ModerationController::class, 'resolveReport'], 'admin.reports.resolve');
    $router->get('/admin/disputes', [\App\Controllers\Admin\ModerationController::class, 'disputes'], 'admin.disputes');
    $router->get('/admin/disputes/{id}', [\App\Controllers\Admin\ModerationController::class, 'showDispute'], 'admin.disputes.show');
    $router->post('/admin/disputes/{id}/resolve', [\App\Controllers\Admin\ModerationController::class, 'resolveDispute'], 'admin.disputes.resolve');
    $router->post('/admin/disputes/{id}/reply', [\App\Controllers\Admin\ModerationController::class, 'replyDispute'], 'admin.disputes.reply');

    $router->get('/admin/announcements', [\App\Controllers\Admin\SystemController::class, 'announcements'], 'admin.announcements');
    $router->post('/admin/announcements', [\App\Controllers\Admin\SystemController::class, 'storeAnnouncement'], 'admin.announcements.store');
    $router->get('/admin/audit-logs', [\App\Controllers\Admin\SystemController::class, 'auditLogs'], 'admin.audit');
    $router->get('/admin/settings', [\App\Controllers\Admin\SystemController::class, 'settings'], 'admin.settings');
    $router->post('/admin/settings', [\App\Controllers\Admin\SystemController::class, 'saveSettings'], 'admin.settings.save');
    $router->get('/admin/profile', [\App\Controllers\Admin\SystemController::class, 'profile'], 'admin.profile');
    $router->post('/admin/profile', [\App\Controllers\Admin\SystemController::class, 'updateProfile'], 'admin.profile.update');
});

// Super administrator only
$router->group([CsrfMiddleware::class, AuthMiddleware::class, AdminMiddleware::class, SuperAdminMiddleware::class], function ($router): void {
    $router->get('/admin/administrators', [\App\Controllers\Admin\RecordsController::class, 'administrators'], 'admin.administrators');
    $router->post('/admin/administrators', [\App\Controllers\Admin\RecordsController::class, 'promoteAdmin'], 'admin.administrators.promote');
    $router->post('/admin/administrators/{id}/demote', [\App\Controllers\Admin\RecordsController::class, 'demoteAdmin'], 'admin.administrators.demote');
});
