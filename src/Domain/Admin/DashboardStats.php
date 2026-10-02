<?php

declare(strict_types=1);

namespace App\Domain\Admin;

use App\Core\Config;
use App\Domain\Auth\PasswordHasher;
use App\Domain\Media\ImageProcessor;
use App\Domain\Media\ImageValidator;
use App\Core\Database;
use App\Http\Controllers\Admin\AdminController;
use PDO;

/**
 * Numbers and checks for the admin dashboard.
 *
 * Separate from ProjectRepository on purpose. That class is the PUBLIC read
 * path and every one of its methods filters to published rows; the dashboard
 * needs to count drafts, which is the opposite requirement. Keeping the two
 * apart means the public repository never grows a method that returns
 * unpublished content.
 */
final class DashboardStats
{
    /** @return array<string, int> */
    public function counts(): array
    {
        $row = Database::connection()->query(
            "SELECT
                (SELECT COUNT(*) FROM projects WHERE deleted_at IS NULL)                              AS projects_total,
                (SELECT COUNT(*) FROM projects WHERE deleted_at IS NULL AND publication='published')  AS projects_published,
                (SELECT COUNT(*) FROM projects WHERE deleted_at IS NULL AND publication='draft')      AS projects_draft,
                (SELECT COUNT(*) FROM projects WHERE deleted_at IS NULL AND is_featured=1
                                                 AND publication='published')                         AS projects_featured,
                (SELECT COUNT(*) FROM skills)                                                         AS skills,
                (SELECT COUNT(*) FROM media)                                                          AS media,
                (SELECT COUNT(*) FROM project_sections)                                               AS sections"
        )->fetch(PDO::FETCH_ASSOC);

        return array_map('intval', $row ?: []);
    }

