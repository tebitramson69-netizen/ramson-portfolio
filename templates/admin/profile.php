<?php
/**
 * Profile editor.
 *
 * THREE independent forms, not one. The photograph, its description and the
 * text fields each post to their own route, because a rejected image must
 * never discard edited prose, and fixing a typo must never re-encode four
 * image variants.
 *
 * @var \App\Domain\Profile\Profile|null $profile
 * @var string $csrf
 * @var array<string,string> $limits
 */
$photo = $profile?->photo;

/** The crops worth inspecting: the tall hero and the square avatar. A bad
 *  crop is invisible in a file picker and obvious here. */
$crops = [
    'hero'  => ['label' => 'Hero — 800 × 1000', 'note' => 'Home page and about section'],
    'thumb' => ['label' => 'Avatar — 160 × 160', 'note' => 'Square crop, anchored high so a face is not cut off'],
    'og'    => ['label' => 'Social preview — 1200 × 630', 'note' => 'WhatsApp, LinkedIn and Twitter cards'],
];
?>

<header class="admin-page-head">
  <p class="t-eyebrow">Profile</p>
  <h1 class="t-display-3 u-mt-3">Identity &amp; photograph</h1>
  <p class="t-body-sm t-muted u-mt-4 admin-section__intro">
    Everything here appears on the public site the moment it is saved. No
    filename is stored in any template: replacing the photograph below replaces
    it in the hero, the about section and every social preview at once.
  </p>
</header>

