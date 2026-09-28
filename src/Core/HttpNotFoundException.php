<?php

declare(strict_types=1);

namespace App\Core;

use RuntimeException;

/**
 * Thrown by a controller when a route matched but the resource behind it does
 * not exist — an unknown project slug, for example. The kernel turns this
 * into a real 404 response with the styled error page.
 */
final class HttpNotFoundException extends RuntimeException
{
}
