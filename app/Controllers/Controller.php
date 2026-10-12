<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\View;

/**
 * Shared controller behaviour: rendering, redirects, flash messages and
 * object-level authorization helpers.
 */
abstract class Controller
{
    /**
     * @param array<string,mixed> $data
     */
    protected function view(string $view, array $data = [], ?string $layout = null): string
    {
        $data['currentUser'] = Auth::user();
        $data['flashMessages'] = Session::pullFlash();
        $data['errors'] = Session::pullErrors();
        $data['activeMode'] = Auth::activeMode();

        $html = View::render($view, $data, $layout);

        // Old input stays available while the view renders, then is discarded so a
        // refresh does not repopulate a form with stale values.
        Session::pullOld();

        return $html;
    }

    /**
     * @param array<string,mixed> $data
     */
    protected function dashboardView(string $view, array $data = []): string
    {
        $data['unreadNotifications'] = Auth::check()
            ? \App\Models\Notification::unreadCount((int) Auth::id())
            : 0;
        $data['unreadMessages'] = Auth::check()
            ? \App\Models\Message::unreadCount((int) Auth::id())
            : 0;

        return $this->view($view, $data, 'layouts/dashboard');
    }

    protected function redirect(string $path): never
    {
        Response::redirect(url($path));
    }

    protected function redirectToRoute(string $name, array $params = []): never
    {
        Response::redirect(route($name, $params));
    }

    protected function back(string $fallback = '/'): never
    {
        Response::redirect(url($fallback));
    }

    protected function success(string $message, string $route, array $params = []): never
    {
        Session::flash('success', $message);
        $this->redirectToRoute($route, $params);
    }

    protected function failure(string $message, string $route, array $params = []): never
    {
        Session::flash('danger', $message);
        $this->redirectToRoute($route, $params);
    }

    /**
     * @param array<string,string> $errors
     * @param array<string,mixed>  $old
     */
    protected function backWithErrors(string $message, array $errors, array $old, string $fallback = '/'): never
    {
        Session::flash('danger', $message);
        Session::flashErrors($errors);
        Session::flashInput($old);
        Response::redirect(url($fallback));
    }

    protected function notFound(string $message = 'The requested page could not be found.'): string
    {
        Response::status(404);

        return $this->view('errors/404', [
            'title'   => 'Page not found',
            'message' => $message,
        ], 'layouts/public');
    }

    protected function forbidden(string $message = 'You do not have permission to access this resource.'): string
    {
        Response::status(403);

        return $this->view('errors/403', [
            'title'   => 'Access denied',
            'message' => $message,
        ], 'layouts/public');
    }

    /**
     * Object-level authorization: fetch a contract only when the current user
     * is a participant.
     *
     * @return array<string,mixed>|null
     */
    protected function participantContract(int $contractId): ?array
    {
        $userId = Auth::id();

        if ($userId === null) {
            return null;
        }

        $contract = \App\Models\Contract::find($contractId);

        if ($contract === null || \App\Models\Contract::roleOf($contract, (int) $userId) === null) {
            \App\Core\Logger::security('Blocked unauthorised contract access', [
                'contract_id' => $contractId,
                'user_id'     => $userId,
                'path'        => Request::capture()->path(),
            ]);

            return null;
        }

        return $contract;
    }
}
