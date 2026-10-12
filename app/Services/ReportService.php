<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Report;

/**
 * User-submitted reports about users, projects, reviews, messages or payments.
 */
final class ReportService
{
    public const TYPES = ['user', 'project', 'review', 'message', 'payment', 'contract'];
    public const REASONS = ['spam', 'fraud', 'abuse', 'misleading', 'payment_issue', 'other'];

    /**
     * @return array{ok:bool,message:string,errors:array<string,string>}
     */
    public static function submit(int $reporterId, string $type, int $targetId, string $reason, string $details): array
    {
        $errors = [];

        if (!in_array($type, self::TYPES, true)) {
            $errors['type'] = 'Please choose a valid report type.';
        }

        if (!in_array($reason, self::REASONS, true)) {
            $errors['reason'] = 'Please choose a valid reason.';
        }

        if ($targetId <= 0) {
            $errors['target'] = 'The item you are reporting could not be identified.';
        }

        if (mb_strlen(trim($details)) < 10) {
            $errors['details'] = 'Please describe the problem in at least 10 characters.';
        }

        if ($errors !== []) {
            return ['ok' => false, 'message' => 'Please correct the highlighted fields.', 'errors' => $errors];
        }

        $reportId = Report::create([
            'reporter_id'     => $reporterId,
            'reportable_type' => $type,
            'reportable_id'   => $targetId,
            'reason'          => $reason,
            'details'         => mb_substr(trim($details), 0, 1500),
            'status'          => 'open',
        ]);

        AuditService::record('report.created', 'Report submitted', 'report', $reportId, [
            'type' => $type,
            'reason' => $reason,
        ]);

        return ['ok' => true, 'message' => 'Thank you. Your report has been submitted for review by our team.', 'errors' => []];
    }

    /**
     * @return array{ok:bool,message:string}
     */
    public static function resolve(int $reportId, int $adminId, string $status, string $note): array
    {
        if (!in_array($status, ['open', 'in_review', 'resolved', 'dismissed'], true)) {
            return ['ok' => false, 'message' => 'Invalid status.'];
        }

        $report = Report::find($reportId);

        if ($report === null) {
            return ['ok' => false, 'message' => 'Report not found.'];
        }

        $isClosed = in_array($status, ['resolved', 'dismissed'], true);

        Report::update($reportId, [
            'status'          => $status,
            'assigned_to'     => $adminId,
            'resolution_note' => $note !== '' ? mb_substr($note, 0, 1000) : null,
            'resolved_by'     => $isClosed ? $adminId : null,
            'resolved_at'     => $isClosed ? gmdate('Y-m-d H:i:s') : null,
        ]);

        AuditService::record('report.' . $status, 'Report status changed to ' . $status, 'report', $reportId, ['note' => $note]);

        NotificationService::notify(
            (int) $report['reporter_id'],
            'report_update',
            'Update on your report',
            'Your report has been marked as ' . str_replace('_', ' ', $status) . '.' . ($note !== '' ? ' Note: ' . $note : ''),
            route('dashboard')
        );

        return ['ok' => true, 'message' => 'Report updated.'];
    }
}
