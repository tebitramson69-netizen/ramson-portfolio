<?php

declare(strict_types=1);

namespace App\Domain\Media;

final class MediaVariant
{
    public function __construct(
        public readonly string $variant,
        public readonly string $format,
        public readonly string $path,
        public readonly int $width,
        public readonly int $height,
    ) {
    }

    /** @param array<string, mixed> $row */
    public static function fromRow(array $row): self
    {
        return new self(
            variant: (string) $row['variant'],
            format:  (string) $row['format'],
            path:    (string) $row['path'],
            width:   (int) $row['width'],
            height:  (int) $row['height'],
        );
    }
}
