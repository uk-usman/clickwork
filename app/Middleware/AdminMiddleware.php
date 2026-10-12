<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Auth;
use App\Core\Logger;
use App\Core\Request;
use App\Core\Response;
use App\Core\View;

/**
 * Administrator-only area (admin or super admin).
 */
final class AdminMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, callable $next): mixed
    {
        if (!Auth::check()) {
            Response::redirect(route('login'));
        }

        if (!Auth::isAdmin()) {
            Logger::security('Unauthorised admin area access attempt', [
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
                'message' => 'You do not have permission to access the administration area.',
            ], 'layouts/public');
        }

        return $next($request);
    }
}
