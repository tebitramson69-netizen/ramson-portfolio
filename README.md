# Ramson Titih — Portfolio

Personal professional portfolio website with a secure, self-hosted CMS.

**Tebit Ramson Titih · Software Engineer / Full-Stack Developer — Cameroon**

> I build practical web systems that turn manual workflows into simple digital experiences.

Full-stack development · PHP · JavaScript · MySQL · AI & Automation

---

## Status

**Phase 5 — Profile management and media.** Complete.

The public site runs as a server-rendered PHP application: front controller,
router, repositories, view layer, security headers, and a database foundation
with migrations. The admin area is live — Argon2id sign-in, a kernel-enforced
route guard, CSRF on every POST, a dashboard, and a profile editor that
uploads, previews, replaces and removes the profile photograph. Uploads are
validated in eight steps, re-encoded (which strips EXIF), and derived into
sized variants that no template ever names by filename.

Project and case-study editing is Phase 6; project content is still edited via
SQL until then.

See [`docs/portfolio/02-ROADMAP.md`](docs/portfolio/02-ROADMAP.md) for the
current phase and what ships next.

---

## What this is

A server-rendered PHP/MySQL portfolio site with a private admin area, where
**every piece of public content is editable from the CMS** — no source edits,
no redeploys, no hard-coded text or image paths.

The CMS is not an add-on. It is the second piece of portfolio evidence: a
custom-built PHP application demonstrating authentication, file uploads,
validation, and layered architecture.

### Core capabilities (planned)

| Area | Capability |
|---|---|
| Public site | Hero, selected work, about, skills, process, services, experience, contact |
| Case studies | Dedicated per-project pages with an 18-section structured narrative |
| CMS | Profile, projects, skills, experience, services, process, messages, settings |
| Profile photo | Upload, preview, replace, remove — resolved from the database, never hard-coded |
| Media | Central `media` table; every image is a foreign key, never a path string |
| Security | PDO prepared statements, CSRF on every mutation, hardened upload pipeline |

---

## Stack

- **PHP 8.2+** — server-rendered, no framework
- **MySQL 8 / MariaDB** — InnoDB, `utf8mb4_unicode_ci`
- **Vanilla JavaScript** — ES modules, no framework, no jQuery
- **Hand-written CSS** — custom properties, no Bootstrap, no Tailwind
- **Composer** — autoloading plus two small libraries (Markdown, HTML sanitiser)
- **Apache / XAMPP** — local development on Windows

**Why no framework:** the requirements are a public site of roughly ten templates
and a single-user CMS. A clean layered PHP application serves that better than a
large dependency surface, deploys anywhere, and makes the architecture visibly
the author's own. See §S.1 of the PRD for the full reasoning, including the
honest counter-argument.

---

## Documentation

| Document | Contents |
|---|---|
| [`docs/portfolio/00-PRD-AND-PLAN.md`](docs/portfolio/00-PRD-AND-PLAN.md) | Product requirements, IA, architecture, security, database entities, roadmap |
| [`docs/portfolio/01-DESIGN-SYSTEM.md`](docs/portfolio/01-DESIGN-SYSTEM.md) | Locked design tokens: colour, typography, spacing, components |
| [`docs/portfolio/02-ROADMAP.md`](docs/portfolio/02-ROADMAP.md) | Ten phases with deliverables and status |
| [`docs/portfolio/03-OPEN-QUESTIONS.md`](docs/portfolio/03-OPEN-QUESTIONS.md) | Information still needed, grouped by what it blocks |
| [`docs/portfolio/04-CONTENT-INVENTORY.md`](docs/portfolio/04-CONTENT-INVENTORY.md) | Every content claim, marked verified or outstanding |
| [`docs/portfolio/05-ARCHITECTURE.md`](docs/portfolio/05-ARCHITECTURE.md) | Architecture: decisions, alternatives, trade-offs, setup, verification |

---

## Planned structure

