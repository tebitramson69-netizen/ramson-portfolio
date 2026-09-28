<?php
/**
 * Designed empty state for content that has not been supplied yet.
 *
 * Deliberately unmistakable, so nothing placeholder can reach production by
 * accident, and every instance names the question it is waiting on. This is
 * the mechanism that keeps the no-invented-content rule enforceable: a gap
 * renders this rather than tempting anyone to fill the layout with fiction.
 *
 * In Phase 7 it becomes the CMS empty state.
 *
 * @var string      $label
 * @var string|null $title
 * @var string      $body
 * @var string|null $ref
 * @var string|null $class
 */
?>
<div class="pending<?= isset($class) ? ' ' . e($class) : '' ?>">
  <p class="pending__label"><?= e($label ?? 'Awaiting content') ?></p>
  <?php if (!empty($title)): ?>
    <p class="pending__title"><?= e($title) ?></p>
  <?php endif; ?>
  <p class="pending__body"><?= e($body ?? '') ?></p>
  <?php if (!empty($ref)): ?>
    <p class="pending__ref"><?= e($ref) ?></p>
  <?php endif; ?>
</div>
