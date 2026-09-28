<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Per-page metadata, built by the controller and rendered by one partial.
 *
 * Keeping it a typed object rather than loose template variables means a page
 * cannot silently ship without a title or description, and the JSON-LD is
 * generated from the same values the user sees — so the structured data can
 * never drift from the visible content.
 */
final class Seo
{
    /**
     * @param list<array<string, mixed>> $jsonLd
     */
    public function __construct(
        public readonly string $title,
        public readonly string $description = '',
        public readonly string $canonical = '',
        public readonly string $ogType = 'website',
        public readonly string $ogImage = '',
        public readonly bool $noindex = false,
        public readonly array $jsonLd = [],
    ) {
    }

    public static function minimal(string $title): self
    {
        return new self(title: $title, noindex: true);
    }

    /** The full <title>, suffixed with the site identity except on the home page. */
    public function documentTitle(string $siteName, bool $isHome = false): string
    {
        if ($isHome || $this->title === '') {
            return $siteName;
        }

        return $this->title . ' — ' . $siteName;
    }
}
