<?php

declare(strict_types=1);

/**
 * Shared bootstrap for command-line seeders.
 *
 * Usage: php database/seeders/seed_categories.php
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Seeders may only be run from the command line.');
}

define('CIRCLEWORK_ROOT', dirname(__DIR__, 2));

require CIRCLEWORK_ROOT . '/app/Core/Autoloader.php';
App\Core\Autoloader::register(CIRCLEWORK_ROOT);

$config = require CIRCLEWORK_ROOT . '/config/config.php';
App\Core\App::boot($config);

require CIRCLEWORK_ROOT . '/app/Helpers/functions.php';

function cw_line(string $message): void
{
    fwrite(STDOUT, $message . PHP_EOL);
}

function cw_fail(string $message): never
{
    fwrite(STDERR, 'ERROR: ' . $message . PHP_EOL);
    exit(1);
}
