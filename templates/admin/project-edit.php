<?php
/**
 * Project editor.
 *
 * The text fields, case-study sections, features and technologies save
 * together, because they are edited together and a partial save would leave
 * the author guessing which half landed. Images and publication state are
 * separate forms, for the reason established on the profile screen: a rejected
 * image must not discard edited prose.
 *
 * @var \App\Domain\Project\Project|null $project   null when creating
 * @var array<string,string> $sectionKeys           key => default heading
 * @var array<string,list<\App\Domain\Skill\Skill>> $skills
 * @var array<string,string> $limits
 * @var string $csrf
 */
$isNew = $project === null;

/** Existing body for a section key, or '' so the textarea renders empty. */
$body = static function (string $key) use ($project): string {
    return $project?->section($key)?->body ?? '';
};

$tagged  = array_map(static fn ($s): int => $s->id, $project->technologies ?? []);
$primary = array_map(
    static fn ($s): int => $s->id,
    array_filter($project->technologies ?? [], static fn ($s): bool => $s->isPrimary)
);
?>

<header class="admin-page-head">
  <p class="t-eyebrow"><?= $isNew ? 'New project' : 'Editing' ?></p>
  <h1 class="t-display-3 u-mt-3"><?= e($isNew ? 'Add a project' : $project->title) ?></h1>
  <?php if (!$isNew): ?>
    <p class="t-caption u-mt-3">
      <a class="t-link" href="<?= e(route_url('/admin/preview/' . $project->slug)) ?>">Preview</a>
      <?php if ($project->isPublished()): ?>
        &middot; <a class="t-link" href="<?= e(route_url($project->url())) ?>">View live</a>
      <?php endif; ?>
      &middot; <a class="t-link" href="<?= e(route_url('/admin/projects')) ?>">All projects</a>
    </p>
  <?php endif; ?>
</header>

<?php if ($isNew): ?>
  <!-- Creating takes a title only. Everything else is edited once the row
       exists, so a half-filled form cannot be lost to a validation error. -->
  <section class="admin-section">
    <form method="post" action="<?= e(route_url('/admin/projects')) ?>">
      <input type="hidden" name="_token" value="<?= e($csrf) ?>">

      <div class="field">
        <label class="field__label" for="title">Project title</label>
        <input class="field__input" type="text" id="title" name="title" maxlength="160" required autofocus>
      </div>

      <div class="field">
        <label class="field__label" for="slug">URL slug <span class="field__required">optional</span></label>
        <input class="field__input" type="text" id="slug" name="slug" maxlength="160"
               placeholder="generated from the title">
        <p class="field__help">Becomes <code>/work/your-slug</code>. Lower case, letters, numbers and hyphens.</p>
      </div>

      <button class="btn btn--primary u-mt-5" type="submit">
        Create as draft <span class="btn__arrow" aria-hidden="true">&rarr;</span>
      </button>
      <p class="t-caption u-mt-4 t-muted">It will not appear on the public site until you publish it.</p>
    </form>
  </section>

