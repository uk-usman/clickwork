<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Session;
use App\Services\ReportService;

/**
 * Reporting suspicious users, projects, reviews, messages or payments.
 */
final class ReportController extends Controller
{
    public function create(Request $request): string
    {
        return $this->dashboardView('dashboard/report', [
            'title'   => 'Report a problem',
            'types'   => ReportService::TYPES,
            'reasons' => ReportService::REASONS,
            'type'    => $request->string('type', 'user'),
            'target'  => $request->int('target'),
        ]);
    }

    public function store(Request $request): mixed
    {
        $result = ReportService::submit(
            (int) Auth::id(),
            $request->string('type'),
            $request->int('target_id'),
            $request->string('reason'),
            $request->string('details')
        );

        if (!$result['ok']) {
            $this->backWithErrors($result['message'], $result['errors'], $request->all(), '/report');
        }

        $this->success($result['message'], 'dashboard');
    }
}
