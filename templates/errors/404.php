<?php
/**
 * 404 — styled to the approved design language.
 * Returns a real 404 status (see Kernel::notFound); a 200 with "not found"
 * text is a common bug that quietly poisons indexing.
 *
 * @var \App\Domain\Profile\Profile|null $profile
 */
?>
<section class="section error-page">
  <div class="container container--sm">

    <p class="section-index">Error 404</p>
    <h1 class="t-display-2 u-mt-4">This page does not exist</h1>

    <p class="t-lead u-mt-5">
      The address may be mistyped, or the page may have moved. Everything on
      this site is reachable from the work and contact sections below.
    </p>

    <div class="btn-group u-mt-7">
      <a class="btn btn--primary btn--lg" href="<?= e(route_url('/')) ?>">
        Back to the portfolio
        <span class="btn__arrow" aria-hidden="true">&rarr;</span>
      </a>
      <a class="btn btn--secondary btn--lg" href="<?= e(route_url('/')) ?>#work">View selected work</a>
    </div>

    <ul class="meta-list u-mt-7">
      <li><a class="t-link" href="<?= e(route_url('/')) ?>#about">About</a></li>
      <li><a class="t-link" href="<?= e(route_url('/')) ?>#contact">Contact</a></li>
      <?php if (($profile?->githubUrl ?? null) !== null): ?>
        <li><a class="t-link" href="<?= e_url($profile->githubUrl) ?>" rel="me noopener">GitHub</a></li>
      <?php endif; ?>
    </ul>

  </div>
</section>
