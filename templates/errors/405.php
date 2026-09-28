<?php
/** 405 — the path exists but not for this HTTP method. */
?>
<section class="section error-page">
  <div class="container container--sm">
    <p class="section-index">Error 405</p>
    <h1 class="t-display-2 u-mt-4">That request is not allowed here</h1>
    <p class="t-lead u-mt-5">
      This address exists, but not for the method used to request it.
    </p>
    <div class="btn-group u-mt-7">
      <a class="btn btn--primary btn--lg" href="<?= e(route_url('/')) ?>">
        Back to the portfolio
        <span class="btn__arrow" aria-hidden="true">&rarr;</span>
      </a>
    </div>
  </div>
</section>
