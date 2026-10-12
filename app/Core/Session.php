<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Secure session wrapper: hardened cookie settings, flash data, CSRF token.
 */
final class Session
{
    private static bool $started = false;

    /**
     * @param array<string,mixed> $config
     */
    public static function start(array $config = []): void
    {
        if (self::$started || session_status() === PHP_SESSION_ACTIVE) {
            self::$started = true;
            return;
        }

        $name = (string) ($config['name'] ?? 'circlework_session');
        $secure = (bool) ($config['secure'] ?? false);
        $lifetime = (int) ($config['lifetime'] ?? 7200);

        // Detect HTTPS (also honouring a reverse proxy header when set).
        $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');

        session_name($name);

        $params = [
            'lifetime' => 0,
            'path'     => '/',
            'domain'   => '',
            'secure'   => $secure || $https,
            'httponly' => true,
            'samesite' => 'Lax',
        ];

        if (PHP_VERSION_ID >= 70300) {
            session_set_cookie_params($params);
        } else {
            session_set_cookie_params(
                $params['lifetime'],
                $params['path'] . '; samesite=' . $params['samesite'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }

        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        ini_set('session.gc_maxlifetime', (string) max($lifetime, 3600));

        session_start();
        self::$started = true;

        if (!isset($_SESSION['_created_at'])) {
            $_SESSION['_created_at'] = time();
        }
    }

    public static function regenerate(bool $deleteOld = true): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id($deleteOld);
        }
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }

    public static function put(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    public static function has(string $key): bool
    {
        return isset($_SESSION[$key]);
    }

    public static function forget(string $key): void
    {
        unset($_SESSION[$key]);
    }

    public static function flush(): void
    {
        $_SESSION = [];
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
    }

    // ------------------------------------------------------------------
    // Flash messages
    // ------------------------------------------------------------------

    /**
     * @param array<string,mixed> $params
     */
    public static function flash(string $type, string $message, array $params = []): void
    {
        $_SESSION['_flash'][] = [
            'type'    => $type,
            'message' => $message,
            'params'  => $params,
        ];
    }

    /**
     * @return array<int,array{type:string,message:string,params:array<string,mixed>}>
     */
    public static function pullFlash(): array
    {
        $messages = $_SESSION['_flash'] ?? [];
        unset($_SESSION['_flash']);

        return is_array($messages) ? $messages : [];
    }

    // ------------------------------------------------------------------
    // Old input (for re-populating forms after a validation failure)
    // ------------------------------------------------------------------

    /**
     * @param array<string,mixed> $input
     */
    public static function flashInput(array $input): void
    {
        unset($input['password'], $input['password_confirmation'], $input['_token'], $input['current_password']);
        $_SESSION['_old'] = $input;
    }

    public static function old(string $key, string $default = ''): string
    {
        $old = $_SESSION['_old'] ?? [];
        $value = $old[$key] ?? $default;

        return is_scalar($value) ? (string) $value : $default;
    }

    public static function pullOld(): void
    {
        unset($_SESSION['_old']);
    }

    /**
     * @param array<string,string> $errors
     */
    public static function flashErrors(array $errors): void
    {
        $_SESSION['_errors'] = $errors;
    }

    /**
     * @return array<string,string>
     */
    public static function pullErrors(): array
    {
        $errors = $_SESSION['_errors'] ?? [];
        unset($_SESSION['_errors']);

        return is_array($errors) ? $errors : [];
    }

    // ------------------------------------------------------------------
    // CSRF
    // ------------------------------------------------------------------

    public static function csrfToken(): string
    {
        if (empty($_SESSION['_csrf_token']) || !is_string($_SESSION['_csrf_token'])) {
            $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['_csrf_token'];
    }

    public static function verifyCsrf(?string $token): bool
    {
        $expected = $_SESSION['_csrf_token'] ?? '';

        if (!is_string($expected) || $expected === '' || !is_string($token) || $token === '') {
            return false;
        }

        return hash_equals($expected, $token);
    }

    // ------------------------------------------------------------------
    // Login throttling (simple, session-independent counter store)
    // ------------------------------------------------------------------

    public static function throttleKey(string $prefix, string $identifier): string
    {
        return $prefix . ':' . strtolower(trim($identifier));
    }
}
