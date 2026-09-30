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

      <?php
      // The JPEG is the fallback every browser understands, so its variant is
      // also what supplies width and height. Those MUST be the variant's own
      // dimensions and not the source photograph's: the source is a 4:3 phone
      // photo, the variant is a 4:5 crop, and using the source numbers would
      // reserve the wrong aspect ratio and shift the whole page on load.
      // Null-safe: a profile with no photograph at all falls through to the
      // designed monogram below, and so does one whose JPEG variant is
      // somehow missing. Neither case can render a broken image.
      $fallback = $photo?->variant($variant, 'jpeg');
      ?>
      <?php if ($photo !== null && $fallback !== null): ?>
        <picture>
          <?php foreach ([['avif', 'image/avif'], ['webp', 'image/webp']] as [$format, $type]): ?>
            <?php if ($url = $photo->url($variant, $format)): ?>
              <source srcset="<?= e(route_url($url)) ?>" type="<?= e($type) ?>">
            <?php endif; ?>
          <?php endforeach; ?>
          <img class="portrait__img"
               src="<?= e(route_url((string) $photo->url($variant, 'jpeg'))) ?>"
               alt="<?= e($photo->alt('Portrait of ' . $name)) ?>"
               width="<?= e((string) $fallback->width) ?>"
               height="<?= e((string) $fallback->height) ?>"
               <?= $variant === 'hero' ? 'fetchpriority="high"' : 'loading="lazy"' ?>
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
