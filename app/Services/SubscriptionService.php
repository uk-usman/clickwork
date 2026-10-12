<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Models\FeaturedListing;
use App\Models\Project;
use App\Models\Subscription;
use App\Models\SubscriptionPayment;
use App\Models\SubscriptionPlan;

/**
 * Subscription plans, server-side feature limits and manual payment verification.
 *
 * No payment gateway is required. Until a provider is integrated, an
 * administrator records and verifies subscription payments manually with an
 * audit trail. Premium features are always resolved from the database, never
 * from request parameters.
 */
final class SubscriptionService
{
    public const FREE_PLAN_CODE = 'free';

    /**
     * @return array<string,mixed>|null
     */
    public static function activePlanFor(int $userId): ?array
    {
        $subscription = Subscription::activeFor($userId);

        if ($subscription !== null) {
            return $subscription;
        }

        return SubscriptionPlan::freePlan();
    }

    /**
     * @return array<string,mixed>
     */
    public static function limitsFor(int $userId): array
    {
        $plan = self::activePlanFor($userId);
        $defaults = [
            'active_projects'   => 3,
            'proposals_per_day' => 10,
            'portfolio_items'   => 5,
            'featured_projects' => 0,
            'applicant_filters' => 0,
            'profile_analytics' => 0,
        ];

        if ($plan === null || $plan['limits'] === null) {
            return $defaults;
        }

        $decoded = json_decode((string) $plan['limits'], true);

        return is_array($decoded) ? array_merge($defaults, $decoded) : $defaults;
    }

    /**
     * @return array<int,string>
     */
    public static function featuresFor(int $userId): array
    {
        $plan = self::activePlanFor($userId);

        if ($plan === null || $plan['features'] === null) {
            return [];
        }

        $decoded = json_decode((string) $plan['features'], true);

        return is_array($decoded) ? array_values(array_map('strval', $decoded)) : [];
    }

    public static function hasFeature(int $userId, string $feature): bool
    {
        return in_array($feature, self::featuresFor($userId), true);
    }

    public static function planNameFor(int $userId): string
    {
        $plan = self::activePlanFor($userId);

        return $plan !== null ? (string) ($plan['plan_name'] ?? $plan['name'] ?? 'Free') : 'Free';
    }

    /**
     * Server-side enforcement of the active-project limit.
     *
     * @return array{ok:bool,message:string}
     */
    public static function canPublishAnotherProject(int $userId): array
    {
        $limits = self::limitsFor($userId);
        $limit = (int) ($limits['active_projects'] ?? 3);

        $current = (int) Database::scalar(
            "SELECT COUNT(*) FROM `projects` WHERE `client_id` = ? AND `status` IN ('published','in_progress')",
            [$userId]
        );

        if ($current >= $limit) {
            return [
                'ok'      => false,
                'message' => 'Your current plan allows ' . $limit . ' active project(s). Upgrade your plan to publish more projects.',
            ];
        }

        return ['ok' => true, 'message' => '']; 
    }

    /**
     * Start a pending subscription for a plan. Nothing is activated until an
     * administrator verifies a recorded manual payment.
     *
     * @return array{ok:bool,message:string,subscription_id?:int}
     */
    public static function requestSubscription(int $userId, int $planId): array
    {
        $plan = SubscriptionPlan::find($planId);

        if ($plan === null || (int) $plan['is_active'] !== 1) {
            return ['ok' => false, 'message' => 'That plan is not available.'];
        }

        if ((int) $plan['is_free'] === 1) {
            return ['ok' => false, 'message' => 'The free plan does not require a payment.'];
        }

        $existingPending = Database::selectOne(
            "SELECT `id` FROM `subscriptions` WHERE `user_id` = ? AND `plan_id` = ? AND `status` = 'pending' LIMIT 1",
            [$userId, $planId]
        );

        if ($existingPending !== null) {
            return ['ok' => true, 'message' => 'You already have a pending request for this plan.', 'subscription_id' => (int) $existingPending['id']];
        }

        $subscriptionId = Subscription::create([
            'user_id' => $userId,
            'plan_id' => $planId,
            'status'  => 'pending',
        ]);

        AuditService::record('subscription.requested', 'Subscription requested', 'subscription', $subscriptionId, ['plan' => $plan['code']]);

        return ['ok' => true, 'message' => 'Your request has been recorded. Record your manual payment details so an administrator can verify it.', 'subscription_id' => $subscriptionId];
    }

    /**
     * Record a manual subscription payment (no gateway involved).
     *
     * @return array{ok:bool,message:string,payment_id?:int}
     */
    public static function recordManualPayment(int $subscriptionId, int $actorId, array $input): array
    {
        $subscription = Subscription::find($subscriptionId);

        if ($subscription === null) {
            return ['ok' => false, 'message' => 'Subscription not found.'];
        }

        $plan = SubscriptionPlan::find((int) $subscription['plan_id']);

        if ($plan === null) {
            return ['ok' => false, 'message' => 'Plan not found.'];
        }

        $amountRaw = trim((string) ($input['amount'] ?? (string) $plan['price']));
        $method = (string) ($input['method'] ?? 'bank_transfer');
        $reference = trim((string) ($input['payer_reference'] ?? ''));
        $paidAt = trim((string) ($input['paid_at'] ?? ''));

        if (!is_numeric($amountRaw) || (float) $amountRaw <= 0) {
            return ['ok' => false, 'message' => 'Please enter the amount paid.'];
        }

        if (!in_array($method, ['bank_transfer', 'cash', 'mobile_money', 'card_transfer', 'other'], true)) {
            return ['ok' => false, 'message' => 'Please choose a valid payment method.'];
        }

        $paymentId = SubscriptionPayment::create([
            'subscription_id' => $subscriptionId,
            'user_id'         => (int) $subscription['user_id'],
            'plan_id'         => (int) $plan['id'],
            'amount'          => number_format((float) $amountRaw, 2, '.', ''),
            'currency'        => (string) $plan['currency'],
            'method'          => $method,
            'payer_reference' => $reference !== '' ? mb_substr($reference, 0, 120) : null,
            'paid_at'         => $paidAt !== '' ? $paidAt : gmdate('Y-m-d'),
            'status'          => 'pending',
            'recorded_by'     => $actorId,
        ]);

        AuditService::record('subscription.payment_recorded', 'Manual subscription payment recorded', 'subscription_payment', $paymentId, [
            'subscription_id' => $subscriptionId,
            'amount'          => number_format((float) $amountRaw, 2, '.', ''),
        ]);

        return ['ok' => true, 'message' => 'Payment recorded as pending manual verification.', 'payment_id' => $paymentId];
    }

