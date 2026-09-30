-- ---------------------------------------------------------------------------
-- admin_users
--
-- One row. There is no registration route anywhere in the application: the
-- account is created by bin/create-admin.php from the command line. A route
-- that does not exist cannot be attacked, cannot be brute-forced, and cannot
-- be left accidentally reachable.
--
-- password_hash holds a modern hash string. Argon2id produces ~96 characters
-- at the parameters used here and bcrypt 60, but the column is generous so a
-- future algorithm change needs no migration.
-- ---------------------------------------------------------------------------

CREATE TABLE admin_users (
    id              TINYINT UNSIGNED NOT NULL AUTO_INCREMENT,

    name            VARCHAR(120) NOT NULL,
    email           VARCHAR(191) NOT NULL,
    password_hash   VARCHAR(255) NOT NULL,

    -- Rotated on every successful login. A stored token is compared against
    -- the session copy on each request, so changing the password or forcing a
    -- sign-out invalidates every existing session at once.
    session_token   CHAR(64)     NOT NULL,

    last_login_at   DATETIME     NULL,
    last_login_ip   VARBINARY(16) NULL,

    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    UNIQUE KEY uq_admin_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
