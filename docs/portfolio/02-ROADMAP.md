# Development Roadmap

Ten phases. Each is independently valuable and ends with something demonstrably working — no
phase leaves the project in a broken state.

**Current phase: 6 — complete. Awaiting confirmation to begin Phase 7.**

| Phase | Name | Status |
|---|---|---|
| 0 | Documentation & Foundation | ✅ Complete |
| 1 | Design Foundation | ✅ Complete |
| 2 | Application Skeleton | ✅ Complete |
| 3 | Database & Read Path | ✅ Complete |
| 4 | Authentication & Admin Shell | ✅ Complete |
| 5 | Profile Management & Media ⭐ | ✅ Complete |
| 6 | Projects & Case Studies ⭐ | ✅ Complete |
| 7 | Remaining Content Management | ✅ Complete |
| 8 | Contact System | ✅ Complete |
| 9 | SEO / Performance / Accessibility | ✅ Complete |
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

## Phase 6 — Projects & Case Studies ⭐ ✅

**Delivered.** Project list with every publication state · create and edit · the full
`SectionKey` case-study vocabulary · features · technology tagging with a separate
home-page-card selection · thumbnail and cover upload reusing the Phase 5 pipeline unchanged ·
publish / draft / archive · featured toggle · reordering · soft delete and restore · a draft
preview behind the admin guard.

**Three things in the original line were deliberately NOT built**, each for a stated reason
rather than being quietly dropped:

| Not built | Why |
|---|---|
| Markdown pipeline | Section bodies split on blank lines and pass through `e()`, so they are XSS-safe by construction. Markdown means adding a sanitiser, and an unsanitised Markdown renderer is the most common XSS hole in a hand-built CMS. The prose here is paragraphs. Revisit only when a section needs links or lists, and then with a real sanitiser |
| Signed draft-preview tokens | There is one administrator. `/admin/preview/{slug}` behind the kernel guard covers every case. Signed tokens exist to show a draft to someone who cannot sign in |
| Multi-image gallery | `project_images` exists, but nothing renders it: the case-study template has no gallery section, and `Project` carries no gallery field. Building an admin screen for images the public site cannot display would be a feature that does nothing. It belongs with its own public presentation, in Phase 7 |

**Key decisions** (reasoning in `05-ARCHITECTURE.md` §2.14a–2.14c): admin reads as `findAll*`
in the existing repository rather than a duplicate class; writes in a separate `ProjectWriter`;
child collections replaced wholesale inside a transaction rather than diffed.

**Verified:** 48 end-to-end assertions. Covered: the guard on every route, GET *and* POST;
CSRF rejection; a draft 404ing publicly while rendering under preview; an unknown section key
being refused; empty feature rows dropped; a non-http URL refused with nothing saved; script
tags escaped on both admin and public pages; publish → 200, unpublish → 404; a slug reusable
after soft delete and a restore onto a taken slug refused cleanly; image upload producing the
same nine variants as the profile photo, and removal leaving no orphaned files or rows.

**Deliverable:** Rendo and the School Management System are now editable **entirely through
the CMS**. This is the milestone at which the portfolio becomes genuinely usable.

---

## Phase 7 — Remaining Content Management ✅

**Delivered.** The skills vocabulary — categories and skills, with create, edit, show/hide,
reorder and a delete that refuses rather than cascades · services · process steps · site
settings. Four new admin screens, all behind the kernel guard.

**Checking the schema before planning changed the shape of this phase.** Two line items in the
original list turned out not to need building:

| Line item | What was actually true |
|---|---|
| Social links | **Already done since Phase 5.** `email`, `whatsapp`, `github_url` and `linkedin_url` are columns on `profile` (migration 0003) and have been editable at `/admin/profile` all along. The roadmap line was stale, not the code |
| Skills, site settings | Half built. The tables (0004, 0006) and read repositories existed from Phase 3; only the writers and screens were missing. Phase 7 added those, not a new design |

**Two things were deliberately NOT built**, each for a stated reason rather than being quietly
dropped:

| Not built | Why |
|---|---|
| Experience | There is no `experience` table and no admin screen for one, because there is nothing verified to put in it. The standing rule here is that employment history is never invented, and an Experience CRUD would have shipped an empty admin screen feeding an empty public section. Unused schema is a liability — it has to be migrated, backed up and explained. The home page's Experience section keeps its `pending` block, which names the question it waits on. Build it when there are real entries |
| Per-page SEO overrides | Phase 9 is the SEO phase. Designing the storage before that work establishes what it needs is guessing at a shape, and a guessed schema is harder to change than an absent one |

**Key decisions:** one `ContentListWriter` parameterised by a `ContentList` enum rather than two
identical writers for services and process steps — the table name can only come from the enum,
never from a request, which is what makes interpolating it safe. Deleting a skill or a category
**counts first and refuses** with a message naming what is in the way, because a cascade would
silently strip technology tags off published case studies and there is no undo for that.
Hiding is the reversible action and is offered first everywhere. `process_steps` has no
`number` column: the step number a visitor reads is its position, and storing both would let
them disagree.

