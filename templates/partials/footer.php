<?php
/** @var \App\Domain\Profile\Profile|null $profile */
/** @var bool $isHome */
$root   = $isHome ? '' : route_url('/');
$name   = $profile?->fullName ?? 'Tebit Ramson Titih';
$intro  = $profile?->shortIntro
       ?? 'Software engineer and full-stack developer building practical web systems. Cameroon.';
$github = $profile?->githubUrl;
$linked = $profile?->linkedinUrl;
?>
<footer class="footer">
  <div class="container">

    <div class="footer__grid">

      <div class="footer__brand">
        <a class="brand" href="<?= e($isHome ? '#top' : route_url('/')) ?>">
          <?= e($name) ?><span class="brand__mark" aria-hidden="true">.</span>
        </a>
        <p class="footer__tagline"><?= e($intro) ?></p>
      </div>

      <div>
        <h2 class="footer__heading">Navigate</h2>
        <ul class="footer__list">
          <li><a href="<?= e($root) ?>#work">Work</a></li>
          <li><a href="<?= e($root) ?>#about">About</a></li>
          <li><a href="<?= e($root) ?>#services">Services</a></li>
          <li><a href="<?= e($root) ?>#contact">Contact</a></li>
        </ul>
      </div>

      <div>
        <h2 class="footer__heading">Elsewhere</h2>
        <ul class="footer__list">
          <?php if ($github !== null): ?>
            <li><a href="<?= e_url($github) ?>" rel="me noopener">GitHub</a></li>
          <?php endif; ?>
          <?php if ($linked !== null): ?>
            <li><a href="<?= e_url($linked) ?>" rel="me noopener">LinkedIn</a></li>
          <?php else: ?>
            <li><span class="t-muted">LinkedIn — soon</span></li>
          <?php endif; ?>
        </ul>
      </div>

    </div>

    <div class="footer__bottom">
      <p>&copy; <span data-year><?= e(date('Y')) ?></span> <?= e($name) ?>. All rights reserved.</p>
      <p>Built with PHP, MySQL and hand-written CSS.</p>
    </div>

  </div>
</footer>
