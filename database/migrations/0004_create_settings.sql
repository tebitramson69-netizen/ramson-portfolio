-- ---------------------------------------------------------------------------
-- settings — genuinely heterogeneous, low-cardinality site configuration
-- (site title, meta description, social image). Key/value is the right shape
-- here precisely because the set of keys is open-ended and each is a scalar,
-- which is the opposite of the profile case above.
--
-- `type` lets the repository cast on read, so a boolean setting comes back as
-- a bool rather than the string "1".
-- ---------------------------------------------------------------------------

CREATE TABLE settings (
    setting_key   VARCHAR(100) NOT NULL,
    setting_value TEXT         NULL,
    value_type    ENUM('string', 'text', 'integer', 'boolean') NOT NULL DEFAULT 'string',
    label         VARCHAR(191) NOT NULL DEFAULT '',
    updated_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
                               ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (setting_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
