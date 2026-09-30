<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Core\Config;
use App\Core\Csrf;
use App\Core\Request;
use App\Core\Response;
use App\Domain\Media\ImageValidator;
use App\Domain\Media\MediaRepository;
use App\Domain\Media\MediaUploadService;

/**
 * Profile editing, including the photograph.
 *
 * The photo is a separate form and a separate route from the text fields, so
 * saving a typo fix cannot accidentally re-process an image, and an image
 * failure cannot discard edited text.
 */
final class ProfileController extends AdminController
{
    /** @param array<string, string> $params */
    public function edit(Request $request, array $params = []): Response
    {
        return $this->adminPage('admin/profile', 'Profile', [
            'csrf'   => Csrf::token(),
            'limits' => $this->limits(),
        ]);
    }

    /** @param array<string, string> $params */
    public function update(Request $request, array $params = []): Response
    {
        if ($response = $this->requireCsrf($request, '/admin/profile')) {
            return $response;
        }

        $post = $request->post;

        $text = static fn (string $key, int $max): string
            => mb_substr(trim((string) ($post[$key] ?? '')), 0, $max);

        // An empty optional field is stored as NULL, not '', so "not supplied"
        // stays distinguishable from "supplied as blank" — which is what the
        // templates key their pending states on.
        $nullable = static function (string $value): ?string {
            return $value === '' ? null : $value;
        };

        $email = $text('email', 191);

        if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $this->flash('error', 'That email address is not valid. Nothing was saved.');

            return Response::redirect(route_url('/admin/profile'));
        }

        foreach (['github_url' => 'GitHub', 'linkedin_url' => 'LinkedIn'] as $key => $label) {
            $url = $text($key, 255);

            if ($url !== '' && !preg_match('#^https?://#i', $url)) {
                $this->flash('error', "The {$label} link must start with http:// or https://. Nothing was saved.");

                return Response::redirect(route_url('/admin/profile'));
            }
        }

        $status = $text('availability_status', 20);

        if (!in_array($status, ['available', 'selective', 'unavailable'], true)) {
            $status = 'selective';
        }

        $this->profiles->update([
            'full_name'           => $text('full_name', 120),
            'monogram'            => mb_strtoupper($text('monogram', 4)),
            'professional_title'  => $text('professional_title', 160),
            'value_proposition'   => $text('value_proposition', 400),
            'technology_line'     => $text('technology_line', 255),
            'short_intro'         => $nullable($text('short_intro', 500)),
            'biography'           => $nullable($text('biography', 5000)),
            'location'            => $text('location', 120),
            'education'           => $text('education', 191),
            'institution'         => $text('institution', 191),
            'availability_status' => $status,
            'availability_note'   => $text('availability_note', 160),
            'email'               => $nullable($email),
            'whatsapp'            => $nullable($text('whatsapp', 40)),
            'github_url'          => $nullable($text('github_url', 255)),
            'linkedin_url'        => $nullable($text('linkedin_url', 255)),
        ]);

        $this->flash('success', 'Profile saved. The public site is already showing it.');

