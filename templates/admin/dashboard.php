<?php
/**
 * @var array<string,int> $counts
 * @var list<array{label:string,done:bool,detail:string}> $checklist
 * @var list<array{label:string,ok:bool,detail:string}> $health
 * @var \App\Domain\Auth\AdminUser|null $admin
 */
$outstanding = array_values(array_filter($checklist, static fn (array $i): bool => !$i['done']));
$unhealthy   = array_values(array_filter($health, static fn (array $i): bool => !$i['ok']));
?>

<header class="admin-page-head">
  <p class="t-eyebrow">Dashboard</p>
  <h1 class="t-display-3 u-mt-3">
    <?= e($admin !== null ? 'Welcome back, ' . $admin->firstName() : 'Welcome back') ?>
  </h1>
  <?php if ($admin?->lastLoginAt !== null): ?>
    <p class="t-caption u-mt-3">Previous sign-in: <?= e($admin->lastLoginAt) ?></p>
  <?php endif; ?>
</header>

<!-- ---------------------------------------------------------------- counts -->
<section class="admin-section" aria-labelledby="counts-title">
  <h2 class="admin-section__title" id="counts-title">Content</h2>

  <div class="admin-stats">
    <?php
    $tiles = [
        ['Published projects', $counts['projects_published'] ?? 0, ($counts['projects_featured'] ?? 0) . ' featured'],
        ['Drafts',             $counts['projects_draft'] ?? 0,     'Not visible on the site'],
        ['Case-study sections',$counts['sections'] ?? 0,           'Across all projects'],
        ['Skills',             $counts['skills'] ?? 0,             'Shared vocabulary'],
        ['Media files',        $counts['media'] ?? 0,              'Images in the library'],
    ];
    ?>
    <?php foreach ($tiles as [$label, $value, $note]): ?>
      <div class="admin-stat">
        <p class="admin-stat__value"><?= e((string) $value) ?></p>
        <p class="admin-stat__label"><?= e($label) ?></p>
        <p class="admin-stat__note"><?= e($note) ?></p>
      </div>
    <?php endforeach; ?>
  </div>
</section>

<!-- ------------------------------------------------------------- checklist -->
<section class="admin-section" aria-labelledby="checklist-title">
  <h2 class="admin-section__title" id="checklist-title">
    Content completeness
    <?php if ($outstanding !== []): ?>
      <span class="admin-badge admin-badge--warn"><?= e((string) count($outstanding)) ?> outstanding</span>
    <?php else: ?>
      <span class="admin-badge admin-badge--ok">complete</span>
    <?php endif; ?>
  </h2>

  <p class="t-body-sm t-muted admin-section__intro">
    Each item is a live query, not a reminder. This is what keeps the portfolio
    from quietly going stale.
  </p>

  <ul class="admin-check">
    <?php foreach ($checklist as $item): ?>
      <li class="admin-check__item<?= $item['done'] ? ' is-done' : '' ?>">
        <span class="admin-check__mark" aria-hidden="true"><?= $item['done'] ? '&check;' : '' ?></span>
        <span class="admin-check__body">
          <span class="admin-check__label"><?= e($item['label']) ?></span>
          <span class="admin-check__detail"><?= e($item['detail']) ?></span>
        </span>
        <span class="u-visually-hidden"><?= $item['done'] ? 'Done' : 'Outstanding' ?></span>
      </li>
    <?php endforeach; ?>
  </ul>
</section>

<!-- ---------------------------------------------------------------- health -->
<section class="admin-section" aria-labelledby="health-title">
  <h2 class="admin-section__title" id="health-title">
    Environment
    <?php if ($unhealthy !== []): ?>
      <span class="admin-badge admin-badge--warn"><?= e((string) count($unhealthy)) ?> to check</span>
    <?php else: ?>
      <span class="admin-badge admin-badge--ok">healthy</span>
    <?php endif; ?>
  </h2>

  <p class="t-body-sm t-muted admin-section__intro">
    Surfaced here rather than as a confusing failure halfway through an upload.
  </p>

  <ul class="admin-check">
    <?php foreach ($health as $item): ?>
      <li class="admin-check__item<?= $item['ok'] ? ' is-done' : '' ?>">
        <span class="admin-check__mark" aria-hidden="true"><?= $item['ok'] ? '&check;' : '!' ?></span>
        <span class="admin-check__body">
          <span class="admin-check__label"><?= e($item['label']) ?></span>
          <span class="admin-check__detail"><?= e($item['detail']) ?></span>
        </span>
        <span class="u-visually-hidden"><?= $item['ok'] ? 'OK' : 'Needs attention' ?></span>
      </li>
    <?php endforeach; ?>
  </ul>
</section>

<!-- --------------------------------------------------------------- next up -->
<section class="admin-section" aria-labelledby="next-title">
  <h2 class="admin-section__title" id="next-title">Not built yet</h2>
  <p class="t-body-sm t-muted admin-section__intro">
    Stated plainly so this dashboard never implies a button exists when it does not.
  </p>
  <ul class="admin-check">
    <li class="admin-check__item">
      <span class="admin-check__mark" aria-hidden="true">&rarr;</span>
      <span class="admin-check__body">
        <span class="admin-check__label">Contact inbox</span>
        <span class="admin-check__detail">Phase 8 — the contact form is not live yet</span>
      </span>
    </li>
  </ul>
</section>
