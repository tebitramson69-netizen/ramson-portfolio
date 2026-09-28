<?php

/**
 * Route table.
 *
 * One flat, greppable list. Adding a page is one line here plus a controller
 * method — there is no route caching, no attribute scanning and no magic.
 *
 * PHASE 2 SCOPE. /about and /contact are currently sections of the home page
 * rather than separate documents, so giving them URLs now would create two
 * addresses for the same content and split their ranking. They become routes
 * in Phase 7, when the CMS gives them content of their own. The admin routes
 * arrive in Phase 4.
 */

declare(strict_types=1);

use App\Core\Router;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\WorkController;

return static function (Router $router): void {
    $router->get('/',             [HomeController::class, 'index']);
    $router->get('/work/{slug}',  [WorkController::class, 'show']);
};
