<?php

declare(strict_types=1);

use App\Core\Database;
use App\Models\Contract;
use App\Models\Review;
use App\Models\User;
use App\Services\ReviewService;

return [
    'a review is only possible once the contract is completed or cancelled' => function (): void {
        $client = Fixtures::user();
        $freelancer = Fixtures::user();
        $active = Fixtures::contract((int) $client['id'], (int) $freelancer['id'], 'active');

        assert_false(ReviewService::eligibleFor((int) $active['id'], (int) $client['id']), 'An active contract must not be reviewable.');

        $result = ReviewService::create((int) $active['id'], (int) $client['id'], 5, 'Great work overall.');
        assert_false($result['ok'], 'The review must be refused while the contract is active.');
    },

    'both parties can review each other after completion' => function (): void {
        $client = Fixtures::user();
        $freelancer = Fixtures::user();
        $contract = Fixtures::contract((int) $client['id'], (int) $freelancer['id'], 'completed');

        $clientReview = ReviewService::create((int) $contract['id'], (int) $client['id'], 5, 'Clear brief and prompt payment.');
        assert_true($clientReview['ok'], 'The client review should succeed.');

        $freelancerReview = ReviewService::create((int) $contract['id'], (int) $freelancer['id'], 4, 'Good communication throughout the project.');
        assert_true($freelancerReview['ok'], 'The freelancer review should succeed.');

        assert_equals(2, (int) Database::scalar('SELECT COUNT(*) FROM `reviews` WHERE `contract_id` = ?', [(int) $contract['id']]), 'Both reviews should be stored.');
    },

    'a reviewer cannot review the same contract twice' => function (): void {
        $client = Fixtures::user();
        $freelancer = Fixtures::user();
        $contract = Fixtures::contract((int) $client['id'], (int) $freelancer['id'], 'completed');

        assert_true(ReviewService::create((int) $contract['id'], (int) $client['id'], 5, 'Excellent experience.')['ok'], 'The first review should succeed.');

        $second = ReviewService::create((int) $contract['id'], (int) $client['id'], 1, 'Attempting a second review.');
        assert_false($second['ok'], 'A duplicate review must be refused.');
        assert_equals(1, (int) Database::scalar('SELECT COUNT(*) FROM `reviews` WHERE `contract_id` = ? AND `reviewer_id` = ?', [(int) $contract['id'], (int) $client['id']]), 'Only one review should exist per reviewer.');
    },

    'a non-participant cannot review a contract' => function (): void {
        $client = Fixtures::user();
        $freelancer = Fixtures::user();
        $outsider = Fixtures::user();
        $contract = Fixtures::contract((int) $client['id'], (int) $freelancer['id'], 'completed');

        $result = ReviewService::create((int) $contract['id'], (int) $outsider['id'], 1, 'Reviewing a contract I was never part of.');
        assert_false($result['ok'], 'A non-participant must not review the contract.');
    },

    'ratings outside one to five are rejected' => function (): void {
        $client = Fixtures::user();
        $freelancer = Fixtures::user();
        $contract = Fixtures::contract((int) $client['id'], (int) $freelancer['id'], 'completed');

        foreach ([0, 6, -1] as $rating) {
            $result = ReviewService::create((int) $contract['id'], (int) $client['id'], $rating, 'Invalid rating attempt.');
            assert_false($result['ok'], 'A rating of ' . $rating . ' must be rejected.');
        }
    },

    'the cached rating aggregate is recalculated from real reviews' => function (): void {
        $client = Fixtures::user();
        $freelancer = Fixtures::user();

        $first = Fixtures::contract((int) $client['id'], (int) $freelancer['id'], 'completed');
        ReviewService::create((int) $first['id'], (int) $client['id'], 5, 'Very strong delivery.');

        $secondClient = Fixtures::user();
        $second = Fixtures::contract((int) $secondClient['id'], (int) $freelancer['id'], 'completed');
        ReviewService::create((int) $second['id'], (int) $secondClient['id'], 3, 'Acceptable work overall.');

        $profile = App\Models\UserProfile::forUser((int) $freelancer['id']);
        assert_true($profile !== null, 'The freelancer profile should exist.');
        assert_equals(2, (int) $profile['rating_count'], 'Two reviews should be counted.');
        assert_equals(4.0, (float) $profile['rating_average'], 'The average of five and three is four.');
    },

    'moderating a review hides it and recalculates the aggregate' => function (): void {
        $client = Fixtures::user();
        $freelancer = Fixtures::user();
        $contract = Fixtures::contract((int) $client['id'], (int) $freelancer['id'], 'completed');

        ReviewService::create((int) $contract['id'], (int) $client['id'], 1, 'Abusive review that moderation should hide.');

        $review = Review::forContractByReviewer((int) $contract['id'], (int) $client['id']);
        assert_true($review !== null, 'The review should exist.');

        Review::update((int) $review['id'], ['status' => 'hidden', 'moderation_note' => 'Hidden during the automated test.']);
        User::recalculateRating((int) $freelancer['id']);

        $profile = App\Models\UserProfile::forUser((int) $freelancer['id']);
        assert_equals(0, (int) $profile['rating_count'], 'A hidden review should not count.');
        assert_equals(0.0, (float) $profile['rating_average'], 'The average should drop to zero.');
    },
];
