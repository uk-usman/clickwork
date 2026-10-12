<?php

declare(strict_types=1);

namespace App\Core;

use RuntimeException;

/**
 * Plain-PHP view renderer with reusable layouts.
 */
final class View
{
    private static string $viewPath = '';
    private static string $defaultLayout = 'layouts/public';

    /** @var array<string,mixed> */
    private static array $shared = [];

    public static function setPath(string $path): void
    {
        self::$viewPath = rtrim($path, '/');
    }

    /**
     * @param array<string,mixed> $data
     */
    public static function share(string $key, mixed $value): void
    {
        self::$shared[$key] = $value;
    }

    /**
     * @param array<string,mixed> $data
     */
    public static function render(string $view, array $data = [], ?string $layout = null): string
    {
        $content = self::partial($view, $data);

        $layout ??= self::$defaultLayout;

        if ($layout === '' || $layout === 'none') {
            return $content;
        }

        $data['content'] = $content;

        return self::partial($layout, $data);
    }

    /**
     * Render a view file without a layout (partials/components).
     *
     * @param array<string,mixed> $data
     */
    public static function partial(string $view, array $data = []): string
    {
        $file = self::resolve($view);

        $data = array_merge(self::$shared, $data);

        extract($data, EXTR_SKIP);

        ob_start();
        try {
            require $file;
        } catch (\Throwable $e) {
            ob_end_clean();
            throw $e;
        }

        return (string) ob_get_clean();
    }

    private static function resolve(string $view): string
    {
        $view = str_replace(['..', '\\'], ['', '/'], $view);
        $file = self::$viewPath . '/' . ltrim($view, '/') . '.php';

        if (!is_file($file)) {
            throw new RuntimeException('View not found: ' . $view);
        }

        return $file;
    }

    public static function exists(string $view): bool
    {
        $view = str_replace(['..', '\\'], ['', '/'], $view);

        return is_file(self::$viewPath . '/' . ltrim($view, '/') . '.php');
    }
}
