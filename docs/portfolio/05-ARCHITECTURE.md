# Architecture (Phase 2)

How the application is put together, and why. Every decision below records the
alternatives considered and the trade-off accepted, so the reasoning can be
argued with later rather than merely inherited.

**Status:** Phase 2 complete. No CMS, no authentication UI, no uploads yet.

---

## 1. Directory layout

Only `public/` is exposed by the web server. Everything else sits above the
document root and is unreachable over HTTP.

```
ramson-portfolio/
├── public/              ← DOCUMENT ROOT — point Apache here, not at the repo root
│   ├── index.php            front controller; the only entry point
│   ├── .htaccess            rewrite, security headers, cache policy
│   ├── assets/              css, js (static, served by Apache)
│   ├── uploads/             CMS media; .htaccess disables execution
│   └── styleguide.html      dev-only design reference (noindex)
│
├── src/
│   ├── Core/                Kernel, Router, Request, Response, View, Config,
│   │                        Database, Session, Csrf, SecurityHeaders,
│   │                        ErrorHandler, Seo, Autoloader
│   ├── Domain/              Media, Profile, Settings — entities + repositories
│   ├── Http/Controllers/    request → view model → response
│   └── Support/helpers.php  e(), e_url(), e_js(), asset(), route_url()
│
├── templates/               layouts, partials, components, pages, errors
├── config/                  app.php (committed) · config.php (GIT-IGNORED)
├── database/migrations/     numbered, forward-only .sql
├── database/seeds/          idempotent PHP seeds
├── storage/                 logs, cache, sessions, uploads — never web-served
├── routes/web.php           the route table
├── bin/                     migrate.php, dev-server.php
└── docs/portfolio/
```

The project **root** carries `Require all denied`, and `public/.htaccess`
grants access back for the one directory that is meant to be reachable. Every
non-public directory carries its own deny-all as well.

### Two supported layouts, one set of files

| Layout | DocumentRoot | URL |
|---|---|---|
| Virtual host *(preferred)* | `…/ramson-portfolio/public` | `http://portfolio.test/work/rendo` |
| Subdirectory *(XAMPP default)* | `…/htdocs` | `http://localhost/ramson-portfolio/public/work/rendo` |

Both are **verified on Apache 2.4.58** with `mod_rewrite` and `mod_headers`.

**`public/.htaccess` must not set `RewriteBase`.** In per-directory context
mod_rewrite resolves a relative substitution against the directory the
`.htaccess` sits in; letting Apache determine that is what makes one file work
for both layouts. An earlier version set `RewriteBase /`, which pins the base
to the *server* root — so `RewriteRule ^ index.php` became `/index.php`, the
document root's own index rather than the project's. Measured: a request for
`/ramson-portfolio/public/work/rendo` was served by `htdocs/index.php`.

**`public/uploads/.htaccess` must not rely on `php_flag`.** That directive
only exists when mod_php is loaded. On a PHP-FPM or CGI host it is an invalid
directive and Apache answers *every* request in the directory with 500 —
measured on Apache 2.4.58 without mod_php. Execution is therefore denied by
filename with `<FilesMatch> … Require all denied`, which holds under every PHP
SAPI, with `php_flag` kept inside `<IfModule>` as belt and braces.

> **Apache setup.** Pointing the document root at `public/` remains the
> preferred deployment. The root guard exists because the subdirectory layout
> is what XAMPP does by default, and without it `/<project>/.git/config` is
> served — measured at 200 before the guard was added. `.git` exposure leaks
> the entire source history.

---

## 2. Decisions

### 2.1 No framework

**Decision.** Plain PHP 8.2+, no framework.

**Alternatives.** Laravel; Symfony; Slim + a handful of components.

**Why.** The requirement is about ten public templates and a single-user CMS.
Laravel brings an ORM, queues, a container, Blade, Artisan and ~80 packages —
almost none of which this project uses — plus a deployment story that shared
hosting and XAMPP handle awkwardly. The architecture below is roughly 1,200
lines of application code that one person can hold in their head and defend in
an interview.

**Trade-off.** Everything a framework provides free — validation, an ORM,
CSRF middleware, a mailer — has to be written or deliberately skipped. That is
accepted because the list this project actually needs is short, and each item
is a few dozen lines rather than a dependency.

**Honest counter-argument.** Laravel is a marketable skill worth learning on a
project where its strengths apply. That is a deliberate next project, not this
one.

### 2.2 A small autoloader instead of requiring Composer

**Decision.** Ship `composer.json` with the PSR-4 mapping, but register a
~25-line autoloader when `vendor/` is absent.

