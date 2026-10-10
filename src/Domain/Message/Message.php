<?php

declare(strict_types=1);

namespace App\Domain\Message;

final class Message
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $email,
        public readonly string $subject,
        public readonly string $body,
        public readonly string $status,
        public readonly string $createdAt,
        public readonly ?string $readAt = null,
        public readonly ?string $ipAddress = null,
        public readonly string $userAgent = '',
    ) {
    }

    /** @param array<string, mixed> $row */
    public static function fromRow(array $row): self
    {
        // Unpacked back to a readable address here rather than in the
        // template: inet_ntop() on a VARBINARY is a storage detail, and a
        // template that knows about it is a template that can get it wrong.
        $ip = null;

        if (($row['ip_address'] ?? null) !== null && $row['ip_address'] !== '') {
            $readable = @inet_ntop((string) $row['ip_address']);
            $ip = $readable === false ? null : $readable;
        }

        return new self(
            id:        (int) $row['id'],
            name:      (string) $row['name'],
            email:     (string) $row['email'],
            subject:   (string) ($row['subject'] ?? ''),
            body:      (string) $row['body'],
            status:    (string) $row['status'],
            createdAt: (string) $row['created_at'],
            readAt:    $row['read_at'] !== null ? (string) $row['read_at'] : null,
            ipAddress: $ip,
            userAgent: (string) ($row['user_agent'] ?? ''),
        );
    }

    public function isUnread(): bool
    {
        return $this->status === 'unread';
    }

    public function isArchived(): bool
    {
        return $this->status === 'archived';
    }

    /** What the subject line should read as when the sender left it empty. */
    public function displaySubject(): string
    {
        return $this->subject !== '' ? $this->subject : '(no subject)';
    }

    /**
     * A mailto: that opens a reply already addressed and titled.
     *
     * The admin screen's "reply" is this link rather than a form, because a
     * reply sent from the site would come from the server — landing in spam,
     * and leaving no copy in his own Sent folder where he would look for it.
     */
    public function replyUrl(): string
    {
        $subject = $this->subject !== '' ? 'Re: ' . $this->subject : 'Re: your message';

        return 'mailto:' . $this->email . '?subject=' . rawurlencode($subject);
    }

    /** First line or so, for the list. */
    public function preview(int $length = 110): string
    {
        $flat = trim((string) preg_replace('/\s+/u', ' ', $this->body));

        return mb_strlen($flat) <= $length ? $flat : mb_substr($flat, 0, $length - 1) . '…';
    }
}
