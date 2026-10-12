<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Request;
use App\Models\FeaturedListing;
use App\Models\Subscription;
use App\Models\SubscriptionPayment;
use App\Models\SubscriptionPlan;
use App\Services\AuditService;
use App\Services\SubscriptionService;

/**
 * Subscription plans, manual payment verification and featured listings.
 */
final class SubscriptionController extends AdminController
{
    public function index(Request $request): string
    {
        $filters = $this->filters($request, ['status']);
        $total = Subscription::adminCount($filters);
        $meta = Subscription::paginateMeta($total, 20, $request->int('page', 1));

        return $this->adminView('admin/subscriptions/index', [
            'title'          => 'Subscriptions',
            'subscriptions'  => Subscription::adminList($filters, $meta['per_page'], $meta['offset']),
            'meta'           => $meta,
            'filters'        => $filters,
            'adminNavActive' => 'subscriptions',
        ]);
    }

    public function payments(Request $request): string
    {
        $filters = $this->filters($request, ['status']);
        $total = SubscriptionPayment::adminCount($filters);
        $meta = SubscriptionPayment::paginateMeta($total, 20, $request->int('page', 1));

        return $this->adminView('admin/subscriptions/payments', [
            'title'          => 'Subscription payments (manual verification)',
            'payments'       => SubscriptionPayment::adminList($filters, $meta['per_page'], $meta['offset']),
            'meta'           => $meta,
            'filters'        => $filters,
            'adminNavActive' => 'subscription-payments',
        ]);
    }

    public function verifyPayment(Request $request): never
    {
        $result = SubscriptionService::verifyPayment(
            (int) ($request->route('id') ?? 0),
            (int) Auth::id(),
            $request->string('decision'),
            $request->string('note')
        );

        $this->ok($result['message'], 'admin.subscription.payments');
    }

    public function featured(Request $request): string
    {
        $filters = $this->filters($request, ['status']);
        $total = FeaturedListing::adminCount($filters);
        $meta = FeaturedListing::paginateMeta($total, 20, $request->int('page', 1));

        return $this->adminView('admin/subscriptions/featured', [
            'title'          => 'Featured listings',
            'listings'       => FeaturedListing::adminList($filters, $meta['per_page'], $meta['offset']),
            'meta'           => $meta,
            'filters'        => $filters,
            'adminNavActive' => 'featured',
        ]);
    }

    public function verifyFeatured(Request $request): never
    {
        $result = SubscriptionService::verifyFeaturedListing(
            (int) ($request->route('id') ?? 0),
            (int) Auth::id(),
            $request->string('decision')
        );

        $this->ok($result['message'], 'admin.subscriptions.featured');
    }

    public function plans(Request $request): string
    {
        return $this->adminView('admin/subscriptions/plans', [
            'title'          => 'Subscription plans',
            'plans'          => SubscriptionPlan::allOrdered(),
            'adminNavActive' => 'plans',
        ]);
    }

    public function storePlan(Request $request): mixed
    {
        $planId = $request->int('plan_id');

        $code = strtolower(preg_replace('/[^a-z0-9_]+/', '_', strtolower($request->string('code'))) ?? '');
        $name = $request->string('name');
        $audience = $request->string('audience', 'both');
        $priceRaw = $request->string('price', '0');
        $billing = $request->string('billing_period', 'monthly');
        $description = $request->string('description');
        $featuresRaw = $request->string('features');
        $activeProjects = $request->int('limit_active_projects', 3);
        $proposalsPerDay = $request->int('limit_proposals_per_day', 10);
        $portfolioItems = $request->int('limit_portfolio_items', 5);

        if ($name === '' || $code === '') {
            $this->err('Please provide a plan name and code.', 'admin.subscription.plans');
        }

        if (!is_numeric($priceRaw) || (float) $priceRaw < 0) {
            $this->err('Please provide a valid price.', 'admin.subscription.plans');
        }

        if (!in_array($audience, ['client', 'freelancer', 'both'], true)) {
            $audience = 'both';
        }

        if (!in_array($billing, ['monthly', 'yearly'], true)) {
            $billing = 'monthly';
        }

        $features = array_values(array_filter(array_map('trim', explode("\n", $featuresRaw))));

        $payload = [
            'code'           => mb_substr($code, 0, 60),
            'name'           => mb_substr($name, 0, 120),
            'audience'       => $audience,
            'price'          => number_format((float) $priceRaw, 2, '.', ''),
            'currency'       => 'NGN',
            'billing_period' => $billing,
            'description'    => $description !== '' ? mb_substr($description, 0, 500) : null,
            'features'       => json_encode($features, JSON_UNESCAPED_UNICODE),
            'limits'         => json_encode([
                'active_projects'   => $activeProjects,
                'proposals_per_day' => $proposalsPerDay,
                'portfolio_items'   => $portfolioItems,
                'featured_projects' => $request->int('limit_featured_projects', 0),
                'applicant_filters' => $request->int('limit_applicant_filters', 0),
                'profile_analytics' => $request->int('limit_profile_analytics', 0),
            ], JSON_UNESCAPED_UNICODE),
            'is_active'      => $request->bool('is_active') ? 1 : 0,
            'is_free'        => (float) $priceRaw === 0.0 ? 1 : 0,
            'sort_order'     => $request->int('sort_order', 0),
        ];

        if ($planId > 0) {
            SubscriptionPlan::update($planId, $payload);
            AuditService::record('admin.plan_updated', 'Subscription plan updated', 'subscription_plan', $planId);
            $this->ok('The plan has been updated.', 'admin.subscription.plans');
        }

        $newId = SubscriptionPlan::create($payload);
        AuditService::record('admin.plan_created', 'Subscription plan created', 'subscription_plan', $newId);

        $this->ok('The plan has been created.', 'admin.subscription.plans');
    }
}
