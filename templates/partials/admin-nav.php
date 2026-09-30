<?php
/**
 * @var \App\Domain\Auth\AdminUser|null $admin
 * @var string $pageTitle
 */
$here = static fn (string $title): string => ($pageTitle ?? '') === $title ? ' aria-current="page"' : '';
?>
<header class="admin-header">
  <div class="admin-container admin-header__inner">

    <div class="admin-header__brand">
      <span class="admin-header__mark">Admin</span>
      <span class="admin-header__page"><?= e($pageTitle ?? '') ?></span>
    </div>

    <nav class="admin-nav" aria-label="Admin">
      <ul class="admin-nav__list">
        <li><a class="admin-nav__link" href="<?= e(route_url('/admin')) ?>"<?= $here('Dashboard') ?>>Dashboard</a></li>
        <li><a class="admin-nav__link" href="<?= e(route_url('/admin/profile')) ?>"<?= $here('Profile') ?>>Profile</a></li>
        <li><a class="admin-nav__link" href="<?= e(route_url('/')) ?>">View site</a></li>
      </ul>

      <?php if ($admin !== null): ?>
        <span class="admin-nav__user" title="<?= e($admin->email) ?>">
          <?= e($admin->firstName()) ?>
        </span>
        <form method="post" action="<?= e(route_url('/admin/logout')) ?>" class="admin-nav__logout">
          <?= \App\Core\Csrf::field() ?>
          <button class="btn btn--ghost btn--sm" type="submit">Sign out</button>
        </form>
      <?php endif; ?>
    </nav>

  </div>
</header>
