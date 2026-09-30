# Development Roadmap

Ten phases. Each is independently valuable and ends with something demonstrably working — no
phase leaves the project in a broken state.

**Current phase: 5 — complete. Awaiting confirmation to begin Phase 6.**

| Phase | Name | Status |
|---|---|---|
| 0 | Documentation & Foundation | ✅ Complete |
| 1 | Design Foundation | ✅ Complete |
| 2 | Application Skeleton | ✅ Complete |
| 3 | Database & Read Path | ✅ Complete |
| 4 | Authentication & Admin Shell | ✅ Complete |
| 5 | Profile Management & Media ⭐ | ✅ Complete |
| 6 | Projects & Case Studies ⭐ | ⬜ Not started |
| 7 | Remaining Content Management | ⬜ Not started |
| 8 | Contact System | ⬜ Not started |
| 9 | SEO / Performance / Accessibility | ⬜ Not started |
| 10 | Deployment & Launch | ⬜ Not started |

**Phases 1–6 are the launchable product.** 7–10 are hardening.

---

## Phase 0 — Documentation & Foundation ✅

Repository created, README, `.gitignore`, `.editorconfig`, and the documentation set.

**Deliverable:** an approved plan in its official home. **Done.**

---

## Phase 1 — Design Foundation ✅

**No backend. Static HTML and CSS only.**

1. `public/assets/css/main.css` — every token from `01-DESIGN-SYSTEM.md`
2. A static style-guide page — all colours, the full type scale, spacing, every button state, cards, forms, tags, empty and loading states
3. A static, fully responsive homepage — hero, tech strip, selected work (placeholder content **clearly marked as placeholder**), about, skills, process, services, experience, contact, footer
4. A static case-study page

**Why first:** the design system is the hardest thing to retrofit. Getting type, spacing and
the hero right in static HTML — where iteration is cheap — before any PHP exists is the
difference between a premium result and a compromised one.

**Questions 1–4 answered** — Instrument Serif, monogram fallback until a photograph
is supplied, "Open to select projects & opportunities", and `Work · About · Services · Contact`.

**Delivered:**

| File | Lines | Contents |
|---|---|---|
| `public/assets/css/main.css` | ~1,500 | Every token, plus base, components, sections and case-study styles |
| `public/assets/css/styleguide.css` | 88 | Style-guide specimens only — never shipped publicly |
| `public/assets/js/app.js` | 216 | Header condense, mobile nav, scroll reveals, scroll-spy, footer year |
| `public/index.html` | ~726 | Full homepage, all ten sections |
| `public/case-study.html` | ~352 | Rendo case study, showing the section model |
| `public/styleguide.html` | ~480 | Nine-section design-system reference |

**Verified in a real browser** (Chromium, five viewports from 320px to 1440px):
no horizontal overflow at any width · no JavaScript errors · single `<h1>` per page ·
no skipped heading levels · every form field labelled · all touch targets ≥ 44px ·
content fully visible with JavaScript disabled · content fully visible under
`prefers-reduced-motion` · mobile nav opens, traps focus, locks scroll and closes on Escape.

**Three defects found and fixed during verification:**
1. The mobile nav panel had no base `display: none`, so above 767px it rendered as an ordinary block painting its links over the top of every page.
2. Featured work blocks did not alternate — the even-item rule used `order: -1`, which kept the media first rather than moving it last.
3. On narrow viewports the portrait fallback caption overlapped the monogram; the fallback is now a flex column, which makes the overlap structurally impossible at any frame size.

**Known Phase 1 limitation:** fonts load from Google Fonts for the static prototype.
Phase 9 self-hosts them as subsetted WOFF2, which removes the third-party connection
and satisfies the CSP in PRD §Q.5.

**Refinement pass (post-review).** The hero was rebuilt to a stronger editorial
composition: the value proposition became the dominant statement, the name
became a letterspaced masthead, and the portrait became a full plate with an
offset hairline and its own caption, aligned to share a top edge with the
masthead. Four further defects were found and fixed while rendering it —
the offset frame and corner ticks were anchored to the figure rather than the
image, so they enclosed the caption; two framing devices on one plate read as
busy, so the ticks were removed; the value proposition sat below the fold on a
390px phone; and the wrapped hero micro-facts left a hairline divider dangling
at the end of a line.

