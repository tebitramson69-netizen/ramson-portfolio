<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Core\Csrf;
use App\Core\Request;
use App\Core\Response;
use App\Core\Seo;
use App\Http\Controllers\Controller;

/**
 * Base for every admin screen.
 *
 * Authentication is NOT checked here — the kernel enforces the route guard
 * before a controller is constructed, so an unauthenticated request never
 * reaches this class. Putting the check in a base class as well would invite
 * the assumption that a controller which forgets to extend it is still safe.
 */
abstract class AdminController extends Controller
{
    /** @param array<string, mixed> $data */
    protected function adminPage(string $template, string $title, array $data = []): Response
    {
        $html = $this->view->render($template, $data + [
            // Admin pages are never indexed.
            'meta'      => new Seo(title: $title, noindex: true),
            'pageTitle' => $title,
            'admin'     => $this->auth->user(),
            'profile'   => $this->profiles->current(),
            'siteName'  => $this->settings->string('site_title', 'Portfolio'),
            'isHome'    => false,
            'flash'     => $this->takeFlash(),
        ], layout: 'layouts/admin');

        return Response::html($html);
    }

    /**
     * Verify the CSRF token on a state-changing request.
     *
     * Returns null when valid, or a Response to send instead.
     */
    protected function requireCsrf(Request $request, string $redirectTo): ?Response
    {
        $token = $request->post['_token'] ?? null;

        if (is_string($token) && Csrf::isValid($token)) {
            return null;
        }

        $this->flash('error', 'That form expired. Please try again.');

        return Response::redirect(route_url($redirectTo));
    }

    protected function flash(string $type, string $message): void
    {
        \App\Core\Session::start();
        $_SESSION['_flash'] = ['type' => $type, 'message' => $message];
    }

    /** @return array{type:string, message:string}|null */
    protected function takeFlash(): ?array
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return null;
        }

        $flash = $_SESSION['_flash'] ?? null;
        unset($_SESSION['_flash']);

        return is_array($flash) ? $flash : null;
    }
}
