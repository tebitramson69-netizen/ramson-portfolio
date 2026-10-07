<?php
/**
 * The technology vocabulary.
 *
 * Categories and their skills on one screen, because they are only meaningful
 * together — a category with no skills is an empty heading, and a skill needs
 * a category to live in.
 *
 * Hidden rows are shown here and marked, never filtered out: this is the
 * screen where you need to see that something exists but is switched off.
 *
 * @var list<array{id:int,name:string,slug:string,is_visible:bool,skills:list<array<string,mixed>>}> $categories
 * @var string $csrf
 */
$badge = static function (bool $visible): array {
    return $visible ? ['ok', 'showing'] : ['warn', 'hidden'];
};

$skillCount = array_sum(array_map(static fn (array $c): int => count($c['skills']), $categories));
?>

<header class="admin-page-head">
  <p class="t-eyebrow">Skills</p>
  <h1 class="t-display-3 u-mt-3">Technology vocabulary</h1>
  <p class="t-body-sm t-muted u-mt-4 admin-section__intro">
    One spelling of each technology, used by the strip under the hero, the
    skills section and every project's tags. Renaming one here renames it
    everywhere &mdash; which is the point of there being one list.
  </p>
</header>

<section class="admin-section" aria-labelledby="order-title">
  <h2 class="admin-section__title" id="order-title">
    Order
    <span class="admin-badge admin-badge--ok"><?= e((string) count($categories)) ?> categories</span>
    <span class="admin-badge admin-badge--muted"><?= e((string) $skillCount) ?> skills</span>
  </h2>

  <?php if ($categories === []): ?>
    <p class="t-body-sm t-muted u-mt-5">No categories yet. Add one below.</p>
  <?php else: ?>
    <form method="post" action="<?= e(route_url('/admin/skills/reorder')) ?>">
      <input type="hidden" name="_token" value="<?= e($csrf) ?>">

      <?php $categoryIndex = 0; $skillIndex = 0; ?>
      <?php foreach ($categories as $category): ?>
        <?php [$tone, $label] = $badge($category['is_visible']); ?>

        <ul class="admin-list u-mt-5">
          <li class="admin-list__item">
            <span class="admin-list__order">
              <label class="u-visually-hidden" for="cat-order-<?= e((string) $category['id']) ?>">
                Position of <?= e($category['name']) ?>
              </label>
              <input class="admin-list__order-input" type="number" min="1"
                     id="cat-order-<?= e((string) $category['id']) ?>"
                     name="category_order_position[]" value="<?= e((string) ++$categoryIndex) ?>">
              <input type="hidden" name="category_order_id[]" value="<?= e((string) $category['id']) ?>">
            </span>

            <span class="admin-list__body">
              <span class="admin-list__title"><?= e($category['name']) ?></span>
              <span class="admin-list__meta"><code><?= e($category['slug']) ?></code></span>
            </span>

            <span class="admin-list__state">
              <span class="admin-badge admin-badge--<?= e($tone) ?>"><?= e($label) ?></span>
            </span>
          </li>

          <?php foreach ($category['skills'] as $skill): ?>
            <?php [$sTone, $sLabel] = $badge((bool) $skill['is_visible']); ?>
            <li class="admin-list__item">
              <span class="admin-list__order">
                <label class="u-visually-hidden" for="skill-order-<?= e((string) $skill['id']) ?>">
                  Position of <?= e((string) $skill['name']) ?>
                </label>
                <input class="admin-list__order-input" type="number" min="1"
                       id="skill-order-<?= e((string) $skill['id']) ?>"
                       name="skill_order_position[]" value="<?= e((string) ++$skillIndex) ?>">
                <input type="hidden" name="skill_order_id[]" value="<?= e((string) $skill['id']) ?>">
              </span>

              <span class="admin-list__body">
                <span class="admin-list__title">&mdash; <?= e((string) $skill['name']) ?></span>
                <span class="admin-list__meta"><code><?= e((string) $skill['slug']) ?></code></span>
              </span>

              <span class="admin-list__state">
                <span class="admin-badge admin-badge--<?= e($sTone) ?>"><?= e($sLabel) ?></span>
              </span>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php endforeach; ?>

      <button class="btn btn--secondary btn--sm u-mt-5" type="submit">Save order</button>
      <p class="field__help">
        Lowest number first. Skills are numbered across the whole list; within a
        category only their relative order matters.
      </p>
    </form>
  <?php endif; ?>
</section>

