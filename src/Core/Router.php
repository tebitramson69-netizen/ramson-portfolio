<?php

declare(strict_types=1);

namespace App\Core;

/**
 * A small pattern-matching router.
 *
 * Routes are declared in routes/web.php as a flat table. Patterns support
 * named placeholders: '/work/{slug}' captures 'slug'. A placeholder matches a
 * single path segment and defaults to a URL-safe slug charset; a different
 * charset can be given inline, e.g. '/admin/projects/{id:\d+}/edit'.
 *
 * About a hundred lines replaces a routing dependency for a site with roughly
 * fifteen routes. If the table ever outgrows this, FastRoute is a drop-in
 * replacement and this route format is already close to its own.
 */
final class Router
{
    /** @var list<array{method:string, pattern:string, handler:array{0:class-string,1:string}}> */
    private array $routes = [];

    /** @var array<string, array{regex:string, names:list<string>}> */
    private array $compiled = [];

    /** @param array{0:class-string, 1:string} $handler [ControllerClass, 'method'] */
    public function get(string $pattern, array $handler): void
    {
        $this->add('GET', $pattern, $handler);
    }

    /** @param array{0:class-string, 1:string} $handler */
    public function post(string $pattern, array $handler): void
    {
        $this->add('POST', $pattern, $handler);
    }

    /** @param array{0:class-string, 1:string} $handler */
    private function add(string $method, string $pattern, array $handler): void
    {
        $this->routes[] = [
            'method'  => $method,
            'pattern' => self::normalise($pattern),
            'handler' => $handler,
        ];
    }

    private static function normalise(string $path): string
    {
        $trimmed = trim($path, '/');
        return $trimmed === '' ? '/' : '/' . $trimmed;
    }

    /**
     * @return array{handler:array{0:class-string,1:string}, params:array<string,string>}|null
     *         null when no route matches the path at all (404).
     * @throws MethodNotAllowedException when the path matches but the verb does not (405).
     */
    public function match(Request $request): ?array
    {
        $path        = self::normalise($request->path);
        $pathMatched = false;

        // HEAD is served by the GET handler; PHP discards the body itself.
        $verb = $request->method === 'HEAD' ? 'GET' : $request->method;

        foreach ($this->routes as $route) {
            $params = $this->matchPattern($route['pattern'], $path);

            if ($params === null) {
                continue;
            }

            $pathMatched = true;

            if ($route['method'] === $verb) {
                return ['handler' => $route['handler'], 'params' => $params];
            }
        }

        if ($pathMatched) {
            throw new MethodNotAllowedException($path);
        }

        return null;
    }

    /** @return array<string, string>|null */
    private function matchPattern(string $pattern, string $path): ?array
    {
        // Static routes are the common case and need no regex at all.
        if (!str_contains($pattern, '{')) {
            return $pattern === $path ? [] : null;
        }

        ['regex' => $regex, 'names' => $names] = $this->compile($pattern);

        if (preg_match($regex, $path, $matches) !== 1) {
            return null;
        }

        array_shift($matches);

        /** @var array<string, string> */
        return array_combine($names, array_map('strval', $matches));
    }

    /**
     * Build the regex by splitting the pattern into literal and placeholder
     * parts, quoting ONLY the literals. Quoting the whole pattern and then
     * trying to unescape the generated groups is how these routers acquire
     * subtle bugs.
     *
     * @return array{regex:string, names:list<string>}
     */
    private function compile(string $pattern): array
    {
        if (isset($this->compiled[$pattern])) {
            return $this->compiled[$pattern];
        }

        $names = [];
        $regex = '';
        $offset = 0;

        preg_match_all(
            '#\{([a-zA-Z_][a-zA-Z0-9_]*)(?::([^{}]+))?\}#',
            $pattern,
            $placeholders,
            PREG_OFFSET_CAPTURE | PREG_SET_ORDER
        );

        foreach ($placeholders as $placeholder) {
            [$whole, $position] = $placeholder[0];

            $regex .= preg_quote(substr($pattern, $offset, $position - $offset), '#');

            $names[] = $placeholder[1][0];

            // The default charset deliberately excludes '/', so a placeholder
            // can never swallow more than one path segment.
            $charset = $placeholder[2][0] ?? '[A-Za-z0-9._-]+';
            $regex  .= '(' . $charset . ')';

            $offset = $position + strlen($whole);
        }

        $regex .= preg_quote(substr($pattern, $offset), '#');

        $compiled = ['regex' => '#^' . $regex . '$#D', 'names' => $names];
        $this->compiled[$pattern] = $compiled;

        return $compiled;
    }
}
