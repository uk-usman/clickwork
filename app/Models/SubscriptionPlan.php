<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class SubscriptionPlan extends Model
{
    protected static string $table = 'subscription_plans';

    /**
     * @return array<int,array<string,mixed>>
     */
    public static function activeFor(string $audience): array
    {
        return Database::select(
            'SELECT * FROM `subscription_plans`
             WHERE `is_active` = 1 AND `audience` IN (?, \'both\')
             ORDER BY `sort_order` ASC, `price` ASC',
            [$audience]
        );
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public static function allOrdered(): array
    {
        return Database::select('SELECT * FROM `subscription_plans` ORDER BY `sort_order` ASC, `price` ASC');
    }

    /**
     * @return array<string,mixed>|null
     */
    public static function findByCode(string $code): ?array
    {
        return self::findBy(['code' => $code]);
    }

    /**
     * @return array<string,mixed>|null
     */
    public static function freePlan(): ?array
    {
        return self::findBy(['is_free' => 1, 'is_active' => 1]);
    }

    /**
     * @return array<int,mixed>
     */
    public static function features(int $planId): array
    {
        $plan = self::find($planId);
        if ($plan === null || $plan['features'] === null) {
            return [];
        }
        $decoded = json_decode((string) $plan['features'], true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * @return array<string,mixed>
     */
    public static function limits(int $planId): array
    {
        $plan = self::find($planId);
        if ($plan === null || $plan['limits'] === null) {
            return [];
        }
        $decoded = json_decode((string) $plan['limits'], true);

        return is_array($decoded) ? $decoded : [];
    }
}