    /**
     * Administrator verification. Only this activates a paid plan.
     *
     * @return array{ok:bool,message:string}
     */
    public static function verifyPayment(int $paymentId, int $adminId, string $decision, string $note = ''): array
    {
        $payment = SubscriptionPayment::find($paymentId);

        if ($payment === null) {
            return ['ok' => false, 'message' => 'Payment not found.'];
        }

        if ((string) $payment['status'] !== 'pending') {
            return ['ok' => false, 'message' => 'This payment has already been reviewed.'];
        }

        if (!in_array($decision, ['verify', 'reject'], true)) {
            return ['ok' => false, 'message' => 'Invalid decision.'];
        }

        if ($decision === 'reject') {
            SubscriptionPayment::update($paymentId, [
                'status'            => 'rejected',
                'verified_by'       => $adminId,
                'verified_at'       => gmdate('Y-m-d H:i:s'),
                'verification_note' => $note !== '' ? mb_substr($note, 0, 500) : null,
            ]);

            AuditService::record('subscription.payment_rejected', 'Manual subscription payment rejected', 'subscription_payment', $paymentId, ['note' => $note]);

            NotificationService::notify(
                (int) $payment['user_id'],
                'subscription_payment',
                'Subscription payment not verified',
                'Your recorded subscription payment could not be verified.' . ($note !== '' ? ' Note: ' . $note : ''),
                route('subscriptions.index')
            );

            return ['ok' => true, 'message' => 'Payment rejected.'];
        }

        Database::transaction(static function () use ($paymentId, $adminId, $payment): void {
            SubscriptionPayment::update($paymentId, [
                'status'            => 'verified',
                'verified_by'       => $adminId,
                'verified_at'       => gmdate('Y-m-d H:i:s'),
                'verification_note' => 'Manually verified by administrator.',
            ]);

            $subscription = Subscription::find((int) $payment['subscription_id']);

            if ($subscription !== null) {
                $plan = SubscriptionPlan::find((int) $subscription['plan_id']);
                $months = ($plan !== null && (string) $plan['billing_period'] === 'yearly') ? 12 : 1;

                $start = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));

                Subscription::update((int) $subscription['id'], [
                    'status'       => 'active',
                    'started_at'   => $start->format('Y-m-d H:i:s'),
                    'expires_at'   => $start->modify('+' . $months . ' month')->format('Y-m-d H:i:s'),
                    'verified_by'  => $adminId,
                    'verification_note' => 'Activated after manual payment verification.',
                ]);
            }
        });

        AuditService::record('subscription.payment_verified', 'Manual subscription payment verified and plan activated', 'subscription_payment', $paymentId);

        NotificationService::notify(
            (int) $payment['user_id'],
            'subscription_activated',
            'Your plan is now active',
            'Your manual subscription payment was verified and your plan has been activated.',
            route('subscriptions.index')
        );

        return ['ok' => true, 'message' => 'Payment verified and the plan is now active.'];
    }

    /**
     * Administrator verification of a featured project purchase.
     *
     * @return array{ok:bool,message:string}
     */
    public static function verifyFeaturedListing(int $listingId, int $adminId, string $decision): array
    {
        $listing = FeaturedListing::find($listingId);

        if ($listing === null) {
            return ['ok' => false, 'message' => 'Featured listing not found.'];
        }

        if ((string) $listing['status'] !== 'pending') {
            return ['ok' => false, 'message' => 'This listing has already been reviewed.'];
        }

        if ($decision === 'reject') {
            FeaturedListing::update($listingId, ['status' => 'rejected', 'verified_by' => $adminId, 'verified_at' => gmdate('Y-m-d H:i:s')]);
            AuditService::record('featured.rejected', 'Featured listing rejected', 'featured_listing', $listingId);

            return ['ok' => true, 'message' => 'Featured listing rejected.'];
        }

        Database::transaction(static function () use ($listingId, $adminId, $listing): void {
            $now = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
            $ends = $now->modify('+' . (int) $listing['duration_days'] . ' day');

            FeaturedListing::update($listingId, [
                'status'      => 'active',
                'starts_at'   => $now->format('Y-m-d H:i:s'),
                'ends_at'     => $ends->format('Y-m-d H:i:s'),
                'verified_by' => $adminId,
                'verified_at' => $now->format('Y-m-d H:i:s'),
            ]);

            Project::update((int) $listing['project_id'], [
                'is_featured'    => 1,
                'featured_until' => $ends->format('Y-m-d H:i:s'),
            ]);
        });

        AuditService::record('featured.verified', 'Featured listing activated', 'featured_listing', $listingId);

        return ['ok' => true, 'message' => 'Featured placement is now active.'];
    }
}