<!-- ---------------------------------------------------------- photograph -->
<section class="admin-section" aria-labelledby="photo-title">
  <h2 class="admin-section__title" id="photo-title">
    Photograph
    <?php if ($photo !== null): ?>
      <span class="admin-badge admin-badge--ok">set</span>
    <?php else: ?>
      <span class="admin-badge admin-badge--warn">monogram fallback</span>
    <?php endif; ?>
  </h2>

  <div class="media-panel">

    <!-- current state ------------------------------------------------- -->
    <div class="media-panel__current">
      <?php if ($photo !== null): ?>
        <div class="media-crops">
          <?php foreach ($crops as $name => $meta): ?>
            <?php
            $variant = $photo->variant($name, 'jpeg') ?? $photo->variant($name, 'webp');
            if ($variant === null) {
                continue;
            }
            $url = $photo->url($name, $variant->format);
            ?>
            <figure class="media-crop media-crop--<?= e($name) ?>">
              <img class="media-crop__img"
                   src="<?= e(route_url((string) $url)) ?>"
                   alt="<?= e($photo->alt('Current photograph')) ?>"
                   width="<?= e((string) $variant->width) ?>"
                   height="<?= e((string) $variant->height) ?>"
                   loading="lazy" decoding="async">
              <figcaption class="media-crop__caption">
                <b><?= e($meta['label']) ?></b>
                <span><?= e($meta['note']) ?></span>
              </figcaption>
            </figure>
          <?php endforeach; ?>
        </div>

        <dl class="media-facts">
          <div><dt>Source</dt><dd><?= e($photo->width . ' × ' . $photo->height . ' px') ?></dd></div>
          <div><dt>Type</dt><dd><?= e($photo->mimeType) ?></dd></div>
          <div><dt>Variants</dt><dd><?= e((string) count($photo->allVariants())) ?> files</dd></div>
          <div><dt>Cache version</dt><dd><?= e((string) $photo->updatedAt) ?></dd></div>
        </dl>

        <!-- alt text, on its own route ------------------------------- -->
        <form method="post" action="<?= e(route_url('/admin/profile/photo/alt')) ?>" class="u-mt-6">
          <input type="hidden" name="_token" value="<?= e($csrf) ?>">
          <div class="field">
            <label class="field__label" for="alt_text_existing">Image description</label>
            <input class="field__input" type="text" id="alt_text_existing" name="alt_text"
                   maxlength="255" value="<?= e($photo->altText ?? '') ?>">
            <p class="field__help">
              Read aloud by screen readers and shown if the image fails to load.
              Describe the person, not the file &mdash; &ldquo;photograph of&rdquo; is already implied.
            </p>
          </div>
          <button class="btn btn--secondary btn--sm u-mt-4" type="submit">Save description</button>
        </form>

        <!-- removal --------------------------------------------------- -->
        <form method="post" action="<?= e(route_url('/admin/profile/photo/remove')) ?>"
              class="u-mt-5" data-confirm="Remove the photograph? The monogram fallback will show until a new one is uploaded.">
          <input type="hidden" name="_token" value="<?= e($csrf) ?>">
          <button class="btn btn--ghost btn--sm" type="submit">Remove photograph</button>
        </form>

      <?php else: ?>
        <div class="media-empty">
          <span class="media-empty__mark" aria-hidden="true"><?= e($profile?->monogram() ?? 'RT') ?></span>
          <p class="t-body-sm t-muted">
            No photograph yet. The designed monogram is showing on the public
            site &mdash; a deliberate fallback state, never a broken image.
          </p>
        </div>
      <?php endif; ?>
    </div>

    <!-- upload / replace ---------------------------------------------- -->
    <div class="media-panel__upload">
      <form method="post" action="<?= e(route_url('/admin/profile/photo')) ?>"
            enctype="multipart/form-data" data-photo-form>
        <input type="hidden" name="_token" value="<?= e($csrf) ?>">

        <div class="field">
          <label class="field__label" for="photo">
            <?= $photo !== null ? 'Replace photograph' : 'Upload photograph' ?>
          </label>
          <input class="field__input field__input--file" type="file" id="photo" name="photo"
                 accept="image/jpeg,image/png,image/webp" required
                 data-photo-input
                 data-max-bytes="<?= e($limits['max_bytes']) ?>"
                 data-min-side="<?= e($limits['min_side']) ?>">
          <p class="field__help">
            JPEG, PNG or WebP. At least <?= e($limits['min_side']) ?> px on the
            short side, up to <?= e($limits['max']) ?>. A portrait orientation
            works best &mdash; the hero frame is 4:5.
            <?php if ($limits['mismatch'] !== ''): ?>
              <br><strong>This server caps uploads at <?= e($limits['max']) ?></strong>
              (php.ini: upload_max_filesize <?= e($limits['php_limit']) ?>,
              post_max_size <?= e($limits['post_limit']) ?>), below the
              application&rsquo;s configured <?= e($limits['configured']) ?>.
            <?php endif; ?>
          </p>
        </div>

        <!-- Client-side preview. It is a courtesy, not a control: every one
             of these checks is repeated on the server, which is the only
             place a check counts. -->
        <div class="media-preview" data-photo-preview hidden>
          <p class="t-caption media-preview__title">Before you upload</p>
          <div class="media-preview__frames">
            <div class="media-preview__frame media-preview__frame--hero">
              <img alt="" data-photo-preview-img>
              <span class="media-preview__label">Hero 4:5</span>
            </div>
            <div class="media-preview__frame media-preview__frame--square">
              <img alt="" data-photo-preview-img>
              <span class="media-preview__label">Avatar 1:1</span>
            </div>
          </div>
          <p class="t-caption media-preview__meta" data-photo-preview-meta></p>
        </div>

        <div class="field">
          <label class="field__label" for="alt_text">Image description</label>
          <input class="field__input" type="text" id="alt_text" name="alt_text" maxlength="255"
                 placeholder="<?= e('Portrait of ' . ($profile?->fullName ?? '')) ?>">
          <p class="field__help">Left blank, a description is generated from the full name.</p>
        </div>

        <button class="btn btn--primary u-mt-5" type="submit" data-photo-submit>
          <?= $photo !== null ? 'Upload replacement' : 'Upload photograph' ?>
          <span class="btn__arrow" aria-hidden="true">&rarr;</span>
        </button>

        <p class="t-caption u-mt-4 t-muted">
          The uploaded bytes are never served. Every size is decoded and
          re-encoded, which strips EXIF &mdash; including the GPS coordinates a
          phone writes into a photograph.
        </p>
      </form>
    </div>

  </div>
</section>

