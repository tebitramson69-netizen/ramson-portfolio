#!/usr/bin/env php
<?php

/**
 * Phase 8 self-check — the contact form and inbox.
 *
 *     php bin/verify-phase8.php
 *
 * CLI only. It writes to the messages table and deletes what it wrote,
 * finishing by asserting the row count is back where it started — the same
 * contract bin/verify-phase7.php keeps, for the same reason: a cleanup that
 * silently failed would leave test rows in a real inbox.
 *
 * The assertion that matters most is the last one. On free and cheap shared
 * hosting PHP's mail() is frequently disabled outright, so "the notification
 * failed and the message survived" is not an edge case here — it is the
 * expected state of the host this site is going to launch on.
 *
 * Exit status is 0 when every assertion passes, 1 otherwise.
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit("This script is CLI-only.\n");
}

require __DIR__ . '/../src/Core/Autoloader.php';

use App\Core\Autoloader;
use App\Core\Config;
use App\Core\Database;
use App\Domain\Message\Message;
use App\Domain\Message\MessageNotifier;
use App\Domain\Message\MessageRepository;
use App\Domain\Message\MessageThrottle;
use App\Domain\Message\MessageWriter;

Autoloader::register('App', __DIR__ . '/../src');
Config::load(__DIR__ . '/../config');

$pass = 0;
$fail = 0;

function heading(string $text): void
{
    echo "\n" . $text . "\n" . str_repeat('-', strlen($text)) . "\n";
}

function check(string $label, bool $ok, string $detail = ''): void
{
    global $pass, $fail;
    $ok ? $pass++ : $fail++;
    printf("  [%s] %s%s\n", $ok ? 'PASS' : 'FAIL', $label, $detail === '' ? '' : ' — ' . $detail);
}

function messageCount(): int
{
    return (int) Database::connection()->query('SELECT COUNT(*) FROM messages')->fetchColumn();
}

echo "Phase 8 self-check\n==================\n";

$before = messageCount();
printf("\nStarting state: %d message(s).\n", $before);

$writer     = new MessageWriter();
$repository = new MessageRepository();

// Addresses from the documentation range, so a real visitor's row can never
// be confused with one of these.
$ipA = '203.0.113.10';
$ipB = '203.0.113.11';

$created = [];

// ------------------------------------------------------------ 1. STORING

heading('1. A message is stored');

$id = $writer->store(
    'Verify Temp Sender',
    'verify-temp@example.com',
    'Verify Temp Subject',
    "First paragraph of a temporary message.\n\nSecond paragraph.",
    $ipA,
    'verify-phase8',
);
$created[] = $id;

$stored = $repository->find($id);

check('store() returns an id and the row reads back', $stored !== null, 'id ' . $id);
check('It starts unread', $stored?->isUnread() === true);
check('read_at is null until it is read', $stored?->readAt === null);
check(
    'The IP round-trips through inet_pton and back',
    $stored?->ipAddress === $ipA,
    (string) $stored?->ipAddress,
);

$empty = $writer->store('No Subject Sender', 'verify-temp2@example.com', '', 'A body with no subject line at all.', $ipA, 'verify-phase8');
$created[] = $empty;

check(
    'An empty subject displays as (no subject) rather than blank',
    $repository->find($empty)?->displaySubject() === '(no subject)',
);

// ------------------------------------------------------------ 2. THROTTLE

heading('2. Rate limiting is per address');

$throttle = new MessageThrottle();
$max      = $throttle->maxMessages();

check('A limit is configured', $max > 0, $max . ' per ' . $throttle->decaySeconds() . 's');

// Two already exist for ipA; fill to the limit.
while ($throttle->recentCount($ipA) < $max) {
    $created[] = $writer->store(
        'Verify Temp Filler',
        'verify-temp@example.com',
        'filler',
        'Filler message body, long enough to be realistic.',
        $ipA,
        'verify-phase8',
    );
}

check('The limit blocks the next message from that address', $throttle->isBlocked($ipA));
check('A retry time is offered', $throttle->retryAfterMinutes($ipA) > 0, $throttle->retryAfterMinutes($ipA) . ' minutes');

// The check that stops a rate limiter from being a denial of service against
// everyone: a different sender must be unaffected.
check('A DIFFERENT address is not blocked', !$throttle->isBlocked($ipB));

// A request with no usable address is never blocked — some hosts pass none,
// and refusing those visitors would turn the form into a wall.
check('A request with no address is not blocked', !$throttle->isBlocked(null));

// ------------------------------------------------------------- 3. INBOX

heading('3. Inbox transitions');

$writer->markRead($id);
$read = $repository->find($id);

check('Marking read sets the status', $read?->status === 'read');
check('Marking read records when', $read?->readAt !== null, (string) $read?->readAt);

$firstReadAt = $read?->readAt;
$writer->markRead($id);

check(
    'Reading again does NOT move read_at',
    $repository->find($id)?->readAt === $firstReadAt,
    'it records when he first saw it, not the last time he opened it',
);

$writer->setStatus($id, 'archived');
check('A message can be archived', $repository->find($id)?->isArchived() === true);

check(
    'An archived message is OUT of the inbox',
    !in_array($id, array_map(static fn (Message $m): int => $m->id, $repository->byStatus(['unread', 'read'])), true),
);

check(
    'An archived message is still IN the table',
    in_array($id, array_map(static fn (Message $m): int => $m->id, $repository->byStatus(['archived'])), true),
    'archiving is not deleting',
);

check('An invalid status is refused', !$writer->setStatus($id, 'burned'));

$counts = $repository->countsByStatus();
check(
    'Counts add up to the rows present',
    array_sum($counts) === messageCount(),
    implode(', ', array_map(static fn ($k, $v): string => "{$k}={$v}", array_keys($counts), $counts)),
);

check('unreadCount() agrees with the status counts', $repository->unreadCount() === $counts['unread']);

// -------------------------------------------- 4. THE NOTIFICATION FAILING

heading('4. A failed notification never costs a message');

$subject = $repository->find($created[1]);

check(
    'No recipient configured means notify() reports failure',
    $subject !== null && (new MessageNotifier(null))->notify($subject) === false,
);

check(
    'An empty recipient reports failure too',
    $subject !== null && (new MessageNotifier(''))->notify($subject) === false,
);

check(
    'The message is STILL THERE after the notification failed',
    $repository->find($created[1]) !== null,
    'the row is the channel; email is a convenience',
);

// Header injection: a CR or LF in a name or subject would end the header and
// start another, which is how a contact form becomes a relay for Bcc.
$injected = $writer->store(
    "Verify Temp\r\nBcc: victim@example.com",
    'verify-temp3@example.com',
    "Subject\nBcc: another@example.com",
    'A body that is long enough to be realistic for this test.',
    $ipB,
    'verify-phase8',
);
$created[] = $injected;

$dangerous = $repository->find($injected);
$reflection = new ReflectionMethod(MessageNotifier::class, 'headerSafe');
$notifier = new MessageNotifier('nobody@example.com');

$safeName = (string) $reflection->invoke($notifier, (string) $dangerous?->name);

check(
    'Newlines are stripped before anything reaches a mail header',
    !str_contains($safeName, "\r") && !str_contains($safeName, "\n"),
    json_encode($safeName),
);

// ---------------------------------------------------------- 5. CLEAN UP

heading('5. The database is as it was found');

foreach ($created as $createdId) {
    $writer->delete($createdId);
}

$after = messageCount();

check(
    sprintf('Back to %d message(s)', $before),
    $after === $before,
    'now ' . $after,
);

printf("\n%d passed, %d failed\n", $pass, $fail);

exit($fail === 0 ? 0 : 1);
