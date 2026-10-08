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

// Dimensions and alt text accompany the image when it comes from the profile.
// Scrapers that are given them lay the card out before the file has finished
// downloading; scrapers that are not given them sometimes skip the image.
$ogVariant = null;

if ($ogImage === '' && ($profile?->hasPhoto() ?? false)) {
    $path = $profile->photo?->url('og', 'jpeg');
    if ($path !== null) {
        $ogImage   = absolute_url($path);
        $ogVariant = $profile->photo?->variant('og', 'jpeg');
        $ogAlt     = $profile->photo?->alt($profile->fullName);
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
<?php if ($ogVariant !== null): ?>
<meta property="og:image:width" content="<?= e((string) $ogVariant->width) ?>">
<meta property="og:image:height" content="<?= e((string) $ogVariant->height) ?>">
<meta property="og:image:alt" content="<?= e($ogAlt ?? '') ?>">
<?php endif; ?>
<meta name="twitter:card" content="summary_large_image">
<?php else: ?>
<meta name="twitter:card" content="summary">
<?php endif; ?>

<?php foreach ($meta->jsonLd as $schema): ?>
<script type="application/ld+json"><?= e_js($schema) ?></script>
<?php endforeach; ?>

<?php /* Fonts are self-hosted and declared at the top of main.css, so there
         is nothing to load from a third party here and no preconnect worth
         making. Phase 9. */ ?>
