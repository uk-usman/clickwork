<?php

declare(strict_types=1);

/**
 * Global helper functions.
 *
 * These are deliberately small and side-effect free so they can be used
 * inside every view without importing a class.
 */

use App\Core\App;
use App\Core\Auth;
use App\Core\Session;

if (!function_exists('e')) {
    /**
     * Escape output for HTML context (XSS protection).
     */
    function e(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if (is_array($value) || is_object($value)) {
            return htmlspecialchars(json_encode($value, JSON_UNESCAPED_UNICODE) ?: '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        }

        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

if (!function_exists('app_url')) {
    /**
     * Build an absolute application URL from the configured base URL.
     */
    function app_url(string $path = ''): string
    {
        $base = rtrim((string) App::configValue('app.url', ''), '/');
        $path = '/' . ltrim($path, '/');

        return $base . ($path === '/' ? '' : $path);
    }
}

if (!function_exists('url')) {
    /**
     * Path-only URL (safe for links and redirects within the app).
     */
    function url(string $path = '/'): string
    {
        $path = '/' . ltrim($path, '/');

        return $path === '/' ? '/' : rtrim($path, '/');
    }
}

if (!function_exists('asset')) {
    /**
     * Asset URL with cache-busting query string.
     */
    function asset(string $path): string
    {
        $path = '/' . ltrim($path, '/');
        $file = CIRCLEWORK_ROOT . '/public' . $path;
        $version = is_file($file) ? (string) filemtime($file) : '1';

        return app_url($path) . '?v=' . $version;
    }
}

if (!function_exists('route')) {
    /**
     * Named-route URL generation.
     *
     * @param array<string,string|int> $params
     */
    function route(string $name, array $params = []): string
    {
        $router = App::router();

        if (!$router->has($name)) {
            return url('/');
        }

        return $router->url($name, $params);
    }
}

if (!function_exists('redirect_to')) {
    /**
     * Redirect using a named route or a raw path.
     *
     * @param array<string,string|int> $params
     */
    function redirect_to(string $nameOrPath, array $params = []): never
    {
        if (App::router()->has($nameOrPath)) {
            \App\Core\Response::redirect(route($nameOrPath, $params));
        }

        \App\Core\Response::redirect(url($nameOrPath));
    }
}

if (!function_exists('csrf_token')) {
    function csrf_token(): string
    {
        return Session::csrfToken();
    }
}

if (!function_exists('csrf_field')) {
    function csrf_field(): string
    {
        return '<input type="hidden" name="_token" value="' . e(csrf_token()) . '">';
    }
}

if (!function_exists('method_field')) {
    function method_field(string $method): string
    {
        return '<input type="hidden" name="_method" value="' . e(strtoupper($method)) . '">';
    }
}

if (!function_exists('old')) {
    function old(string $key, string $default = ''): string
    {
        return e(Session::old($key, $default));
    }
}

if (!function_exists('current_user')) {
    /**
     * @return array<string,mixed>|null
     */
    function current_user(): ?array
    {
        return Auth::user();
    }
}


if (!function_exists('money')) {
    /**
     * Format a decimal money string/float for display in a given currency.
     */
    function money(mixed $amount, string $currency = 'NGN'): string
    {
        $symbols = [
            'NGN' => '₦',
            'USD' => '$',
            'GBP' => '£',
            'EUR' => '€',
        ];

        $symbol = $symbols[$currency] ?? ($currency . ' ');

        if ($amount === null || $amount === '') {
            return $symbol . '0';
        }

        $value = (float) $amount;
        $decimals = ($value === floor($value)) ? 0 : 2;

        return $symbol . number_format($value, $decimals);
    }
}

if (!function_exists('setting')) {
    function setting(string $key, ?string $default = null): ?string
    {
        return \App\Models\Setting::get($key, $default);
    }
}

if (!function_exists('dt')) {
    /**
     * Format a stored UTC timestamp for display in the application timezone.
     */
    function dt(?string $utcTimestamp, string $format = 'd M Y, H:i'): string
    {
        if ($utcTimestamp === null || $utcTimestamp === '' || str_starts_with($utcTimestamp, '0000')) {
            return '—';
        }

        try {
            $date = new DateTimeImmutable($utcTimestamp, new DateTimeZone('UTC'));
            $date = $date->setTimezone(new DateTimeZone((string) App::configValue('app.timezone', 'Africa/Lagos')));
        } catch (Throwable) {
            return '—';
        }

        return $date->format($format);
    }
}

if (!function_exists('now_utc')) {
    function now_utc(): string
    {
        return gmdate('Y-m-d H:i:s');
    }
}

if (!function_exists('str_excerpt')) {
    function str_excerpt(?string $text, int $length = 140): string
    {
        $text = trim(preg_replace('/\s+/', ' ', (string) $text) ?? '');

        if (mb_strlen($text) <= $length) {
            return $text;
        }

        return rtrim(mb_substr($text, 0, $length - 1)) . '…';
    }
}

if (!function_exists('initials')) {
    function initials(?string $name): string
    {
        $name = trim((string) $name);
        if ($name === '') {
            return '?';
        }

        $parts = preg_split('/\s+/', $name) ?: [];
        $letters = '';
        foreach (array_slice($parts, 0, 2) as $part) {
            $letters .= mb_strtoupper(mb_substr($part, 0, 1));
        }

        return $letters !== '' ? $letters : '?';
    }
}

if (!function_exists('active_if')) {
    /**
     * Returns 'active' when the current request path matches, for navigation.
     */
    function active_if(string $path, string $class = 'active'): string
    {
        $current = \App\Core\Request::capture()->path();
        $path = '/' . trim($path, '/');

        if ($path === '/') {
            return $current === '/' ? $class : '';
        }

        return str_starts_with($current, $path) ? $class : '';
    }
}

if (!function_exists('status_badge_class')) {
    function status_badge_class(string $status): string
    {
        return match ($status) {
            'active', 'approved', 'published', 'completed', 'confirmed', 'accepted', 'paid', 'open'
                => 'text-bg-success',
            'pending', 'draft', 'submitted', 'reported', 'in_review', 'in_progress', 'awaiting_acceptance', 'revision_requested'
                => 'text-bg-warning',
            'rejected', 'suspended', 'cancelled', 'disputed', 'reversed', 'closed', 'withdrawn', 'declined'
                => 'text-bg-danger',
            'featured' => 'text-bg-primary',
            default    => 'text-bg-secondary',
        };
    }
}

if (!function_exists('humanize')) {
    function humanize(?string $value): string
    {
        return ucwords(str_replace('_', ' ', (string) $value));
    }
}

if (!function_exists('bootstrap_css')) {
    /**
     * Local Bootstrap stylesheet when present, otherwise the configurable CDN
     * fallback. See README for how to install local assets.
     */
    function bootstrap_css(): string
    {
        $local = '/assets/vendor/bootstrap/css/bootstrap.min.css';

        if (is_file(CIRCLEWORK_ROOT . '/public' . $local)) {
            return '<link rel="stylesheet" href="' . e(asset($local)) . '">';
        }

        return '<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"'
            . ' integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">';
    }
}

if (!function_exists('bootstrap_js')) {
    function bootstrap_js(): string
    {
        $local = '/assets/vendor/bootstrap/js/bootstrap.bundle.min.js';

        if (is_file(CIRCLEWORK_ROOT . '/public' . $local)) {
            return '<script src="' . e(asset($local)) . '" defer></script>';
        }

        return '<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"'
            . ' integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous" defer></script>';
    }
}

if (!function_exists('bootstrap_icons')) {
    function bootstrap_icons(): string
    {
        $local = '/assets/vendor/bootstrap-icons/bootstrap-icons.css';

        if (is_file(CIRCLEWORK_ROOT . '/public' . $local)) {
            return '<link rel="stylesheet" href="' . e(asset($local)) . '">';
        }

        return '<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">';
    }
}

if (!function_exists('avatar_url')) {
    /**
     * Avatar image URL or null when the user has no uploaded photo.
     */
    function avatar_url(?string $path): ?string
    {
        if ($path === null || $path === '') {
            return null;
        }

        return app_url('/media/avatar/' . rawurlencode($path));
    }
}

if (!function_exists('portfolio_image_url')) {
    function portfolio_image_url(?string $path): ?string
    {
        if ($path === null || $path === '') {
            return null;
        }

        return app_url('/media/portfolio/' . rawurlencode($path));
    }
}
