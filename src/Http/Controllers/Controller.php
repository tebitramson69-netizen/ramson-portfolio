<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Core\Response;
use App\Core\Seo;
use App\Core\View;
use App\Domain\Auth\AuthService;
use App\Domain\Content\ContentList;
use App\Domain\Content\ContentListRepository;
use App\Domain\Profile\ProfileRepository;
use App\Domain\Project\ProjectRepository;
use App\Domain\Settings\SettingsRepository;
use App\Domain\Skill\SkillRepository;

/**
 * Shared controller plumbing.
 *
 * Controllers assemble a view model and hand it to a template. They contain
 * no SQL: every read goes through a repository, which is what keeps the
 * published/unpublished distinction enforceable in one place once projects
 * arrive in Phase 3.
 */
abstract class Controller
{
    public function __construct(
        protected readonly View $view,
        protected readonly ProfileRepository $profiles,
        protected readonly SettingsRepository $settings,
        protected readonly ProjectRepository $projects,
        protected readonly SkillRepository $skills,
        protected readonly AuthService $auth,
    ) {
    }

    /** @param array<string, mixed> $data */
    protected function page(string $template, Seo $meta, array $data = [], bool $isHome = false): Response
    {
        $html = $this->view->render($template, $data + [
            'meta'     => $meta,
            'profile'  => $this->profiles->current(),
            'siteName' => $this->settings->string(
                'site_title',
                'Tebit Ramson Titih — Software Engineer & Full-Stack Developer'
            ),
            'isHome'   => $isHome,

            // The nav links to #services on the home page from every page, so
            // every page has to know whether that section is actually there.
            // A link to an anchor that does not exist does nothing when
            // clicked, which reads as a broken site rather than as a section
            // the owner has not written yet.
            'hasServices' => (new ContentListRepository())->visible(ContentList::Services) !== [],
        ] + $this->contactState());

        return Response::html($html);
    }

    /**
     * Flash message and preserved form input for the contact section.
     *
     * Read ONLY from a session that already exists. Rendering a page must not
     * start one: a session on the home page means a cookie and a session file
     * for every anonymous visitor, and the contact form is deliberately built
     * so that someone who never submits never gets either. ContactController
     * starts the session on POST, which is the moment a visitor has chosen to
     * interact and carrying their draft back is worth it.
     *
     * @return array<string, mixed>
     */
    private function contactState(): array
    {
        // Session::cookieName(), NOT session_name(). Before a session starts
        // session_name() is still PHP's default, while the cookie this
        // application sets is rp_session (or __Host-rp_session over HTTPS).
        // Looking for the wrong name finds nothing and silently swallows
        // every flash — which is precisely what that method's docblock warns
        // about, and precisely the bug this line had on the first attempt.
        if (session_status() !== PHP_SESSION_ACTIVE
            && ($_COOKIE[\App\Core\Session::cookieName()] ?? null) === null) {
            return ['flash' => null, 'contactErrors' => [], 'contactOld' => []];
        }

        \App\Core\Session::start();

        $state = [
            'flash'         => is_array($_SESSION['_flash'] ?? null) ? $_SESSION['_flash'] : null,
            'contactErrors' => (array) ($_SESSION['_contact_errors'] ?? []),
            'contactOld'    => (array) ($_SESSION['_contact_old'] ?? []),
        ];

        // Read once. A flash that survived the page it was written for would
        // reappear on the next one, announcing a success that already happened.
        unset($_SESSION['_flash'], $_SESSION['_contact_errors'], $_SESSION['_contact_old']);

        return $state;
    }
}
