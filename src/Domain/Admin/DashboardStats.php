<?php

declare(strict_types=1);

namespace App\Domain\Admin;

use App\Core\Config;
use App\Domain\Auth\PasswordHasher;
use App\Core\Database;
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
            'detail' => $hasPhoto ? 'Shown in the hero and about section' : 'The RT monogram fallback is showing (Phase 5)',
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
                                       : $noThumb . ' still showing the placeholder frame (Phase 5)',
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
                    ? 'Available — image resizing will work in Phase 5'
                    : 'Missing — profile photo uploads will fail in Phase 5',
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
            [
                'label'  => 'Debug mode off',
                'ok'     => !Config::isDebug(),
                'detail' => Config::isDebug()
                    ? 'Debug is ON — correct locally, must be off in production'
                    : 'Errors show no internal detail',
            ],
        ];
    }
}
