<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Session;
use App\Models\Subscription;
use App\Models\SubscriptionPayment;
use App\Models\SubscriptionPlan;
use App\Services\FeaturedService;
use App\Services\SubscriptionService;

/**
 * Subscription plans and manual (administrator-verified) payments.
 */
final class SubscriptionController extends Controller
{
    public function index(Request $request): string
    {
        $userId = (int) Auth::id();
        $mode = Auth::activeMode();

        $plans = SubscriptionPlan::activeFor($mode);
        $subscriptions = Subscription::forUser($userId);

        $payments = [];
        foreach ($subscriptions as $subscription) {
            foreach (SubscriptionPayment::forSubscription((int) $subscription['id']) as $payment) {
                $payments[] = $payment;
            }
        }

        return $this->dashboardView('subscriptions/index', [
            'title'         => 'Subscription and plans',
            'plans'         => $plans,
            'subscriptions' => $subscriptions,
            'payments'      => $payments,
            'activePlan'    => SubscriptionService::activePlanFor($userId),
            'limits'        => SubscriptionService::limitsFor($userId),
            'features'      => SubscriptionService::featuresFor($userId),
            'featuredPrice' => FeaturedService::price(),
            'featuredDays'  => FeaturedService::durationDays(),
            'mode'          => $mode,
        ]);
    }

    public function request(Request $request): never
    {
        $result = SubscriptionService::requestSubscription((int) Auth::id(), $request->int('plan_id'));

        Session::flash($result['ok'] ? 'success' : 'danger', $result['message']);
        $this->redirectToRoute('subscriptions.index');
    }

    public function recordPayment(Request $request): never
    {
        $result = SubscriptionService::recordManualPayment(
            $request->int('subscription_id'),
            (int) Auth::id(),
            [
                'amount'          => $request->input('amount', ''),
                'method'          => $request->string('method', 'bank_transfer'),
                'payer_reference' => $request->string('payer_reference'),
                'paid_at'         => $request->string('paid_at'),
            ]
        );

        Session::flash($result['ok'] ? 'success' : 'danger', $result['message']);
        $this->redirectToRoute('subscriptions.index');
    }
}
