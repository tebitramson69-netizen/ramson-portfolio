<?php

declare(strict_types=1);

namespace App\Domain\Project;

/**
 * The canonical case-study section vocabulary and its default headings.
 *
 * Kept here rather than as a database ENUM so adding a section type is a code
 * change, not a migration — which is the whole point of storing sections as
 * rows. An unrecognised key coming back from the database is rendered with a
 * humanised heading rather than being dropped, so data never silently
 * disappears because code and schema drifted.
 */
final class SectionKey
{
    /** @var array<string, string> key => default heading, in display order */
    public const ORDER = [
        'overview'   => 'Overview',
        'problem'    => 'The problem',
        'context'    => 'Context',
        'goal'       => 'Goal',
        'solution'   => 'The solution',
        'role'       => 'My role',
        'process'    => 'Process',
        'features'   => 'Features',
        'technical'  => 'Technical implementation',
        'stack'      => 'Technologies',
        'challenges' => 'Challenges',
        'decisions'  => 'Key decisions',
        'outcome'    => 'Outcome',
        'lessons'    => 'Lessons learned',
        'next'       => 'Next steps',
    ];

    public static function heading(string $key): string
    {
        return self::ORDER[$key] ?? ucfirst(str_replace(['_', '-'], ' ', $key));
    }

    /** Display position; unknown keys sort to the end rather than vanishing. */
    public static function position(string $key): int
    {
        $index = array_search($key, array_keys(self::ORDER), true);

        return $index === false ? 999 : $index;
    }

    public static function isKnown(string $key): bool
    {
        return isset(self::ORDER[$key]);
    }
}
