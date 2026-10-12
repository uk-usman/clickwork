<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Logger;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;

/**
 * CSRF protection for every state-changing request.
 */
final class CsrfMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, callable $next): mixed
    {
        if (in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            $token = $request->input('_token');
            $token = is_string($token) ? $token : null;

            if (!Session::verifyCsrf($token)) {
                Logger::security('CSRF token validation failed', [
                    'path' => $request->path(),
                    'ip'   => $request->ip(),
                ]);

                if ($request->isAjax()) {
                    Response::jsonEnvelope(false, null, 'Your session has expired. Please refresh the page.', [], 419);
                }

                Session::flash('danger', 'Your session has expired or the request could not be verified. Please try again.');
                Response::redirect(route('home'));
            }
        }

        return $next($request);
    }
}
