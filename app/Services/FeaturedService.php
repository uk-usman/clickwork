<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\FeaturedListing;
use App\Models\Project;
use App\Models\Setting;

/**
 * Featured project placements (paid add-on, manually verified).
 */
final class FeaturedService
{
    public static function price(): string
    {
        return (string) (Setting::get('featured_price', '1000.00') ?? '1000.00');
    }

    public static function durationDays(): int
    {
        return max(1, (int) (Setting::get('featured_duration_days', '14') ?? '14'));
    }

    /**
     * @return array{ok:bool,message:string,listing_id?:int}
     */
    public static function request(int $projectId, int $userId, string $method, string $reference): array
    {
        $project = Project::find($projectId);

        if ($project === null) {
            return ['ok' => false, 'message' => 'Project not found.'];
        }

        if ((int) $project['client_id'] !== $userId) {
            return ['ok' => false, 'message' => 'You do not own this project.'];
        }

        if (!in_array((string) $project['status'], ['published', 'in_progress'], true)) {
            return ['ok' => false, 'message' => 'Only a published project can be featured.'];
        }

        if (!in_array($method, ['bank_transfer', 'cash', 'mobile_money', 'card_transfer', 'other'], true)) {
            return ['ok' => false, 'message' => 'Please choose a valid payment method.'];
        }

        $pending = FeaturedListing::findBy(['project_id' => $projectId, 'status' => 'pending']);

        if ($pending !== null) {
            return ['ok' => false, 'message' => 'A featured listing request for this project is already awaiting verification.'];
        }

        $listingId = FeaturedListing::create([
            'project_id'     => $projectId,
            'purchased_by'   => $userId,
            'amount'         => self::price(),
            'currency'       => (string) $project['currency'],
            'duration_days'  => self::durationDays(),
            'status'         => 'pending',
            'payment_method' => $method,
            'payer_reference' => $reference !== '' ? mb_substr($reference, 0, 120) : null,
            'recorded_by'    => $userId,
        ]);

        AuditService::record('featured.requested', 'Featured listing requested', 'featured_listing', $listingId, ['project_id' => $projectId]);

        return [
            'ok'         => true,
            'message'    => 'Your featured placement request has been recorded. An administrator will verify your manual payment before it becomes active.',
            'listing_id' => $listingId,
        ];
    }
}
