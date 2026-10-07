<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Core\Csrf;
use App\Core\Request;
use App\Core\Response;
use App\Domain\Content\ContentList;
use App\Domain\Content\ContentListRepository;
use App\Domain\Content\ContentListWriter;

/**
 * Shared behaviour for the two ordered content lists.
 *
 * Services and process steps are edited identically — add, edit, show/hide,
 * delete, reorder — so the behaviour lives once and the subclasses supply only
 * which list they are and what to call it on screen. Two copies of this would
 * be two places for the reorder rule to drift.
 *
 * Every route carries the kernel's 'auth' guard, declared in routes/web.php.
 */
abstract class ContentListController extends AdminController
{
    abstract protected function list(): ContentList;

    /** The admin path this screen lives at, e.g. '/admin/services'. */
    abstract protected function path(): string;

    /** The heading and what the pending state should prompt the owner to write. */
    abstract protected function intro(): string;

    /** @param array<string, string> $params */
    public function index(Request $request, array $params = []): Response
    {
        $list = $this->list();

        return $this->adminPage('admin/content-list', $list->label(), [
            'csrf'  => Csrf::token(),
            'items' => (new ContentListRepository())->all($list),
            'noun'  => $list->noun(),
            'path'  => $this->path(),
            'intro' => $this->intro(),
        ]);
    }

    /** @param array<string, string> $params */
    public function store(Request $request, array $params = []): Response
    {
        if ($response = $this->requireCsrf($request, $this->path())) {
            return $response;
        }

        $title = $this->text($request, 'title', 120);

        if ($title === '') {
            return $this->back('error', 'A ' . $this->list()->noun() . ' needs a title.');
        }

        $this->writer()->create($title, $this->text($request, 'description', 500));

        return $this->back('success', "\"{$title}\" added.");
    }

    /** @param array<string, string> $params */
    public function update(Request $request, array $params = []): Response
    {
        if ($response = $this->requireCsrf($request, $this->path())) {
            return $response;
        }

        $id = (int) ($params['id'] ?? 0);

        if (!$this->exists($id)) {
            return $this->gone();
        }

        $title = $this->text($request, 'title', 120);

        if ($title === '') {
            return $this->back('error', 'A ' . $this->list()->noun() . ' needs a title.');
        }

        $this->writer()->update($id, $title, $this->text($request, 'description', 500));

        return $this->back('success', 'Saved.');
    }

    /** @param array<string, string> $params */
    public function visibility(Request $request, array $params = []): Response
    {
        if ($response = $this->requireCsrf($request, $this->path())) {
            return $response;
        }

        $id = (int) ($params['id'] ?? 0);

        if (!$this->exists($id)) {
            return $this->gone();
        }

        $visible = ($request->post['visible'] ?? '') === '1';

        $this->writer()->setVisible($id, $visible);

        return $this->back('success', $visible
            ? 'Showing on the site.'
            : 'Hidden. The text is kept, so you can put it back without rewriting it.');
    }

    /** @param array<string, string> $params */
    public function destroy(Request $request, array $params = []): Response
    {
        if ($response = $this->requireCsrf($request, $this->path())) {
            return $response;
        }

        $id = (int) ($params['id'] ?? 0);

        if (!$this->exists($id)) {
            return $this->gone();
        }

        $this->writer()->delete($id);

        return $this->back('success', 'Deleted. Hiding would have kept the text.');
    }

    /** @param array<string, string> $params */
    public function reorder(Request $request, array $params = []): Response
    {
        if ($response = $this->requireCsrf($request, $this->path())) {
            return $response;
        }

        $ids       = (array) ($request->post['order_id'] ?? []);
        $positions = (array) ($request->post['order_position'] ?? []);

        $pairs = [];

        foreach ($ids as $index => $id) {
            $pairs[] = ['id' => (int) $id, 'position' => (int) ($positions[$index] ?? 0)];
        }

        usort($pairs, static fn (array $a, array $b): int => $a['position'] <=> $b['position']);

        $this->writer()->reorder(array_map(static fn (array $p): int => $p['id'], $pairs));

        return $this->back('success', 'Order saved.');
    }

    private function writer(): ContentListWriter
    {
        return new ContentListWriter($this->list());
    }

    private function exists(int $id): bool
    {
        return (new ContentListRepository())->find($this->list(), $id) !== null;
    }

    private function gone(): Response
    {
        return $this->back('error', 'That ' . $this->list()->noun() . ' no longer exists.');
    }

    private function back(string $type, string $message): Response
    {
        $this->flash($type, $message);

        return Response::redirect(route_url($this->path()));
    }
}