Only `public/` is web-accessible. Source, configuration, storage and `.git`
are unreachable over HTTP — a deliberate decision that removes an entire
category of vulnerability.

```
ramson-portfolio/
├── public/          ← DOCUMENT ROOT (front controller, assets, uploads)
├── src/             Core, Repositories, Services, Controllers, Middleware
├── templates/       layouts, partials, components, pages, admin
├── config/          app.php, local.example.php  (local.php is git-ignored)
├── database/        migrations, seeds, schema.sql
├── storage/         uploads, cache, logs, sessions   (not web-accessible)
├── scripts/         create-admin, reset-password, generate-sitemap
├── tests/
└── docs/portfolio/
```

---

## Local development

```bash
# 1. Clone into the XAMPP web root
cd C:\xampp1\htdocs
git clone https://github.com/tebitramson69-netizen/ramson-portfolio.git
cd ramson-portfolio

# 2. Create the local config — git-ignored, the only place credentials live
copy config\config.example.php config\config.php
#    then edit it with your database name, user and password

# 3. Create the database, then run migrations and seeds
php bin/migrate.php --seed
php bin/migrate.php --status

# 4. Serve it
#    Either point an Apache virtual host at public/ …
#    … or run without Apache:
php -S localhost:8000 -t public bin/dev-server.php

# 5. Create the administrator account (CLI only — there is no sign-up route)
php bin/create-admin.php
```

Verify the whole install in one command (Windows/XAMPP):

```powershell
powershell -ExecutionPolicy Bypass -File bin\verify-local.ps1
```

Verify the image pipeline on any platform — it touches no database and no
files of yours:

```bash
php bin/verify-media.php
```

No `composer install` is needed: the project has no runtime dependencies yet
and ships a small PSR-4 autoloader. Composer takes over automatically once
`vendor/` exists.

> **Apache note.** The document root must be `public/`, not the project root.
> On XAMPP that means a virtual host. Serving the project root would expose
> `config/`, `src/`, `storage/` and `.git` over HTTP.

> **Antivirus note (Windows).** Avast, AVG and some Defender configurations
> flag `php.exe` as `IDP.Generic` through Behavior Shield when it opens a
> listening socket — which is exactly what `bin/dev-server.php` makes it do.
> The symptom is not an error message. PHP is suspended, so HTTP requests
> return nothing at all and CLI scripts hang without ever reading input;
> `verify-local.ps1` reports `got 0` and `bin/create-admin.php` stops dead at
> a prompt.
>
> The alert names a `.php` file, but the verdict is on the **process**, not on
> that file's contents — the file is only what `php.exe` had open at the time.
> So the exception has to cover the executables. In Avast:
> **Menu → Settings → General → Exceptions → Add exception**, which accepts a
> file *or* a folder path, typed or browsed. Add `C:\xampp1` as one entry —
> that covers `php.exe`, `httpd.exe` and `mysqld.exe` together, so the next
> detection on Apache or MySQL does not start this over. (Not "Blocked &
> Allowed apps": that list shows installed applications and will not surface a
> loose `.exe` inside XAMPP.)
>
> The cost is worth stating: XAMPP, `htdocs` included, is then unscanned.
> Normal on a development machine, but it is a real reduction in protection.

---

## Conventions

- **Every** database query uses PDO prepared statements. No string interpolation of user input into SQL, ever.
- **Every** output is escaped through the `e()` helper, in the correct context (HTML, attribute, URL, JS).
- **Every** state-changing request is POST and carries a CSRF token. No GET ever mutates state.
- **No** image filename appears in PHP, HTML, CSS or JS. Images resolve through the `media` table.
- **No** invented content. Every claim on the public site is verifiable — see the content inventory.

---

## Author

**Ramson Titih** — Software Engineer / Full-Stack Developer
HND Software Engineering student, Saint Louis University Institute Douala, Cameroon

---

## License

Not yet chosen. The source is public; the written content, case studies and
images are the author's own work and are not licensed for reuse.
