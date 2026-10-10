-- ---------------------------------------------------------------------------
-- messages — what the public contact form captures.
--
-- The database is the channel, not the notification. An email can be
-- disabled by the host, rejected by a spam filter or simply missed; a row is
-- none of those things. bin/../MessageNotifier sends mail as a convenience
-- AFTER this row is committed, and a failure there never loses the message.
--
-- ip_address is VARBINARY(16) holding the packed form from inet_pton, the
-- same convention login_attempts (0010) set: one column for IPv4 and IPv6,
-- compared exactly rather than as a string with several spellings.
--
-- There is no separate throttle table. The rate limiter counts rows HERE for
-- an address inside a window, which is what ix_message_ip exists for — the
-- messages are their own evidence, and a second table recording that a
-- message arrived, beside the message, would be two places to disagree.
-- ---------------------------------------------------------------------------

CREATE TABLE messages (
    id         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,

    name       VARCHAR(120)  NOT NULL,
    email      VARCHAR(191)  NOT NULL,

    -- Optional. A sender who writes nothing here still has a message worth
    -- reading, so an empty subject is a blank string rather than a rejection.
    subject    VARCHAR(160)  NOT NULL DEFAULT '',

    body       TEXT          NOT NULL,

    ip_address VARBINARY(16) NULL,
    user_agent VARCHAR(255)  NOT NULL DEFAULT '',

    -- Archived is not deleted. A message that has been dealt with leaves the
    -- inbox without leaving the database, because "who contacted me in March"
    -- is a question worth being able to answer.
    status     ENUM('unread', 'read', 'archived') NOT NULL DEFAULT 'unread',

    created_at DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    read_at    DATETIME      NULL,

    PRIMARY KEY (id),
    KEY ix_message_status (status, created_at),
    KEY ix_message_ip (ip_address, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
