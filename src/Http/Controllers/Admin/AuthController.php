<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Core\Csrf;
use App\Core\Request;
use App\Core\Response;
use App\Core\Seo;
use App\Core\Session;
use App\Http\Controllers\Controller;

/**
 * Sign in and out.
 *
 * These are the only admin routes without the auth guard, for the obvious
 * reason. There is no registration action and no password-reset action: the
 * account is created by bin/create-admin.php from the command line, and a
 * forgotten password is reset the same way. An emailed reset link is a whole
 * attack surface — token generation, expiry, mail delivery, enumeration — for
 * a single-user site whose owner has shell access.
 */
final class AuthController extends Controller
{
    /** @param array<string, string> $params */
    public function showLogin(Request $request, array $params = []): Response
    {
        if ($this->auth->check()) {
            return Response::redirect(route_url('/admin'));
        }

        $flash = null;
        if (session_status() === PHP_SESSION_ACTIVE) {
            $flash = $_SESSION['_flash'] ?? null;
            unset($_SESSION['_flash']);
        }

        $html = $this->view->render('admin/login', [
            'meta'      => new Seo(title: 'Sign in', noindex: true),
            'pageTitle' => 'Sign in',
            'siteName'  => $this->settings->string('site_title', 'Portfolio'),
            'profile'   => $this->profiles->current(),
            'isHome'    => false,
            'csrf'      => Csrf::token(),
            'flash'     => is_array($flash) ? $flash : null,
            'email'     => '',
        ], layout: 'layouts/auth');

        return Response::html($html);
    }

    /** @param array<string, string> $params */
    public function login(Request $request, array $params = []): Response
    {
        $token = $request->post['_token'] ?? null;

        if (!is_string($token) || !Csrf::isValid($token)) {
            return $this->back('That form expired. Please try again.');
        }

        $email    = trim((string) ($request->post['email'] ?? ''));
        $password = (string) ($request->post['password'] ?? '');

        if ($email === '' || $password === '') {
            return $this->back('Enter your email and password.');
        }

        $failure = $this->auth->attempt(
            $email,
            $password,
            $this->clientIp(),
            (string) ($_SERVER['HTTP_USER_AGENT'] ?? '')
        );

        if ($failure !== null) {
            return $this->back($failure);
        }

        // Send them where they were originally going, if anywhere. The stored
        // value is a path this application produced, never request input.
        $intended = $_SESSION['admin_intended'] ?? null;
        unset($_SESSION['admin_intended']);

        $target = is_string($intended) && str_starts_with($intended, '/admin')
            ? $intended
            : '/admin';

        return Response::redirect(route_url($target));
    }

    /** @param array<string, string> $params */
    public function logout(Request $request, array $params = []): Response
    {
        $token = $request->post['_token'] ?? null;

        // Logout is a state change, so it is a POST and it is CSRF-protected.
        // A GET logout can be triggered by any third-party image tag.
        if (is_string($token) && Csrf::isValid($token)) {
            $this->auth->logout();
        }

        return Response::redirect(route_url('/admin/login'));
    }

    private function back(string $message): Response
    {
        Session::start();
        $_SESSION['_flash'] = ['type' => 'error', 'message' => $message];

        return Response::redirect(route_url('/admin/login'));
    }

    /**
     * Client address.
     *
     * Forwarding headers are trusted only when the deployment says so,
     * because a client can set X-Forwarded-For freely and would otherwise
     * defeat IP throttling by varying it.
     */
    private function clientIp(): ?string
    {
        if (\App\Core\Config::get('app.trust_proxy', false)) {
            $forwarded = (string) ($_SERVER['HTTP_X_FORWARDED_FOR'] ?? '');
            if ($forwarded !== '') {
                $first = trim(explode(',', $forwarded)[0]);
                if (filter_var($first, FILTER_VALIDATE_IP) !== false) {
                    return $first;
                }
            }
        }

        $remote = (string) ($_SERVER['REMOTE_ADDR'] ?? '');

        return filter_var($remote, FILTER_VALIDATE_IP) === false ? null : $remote;
    }
}
