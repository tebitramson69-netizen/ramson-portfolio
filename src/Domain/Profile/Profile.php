<?php

declare(strict_types=1);

namespace App\Domain\Profile;

use App\Domain\Media\Media;

/**
 * The site's identity. One row, one object.
 */
final class Profile
{
    public function __construct(
        public readonly string $fullName,
        public readonly string $monogram,
        public readonly string $professionalTitle,
        public readonly string $valueProposition,
        public readonly string $technologyLine,
        public readonly ?string $shortIntro,
        public readonly ?string $biography,
        public readonly string $location,
        public readonly string $education,
        public readonly string $institution,
        public readonly string $availabilityStatus,
        public readonly string $availabilityNote,
        public readonly ?string $email,
        public readonly ?string $whatsapp,
        public readonly ?string $githubUrl,
        public readonly ?string $linkedinUrl,
        public readonly ?Media $photo,
    ) {
    }

    /**
     * The fallback monogram shown when no photograph is set.
     *
     * Uses the stored mark when there is one, because the mark is a brand
     * decision rather than something to compute: deriving initials from
     * "Tebit Ramson Titih" would give "TT", while the approved mark is "RT".
     * Falls back to first + last initials so a profile that has never set one
     * still renders something sensible.
     */
    public function monogram(): string
    {
        if ($this->monogram !== '') {
            return $this->monogram;
        }

        return $this->derivedInitials();
    }

    private function derivedInitials(): string
    {
        $words = preg_split('/\s+/', trim($this->fullName)) ?: [];
        $words = array_values(array_filter($words));

        if ($words === []) {
            return '';
        }

        $first = mb_substr($words[0], 0, 1);
        $last  = count($words) > 1 ? mb_substr($words[count($words) - 1], 0, 1) : '';

        return mb_strtoupper($first . $last);
    }

    /** @return list<string> Paragraphs of the biography, for templating. */
    public function biographyParagraphs(): array
    {
        if ($this->biography === null || trim($this->biography) === '') {
            return [];
        }

        $parts = preg_split('/\R{2,}/', trim($this->biography)) ?: [];

        return array_values(array_filter(array_map('trim', $parts)));
    }

    public function hasPhoto(): bool
    {
        return $this->photo !== null;
    }

    /** @return array<string, string> Contact channels that actually exist. */
    public function contactLinks(): array
    {
        return array_filter([
            'email'    => $this->email,
            'whatsapp' => $this->whatsapp,
            'github'   => $this->githubUrl,
            'linkedin' => $this->linkedinUrl,
        ], static fn (?string $v): bool => $v !== null && $v !== '');
    }
}
