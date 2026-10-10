<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Domain\Message\MessageNotifier;
use App\Domain\Message\MessageRepository;
use App\Domain\Message\MessageThrottle;
use App\Domain\Message\MessageWriter;

/**
 * The public contact form.
 *
 * The only route in this application a stranger can POST to, which is why
 * every decision below is about what happens when the sender is not acting
 * in good faith.
 *
 * NO CSRF TOKEN HERE, deliberately. CSRF protects a victim from an action
 * performed as them; the action here is sending the owner a message, which
 * an attacker can simply do directly without involving a victim. It stops no
 * bot either, since a bot can fetch a token as easily as a form. And it is
 * not free: Csrf::token() starts a session, so a token on the home page
 * means a session file per anonymous visitor on shared hosting and a
 * Set-Cookie on the most-requested page on the site. Every admin route keeps
 * its CSRF check, where a victim and a privileged action both exist.
 *
 * What is here instead: a honeypot, a per-address rate limit, and real
 * validation. No CAPTCHA — that means a third-party script, and Phase 9
 * spent real effort getting the Content-Security-Policy down to 'self'.
 */
final class ContactController extends Controller
{
    /** Hidden from people, irresistible to a naive bot. */
    private const HONEYPOT = 'website';

    private const MAX_BODY = 5000;
    private const MIN_BODY = 10;

    /** @param array<string, string> $params */
    public function submit(Request $request, array $params = []): Response
    {
        $name    = $this->field($request, 'name', 120);
        $email   = $this->field($request, 'email', 191);
        $subject = $this->field($request, 'subject', 160);
        $body    = trim((string) ($request->post['message'] ?? ''));

        // ---- the honeypot ------------------------------------------------
        //
        // A hit returns the SAME success the sender would have seen, and
        // stores nothing. Reporting the rejection would tell whoever wrote
        // the bot exactly which field gave it away, which is free help in
        // writing the next one.
        if (trim((string) ($request->post[self::HONEYPOT] ?? '')) !== '') {
            return $this->thanks();
        }

        // ---- validation --------------------------------------------------
        $errors = [];

        if ($name === '') {
            $errors['name'] = 'Please tell me your name.';
        }

        if ($email === '' || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $errors['email'] = 'That email address does not look right — I need it to reply.';
        }

        if (mb_strlen($body) < self::MIN_BODY) {
            $errors['message'] = 'A little more detail would help me answer properly.';
        } elseif (mb_strlen($body) > self::MAX_BODY) {
            $errors['message'] = sprintf(
                'That is longer than %s characters. Send the short version and we can go deeper by email.',
                number_format(self::MAX_BODY),
            );
        }

        if ($errors !== []) {
            // Everything typed comes back. Someone who wrote three paragraphs
            // and mistyped their address must not lose the three paragraphs.
            return $this->backToForm($errors, compact('name', 'email', 'subject') + ['message' => $body]);
        }

        // ---- rate limit ----------------------------------------------------
        $ip       = $this->clientIp();
        $throttle = new MessageThrottle();

        if ($throttle->isBlocked($ip)) {
            $minutes = $throttle->retryAfterMinutes($ip);

            return $this->backToForm(
                ['message' => sprintf(
                    'You have sent several messages already. Try again in about %d minute%s — '
                    . 'or use WhatsApp, which is faster anyway.',
                    $minutes,
                    $minutes === 1 ? '' : 's',
                )],
                compact('name', 'email', 'subject') + ['message' => $body],
            );
        }

        // ---- store, THEN notify --------------------------------------------
        //
        // This order is the whole design. The row is the message; the email
        // is a convenience that may never arrive. See MessageNotifier.
        $id = (new MessageWriter())->store(
            $name,
            $email,
            $subject,
            $body,
            $ip,
            (string) ($_SERVER['HTTP_USER_AGENT'] ?? ''),
        );

        $stored = (new MessageRepository())->find($id);

        if ($stored !== null) {
            $notifier = new MessageNotifier($this->profiles->current()?->email);

            if (!$notifier->notify($stored)) {
                // Logged, never surfaced. The sender's message arrived; the
                // fact that the owner's mail server did not cooperate is not
                // their problem and not their business.
                error_log('[contact] message ' . $id . ' stored; email notification not sent');
            }
        }

        return $this->thanks();
    }

    /**
     * The visitor-facing success, which is the same whether the message was
     * stored or silently discarded as a honeypot hit.
     */
    private function thanks(): Response
    {
        $this->flashContact('success', 'Thank you — your message has been received. I will reply to the address you gave.');

        return Response::redirect(route_url('/') . '#contact');
    }

    /**
     * @param array<string, string> $errors
     * @param array<string, string> $old
     */
    private function backToForm(array $errors, array $old): Response
    {
        \App\Core\Session::start();
        $_SESSION['_contact_errors'] = $errors;
        $_SESSION['_contact_old']    = $old;

        $this->flashContact('error', 'Your message was not sent — see the note under the field below.');

        return Response::redirect(route_url('/') . '#contact');
    }

    /**
     * A session is started ONLY on this path.
     *
     * Rendering the form starts nothing, so an ordinary visitor who never
     * submits is never given a cookie or a session file. Someone who has
     * posted the form has already chosen to interact, and carrying their
     * draft back to them is worth the cookie.
     */
    private function flashContact(string $type, string $message): void
    {
        \App\Core\Session::start();
        $_SESSION['_flash'] = ['type' => $type, 'message' => $message];
    }

    private function field(Request $request, string $key, int $max): string
    {
        return mb_substr(trim((string) ($request->post[$key] ?? '')), 0, $max);
    }

    /**
     * REMOTE_ADDR only — never a forwarded header.
     *
     * X-Forwarded-For is set by the client and trusted only when a proxy you
     * control overwrote it. Reading it here would let anyone reset their own
     * rate limit by changing one header, which is worse than no limit because
     * it looks like one.
     */
    private function clientIp(): ?string
    {
        $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? '');

        return $ip === '' ? null : $ip;
    }
}
