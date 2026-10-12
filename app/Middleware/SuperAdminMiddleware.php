<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Auth;
use App\Core\Logger;
use App\Core\Request;
use App\Core\Response;
use App\Core\View;

/**
 * Super administrator only (administrator account management, sensitive settings).
 */
final class SuperAdminMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, callable $next): mixed
    {
        if (!Auth::isSuperAdmin()) {
            Logger::security('Unauthorised super admin access attempt', [
                'user_id' => Auth::id(),
                'path'    => $request->path(),
                'ip'      => $request->ip(),
            ]);

            if ($request->isAjax()) {
                Response::jsonEnvelope(false, null, 'You are not authorised to perform this action.', [], 403);
            }

            Response::status(403);
            return View::render('errors/403', [
                'title'   => 'Access denied',
                'message' => 'This section is restricted to super administrators.',
            ], 'layouts/public');
        }

        return $next($request);
    }
}
