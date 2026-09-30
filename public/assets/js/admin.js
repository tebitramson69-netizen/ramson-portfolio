/**
 * Admin-only behaviour.
 *
 * Loaded by the admin layout only, so the public site ships none of it.
 *
 * Everything here is a courtesy. The file size, the dimensions and the type
 * are all re-checked on the server by ImageValidator, which is the only place
 * a check counts: this code runs in the visitor's browser and can be edited,
 * disabled or bypassed with a direct POST. Its job is to make a bad crop
 * visible BEFORE the upload, which no server-side check can do.
 */
(function () {
  'use strict';

  /* ---------------------------------------------------------- confirmations */

  document.querySelectorAll('form[data-confirm]').forEach(function (form) {
    form.addEventListener('submit', function (event) {
      if (!window.confirm(form.getAttribute('data-confirm'))) {
        event.preventDefault();
      }
    });
  });

  /* --------------------------------------------------------- photo preview */

  var input = document.querySelector('[data-photo-input]');
  var panel = document.querySelector('[data-photo-preview]');

  if (!input || !panel) {
    return;
  }

  var frames = panel.querySelectorAll('[data-photo-preview-img]');
  var meta = panel.querySelector('[data-photo-preview-meta]');
  var submit = document.querySelector('[data-photo-submit]');

  var maxBytes = parseInt(input.getAttribute('data-max-bytes'), 10) || 0;
  var minSide = parseInt(input.getAttribute('data-min-side'), 10) || 0;

  // Held so it can be released: an object URL keeps the whole file alive in
  // memory until it is revoked, and a photographer picking six candidates in
  // a row would otherwise pin all six.
  var objectUrl = null;

  function release() {
    if (objectUrl !== null) {
      URL.revokeObjectURL(objectUrl);
      objectUrl = null;
    }
  }

  function block(reason) {
    if (submit) {
      submit.disabled = true;
    }
    meta.textContent = reason;
    meta.classList.add('is-problem');
  }

  function allow(text) {
    if (submit) {
      submit.disabled = false;
    }
    meta.textContent = text;
    meta.classList.remove('is-problem');
  }

  function formatBytes(bytes) {
    return bytes >= 1048576
      ? (bytes / 1048576).toFixed(1) + ' MB'
      : Math.max(1, Math.round(bytes / 1024)) + ' KB';
  }

  input.addEventListener('change', function () {
    release();

    var file = input.files && input.files[0];

    if (!file) {
      panel.hidden = true;
      allow('');
      return;
    }

    objectUrl = URL.createObjectURL(file);
    panel.hidden = false;

    frames.forEach(function (img) {
      img.src = objectUrl;
    });

    if (maxBytes > 0 && file.size > maxBytes) {
      block('This file is ' + formatBytes(file.size) + '. The limit is ' +
            formatBytes(maxBytes) + ', so the server would reject it.');
      return;
    }

    // Dimensions are only known once the browser has decoded the header, so
    // the verdict has to wait for the load event rather than being returned
    // from this handler.
    var probe = new Image();

    probe.onload = function () {
      var shortest = Math.min(probe.naturalWidth, probe.naturalHeight);
      var size = probe.naturalWidth + ' × ' + probe.naturalHeight + ' px, ' +
                 formatBytes(file.size);

      if (minSide > 0 && shortest < minSide) {
        block(size + ' — too small. At least ' + minSide +
              ' px is needed on the short side, or the hero would be upscaled and soft.');
        return;
      }

      if (probe.naturalWidth > probe.naturalHeight) {
        allow(size + ' — landscape. It will still work, but the 4:5 hero frame ' +
              'will crop the sides heavily.');
        return;
      }

      allow(size + ' — ready to upload.');
    };

    probe.onerror = function () {
      block('The browser could not read this file as an image.');
    };

    probe.src = objectUrl;
  });

  // Releasing on submit would revoke the URL while the frames still reference
  // it, so the release happens when the page goes away instead.
  window.addEventListener('pagehide', release);
})();
