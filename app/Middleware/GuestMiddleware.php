<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;

/**
 * Only for signed-out visitors.
 */
final class GuestMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, callable $next): mixed
    {
        if (Auth::check()) {
            Response::redirect(route('dashboard'));
        }

        return $next($request);
    }
}