    /**
     * The content-completeness checklist.
     *
     * This is the part of the dashboard that keeps a portfolio from quietly
     * rotting. Each item is a real query, not a reminder someone has to
     * remember to tick off, and each names the phase that will let it be
     * fixed from the CMS.
     *
     * @return list<array{label:string, done:bool, detail:string}>
     */
    public function checklist(): array
    {
        $pdo   = Database::connection();
        $items = [];

        // Profile photo
        $hasPhoto = (bool) $pdo->query(
            'SELECT COUNT(*) FROM profile WHERE id = 1 AND photo_media_id IS NOT NULL'
        )->fetchColumn();
        $items[] = [
            'label'  => 'Profile photograph uploaded',
            'done'   => $hasPhoto,
            'detail' => $hasPhoto
                ? 'Shown in the hero, the about section and every social preview'
                : 'The monogram fallback is showing — upload one under Profile',
        ];

        // CV
        $hasCv = (bool) $pdo->query(
            'SELECT COUNT(*) FROM profile WHERE id = 1 AND cv_media_id IS NOT NULL'
        )->fetchColumn();
        $items[] = [
            'label'  => 'CV uploaded',
            'done'   => $hasCv,
            'detail' => $hasCv ? 'Download button is live' : 'Button stays hidden until a file exists, so it cannot 404',
        ];

        // Contact channels
        $contact = $pdo->query(
            'SELECT email IS NOT NULL AS has_email, whatsapp IS NOT NULL AS has_whatsapp,
                    linkedin_url IS NOT NULL AS has_linkedin
             FROM profile WHERE id = 1'
        )->fetch(PDO::FETCH_ASSOC) ?: [];
        $missingContact = array_keys(array_filter([
            'email'    => !($contact['has_email'] ?? 0),
            'WhatsApp' => !($contact['has_whatsapp'] ?? 0),
            'LinkedIn' => !($contact['has_linkedin'] ?? 0),
        ]));
        $items[] = [
            'label'  => 'Contact channels set',
            'done'   => $missingContact === [],
            'detail' => $missingContact === [] ? 'Email, WhatsApp and LinkedIn all present'
                                               : 'Missing: ' . implode(', ', $missingContact),
        ];

        // Featured projects without a thumbnail
        $noThumb = (int) $pdo->query(
            "SELECT COUNT(*) FROM projects
             WHERE deleted_at IS NULL AND publication='published' AND is_featured=1
               AND thumbnail_media_id IS NULL"
        )->fetchColumn();
        $items[] = [
            'label'  => 'Featured projects have a screenshot',
            'done'   => $noThumb === 0,
            'detail' => $noThumb === 0 ? 'Every featured project shows a real preview'
                                       : $noThumb . ' still showing the placeholder frame — upload one under Projects',
        ];

        // Projects missing the high-signal case-study sections
        $thin = $pdo->query(
            "SELECT p.title,
                    SUM(s.section_key IN ('role','technical','decisions','outcome','lessons')) AS deep
             FROM projects p
             LEFT JOIN project_sections s ON s.project_id = p.id
             WHERE p.deleted_at IS NULL AND p.publication = 'published'
             GROUP BY p.id, p.title
             HAVING deep < 3"
        )->fetchAll(PDO::FETCH_ASSOC);
        $items[] = [
            'label'  => 'Case studies show engineering judgement',
            'done'   => $thin === [],
            'detail' => $thin === []
                ? 'Role, technical implementation, decisions, outcome and lessons are written'
                : 'Thin: ' . implode(', ', array_column($thin, 'title'))
                  . ' — "Key decisions" and "Lessons learned" are what demonstrate judgement',
        ];

        // Projects with no technology tags
        $untagged = $pdo->query(
            "SELECT p.title FROM projects p
             LEFT JOIN project_technologies t ON t.project_id = p.id
             WHERE p.deleted_at IS NULL AND p.publication='published'
             GROUP BY p.id, p.title HAVING COUNT(t.skill_id) = 0"
        )->fetchAll(PDO::FETCH_COLUMN);
        $items[] = [
            'label'  => 'Every published project lists its stack',
            'done'   => $untagged === [],
            'detail' => $untagged === [] ? 'All tagged'
                : 'No stack recorded: ' . implode(', ', $untagged) . ' — nothing was assumed',
        ];

        return $items;
    }

    /**
     * Environment health — surfaced BEFORE it is needed rather than as a
     * confusing failure halfway through an upload.
     *
     * @return list<array{label:string, ok:bool, detail:string}>
     */
    public function health(): array
    {
        $uploads = dirname(__DIR__, 3) . '/public/uploads';
        $storage = dirname(__DIR__, 3) . '/storage/uploads';

        return [
            [
                'label'  => 'PHP version',
                'ok'     => PHP_VERSION_ID >= 80200,
                'detail' => PHP_VERSION . (PHP_VERSION_ID >= 80200 ? '' : ' — 8.2 or newer required'),
            ],
            [
                'label'  => 'Password hashing',
                'ok'     => PasswordHasher::argon2Available(),
                'detail' => PasswordHasher::describe(),
            ],
            [
                'label'  => 'GD image library',
                'ok'     => extension_loaded('gd'),
                'detail' => extension_loaded('gd')
                    ? 'Available — uploads are resized and re-encoded'
                    : 'Missing — profile photo uploads cannot work at all',
            ],
            [
                'label'  => 'fileinfo extension',
                'ok'     => extension_loaded('fileinfo'),
                'detail' => extension_loaded('fileinfo')
                    ? 'Available — real MIME detection on upload'
                    : 'Missing — uploads cannot be validated safely',
            ],
            [
                'label'  => 'Upload directories writable',
                'ok'     => is_writable($uploads) && is_writable($storage),
                'detail' => (is_writable($uploads) ? '' : 'public/uploads not writable. ')
                          . (is_writable($storage) ? '' : 'storage/uploads not writable.')
                          ?: 'Both writable',
            ],
            $this->uploadSizeCheck(),
            $this->imageFormatCheck(),
            [
                'label'  => 'EXIF orientation',
                'ok'     => extension_loaded('exif'),
                'detail' => extension_loaded('exif')
                    ? 'Available — a phone photo is rotated upright before cropping'
                    : 'Missing — GD ignores EXIF, so a phone photo may be stored sideways',
            ],
            [
                'label'  => 'Debug mode off',
                'ok'     => !Config::isDebug(),
                'detail' => Config::isDebug()
                    ? 'Debug is ON — correct locally, must be off in production'
                    : 'Errors show no internal detail',
            ],
        ];
    }

