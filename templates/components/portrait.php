<?php
/**
 * Profile portrait plate.
 *
 * The ONE place in the application that turns a profile photo into markup.
 * It receives a Media object or null; it never knows a filename. That is what
 * makes "upload, replace or remove the photo from the admin and it changes
 * everywhere" structurally true rather than merely currently true.
 *
 * @var \App\Domain\Profile\Profile|null $profile
 * @var string $variant   which derived size to request
 * @var bool   $showCaption
 */
$photo    = $profile?->photo;
$name     = $profile?->fullName ?? '';
$monogram = $profile?->monogram() ?? 'RT';
$location = $profile?->location ?? '';
$variant  = $variant ?? 'hero';
$showCaption = $showCaption ?? true;
?>
<figure class="portrait">
  <div class="portrait__plate">
    <div class="portrait__inner">

      <?php if ($photo !== null): ?>
        <picture>
          <?php if ($webp = $photo->url($variant, 'webp')): ?>
            <source srcset="<?= e(route_url($webp)) ?>" type="image/webp">
          <?php endif; ?>
          <img class="portrait__img"
               src="<?= e(route_url((string) $photo->url($variant, 'jpeg'))) ?>"
               alt="<?= e($photo->alt('Portrait of ' . $name)) ?>"
               width="<?= e((string) $photo->width) ?>"
               height="<?= e((string) $photo->height) ?>"
               fetchpriority="high"
               decoding="async">
        </picture>
      <?php else: ?>
        <?php /* Designed fallback state — never a broken image. */ ?>
        <div class="portrait__fallback">
          <span class="portrait__monogram" aria-hidden="true"><?= e($monogram) ?></span>
          <span class="portrait__note">
            Photo managed from<br>the admin dashboard
          </span>
        </div>
      <?php endif; ?>

      <span class="portrait__tint" aria-hidden="true"></span>
    </div>
  </div>

  <?php if ($showCaption): ?>
    <figcaption class="portrait__caption">
      <b><?= e($name) ?></b>
      <?php if ($location !== ''): ?><span><?= e($location) ?></span><?php endif; ?>
    </figcaption>
  <?php endif; ?>
</figure>
