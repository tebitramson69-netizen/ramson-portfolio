<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Response headers applied to every PHP-rendered page.
 *
 * Apache also sets a subset in public/.htaccess so that static assets it
 * serves directly are covered. Setting them in both places is intentional
 * redundancy, not duplication: neither path covers the other.
 */
final class SecurityHeaders
{
    public static function apply(Response $response, Request $request): Response
    {
        $response = $response
            ->withHeader('X-Content-Type-Options', 'nosniff')
            ->withHeader('X-Frame-Options', 'DENY')
            ->withHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->withHeader('Permissions-Policy', 'geolocation=(), camera=(), microphone=(), interest-cohort=()')
            ->withHeader('Content-Security-Policy', self::contentSecurityPolicy())
            ->withHeader('Cross-Origin-Opener-Policy', 'same-origin');

        // HSTS is only meaningful over HTTPS, and sending it over plain HTTP
        // on localhost would be actively unhelpful during development.
        if ($request->isSecure && Config::isProduction()) {
            $response = $response->withHeader(
                'Strict-Transport-Security',
                'max-age=31536000; includeSubDomains'
            );
        }

        return $response;
    }

    private static function contentSecurityPolicy(): string
    {
        /** @var array<string, string[]> $directives */
        $directives = Config::get('csp', []);
        $parts      = [];

        foreach ($directives as $name => $sources) {
            $parts[] = $sources === []
                ? $name
                : $name . ' ' . implode(' ', $sources);
        }

        return implode('; ', $parts);
    }
}