    /**
     * php.ini versus the application's configured ceiling.
     *
     * This is the check that earns its place. When upload_max_filesize is
     * below the configured limit, the form promises a size the server will
     * reject — and when post_max_size is the smaller of the two, PHP throws
     * the request body away before the application runs, so there is no
     * $_FILES entry and no error code to report. Both failures are silent at
     * the point of use, which is exactly why they belong here instead.
     *
     * @return array{label:string, ok:bool, detail:string}
     */
    private function uploadSizeCheck(): array
    {
        $configured = (int) Config::get('uploads.max_bytes', 5 * 1024 * 1024);
        $uploadMax  = AdminController::iniBytes((string) ini_get('upload_max_filesize'));
        $postMax    = AdminController::iniBytes((string) ini_get('post_max_size'));

        $problems = [];

        if ($uploadMax > 0 && $uploadMax < $configured) {
            $problems[] = sprintf(
                'upload_max_filesize is %s, below the configured %s',
                (string) ini_get('upload_max_filesize'),
                ImageValidator::formatBytes($configured)
            );
        }

        $effective = (int) min(array_filter([$configured, $uploadMax, $postMax]));

        // Measured against what we actually accept, not against
        // upload_max_filesize. XAMPP ships 40M for both, which is fine here:
        // the application caps uploads at 5 MB, so the largest body it can
        // produce is nowhere near 40M. The real failure is a post_max_size
        // below the accepted size, because PHP then throws the body away with
        // no error code for the application to report.
        if ($postMax > 0 && $postMax <= $effective) {
            $problems[] = sprintf(
                'post_max_size (%s) is not larger than the %s this site accepts, '
                . 'so an upload at the limit is discarded before PHP can report it',
                (string) ini_get('post_max_size'),
                ImageValidator::formatBytes($effective)
            );
        }

        return [
            'label'  => 'Upload size limits agree',
            'ok'     => $problems === [],
            'detail' => $problems === []
                ? 'Effective limit ' . ImageValidator::formatBytes($effective)
                : implode('; ', $problems) . '. Raise both in php.ini and restart Apache.',
        ];
    }

    /**
     * Which output formats this GD build can actually write.
     *
     * Not a failure: a missing format simply produces fewer variants and the
     * <picture> element falls through to the next source. Reported because
     * "why is the site serving JPEG" otherwise has no visible answer.
     *
     * @return array{label:string, ok:bool, detail:string}
     */
    private function imageFormatCheck(): array
    {
        $wanted = [];

        /** @var array<string, array{formats:list<string>}> $variants */
        $variants = (array) Config::get('uploads.variants', []);

        foreach ($variants as $spec) {
            foreach ($spec['formats'] as $format) {
                $wanted[$format] = true;
            }
        }

        $missing = array_keys(array_filter(
            $wanted,
            static fn (bool $_, string $format): bool => !ImageProcessor::supports($format),
            ARRAY_FILTER_USE_BOTH
        ));

        return [
            'label'  => 'Image formats available',
            'ok'     => $missing === [],
            'detail' => $missing === []
                ? strtoupper(implode(', ', array_keys($wanted))) . ' — all configured variants can be written'
                : 'GD cannot write ' . strtoupper(implode(', ', $missing))
                  . '; those variants are skipped and the next format is served instead',
        ];
    }
}
