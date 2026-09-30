<?php
/**
 * Login layout — no navigation, no site chrome, nothing to wander into.
 *
 * @var \App\Core\Seo $meta
 * @var string $siteName
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
</head>
<body class="auth-body">
<main id="main" class="auth-shell">
<?= $content ?>
</main>
</body>
</html>
