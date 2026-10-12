<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Request;

interface MiddlewareInterface
{
    /**
     * @param callable(Request):mixed $next
     */
    public function handle(Request $request, callable $next): mixed;
}
