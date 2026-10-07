<?php
/**
 * Services and process steps share this screen.
 *
 * They are the same thing to edit — an ordered list of a title and a short
 * body — so one template renders both and the controller supplies the noun,
 * the path and the prompt. See ContentListController.
 *
 * @var list<\App\Domain\Content\ContentItem> $items
 * @var string $csrf
 * @var string $noun   singular, lower case: 'service' / 'process step'
 * @var string $path   this screen's admin path
 * @var string $intro  what the owner is being asked to write
 * @var string $pageTitle
 */
$action = static fn (string $suffix = ''): string => e(route_url($path . $suffix));
?>

<header class="admin-page-head">
  <p class="t-eyebrow"><?= e($pageTitle) ?></p>
  <h1 class="t-display-3 u-mt-3"><?= e($pageTitle) ?></h1>
  <p class="t-body-sm t-muted u-mt-4 admin-section__intro"><?= e($intro) ?></p>
</header>

<section class="admin-section" aria-labelledby="list-title">
  <h2 class="admin-section__title" id="list-title">
    Current
    <span class="admin-badge admin-badge--ok"><?= e((string) count($items)) ?></span>
  </h2>

  <?php if ($items === []): ?>
    <p class="t-body-sm t-muted u-mt-5">
      Nothing here yet &mdash; and the public page shows no
      <?= e($pageTitle) ?> section at all while that is true, rather than an
      empty heading.
    </p>
  <?php else: ?>
    <!-- Reordering posts ids and positions as parallel arrays; the controller
         sorts and the writer rewrites dense integers. -->
    <form method="post" action="<?= $action('/reorder') ?>">
      <input type="hidden" name="_token" value="<?= e($csrf) ?>">

      <ul class="admin-list u-mt-5">
        <?php foreach ($items as $index => $item): ?>
          <li class="admin-list__item">
            <span class="admin-list__order">
              <label class="u-visually-hidden" for="order-<?= e((string) $item->id) ?>">
                Position of <?= e($item->title) ?>
              </label>
              <input class="admin-list__order-input" type="number" min="1"
                     id="order-<?= e((string) $item->id) ?>"
                     name="order_position[]" value="<?= e((string) ($index + 1)) ?>">
              <input type="hidden" name="order_id[]" value="<?= e((string) $item->id) ?>">
            </span>

            <span class="admin-list__body">
              <span class="admin-list__title"><?= e($item->title) ?></span>
              <?php if ($item->description !== ''): ?>
                <span class="admin-list__meta"><?= e($item->description) ?></span>
              <?php endif; ?>
            </span>

            <span class="admin-list__state">
              <?php [$tone, $label] = $item->isVisible ? ['ok', 'showing'] : ['warn', 'hidden']; ?>
              <span class="admin-badge admin-badge--<?= e($tone) ?>"><?= e($label) ?></span>
            </span>
          </li>
        <?php endforeach; ?>
      </ul>

      <button class="btn btn--secondary btn--sm u-mt-5" type="submit">Save order</button>
      <p class="field__help">Lowest number first.</p>
    </form>
  <?php endif; ?>
</section>

<?php foreach ($items as $item): ?>
  <section class="admin-section" aria-labelledby="item-<?= e((string) $item->id) ?>">
    <h2 class="admin-section__title" id="item-<?= e((string) $item->id) ?>">
      <?= e($item->title) ?>
    </h2>

    <form method="post" action="<?= $action('/' . $item->id) ?>">
      <input type="hidden" name="_token" value="<?= e($csrf) ?>">

      <div class="field">
        <label class="field__label" for="title-<?= e((string) $item->id) ?>">Title</label>
        <input class="field__input" type="text" maxlength="120" required
               id="title-<?= e((string) $item->id) ?>" name="title"
               value="<?= e($item->title) ?>">
      </div>

      <div class="field">
        <label class="field__label" for="desc-<?= e((string) $item->id) ?>">Description</label>
        <textarea class="field__textarea" rows="3" maxlength="500"
                  id="desc-<?= e((string) $item->id) ?>" name="description"><?= e($item->description) ?></textarea>
        <p class="field__help">Up to 500 characters. Leave empty to show the title alone.</p>
      </div>

      <button class="btn btn--primary btn--sm" type="submit">Save</button>
    </form>

    <div class="admin-actions u-mt-4">
      <form method="post" action="<?= $action('/' . $item->id . '/visible') ?>">
        <input type="hidden" name="_token" value="<?= e($csrf) ?>">
        <input type="hidden" name="visible" value="<?= $item->isVisible ? '0' : '1' ?>">
        <button class="btn btn--ghost btn--sm" type="submit">
          <?= $item->isVisible ? 'Hide from the site' : 'Show on the site' ?>
        </button>
      </form>

      <?php /* Deleting is permanent here — unlike a project, these rows carry
               no media and nothing references them, so a soft delete would be
               a graveyard rather than a safety net. Hiding is the reversible
               action, which is why it sits first. */ ?>
      <form method="post" action="<?= $action('/' . $item->id . '/delete') ?>"
            data-confirm="Delete &quot;<?= e($item->title) ?>&quot;? Hiding keeps the text; this does not.">
        <input type="hidden" name="_token" value="<?= e($csrf) ?>">
        <button class="btn btn--ghost btn--sm" type="submit">Delete</button>
      </form>
    </div>
  </section>
<?php endforeach; ?>

<section class="admin-section" aria-labelledby="add-title">
  <h2 class="admin-section__title" id="add-title">Add a <?= e($noun) ?></h2>

  <form method="post" action="<?= $action() ?>">
    <input type="hidden" name="_token" value="<?= e($csrf) ?>">

    <div class="field">
      <label class="field__label" for="new-title">Title</label>
      <input class="field__input" type="text" maxlength="120" required
             id="new-title" name="title">
    </div>

    <div class="field">
      <label class="field__label" for="new-description">Description</label>
      <textarea class="field__textarea" rows="3" maxlength="500"
                id="new-description" name="description"></textarea>
    </div>

    <button class="btn btn--primary btn--sm" type="submit">
      Add <span class="btn__arrow" aria-hidden="true">&rarr;</span>
    </button>
  </form>
</section>
