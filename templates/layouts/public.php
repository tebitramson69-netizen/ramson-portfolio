<?php
/**
 * Public site layout.
 *
 * @var \App\Core\Seo                     $meta
 * @var \App\Domain\Profile\Profile|null  $profile
 * @var string                            $siteName
 * @var bool                              $isHome
 * @var string                            $content
 */
$locale = 'en';
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

<body>

<a class="skip-link" href="#main">Skip to content</a>

<?php require __DIR__ . '/../partials/header.php'; ?>
<?php require __DIR__ . '/../partials/nav-panel.php'; ?>

<main id="main">
<?= $content ?>
</main>

<?php require __DIR__ . '/../partials/footer.php'; ?>

</body>
</html>