<?php else: ?>

  <!-- ------------------------------------------------------------- state -->
  <section class="admin-section" aria-labelledby="state-title">
    <h2 class="admin-section__title" id="state-title">
      Status
      <?php if ($project->isPublished()): ?>
        <span class="admin-badge admin-badge--ok">live</span>
      <?php else: ?>
        <span class="admin-badge admin-badge--warn"><?= e($project->publication) ?></span>
      <?php endif; ?>
      <?php if ($project->isFeatured): ?>
        <span class="admin-badge admin-badge--muted">featured</span>
      <?php endif; ?>
    </h2>

    <div class="admin-state-row u-mt-5">
      <?php
      $actions = $project->isPublished()
          ? [['draft', 'Move to draft', 'btn--secondary'], ['archive', 'Archive', 'btn--ghost']]
          : [['publish', 'Publish', 'btn--primary'], ['archive', 'Archive', 'btn--ghost']];

      $actions[] = $project->isFeatured
          ? ['unfeature', 'Remove from featured', 'btn--ghost']
          : ['feature', 'Feature on home page', 'btn--secondary'];
      ?>
      <?php foreach ($actions as [$action, $label, $class]): ?>
        <form method="post" action="<?= e(route_url('/admin/projects/' . $project->id . '/state')) ?>">
          <input type="hidden" name="_token" value="<?= e($csrf) ?>">
          <input type="hidden" name="action" value="<?= e($action) ?>">
          <button class="btn <?= e($class) ?> btn--sm" type="submit"><?= e($label) ?></button>
        </form>
      <?php endforeach; ?>
    </div>
  </section>

  <!-- ------------------------------------------------------------ images -->
  <section class="admin-section" aria-labelledby="images-title">
    <h2 class="admin-section__title" id="images-title">Screenshots</h2>
    <p class="t-body-sm t-muted admin-section__intro">
      The thumbnail is what the home page and the work list show. The cover is
      used for social previews. Both go through the same pipeline as the profile
      photograph: re-encoded, EXIF stripped, sized variants generated.
    </p>

    <div class="admin-grid u-mt-5">
      <?php foreach ([['thumbnail', 'Thumbnail', $project->thumbnail], ['cover', 'Cover', $project->cover]] as [$kind, $label, $media]): ?>
        <div class="media-slot">
          <p class="media-slot__label"><?= e($label) ?></p>

          <?php $url = $media?->url('thumb', 'jpeg') ?? $media?->url('hero', 'jpeg'); ?>
          <?php if ($url !== null): ?>
            <img class="media-slot__img" src="<?= e(route_url($url)) ?>"
                 alt="<?= e($media->alt($project->title)) ?>" width="120" height="120" loading="lazy">
          <?php else: ?>
            <div class="media-slot__empty" aria-hidden="true">no image</div>
          <?php endif; ?>

          <form method="post" enctype="multipart/form-data" class="u-mt-4"
                action="<?= e(route_url('/admin/projects/' . $project->id . '/image')) ?>">
            <input type="hidden" name="_token" value="<?= e($csrf) ?>">
            <input type="hidden" name="kind" value="<?= e($kind) ?>">

            <div class="field">
              <label class="field__label u-visually-hidden" for="image-<?= e($kind) ?>">
                <?= e($label) ?> file
              </label>
              <input class="field__input field__input--file" type="file" required
                     id="image-<?= e($kind) ?>" name="image"
                     accept="image/jpeg,image/png,image/webp">
            </div>

            <div class="field">
              <label class="field__label" for="alt-<?= e($kind) ?>">Image description</label>
              <input class="field__input" type="text" id="alt-<?= e($kind) ?>" name="alt_text"
                     maxlength="255" value="<?= e($media?->altText ?? '') ?>"
                     placeholder="<?= e($project->title . ' screenshot') ?>">
            </div>

            <button class="btn btn--secondary btn--sm u-mt-4" type="submit">
              <?= $media === null ? 'Upload' : 'Replace' ?>
            </button>
          </form>

          <?php if ($media !== null): ?>
            <form method="post" class="u-mt-3"
                  action="<?= e(route_url('/admin/projects/' . $project->id . '/image/remove')) ?>"
                  data-confirm="Remove this image?">
              <input type="hidden" name="_token" value="<?= e($csrf) ?>">
              <input type="hidden" name="kind" value="<?= e($kind) ?>">
              <button class="btn btn--ghost btn--sm" type="submit">Remove</button>
            </form>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>

    <p class="field__help u-mt-4">
      JPEG, PNG or WebP. At least <?= e($limits['min_side']) ?> px on the short
      side, up to <?= e($limits['max']) ?>.
      <?php if ($limits['mismatch'] !== ''): ?>
        <br><strong>This server caps uploads at <?= e($limits['max']) ?></strong>
        (php.ini: upload_max_filesize <?= e($limits['php_limit']) ?>,
        post_max_size <?= e($limits['post_limit']) ?>), below the
        application&rsquo;s configured <?= e($limits['configured']) ?>.
      <?php endif; ?>
    </p>
  </section>

  <!-- --------------------------------------------------- everything else -->
  <form method="post" action="<?= e(route_url('/admin/projects/' . $project->id)) ?>">
    <input type="hidden" name="_token" value="<?= e($csrf) ?>">

    <section class="admin-section" aria-labelledby="basics-title">
      <h2 class="admin-section__title" id="basics-title">Basics</h2>

      <div class="admin-grid">
        <div class="field">
          <label class="field__label" for="title">Title</label>
          <input class="field__input" type="text" id="title" name="title" maxlength="160"
                 required value="<?= e($project->title) ?>">
        </div>
        <div class="field">
          <label class="field__label" for="slug">URL slug</label>
          <input class="field__input" type="text" id="slug" name="slug" maxlength="160"
                 required value="<?= e($project->slug) ?>">
          <p class="field__help">Changing this changes the public URL and breaks existing links to it.</p>
        </div>
        <div class="field">
          <label class="field__label" for="category_label">Category</label>
          <input class="field__input" type="text" id="category_label" name="category_label"
                 maxlength="120" value="<?= e($project->categoryLabel) ?>">
        </div>
        <div class="field">
          <label class="field__label" for="year_label">Year</label>
          <input class="field__input" type="text" id="year_label" name="year_label"
                 maxlength="40" value="<?= e($project->yearLabel) ?>">
        </div>
        <div class="field">
          <label class="field__label" for="role">Role</label>
          <input class="field__input" type="text" id="role" name="role" maxlength="200"
                 value="<?= e($project->role) ?>">
        </div>
        <div class="field">
          <label class="field__label" for="status_label">Status label</label>
          <input class="field__input" type="text" id="status_label" name="status_label"
                 maxlength="80" value="<?= e($project->statusLabel) ?>">
          <p class="field__help">Shown on the case study, e.g. &ldquo;landing page live, booking flow in development&rdquo;.</p>
        </div>
      </div>

      <div class="field">
        <label class="field__label" for="problem_statement">Problem statement</label>
        <textarea class="field__textarea" id="problem_statement" name="problem_statement"
                  maxlength="500" rows="3"><?= e($project->problemStatement) ?></textarea>
      </div>

      <div class="field">
        <label class="field__label" for="summary">Summary</label>
        <textarea class="field__textarea" id="summary" name="summary"
                  maxlength="2000" rows="4"><?= e($project->summary ?? '') ?></textarea>
        <p class="field__help">Used for the meta description and social previews.</p>
      </div>

      <div class="admin-grid">
        <div class="field">
          <label class="field__label" for="github_url">GitHub URL</label>
          <input class="field__input" type="url" id="github_url" name="github_url"
                 maxlength="255" value="<?= e($project->githubUrl ?? '') ?>">
          <p class="field__help">Left empty, the link is hidden rather than broken.</p>
        </div>
        <div class="field">
          <label class="field__label" for="live_url">Live URL</label>
          <input class="field__input" type="url" id="live_url" name="live_url"
                 maxlength="255" value="<?= e($project->liveUrl ?? '') ?>">
        </div>
      </div>
    </section>

    <!-- ---------------------------------------------------- case study -->
    <section class="admin-section" aria-labelledby="sections-title">
      <h2 class="admin-section__title" id="sections-title">Case study</h2>
      <p class="t-body-sm t-muted admin-section__intro">
        Leave a section empty to omit it &mdash; the page shows no heading for a
        section with no body, rather than an empty one. Blank lines separate
        paragraphs. No HTML: it would be escaped, not rendered.
      </p>

      <?php foreach ($sectionKeys as $key => $heading): ?>
        <div class="field">
          <label class="field__label" for="section-<?= e($key) ?>">
            <?= e($heading) ?>
            <span class="field__required"><?= e($key) ?></span>
          </label>
          <textarea class="field__textarea" id="section-<?= e($key) ?>"
                    name="sections[<?= e($key) ?>]" rows="4"><?= e($body($key)) ?></textarea>
        </div>
      <?php endforeach; ?>
    </section>

    <!-- ------------------------------------------------------ features -->
    <section class="admin-section" aria-labelledby="features-title">
      <h2 class="admin-section__title" id="features-title">Features</h2>
      <p class="t-body-sm t-muted admin-section__intro">
        Empty rows are dropped on save. Add more by filling the blank row and
        saving &mdash; a fresh blank row appears each time.
      </p>

      <?php
      $features = $project->features;
      $features[] = null;   // one always-empty row, so adding needs no JavaScript
      ?>
      <?php foreach ($features as $index => $feature): ?>
        <div class="admin-grid">
          <div class="field">
            <label class="field__label" for="feature-title-<?= e((string) $index) ?>">
              Feature <?= e((string) ($index + 1)) ?>
            </label>
            <input class="field__input" type="text" id="feature-title-<?= e((string) $index) ?>"
                   name="feature_title[]" maxlength="200"
                   value="<?= e($feature?->title ?? '') ?>">
          </div>
          <div class="field">
            <label class="field__label" for="feature-desc-<?= e((string) $index) ?>">Description</label>
            <input class="field__input" type="text" id="feature-desc-<?= e((string) $index) ?>"
                   name="feature_description[]" maxlength="500"
                   value="<?= e($feature?->description ?? '') ?>">
          </div>
        </div>
      <?php endforeach; ?>
    </section>

    <!-- -------------------------------------------------- technologies -->
    <section class="admin-section" aria-labelledby="tech-title">
      <h2 class="admin-section__title" id="tech-title">Technologies</h2>
      <p class="t-body-sm t-muted admin-section__intro">
        Only tag what the project genuinely uses now. Planned work belongs in
        the &ldquo;Next steps&rdquo; section, not here &mdash; a tag is a claim.
      </p>

      <?php foreach ($skills as $category => $group): ?>
        <fieldset class="admin-tags">
          <legend class="admin-tags__legend"><?= e($category) ?></legend>
          <?php foreach ($group as $skill): ?>
            <label class="admin-tag">
              <input type="checkbox" name="technologies[]" value="<?= e((string) $skill->id) ?>"
                     <?= in_array($skill->id, $tagged, true) ? 'checked' : '' ?>>
              <span><?= e($skill->name) ?></span>
            </label>
          <?php endforeach; ?>
        </fieldset>
      <?php endforeach; ?>

      <?php /* The home-page card shows ONLY the primary ones, so this list is
               not decorative: leave it empty and that card shows no tags at
               all. A multi-select rather than a second checkbox beside every
               skill, because this is meant to be a handful, not a copy of the
               grid above. */ ?>
      <div class="field u-mt-6">
        <label class="field__label" for="primary_technologies">
          Show on the home-page card <span class="field__required">pick a few</span>
        </label>
        <select class="field__input admin-multiselect" id="primary_technologies"
                name="primary_technologies[]" multiple size="8">
          <?php foreach ($skills as $category => $group): ?>
            <optgroup label="<?= e($category) ?>">
              <?php foreach ($group as $skill): ?>
                <option value="<?= e((string) $skill->id) ?>"
                        <?= in_array($skill->id, $primary, true) ? 'selected' : '' ?>>
                  <?= e($skill->name) ?>
                </option>
              <?php endforeach; ?>
            </optgroup>
          <?php endforeach; ?>
        </select>
        <p class="field__help">
          Hold Ctrl (Cmd on a Mac) to select several. Anything chosen here that
          is not ticked above is ignored &mdash; a project cannot headline a
          technology it does not use.
        </p>
      </div>
    </section>

    <div class="admin-actions">
      <button class="btn btn--primary btn--lg" type="submit">
        Save project <span class="btn__arrow" aria-hidden="true">&rarr;</span>
      </button>
      <a class="btn btn--ghost" href="<?= e(route_url('/admin/projects')) ?>">Back to list</a>
    </div>
  </form>

  <!-- ------------------------------------------------------------ delete -->
  <section class="admin-section">
    <form method="post" action="<?= e(route_url('/admin/projects/' . $project->id . '/delete')) ?>"
          data-confirm="Delete this project? It can be restored from the project list.">
      <input type="hidden" name="_token" value="<?= e($csrf) ?>">
      <button class="btn btn--ghost btn--sm" type="submit">Delete project</button>
      <p class="field__help">A soft delete &mdash; nothing is lost, and the slug becomes reusable.</p>
    </form>
  </section>

<?php endif; ?>
