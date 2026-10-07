-- ---------------------------------------------------------------------------
-- services + process_steps
--
-- The last two sections of the home page still carrying static structure.
-- Both are the same shape as skill_categories — an ordered, visibility-flagged
-- list — and that column set is reused verbatim rather than inventing a third
-- convention for the third ordered list in this schema.
--
-- There is deliberately no `number` column on process_steps. The step number
-- the visitor reads is its position, and storing both would let them disagree;
-- the template counts, so sort_order is the single source of truth.
--
-- Neither table is seeded. Services (Q10) and the process (Q11) are the two
-- things on this site that cannot be written by anyone but the owner, and an
-- empty table is the honest state until he writes them.
-- ---------------------------------------------------------------------------

CREATE TABLE services (
    id          SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
    title       VARCHAR(120) NOT NULL,
    description VARCHAR(500) NOT NULL DEFAULT '',
    sort_order  SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    is_visible  TINYINT(1)   NOT NULL DEFAULT 1,
    created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY ix_service_order (is_visible, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE process_steps (
    id          SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
    title       VARCHAR(120) NOT NULL,
    description VARCHAR(500) NOT NULL DEFAULT '',
    sort_order  SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    is_visible  TINYINT(1)   NOT NULL DEFAULT 1,
    created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY ix_process_order (is_visible, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