**The empty state is the designed state.** A services or process list with no rows renders
*nothing* on the home page — no heading, no empty grid, no "coming soon" — the same rule
`work-show.php` states for case-study sections. The nav follows the same fact, so Services
never appears as a link to a section that is not on the page.

**Verified:** 31 assertions in `bin/verify-phase7.php`, plus the Phase 7 routes added to STEP 9
of `bin/verify-local.ps1` (GET *and* POST, each listed rather than assumed covered). Covered:
duplicate slugs refused rather than thrown, for categories and across all skills; a category
holding skills refusing deletion and surviving it; hidden rows absent from the public read and
present in the admin read; new rows appended to the end; reorder writing dense positions rather
than the numbers typed; a hidden row keeping its text; an unknown settings key ignored rather
than created. The script writes to the database and ends by asserting the row counts are back
where it found them — a cleanup that silently failed is a FAIL, not debris discovered months
later.

Checked over HTTP as well: all four screens 200 when signed in and 302 to the login form when
not; the delete refusal reaching the user as a flash with the count correctly pluralised; the
home page rendering no `id="services"` and no `#services` nav link while the table is empty,
and all three nav links plus both sections once it is not.

**Deliverable:** zero hard-coded content anywhere that Phase 7 covers. The four `pending`
blocks still on the home page wait on *content* (About copy Q12, Experience, Contact Q8/Q9) or
on Phase 8 — not on code.

---

## Phase 8 — Contact System ✅

**Delivered.** A public form on the home page, messages stored in the database, and an admin
inbox at `/admin/messages` with unread / read / archived, a reply shortcut and an unread count
on the dashboard.

**This phase added reliability, not reachability.** The `mailto:` and `wa.me` buttons have
worked since Phase 5 and are still there, above the form — WhatsApp is the primary business
channel in Cameroon and no form replaces it. What the form adds is a path for people who will
not open a mail client, and a record that cannot be lost in a spam folder.

### The database is the channel; email is a convenience

The row is committed **before** the notification is attempted, and a failed or disabled
`mail()` can never fail a submission. This is not defensive habit: on the free and cheap shared
hosting this site is launching on, `mail()` is frequently disabled outright, and where it works
the mail often lands in spam because it comes from a shared web server with no SPF record.
A design that treated email as the channel would lose messages silently on exactly that
hosting. The inbox is the channel, which is why the dashboard carries the unread count.

`mail()` and no library, because this project has **zero runtime dependencies** by design.
PHPMailer plus SMTP credentials plus another way for a deploy to be wrong, for one
notification, is not a trade worth making.

### Spam: three layers, no CAPTCHA

A CAPTCHA means a third-party script, and Phase 9 spent real effort getting the CSP to
`'self'`. Instead: a **honeypot** whose hits return the same success the sender would have seen
while storing nothing — reporting the rejection tells whoever wrote the bot which field gave it
away — a **per-address rate limit**, and real validation.

`MessageThrottle` has **no table of its own**. It counts rows in `messages` for an address
inside a window, which is what `ix_message_ip` exists for: the messages are their own evidence,
and a log recording that a message arrived, sitting beside the message, is two places to
disagree about one fact.

### Two defences deliberately NOT built

| Not built | Why |
|---|---|
| Timing check | Doing it honestly needs a signed timestamp, and there is no `APP_KEY` in this project — so a new secret, a new deployment step, and a new way for every submission to fail if it is wrong. Marginal value over the honeypot. Add it if spam actually arrives, with evidence |
| CSRF token on the public form | CSRF protects a victim from an action taken as them; here the action is sending Ramson a message, which an attacker can simply do directly. It stops no bot — a bot fetches a token as easily as a form. And it costs: `Csrf::token()` starts a session, so a token on the home page means a session file per anonymous visitor on shared hosting and a `Set-Cookie` on the most-requested page. Every admin route keeps its check, where a victim and a privileged action both exist |

A session is started on the POST path only, so a visitor who never submits is never given a
cookie — and someone whose submission was rejected gets every field back, because a person who
wrote three paragraphs and mistyped their address must not lose the three paragraphs.

**Verified:** 24 assertions in `bin/verify-phase8.php`, which writes and cleans up and ends by
asserting the row count is unchanged. Covered: storage and the IP round-trip through
`inet_pton`; an empty subject displaying as `(no subject)`; the limit blocking one address while
**not** blocking a different one, and never blocking a request with no address at all; read
recording *when he first saw it* rather than the last time he opened it; archived leaving the
inbox but not the table; and header injection — a `\r\n` in a name is what turns a contact form
into an open relay for `Bcc`.

Checked over HTTP too: the honeypot returning success while storing nothing, a rejected
submission preserving the body, the throttle refusing the sixth message with a retry time, and
the inbox 302ing an anonymous request while serving a signed-in one.

