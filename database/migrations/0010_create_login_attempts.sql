-- ---------------------------------------------------------------------------
-- login_attempts — throttling evidence.
--
-- Every attempt is recorded, successful or not, keyed by BOTH the submitted
-- identifier and the client IP. Throttling on the identifier alone lets a
-- botnet spread attempts across addresses; throttling on IP alone lets one
-- address lock a legitimate user out of their own account by attacking it.
-- Recording both allows either signal to trip the limit.
--
-- ip_address is VARBINARY(16) holding the packed form from inet_pton, which
-- stores IPv4 and IPv6 in the same column and compares exactly, unlike a
-- string that can arrive in several equivalent spellings.
-- ---------------------------------------------------------------------------

CREATE TABLE login_attempts (
    id           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,

    -- The submitted email, lowercased. Stored to throttle per account; it is
    -- not a foreign key because most failures name an account that does not
    -- exist.
    identifier   VARCHAR(191) NOT NULL,

    ip_address   VARBINARY(16) NULL,
    user_agent   VARCHAR(255) NOT NULL DEFAULT '',
    succeeded    TINYINT(1)   NOT NULL DEFAULT 0,
    attempted_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    KEY ix_attempt_identifier (identifier, attempted_at),
    KEY ix_attempt_ip (ip_address, attempted_at),
    KEY ix_attempt_pruning (attempted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
