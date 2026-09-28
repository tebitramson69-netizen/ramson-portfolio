<?php
/**
 * Case study, rendered from the database.
 *
 * The approved Phase 1 composition, now driven by data. Two guarantees carry
 * over from the design and are enforced here:
 *
 *   - a section with no row renders NOTHING — no orphan heading, no
 *     "coming soon", no lorem;
 *   - a GitHub or live button appears only when the CMS holds that URL,
 *     because a dead link is worse than no link.
 *
 * @var \App\Domain\Project\Project $project
 * @var array{slug:string,title:string,category_label:string}|null $previous
 * @var array{slug:string,title:string,category_label:string}|null $next
 * @var \App\Domain\Profile\Profile|null $profile
 */
$sections = $project->orderedSections();

$pending = static function (array $data): void {
    extract($data, EXTR_SKIP);
    require dirname(__DIR__) . '/components/pending.php';
};

$questionRef = $project->slug === 'rendo'
    ? '03-OPEN-QUESTIONS · Q5'
    : '03-OPEN-QUESTIONS · Q6';
?>
<div class="progress" aria-hidden="true"></div>

<article>
<header class="cs-hero">
  <div class="container">

    <a class="cs-hero__back" href="<?= e(route_url('/')) ?>#work">
      <span aria-hidden="true">&larr;</span> All work
    </a>

    <?php if ($project->categoryLabel !== ''): ?>
      <p class="t-eyebrow u-mt-6"><?= e($project->categoryLabel) ?></p>
    <?php endif; ?>

    <h1 class="t-display-1 cs-hero__title"><?= e($project->title) ?></h1>

    <?php if ($project->summary !== null && $project->summary !== ''): ?>
      <p class="t-lead cs-hero__summary"><?= e($project->summary) ?></p>
    <?php endif; ?>

    <div class="cs-meta cs-hero__meta">
      <div>
        <p class="cs-meta__label">Role</p>
        <p class="cs-meta__value<?= $project->role === '' ? ' t-muted' : '' ?>">
          <?= e($project->role !== '' ? $project->role : 'Awaiting confirmation') ?>
        </p>
      </div>
      <div>
        <p class="cs-meta__label">Stack</p>
        <?php $technologies = $project->technologies; ?>
        <p class="cs-meta__value<?= $technologies === [] ? ' t-muted' : '' ?>">
          <?= e($technologies === []
                ? 'Awaiting confirmation'
                : implode(' · ', array_map(static fn ($s) => $s->name, $technologies))) ?>
        </p>
      </div>
      <div>
        <p class="cs-meta__label">Category</p>
        <p class="cs-meta__value"><?= e($project->categoryLabel) ?></p>
      </div>
      <div>
        <p class="cs-meta__label">Status</p>
        <p class="cs-meta__value<?= $project->statusLabel === '' ? ' t-muted' : '' ?>">
          <?= e($project->statusLabel !== '' ? $project->statusLabel : 'Awaiting confirmation') ?>
        </p>
      </div>
    </div>

    <?php if ($project->hasLinks()): ?>
      <div class="btn-group cs-hero__actions">
        <?php if ($project->liveUrl !== null): ?>
          <a class="btn btn--primary" href="<?= e_url($project->liveUrl) ?>" rel="noopener">
            Visit live site <span class="btn__arrow" aria-hidden="true">&rarr;</span>
          </a>
        <?php endif; ?>
        <?php if ($project->githubUrl !== null): ?>
          <a class="btn btn--secondary" href="<?= e_url($project->githubUrl) ?>" rel="noopener">View on GitHub</a>
        <?php endif; ?>
      </div>
    <?php endif; ?>

  </div>
</header>


