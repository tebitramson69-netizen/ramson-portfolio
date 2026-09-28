# Ramson Titih — Personal Professional Portfolio
## Product Requirements, Architecture & Development Plan

**Author:** Ramson Titih
**Repository:** `tebitramson69-netizen/ramson-portfolio` — the official home of this project
**Local path:** `C:\xampp1\htdocs\ramson-portfolio`
**Status:** Phase 0 — documentation complete, implementation not started
**Last updated:** 2026-09-28

---

## Content integrity rule

**This rule governs every other decision in this document.**

Every factual claim about Ramson — skills, education, projects, location — comes only from
what he has supplied. Anything still required is marked `[NEEDS INPUT]` and tracked in
`04-CONTENT-INVENTORY.md`.

**No invented achievements, clients, testimonials, statistics, certifications, user counts,
revenue or business results appear anywhere in this project.** A recruiter who catches one
fabricated number discards the credibility of the entire site. Honest small numbers, or no
numbers.

**CareerForge AI is excluded from this portfolio entirely** — it must not appear in any
document, template, database seed, screenshot or commit message.

---

## Locked decisions

These were confirmed and are no longer open. They are inputs to implementation, not topics
for re-litigation.

| Decision | Value |
|---|---|
| Repository | `ramson-portfolio` — new, dedicated, official |
| Name | Ramson Titih |
| Professional identity | Software Engineer / Full-Stack Developer |
| Education | HND Software Engineering student, Saint Louis University Institute Douala |
| Location | Cameroon |
| Primary project 1 | **Rendo** — business/booking automation via WhatsApp |
| Primary project 2 | **School Management System with Automated Report Card Generation** |
| Excluded | **CareerForge AI** — must not appear anywhere |
| Accent colour | `#2DD4A7` |
| Typography | Serif display + modern sans-serif body |
| Hero portrait | Rectangular editorial frame — **not** a circular avatar |
| Value proposition | *"I build practical web systems that turn manual workflows into simple digital experiences."* |
| Technology line | *"Full-stack development · PHP · JavaScript · MySQL · AI & Automation"* |
| Motion | Subtle, purposeful — never decorative excess |
| Stack | PHP 8.2+, MySQL, vanilla JS, hand-written CSS, server-rendered, no framework |
| CMS | Required — secure, single-administrator |
| Profile photo | CMS-managed: upload, preview, replace, remove. Never hard-coded. |
| Content source | Database/CMS. No hard-coded content anywhere. |
| First-class requirements | Accessibility, SEO, security, performance, responsiveness |

---

# A. Repository Audit

`tebitramson69-netizen/ramson-portfolio` — public, empty, branch `main`, zero commits at the
time this document was written. A genuine clean slate: no prior code, no dependencies to
reconcile, no legacy decisions to work around.

This is the ideal starting condition, and it means three things are established from the very
first commit rather than retrofitted:

