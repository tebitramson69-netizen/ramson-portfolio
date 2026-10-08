<?php

/**
 * Route table.
 *
 * One flat, greppable list. The third argument is a guard: 'auth' means the
 * kernel refuses the request and redirects to the login form BEFORE the
 * controller is constructed, so an admin screen cannot be reached by an
 * anonymous visitor even if a check inside it were ever forgotten.
 *
 * PHASE 7 SCOPE. Profile, the profile photograph, projects and case studies,
 * the skills vocabulary, services, the process steps and site settings are all
 * editable. /about and /contact remain sections of the home page until they
 * have content of their own, so the site does not ship two URLs for the same
 * text.
 */

declare(strict_types=1);

use App\Core\Router;
use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ProcessController;
use App\Http\Controllers\Admin\ProfileController;
use App\Http\Controllers\Admin\ProjectController;
use App\Http\Controllers\Admin\ServiceController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\SkillController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\WorkController;

return static function (Router $router): void {

    // ---- public ---------------------------------------------------------
    $router->get('/',            [HomeController::class, 'index']);

    // A route, not a file: the sitemap has to reflect what is published
    // right now, and a static file would be wrong the first time a
    // project was published from the admin.
    $router->get('/sitemap.xml', [SitemapController::class, 'index']);
    $router->get('/robots.txt',  [SitemapController::class, 'robots']);
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

    // Skills. Categories and skills share one screen, so they share one path
    // and differ by the noun in the segment after it.
    $router->get('/admin/skills', [SkillController::class, 'index'], 'auth');

    $router->post('/admin/skills/categories',                   [SkillController::class, 'storeCategory'], 'auth');
    $router->post('/admin/skills/categories/{id:\d+}',          [SkillController::class, 'updateCategory'], 'auth');
    $router->post('/admin/skills/categories/{id:\d+}/visible',  [SkillController::class, 'categoryVisibility'], 'auth');
    $router->post('/admin/skills/categories/{id:\d+}/delete',   [SkillController::class, 'destroyCategory'], 'auth');

    $router->post('/admin/skills/items',                  [SkillController::class, 'storeSkill'], 'auth');
    $router->post('/admin/skills/items/{id:\d+}',         [SkillController::class, 'updateSkill'], 'auth');
    $router->post('/admin/skills/items/{id:\d+}/visible', [SkillController::class, 'skillVisibility'], 'auth');
    $router->post('/admin/skills/items/{id:\d+}/delete',  [SkillController::class, 'destroySkill'], 'auth');

    $router->post('/admin/skills/reorder', [SkillController::class, 'reorder'], 'auth');

    // Services and process steps. Identical shapes, two controllers that
    // differ only in which list they name — see ContentListController.
    $router->get('/admin/services',  [ServiceController::class, 'index'], 'auth');
    $router->post('/admin/services', [ServiceController::class, 'store'], 'auth');
    $router->post('/admin/services/{id:\d+}',         [ServiceController::class, 'update'], 'auth');
    $router->post('/admin/services/{id:\d+}/visible', [ServiceController::class, 'visibility'], 'auth');
    $router->post('/admin/services/{id:\d+}/delete',  [ServiceController::class, 'destroy'], 'auth');
    $router->post('/admin/services/reorder',           [ServiceController::class, 'reorder'], 'auth');

    $router->get('/admin/process',  [ProcessController::class, 'index'], 'auth');
    $router->post('/admin/process', [ProcessController::class, 'store'], 'auth');
    $router->post('/admin/process/{id:\d+}',         [ProcessController::class, 'update'], 'auth');
    $router->post('/admin/process/{id:\d+}/visible', [ProcessController::class, 'visibility'], 'auth');
    $router->post('/admin/process/{id:\d+}/delete',  [ProcessController::class, 'destroy'], 'auth');
    $router->post('/admin/process/reorder',           [ProcessController::class, 'reorder'], 'auth');

    // Settings. One form, saved whole — there are few enough that a per-field
    // save would be more chrome than content.
    $router->get('/admin/settings',  [SettingsController::class, 'edit'], 'auth');
    $router->post('/admin/settings', [SettingsController::class, 'update'], 'auth');

    // Draft preview. The public /work/{slug} still goes through
    // findPublishedBySlug, so an anonymous visitor guessing a draft's address
    // gets a genuine 404; this guarded route is the only way to see one.
    $router->get('/admin/preview/{slug}', [WorkController::class, 'preview'], 'auth');
};
