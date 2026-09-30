<?php

declare(strict_types=1);

namespace App\Domain\Media;

/**
 * An uploaded image and its derived variants.
 *
 * Templates ask this object for a URL. They never know a filename, which is
 * what makes "the profile photo is replaceable from the admin" structurally
 * true rather than merely currently true.
 */
final class Media
{
    /** @param list<MediaVariant> $variants */
    public function __construct(
        public readonly int $id,
        public readonly string $storageKey,
        public readonly string $mimeType,
        public readonly int $width,
        public readonly int $height,
        public readonly ?string $altText,
        public readonly int $updatedAt,
        private readonly array $variants = [],
    ) {
    }

    public function variant(string $name, string $format = 'webp'): ?MediaVariant
    {
        foreach ($this->variants as $variant) {
            if ($variant->variant === $name && $variant->format === $format) {
                return $variant;
            }
        }

        return null;
    }

    /**
     * Public URL for a variant, carrying a cache-busting version derived from
     * the media row's updated_at. This is what lets uploaded files be served
     * with a one-year immutable cache while a replacement still appears
     * immediately — the two requirements are otherwise contradictory.
     */
    public function url(string $variantName, string $format = 'webp'): ?string
    {
        $variant = $this->variant($variantName, $format);

        if ($variant === null) {
            return null;
        }

        return '/uploads/' . ltrim($variant->path, '/') . '?v=' . $this->updatedAt;
    }

    public function alt(string $fallback = ''): string
    {
        return $this->altText !== null && $this->altText !== '' ? $this->altText : $fallback;
    }

    /** @return list<string> Relative paths of every stored variant. */
    public function variantPaths(): array
    {
        return array_map(
            static fn (MediaVariant $v): string => $v->path,
            $this->variants
        );
    }

    /** @return list<MediaVariant> */
    public function allVariants(): array
    {
        return $this->variants;
    }
}
