<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Core\Csrf;
use App\Core\Request;
use App\Core\Response;
use App\Domain\Skill\SkillWriter;

/**
 * The technology vocabulary — categories and the skills inside them.
 *
 * One screen for both, because they are only meaningful together: a category
 * with no skills is an empty heading, and a skill needs a category to live in.
 * Splitting them across two pages would mean navigating away to do the obvious
 * next thing.
 *
 * Every route carries the kernel's 'auth' guard, declared in routes/web.php.
 */
final class SkillController extends AdminController
{
    private const HERE = '/admin/skills';

    /** @param array<string, string> $params */
    public function index(Request $request, array $params = []): Response
    {
        return $this->adminPage('admin/skills', 'Skills', [
            'csrf'       => Csrf::token(),
            'categories' => $this->skills->allCategoriesWithSkills(),
        ]);
    }

    // ------------------------------------------------------------ categories

    /** @param array<string, string> $params */
    public function storeCategory(Request $request, array $params = []): Response
    {
        if ($response = $this->requireCsrf($request, self::HERE)) {
            return $response;
        }

        $name = $this->text($request, 'name', 80);

        if ($name === '') {
            return $this->back('error', 'A category needs a name.');
        }

        $slug = self::slugify($this->text($request, 'slug', 80) ?: $name);

        if ((new SkillWriter())->createCategory($name, $slug) === null) {
            return $this->back('error', "The slug \"{$slug}\" is already used by another category.");
        }

        return $this->back('success', "Category \"{$name}\" added.");
    }

    /** @param array<string, string> $params */
    public function updateCategory(Request $request, array $params = []): Response
    {
        if ($response = $this->requireCsrf($request, self::HERE)) {
            return $response;
        }

        $id = (int) ($params['id'] ?? 0);

        if ($this->skills->findCategory($id) === null) {
            return $this->back('error', 'That category no longer exists.');
        }

        $name = $this->text($request, 'name', 80);

        if ($name === '') {
            return $this->back('error', 'A category needs a name.');
        }

        $slug = self::slugify($this->text($request, 'slug', 80) ?: $name);

        if (!(new SkillWriter())->updateCategory($id, $name, $slug)) {
            return $this->back('error', "The slug \"{$slug}\" is already used by another category.");
        }

        return $this->back('success', 'Category saved.');
    }

    /** @param array<string, string> $params */
    public function categoryVisibility(Request $request, array $params = []): Response
    {
        if ($response = $this->requireCsrf($request, self::HERE)) {
            return $response;
        }

        $visible = ($request->post['visible'] ?? '') === '1';

        (new SkillWriter())->setCategoryVisible((int) ($params['id'] ?? 0), $visible);

        return $this->back('success', $visible
            ? 'Category is showing on the site.'
            : 'Category hidden. Its skills are kept and still tag any project that uses them.');
    }

    /** @param array<string, string> $params */
    public function destroyCategory(Request $request, array $params = []): Response
    {
        if ($response = $this->requireCsrf($request, self::HERE)) {
            return $response;
        }

        $id       = (int) ($params['id'] ?? 0);
        $category = $this->skills->findCategory($id);

        if ($category === null) {
            return $this->back('error', 'That category no longer exists.');
        }

        $inUse = (new SkillWriter())->deleteCategory($id);

        if ($inUse > 0) {
            return $this->back('error', sprintf(
                'Nothing was deleted: "%s" still holds %d skill%s. Move or delete them first, '
                . 'or hide the category instead — hiding keeps everything and takes it off the site.',
                (string) $category['name'],
                $inUse,
                $inUse === 1 ? '' : 's',
            ));
        }

        return $this->back('success', 'Category deleted.');
    }

    // ---------------------------------------------------------------- skills

    /** @param array<string, string> $params */
    public function storeSkill(Request $request, array $params = []): Response
    {
        if ($response = $this->requireCsrf($request, self::HERE)) {
            return $response;
        }

        $categoryId = (int) ($request->post['category_id'] ?? 0);

        if ($this->skills->findCategory($categoryId) === null) {
            return $this->back('error', 'Choose a category that exists.');
        }

        $name = $this->text($request, 'name', 80);

        if ($name === '') {
            return $this->back('error', 'A skill needs a name.');
        }

        $slug = self::slugify($this->text($request, 'slug', 80) ?: $name);

        if ((new SkillWriter())->createSkill($categoryId, $name, $slug) === null) {
            return $this->back('error', sprintf(
                'The slug "%s" is already used. Skill slugs are unique across every category, '
                . 'because one spelling of a technology is the point of this list.',
                $slug,
            ));
        }

        return $this->back('success', "\"{$name}\" added.");
    }

