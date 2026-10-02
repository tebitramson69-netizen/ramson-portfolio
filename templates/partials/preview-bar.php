<?php
/**
 * Draft-preview bar.
 *
 * Rendered by the public layout only on the guarded /admin/preview/{slug}
 * route. It is deliberately loud: without it an unpublished page looks
 * identical to a published one, and the easy mistake is then believing a draft
 * is live because it rendered.
 *
 * `body.is-preview` pushes the fixed site header down by this bar's height, so
 * the two do not both occupy the top of the viewport.
 *
 * @var \App\Domain\Project\Project|null $project
 */
$state = $project?->publication ?? 'draft';
?>
<div class="preview-bar" role="status">
  <div class="container preview-bar__inner">
    <span class="preview-bar__state"><?= e($state) ?></span>
    <span class="preview-bar__text">
      <?php if ($project !== null && $project->isPublished()): ?>
        Preview of a published project &mdash; this is what visitors see.
      <?php else: ?>
        Preview only. This project is <strong><?= e($state) ?></strong>,
        so the public URL returns 404.
      <?php endif; ?>
    </span>
    <a class="preview-bar__link" href="<?= e(route_url('/admin/projects')) ?>">Back to admin</a>
  </div>
</div>
