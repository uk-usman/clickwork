<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Immutable-ish wrapper around the current HTTP request.
 */
final class Request
{
    private string $method;
    private string $path;
    /** @var array<string,mixed> */
    private array $query;
    /** @var array<string,mixed> */
    private array $body;
    /** @var array<string,mixed> */
    private array $files;
    /** @var array<string,mixed> */
    private array $server;
    /** @var array<string,mixed> */
    private array $routeParams = [];

    /**
     * @param array<string,mixed> $query
     * @param array<string,mixed> $body
     * @param array<string,mixed> $files
     * @param array<string,mixed> $server
     */
    public function __construct(
        string $method,
        string $path,
        array $query = [],
        array $body = [],
        array $files = [],
        array $server = []
    ) {
        $this->method = strtoupper($method);
        $this->path = $path;
        $this->query = $query;
        $this->body = $body;
        $this->files = $files;
        $this->server = $server;
    }

    public static function capture(): self
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

        // Support method spoofing for HTML forms: <input name="_method" value="PUT">
        if ($method === 'POST' && isset($_POST['_method'])) {
            $spoofed = strtoupper((string) $_POST['_method']);
            if (in_array($spoofed, ['PUT', 'PATCH', 'DELETE'], true)) {
                $method = $spoofed;
            }
        }

        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $path = parse_url($uri, PHP_URL_PATH);
        $path = is_string($path) ? $path : '/';

        // Normalise: strip the application base path when hosted in a sub-folder.
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
        if ($scriptName !== '') {
            $base = rtrim(str_replace('\\', '/', dirname($scriptName)), '/');
            if ($base !== '' && $base !== '/' && str_starts_with($path, $base)) {
                $path = substr($path, strlen($base));
            }
        }

        $path = '/' . ltrim(rawurldecode($path), '/');
        $path = rtrim($path, '/');
        if ($path === '') {
            $path = '/';
        }

        return new self($method, $path, $_GET, $_POST, $_FILES, $_SERVER);
    }

    public function method(): string
    {
        return $this->method;
    }

    public function path(): string
    {
        return $this->path;
    }

    public function isPost(): bool
    {
        return $this->method === 'POST';
    }

    public function isGet(): bool
    {
        return $this->method === 'GET';
    }

    /**
     * @return array<string,mixed>
     */
    public function all(): array
    {
        return array_merge($this->query, $this->body);
    }

    public function input(string $key, mixed $default = null): mixed
    {
        return $this->body[$key] ?? $this->query[$key] ?? $default;
    }

    public function string(string $key, string $default = ''): string
    {
        $value = $this->input($key, $default);

        if (is_array($value)) {
            return $default;
        }

        return trim((string) $value);
    }

    public function int(string $key, int $default = 0): int
    {
        $value = $this->input($key);

        if ($value === null || $value === '' || is_array($value)) {
            return $default;
        }

        return (int) $value;
    }

    public function bool(string $key): bool
    {
        $value = $this->input($key);

        if (is_array($value)) {
            return false;
        }

        return in_array(strtolower((string) $value), ['1', 'true', 'on', 'yes'], true);
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->body) || array_key_exists($key, $this->query);
    }

    /**
     * @return array<string,mixed>
     */
    public function only(array $keys): array
    {
        $out = [];
        foreach ($keys as $key) {
            if ($this->has($key)) {
                $out[$key] = $this->input($key);
            }
        }

        return $out;
    }

    /**
     * @return array<string,mixed>|null
     */
    public function file(string $key): ?array
    {
        $file = $this->files[$key] ?? null;

        if (!is_array($file) || !isset($file['tmp_name'])) {
            return null;
        }

        return $file;
    }

    public function isAjax(): bool
    {
        $requestedWith = $this->server['HTTP_X_REQUESTED_WITH'] ?? '';
        $accept = $this->server['HTTP_ACCEPT'] ?? '';

        return strtolower((string) $requestedWith) === 'xmlhttprequest'
            || str_contains((string) $accept, 'application/json');
    }

    public function ip(): string
    {
        return (string) ($this->server['REMOTE_ADDR'] ?? '0.0.0.0');
    }

    public function userAgent(): string
    {
        return substr((string) ($this->server['HTTP_USER_AGENT'] ?? ''), 0, 255);
    }

    /**
     * @param array<string,string> $params
     */
    public function setRouteParams(array $params): void
    {
        $this->routeParams = $params;
    }

    public function route(string $key, ?string $default = null): ?string
    {
        return $this->routeParams[$key] ?? $default;
    }

    /**
     * @return array<string,string>
     */
    public function routeParams(): array
    {
        return $this->routeParams;
    }
}
