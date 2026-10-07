<?php
/**
 * Site settings.
 *
 * One form, saved whole. There is no "add setting" button, and that is
 * deliberate: a key is read by name in a template, so inventing one here would
 * write a row nothing reads while the real setting silently kept its default.
 * New keys arrive in a seed, next to the template that reads them.
 *
 * @var list<array{key:string,label:string,type:string,value:?string}> $settings
 * @var string $csrf
 */
?>

<header class="admin-page-head">
  <p class="t-eyebrow">Settings</p>
  <h1 class="t-display-3 u-mt-3">Site settings</h1>
  <p class="t-body-sm t-muted u-mt-4 admin-section__intro">
    The handful of values the templates read by name. The site title and meta
    description are what search results and shared links show, so they are
    worth reading aloud before saving.
  </p>
</header>

<section class="admin-section" aria-labelledby="settings-title">
  <h2 class="admin-section__title" id="settings-title">
    Values
    <span class="admin-badge admin-badge--ok"><?= e((string) count($settings)) ?></span>
  </h2>

  <?php if ($settings === []): ?>
    <p class="t-body-sm t-muted u-mt-5">
      No settings rows exist. Run the seeds &mdash; these keys are created
      there, not here.
    </p>
  <?php else: ?>
    <form method="post" action="<?= e(route_url('/admin/settings')) ?>">
      <input type="hidden" name="_token" value="<?= e($csrf) ?>">

      <?php foreach ($settings as $setting): ?>
        <?php $id = 'setting-' . $setting['key']; ?>
        <div class="field u-mt-6">
          <label class="field__label" for="<?= e($id) ?>">
            <?= e($setting['label'] !== '' ? $setting['label'] : $setting['key']) ?>
          </label>

          <?php if ($setting['type'] === 'boolean'): ?>
            <?php /* An unchecked box posts nothing, which the writer reads as
                     false — see SettingsWriter::updateMany(). */ ?>
            <input type="checkbox" id="<?= e($id) ?>"
                   name="settings[<?= e($setting['key']) ?>]" value="1"
                   <?= filter_var($setting['value'], FILTER_VALIDATE_BOOL) ? ' checked' : '' ?>>

          <?php elseif ($setting['type'] === 'text'): ?>
            <textarea class="field__textarea" rows="3" maxlength="5000"
                      id="<?= e($id) ?>"
                      name="settings[<?= e($setting['key']) ?>]"><?= e((string) $setting['value']) ?></textarea>

          <?php elseif ($setting['type'] === 'integer'): ?>
            <input class="field__input" type="number" id="<?= e($id) ?>"
                   name="settings[<?= e($setting['key']) ?>]"
                   value="<?= e((string) $setting['value']) ?>">

          <?php else: ?>
            <input class="field__input" type="text" maxlength="500" id="<?= e($id) ?>"
                   name="settings[<?= e($setting['key']) ?>]"
                   value="<?= e((string) $setting['value']) ?>">
          <?php endif; ?>

          <p class="field__help"><code><?= e($setting['key']) ?></code> &middot; <?= e($setting['type']) ?></p>
        </div>
      <?php endforeach; ?>

      <button class="btn btn--primary u-mt-5" type="submit">
        Save settings <span class="btn__arrow" aria-hidden="true">&rarr;</span>
      </button>
    </form>
  <?php endif; ?>
</section>