        return Response::redirect(route_url('/admin/profile'));
    }

    /** @param array<string, string> $params */
    public function uploadPhoto(Request $request, array $params = []): Response
    {
        // MUST come before the CSRF check. When a body exceeds post_max_size
        // PHP discards $_POST and $_FILES entirely, so the token is missing
        // and the CSRF branch would report "that form expired" for what is
        // actually an oversized file — the single most confusing upload bug
        // there is, because the real cause is never mentioned.
        if ($this->postDiscarded($request)) {
            $this->flash('error', sprintf(
                'That file is too large for this server to accept at all. PHP discards a request '
                . 'body over post_max_size (%s) before the application sees it. Choose a smaller '
                . 'image, or raise post_max_size and upload_max_filesize in php.ini.',
                (string) ini_get('post_max_size')
            ));

            return Response::redirect(route_url('/admin/profile'));
        }

        if ($response = $this->requireCsrf($request, '/admin/profile')) {
            return $response;
        }

        $file = $_FILES['photo'] ?? null;

        if (!is_array($file)) {
            $this->flash('error', 'No file was received.');

            return Response::redirect(route_url('/admin/profile'));
        }

        $alt = mb_substr(trim((string) ($request->post['alt_text'] ?? '')), 0, 255);

        if ($alt === '') {
            $profile = $this->profiles->current();
            $alt = 'Portrait of ' . ($profile?->fullName ?? '');
        }

        $uploads = $this->uploadService();
        $result  = $uploads->store($file, $alt, 'profile');

        if ($result['media'] === null) {
            $this->flash('error', implode(' ', $result['errors']));

            return Response::redirect(route_url('/admin/profile'));
        }

        // Point the profile at the new image BEFORE removing the old one, so
        // there is never an instant where the site has no photo.
        $previous = $this->profiles->currentPhotoId();
        $this->profiles->setPhoto($result['media']->id);

        if ($previous !== null && $previous !== $result['media']->id) {
            $uploads->delete($previous);
        }

        $this->flash('success', 'Photograph updated. It is live everywhere it appears.');

        return Response::redirect(route_url('/admin/profile'));
    }

    /** @param array<string, string> $params */
    public function removePhoto(Request $request, array $params = []): Response
    {
        if ($response = $this->requireCsrf($request, '/admin/profile')) {
            return $response;
        }

        $current = $this->profiles->currentPhotoId();

        if ($current === null) {
            $this->flash('info', 'There is no photograph to remove.');

            return Response::redirect(route_url('/admin/profile'));
        }

        // Detach first: the foreign key is ON DELETE RESTRICT, so the media
        // row cannot be removed while the profile still points at it.
        $this->profiles->setPhoto(null);
        $this->uploadService()->delete($current);

        $this->flash('success', 'Photograph removed. The monogram fallback is showing again.');

        return Response::redirect(route_url('/admin/profile'));
    }

    /**
     * Change the alternative text without re-uploading the image.
     *
     * Its own route because alt text is the one field a sighted author is
     * most likely to want to fix after the fact, and re-encoding four
     * variants to correct a sentence would be absurd.
     *
     * @param array<string, string> $params
     */
    public function updateAlt(Request $request, array $params = []): Response
    {
        if ($response = $this->requireCsrf($request, '/admin/profile')) {
            return $response;
        }

        $mediaId = $this->profiles->currentPhotoId();

        if ($mediaId === null) {
            $this->flash('error', 'There is no photograph to describe.');

            return Response::redirect(route_url('/admin/profile'));
        }

        $alt = mb_substr(trim((string) ($request->post['alt_text'] ?? '')), 0, 255);

        (new MediaRepository())->updateAltText($mediaId, $alt === '' ? null : $alt);
        $this->profiles->forget();

        $this->flash('success', 'Image description saved.');

        return Response::redirect(route_url('/admin/profile'));
    }

    /**
     * True when PHP threw the request body away for exceeding post_max_size.
     *
     * The signature is a POST that declared a content length but arrived with
     * nothing parsed out of it.
     */
    private function postDiscarded(Request $request): bool
    {
        $declared = (int) ($_SERVER['CONTENT_LENGTH'] ?? 0);

        return $declared > 0 && $request->post === [] && $_FILES === [];
    }

    private function uploadService(): MediaUploadService
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
    private function limits(): array
    {
        $configured = (int) Config::get('uploads.max_bytes', 5 * 1024 * 1024);
        $uploadMax  = self::iniBytes((string) ini_get('upload_max_filesize'));
        $postMax    = self::iniBytes((string) ini_get('post_max_size'));

        $effective = min(array_filter([$configured, $uploadMax, $postMax]));

        return [
            'max'        => ImageValidator::formatBytes((int) $effective),
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
}
