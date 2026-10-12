<?php

/**
 * CircleWork Africa — front controller.
 *
 * The only PHP file that should be web-accessible. Everything else
 * (application code, configuration, storage) lives outside the web root
 * wherever the hosting layout allows it.
 */

declare(strict_types=1);

define('CIRCLEWORK_ROOT', dirname(__DIR__));

require CIRCLEWORK_ROOT . '/app/Core/Autoloader.php';

App\Core\Autoloader::register(CIRCLEWORK_ROOT);

$config = require CIRCLEWORK_ROOT . '/config/config.php';

App\Core\App::boot($config);

// Basic security headers.
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('X-XSS-Protection: 0');
header('Permissions-Policy: geolocation=(), microphone=(), camera=()');

if (($config['app']['env'] ?? 'production') === 'production') {
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
}

require CIRCLEWORK_ROOT . '/app/Helpers/functions.php';

App\Core\Session::start($config['session'] ?? []);

$router = App\Core\App::router();

require CIRCLEWORK_ROOT . '/routes/web.php';

$request = App\Core\Request::capture();

$response = $router->dispatch($request);

if (is_string($response)) {
    echo $response;
}
