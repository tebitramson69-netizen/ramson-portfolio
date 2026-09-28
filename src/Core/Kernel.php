<?php

declare(strict_types=1);

namespace App\Core;

use App\Domain\Media\MediaRepository;
use App\Domain\Profile\ProfileRepository;
use App\Domain\Settings\SettingsRepository;

/**
 * Application bootstrap and request lifecycle.
 *
 * Deliberately a small hand-wired container rather than a reflection-based
 * one: there are six services, the wiring fits on a screen, and anyone
 * reading it can see exactly what depends on what. A DI container earns its
 * place when construction graphs get deep; this one is two levels.
 */
final class Kernel
{
    private Router $router;
    private View $view;

    /** @var array<string, object> */
    private array $services = [];

    public function __construct(private readonly string $basePath)
    {
    }

    public function boot(): self
    {
        Config::load($this->basePath . '/config');

        date_default_timezone_set((string) Config::get('app.timezone', 'UTC'));
        mb_internal_encoding('UTF-8');
        setlocale(LC_ALL, 'C');   // keep number and date formatting predictable

        require_once $this->basePath . '/src/Support/helpers.php';

        $this->view = new View($this->basePath . '/templates');

        ErrorHandler::register($this->basePath . '/storage/logs/app.log', $this->view);

        // Services. Repositories are shared for the request so the profile is
        // read once no matter how many templates ask for it.
        $media = new MediaRepository();

        $this->services = [
            MediaRepository::class    => $media,
            ProfileRepository::class  => new ProfileRepository($media),
            SettingsRepository::class => new SettingsRepository(),
            View::class               => $this->view,
        ];

        $this->router = new Router();
        (require $this->basePath . '/routes/web.php')($this->router);

        return $this;
    }

    public function handle(Request $request): Response
    {
        try {
            $match = $this->router->match($request);

            if ($match === null) {
                return $this->notFound($request);
            }

            [$controllerClass, $method] = $match['handler'];

            $controller = new $controllerClass(
                $this->view,
                $this->services[ProfileRepository::class],
                $this->services[SettingsRepository::class],
            );

            /** @var Response $response */
            $response = $controller->{$method}($request, $match['params']);
        } catch (HttpNotFoundException) {
            $response = $this->notFound($request);
        } catch (MethodNotAllowedException) {
            $response = Response::html(
                $this->renderError(405, 'Method not allowed', $request),
                405
            );
        }

        return SecurityHeaders::apply($response, $request);
    }

    private function notFound(Request $request): Response
    {
        return Response::html($this->renderError(404, 'Page not found', $request), 404);
    }

    private function renderError(int $status, string $title, Request $request): string
    {
        /** @var ProfileRepository $profiles */
        $profiles = $this->services[ProfileRepository::class];
        /** @var SettingsRepository $settings */
        $settings = $this->services[SettingsRepository::class];

        // The error page must render even when the database is unreachable —
        // an error page that itself errors is the worst possible outcome.
        try {
            $profile  = $profiles->current();
            $siteName = $settings->string('site_title', 'Portfolio');
        } catch (\Throwable) {
            $profile  = null;
            $siteName = 'Portfolio';
        }

        return $this->view->render('errors/' . $status, [
            'meta'     => new Seo(title: $title, noindex: true),
            'profile'  => $profile,
            'siteName' => $siteName,
            'isHome'   => false,
        ]);
    }
}
