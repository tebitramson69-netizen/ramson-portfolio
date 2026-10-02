<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Core\Csrf;
use App\Core\Request;
use App\Core\Response;
use App\Domain\Project\ProjectWriter;
use App\Domain\Project\SectionKey;

/**
 * Project and case-study editing.
 *
 * Reads go through ProjectRepository's findAll* methods, writes through
 * ProjectWriter. No SQL here.
 *
 * Every route in this controller carries the kernel's 'auth' guard, declared
 * in routes/web.php — enforced before the controller is constructed, so a
 * forgotten check inside a method cannot expose anything.
 */
final class ProjectController extends AdminController
{
    /** @param array<string, string> $params */
    public function index(Request $request, array $params = []): Response
    {
        return $this->adminPage('admin/projects', 'Projects', [
            'csrf'     => Csrf::token(),
            'projects' => $this->projects->findAll(),
            'deleted'  => $this->projects->findAllDeleted(),
        ]);
    }

    /** @param array<string, string> $params */
    public function create(Request $request, array $params = []): Response
    {
        return $this->editor(null, 'New project');
    }

    /** @param array<string, string> $params */
    public function store(Request $request, array $params = []): Response
    {
        if ($response = $this->requireCsrf($request, '/admin/projects')) {
            return $response;
        }

        $title = $this->text($request, 'title', 160);

        if ($title === '') {
            $this->flash('error', 'A project needs a title.');

            return Response::redirect(route_url('/admin/projects/new'));
        }

        $slug = $this->text($request, 'slug', 160);
        $slug = self::slugify($slug !== '' ? $slug : $title);

        $id = (new ProjectWriter())->create([
            'slug'        => $slug,
            'title'       => $title,
            'publication' => 'draft',
        ]);

        if ($id === null) {
            $this->flash('error', "The slug \"{$slug}\" is already used by another project. Choose a different one.");

            return Response::redirect(route_url('/admin/projects/new'));
        }

        $this->flash('success', 'Project created as a draft. It is not on the public site yet.');

        return Response::redirect(route_url('/admin/projects/' . $id));
    }

    /** @param array<string, string> $params */
    public function edit(Request $request, array $params = []): Response
    {
        $project = $this->projects->findAnyById((int) ($params['id'] ?? 0));

        if ($project === null) {
            $this->flash('error', 'That project no longer exists.');

            return Response::redirect(route_url('/admin/projects'));
        }

        return $this->editor($project, $project->title);
    }

    /**
     * Save the text fields, sections, features and technologies together.
     *
     * One submit for the whole case study, because they are edited together on
     * one screen and a partial save would leave the author guessing which half
     * landed. Images are separate routes for the reason given on the profile
     * screen: a rejected image must not discard edited prose.
     *
     * @param array<string, string> $params
     */
    public function update(Request $request, array $params = []): Response
    {
        $id = (int) ($params['id'] ?? 0);

        if ($response = $this->requireCsrf($request, '/admin/projects/' . $id)) {
            return $response;
        }

        $project = $this->projects->findAnyById($id);

        if ($project === null) {
            $this->flash('error', 'That project no longer exists.');

            return Response::redirect(route_url('/admin/projects'));
        }

        $writer = new ProjectWriter();

        foreach (['github_url' => 'GitHub', 'live_url' => 'Live'] as $key => $label) {
            $url = $this->text($request, $key, 255);

            if ($url !== '' && !preg_match('#^https?://#i', $url)) {
                $this->flash('error', "The {$label} link must start with http:// or https://. Nothing was saved.");

                return Response::redirect(route_url('/admin/projects/' . $id));
            }
        }

        $title = $this->text($request, 'title', 160);
        $slug  = self::slugify($this->text($request, 'slug', 160) ?: $title);

        if ($title === '' || $slug === '') {
            $this->flash('error', 'A project needs a title and a slug.');

            return Response::redirect(route_url('/admin/projects/' . $id));
        }

        $nullable = static fn (string $v): ?string => $v === '' ? null : $v;

        $saved = $writer->update($id, [
            'title'             => $title,
            'slug'              => $slug,
            'category_label'    => $this->text($request, 'category_label', 120),
            'problem_statement' => $this->text($request, 'problem_statement', 500),
            'summary'           => $nullable($this->text($request, 'summary', 2000)),
            'role'              => $this->text($request, 'role', 200),
            'year_label'        => $this->text($request, 'year_label', 40),
            'status_label'      => $this->text($request, 'status_label', 80),
            'github_url'        => $nullable($this->text($request, 'github_url', 255)),
            'live_url'          => $nullable($this->text($request, 'live_url', 255)),
        ]);

        if (!$saved) {
            $this->flash('error', "The slug \"{$slug}\" belongs to another project. Nothing was saved.");

            return Response::redirect(route_url('/admin/projects/' . $id));
        }

        $writer->replaceSections($id, $this->sectionsFromRequest($request));
        $writer->replaceFeatures($id, $this->featuresFromRequest($request));

        $writer->replaceTechnologies(
            $id,
            array_map('intval', (array) ($request->post['technologies'] ?? [])),
            array_map('intval', (array) ($request->post['primary_technologies'] ?? [])),
        );

        $this->flash('success', 'Saved.');

        return Response::redirect(route_url('/admin/projects/' . $id));
    }

