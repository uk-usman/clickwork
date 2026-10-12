<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Request;
use App\Models\Dispute;
use App\Models\Review;
use App\Models\Report;
use App\Models\User;
use App\Services\AuditService;
use App\Services\DisputeService;
use App\Services\ReportService;

/**
 * Reviews, reports and disputes moderation.
 */
final class ModerationController extends AdminController
{
    public function reviews(Request $request): string
    {
        $filters = $this->filters($request, ['status']);
        $total = Review::adminCount($filters);
        $meta = Review::paginateMeta($total, 20, $request->int('page', 1));

        return $this->adminView('admin/reviews/index', [
            'title'          => 'Reviews',
            'reviews'        => Review::adminList($filters, $meta['per_page'], $meta['offset']),
            'meta'           => $meta,
            'filters'        => $filters,
            'adminNavActive' => 'reviews',
        ]);
    }

    public function moderateReview(Request $request): never
    {
        $reviewId = (int) ($request->route('id') ?? 0);
        $review = Review::find($reviewId);

        if ($review === null) {
            $this->err('That review could not be found.', 'admin.reviews');
        }

        $status = $request->string('status');
        if (!in_array($status, ['published', 'hidden', 'removed'], true)) {
            $this->err('Please choose a valid review status.', 'admin.reviews');
        }

        Review::update($reviewId, [
            'status'          => $status,
            'moderation_note' => $request->string('note') !== '' ? mb_substr($request->string('note'), 0, 500) : null,
            'moderated_by'    => Auth::id(),
            'moderated_at'    => gmdate('Y-m-d H:i:s'),
        ]);

        User::recalculateRating((int) $review['reviewee_id']);

        AuditService::record('admin.review_moderated', 'Review marked as ' . $status, 'review', $reviewId, ['note' => $request->string('note')]);

        $this->ok('The review has been updated and the rating recalculated.', 'admin.reviews');
    }

    public function reports(Request $request): string
    {
        $filters = $this->filters($request, ['status', 'type']);
        $total = Report::adminCount($filters);
        $meta = Report::paginateMeta($total, 20, $request->int('page', 1));

        return $this->adminView('admin/reports/index', [
            'title'          => 'Reports',
            'reports'        => Report::adminList($filters, $meta['per_page'], $meta['offset']),
            'meta'           => $meta,
            'filters'        => $filters,
            'adminNavActive' => 'reports',
        ]);
    }

    public function resolveReport(Request $request): never
    {
        $result = ReportService::resolve(
            (int) ($request->route('id') ?? 0),
            (int) Auth::id(),
            $request->string('status'),
            $request->string('note')
        );

        $this->ok($result['message'], 'admin.reports');
    }

    public function disputes(Request $request): string
    {
        $filters = $this->filters($request, ['status']);
        $total = Dispute::adminCount($filters);
        $meta = Dispute::paginateMeta($total, 20, $request->int('page', 1));

        return $this->adminView('admin/disputes/index', [
            'title'          => 'Disputes',
            'disputes'       => Dispute::adminList($filters, $meta['per_page'], $meta['offset']),
            'meta'           => $meta,
            'filters'        => $filters,
            'adminNavActive' => 'disputes',
        ]);
    }

    public function showDispute(Request $request): mixed
    {
        $disputeId = (int) ($request->route('id') ?? 0);
        $dispute = Dispute::detailed($disputeId);

        if ($dispute === null) {
            return $this->notFound('That dispute could not be found.');
        }

        return $this->adminView('admin/disputes/show', [
            'title'          => 'Dispute ' . $dispute['reference'],
            'dispute'        => $dispute,
            'messages'       => \App\Models\DisputeMessage::forDispute($disputeId, true),
            'adminNavActive' => 'disputes',
        ]);
    }

    public function resolveDispute(Request $request): never
    {
        $disputeId = (int) ($request->route('id') ?? 0);

        $result = DisputeService::resolve(
            $disputeId,
            (int) Auth::id(),
            $request->string('status'),
            $request->string('note')
        );

        $this->ok($result['message'], 'admin.disputes.show', ['id' => $disputeId]);
    }

    public function replyDispute(Request $request): never
    {
        $disputeId = (int) ($request->route('id') ?? 0);

        $result = DisputeService::addMessage(
            $disputeId,
            (int) Auth::id(),
            $request->string('body'),
            $request->bool('is_internal')
        );

        if (!$result['ok']) {
            $this->err($result['message'], 'admin.disputes.show', ['id' => $disputeId]);
        }

        $this->ok('Message added to the dispute.', 'admin.disputes.show', ['id' => $disputeId]);
    }
}
