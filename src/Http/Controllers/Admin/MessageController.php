<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Core\Csrf;
use App\Core\Request;
use App\Core\Response;
use App\Domain\Message\MessageRepository;
use App\Domain\Message\MessageWriter;

/**
 * The contact inbox.
 *
 * This is the channel the contact form actually delivers to — email is a
 * best-effort notification that may be disabled by the host entirely, so a
 * message is only reliably seen here. The dashboard carries the unread count
 * for that reason: it is the screen he lands on.
 *
 * Every route carries the kernel's 'auth' guard, declared in routes/web.php.
 */
final class MessageController extends AdminController
{
    private const HERE = '/admin/messages';

    /** @param array<string, string> $params */
    public function index(Request $request, array $params = []): Response
    {
        $repository = new MessageRepository();

        // Archived is excluded from the default view rather than deleted from
        // the table: dealt-with messages should leave the inbox without
        // leaving the record.
        $showArchived = ($request->query['archived'] ?? '') === '1';

        $statuses = $showArchived ? ['archived'] : ['unread', 'read'];

        return $this->adminPage('admin/messages', 'Messages', [
            'csrf'         => Csrf::token(),
            'messages'     => $repository->byStatus($statuses),
            'counts'       => $repository->countsByStatus(),
            'showArchived' => $showArchived,
        ]);
    }

    /**
     * Opening a message marks it read.
     *
     * A separate "mark as read" button would be a step that exists only to be
     * forgotten, leaving an unread badge that means nothing. Reading it is
     * what "read" means.
     *
     * @param array<string, string> $params
     */
    public function show(Request $request, array $params = []): Response
    {
        $id      = (int) ($params['id'] ?? 0);
        $message = (new MessageRepository())->find($id);

        if ($message === null) {
            return $this->back('error', 'That message no longer exists.');
        }

        if ($message->isUnread()) {
            (new MessageWriter())->markRead($id);
            // Re-read so the screen shows the state it just wrote, rather
            // than claiming unread on the page that made it read.
            $message = (new MessageRepository())->find($id) ?? $message;
        }

        return $this->adminPage('admin/message-show', $message->displaySubject(), [
            'csrf'    => Csrf::token(),
            'message' => $message,
        ]);
    }

    /** @param array<string, string> $params */
    public function state(Request $request, array $params = []): Response
    {
        if ($response = $this->requireCsrf($request, self::HERE)) {
            return $response;
        }

        $id     = (int) ($params['id'] ?? 0);
        $status = (string) ($request->post['status'] ?? '');

        if ((new MessageRepository())->find($id) === null) {
            return $this->back('error', 'That message no longer exists.');
        }

        if (!(new MessageWriter())->setStatus($id, $status)) {
            return $this->back('error', 'That is not a state a message can be in.');
        }

        return $this->back('success', match ($status) {
            'archived' => 'Archived. It is out of the inbox and still in the record.',
            'unread'   => 'Marked unread.',
            default    => 'Marked read.',
        });
    }

    /** @param array<string, string> $params */
    public function destroy(Request $request, array $params = []): Response
    {
        if ($response = $this->requireCsrf($request, self::HERE)) {
            return $response;
        }

        $id = (int) ($params['id'] ?? 0);

        if ((new MessageRepository())->find($id) === null) {
            return $this->back('error', 'That message no longer exists.');
        }

        (new MessageWriter())->delete($id);

        // Permanent, and said so. Archiving is the reversible action and is
        // offered first on both screens — the same rule as every Phase 7 list.
        return $this->back('success', 'Deleted permanently. Archiving would have kept it.');
    }

    private function back(string $type, string $message): Response
    {
        $this->flash($type, $message);

        return Response::redirect(route_url(self::HERE));
    }
}
