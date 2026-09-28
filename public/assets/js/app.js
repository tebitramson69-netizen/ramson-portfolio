/* ==========================================================================
   Ramson Titih — Portfolio
   app.js — progressive enhancement only.

   Every feature here is an ENHANCEMENT. With JavaScript disabled the page
   renders completely, all content is visible, and every link works.

   Scope (deliberately small — nothing else earns its bytes):
     1. Sticky header condense
     2. Mobile navigation (focus trap, Escape, scroll lock)
     3. Scroll reveals (fire once, then unobserve)
     4. Scroll-spy for the active nav item
     5. Current year in the footer
   ========================================================================== */

(function () {
  "use strict";

  var reduceMotion = window.matchMedia("(prefers-reduced-motion: reduce)").matches;

  /* ------------------------------------------------------------------------
     1. Sticky header condense
     ---------------------------------------------------------------------- */

  var header = document.querySelector("[data-header]");

  if (header) {
    var condensed = false;

    var onScroll = function () {
      var shouldCondense = window.scrollY > 80;
      if (shouldCondense !== condensed) {
        condensed = shouldCondense;
        header.classList.toggle("is-condensed", condensed);
      }
    };

    // passive: this listener never calls preventDefault, so the browser can
    // keep scrolling on the compositor thread.
    window.addEventListener("scroll", onScroll, { passive: true });
    onScroll();
  }

  /* ------------------------------------------------------------------------
     2. Mobile navigation
     ---------------------------------------------------------------------- */

  var navToggle = document.querySelector("[data-nav-toggle]");
  var navPanel  = document.querySelector("[data-nav-panel]");

  if (navToggle && navPanel) {
    var lastFocused = null;

    var focusableIn = function (root) {
      return Array.prototype.slice.call(
        root.querySelectorAll('a[href], button:not([disabled]), [tabindex]:not([tabindex="-1"])')
      ).filter(function (el) { return el.offsetParent !== null; });
    };

    var openNav = function () {
      lastFocused = document.activeElement;
      navPanel.classList.add("is-open");
      navToggle.setAttribute("aria-expanded", "true");
      document.body.style.overflow = "hidden";

      var focusable = focusableIn(navPanel);
      if (focusable.length) { focusable[0].focus(); }
    };

    var closeNav = function () {
      navPanel.classList.remove("is-open");
      navToggle.setAttribute("aria-expanded", "false");
      document.body.style.overflow = "";
      // Focus returns to the trigger, never to the top of the document.
      if (lastFocused) { lastFocused.focus(); }
    };

    var isOpen = function () {
      return navToggle.getAttribute("aria-expanded") === "true";
    };

    navToggle.addEventListener("click", function () {
      isOpen() ? closeNav() : openNav();
    });

    // Close after following an in-page link.
    navPanel.addEventListener("click", function (event) {
      if (event.target.closest("a")) { closeNav(); }
    });

    document.addEventListener("keydown", function (event) {
      if (!isOpen()) { return; }

      if (event.key === "Escape") {
        event.preventDefault();
        closeNav();
        return;
      }

      // Focus trap — Tab cycles within the panel while it is open.
      if (event.key === "Tab") {
        var focusable = focusableIn(navPanel);
        if (!focusable.length) { return; }

        var first = focusable[0];
        var last  = focusable[focusable.length - 1];

        if (event.shiftKey && document.activeElement === first) {
          event.preventDefault();
          last.focus();
        } else if (!event.shiftKey && document.activeElement === last) {
          event.preventDefault();
          first.focus();
        }
      }
    });

    // Reset state if the viewport grows past the mobile breakpoint while open.
    window.matchMedia("(min-width: 768px)").addEventListener("change", function (e) {
      if (e.matches && isOpen()) { closeNav(); }
    });
  }

  /* ------------------------------------------------------------------------
     3. Scroll reveals

     Each element animates ONCE and is then unobserved. Re-animating on every
     scroll-past is nauseating and reads as amateur.
     ---------------------------------------------------------------------- */

  var revealTargets = document.querySelectorAll("[data-reveal]");

  if (revealTargets.length) {
    if (reduceMotion || !("IntersectionObserver" in window)) {
      // No observer, or motion is unwelcome: show everything immediately.
      Array.prototype.forEach.call(revealTargets, function (el) {
        el.classList.add("is-visible");
      });
    } else {
      var revealObserver = new IntersectionObserver(function (entries, observer) {
        entries.forEach(function (entry) {
          if (entry.isIntersecting) {
            entry.target.classList.add("is-visible");
            observer.unobserve(entry.target);
          }
        });
      }, {
        // Trigger slightly before the element reaches the viewport edge.
        rootMargin: "0px 0px -10% 0px",
        threshold: 0.05
      });

      Array.prototype.forEach.call(revealTargets, function (el) {
        revealObserver.observe(el);
      });
    }
  }

  /* ------------------------------------------------------------------------
     4. Scroll-spy — marks the nav item for the section currently in view
     ---------------------------------------------------------------------- */

  var spyLinks = document.querySelectorAll("[data-spy-link]");

  if (spyLinks.length && "IntersectionObserver" in window) {
    var linkFor = {};
    var sections = [];

    Array.prototype.forEach.call(spyLinks, function (link) {
      var id = link.getAttribute("href");
      if (!id || id.charAt(0) !== "#") { return; }

      var section = document.querySelector(id);
      if (!section) { return; }

      linkFor[id.slice(1)] = link;
      sections.push(section);
    });

    var setActive = function (id) {
      Array.prototype.forEach.call(spyLinks, function (link) {
        link.removeAttribute("aria-current");
      });
      if (linkFor[id]) {
        linkFor[id].setAttribute("aria-current", "true");
      }
    };

    var spyObserver = new IntersectionObserver(function (entries) {
      // Choose the visible section nearest the top of the viewport.
      var visible = entries.filter(function (e) { return e.isIntersecting; });
      if (!visible.length) { return; }

      visible.sort(function (a, b) {
        return a.boundingClientRect.top - b.boundingClientRect.top;
      });
      setActive(visible[0].target.id);
    }, {
      // A band across the upper-middle of the viewport.
      rootMargin: "-20% 0px -70% 0px",
      threshold: 0
    });

    sections.forEach(function (section) { spyObserver.observe(section); });
  }

  /* ------------------------------------------------------------------------
     5. Footer year
     ---------------------------------------------------------------------- */

  var yearEl = document.querySelector("[data-year]");
  if (yearEl) {
    yearEl.textContent = String(new Date().getFullYear());
  }

}());