<div class="section">
  <div class="container">
    <div class="cs-layout">

      <nav class="cs-toc" aria-label="On this page">
        <h2 class="cs-toc__heading">On this page</h2>
        <ul class="cs-toc__list">
          <?php foreach ($sections as $section): ?>
            <li>
              <a class="cs-toc__link" href="#<?= e($section->key) ?>" data-spy-link>
                <?= e($section->heading) ?>
              </a>
            </li>
          <?php endforeach; ?>
          <?php if ($project->features !== []): ?>
            <li><a class="cs-toc__link" href="#features" data-spy-link>Features</a></li>
          <?php endif; ?>
        </ul>
      </nav>

      <div class="cs-body">

        <?php $number = 0; ?>
        <?php foreach ($sections as $section): ?>
          <?php $number++; ?>
          <section class="cs-section" id="<?= e($section->key) ?>" data-reveal>
            <p class="cs-section__index"><?= e(str_pad((string) $number, 2, '0', STR_PAD_LEFT)) ?></p>
            <h2 class="t-display-3 cs-section__title"><?= e($section->heading) ?></h2>
            <div class="cs-section__body t-prose">
              <?php foreach ($section->paragraphs() as $paragraph): ?>
                <p><?= e($paragraph) ?></p>
              <?php endforeach; ?>
            </div>
          </section>
        <?php endforeach; ?>

        <?php if ($project->features !== []): ?>
          <?php $number++; ?>
          <section class="cs-section" id="features" data-reveal>
            <p class="cs-section__index"><?= e(str_pad((string) $number, 2, '0', STR_PAD_LEFT)) ?></p>
            <h2 class="t-display-3 cs-section__title">Features</h2>
            <div class="cs-section__body">
              <ul class="work__features">
                <?php foreach ($project->features as $feature): ?>
                  <li>
                    <strong><?= e($feature->title) ?></strong><?php
                      if ($feature->description !== '') {
                          echo ' — ' . e($feature->description);
                      }
                    ?>
                  </li>
                <?php endforeach; ?>
              </ul>
            </div>
          </section>
        <?php endif; ?>

        <?php
        // Name the sections that have no row yet, rather than rendering an
        // empty heading for each. The list is derived, so it shrinks by itself
        // as content is added through the CMS.
        $present = array_map(static fn ($s) => $s->key, $sections);
        $wanted  = ['context', 'role', 'technical', 'decisions', 'outcome', 'lessons'];
        $missing = array_values(array_diff($wanted, $present));
        ?>
        <?php if ($missing !== []): ?>
          <div class="u-mt-7" data-reveal>
            <?php $pending([
              'label' => 'Awaiting content',
              'title' => 'Sections not yet written',
              'body'  => implode(', ', array_map(
                  static fn (string $k): string => \App\Domain\Project\SectionKey::heading($k),
                  $missing
              )) . '. Each appears automatically once it has content — an unwritten section renders nothing at all.',
              'ref'   => $questionRef,
            ]); ?>
          </div>
        <?php endif; ?>

        <nav class="cs-nav" aria-label="More projects">
          <?php if ($next !== null): ?>
            <div class="card card--link cs-nav__item">
              <p class="cs-nav__dir">Next project</p>
              <h2 class="t-heading-1 cs-nav__title">
                <a href="<?= e(route_url('/work/' . $next['slug'])) ?>"><?= e($next['title']) ?></a>
              </h2>
              <p class="t-caption u-mt-2"><?= e($next['category_label']) ?></p>
            </div>
          <?php elseif ($previous !== null): ?>
            <div class="card card--link cs-nav__item">
              <p class="cs-nav__dir">Previous project</p>
              <h2 class="t-heading-1 cs-nav__title">
                <a href="<?= e(route_url('/work/' . $previous['slug'])) ?>"><?= e($previous['title']) ?></a>
              </h2>
              <p class="t-caption u-mt-2"><?= e($previous['category_label']) ?></p>
            </div>
          <?php endif; ?>

          <div class="card cs-nav__item">
            <p class="cs-nav__dir">Get in touch</p>
            <h2 class="t-heading-1 cs-nav__title">
              <a href="<?= e(route_url('/')) ?>#contact">Start a conversation</a>
            </h2>
            <?php if (($profile?->availabilityNote ?? '') !== ''): ?>
              <p class="t-caption u-mt-2"><?= e($profile->availabilityNote) ?></p>
            <?php endif; ?>
          </div>
        </nav>

      </div>
    </div>
  </div>
</div>
</article>

<footer class="footer">
  <div class="container">
    <div class="footer__bottom footer__bottom--bare">
      <p>&copy; <span data-year><?= e(date('Y')) ?></span> <?= e($profile?->fullName ?? '') ?>. All rights reserved.</p>
      <p><a class="t-link" href="<?= e(route_url('/')) ?>">Back to portfolio</a></p>
    </div>
  </div>
</footer>
