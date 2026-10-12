<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Contract;
use App\Models\Review;
use App\Models\User;

/**
 * Reviews are only possible for genuine participants of an eligible contract,
 * once per reviewer per contract.
 */
final class ReviewService
{
    /**
     * @return array{ok:bool,message:string,errors:array<string,string>}
     */
    public static function create(int $contractId, int $reviewerId, int $rating, string $comment): array
    {
        $contract = Contract::find($contractId);

        if ($contract === null) {
            return ['ok' => false, 'message' => 'Contract not found.', 'errors' => []];
        }

        $role = Contract::roleOf($contract, $reviewerId);

        if ($role === null) {
            return ['ok' => false, 'message' => 'Only the client and freelancer on a contract can leave a review.', 'errors' => []];
        }

        if (!in_array((string) $contract['status'], ['completed', 'cancelled'], true)) {
            return ['ok' => false, 'message' => 'Reviews become available once the contract is completed or cancelled.', 'errors' => []];
        }

        if (Review::forContractByReviewer($contractId, $reviewerId) !== null) {
            return ['ok' => false, 'message' => 'You have already reviewed this contract.', 'errors' => []];
        }

        $errors = [];

        if ($rating < 1 || $rating > 5) {
            $errors['rating'] = 'Please choose a rating between 1 and 5.';
        }

        $comment = trim($comment);
        if ($comment !== '' && mb_strlen($comment) > 2000) {
            $errors['comment'] = 'Your review must not exceed 2000 characters.';
        }

        $revieweeId = Contract::counterpartyId($contract, $reviewerId);

        if ($revieweeId === $reviewerId) {
            $errors['review'] = 'You cannot review yourself.';
        }

        if ($errors !== []) {
            return ['ok' => false, 'message' => 'Please correct the highlighted fields.', 'errors' => $errors];
        }

        Review::create([
            'contract_id'   => $contractId,
            'reviewer_id'   => $reviewerId,
            'reviewee_id'   => $revieweeId,
            'reviewer_role' => $role,
            'rating'        => $rating,
            'comment'       => $comment !== '' ? $comment : null,
            'status'        => 'published',
        ]);

        User::recalculateRating($revieweeId);

        AuditService::record('review.created', 'Review submitted', 'review', null, [
            'contract_id' => $contractId,
            'rating'      => $rating,
        ]);

        NotificationService::notify(
            $revieweeId,
            'review_received',
            'You received a new review',
            'A ' . $rating . '-star review was left for contract ' . $contract['reference'] . '.',
            route('contracts.show', ['id' => $contractId])
        );

        return ['ok' => true, 'message' => 'Thank you. Your review has been published.', 'errors' => []];
    }

    public static function eligibleFor(int $contractId, int $userId): bool
    {
        $contract = Contract::find($contractId);

        if ($contract === null || Contract::roleOf($contract, $userId) === null) {
            return false;
        }

        if (!in_array((string) $contract['status'], ['completed', 'cancelled'], true)) {
            return false;
        }

        return Review::forContractByReviewer($contractId, $userId) === null;
    }
}
