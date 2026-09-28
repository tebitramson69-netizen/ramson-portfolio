<?php
/** @var \App\Domain\Profile\Profile|null $profile */
/** @var bool $isHome */
$home = $isHome ? '#top' : route_url('/');
$name = $profile?->fullName ?? 'Tebit Ramson Titih';
?>
<header class="header" data-header>
  <div class="container header__inner">

    <a class="brand" href="<?= e($home) ?>">
      <?= e($name) ?><span class="brand__mark" aria-hidden="true">.</span>
    </a>

    <nav class="nav" aria-label="Primary">
      <ul class="nav__list">
        <li><a class="nav__link" href="<?= e($isHome ? '#work' : route_url('/') . '#work') ?>"<?= $isHome ? ' data-spy-link' : '' ?>>Work</a></li>
        <li><a class="nav__link" href="<?= e($isHome ? '#about' : route_url('/') . '#about') ?>"<?= $isHome ? ' data-spy-link' : '' ?>>About</a></li>
        <li><a class="nav__link" href="<?= e($isHome ? '#services' : route_url('/') . '#services') ?>"<?= $isHome ? ' data-spy-link' : '' ?>>Services</a></li>
        <li><a class="nav__link" href="<?= e($isHome ? '#contact' : route_url('/') . '#contact') ?>"<?= $isHome ? ' data-spy-link' : '' ?>>Contact</a></li>
      </ul>

      <a class="btn btn--secondary btn--sm" href="<?= e($isHome ? '#contact' : route_url('/') . '#contact') ?>">Get in touch</a>

      <button class="nav-toggle" type="button"
              aria-expanded="false" aria-controls="nav-panel"
              data-nav-toggle>
        <span class="u-visually-hidden">Menu</span>
        <span class="nav-toggle__bars" aria-hidden="true"></span>
      </button>
    </nav>

  </div>
</header>
