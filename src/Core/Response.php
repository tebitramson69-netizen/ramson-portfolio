<?php

declare(strict_types=1);

namespace App\Core;

/**
 * A response that is built up and then sent once, so headers and body can
 * never be emitted halfway through rendering.
 */
final class Response
{
    /** @var array<string, string> */
    private array $headers = [];

    public function __construct(
        private string $body = '',
        private int $status = 200,
    ) {
    }

    public static function html(string $body, int $status = 200): self
    {
        return (new self($body, $status))->withHeader('Content-Type', 'text/html; charset=UTF-8');
    }

    /**
     * Redirect to a path within this application.
     *
     * Only relative paths are accepted. Passing a caller-controlled absolute
     * URL here is how open-redirect vulnerabilities happen, so the type
     * simply does not allow it.
     */
    public static function redirect(string $path, int $status = 302): self
    {
        $safe = '/' . ltrim(str_replace(["\r", "\n"], '', $path), '/');

        return (new self('', $status))->withHeader('Location', $safe);
    }

    public function withHeader(string $name, string $value): self
    {
        $this->headers[$name] = $value;
        return $this;
    }

    public function status(): int
    {
        return $this->status;
    }

    public function send(): void
    {
        if (!headers_sent()) {
            http_response_code($this->status);

            foreach ($this->headers as $name => $value) {
                header($name . ': ' . $value, true);
            }
        }

        echo $this->body;
    }
}
