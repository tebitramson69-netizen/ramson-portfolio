<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Core\Config;
use App\Core\Csrf;
use App\Core\Request;
use App\Core\Response;
use App\Core\Seo;
use App\Domain\Media\ImageValidator;
use App\Domain\Media\MediaRepository;
use App\Domain\Media\MediaUploadService;
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

    // ------------------------------------------------------ shared uploading
    //
    // Every admin screen that accepts an image needs the same four things, so
    // they live here rather than being copied into each controller. The
    // profile photograph and a project screenshot go through one pipeline, one
    // validator and one set of limits — which is also why a fix to either is a
    // fix to both.

    /**
     * True when PHP threw the request body away for exceeding post_max_size.
     *
     * MUST be checked BEFORE the CSRF token on any upload route. Over that
     * limit PHP discards $_POST and $_FILES entirely, so the token is missing
     * and requireCsrf() reports "that form expired" for what is actually an
     * oversized file — the wrong cause, and one that sends the author back to
     * retry the same file forever.
     *
     * The signature is a POST that declared a content length but arrived with
     * nothing parsed out of it.
     */
    protected function postDiscarded(Request $request): bool
    {
        $declared = (int) ($_SERVER['CONTENT_LENGTH'] ?? 0);

        return $declared > 0 && $request->post === [] && $_FILES === [];
    }

    protected function postDiscardedMessage(): string
    {
        return sprintf(
            'That file is too large for this server to accept at all. PHP discards a request '
            . 'body over post_max_size (%s) before the application sees it. Choose a smaller '
            . 'image, or raise post_max_size and upload_max_filesize in php.ini.',
            (string) ini_get('post_max_size')
        );
    }

    protected function uploadService(): MediaUploadService
    {
        $root = dirname(__DIR__, 4);

        return new MediaUploadService(
            new MediaRepository(),
            new ImageValidator(),
            $root . '/public/uploads',
            $root . '/storage/uploads',
        );
    }

    /**
     * The limit actually in force, which is the SMALLEST of three numbers.
     *
     * Stating the application's configured 5 MB when php.ini allows 2 MB would
     * invite a failure the form had already promised would not happen, so the
     * effective ceiling is computed rather than quoted.
     *
     * @return array<string, string>
     */
    protected function uploadLimits(): array
    {
        $configured = (int) Config::get('uploads.max_bytes', 5 * 1024 * 1024);
        $uploadMax  = self::iniBytes((string) ini_get('upload_max_filesize'));
        $postMax    = self::iniBytes((string) ini_get('post_max_size'));

        $effective = (int) min(array_filter([$configured, $uploadMax, $postMax]));

        return [
            'max'        => ImageValidator::formatBytes($effective),
            'max_bytes'  => (string) $effective,
            'min_side'   => (string) Config::get('uploads.min_dimension', 400),
            'php_limit'  => (string) ini_get('upload_max_filesize'),
            'post_limit' => (string) ini_get('post_max_size'),
            'mismatch'   => $effective < $configured ? 'yes' : '',
            'configured' => ImageValidator::formatBytes($configured),
        ];
    }

    public static function iniBytes(string $value): int
    {
        $value = trim($value);

        if ($value === '') {
            return 0;
        }

        $bytes = (int) $value;

        return $bytes * match (strtolower(substr($value, -1))) {
            'g' => 1024 ** 3,
            'm' => 1024 ** 2,
            'k' => 1024,
            default => 1,
        };
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
