# Development Roadmap

Ten phases. Each is independently valuable and ends with something demonstrably working — no
phase leaves the project in a broken state.

**Current phase: 0 — complete. Awaiting confirmation to begin Phase 1.**

| Phase | Name | Status |
|---|---|---|
| 0 | Documentation & Foundation | ✅ Complete |
| 1 | Design Foundation | ⬜ Not started |
| 2 | Application Skeleton | ⬜ Not started |
| 3 | Database & Read Path | ⬜ Not started |
| 4 | Authentication & Admin Shell | ⬜ Not started |
| 5 | Profile Management & Media ⭐ | ⬜ Not started |
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

## Phase 1 — Design Foundation ⬜

**No backend. Static HTML and CSS only.**

1. `public/assets/css/main.css` — every token from `01-DESIGN-SYSTEM.md`
2. A static style-guide page — all colours, the full type scale, spacing, every button state, cards, forms, tags, empty and loading states
3. A static, fully responsive homepage — hero, tech strip, selected work (placeholder content **clearly marked as placeholder**), about, skills, process, services, experience, contact, footer
4. A static case-study page

**Why first:** the design system is the hardest thing to retrofit. Getting type, spacing and
the hero right in static HTML — where iteration is cheap — before any PHP exists is the
difference between a premium result and a compromised one.

**Blocked on:** questions 1–4 in `03-OPEN-QUESTIONS.md`.

**Deliverable:** a static site that already looks like the finished product.

---

## Phase 2 — Application Skeleton ⬜

Folder structure · front controller · router · config layer · PDO wrapper · view renderer with
the `e()` helper · error handler · security-headers middleware · `public/.htaccess` ·
`composer.json` · `config/local.example.php` · 404/500 pages · Phase 1 HTML converted into
templates and partials.

**Deliverable:** the static site running through the real application architecture.

---

## Phase 3 — Database & Read Path ⬜

Migrations for all entities in PRD §R · seeds · the repository layer · the public site reads
**everything** from the database.

**Deliverable:** a fully database-driven public site (edited via SQL for now).

---

## Phase 4 — Authentication & Admin Shell ⬜

`scripts/create-admin.php` · login/logout · sessions · CSRF · rate limiting · auth middleware ·
admin layout and navigation · dashboard with counts and the content-completeness checklist.

**Deliverable:** a secure, empty CMS he can log into.

---

## Phase 5 — Profile Management & Media ⭐ ⬜

The `media` table · `ImageUploadService` with the complete 11-step validation pipeline ·
variant generation · the profile editor · upload / preview / replace / remove · the monogram
fallback · versioned-URL cache strategy.

**Why here and not later:** this is the requirement Ramson emphasised most, and `media` is the
abstraction every subsequent phase depends on. Building projects first would mean retrofitting
image handling into project management.

**Acceptance test:** `grep` finds no image filename anywhere in `public/` or `src/`.

**Deliverable:** he changes his photo from the admin and it updates everywhere.

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
