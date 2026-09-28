<?php
/**
 * 500 — production error page.
 *
 * Shows a reference the visitor can quote and NOTHING else. No stack trace,
 * no SQL, no file paths. The detail is in storage/logs/app.log against the
 * same reference.
 *
 * @var string $reference
 */
?>
<section class="section error-page">
  <div class="container container--sm">

    <p class="section-index">Error 500</p>
    <h1 class="t-display-2 u-mt-4">Something went wrong</h1>

    <p class="t-lead u-mt-5">
      This one is on the server, not on you. The error has been logged and
      will be looked at.
    </p>

    <?php if (!empty($reference)): ?>
      <p class="t-caption u-mt-5">
        Reference <span class="tag"><?= e($reference) ?></span>
      </p>
    <?php endif; ?>

    <div class="btn-group u-mt-7">
      <a class="btn btn--primary btn--lg" href="<?= e(route_url('/')) ?>">
        Back to the portfolio
        <span class="btn__arrow" aria-hidden="true">&rarr;</span>
      </a>
    </div>

  </div>
</section>
