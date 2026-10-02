<?php
/**
 * Project list.
 *
 * Shows every project whatever its state, which is the point: the public site
 * shows published ones, and this is the screen where you can see that a draft
 * exists at all.
 *
 * @var list<\App\Domain\Project\Project> $projects
 * @var list<\App\Domain\Project\Project> $deleted
 * @var string $csrf
 */
$badge = static function (string $publication): array {
    return match ($publication) {
        'published' => ['ok',   'live'],
        'archived'  => ['warn', 'archived'],
        default     => ['warn', 'draft'],
    };
};
?>

<header class="admin-page-head">
  <p class="t-eyebrow">Projects</p>
  <h1 class="t-display-3 u-mt-3">Work &amp; case studies</h1>
  <p class="t-body-sm t-muted u-mt-4 admin-section__intro">
    Everything here is editable without touching code. A draft is invisible to
    the public &mdash; its URL returns a genuine 404, not an empty page.
  </p>
</header>

<section class="admin-section" aria-labelledby="list-title">
  <h2 class="admin-section__title" id="list-title">
    All projects
    <span class="admin-badge admin-badge--ok"><?= e((string) count($projects)) ?></span>
  </h2>

  <p class="u-mt-5">
    <a class="btn btn--primary" href="<?= e(route_url('/admin/projects/new')) ?>">
      New project <span class="btn__arrow" aria-hidden="true">&rarr;</span>
    </a>
  </p>

  <?php if ($projects === []): ?>
    <p class="t-body-sm t-muted u-mt-6">No projects yet.</p>
  <?php else: ?>
    <!-- Reordering posts the ids in the order the rows appear. Dense integers
         rewritten wholesale — see 05-ARCHITECTURE.md §2.6b. -->
    <form method="post" action="<?= e(route_url('/admin/projects/reorder')) ?>">
      <input type="hidden" name="_token" value="<?= e($csrf) ?>">

      <ul class="admin-list u-mt-5">
        <?php foreach ($projects as $index => $project): ?>
          <?php [$tone, $label] = $badge($project->publication); ?>
          <li class="admin-list__item">
            <span class="admin-list__order">
              <label class="u-visually-hidden" for="order-<?= e((string) $project->id) ?>">
                Position of <?= e($project->title) ?>
              </label>
              <input class="admin-list__order-input" type="number" min="1"
                     id="order-<?= e((string) $project->id) ?>"
                     name="order_position[]" value="<?= e((string) ($index + 1)) ?>">
              <input type="hidden" name="order_id[]" value="<?= e((string) $project->id) ?>">
            </span>

            <span class="admin-list__thumb" aria-hidden="true">
              <?php $thumb = $project->thumbnail?->url('thumb', 'jpeg'); ?>
              <?php if ($thumb !== null): ?>
                <img src="<?= e(route_url($thumb)) ?>" alt="" width="48" height="48" loading="lazy">
              <?php else: ?>
                <span class="admin-list__thumb-empty">&mdash;</span>
              <?php endif; ?>
            </span>

            <span class="admin-list__body">
              <a class="admin-list__title" href="<?= e(route_url('/admin/projects/' . $project->id)) ?>">
                <?= e($project->title) ?>
              </a>
              <span class="admin-list__meta">
                <code>/work/<?= e($project->slug) ?></code>
                <?php if ($project->categoryLabel !== ''): ?>
                  &middot; <?= e($project->categoryLabel) ?>
                <?php endif; ?>
              </span>
            </span>

            <span class="admin-list__state">
              <span class="admin-badge admin-badge--<?= e($tone) ?>"><?= e($label) ?></span>
              <?php if ($project->isFeatured): ?>
                <span class="admin-badge admin-badge--muted">featured</span>
              <?php endif; ?>
            </span>

            <span class="admin-list__links">
              <a class="t-link" href="<?= e(route_url('/admin/preview/' . $project->slug)) ?>">Preview</a>
              <?php if ($project->isPublished()): ?>
                <a class="t-link" href="<?= e(route_url($project->url())) ?>">View live</a>
              <?php endif; ?>
            </span>
          </li>
        <?php endforeach; ?>
      </ul>

      <button class="btn btn--secondary btn--sm u-mt-5" type="submit">Save order</button>
      <p class="field__help">Lowest number first. Featured projects still lead on the home page.</p>
    </form>
  <?php endif; ?>
</section>

<?php if ($deleted !== []): ?>
  <section class="admin-section" aria-labelledby="deleted-title">
    <h2 class="admin-section__title" id="deleted-title">
      Deleted
      <span class="admin-badge admin-badge--warn"><?= e((string) count($deleted)) ?></span>
    </h2>
    <p class="t-body-sm t-muted admin-section__intro">
      Soft-deleted, so nothing is lost and the slug is free for reuse. Restoring
      brings a project back as a draft.
    </p>

    <ul class="admin-list u-mt-5">
      <?php foreach ($deleted as $project): ?>
        <li class="admin-list__item">
          <span class="admin-list__body">
            <span class="admin-list__title"><?= e($project->title) ?></span>
            <span class="admin-list__meta"><code>/work/<?= e($project->slug) ?></code></span>
          </span>
          <span class="admin-list__links">
            <form method="post" action="<?= e(route_url('/admin/projects/' . $project->id . '/restore')) ?>">
              <input type="hidden" name="_token" value="<?= e($csrf) ?>">
              <button class="btn btn--ghost btn--sm" type="submit">Restore</button>
            </form>
          </span>
        </li>
      <?php endforeach; ?>
    </ul>
  </section>
<?php endif; ?>
