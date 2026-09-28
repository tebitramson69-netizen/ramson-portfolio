-- ---------------------------------------------------------------------------
-- media + media_variants
--
-- The core abstraction of the whole project: an image is an ENTITY, never a
-- filename copied into a template. Every image reference anywhere in the
-- application is a foreign key to media.id.
--
-- Variants live in a child table rather than a JSON column. On MariaDB — the
-- XAMPP default — JSON is an alias for LONGTEXT with no validation: a column
-- declared JSON reports as `longtext` in information_schema and will happily
-- accept the string 'this is not json'. A child table gives real constraints,
-- a unique key per (media, variant, format), cheap addition of AVIF later,
-- and ON DELETE CASCADE cleanup for free.
-- ---------------------------------------------------------------------------

CREATE TABLE media (
    id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,

    -- Opaque, server-generated base name. Never derived from the uploaded
    -- filename: no user-controlled byte ever reaches the filesystem.
    storage_key     CHAR(32)        NOT NULL,

    -- Kept for display in the admin only. Never used to build a path.
    original_name   VARCHAR(255)    NOT NULL,

    mime_type       VARCHAR(100)    NOT NULL,
    width           SMALLINT UNSIGNED NOT NULL,
    height          SMALLINT UNSIGNED NOT NULL,
    byte_size       INT UNSIGNED    NOT NULL,

    -- SHA-256 of the re-encoded original, for integrity checks and to detect
    -- a re-upload of an identical image.
    checksum        CHAR(64)        NOT NULL,

    -- Required whenever the image conveys meaning. Enforced by the upload
    -- form, nullable here because decorative images legitimately have none.
    alt_text        VARCHAR(255)    NULL,

    created_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP
                                             ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    UNIQUE KEY uq_media_storage_key (storage_key),
    KEY ix_media_checksum (checksum)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE media_variants (
    id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    media_id    BIGINT UNSIGNED NOT NULL,

    -- 'hero' | 'about' | 'thumb' | 'og' — the derived sizes.
    variant     VARCHAR(32)     NOT NULL,

    -- 'webp' | 'jpeg' | 'avif'
    format      VARCHAR(16)     NOT NULL,

    -- Path relative to the public uploads root. Still not a name anyone typed.
    path        VARCHAR(255)    NOT NULL,

    width       SMALLINT UNSIGNED NOT NULL,
    height      SMALLINT UNSIGNED NOT NULL,
    byte_size   INT UNSIGNED    NOT NULL,

    created_at  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    UNIQUE KEY uq_variant (media_id, variant, format),
    CONSTRAINT fk_variant_media
        FOREIGN KEY (media_id) REFERENCES media (id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
