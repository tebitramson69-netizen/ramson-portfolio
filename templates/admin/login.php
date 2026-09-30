<?php
/**
 * @var string $csrf
 * @var array{type:string,message:string}|null $flash
 * @var string $siteName
 * @var \App\Domain\Profile\Profile|null $profile
 */
?>
<div class="auth-card">

  <p class="t-eyebrow">Administration</p>
  <h1 class="t-display-3 u-mt-3">Sign in</h1>
  <p class="t-body-sm t-muted u-mt-3">
    <?= e($profile?->fullName ?? '') ?> — portfolio content management.
  </p>

  <?php if ($flash !== null): ?>
    <div class="admin-flash admin-flash--<?= e($flash['type']) ?> u-mt-5"
         role="<?= $flash['type'] === 'error' ? 'alert' : 'status' ?>">
      <?= e($flash['message']) ?>
    </div>
  <?php endif; ?>

  <form method="post" action="<?= e(route_url('/admin/login')) ?>" class="u-mt-6" novalidate>
    <input type="hidden" name="_token" value="<?= e($csrf) ?>">

    <div class="field">
      <label class="field__label" for="email">Email</label>
      <input class="field__input" type="email" id="email" name="email"
             autocomplete="username" required autofocus
             value="<?= e($email ?? '') ?>">
    </div>

    <div class="field">
      <label class="field__label" for="password">Password</label>
      <input class="field__input" type="password" id="password" name="password"
             autocomplete="current-password" required>
    </div>

    <button class="btn btn--primary btn--lg auth-card__submit u-mt-5" type="submit">
      Sign in
      <span class="btn__arrow" aria-hidden="true">&rarr;</span>
    </button>
  </form>

  <p class="t-caption u-mt-6">
    <a class="t-link" href="<?= e(route_url('/')) ?>">Back to the site</a>
  </p>

</div>
