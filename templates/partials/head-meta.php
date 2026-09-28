<?php
/**
 * Document head metadata: title, description, canonical, Open Graph, JSON-LD.
 *
 * One partial so a page cannot ship half a set of tags, and so the Open Graph
 * values are guaranteed to be the same strings as the visible title and
 * description rather than a second, drifting copy.
 *
 * @var \App\Core\Seo                    $meta
 * @var \App\Domain\Profile\Profile|null $profile
 * @var string                           $siteName
 * @var bool                             $isHome
 */
$title       = $meta->documentTitle($siteName, $isHome);
$description = $meta->description;
$canonical   = $meta->canonical;
$ogImage     = $meta->ogImage;

if ($ogImage === '' && ($profile?->hasPhoto() ?? false)) {
    $path = $profile->photo?->url('og', 'jpeg');
    if ($path !== null) {
        $ogImage = absolute_url($path);
    }
}
?>
<title><?= e($title) ?></title>
<?php if ($description !== ''): ?>
<meta name="description" content="<?= e($description) ?>">
<?php endif; ?>
<?php if ($meta->noindex): ?>
<meta name="robots" content="noindex, nofollow">
<?php endif; ?>
<?php if ($canonical !== ''): ?>
<link rel="canonical" href="<?= e_url($canonical) ?>">
<?php endif; ?>

<meta property="og:site_name" content="<?= e($siteName) ?>">
<meta property="og:type" content="<?= e($meta->ogType) ?>">
<meta property="og:title" content="<?= e($title) ?>">
<?php if ($description !== ''): ?>
<meta property="og:description" content="<?= e($description) ?>">
<?php endif; ?>
<?php if ($canonical !== ''): ?>
<meta property="og:url" content="<?= e_url($canonical) ?>">
<?php endif; ?>
<?php if ($ogImage !== ''): ?>
<meta property="og:image" content="<?= e_url($ogImage) ?>">
<meta name="twitter:card" content="summary_large_image">
<?php else: ?>
<meta name="twitter:card" content="summary">
<?php endif; ?>

<?php foreach ($meta->jsonLd as $schema): ?>
<script type="application/ld+json"><?= e_js($schema) ?></script>
<?php endforeach; ?>

<!--
  PHASE 1 NOTE — fonts load from Google Fonts. Phase 9 self-hosts them as
  subsetted WOFF2, which removes this third-party connection and lets the
  Content-Security-Policy in config/app.php collapse to 'self' throughout.
-->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Instrument+Serif:ital@0;1&amp;family=Inter:wght@400;500;600&amp;family=JetBrains+Mono:wght@400;500&amp;display=swap">
