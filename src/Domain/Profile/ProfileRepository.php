<?php

declare(strict_types=1);

namespace App\Domain\Profile;

use App\Core\Database;
use App\Domain\Media\MediaRepository;

/**
 * The only place that reads the profile table.
 *
 * current() is memoised for the lifetime of the request because the profile
 * is needed by the header, the hero, the about section, the footer and the
 * JSON-LD — five call sites that must not become five queries.
 */
final class ProfileRepository
{
    private ?Profile $cached = null;
    private bool $loaded = false;

    public function __construct(private readonly MediaRepository $media)
    {
    }

    public function current(): ?Profile
    {
        if ($this->loaded) {
            return $this->cached;
        }

        $this->loaded = true;

        // One query, joined to media — the photo is never a second round trip.
        $row = Database::connection()->query(
            'SELECT p.*,
                    m.id          AS media_id,
                    m.storage_key AS media_storage_key,
                    m.mime_type   AS media_mime_type,
                    m.width       AS media_width,
                    m.height      AS media_height,
                    m.alt_text    AS media_alt_text,
                    UNIX_TIMESTAMP(m.updated_at) AS media_updated_ts
             FROM profile p
             LEFT JOIN media m ON m.id = p.photo_media_id
             WHERE p.id = 1
             LIMIT 1'
        )->fetch();

        if ($row === false) {
            return $this->cached = null;
        }

        $photo = null;

        if ($row['media_id'] !== null) {
            $mediaId  = (int) $row['media_id'];
            $variants = $this->media->variantsFor([$mediaId])[$mediaId] ?? [];

            $photo = $this->media->hydrate([
                'id'          => $mediaId,
                'storage_key' => $row['media_storage_key'],
                'mime_type'   => $row['media_mime_type'],
                'width'       => $row['media_width'],
                'height'      => $row['media_height'],
                'alt_text'    => $row['media_alt_text'],
                'updated_ts'  => $row['media_updated_ts'],
            ], $variants);
        }

        return $this->cached = new Profile(
            fullName:           (string) $row['full_name'],
            monogram:           (string) ($row['monogram'] ?? ''),
            professionalTitle:  (string) $row['professional_title'],
            valueProposition:   (string) $row['value_proposition'],
            technologyLine:     (string) $row['technology_line'],
            shortIntro:         $row['short_intro'] !== null ? (string) $row['short_intro'] : null,
            biography:          $row['biography'] !== null ? (string) $row['biography'] : null,
            location:           (string) $row['location'],
            education:          (string) $row['education'],
            institution:        (string) $row['institution'],
            availabilityStatus: (string) $row['availability_status'],
            availabilityNote:   (string) $row['availability_note'],
            email:              $row['email'] !== null ? (string) $row['email'] : null,
            whatsapp:           $row['whatsapp'] !== null ? (string) $row['whatsapp'] : null,
            githubUrl:          $row['github_url'] !== null ? (string) $row['github_url'] : null,
            linkedinUrl:        $row['linkedin_url'] !== null ? (string) $row['linkedin_url'] : null,
            photo:              $photo,
        );
    }
}