**Deliverable:** visitors can reach him reliably, and a message survives a host that cannot
send email.

---

## Phase 9 — SEO / Performance / Accessibility Hardening ✅

**Reading the code first shrank this phase by more than half.** Most of the original line was
already built in Phases 1–6 and simply not recorded here:

| Line item | Already done |
|---|---|
| JSON-LD, Open Graph, canonicals | The `Seo` object and `head-meta.php`; `WorkController` sets a per-project `ogImage`, `article` type and a `CreativeWork` schema |
| Per-project OG images | The media pipeline has produced a 1200×630 `og` variant since Phase 5 |
| Image optimisation, AVIF | Every upload already produces AVIF, WebP and JPEG variants |
| Page caching | `?v=<filemtime>` on every asset URL plus a one-year `Expires` per type; `font/woff2` was already listed in anticipation of this phase |

**Delivered.**

**Fonts are self-hosted.** Instrument Serif, Inter and JetBrains Mono now come from this
origin as `latin` and `latin-ext` WOFF2. That removes a render-blocking third-party
stylesheet and two TLS handshakes from the critical path, and it is what let the CSP collapse
to `'self'` throughout — no external host in any directive. Nothing this site serves reaches a
third party, and no third party can see who reads it.

Two findings along the way. Google serves **23 of its 37 faces** for Cyrillic, Greek and
Vietnamese, which this site does not use; the `unicode-range` on each face is what makes
dropping them safe. And Inter and JetBrains Mono are **variable** fonts — Google returns one
file per family/style/subset and repeats it under each requested weight, so naming faces by
weight stored the same 48 KB three times. Keying on the source URL collapsed 14 files to 8,
564 KB to 256 KB, and a `font-weight: 700` added later now renders from the real axis rather
than being synthesised.

**`sitemap.xml` and `robots.txt` are generated, not files.** A static sitemap is correct the
day it is written and wrong the first time a project is published from the admin, with nothing
to notice. The sitemap reads through `findAllPublished()` — the same published-only method the
public site uses — so a draft cannot reach a search engine without someone deliberately
calling a `findAll*` method. Proven both ways: unpublishing a project removes it from the
sitemap, republishing brings it back.

`robots.txt` had a real bug. Its `Sitemap:` line read `/sitemap.xml`, and the robots.txt
specification requires an **absolute** URL — so crawlers ignored it, and it pointed at a 404
besides. A static file cannot know its own domain, which is why it is generated too. The
static file was **deleted**, not left in place: `public/.htaccess` serves a real file before
consulting the front controller, so leaving it would have silently kept the broken version
winning.

**Compression.** `public/.htaccess` cached but never compressed. A `mod_deflate` block now
covers the text types, by MIME type rather than extension — the front controller serves HTML
and the sitemap with no extension at all, so an extension rule would miss every page on the
site. Already-compressed formats are deliberately excluded.

**The accessibility audit found a real failure**, which is the point of auditing rather than
asserting. `--fg-tertiary` was documented as `~4.6:1 AA` and measured **3.78:1** against
`--surface-overlay` — under the 4.5 floor for normal text, and it is used for exactly that:
meta lines and eyebrows. The quoted figure had been taken against the *darkest* surface, which
flatters it. Changed `#6E7480` → `#7E8490`, which clears 4.5 on every surface with margin
while keeping the hue so the hierarchy is unchanged. The other three documented ratios were
re-measured and corrected as well — `--accent-fg` was labelled `~13:1` and is 8.96:1 (still
AAA; the colour was fine, the comment was not).

Structure was already clean: one `<h1>` per page, no skipped heading levels, a skip link,
`lang`, landmarks, labelled fields, `:focus-visible`, and `prefers-reduced-motion` honoured.

**Deliberately NOT built**, each for a stated reason:

| Not built | Why |
|---|---|
| Minification | This project has **no build step by design**. A minifier adds a toolchain to install, run and forget, to save a few KB that `mod_deflate` already saves without one |
| Critical-CSS inlining | The CSP forbids `unsafe-inline`, deliberately — that is why there is no inline JavaScript anywhere. Inlining would need a nonce or hash on every page, a real cost against one stylesheet already served with a one-year cache |
| Lighthouse CI | This repo has no CI at all, and a Lighthouse job needs a URL to hit. Adding it before the site exists is backwards. Worth doing after launch, against the real domain |

**Verified:** 22 assertions in `bin/verify-phase9.php`, plus the crawler routes and a font-file
count added to `bin/verify-local.ps1`. Every assertion covers something that fails *silently* —
a font quietly falling back to Georgia, a CSP quietly re-admitting a third party, a contrast
ratio quietly drifting under 4.5. The contrast checks read the hex values straight out of
`main.css`, so a token edited without re-measuring fails the check rather than the reader.

**Deliverable:** no third-party origin on any page, drafts unreachable by crawler, and every
documented contrast ratio true.

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
