<?php

declare(strict_types=1);

namespace App\Core;

use RuntimeException;

final class MethodNotAllowedException extends RuntimeException
{
    public function __construct(public readonly string $path)
    {
        parent::__construct('Method not allowed for ' . $path);
    }
}
