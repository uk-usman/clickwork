<?php

declare(strict_types=1);

namespace App\Core;

use Throwable;

/**
 * Application bootstrap: configuration, error handling, shared services.
 */
final class App
{
    /** @var array<string,mixed> */
    private static array $config = [];

    private static ?Router $router = null;

    private static bool $booted = false;

    /**
     * @param array<string,mixed> $config
     */
    public static function boot(array $config): void
    {
        if (self::$booted) {
            return;
        }

        self::$config = $config;
        self::$booted = true;

        $debug = (bool) ($config['app']['debug'] ?? false);

        // Production-safe error handling.
        error_reporting(E_ALL);
        ini_set('display_errors', $debug ? '1' : '0');
        ini_set('display_startup_errors', $debug ? '1' : '0');
        ini_set('log_errors', '1');
        ini_set('error_log', (string) ($config['storage']['logs'] ?? sys_get_temp_dir()) . '/php-error.log');

        date_default_timezone_set('UTC');

        Logger::setPath((string) ($config['storage']['logs'] ?? sys_get_temp_dir()));
        Database::configure((array) ($config['database'] ?? []));
        View::setPath(CIRCLEWORK_ROOT . '/resources/views');

        set_error_handler(static function (int $severity, string $message, string $file = '', int $line = 0): bool {
            if ((error_reporting() & $severity) === 0) {
                return false;
            }
            throw new \ErrorException($message, 0, $severity, $file, $line);
        });

        set_exception_handler(static function (Throwable $e) use ($config): void {
            self::handleException($e, $config);
        });

        register_shutdown_function(static function (): void {
            $error = error_get_last();
            if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
                Logger::error('Fatal error: ' . $error['message'], [
                    'file' => $error['file'],
                    'line' => $error['line'],
                ]);
            }
        });
    }

    /**
     * @param array<string,mixed> $config
     */
    private static function handleException(Throwable $e, array $config): void
    {
        Logger::error($e->getMessage(), [
            'exception' => $e::class,
            'file'      => $e->getFile(),
            'line'      => $e->getLine(),
            'trace'     => $debug = (bool) ($config['app']['debug'] ?? false)
                ? array_slice(explode("\n", $e->getTraceAsString()), 0, 12)
                : '[hidden]',
        ]);

        if (headers_sent()) {
            echo 'An unexpected error occurred.';
            return;
        }

        http_response_code(500);

        $isAjax = strtolower((string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest';

        if ($isAjax) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'ok'      => false,
                'message' => 'An unexpected server error occurred.',
                'data'    => null,
                'errors'  => [],
            ]);
            return;
        }

        $payload = [
            'title'   => 'Something went wrong',
            'message' => 'An unexpected server error occurred. The issue has been logged.',
            'debug'   => $debug,
            'detail'  => $debug ? $e->getMessage() : null,
            'trace'   => $debug ? $e->getTraceAsString() : null,
        ];

        try {
            echo View::render('errors/500', $payload, 'layouts/public');
        } catch (Throwable) {
            echo '<h1>500 — Server Error</h1><p>An unexpected error occurred.</p>';
        }
    }

    /**
     * @return array<string,mixed>
     */
    public static function config(): array
    {
        return self::$config;
    }

    public static function configValue(string $key, mixed $default = null): mixed
    {
        $segments = explode('.', $key);
        $value = self::$config;

        foreach ($segments as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }
            $value = $value[$segment];
        }

        return $value;
    }

    public static function router(): Router
    {
        if (!self::$router instanceof Router) {
            self::$router = new Router();
        }

        return self::$router;
    }

    public static function setRouter(Router $router): void
    {
        self::$router = $router;
    }

    public static function isBooted(): bool
    {
        return self::$booted;
    }
}