**Deliverable:** a static site that already looks like the finished product. **Done.**

---

## Phase 2 — Application Skeleton ✅

Delivered: front controller · pattern router · config layer · lazy PDO · plain-PHP view
layer with explicit escaping · error handler · security headers with a strict CSP ·
`public/.htaccess` · `composer.json` · `config/config.example.php` · styled 404/405/500 ·
Phase 1 HTML converted into layouts, partials, components and pages.

Also in Phase 2, beyond the skeleton: the database foundation (`media`, `media_variants`,
`profile`, `settings`, `schema_migrations`), a forward-only migration runner with seeds, and
the profile read path wired end to end so the hero, about section, footer, metadata and
JSON-LD all come from the database.

**Full reasoning, with alternatives and trade-offs:** `05-ARCHITECTURE.md`.

**Deferred deliberately.** Projects and their case-study tables are Phase 3, not Phase 2:
creating five tables before their read path exists would be scaffolding rather than
architecture. `/about` and `/contact` stay as home-page sections until they have content of
their own, so the site does not ship two URLs for the same text.

**Deliverable:** the static site running through the real application architecture. **Done.**

---

## Phase 3 — Database & Read Path ✅

**Delivered.** Seven new tables — `skill_categories`, `skills`, `projects`,
`project_sections`, `project_features`, `project_technologies`, `project_images` — plus the
repository layer and the public read path. The home page's selected work, technology strip and
skills section, and the entire case-study page, all render from the database. The hard-coded
Rendo template is gone.

**Key decisions** (full reasoning in `05-ARCHITECTURE.md` §2.6b–2.6e): dense integer ordering
rather than LexoRank; a generated `alive` column so soft-deleted slugs are reusable; sections
stored one row per section; publication filtering in the repository via `findPublished*`
rather than a flag.

**Measured:** home page 5 queries, case study 7, 404 two — all inside the ≤8 budget. Query
count is constant as projects are added, so there is no N+1.

**Content integrity.** Rendo carries **no technology tags**, because its stack has never been
supplied. Both projects have no role, status, GitHub or live URL for the same reason — each
renders its designed pending state naming exactly what is missing.

**Deliverable:** a fully database-driven public site (edited via SQL for now). **Done.**

---

## Phase 4 — Authentication & Admin Shell ✅

**Delivered.** `bin/create-admin.php` (CLI only) · Argon2id hashing · login and logout ·
session binding · CSRF on every POST · dual-key throttling · a route guard enforced in the
kernel · admin layout and navigation · a dashboard with live counts, a content-completeness
checklist and an environment health panel.

**Key decisions** (reasoning in `05-ARCHITECTURE.md` §2.11b–2.11d): Argon2id rather than
`PASSWORD_DEFAULT`; throttling on account *and* IP; the guard enforced before the controller
is constructed; no registration or email-reset route at all.

**Verified:** 14 authentication assertions pass, covering the guard, CSRF rejection, session
invalidation on re-login, user-agent binding, POST-only logout, and lockout that the correct
password cannot bypass. Login timing is level at 262 ms for a wrong password and 263 ms for a
right one, so response time reveals nothing.

**Deliverable:** a secure, empty CMS he can log into. **Done.**

---

## Phase 5 — Profile Management & Media ✅

**Delivered.** `ImageValidator` (an eight-step pipeline) · `ImageProcessor` (EXIF correction,
cropping, encoding) · `MediaUploadService` (files, transaction, cleanup) · the profile editor
at `/admin/profile` · upload, preview, replace, alt-text and remove, each on its own route ·
the monogram fallback · `bin/verify-media.php`, a self-check that runs on Windows and Linux
alike.