    /**
     * Publication state and the featured flag.
     *
     * @param array<string, string> $params
     */
    public function state(Request $request, array $params = []): Response
    {
        $id = (int) ($params['id'] ?? 0);

        if ($response = $this->requireCsrf($request, '/admin/projects/' . $id)) {
            return $response;
        }

        $writer = new ProjectWriter();
        $action = (string) ($request->post['action'] ?? '');

        $message = match ($action) {
            'publish'   => 'Published. It is live on the site now.',
            'draft'     => 'Moved back to draft. The public page now returns 404.',
            'archive'   => 'Archived. It is off the site but not deleted.',
            'feature'   => 'Featured on the home page.',
            'unfeature' => 'No longer featured.',
            default     => null,
        };

        if ($message === null) {
            return Response::redirect(route_url('/admin/projects/' . $id));
        }

        match ($action) {
            'publish'   => $writer->setPublication($id, 'published'),
            'draft'     => $writer->setPublication($id, 'draft'),
            'archive'   => $writer->setPublication($id, 'archived'),
            'feature'   => $writer->setFeatured($id, true),
            'unfeature' => $writer->setFeatured($id, false),
        };

        $this->flash('success', $message);

        return Response::redirect(route_url('/admin/projects/' . $id));
    }

    /** @param array<string, string> $params */
    public function uploadImage(Request $request, array $params = []): Response
    {
        $id = (int) ($params['id'] ?? 0);

        // Before CSRF: see AdminController::postDiscarded().
        if ($this->postDiscarded($request)) {
            $this->flash('error', $this->postDiscardedMessage());

            return Response::redirect(route_url('/admin/projects/' . $id));
        }

        if ($response = $this->requireCsrf($request, '/admin/projects/' . $id)) {
            return $response;
        }

        $project = $this->projects->findAnyById($id);
        $kind    = ($request->post['kind'] ?? '') === 'cover' ? 'cover' : 'thumbnail';

        if ($project === null) {
            $this->flash('error', 'That project no longer exists.');

            return Response::redirect(route_url('/admin/projects'));
        }

        $file = $_FILES['image'] ?? null;

        if (!is_array($file)) {
            $this->flash('error', 'No file was received.');

            return Response::redirect(route_url('/admin/projects/' . $id));
        }

        $alt = $this->text($request, 'alt_text', 255);
        $alt = $alt !== '' ? $alt : $project->title . ' screenshot';

        $uploads = $this->uploadService();
        $result  = $uploads->store($file, $alt, 'projects');

        if ($result['media'] === null) {
            $this->flash('error', implode(' ', $result['errors']));

            return Response::redirect(route_url('/admin/projects/' . $id));
        }

        $writer   = new ProjectWriter();
        $previous = $kind === 'cover' ? $project->cover?->id : $project->thumbnail?->id;

        // Point at the new image BEFORE removing the old one, so there is never
        // an instant where the project has no screenshot.
        $kind === 'cover'
            ? $writer->setCover($id, $result['media']->id)
            : $writer->setThumbnail($id, $result['media']->id);

        if ($previous !== null && $previous !== $result['media']->id) {
            $uploads->delete($previous);
        }

        $this->flash('success', ucfirst($kind) . ' updated.');

        return Response::redirect(route_url('/admin/projects/' . $id));
    }

    /** @param array<string, string> $params */
    public function removeImage(Request $request, array $params = []): Response
    {
        $id = (int) ($params['id'] ?? 0);

        if ($response = $this->requireCsrf($request, '/admin/projects/' . $id)) {
            return $response;
        }

        $project = $this->projects->findAnyById($id);
        $kind    = ($request->post['kind'] ?? '') === 'cover' ? 'cover' : 'thumbnail';
        $current = $kind === 'cover' ? $project?->cover?->id : $project?->thumbnail?->id;

        if ($current === null) {
            $this->flash('info', "There is no {$kind} to remove.");

            return Response::redirect(route_url('/admin/projects/' . $id));
        }

        // Detach first: the foreign key refuses to delete a media row that is
        // still pointed at.
        $writer = new ProjectWriter();
        $kind === 'cover' ? $writer->setCover($id, null) : $writer->setThumbnail($id, null);

        $this->uploadService()->delete($current);

        $this->flash('success', ucfirst($kind) . ' removed.');

        return Response::redirect(route_url('/admin/projects/' . $id));
    }