    /** @param array<string, string> $params */
    public function updateSkill(Request $request, array $params = []): Response
    {
        if ($response = $this->requireCsrf($request, self::HERE)) {
            return $response;
        }

        $id = (int) ($params['id'] ?? 0);

        if ($this->skills->findSkill($id) === null) {
            return $this->back('error', 'That skill no longer exists.');
        }

        $categoryId = (int) ($request->post['category_id'] ?? 0);

        if ($this->skills->findCategory($categoryId) === null) {
            return $this->back('error', 'Choose a category that exists.');
        }

        $name = $this->text($request, 'name', 80);

        if ($name === '') {
            return $this->back('error', 'A skill needs a name.');
        }

        $slug = self::slugify($this->text($request, 'slug', 80) ?: $name);

        if (!(new SkillWriter())->updateSkill($id, $categoryId, $name, $slug)) {
            return $this->back('error', "The slug \"{$slug}\" is already used by another skill.");
        }

        return $this->back('success', 'Skill saved.');
    }

    /** @param array<string, string> $params */
    public function skillVisibility(Request $request, array $params = []): Response
    {
        if ($response = $this->requireCsrf($request, self::HERE)) {
            return $response;
        }

        $visible = ($request->post['visible'] ?? '') === '1';

        (new SkillWriter())->setSkillVisible((int) ($params['id'] ?? 0), $visible);

        return $this->back('success', $visible ? 'Skill is showing.' : 'Skill hidden.');
    }

    /** @param array<string, string> $params */
    public function destroySkill(Request $request, array $params = []): Response
    {
        if ($response = $this->requireCsrf($request, self::HERE)) {
            return $response;
        }

        $id    = (int) ($params['id'] ?? 0);
        $skill = $this->skills->findSkill($id);

        if ($skill === null) {
            return $this->back('error', 'That skill no longer exists.');
        }

        $inUse = (new SkillWriter())->deleteSkill($id);

        if ($inUse > 0) {
            return $this->back('error', sprintf(
                'Nothing was deleted: %d project%s still tagged with "%s". Deleting it would '
                . 'strip the tag off %s case stud%s. Hide it instead if you want it off the skills list.',
                $inUse,
                $inUse === 1 ? ' is' : 's are',
                (string) $skill['name'],
                $inUse === 1 ? 'that' : 'those',
                $inUse === 1 ? 'y' : 'ies',
            ));
        }

        return $this->back('success', 'Skill deleted.');
    }

    /** @param array<string, string> $params */
    public function reorder(Request $request, array $params = []): Response
    {
        if ($response = $this->requireCsrf($request, self::HERE)) {
            return $response;
        }

        $writer = new SkillWriter();

        $writer->reorderCategories($this->orderedIds($request, 'category_order'));
        $writer->reorderSkills($this->orderedIds($request, 'skill_order'));

        return $this->back('success', 'Order saved.');
    }

    /**
     * Ids sorted by the position the author typed beside each one.
     *
     * The form posts ids and positions as parallel arrays. Sorting here and
     * handing the writer a plain sequence keeps the "dense integers, rewritten
     * wholesale" rule in one place rather than in every caller.
     *
     * @return list<int>
     */
    private function orderedIds(Request $request, string $field): array
    {
        $ids       = (array) ($request->post[$field . '_id'] ?? []);
        $positions = (array) ($request->post[$field . '_position'] ?? []);

        $pairs = [];

        foreach ($ids as $index => $id) {
            $pairs[] = [
                'id'       => (int) $id,
                'position' => (int) ($positions[$index] ?? 0),
            ];
        }

        usort($pairs, static fn (array $a, array $b): int => $a['position'] <=> $b['position']);

        return array_map(static fn (array $pair): int => $pair['id'], $pairs);
    }

    private function back(string $type, string $message): Response
    {
        $this->flash($type, $message);

        return Response::redirect(route_url(self::HERE));
    }
}
