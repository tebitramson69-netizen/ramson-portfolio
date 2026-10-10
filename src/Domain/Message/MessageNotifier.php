<?php

declare(strict_types=1);

namespace App\Domain\Message;

use App\Core\Config;

/**
 * Best-effort email notification. NEVER the channel.
 *
 * The contract is one sentence: a failure here must never cost a message.
 * The row is committed before this is called, the return value tells the
 * caller only whether to mention email in the log, and the visitor sees
 * success either way — because the message WAS received.
 *
 * That ordering is not defensive habit. On free and cheap shared hosting
 * mail() is frequently disabled outright, and on the hosts where it works
 * the mail often lands in spam because it is sent by a shared web server
 * with no SPF record for the From address. A design that treated email as
 * the channel would lose messages silently on exactly the hosting this site
 * is going to launch on. The inbox at /admin/messages is the channel.
 *
 * PHP's own mail() and no library. This project has zero runtime
 * dependencies by design, and adding PHPMailer — plus SMTP credentials in
 * config, plus a new way for a deploy to be wrong — for one notification is
 * not a trade worth making. If delivery ever has to be reliable, that is
 * when a mailer earns its place.
 */
final class MessageNotifier
{
    public function __construct(private readonly ?string $to) {}

    /**
     * @return bool Whether mail() accepted it for delivery — which is NOT the
     *              same as it arriving, and is only used for logging.
     */
    public function notify(Message $message): bool
    {
        if ($this->to === null || $this->to === '' || !function_exists('mail')) {
            return false;
        }

        $site = (string) Config::get('app.url', '');

        $subject = 'Portfolio message: ' . $message->displaySubject();

        $body = implode("\n", [
            'From:    ' . $message->name . ' <' . $message->email . '>',
            'Subject: ' . $message->displaySubject(),
            'Sent:    ' . $message->createdAt,
            '',
            $message->body,
            '',
            str_repeat('-', 60),
            'Read and reply in the admin inbox:',
            rtrim($site, '/') . '/admin/messages/' . $message->id,
            '',
            'This notification is a convenience. The message itself is stored',
            'in the database and is not lost if this mail never arrives.',
        ]);

        // From: is the SITE's own address, never the sender's. Forging the
        // visitor's domain in From is what gets a shared host's mail rejected
        // by SPF or binned as spoofing. Reply-To carries the real sender, so
        // hitting reply still reaches them.
        $from = 'no-reply@' . ($this->hostFromUrl($site) ?? 'localhost');

        $headers = implode("\r\n", [
            'From: ' . $from,
            'Reply-To: ' . $this->headerSafe($message->name) . ' <' . $message->email . '>',
            'Content-Type: text/plain; charset=UTF-8',
            'X-Mailer: ramson-portfolio',
        ]);

        // @-suppressed on purpose: a host with mail() disabled raises a
        // warning that would otherwise be rendered into the visitor's
        // response. The failure is handled by the return value.
        return @mail($this->to, $this->headerSafe($subject), $body, $headers);
    }

    /**
     * Strip anything that could inject a header.
     *
     * A name or subject reaching a header unfiltered is the classic mail
     * header injection: a CR or LF in the value ends the header and starts
     * another, which is how a contact form becomes an open relay for Bcc.
     */
    private function headerSafe(string $value): string
    {
        return mb_substr(trim((string) preg_replace('/[\r\n]+/', ' ', $value)), 0, 180);
    }

    private function hostFromUrl(string $url): ?string
    {
        $host = parse_url($url, PHP_URL_HOST);

        return is_string($host) && $host !== '' ? $host : null;
    }
}
