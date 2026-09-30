<?php
/**
 * Admin layout.
 *
 * @var \App\Core\Seo $meta
 * @var \App\Domain\Auth\AdminUser|null $admin
 * @var string $pageTitle
 * @var string $siteName
 * @var array{type:string,message:string}|null $flash
 * @var string $content
 */
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<?php require __DIR__ . '/../partials/head-meta.php'; ?>
<link rel="stylesheet" href="<?= e(asset('assets/css/main.css')) ?>">
<link rel="stylesheet" href="<?= e(asset('assets/css/admin.css')) ?>">
<script src="<?= e(asset('assets/js/app.js')) ?>" defer></script>
<script src="<?= e(asset('assets/js/admin.js')) ?>" defer></script>
</head>
<body class="admin-body">

<a class="skip-link" href="#main">Skip to content</a>

<?php require __DIR__ . '/../partials/admin-nav.php'; ?>

<main id="main" class="admin-main">
  <div class="admin-container">

    <?php if ($flash !== null): ?>
      <div class="admin-flash admin-flash--<?= e($flash['type']) ?>"
           role="<?= $flash['type'] === 'error' ? 'alert' : 'status' ?>">
        <?= e($flash['message']) ?>
      </div>
    <?php endif; ?>

    <?= $content ?>

  </div>
</main>

</body>
</html>
