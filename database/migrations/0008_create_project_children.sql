-- ---------------------------------------------------------------------------
-- The four tables owned by a project. All cascade on delete, because none of
-- them is meaningful without its parent.
-- ---------------------------------------------------------------------------

-- Case-study body: ONE ROW PER SECTION, not eighteen columns.
--
-- New section types need no migration, an unsupplied section simply has no
-- row (so it renders nothing at all — never an orphan heading), and the
-- ordering is data rather than template structure.
--
-- section_key is VARCHAR rather than ENUM on purpose: an ENUM would put the
-- vocabulary in the schema and make every addition a migration, which is the
-- exact cost this design exists to avoid. The canonical list lives in
-- App\Domain\Project\SectionKey.
CREATE TABLE project_sections (
    id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    project_id      INT UNSIGNED NOT NULL,
    section_key     VARCHAR(40)  NOT NULL,

    -- Overrides the default heading for this key when set.
    heading         VARCHAR(160) NOT NULL DEFAULT '',

    body            MEDIUMTEXT   NOT NULL,
    sort_order      SMALLINT UNSIGNED NOT NULL DEFAULT 0,

    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    UNIQUE KEY uq_section_per_project (project_id, section_key),
    KEY ix_section_order (project_id, sort_order),
    CONSTRAINT fk_section_project
        FOREIGN KEY (project_id) REFERENCES projects (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE project_features (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    project_id  INT UNSIGNED NOT NULL,
    title       VARCHAR(200) NOT NULL,
    description VARCHAR(500) NOT NULL DEFAULT '',
    sort_order  SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY ix_feature_order (project_id, sort_order),
    CONSTRAINT fk_feature_project
        FOREIGN KEY (project_id) REFERENCES projects (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- Project <-> skill join. is_primary marks the handful of technologies shown
-- on the card; the rest appear only on the case study.
CREATE TABLE project_technologies (
    project_id  INT UNSIGNED NOT NULL,
    skill_id    SMALLINT UNSIGNED NOT NULL,
    is_primary  TINYINT(1)   NOT NULL DEFAULT 0,
    sort_order  SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (project_id, skill_id),
    KEY ix_project_tech_order (project_id, is_primary, sort_order),
    KEY ix_tech_skill (skill_id),
    CONSTRAINT fk_ptech_project
        FOREIGN KEY (project_id) REFERENCES projects (id) ON DELETE CASCADE,
    CONSTRAINT fk_ptech_skill
        FOREIGN KEY (skill_id) REFERENCES skills (id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- Screenshot gallery. RESTRICT on media, like every other media reference:
-- an image still in use cannot be deleted out from under the site.
CREATE TABLE project_images (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    project_id  INT UNSIGNED NOT NULL,
    media_id    BIGINT UNSIGNED NOT NULL,
    caption     VARCHAR(300) NOT NULL DEFAULT '',
    sort_order  SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY ix_image_order (project_id, sort_order),
    CONSTRAINT fk_pimage_project
        FOREIGN KEY (project_id) REFERENCES projects (id) ON DELETE CASCADE,
    CONSTRAINT fk_pimage_media
        FOREIGN KEY (media_id) REFERENCES media (id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