<?php foreach ($categories as $category): ?>
  <section class="admin-section" aria-labelledby="cat-<?= e((string) $category['id']) ?>">
    <h2 class="admin-section__title" id="cat-<?= e((string) $category['id']) ?>">
      <?= e($category['name']) ?>
    </h2>

    <form method="post" action="<?= e(route_url('/admin/skills/categories/' . $category['id'])) ?>">
      <input type="hidden" name="_token" value="<?= e($csrf) ?>">

      <div class="admin-grid u-mt-5">
        <div class="field">
          <label class="field__label" for="cat-name-<?= e((string) $category['id']) ?>">Name</label>
          <input class="field__input" type="text" maxlength="80" required
                 id="cat-name-<?= e((string) $category['id']) ?>" name="name"
                 value="<?= e($category['name']) ?>">
        </div>

        <div class="field">
          <label class="field__label" for="cat-slug-<?= e((string) $category['id']) ?>">Slug</label>
          <input class="field__input" type="text" maxlength="80"
                 id="cat-slug-<?= e((string) $category['id']) ?>" name="slug"
                 value="<?= e($category['slug']) ?>">
          <p class="field__help">Leave empty to derive it from the name.</p>
        </div>
      </div>

      <button class="btn btn--primary btn--sm u-mt-4" type="submit">Save category</button>
    </form>

    <div class="admin-actions u-mt-4">
      <form method="post" action="<?= e(route_url('/admin/skills/categories/' . $category['id'] . '/visible')) ?>">
        <input type="hidden" name="_token" value="<?= e($csrf) ?>">
        <input type="hidden" name="visible" value="<?= $category['is_visible'] ? '0' : '1' ?>">
        <button class="btn btn--ghost btn--sm" type="submit">
          <?= $category['is_visible'] ? 'Hide from the site' : 'Show on the site' ?>
        </button>
      </form>

      <form method="post" action="<?= e(route_url('/admin/skills/categories/' . $category['id'] . '/delete')) ?>"
            data-confirm="Delete &quot;<?= e($category['name']) ?>&quot;? Only works if it holds no skills.">
        <input type="hidden" name="_token" value="<?= e($csrf) ?>">
        <button class="btn btn--ghost btn--sm" type="submit">Delete category</button>
      </form>
    </div>

    <?php if ($category['skills'] !== []): ?>
      <?php foreach ($category['skills'] as $skill): ?>
        <form method="post" action="<?= e(route_url('/admin/skills/items/' . $skill['id'])) ?>" class="u-mt-5">
          <input type="hidden" name="_token" value="<?= e($csrf) ?>">

          <div class="admin-grid">
            <div class="field">
              <label class="field__label" for="skill-name-<?= e((string) $skill['id']) ?>">Skill</label>
              <input class="field__input" type="text" maxlength="80" required
                     id="skill-name-<?= e((string) $skill['id']) ?>" name="name"
                     value="<?= e((string) $skill['name']) ?>">
            </div>

            <div class="field">
              <label class="field__label" for="skill-slug-<?= e((string) $skill['id']) ?>">Slug</label>
              <input class="field__input" type="text" maxlength="80"
                     id="skill-slug-<?= e((string) $skill['id']) ?>" name="slug"
                     value="<?= e((string) $skill['slug']) ?>">
            </div>

            <div class="field">
              <label class="field__label" for="skill-cat-<?= e((string) $skill['id']) ?>">Category</label>
              <select class="field__input" id="skill-cat-<?= e((string) $skill['id']) ?>" name="category_id">
                <?php foreach ($categories as $option): ?>
                  <option value="<?= e((string) $option['id']) ?>"
                    <?= (int) $option['id'] === (int) $skill['category_id'] ? ' selected' : '' ?>>
                    <?= e($option['name']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>

          <div class="admin-actions u-mt-4">
            <button class="btn btn--primary btn--sm" type="submit">Save</button>
          </div>
        </form>

        <div class="admin-actions u-mt-3">
          <form method="post" action="<?= e(route_url('/admin/skills/items/' . $skill['id'] . '/visible')) ?>">
            <input type="hidden" name="_token" value="<?= e($csrf) ?>">
            <input type="hidden" name="visible" value="<?= $skill['is_visible'] ? '0' : '1' ?>">
            <button class="btn btn--ghost btn--sm" type="submit">
              <?= $skill['is_visible'] ? 'Hide' : 'Show' ?>
            </button>
          </form>

          <form method="post" action="<?= e(route_url('/admin/skills/items/' . $skill['id'] . '/delete')) ?>"
                data-confirm="Delete &quot;<?= e((string) $skill['name']) ?>&quot;? Only works if no project is tagged with it.">
            <input type="hidden" name="_token" value="<?= e($csrf) ?>">
            <button class="btn btn--ghost btn--sm" type="submit">Delete</button>
          </form>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>

    <form method="post" action="<?= e(route_url('/admin/skills/items')) ?>" class="u-mt-6">
      <input type="hidden" name="_token" value="<?= e($csrf) ?>">
      <input type="hidden" name="category_id" value="<?= e((string) $category['id']) ?>">

      <div class="field">
        <label class="field__label" for="new-skill-<?= e((string) $category['id']) ?>">
          Add a skill to <?= e($category['name']) ?>
        </label>
        <input class="field__input" type="text" maxlength="80" required
               id="new-skill-<?= e((string) $category['id']) ?>" name="name">
      </div>

      <button class="btn btn--secondary btn--sm u-mt-4" type="submit">Add skill</button>
    </form>
  </section>
<?php endforeach; ?>

<section class="admin-section" aria-labelledby="new-cat-title">
  <h2 class="admin-section__title" id="new-cat-title">Add a category</h2>

  <form method="post" action="<?= e(route_url('/admin/skills/categories')) ?>">
    <input type="hidden" name="_token" value="<?= e($csrf) ?>">

    <div class="field u-mt-5">
      <label class="field__label" for="new-cat-name">Name</label>
      <input class="field__input" type="text" maxlength="80" required
             id="new-cat-name" name="name">
      <p class="field__help">The slug is derived from the name unless you set one.</p>
    </div>

    <button class="btn btn--primary btn--sm u-mt-4" type="submit">
      Add category <span class="btn__arrow" aria-hidden="true">&rarr;</span>
    </button>
  </form>
</section>