**Alternatives.** Require `composer install` before the site runs; hand-write
`require` statements.

**Why.** The project has no runtime dependencies yet. Making `composer install`
a precondition for the site rendering at all adds a failure mode for zero
benefit. `public/index.php` prefers Composer's autoloader the moment it exists,
so adopting it in Phase 6 (Markdown parser, HTML sanitiser) means running one
command — nothing moves.

**Trade-off.** Two autoloading paths exist briefly. Mitigated by the fallback
being ~25 lines implementing the same standard.

### 2.3 Configuration as a PHP array, not `.env`

**Decision.** `config/config.php` returns an array. `config/app.php` holds
committed defaults. The local file is git-ignored and required.

**Alternatives.** `.env` with `vlucas/phpdotenv`; a hand-rolled `.env` parser;
constants.

**Why.** Correct `.env` parsing (quoting, escapes, multiline) needs a library —
a dependency solely to read configuration. A returned array is opcache-compiled,
type-safe, and needs no parser. Each value still reads from a real environment
variable first via the `env()` closure, so a future container deployment is
open without a rewrite.

**Trade-off.** Less familiar to a 12-factor audience. Mitigated by the env-var
passthrough.

### 2.4 Hand-wired services, not a DI container

**Decision.** `Kernel::boot()` constructs the six services explicitly.

**Why.** The construction graph is two levels deep. A reflection container
would add indirection and a dependency to solve a problem this size does not
have. The wiring fits on one screen and is greppable.

**Trade-off.** Adding a service means editing `Kernel`. At six services that is
a feature — it is a visible inventory of what exists.

### 2.5 Media as an entity, never a filename

**Decision.** `media` + `media_variants`. Every image reference anywhere is a
foreign key to `media.id`. Templates ask a `Media` object for a URL.

**Alternatives.** A `photo_filename` column on each entity; one `media` table
with a JSON `variants` column.

**Why not filenames.** It is the decision that cannot be undone cheaply. Once
`profile.photo.jpg` appears in three templates, "replace the photo from the
admin" becomes a find-and-replace rather than an UPDATE.

**Why not JSON.** Tested directly on MariaDB 10.11, the XAMPP default: a column
declared `JSON` reports as `longtext` in `information_schema` and accepts the
string `'this is not json'` without error. It is an alias, not a type — no
validation, no native indexing. A child table gives a real unique key per
`(media_id, variant, format)`, `ON DELETE CASCADE` cleanup, and room for AVIF
later without rewriting stored documents.

**Trade-off.** One extra join. Neutralised by `MediaRepository::variantsFor()`,
which takes an array of ids and fetches every variant in one query — written
that way from the start because the per-row version is the N+1 that would
otherwise appear the first time a project gallery renders.

### 2.6 `profile` as a singleton table; `settings` as key/value

**Decision.** Two different shapes, deliberately.

**Why.** The profile's fields are a fixed, known set with different types and
constraints — two of them are foreign keys to `media`. A key/value bag would
flatten all of that into nullable strings and move validation into application
code. Settings are the opposite: an open-ended set of scalars, which is exactly
what key/value is for. `value_type` lets the repository cast on read, so a
boolean comes back as a bool rather than `"1"`.

The singleton is enforced by `CHECK (id = 1)` — verified to reject a second
row — so it is a database guarantee rather than a convention.

### 2.6b Project ordering: dense integers, not LexoRank

**Decision.** `sort_order` is a dense integer; a reorder rewrites the affected
rows in one transaction.

**Alternatives.** Sparse integers with periodic compaction; fractional
indexing; LexoRank (what Jira uses).

**Why.** LexoRank makes a reorder O(1) instead of O(n) and never needs
compaction. That matters for a backlog of thousands of issues reordered
concurrently by many people. This is a portfolio: fewer than twenty rows,
reordered occasionally by one person. Rewriting twenty integers in a single
UPDATE is correct, obvious, and cannot drift or exhaust its gaps — and sparse
integers only postpone exhaustion rather than removing it.

**Trade-off.** A reorder touches every row between the old and new position
instead of one. At this scale that is a single small UPDATE. Choosing
LexoRank here would be sophistication for its own sake.

### 2.6c Unique slugs alongside soft delete

**Decision.** A generated column, `alive`, that is `1` while the row is live
and `NULL` once soft-deleted, with `UNIQUE (slug, alive)`.

**The problem.** A plain `UNIQUE (slug)` makes a slug unusable forever once a
project is soft-deleted, because the deleted row still occupies it. PostgreSQL
solves this with a partial index; MySQL and MariaDB have none.

