<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Core\HttpNotFoundException;
use App\Core\Request;
use App\Core\Response;
use App\Core\Seo;
use App\Domain\Project\Project;

/**
 * Case-study pages, served from the database.
 *
 * The slug lookup goes through ProjectRepository::findPublishedBySlug(), which
 * filters on publication and soft deletion in SQL. A draft is therefore
 * unreachable by URL rather than merely unlinked: guessing the address returns
 * a genuine 404, because the row never leaves the repository.
 */
final class WorkController extends Controller
{
    /** @param array<string, string> $params */
    public function show(Request $request, array $params = []): Response
    {
        $slug    = $params['slug'] ?? '';
        $project = $this->projects->findPublishedBySlug($slug);

        if ($project === null) {
            throw new HttpNotFoundException('No published project for slug: ' . $slug);
        }

        return $this->render($project, $slug, preview: false);
    }

    /**
     * The same page, for a project in any state, behind the admin guard.
     *
     * Lives here rather than in the admin controller so there is ONE assembly
     * of this view model. A second copy would drift, and the copy that drifts
     * is the preview — which would then stop being a preview of anything.
     *
     * Reaching it requires the kernel's 'auth' guard to have passed; show()
     * still calls findPublishedBySlug and must continue to, so an anonymous
     * visitor guessing a draft's address still gets a real 404.
     *
     * @param array<string, string> $params
     */
    public function preview(Request $request, array $params = []): Response
    {
        $slug    = $params['slug'] ?? '';
        $project = $this->projects->findAnyBySlug($slug);

        if ($project === null) {
            throw new HttpNotFoundException('No project for slug: ' . $slug);
        }

        return $this->render($project, $slug, preview: true);
    }

    private function render(Project $project, string $slug, bool $preview): Response
    {
        [$previous, $next] = $this->neighbours($slug);

        $meta = new Seo(
            title:       $project->title,
            description: $this->description($project),
            canonical:   absolute_url($project->url()),
            ogType:      'article',
            ogImage:     $this->socialImage($project),
            jsonLd:      [$this->creativeWorkSchema($project)],
            // A preview must never be indexed, whatever the project's state.
            noindex:     $preview,
        );

        return $this->page('pages/work-show', $meta, [
            'project'   => $project,
            'previous'  => $previous,
            'next'      => $next,
            'isPreview' => $preview,
        ]);
    }

    /**
     * Meta description from the project's own words — never a second copy
     * that can drift from what the page shows.
     */
    private function description(Project $project): string
    {
        $text = $project->summary ?? $project->problemStatement;
        $text = trim(preg_replace('/\s+/', ' ', $text) ?? '');

        return mb_strimwidth($text, 0, 158, '…');
    }

    private function socialImage(Project $project): string
    {
        $path = $project->cover?->url('og', 'jpeg')
            ?? $project->thumbnail?->url('og', 'jpeg');

        return $path === null ? '' : absolute_url($path);
    }

    /**
     * Previous and next in the published running order.
     *
     * @return array{0: ?array{slug:string,title:string,category_label:string},
     *               1: ?array{slug:string,title:string,category_label:string}}
     */
    private function neighbours(string $slug): array
    {
        $all   = $this->projects->publishedNavigation();
        $index = null;

        foreach ($all as $position => $entry) {
            if ($entry['slug'] === $slug) {
                $index = $position;
                break;
            }
        }

        if ($index === null) {
            return [null, null];
        }

        return [$all[$index - 1] ?? null, $all[$index + 1] ?? null];
    }

    /**
     * schema.org/CreativeWork, generated from stored project data only.
     *
     * @return array<string, mixed>
     */
    private function creativeWorkSchema(Project $project): array
    {
        $schema = [
            '@context' => 'https://schema.org',
            '@type'    => 'CreativeWork',
            'name'     => $project->title,
            'url'      => absolute_url($project->url()),
        ];

        if ($project->summary !== null && $project->summary !== '') {
            $schema['description'] = $project->summary;
        }

        $author = $this->profiles->current();
        if ($author !== null) {
            $schema['author'] = ['@type' => 'Person', 'name' => $author->fullName];
        }

        $technologies = array_map(
            static fn ($skill): string => $skill->name,
            $project->technologies
        );
        if ($technologies !== []) {
            $schema['keywords'] = implode(', ', $technologies);
        }

        if ($project->githubUrl !== null) {
            $schema['codeRepository'] = $project->githubUrl;
        }

        return $schema;
    }
}
