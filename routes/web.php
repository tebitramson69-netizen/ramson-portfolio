<?php

/**
 * Route table.
 *
 * One flat, greppable list. The third argument is a guard: 'auth' means the
 * kernel refuses the request and redirects to the login form BEFORE the
 * controller is constructed, so an admin screen cannot be reached by an
 * anonymous visitor even if a check inside it were ever forgotten.
 *
 * PHASE 5 SCOPE. Profile editing and the profile photograph are live. Project
 * CRUD arrives in Phase 6. /about and /contact remain sections of the home
 * page until they have content of their own, so the site does not ship two
 * URLs for the same text.
 */

declare(strict_types=1);

use App\Core\Router;
use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ProfileController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\WorkController;

return static function (Router $router): void {

    // ---- public ---------------------------------------------------------
    $router->get('/',            [HomeController::class, 'index']);
    $router->get('/work/{slug}', [WorkController::class, 'show']);

    // ---- admin: unauthenticated by necessity ----------------------------
    $router->get('/admin/login',  [AuthController::class, 'showLogin']);
    $router->post('/admin/login', [AuthController::class, 'login']);

    // Logout is a POST because it changes state; a GET logout can be fired by
    // any third-party image tag.
    $router->post('/admin/logout', [AuthController::class, 'logout']);

    // ---- admin: guarded -------------------------------------------------
    $router->get('/admin', [DashboardController::class, 'index'], 'auth');

    // Profile. The photograph is its own route, not a field of the text form:
    // saving a typo fix must not re-process an image, and a rejected image
    // must not discard edited text.
    $router->get('/admin/profile',  [ProfileController::class, 'edit'], 'auth');
    $router->post('/admin/profile', [ProfileController::class, 'update'], 'auth');

    $router->post('/admin/profile/photo',        [ProfileController::class, 'uploadPhoto'], 'auth');
    $router->post('/admin/profile/photo/alt',    [ProfileController::class, 'updateAlt'], 'auth');
    $router->post('/admin/profile/photo/remove', [ProfileController::class, 'removePhoto'], 'auth');
};
