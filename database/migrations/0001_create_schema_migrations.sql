-- Migration ledger.
-- Forward-only: there are no down migrations. For a single-maintainer project
-- a rollback script is another thing to keep correct and is almost never the
-- right recovery tool — restoring a backup is. A mistake is corrected by
-- writing the next migration.

CREATE TABLE IF NOT EXISTS schema_migrations (
    version     VARCHAR(191) NOT NULL,
    applied_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (version)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
