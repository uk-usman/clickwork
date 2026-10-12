<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Simple route dispatcher with named routes, route parameters and middleware.
 */
final class Router
{
    /** @var array<int,array{method:string,path:string,regex:string,vars:string[],handler:mixed,name:?string,middleware:array<int,string>}> */
    private array $routes = [];

    /** @var array<string,string> */
    private array $named = [];

    /** @var array<int,string> */
    private array $groupMiddleware = [];

    private string $groupPrefix = '';

    public function get(string $path, mixed $handler, ?string $name = null, array $middleware = []): self
    {
        return $this->add('GET', $path, $handler, $name, $middleware);
    }

    public function post(string $path, mixed $handler, ?string $name = null, array $middleware = []): self
    {
        return $this->add('POST', $path, $handler, $name, $middleware);
    }

    public function put(string $path, mixed $handler, ?string $name = null, array $middleware = []): self
    {
        return $this->add('PUT', $path, $handler, $name, $middleware);
    }

    public function delete(string $path, mixed $handler, ?string $name = null, array $middleware = []): self
    {
        return $this->add('DELETE', $path, $handler, $name, $middleware);
    }

    /**
     * @param array<int,string> $middleware
     */
    public function group(array $middleware, callable $callback, string $prefix = ''): void
    {
        $previousMiddleware = $this->groupMiddleware;
        $previousPrefix = $this->groupPrefix;

        $this->groupMiddleware = array_merge($this->groupMiddleware, $middleware);
        $this->groupPrefix = rtrim($previousPrefix . $prefix, '/');

        $callback($this);

        $this->groupMiddleware = $previousMiddleware;
        $this->groupPrefix = $previousPrefix;
    }

    /**
     * @param array<int,string> $middleware
     */
    private function add(string $method, string $path, mixed $handler, ?string $name, array $middleware): self
    {
        $full = rtrim($this->groupPrefix . $path, '/');
        if ($full === '') {
            $full = '/';
        }

        $vars = [];
        $regex = preg_replace_callback(
            '#\{([a-zA-Z_][a-zA-Z0-9_]*)\}#',
            static function (array $m) use (&$vars): string {
                $vars[] = $m[1];
                return '([^/]+)';
            },
            $full
        );

        $this->routes[] = [
            'method'     => $method,
            'path'       => $full,
            'regex'      => '#^' . $regex . '$#',
            'vars'       => $vars,
            'handler'    => $handler,
            'name'       => $name,
            'middleware' => array_merge($this->groupMiddleware, $middleware),
        ];

        if ($name !== null) {
            $this->named[$name] = $full;
        }

        return $this;
    }

    /**
     * @param array<string,string> $params
     */
    public function url(string $name, array $params = []): string
    {
        $path = $this->named[$name] ?? '/';

        foreach ($params as $key => $value) {
            $path = str_replace('{' . $key . '}', rawurlencode((string) $value), $path);
        }

        return $path;
    }

    public function has(string $name): bool
    {
        return isset($this->named[$name]);
    }

    public function dispatch(Request $request): mixed
    {
        $method = $request->method();
        $path = $request->path();
        $methodMismatch = false;

        foreach ($this->routes as $route) {
            if (preg_match($route['regex'], $path, $matches) !== 1) {
                continue;
            }

            if ($route['method'] !== $method) {
                $methodMismatch = true;
                continue;
            }

            array_shift($matches);
            $params = [];
            foreach ($route['vars'] as $index => $varName) {
                $params[$varName] = $matches[$index] ?? '';
            }
            $request->setRouteParams($params);

            return $this->runPipeline($request, $route['middleware'], $route['handler']);
        }

        if ($methodMismatch) {
            Response::status(405);
            return View::render('errors/405', ['title' => 'Method Not Allowed'], 'layouts/public');
        }

        Response::status(404);
        return View::render('errors/404', ['title' => 'Page Not Found'], 'layouts/public');
    }

    /**
     * @param array<int,string> $middleware
     */
    private function runPipeline(Request $request, array $middleware, mixed $handler): mixed
    {
        $core = function (Request $req) use ($handler): mixed {
            if (is_array($handler) && is_string($handler[0])) {
                $controller = new $handler[0]();
                return $controller->{$handler[1]}($req);
            }

            if (is_callable($handler)) {
                return $handler($req);
            }

            throw new \RuntimeException('Invalid route handler.');
        };

        $pipeline = array_reduce(
            array_reverse($middleware),
            static function (callable $next, string $middlewareClass): callable {
                return static function (Request $req) use ($next, $middlewareClass): mixed {
                    /** @var \App\Middleware\MiddlewareInterface $instance */
                    $instance = new $middlewareClass();

                    return $instance->handle($req, $next);
                };
            },
            $core
        );

        return $pipeline($request);
    }
}