1. **A correct `.gitignore`** — credentials, uploads, build output and logs are excluded before any of them exist. (A previous project of Ramson's committed database credentials in `connection.php`; that habit is corrected here structurally, not by discipline.)
2. **`public/` as the sole document root** — decided before any file is placed, so nothing ends up in the wrong tree.
3. **The media abstraction** — designed before the first image, so no filename is ever hard-coded "just for now".

**Carried forward from the planning work:** the audit of the earlier `Student_registration`
project established what *not* to repeat — string-concatenated SQL, state-changing GET
requests, committed credentials, inline styles competing with an external stylesheet, and
default Bootstrap blue `#007bff`. Those findings shaped the conventions in §Q and §U. No code
is carried over.

**Observed on GitHub, not yet confirmed as portfolio content:** the account also holds
`School-Management-System` and `ai-script-to-video-studio` repositories. The former is very
likely the source for primary project 2 and would supply a real GitHub URL; the latter may or
may not be a project Ramson wants featured. Both are logged as questions in
`03-OPEN-QUESTIONS.md` rather than assumed.

---

# B. Product Vision

**Positioning:**
> A credibility instrument, not a brochure — a website that lets a recruiter reach an informed
> hiring decision in under 90 seconds, and lets a business owner understand in under three
> minutes that Ramson can build the system they need.

**Three principles, in priority order:**

1. **Evidence over adjectives.** No "passionate", no "hardworking", no skill percentage bars, no invented numbers. Real projects described with real technical specificity are the entire argument. Specificity *is* the credibility signal.
2. **Editorial restraint as the design language.** The site must look expensive because of typography, spacing, composition and restraint — not effects. A junior portfolio is over-decorated; a senior one is under-decorated.
3. **The CMS is a real product.** A recruiter who learns there is a custom PHP CMS behind the site learns more about Ramson's engineering ability than any skills list conveys.

**Non-goals:** not a blog (for now), not a sales funnel, not a JavaScript SPA, not multi-user.
**Single administrator: Ramson.** That one decision removes roles, permissions, invitations and
audit complexity from the entire design.

**Definition of done:** a recruiter on a mid-range Android phone over a Cameroonian mobile
connection sees meaningful content in under 2.5 seconds, understands who Ramson is and what he
has built without scrolling more than twice, and can reach him in one tap — and Ramson can
change any of it from his phone's browser.

---

# C. Target Users

Four audiences, weighted. Design conflicts resolve toward the higher weight.

### C.1 Technical Recruiter / Sourcer — 40%
Decides in seconds whether to shortlist. Scans, does not read. Often mobile, often twenty tabs
deep. Needs above the fold: name, title, stack, location, availability, contact.
**Kills the page:** vagueness, no evidence of shipped work, obviously fake metrics, broken
links, no contact route.

### C.2 Hiring Manager / Senior Engineer — 25%
The only visitor who reads a case study end to end and clicks through to GitHub. Assesses
judgement, not output.
**Kills the page:** a feature list with no architectural reasoning; "I built X" with no
decisions, trade-offs or problems named.

### C.3 Business Owner / Potential Client — 25%
Cameroonian SMEs, clinics, schools. Does not care about the stack; cares about outcomes,
process, reliability and communication. **Very likely to make contact via WhatsApp, not email.**
**Kills the page:** pure jargon, no visible process, no low-commitment way to start talking.

### C.4 Peer / Collaborator — 10%
Evaluates for collaboration or referral. Needs GitHub prominence and honest technical writing.

### C.5 The fifth user: Ramson as administrator
Frequently forgotten, and the main reason portfolios go stale. Requirements: mobile-usable
admin, fast login, previewable drafts, no fear of breaking the live site, and low enough
friction that adding a project takes under ten minutes. **If the CMS is annoying, the portfolio
rots — and a stale portfolio is worse than a static one.**

---

# D. Recruiter Journey

Hard budget: **decision in 90 seconds, contact in ≤ 3 interactions.**

| Step | Time | Where | What must happen |
|---|---|---|---|
| 1 | 0–3s | Hero | "Software Engineer, full-stack, PHP/MySQL, Cameroon, available" absorbed without scrolling |
| 2 | 3–10s | Hero | Stack confirmed via the technology line, still without scrolling |
| 3 | 10–35s | Selected Work | Two real, substantial projects seen — **the most important moment on the site** |
| 4 | 35–60s | Case study | Depth verified: the problem, what he personally built, what he decided |
| 5 | 60–75s | About | Context confirmed: HND Software Engineering, Saint Louis University Institute Douala, Cameroon |
| 6 | 75–90s | Contact | Contact achieved |

**Non-negotiable:**
- Contact reachable from any scroll position — the sticky header CTA guarantees it.
- Real `mailto:` and `wa.me` links, not only a form. Recruiters copy addresses into their ATS; a form alone is a drop-off point.
- Availability explicit and CMS-editable (`Available for opportunities` / `Open to select projects` / `Not currently available`). Ambiguity costs shortlist slots.
- **A CV/résumé download.** Every recruiter looks for it. The CMS keeps the button hidden until a file is uploaded, so it can never 404.
- No hover-dependent information anywhere — phones have no hover.

---

# E. Client Journey

Non-technical, outcome-driven, needs trust. Served **without** becoming a landing page: no
countdown timers, no "limited slots", no fake urgency, no invented pricing tiers.

| Step | Section | Question answered |
|---|---|---|
| 1 | Hero | "Is this a real professional?" |
| 2 | Selected Work | "Has he built something like what I need?" — Rendo speaks directly to clinics, dental practices and beauty studios; the School Management System speaks directly to schools. **These are the two strongest client-conversion assets on the site.** |
| 3 | Services | "What exactly can he do for me?" — written in business outcomes, not stack names |
| 4 | How I Work | "What is working with him actually like?" — the highest-trust, lowest-cost section on the site `[NEEDS INPUT: your real process, in your own words]` |
| 5 | Contact | "How do I start, without commitment?" — WhatsApp first; this is Cameroon, and WhatsApp *is* the business channel |

**Structural insight:** both journeys share sections 1–3 and diverge only at Services and How
I Work. This is why the homepage places those *after* Selected Work and About — the recruiter
has already decided by then, and the client still gets what they need without the recruiter
wading through sales copy.

---

# F. Information Architecture

## F.1 Content model

```
PROFILE (singleton)          identity, bio, photo, availability, contact endpoints
SOCIAL LINKS (ordered)       platform, label, URL, icon, visibility
PROJECTS (ordered)           the core content type; carries the full case study
  ├── PROJECT IMAGES         thumbnail, cover, gallery
  ├── PROJECT TECHNOLOGIES   → SKILLS
  ├── PROJECT FEATURES       ordered bullets
  └── PROJECT SECTIONS       the 18-part case-study narrative
SKILLS (grouped, ordered)    grouped by SKILL CATEGORY
EXPERIENCE (ordered)         roles / education / milestones
SERVICES (ordered)           client-facing offerings
PROCESS STEPS (ordered)      the "How I Work" content
MESSAGES                     contact submissions, read/unread/archived
SETTINGS                     site title, meta, favicon, OG image, flags
```

## F.2 Three structural decisions

1. **Every public list is ordered by an explicit `sort_order` integer** — never by `id` or `created_at`. This is what makes "put Rendo first" a CMS action rather than a code change.
2. **`is_published` and `is_featured` are independent booleans.** Publication controls existence on the public site; featuring controls whether a project gets the large editorial treatment. Two flags yield three presentation tiers: *featured*, *published-not-featured*, *draft*.
3. **Skills are a shared vocabulary, not free text.** Project technology tags reference the skills table, giving one canonical spelling of "MySQL" site-wide and feeding the JSON-LD `knowsAbout` property.

## F.3 Content hierarchy

```
1 Identity     name, title, value proposition, photo, availability
2 Evidence     featured projects (Rendo, School Management System)
3 Capability   skills, services, process
4 Context      about, education, experience
5 Depth        individual case-study pages
6 Action       contact
```

The homepage carries levels 1–4 and 6. Level 5 lives on its own pages — deliberately, so the
homepage stays scannable and the interested reader self-selects into depth.

---

# G. Sitemap

## G.1 Public

```
/                        Homepage
/projects                All projects (grid, filterable by technology)
/projects/{slug}         Case study        e.g. /projects/rendo
                                                /projects/school-management-system
/about                   Extended about + experience + education
/services                Extended services (optional — the homepage section may suffice)
/contact                 Contact form + all channels
/cv                      CV download (route exists only when a CV is uploaded)

/404  /500               Styled error pages
/sitemap.xml             Generated from published content
/robots.txt              Allows the public site, disallows /admin
/uploads/...             Served images — never executable (see §Q.6)
```

**Deliberately excluded for now:** blog, testimonials (none exist — inventing them is
disqualifying), and any statistics band (no honest numbers exist). Each can be added later
through the CMS without restructuring.

## G.2 Admin

```
/admin/login                   Rate-limited login
/admin/logout
/admin                         Dashboard — counts, unread, quick actions, completeness checklist
/admin/profile                 Profile editor, including the photo manager
/admin/social-links            CRUD + reorder
/admin/projects                List — search, filter, reorder, publish toggle
/admin/projects/create
/admin/projects/{id}/edit      Tabbed: Basics · Case Study · Technologies · Features · Media
/admin/skills                  Skills + categories, CRUD + reorder
/admin/experience              CRUD + reorder
/admin/services                CRUD + reorder
/admin/process                 "How I Work" steps
/admin/messages                Inbox — read/unread/archived
/admin/messages/{id}           Detail + reply shortcut
/admin/settings                Site title, meta, OG image, favicon, availability
/admin/account                 Password, active sessions
```

**Depth rule:** no admin screen is more than two clicks from `/admin`. Every CMS that violates
this gets abandoned.

---

# H. Homepage Structure

| # | Section | Purpose |
|---|---|---|
| 0 | **Header / Nav** | Sticky, condenses on scroll, always carries the contact CTA |
| 1 | **Hero** | Identity, value proposition, CTAs, availability, portrait — see §H.3 |
| 2 | **Technology strip** | Instant stack confirmation at zero scroll cost. Plain text marks, **not** a logo grid |
| 3 | **Selected Work** | 2 featured + up to 2 compact. Highest-value section; most vertical space |
| 4 | **About** | Human context, education, location — answers "who is this?" right after the evidence |
| 5 | **Skills** | Grouped (Frontend / Backend / Database / Tools / Practices). **Names only — no proficiency bars, percentages or star ratings**; they are unverifiable and read as junior |
| 6 | **How I Work** | Four numbered steps. Process before offer reads consultative |
| 7 | **Services** | What he can be hired to build |
| 8 | **Experience / Education** | Honest timeline. A short timeline stated plainly is credible; a padded one is not |
| 9 | **Contact CTA** | One confident conversion moment |
| 10 | **Footer** | Navigation, socials, copyright, discreet admin entry |

## H.1 Section rhythm

Alternating surface tone and container width prevents the "endless identical bands" template
feel:

```
Hero            dark,     full-bleed,  asymmetric 7/5
Tech strip      dark,     thin band,   single row, hairlines top and bottom
Selected Work   base,     wide,        large alternating blocks
About           raised,   narrow,      2-col (text / portrait + facts)
Skills          base,     wide,        3–4 column category grid
How I Work      raised,   narrow,      numbered vertical steps
Services        base,     wide,        2×2 grid
Experience      base,     narrow,      single-column timeline
Contact CTA     dark,     full-bleed,  centred, generous padding
Footer          sunken,   wide,        multi-column
```

## H.2 Hero specification

The most important 600 pixels on the site.

**Desktop (≥1024px):** asymmetric two-column, 7/5 split, content left, portrait right,
vertically centred, **~88vh — not 100vh**. Showing a sliver of the next section signals depth
and invites scrolling.

Left column, top to bottom:

1. **Availability pill** — small caps, hairline border, 8px dot with a very slow ambient pulse (2.4s, 0.6→1 opacity). The one piece of motion earned in the hero. Disabled under `prefers-reduced-motion`.
2. **Eyebrow** — `Software Engineer · Full-Stack Developer`, letter-spaced small caps, muted.
3. **Name** — `Ramson Titih`, the largest type on the site: `clamp(2.75rem, 7vw, 5.25rem)`, tracking `-0.03em`, leading `0.95–1.0`. **The confidence of the whole page lives in this one setting.**
4. **Value proposition** — *"I build practical web systems that turn manual workflows into simple digital experiences."* At `~1.125–1.25rem`, capped near 34ch so it breaks into two or three confident lines.
5. **Technology line** — `Full-stack development · PHP · JavaScript · MySQL · AI & Automation`, muted, hairline-separated.
6. **CTAs** — Primary `View Selected Work` (solid accent; evidence converts better than a premature contact ask). Secondary `Get in Touch` (bordered ghost). Optional tertiary `Download CV` text link.
7. **Micro-facts** — `Cameroon` · `HND Software Engineering` — small, muted, hairline-separated.

**Portrait treatment** — where most portfolios look amateur:

- Tall frame, **4:5 ratio, `border-radius: 4px`. Not a circle.** Circular avatars read as social-media profile pictures; a rectangular editorial frame reads as a magazine feature.
- A 1px hairline border offset 16px down-right behind the image, creating quiet depth with no shadow or glow.
- Slight desaturation plus a 4% accent-tinted overlay, so **any** uploaded photo harmonises with the palette regardless of its background. This matters specifically because the image is CMS-managed and unknown at design time.
- `<picture>` with AVIF → WebP → JPEG, three widths, `fetchpriority="high"`, explicit `width`/`height` to prevent layout shift. **Never `loading="lazy"`** — this is the LCP element.
- **Fallback when no image exists:** a monogram tile (`RT`) in the same frame with the same border treatment. The hero must never break and must never show a broken-image icon.

**Tablet (768–1023px):** portrait narrows, name clamps to the mid-range, micro-facts wrap.

**Mobile (<768px):** single column, **portrait first** at 3:4 and ~62vw, centred; then pill,
eyebrow, name, value proposition, full-width stacked CTAs (primary first, 48px min height),
micro-facts wrapped. Height becomes content-driven — `100vh` on mobile is a known bug source
with browser chrome.

---

# I. Project & Case-Study Structure

## I.1 The two primary projects

All descriptive content below comes strictly from what Ramson supplied. Gaps are marked.

### Rendo — Business Automation / SaaS
A business and booking automation platform helping clinics, dental practices, beauty studios
and similar businesses handle client messages, bookings and appointment reminders through
WhatsApp.

**Known scope:** customer messaging · booking · appointment reminders · business onboarding ·
waitlist · automated workflow · a polished SaaS-style landing page.

`[NEEDS INPUT: technology stack · current state (concept / landing page live / functional
product) · live URL · GitHub URL · screenshots · what you personally built · the hardest
technical problem you hit]`

### School Management System with Automated Report Card Generation — Education / Full-Stack
A full-stack school management platform designed around secondary-school workflows.

**Known scope:** students · teachers · classes · subjects · attendance · scores · results ·
ranking · report cards · role-based dashboards, with administrator, teacher, student and
parent functionality.
**Known stack:** PHP · MySQL · JavaScript · HTML · CSS.

**Strongest single asset:** automated report-card generation with ranking. That is real
computational logic — weighted averages, per-subject aggregation, class ranking, printable
output — and it is the most technically impressive thing to foreground.

`[NEEDS INPUT: confirm the GitHub repo · live URL · screenshots · whether a real school uses
it · the ranking logic in your own words · the hardest problem you solved]`

### Further projects
Other genuine projects may be added later through the CMS. None are assumed here.

## I.2 Featured card — required elements

1. **Visual preview** — a real UI screenshot in subtle browser chrome, 16:10, `radius: 6px`, 1px hairline, no drop shadow. Hover: 1.5% scale over 400ms plus a border-colour shift. Nothing more.
2. **Category eyebrow** — small caps, muted.
3. **Project name** — second-largest type on the homepage.
4. **Problem line** — one sentence naming the real-world problem. Recruiters read this line more than any other project text.
5. **Solution summary** — two or three sentences.
6. **Role line** — critical: recruiters must know what Ramson *personally* did.
7. **Technology tags** — small bordered marks, not coloured pills.
8. **Key features** — three or four, terse.
9. **Actions** — `View Case Study` primary; `GitHub` and `Live Site` **rendered only when the CMS holds a URL.** A dead link is worse than no link.

## I.3 Case-study page

A fixed, ordered set of sections. Every one is optional at the data layer and **simply does
not render when empty** — never an orphan heading, never "Coming soon", never lorem ipsum.

```
1  Hero                     name, category, summary, year, cover, meta strip, GitHub/Live
2  Overview                 what it is
3  The Problem              the real-world pain that justified building it
4  Context                  who it is for, constraints, environment
5  Goal                     what success meant
6  The Solution             what was built, and why this shape
7  My Role                  explicitly what Ramson personally designed and built
8  Process                  how it was approached
9  Features                 grouped, with short explanations
10 Technical Implementation architecture, data model, security decisions
                            — the section hiring managers actually read
11 Technologies             grouped stack, one line of "why this" per major choice
12 Challenges               real problems hit and how they were solved
13 Key Decisions            trade-offs, naming the rejected alternative and why.
                            THE highest-signal section on the entire site — the only place
                            that demonstrates judgement rather than output
14 Screenshots              captioned gallery, lightbox, keyboard-navigable
15 Outcome                  current state, honestly stated. "Functional prototype" and
                            "in development" are respectable; invented adoption is not
16 Lessons Learned          what he would do differently. Counter-intuitively a STRENGTH
                            signal — senior engineers recognise honest retrospection
17 Next Steps               planned work (optional)
18 Footer nav               previous / next project + contact CTA
```

**Reading experience:** 68ch measure for prose, full-bleed images, a sticky in-page table of
contents at ≥1280px, a 2px scroll-progress hairline, and an estimated reading time in the hero
(it measurably increases completion).

---

# J. Profile Management

The most emphasised requirement in the brief. Specified in full.

## J.1 Hard rule

**No profile image filename, path or URL is ever written in PHP, HTML, CSS or JavaScript
source.** Every rendering site resolves the image through one accessor reading from the
database.

**Acceptance criterion for Phase 5:** a grep for image filenames across `public/` and `src/`
returns nothing.

## J.2 Architecture

```
ADMIN UPLOAD
   │  POST /admin/profile/photo   (multipart, CSRF-protected, authenticated)
   ▼
ImageUploadService::store()
   │  validation pipeline (§J.4) → normalise → derive variants → write → transaction
   ▼
FILESYSTEM  storage/uploads/profile/{random}-{variant}.{ext}   (non-executable)
DATABASE    media row + profile.photo_media_id FK
   ▼
PUBLIC      ProfileRepository::current() → Media::responsiveSrcset()
   ▼
RENDER      <picture> AVIF/WebP/JPEG · 3 widths · explicit dimensions
            · alt text from the database · monogram fallback when NULL
```

## J.3 Derived variants

One upload produces four sizes, so every surface gets a correctly-sized file:

| Variant | Size | Used by |
|---|---|---|
| `hero` | 800 × 1000 (4:5) | Homepage hero portrait |
| `about` | 600 × 750 (4:5) | About section |
| `thumb` | 160 × 160 (1:1, centre-weighted) | Admin UI, footer, OG fallback |
| `og` | 1200 × 630 | Social preview card |

Each written as WebP **and** JPEG. AVIF added in Phase 9 if the host's PHP build supports it —
detected at runtime, never assumed.

## J.4 Validation pipeline

Order matters: cheapest and most decisive checks first. **Nothing the client sends is trusted.**

1. **Authentication + CSRF** — verified before the upload is even read from disk.
2. **`$_FILES` error code** — `UPLOAD_ERR_INI_SIZE`, `UPLOAD_ERR_PARTIAL`, `UPLOAD_ERR_NO_FILE` each get a distinct human message. A silent failure here is the classic "why isn't my photo changing" bug.
3. **`is_uploaded_file()`** — guards against a crafted path passed as a temp file.
4. **Size** — max 5 MB, enforced in PHP *and* `upload_max_filesize` / `post_max_size` *and* the UI *and* client-side. Four layers, because the PHP check never runs if the ini limit rejects the request first — a subtle failure mode worth understanding.
5. **Real MIME via `finfo_file()`** — **never** `$_FILES['type']` (client-supplied, trivially forged) and never the extension. Allowlist: `image/jpeg`, `image/png`, `image/webp`.
6. **`getimagesize()`** — must return valid dimensions with a type in `IMAGETYPE_JPEG|PNG|WEBP`. Confirms it parses as a real image, not merely that its first bytes look like one.
7. **Dimension bounds** — min 400×400 (reject rather than upscale into a blurry hero), max 8000×8000 plus a total-pixel cap (decompression-bomb guard).
8. **Re-encode, always.** The uploaded bytes are **never stored.** The image is decoded with GD and re-encoded. This single step neutralises polyglot files, EXIF-embedded payloads and malformed-chunk exploits more reliably than any scanner — the payload simply does not survive a decode/encode round trip. It also strips EXIF, removing GPS coordinates from a phone photo: a real privacy protection.
9. **Generated filename** — `bin2hex(random_bytes(16))` plus a server-derived extension. The original filename is stored as a display label only and **never** used to build a path. No user-controlled byte reaches the filesystem.
10. **Write outside the web root, then serve deliberately.** Files land in `storage/uploads/`. Images are copied to `public/uploads/`, which carries an `.htaccess` denying PHP execution and disabling indexes. *(Recommended over a PHP delivery route on XAMPP/shared hosting: simpler, and Apache serves the bytes with no PHP overhead.)*
11. **Atomic database update** — variants written first, then the `media` row and the `profile.photo_media_id` update commit in one transaction. On failure: no orphan state, old photo still live. Old files are deleted only *after* the commit succeeds.

## J.5 The five admin operations

| Operation | UX | Behaviour |
|---|---|---|
| **Upload** | Drag-and-drop zone **plus** a keyboard-accessible "Choose file" button (`<input type="file">` never hidden from assistive technology) | Client-side type/size pre-check → instant local preview via `URL.createObjectURL()` → upload on explicit Save, with a progress bar |
| **Preview** | Live preview in the **exact hero frame**, before saving | Uses the real hero component styling, so what he sees is what visitors get. Also shows the 1:1 thumb crop — centre-cropping a portrait can decapitate it, and showing this prevents a bad crop going live |
| **Replace** | Same control; current and new shown side by side | Old variants deleted only after the new ones commit. Never a window where the site has no photo |
| **Remove** | "Remove photo" with a typed confirmation | Sets `photo_media_id = NULL`, deletes files, public site falls back to the monogram. Fully reversible |
| **Change any time** | — | No limits, no cooldown. Appears site-wide immediately (see §J.6) |

**Alt text is a required field** when a photo is present, defaulting to
`"Portrait of {profile.name}"` and editable. Accessibility is part of the upload flow, not an
afterthought.

## J.6 Cache invalidation

Image URLs carry the media row's `updated_at` as a version parameter
(`/uploads/profile/ab12…-hero.webp?v=1727481600`), so files are served with
`Cache-Control: public, max-age=31536000, immutable` while a replacement still appears
instantly.

**This is the mechanism behind "the new photo appears everywhere automatically."** Without it,
long cache lifetimes and instant updates are contradictory requirements.

## J.7 Failure states

| Condition | Behaviour |
|---|---|
| Wrong type | Inline error naming the accepted types. Nothing uploaded |
| Too large | Inline error naming the limit **and** the actual size |
| Too small | Inline error naming the minimum. Never silently upscale |
| Corrupt / not an image | "This file could not be read as an image." |
| GD unavailable | A startup health check surfaces it on `/admin` **before** he tries to upload |
| Disk full / unwritable | Transaction rolls back, old photo stays live, clear error shown |
| No photo set | Monogram fallback; public site renders normally; admin shows a gentle prompt |
| Upload interrupted | Temp files cleaned up, no partial row |

---

# K. CMS Requirements

## K.1 Scope

Single administrator. **No registration route, no email password reset in v1** (a CLI reset
script instead — fewer moving parts, no email attack surface), no roles, no multi-user.

## K.2 Screens

**`/admin/login`** — email + password, CSRF, **generic error message** ("Invalid credentials" — never "no such user", which enumerates accounts), rate limiting (5 attempts per 15 minutes, per IP *and* per account), a constant-time floor (~250ms) so timing reveals nothing, and redirect to the originally-requested URL.

**`/admin` dashboard** — unread messages (the only true action item), publish-state summary, quick actions, environment health (GD available, uploads writable, PHP version), and a **content-completeness checklist**: profile photo set? CV uploaded? every featured project has a thumbnail? any project missing a case study? *This checklist is what keeps the portfolio from quietly rotting.*

**`/admin/profile`** — the photo manager (§J) plus name, title, tagline, intro, about, location, availability status and note, email, phone, WhatsApp, CV upload. **Character counters on every field feeding a design-constrained slot** — a 200-character "tagline" will break the hero, and the CMS must protect the design.

**`/admin/projects`** — table with title, category, status, featured flag, updated date; search; status filter; **drag-to-reorder with a keyboard alternative** (explicit Move up / Move down buttons — drag-and-drop alone is inaccessible and painful on mobile); inline publish/feature toggles; duplicate; delete with typed confirmation.

**`/admin/projects/{id}/edit`** — tabbed, because one form with 18 case-study fields is unusable:
- *Basics* — title, slug (auto-generated, editable, uniqueness-validated), category, summary, year, status, GitHub URL, live URL, featured, published
- *Case Study* — the 18 sections, each a textarea with a **light Markdown subset** (headings, bold, italic, lists, links, inline code, code blocks). **Not a WYSIWYG editor** — those produce unpredictable HTML and are a security liability. Rendered server-side, then sanitised through an allowlist
- *Technologies* — multi-select against the skills table, plus an ordered "primary" subset for the card
- *Features* — repeatable rows
- *Media* — thumbnail, cover, gallery with captions, alt text and ordering

Every tab: **Save draft · Preview · Publish.** Preview renders the real public template from
draft data via a signed, expiring token. **Preview is essential** — without it he edits live,
which means he will avoid editing.

**`/admin/messages`** — inbox with read state, detail view, one-tap `mailto:` / `wa.me` reply, archive, delete, spam indicator. Messages stored in the database **and** forwarded by email, because a database-only inbox he forgets to check means lost opportunities.

**`/admin/settings`** — site title, meta description, canonical URL, default OG image, favicon, analytics ID, maintenance toggle, global availability.

## K.3 Cross-cutting

- **Flash messaging after every write.** Silent success is indistinguishable from failure.
- **Server-side validation is authoritative;** client-side exists only for speed of feedback.
- **On a validation error, re-render with submitted values intact.** Losing a 2,000-word case study to a validation error will make him hate the CMS.
- **Unsaved-changes warning** via `beforeunload`, plus per-form `localStorage` autosave cleared on successful save.
- **Mobile-usable admin.** He will want to fix a typo from his phone. A real requirement.
- **Soft delete** (`deleted_at`) on projects, so an accidental delete is recoverable.

---

# L. Design System

Full token values live in `01-DESIGN-SYSTEM.md`. Direction summarised here.

**Name:** *Editorial Engineering.* Swiss-editorial typographic discipline, dark primary
surface, near-monochrome palette, a single restrained accent, hairline borders instead of
shadows, generous asymmetric whitespace.

**Colour** — dark-first, five-step cool-shifted neutral ramp (hue ~222°), four foreground
steps all WCAG-verified, three hairline border levels, and **one accent: `#2DD4A7`.**

> **The accent discipline rule — this is what separates premium from template:** the accent
> appears on **at most three elements per viewport**. Permitted: the primary button, the
> availability dot, a link underline on hover, a focus ring, an active nav indicator.
> Forbidden: accent headings, accent gradients, accent card borders by default, accent icons
> everywhere. **Scarcity is what makes it read as intentional.**

**Typography** — serif display + sans body. The serif display face is the single most effective
way to escape the generic all-sans developer-portfolio look. Rules that do most of the work:
display type gets *tighter* tracking (`-0.02em` to `-0.035em`) and leading (`1.0–1.1`) than
defaults give it — loose display type is the commonest reason a heading looks amateur. Body at
`1.65` leading, prose capped at 68ch. Three weights only; **never 700+ for the display serif**,
which muddies the contrast that makes it work.

**Spacing** — 8px base. **Section vertical rhythm at `clamp(5rem, 10vw, 10rem)`.** This single
consistent value is the highest-leverage decision for making the site feel premium; cramped
vertical spacing is the number-one reason a portfolio reads as a template.

**Grid** — 12/8/4 columns. **Asymmetric splits (7/5, 5/7, 8/4) are the default**; symmetric 6/6
is the template look and is used rarely and deliberately.

**Radii** — restrained by rule: cards, images, buttons and inputs at `6px`; the portrait frame
at `4px`. `radius-full` permitted **only** on the availability dot and avatar thumbnails. No
24px blobs, no pill buttons.

**Elevation is borders, not shadows.** Depth comes from hairlines and surface-tone steps.
Shadows appear only on genuinely floating layers (dropdowns, modals, toasts).

**Motion** — opacity, transform, colour and border-colour only. Scroll reveals are a 16px
translate-Y plus opacity, staggered 60ms, **fired once** via `IntersectionObserver` then
unobserved — re-animating on every scroll-past is nauseating and immediately reads as amateur.
Everything inside a `prefers-reduced-motion` guard, and **content must be fully visible with
JavaScript disabled**, so reveals start visible and JS opts them into hiding.

**Components specified in `01-DESIGN-SYSTEM.md`:** buttons (4 variants × 3 sizes × 6 states),
cards, forms, navigation, tables, modals, toasts, and full empty / loading / error / success
states for every list and form.

---

# M. Responsive Strategy

**Mobile-first CSS, `min-width` queries only.** A significant share of visitors — recruiters
and Cameroonian business owners alike — arrive on a mid-range Android phone over mobile data.
**Mobile is the primary experience, not a fallback.**

```
base    0–639px     mobile              4-col
sm      ≥640px      large phone         8-col
md      ≥768px      tablet portrait     8-col
lg      ≥1024px     laptop             12-col
xl      ≥1280px     desktop            12-col
2xl     ≥1536px     large — containers cap, type stops growing
```

| Area | Mobile | Tablet | Desktop |
|---|---|---|---|
| Nav | Hamburger → full-screen overlay | Condensed links | Full horizontal + CTA, sticky |
| Hero | 1-col, **portrait first**, stacked CTAs | 2-col 5/3 | 2-col 7/5, ~88vh |
| Tech strip | Scrollable row, edge fade, scroll-snap | Wrapped | Single centred row |
| Selected Work | Stacked, image above text | Stacked, wider | Alternating 2-col blocks |
| Skills | Accordion, first expanded | 2-col | 3–4-col grid |
| Services | 1-col | 2-col | 2×2 |
| Experience | Single-column timeline | Same, wider | Same — **never** a centred alternating timeline; hard to scan and dates the design |
| Case study | 1-col, inline ToC | Wider measure | Sticky sidebar ToC + 68ch |
| Admin tables | Stacked cards with `data-label` | Bounded horizontal scroll | Full table |

**Ten mobile rules to enforce:**
1. Never `100vh` for the hero — `100dvh` with a `min-height` fallback, or content-driven.
2. 44×44px minimum touch targets, 8px minimum spacing between adjacent targets.
3. No horizontal page scroll at any width, tested to 320px.
4. No hover-only information anywhere.
5. 16px minimum body font.
6. **16px minimum on form inputs** — below that, iOS Safari zooms on focus.
7. Explicit `width`/`height` and `aspect-ratio` on every image — zero CLS.
8. `env(safe-area-inset-*)` padding on fixed elements for notched devices.
9. Sticky header tap targets must clear browser chrome.
10. **Test on a real mid-range Android device**, not only devtools — devtools reproduces neither slow CPU, slow network, nor real font-loading behaviour.

---

# N. Accessibility

**Target: WCAG 2.1 Level AA**, with AAA on body-text contrast where it costs nothing.

**Structure** — one `<h1>` per page, no skipped heading levels, full landmark set, a skip-to-content link as the first focusable element, `lang="en"`, sections labelled via `aria-labelledby`.

**Keyboard** — everything reachable and operable; logical DOM-order tab sequence; **no positive `tabindex`**; visible `:focus-visible` everywhere (2px accent outline, 2px offset); focus traps in the mobile nav, modals and lightbox; focus restored to the trigger on close; `Escape` closes every overlay; **admin reordering never depends on drag-and-drop.**

**Screen readers** — meaningful `alt` on every content image (CMS-editable, required when a photo is present); `alt=""` on decorative; `aria-label` on icon-only buttons; real `<label for>`; errors linked via `aria-describedby` with `aria-invalid`; live regions (`role="status"` / `role="alert"`); `aria-current="page"`; `aria-expanded` on disclosures; the decorative availability dot `aria-hidden` with the status conveyed as text.

**Visual** — text ≥ 4.5:1 (large ≥ 3:1); UI components and focus indicators ≥ 3:1; **colour never the sole carrier of meaning** (status badges get an icon or text, tags get a border); layout holds at 200% zoom and 400% with reflow; readable in forced-colours mode.

**Motion & media** — `prefers-reduced-motion` honoured globally; no autoplay; nothing flashing above 3Hz; **all content reachable with JavaScript disabled.**

**Forms** — no placeholder-as-label; errors in text, not just colour; an error summary at the top linking to each field; autocomplete attributes; submitted values preserved.

**Per-phase verification:** axe DevTools clean · keyboard-only walkthrough · VoiceOver/NVDA pass on the homepage and one case study · 200% zoom · reduced-motion on · JavaScript off · Lighthouse accessibility 100.

---

# O. Performance

**Budgets are build-failing thresholds, not aspirations.** Set for a mid-range Android phone on
a throttled connection, because that is the real audience baseline — a fast-laptop measurement
is not evidence.

| Metric | Target | Hard limit |
|---|---|---|
| Largest Contentful Paint | < 1.8s | 2.5s |
| Interaction to Next Paint | < 100ms | 200ms |
| Cumulative Layout Shift | < 0.03 | 0.1 |
| First Contentful Paint | < 1.2s | 1.8s |
| Time to First Byte | < 400ms | 800ms |
| Homepage total transfer | < 500 KB | 900 KB |
| CSS (compressed) | < 30 KB | 50 KB |
| JavaScript (compressed) | < 25 KB | 40 KB |
| Fonts total | < 120 KB | 180 KB |
| Lighthouse Performance (mobile) | ≥ 95 | 90 |
| Database queries per page | ≤ 8 | 12 |

**Images** (the dominant cost in any portfolio) — AVIF → WebP → JPEG; three widths with
`srcset`/`sizes`; explicit dimensions everywhere; `loading="lazy"` + `decoding="async"` below
the fold; `fetchpriority="high"` and **no lazy-loading on the hero portrait** — lazy-loading
the LCP element is a classic self-inflicted regression. All derivatives generated at upload,
never on request.

**CSS** — one hand-written stylesheet. No framework: Tailwind's or Bootstrap's full build costs
more than this entire budget. Critical above-the-fold CSS inlined, the rest async. No `@import`.

**JavaScript** — vanilla, ES modules, `defer`. Total scope by design: mobile nav, scroll
reveals, scroll-spy, lightbox, form-submit state, admin upload preview, admin reordering.
Nothing else earns its bytes.

**Fonts** — self-hosted WOFF2 (avoids a third-party connection and a privacy question),
variable fonts, `font-display: swap`, `latin` + `latin-ext` subset (French accents matter for a
Cameroonian audience), preload the two used above the fold, and a metric-compatible fallback
stack so the swap does not reflow.

**Server** — OPcache; PDO prepared statements; indexed queries; **no N+1** — project
technologies and features fetched in one batched query per collection, not one per project
(*the single most likely performance bug in this design*); gzip/brotli; `immutable` caching on
versioned assets; a file-based page cache invalidated on every CMS write (§S.4).

**Measurement:** Lighthouse CI in GitHub Actions on every PR, failing below the hard limits,
plus a real-device check before each release.

---

# P. SEO

**Goal:** searching "Ramson Titih" returns this site first, with a rich preview. Secondary:
discoverability for "web developer Douala" / "PHP developer Cameroon" queries.

**Technical** — server-rendered HTML is already the strongest SEO property here: full content
in the initial response, no JS dependency. Plus: semantic slugs; one canonical URL per page;
a single host (301 from the other variant); HTTPS enforced; generated `sitemap.xml` covering
only published content with accurate `lastmod`; `robots.txt` disallowing `/admin`; `noindex` on
admin and previews; and **correct 404 status codes** — a 200 with "not found" text is a
surprisingly common bug that poisons indexing.

**On-page** — unique CMS-editable `<title>` ≤60 chars, pattern `{Page} — Ramson Titih | Software Engineer`; unique 150–160 char meta description; one `<h1>`; descriptive internal link text (never "click here"); descriptive image `alt`.

**Structured data** (JSON-LD, generated from CMS data so it never drifts from visible content):
`Person` on the homepage (`name`, `jobTitle`, `url`, `image`, `sameAs`, `address`, `alumniOf`,
`knowsAbout` from the skills table) · `CreativeWork` or `SoftwareApplication` per case study ·
`BreadcrumbList` · `ContactPoint`.

**Social preview** — full Open Graph (1200×630 image) and Twitter `summary_large_image`, every
field CMS-editable. **A per-project OG image generated from the project cover**, so a shared
case-study link previews with that project's screenshot. Validated against the Facebook Sharing
Debugger and Twitter Card Validator before launch — a broken OG image is invisible until
someone shares the link, by which point the impression is made.

**Future:** `hreflang` if a French version is added — worth serious consideration for a
Cameroonian client audience, and the reason templates must not hard-code English strings.

**Content SEO:** substantive case-study prose is the actual ranking asset. Thin project pages
will not rank; 800–1,500 honest words about a real system will.

---

# Q. Security

Threat model: a public PHP application with one privileged administrator, file uploads and a
public form. High-value targets are the admin session and the upload endpoint.

## Q.1 Authentication
`password_hash()` with `PASSWORD_DEFAULT`, cost tuned to ~250ms on the production host;
`password_verify()`; `password_needs_rehash()` on login to migrate cost transparently. **Never**
MD5, SHA-1 or anything hand-rolled. The admin account is created by a one-off CLI script —
**no public registration endpoint exists, and a route that does not exist cannot be attacked.**
Rate limiting 5 per 15 minutes on IP *and* account, with progressive delay. Generic failure
message plus a constant-time floor. Attempts logged with IP, user agent and timestamp.

## Q.2 Sessions
`cookie_httponly`, `cookie_secure`, `cookie_samesite=Strict`, `use_strict_mode`,
`use_only_cookies`, a non-default session name, and a save path outside the web root.
**`session_regenerate_id(true)` on login** — the fixation defence. Idle timeout 2h, absolute 12h,
both enforced server-side (a client-side timer is decoration). Session bound to a user-agent
hash. Logout destroys the server-side session *and* expires the cookie.

## Q.3 CSRF
Per-session token in **every** state-changing form, compared with `hash_equals()`. **Every
mutation is POST/PUT/DELETE — no GET ever changes state.** `SameSite=Strict` plus an
`Origin`/`Referer` check on admin mutations as defence in depth.

## Q.4 Injection
**PDO prepared statements with bound parameters for 100% of queries.** No string interpolation
of user input into SQL, ever — not even for "safe-looking" integers.
`PDO::ATTR_EMULATE_PREPARES => false`, `ERRMODE_EXCEPTION`, `FETCH_ASSOC`. Identifiers that
genuinely cannot be bound (`ORDER BY` column, sort direction) validated against a hard
allowlist. **A dedicated database user with only `SELECT, INSERT, UPDATE, DELETE` on the
portfolio schema — not `root`.** No `DROP`, no `ALTER`, no `FILE`, no other databases.

## Q.5 XSS
`htmlspecialchars($v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')` on every output via a single `e()`
helper, so it cannot be forgotten inconsistently. **Context-correct escaping** — HTML body,
attribute, URL (`rawurlencode`) and JavaScript (`json_encode` with the HEX flags) are different
escapes, and using the wrong one is a real vulnerability. Markdown rendered server-side then
passed through an HTML sanitiser with a tag/attribute allowlist. A strict CSP:

```
default-src 'self'; script-src 'self'; style-src 'self'; img-src 'self' data:;
font-src 'self'; object-src 'none'; base-uri 'self'; frame-ancestors 'none';
form-action 'self'
```

**No `unsafe-inline`** — which is precisely why there is no inline JavaScript anywhere, and why
inlined critical CSS carries a nonce.

## Q.6 File uploads
Full pipeline in §J.4. The five decisive controls: **real MIME via `finfo`** · **always
re-encode through GD** · **cryptographically random server-generated filenames** · **store
outside the web root and serve where `.htaccess` denies PHP execution** · **strict size,
dimension and pixel-count bounds.**

## Q.7 Transport & headers
HTTPS enforced with a 301; HSTS `max-age=31536000; includeSubDomains` (`preload` only once
verified). On every response: `X-Content-Type-Options: nosniff`, `X-Frame-Options: DENY`,
`Referrer-Policy: strict-origin-when-cross-origin`,
`Permissions-Policy: geolocation=(), camera=(), microphone=()`, plus the CSP.
`X-Powered-By` and the Apache banner suppressed.

## Q.8 Public contact form
Honeypot (hidden from sight **and** assistive technology, `tabindex="-1"`) · submission-timing
check (a sub-2-second fill is a bot) · 3 submissions per IP per hour · length caps ·
`filter_var(FILTER_VALIDATE_EMAIL)` · **header injection prevented by never placing user input
in a mail header** · stored with the submitting IP for abuse handling · CSRF-protected · no
unescaped reflection.

`[DECISION: start without a CAPTCHA. Honeypot + timing + rate limiting handles the volume a
personal site attracts, and a CAPTCHA costs real conversions and accessibility. Revisit only
if spam actually materialises.]`

## Q.9 Configuration & errors
Credentials in a git-ignored `config/local.php`, **never committed**;
`config/local.example.php` committed as the template; `.gitignore` present from the first
commit. Production: `display_errors=0`, `log_errors=1`, logs outside the web root, a custom
handler rendering the styled 500 page with a reference ID and no internal detail.
`error_reporting(E_ALL)` in development. Directory listing disabled. `.git`, `config/`,
`storage/` and `src/` unreachable over HTTP **because only `public/` is the document root.**
Permissions `644`/`755`; uploads writable but **never** executable; **no `chmod 777` anywhere.**

## Q.10 Dependencies & operations
Minimal surface by design (a Markdown parser, an HTML sanitiser, possibly a mailer). Committed
`composer.lock`; `composer audit` in CI. Automated database backups with a documented **and
tested** restore — an untested backup is not a backup — and an `uploads/` backup alongside it,
since the two are only meaningful together.

---

# R. Database Entities

MySQL 8 / MariaDB, `utf8mb4_unicode_ci`, InnoDB, UTC timestamps.
**Recommendation only — no schema is created until Phase 3.**

| # | Entity | Purpose | Notes |
|---|---|---|---|
| 1 | `admin_users` | The single administrator | One row. No public registration |
| 2 | `login_attempts` | Brute-force defence | Indexed on (identifier, attempted_at); pruned |
| 3 | `sessions` *(optional)* | DB-backed sessions | Enables "sign out other devices" |
| 4 | `profile` | Identity singleton | `photo_media_id` and `cv_media_id` are nullable FKs to `media` |
| 5 | `media` | **Every** uploaded file | uuid, mime, dimensions, bytes, `variants` JSON, alt text. **The backbone of the no-hard-coded-image rule** — every image reference in the system is an FK here, never a string path in code |
| 6 | `social_links` | Ordered social profiles | |
| 7 | `skill_categories` | Skill grouping | Frontend, Backend, Database, Tools, Practices |
| 8 | `skills` | Canonical technology vocabulary | Shared by the skills section, project tags and JSON-LD. **No proficiency column — deliberately** |
| 9 | `projects` | Core content type | `is_published` and `is_featured` independent; `deleted_at` soft delete |
| 10 | `project_sections` | Case-study body | **Row-per-section, not 18 columns** — new section types need no migration, empty sections have no row, ordering is data |
| 11 | `project_features` | Feature bullets | |
| 12 | `project_technologies` | Project ↔ skill join | `is_primary` drives the compact card's tags |
| 13 | `project_images` | Gallery | FK to `media`, with caption, alt text, order |
| 14 | `experience` | Roles / education / milestones | Holds the HND at Saint Louis University Institute Douala |
| 15 | `services` | Client offerings | |
| 16 | `process_steps` | "How I Work" | |
| 17 | `messages` | Contact inbox | read / archived / spam, IP, user agent |
| 18 | `settings` | Global key-value config | |
| 19 | `page_meta` | Per-page SEO overrides | |
| 20 | `activity_log` *(Phase 8+)* | Audit trail | "what did I change and when" |

**Integrity rules:** `ON DELETE RESTRICT` where loss would be destructive (**a `media` row in
use cannot be deleted**); `ON DELETE CASCADE` for genuinely owned children
(`project_sections`, `project_features`, `project_images`). Unique indexes on every slug and on
`admin_users.email`. Composite indexes on `(status, is_featured, sort_order)` for the homepage
query and `(project_id, sort_order)` on every child table. `sort_order` on every ordered
entity. `NOT NULL` by default, with nullability a deliberate decision.

---

# S. Technical Architecture

## S.1 Stack rationale

PHP 8.2+, MySQL, vanilla JS, hand-written CSS, server-rendered. Composer for autoloading and
two small libraries. **No framework.**

The requirements are a public site of roughly ten templates and a single-user CMS. Laravel
would add a large dependency surface, a deployment story XAMPP and shared hosting handle
awkwardly, and a learning curve — in exchange for capabilities this project does not need. A
clean layered PHP application also demonstrates *more* engineering ability to a hiring manager,
because the architecture is visibly Ramson's own.

**The honest counter-argument:** Laravel is a marketable skill worth learning — on a project
where its strengths actually apply. That is a deliberate next step, not this project.

## S.2 Folder structure

**Only `public/` is web-accessible.** This one decision eliminates an entire vulnerability
category.

```
ramson-portfolio/
├── public/                      ← DOCUMENT ROOT
│   ├── index.php                    single front controller
│   ├── .htaccess                    rewrite all → index.php; security headers
│   ├── assets/
│   │   ├── css/   main.css, admin.css
│   │   ├── js/    app.js, admin.js, modules/
│   │   ├── fonts/                   self-hosted WOFF2
│   │   └── img/                     static UI art only — NEVER profile photos
│   ├── uploads/
│   │   └── .htaccess                php_flag engine off; Options -Indexes
│   ├── robots.txt
│   └── favicon.ico
│
├── src/
│   ├── Core/          Router, Request, Response, Container, View, Config, Database,
│   │                  Session, Csrf, Validator, Auth, Flash, Logger, ErrorHandler
│   ├── Repositories/  Profile, Project, Skill, Experience, Service, Process,
│   │                  Message, Setting, Media
│   ├── Services/      ImageUploadService, ImageProcessor, MarkdownRenderer,
│   │                  HtmlSanitizer, MailService, SitemapGenerator, SeoService,
│   │                  CacheService, SlugGenerator
│   ├── Controllers/
│   │   ├── Public/    Home, Project, About, Contact, Sitemap, Error
│   │   └── Admin/     Auth, Dashboard, Profile, Project, Media, Skill,
│   │                  Experience, Service, Process, Message, Setting
│   ├── Middleware/    Auth, Csrf, SecurityHeaders, RateLimit, Maintenance
│   └── Models/        plain data objects / DTOs
│
├── templates/
│   ├── layouts/       public, admin, auth, error
│   ├── partials/      header, footer, nav, meta, json-ld, flash, pagination
│   ├── components/    button, card, project-card-featured, project-card-compact,
│   │                  tag, avatar, empty-state, skeleton, modal, toast, form-field
│   ├── pages/         home, projects-index, project-show, about, services,
│   │                  contact, 404, 500
│   └── admin/         login, dashboard, profile/, projects/, skills/, …
│
├── config/
│   ├── app.php                  committed defaults
│   ├── local.example.php        committed template
│   └── local.php                ← GIT-IGNORED. Credentials live only here
│
├── database/
│   ├── migrations/              numbered, forward-only SQL
│   ├── seeds/                   skill categories, skills, settings defaults
│   └── schema.sql               current full schema, for reference
│
├── storage/                     ← never web-accessible
│   ├── uploads/  cache/  logs/  sessions/
│
├── scripts/                     create-admin, reset-password, generate-sitemap,
│                                clear-cache, build-assets
├── tests/
└── docs/portfolio/
```

## S.3 Request lifecycle

```
Browser
 → Apache (public/.htaccess rewrites everything to index.php)
   → public/index.php
     → bootstrap: config, error handler, session, lazy database connection
     → Router: method + path → controller@action
     → Middleware: SecurityHeaders → Maintenance → [Auth] → [Csrf] → [RateLimit]
     → Controller: validate → repository/service → view model
     → Repository: PDO prepared statement → data objects
     → View: template + layout, EVERY output escaped through e()
     → Response: status, headers, body
 ← Browser
```

One front controller, one routing table, one place where every security header is set, one
escaping helper. A new page is a route plus a controller plus a template — the architecture is
never re-derived.

## S.4 How the public site and the CMS communicate

**They do not talk to each other. They share a database, and nothing else.** No internal HTTP
calls, no shared session, no shared state. **This is the single most important architectural
decision in the project.**

```
   ┌────────────────────────┐        ┌────────────────────────┐
   │  PUBLIC SITE           │        │  ADMIN CMS             │
   │  read-only             │        │  read + write          │
   │  no session required   │        │  session + CSRF        │
   └───────────┬────────────┘        └───────────┬────────────┘
               │        ┌──────────────┐         │
               └───────►│ REPOSITORIES │◄────────┘
                        │ (the ONLY    │
                        │  SQL layer)  │
                        └──────┬───────┘
                               ▼
                        ┌──────────────┐
                        │  MySQL + FS  │
                        └──────────────┘
```

Consequences:
- **A shared repository layer means a field added once serves both sides.** No duplicated SQL, no drift.
- **The public site never writes**, with exactly one exception: inserting a contact message. Its credentials could even be a separate read-mostly database user — a meaningful hardening step.
- **Publication filtering lives in the repository**, not in templates, so a draft cannot leak through a forgotten template condition. Public methods are `findPublished*`; admin methods are `findAll*`. **Two distinct names make the mistake hard to make.**
- **Cache invalidation is the communication channel.** Every admin write calls `CacheService::invalidate()` for the affected keys. This is why a change appears immediately despite caching.
- **Preview is the one controlled crossing:** the admin renders a *public* template against unpublished data, reached through a signed, short-lived token, with `noindex` on the response.

## S.5 Profile data flow — ADMIN → DATABASE → PUBLIC WEBSITE

```
1  ADMIN       /admin/profile — edit fields, select a photo.
               Client-side preview via URL.createObjectURL(). Nothing persisted yet.

2  SUBMIT      POST /admin/profile (multipart)
               AuthMiddleware → session valid?   CsrfMiddleware → token valid?

3  VALIDATE    Text: required, length, email, URL, enum membership.
               File: the full 11-step pipeline (§J.4).
               On failure → re-render with values intact + field errors.
               NOTHING is written. The live site is untouched.

4  PROCESS     ImageUploadService:
                 decode with GD → re-encode (EXIF stripped)
                 → derive hero / about / thumb / og → write WebP + JPEG
                 → storage/uploads/profile/{random}-{variant}.{ext}
                 → copy to public/uploads/profile/

5  PERSIST     BEGIN TRANSACTION
                 INSERT INTO media (...)              → media_id
                 UPDATE profile SET name=?, title=?, …,
                        photo_media_id = :media_id,
                        photo_alt = ?, updated_at = NOW()
               COMMIT
               (rollback ⇒ no orphan row, old photo still live)

6  CLEAN       Old media files deleted ONLY after a successful commit.

7  INVALIDATE  CacheService::invalidate(['home','about','contact','layout'])
               Image URLs versioned by media.updated_at, so the browser fetches
               the new file immediately despite immutable caching.

8  PUBLIC READ GET /  → HomeController
                 → ProfileRepository::current()  (1 query, joined to media)
                 → view model { name, title, tagline, photo: Media|null, … }

9  RENDER      templates/components/avatar.php receives the Media object:
                 photo === null → monogram fallback tile
                 otherwise      → <picture> AVIF/WebP/JPEG, 3 widths,
                                  explicit w/h, alt from photo_alt, ?v={updated_at}

10 EVERYWHERE  Hero, About, footer, admin header, OG image and JSON-LD `image`
               all resolve through the SAME accessor. One upload updates all of
               them, because none of them knows a filename.
```

**The invariant:** grep `public/` and `src/` for an image filename and find nothing. Every
image is a foreign key resolved at render time. That is what makes the requirement
*structurally* true rather than merely currently true.

## S.6 Project data flow — ADMIN → DATABASE → PROJECT PAGES

```
1  ADMIN       /admin/projects/{id}/edit — tabbed form.
               Each tab saves independently, so a long case study is never held
               hostage by an unrelated validation error on another tab.

2  SUBMIT      POST per tab. Auth + CSRF on every one.

3  VALIDATE    Slug unique, URL-safe, auto-generated, editable. Status enum.
               URLs format-checked. Markdown length-capped.
               Media ids verified to exist in `media`.

4  PERSIST     BEGIN TRANSACTION
                 UPDATE projects SET …
                 UPSERT project_sections      (row per non-empty section)
                 REPLACE project_technologies
                 UPSERT project_features
                 UPSERT project_images
               COMMIT

5  DERIVE      Recompute reading_time_minutes. Regenerate sitemap if the
               published set changed.

6  INVALIDATE  CacheService::invalidate([
                 'home', 'projects-index', "project-{slug}", 'sitemap', 'json-ld'
               ])

7  PREVIEW     Draft viewable through the real public template via a signed,
               expiring token. Response carries noindex. Nothing is published.

8  PUBLISH     status = 'published' → routable, and appears in homepage
               and index queries.

──────────────── PUBLIC READ PATH ────────────────

HOMEPAGE   GET /
  ProjectRepository::findFeaturedForHome(limit: 4)
    SELECT … FROM projects
    WHERE status='published' AND deleted_at IS NULL
    ORDER BY is_featured DESC, sort_order ASC
    → then ONE batched query for primary technologies across those ids
      (WHERE project_id IN (…)) — never one query per project
  → 2 featured blocks + up to 2 compact cards

INDEX      GET /projects
  findAllPublished(page, technologyFilter)
  → grid of compact cards, filterable via the join table

CASE STUDY GET /projects/{slug}
  findPublishedBySlug($slug)
    → real 404 if missing, unpublished or soft-deleted
    → 5 queries total: project, sections, features, technologies, images
  → render sections IN sort_order, SKIPPING every empty one
  → Markdown → HTML → sanitiser allowlist → escaped output
  → JSON-LD CreativeWork + BreadcrumbList from the same data
  → prev/next from sort_order
```

**Two guarantees:** an unpublished project is unreachable by URL (repository-level filtering,
not template-level), and an empty case-study section renders nothing at all — no orphan
heading, no "TBD".

---

# T. Development Roadmap

Ten phases. Each is independently valuable and ends with something demonstrably working.
Status tracked in `02-ROADMAP.md`.

| Phase | Deliverable |
|---|---|
| **0 · Documentation & Foundation** ✅ | This repository, README, `.gitignore`, the docs set |
| **1 · Design Foundation** | Static style guide + static homepage + static case-study page. **No backend.** The design system is the hardest thing to retrofit; getting type, spacing and the hero right in static HTML, where iteration is cheap, is what decides whether the finished site looks premium |
| **2 · Application Skeleton** | Front controller, router, config, PDO wrapper, view renderer with `e()`, error handler, security headers, `.htaccess`, `composer.json`, error pages — Phase 1 HTML converted into templates |
| **3 · Database & Read Path** | Migrations, seeds, repositories. The public site reads **everything** from the database |
| **4 · Auth & Admin Shell** | CLI admin creation, login/logout, sessions, CSRF, rate limiting, admin layout, dashboard with the completeness checklist |
| **5 · Profile & Media** ⭐ | The `media` table, the full upload pipeline, variants, the photo upload/preview/replace/remove flow, monogram fallback, versioned URLs. **Before projects, because `media` is the abstraction every later phase depends on** |
| **6 · Projects & Case Studies** ⭐ | Projects CRUD, tabbed editor, sections, features, tagging, gallery, reordering, draft preview, Markdown + sanitisation, public case-study rendering. **Rendo and the School Management System entered entirely through the CMS. This is where the portfolio becomes genuinely usable** |
| **7 · Remaining Content** | Skills, experience, services, process, social links, settings, per-page SEO. Zero hard-coded content anywhere |
| **8 · Contact System** | Public form with honeypot/timing/rate limiting, database storage, email notification, admin inbox |
| **9 · SEO / Performance / A11y Hardening** | JSON-LD, OG, per-project OG images, sitemap; image optimisation, critical CSS, caching, N+1 elimination; full accessibility audit; Lighthouse CI |
| **10 · Deployment & Launch** | Hosting, domain, HTTPS + HSTS, backups with a **tested** restore, real content, cross-browser and real-device testing, OG validation |

**Phases 1–6 are the launchable product.** 7–10 are hardening. Ship early, improve in public.

**Post-launch, in priority order:** CV upload · light theme · **French translation** (genuinely
valuable for the Cameroonian client audience — and the reason §P warns against hard-coding
English in templates) · project filtering · a blog if he wants to write · activity log ·
privacy-respecting analytics.

---

# U. What to Avoid

## U.1 Design mistakes that make a portfolio read as a student template

**Layout & spacing**
1. **Cramped vertical section padding — the single most common tell.** Premium sites breathe; template sites stack.
2. Everything centred. Confident design uses asymmetry and left-aligned text blocks.
3. Uniform 6/6 splits and identical card grids in every section — a page with no rhythm.
4. Full-width prose with no measure limit.
5. Visible Bootstrap or unmodified Tailwind defaults — recognisable instantly, and that recognition is the whole problem.

**Typography**
6. A single sans at 16px/24px for everything, with no display scale.
7. **Timid headings.** If the hero name is 32px, the page has already lost.
8. Default loose tracking and leading on large display type.
9. More than three weights, or a 700-weight display serif.
10. All-caps or letter-spaced body copy.

**Colour & effects**
11. **Default Bootstrap blue `#007bff`** — the universal marker of an unstyled student project.
12. Purple-to-pink gradients, especially on buttons and hero backgrounds.
13. Neon-on-black "hacker" aesthetics, glowing text, matrix backgrounds.
14. Heavy glassmorphism everywhere instead of on one deliberate surface.
15. **Accent colour on every element — which means it accents nothing.**
16. Coloured drop shadows and stacked box-shadows simulating depth.

**Content & credibility**
17. **Skill percentage bars.** "JavaScript 85%" is unverifiable, meaningless, and reads as junior to every engineer who sees it. The easiest high-impact removal.
18. **Fabricated statistics** — "50+ projects", "30 happy clients", "5 years experience". One caught invented number discards the whole site.
19. **Invented testimonials.** Disqualifying if discovered.
20. "Hi, I'm X, a passionate developer who loves to code" — the exact sentence in thousands of student portfolios.
21. **Padding the projects section with tutorial clones** (to-do apps, calculators, weather widgets). Two real projects with depth beat ten small ones.
22. Listing every technology ever touched, including things used once.
23. A "Download CV" that 404s, or links that open nothing.
24. Lorem ipsum or "Coming soon" in production.

**Motion & imagery**
25. **Entrance animations that re-fire every time a section scrolls into view.**
26. Typewriter headlines and animated text carousels.
27. Custom cursors, trailing blobs, mouse-following gradients.
28. Parallax on everything.
29. Generic stock illustrations (flat purple undraw.co people) and cliché developer art: floating code windows, hooded hackers, binary rain, robot mascots.
30. A circular avatar with a hard-edged cut-out from a badly background-removed photo.
31. A preloader on a site that loads in under a second.

**Technical**
32. Layout shift as images load (no explicit dimensions).
33. Hover-only information, invisible on every touch device.
34. Horizontal scroll at mobile widths.
35. `outline: none` on focus with no replacement — breaks keyboard use and fails WCAG.
36. A 4 MB unoptimised hero photograph.
37. Inline styles scattered through the HTML, competing with the external stylesheet.

## U.2 Architectural mistakes to avoid on this build
38. **Hard-coding the profile image path "just for now"** — it always survives to production.
39. String-concatenated SQL anywhere, even for integers.
40. Skipping CSRF "because only I use it".
41. Trusting `$_FILES['type']` or a file extension.
42. Storing uploaded bytes without re-encoding.
43. Mutating state via GET.
44. **Building the CMS before the design system**, which guarantees a CMS-shaped public site.
45. Committing credentials.
46. N+1 queries in the project loop.
47. A WYSIWYG editor producing unpredictable, unsanitised HTML.
48. Adding Laravel, React or Tailwind because they are fashionable, when the requirements do not call for them.

---

# V. Professional Recommendations

## V.1 The five decisions that determine whether this looks premium

1. **Section vertical padding at `clamp(5rem, 10vw, 10rem)`**, applied consistently. One value; highest leverage on the entire site.
2. **Serif display against sans body.** The fastest escape from the all-sans developer look.
3. **A tight display scale** — hero name at `clamp(2.75rem, 7vw, 5.25rem)`, `-0.03em` tracking, `~1.0` leading. Large type with tight tracking reads as designed; with default tracking it reads as unstyled.
4. **Hairline borders instead of shadows, and a hard three-element-per-viewport limit on `#2DD4A7`.** Restraint is the signal.
5. **A rectangular editorial portrait frame.** Magazine feature, not social profile.

## V.2 The three content decisions that determine whether it converts

1. **Depth over breadth.** Two genuinely deep case studies on Rendo and the School Management System will outperform eight shallow cards. **"Key Decisions" and "Lessons Learned" are where engineering judgement becomes visible — and judgement, not output, is what gets people hired.**
2. **Absolute honesty about scale and status.** "Functional prototype", "in development", "built for a secondary-school workflow" are all respectable. One invented number destroys everything else on the page. A hard constraint, not a stylistic preference.
3. **WhatsApp as a first-class contact channel.** For the Cameroonian client audience this is not a nice-to-have; it is the primary business channel, and its absence is a real conversion cost.

## V.3 The main risk to manage

**Scope creep before launch.** The temptation will be to keep adding sections. A live site with
two deep case studies beats an unlaunched site with ten planned ones. Phases 1–6 are the
launchable product.

---

*Document status: approved plan, recreated in the official repository. Implementation begins
at Phase 1.*