**Why this works.** A unique index permits many NULLs, so uniqueness applies
only to live rows. **Verified on MariaDB 10.11:** a duplicate live slug is
rejected, a slug is reusable after soft deletion, and several soft-deleted
rows may share one slug.

**Trade-off.** A column that exists only to serve an index, and the trick
needs a comment to be readable — which the migration carries.

### 2.6d Publication enforced in the repository, not in templates

Public read methods are named `findPublished*`; the admin methods arriving in
Phase 6 will be `findAll*`. **Two distinct names rather than an
`$includeDrafts` flag**, because a flag has a default and a default is exactly
how a draft leaks. A template cannot leak a draft it was never handed, and a
reviewer can grep the public controllers for `findAll` to prove none is there.

**Verified:** setting a project to `draft`, to `archived`, or soft-deleting it
each returns a genuine 404 on its URL and removes it from the home page.
Restoring it returns 200.

### 2.6e Batched child queries

Every list method fetches its children — technologies, sections, features,
media variants — in one query per child type, using `WHERE parent_id IN (…)`.

**Measured** with the MariaDB general log: the home page costs **5 queries**
with two projects and **5 queries** with six. Constant, not linear. The
obvious per-project loop would have been an N+1 the first time two projects
rendered with technologies.

### 2.7 Forward-only SQL migrations

**Decision.** Numbered `.sql` files, applied in order, recorded in
`schema_migrations`. No down migrations.

**Alternatives.** Phinx or Doctrine Migrations; PHP migration classes with
`up()`/`down()`.

**Why.** SQL files are readable by anyone who knows SQL, diff cleanly, and can
be run by hand in an emergency without the runner. Down migrations are another
thing to keep correct and are almost never the right recovery tool — restoring
a backup is. A mistake is corrected by writing the next migration.

**Trade-off.** No automated rollback. Accepted for a single-maintainer project
with backups.

**Note.** DDL in MySQL/MariaDB causes an implicit commit, so wrapping a
migration in a transaction would give false reassurance. Each file is instead
small enough to reason about, and the ledger row is written only after every
statement in it succeeded.

### 2.8 Routing

**Decision.** A ~120-line pattern router with a flat table in `routes/web.php`.

**Alternatives.** `switch` on the path; FastRoute; a framework router.

**Why.** A `switch` cannot express `/work/{slug}`. FastRoute is excellent but
is a dependency for about fifteen routes. The placeholder charset deliberately
excludes `/`, so a parameter can never swallow two path segments — verified.

**Trade-off.** No route caching or named-route generation. Irrelevant at this
scale; `route_url()` covers URL building.

### 2.9 Plain-PHP views with explicit escaping

**Decision.** PHP templates, a `View` class for layout and partials, and `e()`
at every output site. Escaping is **not** automatic.

**Alternatives.** Twig (auto-escaping); a hand-written compiler.

**Why.** Twig's auto-escaping is genuinely safer by default, and is the right
answer on a team. Here, a compiler is the "build a small framework" trap, and
Twig is a dependency plus a cache directory plus a second language. The
mitigation is that there is exactly one escaping helper, its name is one
character, and `e_url()` and `e_js()` exist for the contexts where `e()` is
wrong — because using the wrong escape is a real vulnerability, not a style
preference.

**Trade-off.** A forgotten `e()` is an XSS hole. This is the single largest
residual risk in the codebase and is listed as such in §6.

### 2.10 No session on public pages

**Decision.** `Session::start()` is called lazily, never in bootstrap.

**Why.** An unconditional `session_start()` sends `Set-Cookie` on every
response, which makes every page uncacheable by any shared cache and costs a
file write per visitor. Only the admin area and CSRF-protected forms need a
session. **Verified:** a request to `/` returns no `Set-Cookie` header.

### 2.11 Session cookie hardening

Follows the OWASP PHP Configuration cheat sheet: `use_strict_mode=1`,
`use_only_cookies=1`, `HttpOnly`, `SameSite=Strict`, and `Secure` plus the
`__Host-` prefix **only when the request is actually over HTTPS**.

`__Host-` is the strongest available protection against subdomain cookie
injection, but it requires `Secure`, and a `Secure` cookie is silently dropped
over plain HTTP. Applying it unconditionally would make local XAMPP logins fail
with no visible cause. The prefix is therefore conditional on the scheme.

### 2.12 Inline styles removed for the CSP