    /** @param array<string, string> $params */
    public function destroy(Request $request, array $params = []): Response
    {
        $id = (int) ($params['id'] ?? 0);

        if ($response = $this->requireCsrf($request, '/admin/projects')) {
            return $response;
        }

        (new ProjectWriter())->softDelete($id);

        $this->flash('success', 'Deleted. It is off the site and can be restored from the list below.');

        return Response::redirect(route_url('/admin/projects'));
    }

    /** @param array<string, string> $params */
    public function restore(Request $request, array $params = []): Response
    {
        $id = (int) ($params['id'] ?? 0);

        if ($response = $this->requireCsrf($request, '/admin/projects')) {
            return $response;
        }

        if (!(new ProjectWriter())->restore($id)) {
            $this->flash('error', 'Its slug has been taken by another project since it was deleted. Rename that one first.');

            return Response::redirect(route_url('/admin/projects'));
        }

        $this->flash('success', 'Restored as a draft.');

        return Response::redirect(route_url('/admin/projects'));
    }

    /** @param array<string, string> $params */
    public function reorder(Request $request, array $params = []): Response
    {
        if ($response = $this->requireCsrf($request, '/admin/projects')) {
            return $response;
        }

        // The form posts an id and a typed position per row. Sorting here
        // rather than trusting row order means the author can simply renumber
        // the boxes, with no JavaScript and no drag-and-drop to fail.
        $ids       = array_map('intval', (array) ($request->post['order_id'] ?? []));
        $positions = array_map('intval', (array) ($request->post['order_position'] ?? []));

        if ($ids === []) {
            return Response::redirect(route_url('/admin/projects'));
        }

        $pairs = [];

        foreach ($ids as $index => $id) {
            $pairs[] = ['id' => $id, 'position' => $positions[$index] ?? PHP_INT_MAX];
        }

        // A stable sort keeps two rows given the same number in the order they
        // were already in, rather than swapping them unpredictably.
        usort($pairs, static fn (array $a, array $b): int => $a['position'] <=> $b['position']);

        (new ProjectWriter())->reorder(array_column($pairs, 'id'));

        $this->flash('success', 'Order saved.');

        return Response::redirect(route_url('/admin/projects'));
    }

    // --------------------------------------------------------------- private

    private function editor(?object $project, string $title): Response
    {
        return $this->adminPage('admin/project-edit', $title, [
            'csrf'        => Csrf::token(),
            'project'     => $project,
            'sectionKeys' => SectionKey::ORDER,
            'skills'      => $this->skills->visibleGroupedByCategory(),
            'limits'      => $this->uploadLimits(),
        ]);
    }

    private function text(Request $request, string $key, int $max): string
    {
        return mb_substr(trim((string) ($request->post[$key] ?? '')), 0, $max);
    }

    /**
     * Sections arrive as sections[<key>] = body, one textarea per known key.
     *
     * @return list<array{key:string, body:string}>
     */
    private function sectionsFromRequest(Request $request): array
    {
        $submitted = (array) ($request->post['sections'] ?? []);
        $sections  = [];

        foreach ($submitted as $key => $body) {
            // Only the known vocabulary. An unexpected key would otherwise let
            // a crafted form write an arbitrary section_key.
            if (!is_string($key) || !SectionKey::isKnown($key)) {
                continue;
            }

            $sections[] = ['key' => $key, 'body' => (string) $body];
        }

        return $sections;
    }

    /** @return list<array{title:string, description:string}> */
    private function featuresFromRequest(Request $request): array
    {
        $titles       = (array) ($request->post['feature_title'] ?? []);
        $descriptions = (array) ($request->post['feature_description'] ?? []);
        $features     = [];

        foreach ($titles as $index => $title) {
            $features[] = [
                'title'       => (string) $title,
                'description' => (string) ($descriptions[$index] ?? ''),
            ];
        }

        return $features;
    }

    /**
     * A URL-safe slug.
     *
     * Deliberately ASCII-only: a slug ends up in a URL, a filename-like path
     * and a unique index, and transliterating accented characters predictably
     * without intl is not worth the surprise. The author can always type one.
     */
    public static function slugify(string $value): string
    {
        $value = strtolower(trim($value));
        $value = (string) preg_replace('/[^a-z0-9]+/', '-', $value);

        return trim($value, '-');
    }
}
