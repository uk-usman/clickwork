<?php

declare(strict_types=1);

/**
 * CircleWork Africa — application configuration.
 *
 * Loads environment values (from the project-root .env file) and exposes them
 * as a single immutable configuration array. This file lives OUTSIDE the public
 * web root so credentials are never web-accessible.
 *
 * @return array<string,mixed>
 */

use App\Core\Env;

if (!defined('CIRCLEWORK_ROOT')) {
    define('CIRCLEWORK_ROOT', dirname(__DIR__));
}

Env::load(CIRCLEWORK_ROOT . '/.env');

$debug = Env::bool('APP_DEBUG', false);

return [
    'app' => [
        'name'     => Env::get('APP_NAME', 'CircleWork Africa'),
        'env'      => Env::get('APP_ENV', 'production'),
        'debug'    => $debug,
        'url'      => rtrim((string) Env::get('APP_URL', 'http://localhost'), '/'),
        'timezone' => Env::get('APP_TIMEZONE', 'Africa/Lagos'),
        'locale'   => Env::get('APP_LOCALE', 'en'),
        'key'      => (string) Env::get('APP_KEY', ''),
        'currency' => Env::get('DEFAULT_CURRENCY', 'NGN'),
    ],

    'database' => [
        'host'     => Env::get('DB_HOST', '127.0.0.1'),
        'port'     => Env::int('DB_PORT', 3306),
        'database' => Env::get('DB_DATABASE', 'circlework'),
        'username' => Env::get('DB_USERNAME', 'root'),
        'password' => Env::get('DB_PASSWORD', ''),
        'charset'  => Env::get('DB_CHARSET', 'utf8mb4'),
    ],

    'session' => [
        'name'     => Env::get('SESSION_NAME', 'circlework_session'),
        'secure'   => Env::bool('SESSION_SECURE_COOKIE', false),
        'lifetime' => Env::int('SESSION_LIFETIME', 7200),
    ],

    'security' => [
        'login_max_attempts'   => Env::int('LOGIN_MAX_ATTEMPTS', 5),
        'login_lockout_seconds' => Env::int('LOGIN_LOCKOUT_SECONDS', 900),
        'upload_max_bytes'     => Env::int('UPLOAD_MAX_BYTES', 5 * 1024 * 1024),
        'portfolio_max_bytes'  => Env::int('PORTFOLIO_MAX_BYTES', 2 * 1024 * 1024),
    ],

    'mail' => [
        'driver'       => Env::get('MAIL_DRIVER', 'log'),
        'from_address' => Env::get('MAIL_FROM_ADDRESS', 'no-reply@example.com'),
        'from_name'    => Env::get('MAIL_FROM_NAME', 'CircleWork Africa'),
        'smtp' => [
            'host'       => Env::get('MAIL_SMTP_HOST', ''),
            'port'       => Env::int('MAIL_SMTP_PORT', 587),
            'username'   => Env::get('MAIL_SMTP_USERNAME', ''),
            'password'   => Env::get('MAIL_SMTP_PASSWORD', ''),
            'encryption' => Env::get('MAIL_SMTP_ENCRYPTION', 'tls'),
            'auth'       => Env::bool('MAIL_SMTP_AUTH', true),
        ],
    ],

    'storage' => [
        'root'      => CIRCLEWORK_ROOT . '/storage',
        'documents' => CIRCLEWORK_ROOT . '/storage/documents',
        'uploads'   => CIRCLEWORK_ROOT . '/storage/uploads',
        'logs'      => CIRCLEWORK_ROOT . '/storage/logs',
    ],

    'defaults' => [
        'marketing_city'  => Env::get('DEFAULT_MARKETING_CITY', 'Kano'),
        'marketing_state' => Env::get('DEFAULT_MARKETING_STATE', 'Kano'),
    ],
];
