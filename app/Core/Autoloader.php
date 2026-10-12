<?php

declare(strict_types=1);

namespace App\Core;

/**
 * PSR-4 style autoloader for the App\ namespace, without Composer.
 *
 * "App\Controllers\HomeController" => <root>/app/Controllers/HomeController.php
 */
final class Autoloader
{
    public static function register(string $rootPath): void
    {
        spl_autoload_register(static function (string $class) use ($rootPath): void {
            $prefix = 'App\\';

            if (!str_starts_with($class, $prefix)) {
                return;
            }

            $relative = substr($class, strlen($prefix));
            $relative = str_replace('\\', DIRECTORY_SEPARATOR, $relative);
            $file = $rootPath . '/app/' . $relative . '.php';

            if (is_file($file)) {
                require $file;
            }
        });
    }
}