The Phase 1 static pages used `style="margin-top: …"` in about eight places.
Inline style attributes are blocked by a CSP without `'unsafe-inline'`, and
weakening the policy to keep eight attributes is the wrong trade. They were
replaced by `u-mt-*` utility classes — no visual change, a materially stronger
policy. **Verified:** zero CSP violations across three pages and four widths.

---

## 3. Request lifecycle

```
Browser
 → Apache (public/.htaccess: real files served directly, everything else rewritten)
   → public/index.php
     → Autoloader (Composer's if present)
     → Kernel::boot()
         Config::load()  ·  timezone  ·  helpers  ·  View
         ErrorHandler::register()
         services: MediaRepository → ProfileRepository, SettingsRepository
         routes/web.php → Router
     → Kernel::handle(Request::fromGlobals())
         Router::match()           404 / 405 handled here
         Controller::__invoke      builds a view model — NO SQL
         Repository                PDO prepared statements
         View::render              template + layout, every output through e()
         SecurityHeaders::apply    CSP and friends on every response
     → Response::send()
 ← Browser
```

---

## 4. Data flow: admin → database → public

Implemented for the profile; the same shape carries projects in Phase 3.

```
ADMIN (Phase 4+)            DATABASE                 PUBLIC
─────────────────           ────────                 ──────
ProfileController::update   profile row              ProfileRepository::current()
  validate                  + media FK                 one query, joined to media
  ImageUploadService        media                      ↓
  transaction               + media_variants         Profile entity (+ Media|null)
  CacheService::invalidate                             ↓
                                                     templates/components/portrait.php
                                                       photo === null → monogram
                                                       otherwise      → <picture>
```

The invariant: **grep `public/` and `src/` for an image filename and find
nothing.** `components/portrait.php` is the only place that turns a photo into
markup, and it receives a `Media` object. That is what makes "upload, replace
or remove from the admin and it changes everywhere" structurally true rather
than merely currently true.

Public and admin never call each other. They share a database through one
repository layer. Publication filtering lives in the repository — public read
methods will be `findPublished*`, admin methods `findAll*` — so a draft cannot
leak through a forgotten template condition.

---

## 5. Local setup

```bash
# 1. Point Apache's document root at public/ (virtual host on XAMPP).

# 2. Create the local configuration — never committed.
copy config\config.example.php config\config.php

# 3. Create the database and a least-privilege user.
#    Do NOT use root in anything reachable from a network.
CREATE DATABASE ramson_portfolio CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'portfolio_app'@'localhost' IDENTIFIED BY '<a real password>';
GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, ALTER, INDEX, REFERENCES, DROP
  ON ramson_portfolio.* TO 'portfolio_app'@'localhost';

# 4. Run migrations and seeds.
php bin/migrate.php --seed
php bin/migrate.php --status

# Optional: run without Apache.
php -S localhost:8000 -t public bin/dev-server.php
```

### Verifying a local Windows/XAMPP install

```powershell
powershell -ExecutionPolicy Bypass -File bin\verify-local.ps1
```

Discovers the toolchain, checks configuration without printing secrets,
creates the database if it is missing (`IF NOT EXISTS` only — it never drops
or resets one), runs migrations and seeds, exercises every route on both the
development server and Apache, and performs the security checks. Writes
`storage/logs/verify-local-report.txt`.

---

## 6. Residual risks

| Risk | Mitigation | Status |
|---|---|---|
| A forgotten `e()` becomes XSS | One-character helper; `e_url()`/`e_js()` for other contexts; strict CSP as a second layer | **Largest residual risk.** Accepted with mitigations |
| Document root pointed at the repo root | Root `Require all denied` + per-directory guards; both layouts tested on Apache 2.4.58 | Mitigated, verified |
| `.htaccess` behaviour on XAMPP's Apache (Windows) | Verified on Apache 2.4.58 (Linux). Same mod_rewrite semantics, but not the same build — run `bin/verify-local.ps1` | Verify locally |
| Google Fonts widens the CSP | Two hosts allowed explicitly; Phase 9 self-hosts and removes them | Tracked |
| Migration runner's SQL splitter | Quote- and comment-aware; no stored programs in the schema. Extend `splitStatements()` if that changes | Bounded |
| `root` used as the database user | `config.example.php` documents a least-privilege user | Documented, not enforced |
| No automated tests | Verification is manual and browser-based. A test suite belongs with the first business logic worth testing — the upload validator in Phase 5 | Open |
| Case-study prose is seeded, not authored | The overview/problem/solution text is Phase 1 wording built from supplied scope. Editable from the CMS in Phase 6 | Tracked |
