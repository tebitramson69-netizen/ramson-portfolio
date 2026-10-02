<?php

/**
 * Route table.
 *
 * One flat, greppable list. The third argument is a guard: 'auth' means the
 * kernel refuses the request and redirects to the login form BEFORE the
 * controller is constructed, so an admin screen cannot be reached by an
 * anonymous visitor even if a check inside it were ever forgotten.
 *
 * PHASE 6 SCOPE. Profile editing, the profile photograph, and project and
 * case-study editing are all live. /about and /contact remain sections of the
 * home page until they have content of their own, so the site does not ship
 * two URLs for the same text.
 */

declare(strict_types=1);

use App\Core\Router;
use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ProfileController;
use App\Http\Controllers\Admin\ProjectController;
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

    // Projects. {id:\d+} rather than a bare placeholder so /admin/projects/new
    // cannot be read as an id, whatever order these are declared in.
    $router->get('/admin/projects',     [ProjectController::class, 'index'], 'auth');
    $router->get('/admin/projects/new', [ProjectController::class, 'create'], 'auth');
    $router->post('/admin/projects',    [ProjectController::class, 'store'], 'auth');

    $router->get('/admin/projects/{id:\d+}',  [ProjectController::class, 'edit'], 'auth');
    $router->post('/admin/projects/{id:\d+}', [ProjectController::class, 'update'], 'auth');

    $router->post('/admin/projects/{id:\d+}/state',        [ProjectController::class, 'state'], 'auth');
    $router->post('/admin/projects/{id:\d+}/image',        [ProjectController::class, 'uploadImage'], 'auth');
    $router->post('/admin/projects/{id:\d+}/image/remove', [ProjectController::class, 'removeImage'], 'auth');
    $router->post('/admin/projects/{id:\d+}/delete',       [ProjectController::class, 'destroy'], 'auth');
    $router->post('/admin/projects/{id:\d+}/restore',      [ProjectController::class, 'restore'], 'auth');
    $router->post('/admin/projects/reorder',                [ProjectController::class, 'reorder'], 'auth');

    // Draft preview. The public /work/{slug} still goes through
    // findPublishedBySlug, so an anonymous visitor guessing a draft's address
    // gets a genuine 404; this guarded route is the only way to see one.
    $router->get('/admin/preview/{slug}', [WorkController::class, 'preview'], 'auth');
};
