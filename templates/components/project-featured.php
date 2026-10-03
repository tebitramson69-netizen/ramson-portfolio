<?php
/**
 * Featured project block — the large alternating editorial treatment.
 *
 * Markup is unchanged from the approved Phase 1 design; only the source of
 * the content changed. Anything the CMS does not hold renders its designed
 * pending state rather than an empty element or an invented value.
 *
 * @var \App\Domain\Project\Project $project
 * @var int $index
 */
$pending = static function (array $data): void {
    extract($data, EXTR_SKIP);
    require dirname(__DIR__) . '/components/pending.php';
};

$questionRef = $project->slug === 'rendo'
    ? '03-OPEN-QUESTIONS · Q5'
    : '03-OPEN-QUESTIONS · Q6';
?>
<article class="work__item" data-reveal>
  <div class="work__grid">

    <div class="work__media">
      <div class="frame">
        <div class="frame__bar" aria-hidden="true">
          <span class="frame__dot"></span><span class="frame__dot"></span><span class="frame__dot"></span>
        </div>
        <div class="frame__body">
          <?php
          /**
           * 'wide' is a 16:10 crop, matching .frame__body's aspect-ratio. A
           * project card shows a website screenshot, which is landscape; the
           * 4:5 'hero' crop belongs to a portrait photograph. Asking for
           * 'hero' here cropped a wide screenshot tall, and object-fit: cover
           * then cropped it wide again.
           *
           * Falls back to 'hero' so an image uploaded before 'wide' existed
           * still renders — badly cropped, but present, which beats a card
           * that silently shows nothing.
           */
          $shot = $project->thumbnail?->variant('wide', 'jpeg')
              ?? $project->thumbnail?->variant('hero', 'jpeg');
          ?>
          <?php if ($project->thumbnail !== null && $shot !== null): ?>
            <?php $size = $shot->variant; ?>
            <picture>
              <?php foreach ([['avif', 'image/avif'], ['webp', 'image/webp']] as [$format, $type]): ?>
                <?php if ($url = $project->thumbnail->url($size, $format)): ?>
                  <source srcset="<?= e(route_url($url)) ?>" type="<?= e($type) ?>">
                <?php endif; ?>
              <?php endforeach; ?>
              <img class="frame__img"
                   src="<?= e(route_url((string) $project->thumbnail->url($size, 'jpeg'))) ?>"
                   alt="<?= e($project->thumbnail->alt($project->title . ' screenshot')) ?>"
                   <?php /* The VARIANT's dimensions, not the source photo's. The
                             source is whatever was uploaded; the variant is the
                             crop actually being served, and using the source
                             numbers reserves the wrong aspect ratio and shifts
                             the page on load. */ ?>
                   width="<?= e((string) $shot->width) ?>"
                   height="<?= e((string) $shot->height) ?>"
                   loading="lazy" decoding="async">
            </picture>
          <?php else: ?>
            <?php $pending([
              'label' => 'Awaiting asset',
              'title' => $project->title . ' screenshot',
              'body'  => 'Uploaded through the CMS in Phase 6.',
              'ref'   => $questionRef,
            ]); ?>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <div class="work__content">
      <?php if ($project->categoryLabel !== ''): ?>
        <p class="t-eyebrow"><?= e($project->categoryLabel) ?></p>
      <?php endif; ?>

      <h3 class="t-display-3 work__title"><?= e($project->title) ?></h3>

      <?php if ($project->problemStatement !== ''): ?>
        <p class="work__problem"><?= e($project->problemStatement) ?></p>
      <?php endif; ?>

      <?php if ($project->summary !== null && $project->summary !== ''): ?>
        <p><?= e($project->summary) ?></p>
      <?php endif; ?>

      <?php if ($project->features !== []): ?>
        <ul class="work__features">
          <?php foreach ($project->features as $feature): ?>
            <li><?= e($feature->title) ?></li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>

      <?php $primary = $project->primaryTechnologies(); ?>
      <?php if ($primary !== []): ?>
        <div class="tag-list u-mt-5">
          <?php foreach ($primary as $technology): ?>
            <span class="tag"><?= e($technology->name) ?></span>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <?php if ($project->role !== ''): ?>
        <p class="work__role">Role — <?= e($project->role) ?></p>
      <?php endif; ?>

      <?php
      // What is still missing, named precisely rather than guessed at.
      $gaps = [];
      if ($project->role === '')          { $gaps[] = 'your personal role'; }
      if ($primary === [])                { $gaps[] = 'the technology stack'; }
      if ($project->statusLabel === '')   { $gaps[] = 'current state'; }
      if ($project->githubUrl === null)   { $gaps[] = 'GitHub URL'; }
      if ($project->liveUrl === null)     { $gaps[] = 'live URL'; }
      ?>
      <?php if ($gaps !== []): ?>
        <?php $pending([
          'label' => 'Awaiting detail',
          'body'  => 'Still needed: ' . implode(', ', $gaps) . '.',
          'ref'   => $questionRef,
          'class' => 'u-mt-5',
        ]); ?>
      <?php endif; ?>

      <div class="btn-group work__actions">
        <a class="btn btn--secondary" href="<?= e(route_url($project->url())) ?>">
          View case study
          <span class="btn__arrow" aria-hidden="true">&rarr;</span>
        </a>
        <?php if ($project->githubUrl !== null): ?>
          <a class="btn btn--ghost" href="<?= e_url($project->githubUrl) ?>" rel="noopener">GitHub</a>
        <?php endif; ?>
        <?php if ($project->liveUrl !== null): ?>
          <a class="btn btn--ghost" href="<?= e_url($project->liveUrl) ?>" rel="noopener">Live site</a>
        <?php endif; ?>
      </div>
    </div>

  </div>
</article>
