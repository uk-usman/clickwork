<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Core\Request;
use App\Core\Session;

/**
 * Shared behaviour for administration controllers.
 */
abstract class AdminController extends Controller
{
    /**
     * @param array<string,mixed> $data
     */
    protected function adminView(string $view, array $data = []): string
    {
        $data['adminNavActive'] = $data['adminNavActive'] ?? '';

        return $this->dashboardView($view, $data);
    }

    /**
     * @return array<string,mixed>
     */
    protected function filters(Request $request, array $keys): array
    {
        $filters = [];
        foreach ($keys as $key) {
            $value = $request->string($key);
            if ($value !== '') {
                $filters[$key] = $value;
            }
        }

        return $filters;
    }

    protected function ok(string $message, string $route, array $params = []): never
    {
        Session::flash('success', $message);
        $this->redirectToRoute($route, $params);
    }

    protected function err(string $message, string $route, array $params = []): never
    {
        Session::flash('danger', $message);
        $this->redirectToRoute($route, $params);
    }
}
