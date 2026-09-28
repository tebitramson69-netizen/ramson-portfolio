-- ---------------------------------------------------------------------------
-- profile — a singleton row holding the site's identity.
--
-- Singleton rather than a key/value bag because these fields are a fixed,
-- known shape with different types and constraints. A key/value table would
-- turn every one of them into a nullable string and move validation into
-- application code.
--
-- The singleton is enforced by a CHECK on the primary key, so a second row is
-- rejected by the database rather than by convention.
--
-- photo_media_id is ON DELETE RESTRICT: a media row that is in use cannot be
-- deleted out from under the site. Removing the photo means setting this
-- column to NULL first, which is exactly what the admin "Remove photo" action
-- will do.
-- ---------------------------------------------------------------------------

CREATE TABLE profile (
    id                  TINYINT UNSIGNED NOT NULL DEFAULT 1,

    full_name           VARCHAR(120)  NOT NULL,
    professional_title  VARCHAR(160)  NOT NULL,

    -- The hero statement.
    value_proposition   VARCHAR(400)  NOT NULL,

    -- The supporting technology line under it.
    technology_line     VARCHAR(255)  NOT NULL DEFAULT '',

    short_intro         TEXT          NULL,
    biography           TEXT          NULL,

    location            VARCHAR(120)  NOT NULL DEFAULT '',
    education           VARCHAR(191)  NOT NULL DEFAULT '',
    institution         VARCHAR(191)  NOT NULL DEFAULT '',

    availability_status ENUM('available', 'selective', 'unavailable')
                        NOT NULL DEFAULT 'selective',
    availability_note   VARCHAR(160)  NOT NULL DEFAULT '',

    email               VARCHAR(191)  NULL,
    whatsapp            VARCHAR(40)   NULL,
    github_url          VARCHAR(255)  NULL,
    linkedin_url        VARCHAR(255)  NULL,

    photo_media_id      BIGINT UNSIGNED NULL,
    cv_media_id         BIGINT UNSIGNED NULL,

    created_at          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
                                 ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    CONSTRAINT ck_profile_singleton CHECK (id = 1),

    CONSTRAINT fk_profile_photo
        FOREIGN KEY (photo_media_id) REFERENCES media (id)
        ON DELETE RESTRICT ON UPDATE CASCADE,

    CONSTRAINT fk_profile_cv
        FOREIGN KEY (cv_media_id) REFERENCES media (id)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
