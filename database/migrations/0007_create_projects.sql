-- ---------------------------------------------------------------------------
-- projects — the core content type.
--
-- ORDERING. sort_order is a dense integer and a reorder rewrites the affected
-- rows in one transaction. LexoRank and fractional indexing exist to make a
-- reorder O(1) instead of O(n); that matters for a Jira backlog of thousands
-- of issues reordered concurrently by many people. This is a portfolio: fewer
-- than twenty rows, reordered occasionally by one person. Rewriting twenty
-- integers in a single UPDATE is correct, obvious, and cannot drift or run out
-- of gaps. Choosing LexoRank here would be sophistication for its own sake.
--
-- PUBLICATION. status and is_featured are independent, which yields three
-- presentation tiers from two flags: featured, published-not-featured, draft.
-- Filtering on them lives in the repository, never in a template.
--
-- SOFT DELETE + UNIQUE SLUG. A plain UNIQUE(slug) would make a slug
-- unusable forever once a project is soft-deleted. MySQL and MariaDB have no
-- partial indexes, so the standard answer is a generated column that is 1
-- while the row is live and NULL once it is deleted: a unique index permits
-- many NULLs, so uniqueness applies only to live rows. Verified on MariaDB
-- 10.11 — a duplicate live slug is rejected, a slug is reusable after soft
-- deletion, and several soft-deleted rows may share one slug.
-- ---------------------------------------------------------------------------

CREATE TABLE projects (
    id               INT UNSIGNED NOT NULL AUTO_INCREMENT,

    slug             VARCHAR(160) NOT NULL,
    title            VARCHAR(160) NOT NULL,

    -- The eyebrow above the project title, e.g. "Business automation · SaaS".
    category_label   VARCHAR(120) NOT NULL DEFAULT '',

    -- One sentence naming the real-world problem. Recruiters read this line
    -- more than any other project text.
    problem_statement VARCHAR(500) NOT NULL DEFAULT '',

    -- Two or three sentences under the problem.
    summary          TEXT         NULL,

    -- What the author personally designed and built.
    role             VARCHAR(200) NOT NULL DEFAULT '',

    year_label       VARCHAR(40)  NOT NULL DEFAULT '',

    -- Honest current state: "Functional prototype", "In development".
    status_label     VARCHAR(80)  NOT NULL DEFAULT '',

    publication      ENUM('draft', 'published', 'archived') NOT NULL DEFAULT 'draft',
    is_featured      TINYINT(1)   NOT NULL DEFAULT 0,
    sort_order       SMALLINT UNSIGNED NOT NULL DEFAULT 0,

    -- Rendered only when present. A dead link is worse than no link.
    github_url       VARCHAR(255) NULL,
    live_url         VARCHAR(255) NULL,

    -- BIGINT to match media.id exactly. A foreign key whose column type
    -- differs from the referenced column is rejected with errno 150.
    thumbnail_media_id BIGINT UNSIGNED NULL,
    cover_media_id     BIGINT UNSIGNED NULL,

    reading_minutes  TINYINT UNSIGNED NOT NULL DEFAULT 0,

    created_at       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at       DATETIME     NULL,

    -- 1 while live, NULL once soft-deleted. See the note above.
    alive            TINYINT UNSIGNED GENERATED ALWAYS AS (IF(deleted_at IS NULL, 1, NULL)) VIRTUAL,

    PRIMARY KEY (id),
    UNIQUE KEY uq_project_slug_alive (slug, alive),

    -- Serves the home-page query directly:
    --   WHERE publication='published' AND deleted_at IS NULL
    --   ORDER BY is_featured DESC, sort_order ASC
    KEY ix_project_listing (publication, deleted_at, is_featured, sort_order),

    CONSTRAINT fk_project_thumbnail
        FOREIGN KEY (thumbnail_media_id) REFERENCES media (id)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_project_cover
        FOREIGN KEY (cover_media_id) REFERENCES media (id)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
