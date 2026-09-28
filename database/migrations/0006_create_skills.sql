-- ---------------------------------------------------------------------------
-- skill_categories + skills
--
-- The canonical technology vocabulary. One spelling of "MySQL" site-wide,
-- shared by the skills section, the technology strip, project tags and the
-- JSON-LD knowsAbout property.
--
-- There is deliberately NO proficiency column. A percentage or star rating is
-- unverifiable and reads as junior to every engineer who sees it.
-- ---------------------------------------------------------------------------

CREATE TABLE skill_categories (
    id          SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
    name        VARCHAR(80)  NOT NULL,
    slug        VARCHAR(80)  NOT NULL,
    sort_order  SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    is_visible  TINYINT(1)   NOT NULL DEFAULT 1,
    created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_skill_category_slug (slug),
    KEY ix_skill_category_order (is_visible, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE skills (
    id          SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
    category_id SMALLINT UNSIGNED NOT NULL,
    name        VARCHAR(80)  NOT NULL,
    slug        VARCHAR(80)  NOT NULL,
    sort_order  SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    is_visible  TINYINT(1)   NOT NULL DEFAULT 1,
    created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_skill_slug (slug),
    KEY ix_skill_order (category_id, is_visible, sort_order),
    CONSTRAINT fk_skill_category
        FOREIGN KEY (category_id) REFERENCES skill_categories (id)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
