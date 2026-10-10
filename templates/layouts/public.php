<?php
/**
 * Public site layout.
 *
 * @var \App\Core\Seo                     $meta
 * @var \App\Domain\Profile\Profile|null  $profile
 * @var string                            $siteName
 * @var bool                              $isHome
 * @var string                            $content
 * @var bool                              $isPreview  guarded admin preview only
 */
$locale = 'en';

// Defaulted, not assumed: every public page renders through this layout, and
// only the guarded preview route passes the flag at all.
$isPreview = $isPreview ?? false;
$flash     = $flash ?? null;
?>
<!doctype html>
<html lang="<?= e($locale) ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">

<?php require __DIR__ . '/../partials/head-meta.php'; ?>

<link rel="stylesheet" href="<?= e(asset('assets/css/main.css')) ?>">
<script src="<?= e(asset('assets/js/app.js')) ?>" defer></script>
</head>

<body<?= $isPreview ? ' class="is-preview"' : '' ?>>

<a class="skip-link" href="#main">Skip to content</a>

<?php if ($isPreview): ?>
  <?php /* Rendered by the LAYOUT, not the page, so it sits above the site
           header rather than fighting it for top: 0. Unmissable on purpose: an
           unpublished page otherwise looks exactly like a published one, and
           the easy mistake is believing a draft is live because it rendered. */ ?>
  <?php require __DIR__ . '/../partials/preview-bar.php'; ?>
<?php endif; ?>

<?php require __DIR__ . '/../partials/header.php'; ?>
<?php require __DIR__ . '/../partials/nav-panel.php'; ?>

<main id="main">
<?php if ($flash !== null): ?>
  <?php /* The contact form's result. Rendered by the LAYOUT so the message
           survives the redirect that follows a POST — a result printed by the
           form itself would need the form to re-render on POST, which would
           break the back button and re-submit on refresh. */ ?>
  <div class="container">
    <div class="site-flash site-flash--<?= e($flash['type']) ?>"
         role="<?= $flash['type'] === 'error' ? 'alert' : 'status' ?>">
      <?= e($flash['message']) ?>
    </div>
  </div>
<?php endif; ?>

<?= $content ?>
</main>

<?php require __DIR__ . '/../partials/footer.php'; ?>

</body>
</html>
