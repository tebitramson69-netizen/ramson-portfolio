<?php

declare(strict_types=1);

namespace App\Domain\Content;

/**
 * The content lists this application has, and nothing else.
 *
 * Both the repository and the writer interpolate a table name into SQL, which
 * a prepared statement cannot parameterise. This enum is what makes that safe:
 * a table name can only ever come from here, never from a request, and a new
 * list is a deliberate code change rather than a string someone passed in.
 */
enum ContentList: string
{
    case Services = 'services';
    case ProcessSteps = 'process_steps';

    /** Singular, lower case — for messages like "That service no longer exists." */
    public function noun(): string
    {
        return match ($this) {
            self::Services     => 'service',
            self::ProcessSteps => 'process step',
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Services     => 'Services',
            self::ProcessSteps => 'Process',
        };
    }
}
