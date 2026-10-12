<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;

/**
 * Requires an authenticated, active account.
 */
final class AuthMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, callable $next): mixed
    {
        if (!Auth::check()) {
            if ($request->isAjax()) {
                Response::jsonEnvelope(false, null, 'Authentication required.', [], 401);
            }

            Session::put('intended_url', $request->path());
            Session::flash('warning', 'Please sign in to continue.');

            Response::redirect(route('login'));
        }

        $user = Auth::user();

        if ($user !== null && $user['status'] === 'suspended') {
            Auth::logout();
            Session::flash('danger', 'Your account has been suspended. Please contact support.');
            Response::redirect(route('login'));
        }

        return $next($request);
    }
}