<!-- ------------------------------------------------------------- identity -->
<form method="post" action="<?= e(route_url('/admin/profile')) ?>">
  <input type="hidden" name="_token" value="<?= e($csrf) ?>">

  <section class="admin-section" aria-labelledby="identity-title">
    <h2 class="admin-section__title" id="identity-title">Identity</h2>

    <div class="admin-grid">
      <div class="field">
        <label class="field__label" for="full_name">Full name</label>
        <input class="field__input" type="text" id="full_name" name="full_name" maxlength="120"
               required value="<?= e($profile?->fullName ?? '') ?>">
      </div>

      <div class="field">
        <label class="field__label" for="monogram">Monogram <span class="field__required">fallback mark</span></label>
        <input class="field__input" type="text" id="monogram" name="monogram" maxlength="4"
               value="<?= e($profile?->monogram ?? '') ?>">
        <p class="field__help">Shown when there is no photograph. A brand decision, not computed initials.</p>
      </div>
    </div>

    <div class="field">
      <label class="field__label" for="professional_title">Professional title</label>
      <input class="field__input" type="text" id="professional_title" name="professional_title"
             maxlength="160" value="<?= e($profile?->professionalTitle ?? '') ?>">
    </div>

    <div class="field">
      <label class="field__label" for="value_proposition">Value proposition</label>
      <textarea class="field__textarea" id="value_proposition" name="value_proposition"
                maxlength="400" rows="3"><?= e($profile?->valueProposition ?? '') ?></textarea>
      <p class="field__help">The dominant sentence on the home page. Say what you build and for whom.</p>
    </div>

    <div class="field">
      <label class="field__label" for="technology_line">Technology line</label>
      <input class="field__input" type="text" id="technology_line" name="technology_line"
             maxlength="255" value="<?= e($profile?->technologyLine ?? '') ?>">
    </div>
  </section>

  <section class="admin-section" aria-labelledby="about-title">
    <h2 class="admin-section__title" id="about-title">About</h2>

    <div class="field">
      <label class="field__label" for="short_intro">Short introduction</label>
      <textarea class="field__textarea" id="short_intro" name="short_intro"
                maxlength="500" rows="3"><?= e($profile?->shortIntro ?? '') ?></textarea>
      <p class="field__help">Left empty, the about section shows its pending state rather than filler.</p>
    </div>

    <div class="field">
      <label class="field__label" for="biography">Biography</label>
      <textarea class="field__textarea" id="biography" name="biography"
                maxlength="5000" rows="10"><?= e($profile?->biography ?? '') ?></textarea>
      <p class="field__help">Blank lines separate paragraphs. No HTML &mdash; it would be escaped, not rendered.</p>
    </div>

    <div class="admin-grid">
      <div class="field">
        <label class="field__label" for="location">Location</label>
        <input class="field__input" type="text" id="location" name="location" maxlength="120"
               value="<?= e($profile?->location ?? '') ?>">
      </div>
      <div class="field">
        <label class="field__label" for="education">Qualification</label>
        <input class="field__input" type="text" id="education" name="education" maxlength="191"
               value="<?= e($profile?->education ?? '') ?>">
      </div>
    </div>

    <div class="field">
      <label class="field__label" for="institution">Institution</label>
      <input class="field__input" type="text" id="institution" name="institution" maxlength="191"
             value="<?= e($profile?->institution ?? '') ?>">
    </div>
  </section>

  <section class="admin-section" aria-labelledby="availability-title">
    <h2 class="admin-section__title" id="availability-title">Availability</h2>

    <div class="admin-grid">
      <div class="field">
        <label class="field__label" for="availability_status">Status</label>
        <?php $status = $profile?->availabilityStatus ?? 'selective'; ?>
        <select class="field__input" id="availability_status" name="availability_status">
          <?php foreach ([
              'available'   => 'Available',
              'selective'   => 'Open to select projects',
              'unavailable' => 'Not currently available',
          ] as $value => $label): ?>
            <option value="<?= e($value) ?>"<?= $status === $value ? ' selected' : '' ?>><?= e($label) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="field">
        <label class="field__label" for="availability_note">Note</label>
        <input class="field__input" type="text" id="availability_note" name="availability_note"
               maxlength="160" value="<?= e($profile?->availabilityNote ?? '') ?>">
        <p class="field__help">The exact wording shown beside the status dot.</p>
      </div>
    </div>
  </section>

  <section class="admin-section" aria-labelledby="contact-title">
    <h2 class="admin-section__title" id="contact-title">Contact</h2>
    <p class="t-body-sm t-muted admin-section__intro">
      Each channel appears on the site only when it has a value, so a link can
      never point at nothing.
    </p>

    <div class="admin-grid">
      <div class="field">
        <label class="field__label" for="email">Email</label>
        <input class="field__input" type="email" id="email" name="email" maxlength="191"
               value="<?= e($profile?->email ?? '') ?>">
      </div>
      <div class="field">
        <label class="field__label" for="whatsapp">WhatsApp</label>
        <input class="field__input" type="text" id="whatsapp" name="whatsapp" maxlength="40"
               value="<?= e($profile?->whatsapp ?? '') ?>">
        <p class="field__help">International format, e.g. +237&hellip;</p>
      </div>
      <div class="field">
        <label class="field__label" for="github_url">GitHub</label>
        <input class="field__input" type="url" id="github_url" name="github_url" maxlength="255"
               value="<?= e($profile?->githubUrl ?? '') ?>">
      </div>
      <div class="field">
        <label class="field__label" for="linkedin_url">LinkedIn</label>
        <input class="field__input" type="url" id="linkedin_url" name="linkedin_url" maxlength="255"
               value="<?= e($profile?->linkedinUrl ?? '') ?>">
      </div>
    </div>
  </section>

  <div class="admin-actions">
    <button class="btn btn--primary btn--lg" type="submit">
      Save profile
      <span class="btn__arrow" aria-hidden="true">&rarr;</span>
    </button>
    <a class="btn btn--ghost" href="<?= e(route_url('/')) ?>">View the site</a>
  </div>
</form>
