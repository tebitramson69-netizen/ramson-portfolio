<?php

declare(strict_types=1);

namespace App\Core;

use RuntimeException;
use Throwable;

/**
 * Plain-PHP templating.
 *
 * Templates are PHP files. There is no compiler, no cache directory and no
 * template language to learn — PHP already is one. What this class adds is
 * the two things raw includes lack: a layout wrapper, and partials that
 * cannot see the caller's variables by accident.
 *
 * Escaping is NOT automatic. It is a single short helper, e(), applied at
 * every output site. An auto-escaping engine would be safer by default, but
 * it would be a compiler, and writing one is exactly the "build a small
 * framework" trap this project is avoiding.
 */
final class View
{
    public function __construct(
        private readonly string $templateDirectory,
        /** @var array<string, mixed> shared with every template */
        private array $shared = [],
    ) {
    }

    public function share(string $key, mixed $value): void
    {
        $this->shared[$key] = $value;
    }

    /**
     * Render a page template inside a layout.
     *
     * @param array<string, mixed> $data
     */
    public function render(string $template, array $data = [], string $layout = 'layouts/public'): string
    {
        $content = $this->capture($template, $data);

        return $this->capture($layout, $data + ['content' => $content]);
    }

    /**
     * Render a partial or component and return it, without a layout.
     *
     * @param array<string, mixed> $data
     */
    public function partial(string $template, array $data = []): string
    {
        return $this->capture($template, $data);
    }

    /** @param array<string, mixed> $data */
    private function capture(string $template, array $data): string
    {
        $file = $this->templateDirectory . '/' . ltrim($template, '/') . '.php';

        // Templates are named in code, never by the request, but resolving
        // the path defensively costs nothing and documents the intent.
        $real = realpath($file);
        $root = realpath($this->templateDirectory);

        if ($real === false || $root === false || !str_starts_with($real, $root)) {
            throw new RuntimeException('Template not found: ' . $template);
        }

        $scope = $this->shared + $data;

        $level = ob_get_level();
        ob_start();

        try {
            (static function (string $__file, array $__scope): void {
                extract($__scope, EXTR_SKIP);
                require $__file;
            })($real, $scope);

            return (string) ob_get_clean();
        } catch (Throwable $e) {
            // Discard the half-rendered buffer so a failure never leaks a
            // partial page alongside the error response.
            while (ob_get_level() > $level) {
                ob_end_clean();
            }
            throw $e;
        }
    }
}