**Why here and not later:** this is the requirement Ramson emphasised most, and `media` is the
abstraction every subsequent phase depends on. Building projects first would mean retrofitting
image handling into project management.

**Key decisions** (reasoning in `05-ARCHITECTURE.md` §2.13a–2.13f): the uploaded bytes are
never served, only a decode and re-encode · variants are rows, not conventions · files are
written before the transaction commits and old files deleted only after · crops are anchored
high and capped, never centred · `post_max_size` is checked before CSRF · the photograph, its
description and the text fields are three separate forms.

**Verified:** 38 end-to-end HTTP assertions and 34 pipeline assertions pass. Covered: the
route guard on every GET *and* POST, CSRF rejection, a PHP file renamed `.jpg`, a GIF, an
undersized image, a file over `upload_max_filesize`, a body over `post_max_size`, all eight
EXIF orientations, aspect-ratio and subject retention for all four crops, no upscaling,
transparency flattening to white, EXIF actually being stripped, every variant URL resolving,
replace leaving no orphan files, remove restoring the monogram, and removing twice being
harmless. Under Apache: a `.php` file inside `public/uploads` returns 403 and is never
executed, directory listing is refused, and images are served
`Cache-Control: public, max-age=31536000, immutable`.

**Acceptance test:** `grep` finds no image filename anywhere in `public/` or `src/`. **Passes**
— the only image paths in the codebase are built from a server-generated `storage_key` inside
`MediaUploadService`; no template knows a filename.

**Deliverable:** he changes his photo from the admin and it updates everywhere. **Done.**

---

## Phase 6 — Projects & Case Studies ⭐ ⬜

Projects CRUD · tabbed editor · section management · features · technology tagging · gallery ·
reordering · publish/feature toggles · draft preview with signed tokens · Markdown pipeline
with sanitisation · public case-study page rendering from the database.

**Blocked on:** questions 5–7 in `03-OPEN-QUESTIONS.md`.

**Deliverable:** Rendo and the School Management System entered and published **entirely
through the CMS**. This is the milestone at which the portfolio becomes genuinely usable.

---

## Phase 7 — Remaining Content Management ⬜

Skills and categories · experience · services · process steps · social links · site settings ·
per-page SEO overrides.

**Deliverable:** zero hard-coded content anywhere.

---

## Phase 8 — Contact System ⬜

Public form with validation, honeypot, timing check and rate limiting · database storage ·
email notification · admin inbox with read/unread, archive and reply shortcuts.

**Deliverable:** visitors can reach him reliably, with messages captured twice.

---

## Phase 9 — SEO / Performance / Accessibility Hardening ⬜

JSON-LD · Open Graph · per-project OG images · sitemap · `robots.txt` · canonical URLs.
Image optimisation and AVIF · critical CSS · minification · page caching · index tuning · N+1
elimination.
Full accessibility audit — axe, keyboard, screen reader, 200% zoom, reduced-motion, JS-off.
Lighthouse CI in GitHub Actions.

**Deliverable:** every budget in PRD §N, §O and §P met and verified.

---

## Phase 10 — Deployment & Launch ⬜

Production hosting · domain · HTTPS + HSTS · production config · database and uploads backup
with a **tested** restore · uptime check · admin account created · real content entered ·
cross-browser and real-device testing · OG preview validation · deployment documentation.

**Blocked on:** questions 8–14 in `03-OPEN-QUESTIONS.md`.

**Deliverable:** live at his own domain.

---

## Post-launch, in priority order

1. CV upload and download
2. Light theme (a token swap — already designed for)
3. **French translation** — genuinely valuable for the Cameroonian client audience, and the reason templates must not hard-code English strings
4. Project filtering on the index
5. A blog, if he wants to write
6. Activity log
7. Privacy-respecting analytics

---

## Working rhythm

One phase per working session where possible. Each phase on its own feature branch, merged
only when its deliverable demonstrably works. **Phases 1, 5 and 6 are the large ones** and may
each span two sessions.

**The main risk to manage is scope creep before launch.** A live site with two deep case
studies beats an unlaunched site with ten planned ones.
