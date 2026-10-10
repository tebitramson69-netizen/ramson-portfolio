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

### 2.11b Argon2id, not PASSWORD_DEFAULT

**Decision.** `PASSWORD_ARGON2ID` at 64 MiB / t=3 / p=1, falling back to bcrypt only where the
PHP build lacks Argon2.

**Why not `PASSWORD_DEFAULT`.** It is still bcrypt in PHP 8.4 — verified, it emits `$2y$` — and
**bcrypt silently truncates at 72 bytes**. This is not theoretical: hashing a 72-character
password and then verifying a 100-character password that merely starts with those 72
characters SUCCEEDS. Reproduced on PHP 8.4.19. A long passphrase would be quietly reduced.

**Parameters.** OWASP's floor is 19 MiB / t=2 / p=1, which measured 18 ms here — cheap for an
attacker too. A login happens rarely, so the cost is raised to ~143 ms locally. More memory
buys more GPU resistance than more iterations; 64 MiB rather than 128 MiB keeps it comfortable
on modest shared hosting.

**Trade-off.** Argon2 must be present in the PHP build. Where it is not, the fallback
**rejects** anything over 72 bytes rather than truncating it — refusing is honest, truncating
is a silent downgrade. The dashboard states which algorithm is actually in use.

### 2.11c Throttling on account AND address

Failed attempts are counted against both the submitted email and the client IP, and either
crossing the limit blocks. Throttling on the account alone lets a botnet spread attempts across
addresses; throttling on the address alone lets one attacker lock the owner out of their own
account. A successful login clears that account's failures.

**Verified:** five failures lock the account; the sixth and seventh are refused *before* the
password check, so they do not inflate the count; and the correct password is refused while
locked, so the lockout cannot be bypassed by finally guessing right.

### 2.11d The guard runs before the controller exists

Route guards are enforced in `Kernel::handle()`, not in an admin base class. A base-class check
invites the assumption that a controller which forgets to extend it is still safe; a kernel
check means an unauthenticated request never reaches any admin controller at all.

Two further properties, both verified: re-logging in rotates a stored `session_token` that every
request compares against, so a stolen cookie stops working the moment the owner signs in again;
and the session is bound to a user-agent hash, so replaying the cookie elsewhere fails.

### 2.11e Deprecations are logged, not thrown

`ErrorHandler` promotes warnings and notices to exceptions, which catches bugs early. It
deliberately does **not** do this for `E_DEPRECATED`. PHP 8.4 deprecated `session.sid_length`,
which the session bootstrap set — and because deprecations were fatal, every admin page returned
500 the first time a session was started. A deprecation is a warning about tomorrow and must not
be fatal today.

### 2.12 Inline styles removed for the CSP

The Phase 1 static pages used `style="margin-top: …"` in about eight places.
Inline style attributes are blocked by a CSP without `'unsafe-inline'`, and
weakening the policy to keep eight attributes is the wrong trade. They were
replaced by `u-mt-*` utility classes — no visual change, a materially stronger
policy. **Verified:** zero CSP violations across three pages and four widths.


### 2.13a The uploaded bytes are never served

**Decision.** Every variant is produced by decoding the upload with GD and
re-encoding it. The original file the browser sent is discarded; a re-encoded
JPEG is kept outside the web root as the canonical source for future sizes.

**Alternatives.** Store the upload as-is and resize on demand; store the
original in `public/` and serve it directly; sanitise metadata with a library.

**Why.** A decode and re-encode is the single cheapest control that closes an
entire class of attack at once. A polyglot file — valid GIF, valid PHP — does
not survive it. Neither does a payload hidden in an EXIF comment. It also
removes the GPS coordinates a phone writes into every photograph, which is a
privacy problem, not a security one, and would otherwise be published. No
allow-list of MIME types achieves any of that on its own.

**Trade-off.** A small quality loss on re-encode, and CPU at upload time. The
measured cost for the full set is 178 ms without AVIF and 2,061 ms with it.
An upload happens perhaps twice a year.

### 2.13b Variants are rows, not a naming convention

**Decision.** `media_variants` stores one row per derived file, carrying its
variant name, format, path and real dimensions. Templates ask a `Media` object
for a URL.

**Alternatives.** Derive the filename in the template from the storage key and
a size name (`{key}-hero.webp`); store a JSON blob of variants on `media`.

**Why.** A convention cannot answer "does this variant exist?" without hitting
the filesystem, and it cannot record the variant's real size. Both matter
here: a GD build without AVIF simply writes fewer files, and the `<picture>`
element must not advertise a source that was never produced. The real
dimensions matter because they are not the configured ones — a source smaller
than the frame is not upscaled, so `hero` may legitimately be 420×525. Those
are the numbers the `<img>` must carry, or the browser reserves the wrong
aspect ratio and the page shifts on load. On MariaDB a JSON column is
`longtext` with no validation (verified), so a child table is also the only
option that a foreign key and an index can act on.

**Trade-off.** One extra table and one extra query — batched with
`WHERE media_id IN (…)`, so it stays one query however many images a page
shows.

### 2.13c Files are written before the commit; old files are deleted after it

**Decision.** The order is: validate → decode → orient → derive → write new
files → COMMIT → delete the old files. A failure anywhere unlinks the files
just written and leaves the database untouched.

**Alternatives.** Write files inside the transaction; delete the old photo
first and then upload the new one; a queue with a background sweeper.

**Why.** The filesystem is not transactional, so one of the two must be able
to fail cleanly, and an orphaned *file* is harmless where an orphaned *row* is
a broken image on the live site. Deleting first is worse still: a failure
would leave the portfolio with no photograph at all.

**Trade-off.** A crash between the writes and the commit leaves files nothing
references. They are invisible and cost a few hundred kilobytes; a sweeper can
be added if that ever matters, and it never needs to run for correctness.

### 2.13d Crops are anchored high, and the anchor is capped

**Decision.** A vertical crop starts 38% of the way into the height being
discarded, but never more than 10% of the source height from the top.

**Alternatives.** Centre the crop; anchor at a fixed fraction of the source;
detect the face.

**Why.** A centred crop of a portrait decapitates it, because a face sits in
the upper part of the frame. A fraction of the discarded height fixes the
mild cases but not the severe ones: cropping 1200×1500 to the 1200×630 social
card discards 870 px, and 38% of that starts the frame 330 px down — past the
face entirely. The cap bounds the offset in terms of the source, so the
subject survives every configured shape. **Verified:** an assertion in
`bin/verify-media.php` places a marker in the top fifth of a test portrait and
fails if any of the four crops loses it. It did fail for the social card
before the cap existed.

**Trade-off.** Two constants rather than one, and neither is derived from the
actual image. Face detection would be better and is not available in GD; the
admin shows all three crops before anything is committed, which is the
practical answer.

### 2.13e `post_max_size` is checked before CSRF

**Decision.** `ProfileController::uploadPhoto()` detects a discarded request
body — a POST with a declared `Content-Length` but empty `$_POST` and
`$_FILES` — and reports it, before the CSRF token is examined.

**Alternatives.** Let the CSRF branch handle it; raise the limits and ignore
the case; a `MAX_FILE_SIZE` hidden field.

**Why.** When a body exceeds `post_max_size`, PHP discards it entirely: there
is no token, no file, and no `$_FILES` error code. The CSRF check therefore
fires and reports "that form expired" — a message that names the wrong cause
and sends the author to retry the same upload forever. `MAX_FILE_SIZE` is no
help at all, since it is a client-side hint PHP only honours after parsing a
body it has already thrown away.

**Trade-off.** One check runs before CSRF. It is safe because it only reads
`Content-Length` and reports a message; it changes nothing and reveals
nothing an attacker does not already know.

### 2.13f Three forms, three routes

**Decision.** The photograph, its description, and the text fields each post
to their own route: `/admin/profile`, `/admin/profile/photo`,
`/admin/profile/photo/alt`, `/admin/profile/photo/remove`.

**Alternatives.** One form containing everything; a single route branching on
which fields arrived.

**Why.** They fail independently and should be recoverable independently. A
rejected image must not discard a page of edited biography, and correcting a
typo in the alt text must not re-encode nine files. Separate routes also make
the audit trivial: every one of them is a POST, guarded, and CSRF-checked, and
that can be read off the route table rather than inferred from a controller.

**Trade-off.** Four routes where one would do, and the page renders four
`<form>` elements. Neither costs anything a reader of the route table has to
untangle.


### 2.14a Admin reads in the same repository, writes in another

**Decision.** `ProjectRepository` gained `findAll()`, `findAllDeleted()`,
`findAnyById()` and `findAnyBySlug()`. Writes went into a new `ProjectWriter`.

**Alternatives.** A separate `ProjectAdminRepository` holding both; an
`$includeDrafts` flag on the existing methods.

**Why.** The flag was never an option — a flag has a default, and a default is
how a draft reaches a public page. That was already settled in this class's
docblock, which also named `findAll*` as the admin convention; following it
costs nothing and keeps one documented rule instead of two.

A fully separate admin repository was planned and then rejected on reading the
code: it would have duplicated `columns()`, `mediaJoins()`, `hydrateList()`,
`mediaFrom()`, `technologiesFor()`, `sectionsFor()` and `featuresFor()` —
around 120 lines of hydration, in two copies that drift. Two hydration paths
producing subtly different `Project` objects is a worse failure than the one
the split was meant to prevent. Writes share none of that, so they did move
out.

**The safety property is unchanged and still mechanically checkable**, though
not as simply as "nothing outside `Admin/`". Running

```
grep -rn "findAll\|findAny" src/Http/Controllers/*.php
```

returns **exactly one** line: `WorkController::preview()`. That method is
declared with the `'auth'` guard in `routes/web.php`, so the kernel refuses the
request before the controller is constructed. A *second* line from that grep is
a bug, and `WorkController::show()` — the method anonymous visitors actually
reach — must keep calling `findPublishedBySlug()`.

(This paragraph first claimed the grep returned nothing. It was written before
being run, and the run contradicted it. Corrected rather than quietly amended,
because a verification claim nobody executed is worth less than no claim.)

**Trade-off.** One class now serves two audiences. The method names carry the
distinction, and the public methods remain the only ones filtering on
`PUBLISHED`.

### 2.14b Child collections are replaced, not diffed

**Decision.** Saving a project deletes its sections, features and technology
rows and inserts the submitted set, inside one `Database::transaction()`.

**Alternatives.** Diff the submitted items against the stored ones and issue
the minimal inserts, updates and deletes.

**Why.** The form posts the whole collection, so a diff would mean matching
submitted items back to row ids — more code, and more ways to attach the wrong
body to the wrong section. Delete-then-insert is obviously correct by
inspection. The transaction is what makes it safe: a failure part-way rolls
back to the previous set rather than leaving a project with no sections.

**Trade-off.** Row ids and `created_at` are not stable across a save. Nothing
references a section by id, so nothing notices.

### 2.14c The preview lives in WorkController, and the bar in the layout

**Decision.** `/admin/preview/{slug}` is a second method on `WorkController`,
sharing every private helper with `show()`. The warning bar is rendered by the
public layout, not by the case-study template.

**Why.** A preview that assembles its view model separately from the real page
is a preview of something else, and the copy that drifts is always the one
nobody looks at. Sharing the helpers makes drift impossible.

The bar moved to the layout because the site header is `position: fixed; top:
0` — rendering the bar inside the page content put two elements at the same
offset, and they overlapped. From the layout it precedes the header and
`body.is-preview` offsets the header by `--preview-h`.

**Trade-off.** `WorkController` now has a method only reachable behind the
admin guard, declared in the route table. `show()` still calls
`findPublishedBySlug()`, so an anonymous visitor guessing a draft's address
still gets a genuine 404 — which the test suite asserts rather than assumes.

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
development server and Apache, proves the admin guard on every GET *and* POST,
runs the media self-check below, and performs the security checks — including
dropping a `.php` file into `public/uploads`, requesting it through Apache, and
deleting it again, because a directive that is present but not in effect passes
a file-content check and fails that one. Writes
`storage/logs/verify-local-report.txt`.

A route reporting `got 0` means no HTTP response arrived at all — not a wrong
status, nothing. On Windows the first thing to suspect is antivirus, not the
application: Avast's Behavior Shield flags `php.exe` as `IDP.Generic` when it
opens a listening socket and suspends the process, so the request never
completes. The README's antivirus note has the fix.

### Verifying the media pipeline anywhere

```
php bin/verify-media.php
```

Touches nothing: no database, no uploads directory, no configuration beyond
reading it. It builds its own fixtures in the temp directory, runs them through
the real `ImageProcessor` and `ImageValidator`, and exits non-zero if any
assertion fails. Covers all eight EXIF orientations, every configured crop's
aspect ratio and subject retention, the no-upscale rule, transparency
flattening, EXIF actually being stripped, and the `php.ini` limits.

### 2.15a One writer for two lists, with the table name in an enum

Services and process steps are structurally identical — an ordered,
visibility-flagged list of a title and a short body. They share
`ContentListWriter`, parameterised by a `ContentList` enum, rather than having
a class each.

The enum is not decoration. Both the writer and the repository interpolate a
table name into SQL, which no prepared statement can parameterise. The enum is
what makes that safe: a table name can only ever come from a case declared in
this application, never from a request, and adding a list is a deliberate code
change rather than a string someone passed in.

### 2.15b Deleting counts first and refuses; hiding is the reversible action

`fk_skill_category` is `ON DELETE RESTRICT`, so the database would already
refuse to delete a category holding skills — but it would refuse with a
`PDOException` and a 500, which tells the author nothing. `SkillWriter`
counts first and returns the count, so the controller can say *"Nothing was
deleted: "Platform" still holds 4 skills"*.

Cascading was never an option. These rows are the site's technology
vocabulary, referenced by `project_technologies` on every published case
study. Deleting a category because someone clicked Delete would silently strip
tags off live pages, and there is no undo for that. The same reasoning guards
an individual skill: it counts the projects tagged with it first.

Every screen offers **hide** before **delete**, and hiding keeps the text. A
service taken off the site for a month goes back without being rewritten.

### 2.15c Settings can be edited but not invented

`SettingsWriter` updates the value of a key that already exists. It cannot
create keys and it cannot delete them, and the admin screen has no "add
setting" button.

A settings key is read by name in a template — `site_title`,
`meta_description`. That makes it part of the code, not data. An admin screen
that let someone type `site_titel` would write a row nothing reads while the
real title silently kept its default, with no error anywhere. New keys arrive
in a seed, next to the template that reads them.

An unchecked checkbox posts nothing at all, so for a `boolean` the writer
reads absence as `false` rather than skipping the field.

### 2.15d An empty list renders nothing, and the nav agrees

A services or process list with no rows produces no section on the home page:
no heading, no empty grid, no "coming soon". Same rule `work-show.php` states
for case-study sections — a visitor cannot tell the section exists.

The nav is the part that is easy to get wrong. `#services` is linked from the
header, the slide-out panel and the footer, on every page. Removing the
section without removing those links leaves an anchor that does nothing when
clicked, which reads as a broken site rather than as a section not yet
written. So `Controller::page()` resolves `hasServices` once and every public
template renders the link only when the section is actually there.

### 2.15e Step numbers are positions, not a column

`process_steps` has no `number` column. The number a visitor reads is the
row's position in the list, rendered by the template's counter.

Storing both would let them disagree — reorder the steps, forget to renumber,
and the page shows "1, 2, 2, 4". One source of truth means moving a step
renumbers the list for free.

### 2.16a Fonts are self-hosted, and that is a privacy decision

Phase 9 moved Instrument Serif, Inter and JetBrains Mono onto this origin.
The performance case is real — a render-blocking third-party stylesheet and
two TLS handshakes leave the critical path — but it is not the main one.

While the fonts came from `fonts.gstatic.com`, every visitor's browser
announced itself to Google on every page load. Self-hosting ends that, and it
is what lets `config/app.php` declare `'self'` in every CSP directive. The
comment there now says so: adding an external host back is a decision about
the reader's privacy, not only about a dependency.

Only `latin` and `latin-ext` are stored — 14 of the 37 faces Google serves.
The `unicode-range` descriptor is what makes dropping the rest safe: a browser
fetches a file only when the page contains a character in its range, so the
absent Cyrillic faces cost nothing and are never requested.

### 2.16b One file per face, not one per weight

Inter and JetBrains Mono are variable fonts. Asking the Google CSS2 API for
`wght@400;500;600` returns three `@font-face` blocks that all point at **the
same URL**, because there is one variable file covering the axis.

Naming the downloads by weight therefore stored the same 48 KB three times.
Keying on the source URL instead collapsed 14 files to 8 and 564 KB to 256 KB,
and each face now declares the weight RANGE the API confirms it covers rather
than a single weight — so a `font-weight: 700` added later renders from the
real axis instead of being synthesised by the browser.

The ranges were verified by asking the API for them, not recalled.

### 2.16c The sitemap and robots.txt are routes

Both are generated by `SitemapController` rather than sitting in `public/`.

A static sitemap is correct the day it is written and wrong the first time a
project is published or unpublished from the admin, with nothing anywhere to
notice. It reads through `findAllPublished()` — the same published-only method
the public site uses — so a draft cannot reach a search engine unless someone
deliberately calls a `findAll*` method. That is what the naming split in
`ProjectRepository` is for.

`robots.txt` was a static file whose `Sitemap:` line read `/sitemap.xml`. The
specification requires that directive to carry an **absolute** URL, so
crawlers ignored it — and it pointed at a 404, since no sitemap existed. A
static file cannot know the domain it is served from.

The static file was **deleted** rather than left beside the route.
`public/.htaccess` serves any real file before consulting the front
controller, so leaving it would have quietly kept the broken version winning —
a route that exists and never runs is worse than no route.

### 2.16d A contrast ratio measured against the wrong background

`--fg-tertiary` carried the comment `~4.6:1 AA`. It measured **3.78:1**
against `--surface-overlay`, under the 4.5 floor for normal text, and it is
used for meta lines and eyebrows — normal text.

The quoted figure was not invented. It was measured against `--surface-base`,
the DARKEST surface, which flatters it. Every other documented ratio had the
same basis, and `--accent-fg` was out by more: labelled `~13:1`, actually
8.96:1.

Two things changed. The token moved `#6E7480` → `#7E8490`, clearing 4.5 on
every surface with margin while keeping the hue so the hierarchy below
`--fg-secondary` is unchanged. And `bin/verify-phase9.php` now parses the hex
values straight out of `main.css` and re-measures them against the LIGHTEST
surface, so a token edited without re-checking fails the suite rather than the
reader.

A comment claiming a ratio is worth less than a test asserting one.

### 2.17a The row is the message; the email is a convenience

`ContactController` commits the row and *then* calls `MessageNotifier`. The
notifier's return value is logged and never surfaced, and the visitor sees
success either way — because the message was received.

The ordering is the design, not caution. On free and cheap shared hosting
`mail()` is frequently disabled outright, and where it works the mail often
lands in spam: it is sent by a shared web server with no SPF record for the
From address. Treating email as the channel would mean messages that silently
never arrive, on exactly the hosting this site launches on. So the inbox at
`/admin/messages` is the channel, and the dashboard carries the unread count
because that is the screen he lands on.

`From:` is the site's own address and never the sender's — forging the
visitor's domain is what gets a shared host's mail rejected as spoofing.
`Reply-To:` carries the real sender, so replying still reaches them.

### 2.17b No CSRF token on the public contact form

Stated here because it looks like an omission and is not.

CSRF protects a victim from an action performed as them. The action here is
sending the site owner a message, which an attacker can do directly without
involving a victim at all. It also stops no bots: a bot can fetch a token as
easily as it fetches a form.

Against that it has a real cost. `Csrf::token()` calls `Session::start()`, so
putting a token on the home page means a session file per anonymous visitor
on shared hosting and a `Set-Cookie` on the most-requested page on the site.

So the public form has a honeypot, a per-address rate limit and validation,
and **every admin route keeps its CSRF check** — there a victim and a
privileged action both exist.

A session is started only on the POST path, which is how a rejected
submission can hand back everything the sender typed without giving a cookie
to visitors who merely read the page.

### 2.17c The rate limiter has no table

`MessageThrottle` counts rows in `messages` for an address inside a window.
`ix_message_ip` exists for that query.

A separate table recording that a message arrived, sitting beside the
message, would be two places to disagree about one fact — and would need its
own pruning, its own migration and its own bug.

It is shaped after `LoginThrottle` but shares none of its code. That class
counts *failed* attempts against two keys and clears them on success; here
there is no account, nothing fails, and a sent message is not something to
clear. Bending three concepts to fit would have read worse than forty lines
that say what they mean.

A request with no usable address is never blocked. Some hosts and proxies
pass none, and refusing those visitors to punish a guess about spam turns a
contact form into a wall. The IP is read from `REMOTE_ADDR` only —
`X-Forwarded-For` is set by the client, so trusting it would let anyone reset
their own limit by changing a header, which is worse than no limit because it
looks like one.

### 2.17d The honeypot answers a bot with success

A hit returns the same redirect and the same thank-you a real sender gets,
and stores nothing.

Reporting the rejection would tell whoever wrote the bot exactly which field
betrayed it, which is free assistance in writing the next one. Silence costs
nothing and teaches nothing.

The field is positioned off-screen rather than `display: none` — some bots
check for that and skip — and carries `tabindex="-1"` with `aria-hidden` on
its wrapper, so the one audience that could otherwise be trapped by an
invisible required field never meets it.

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
| No automated tests | `bin/verify-media.php` covers the media pipeline with 34 assertions and runs on Windows and Linux alike; `bin/verify-local.ps1` covers the environment and routes; the admin flows are covered by end-to-end suites run against a live server. Everything else is still verified by hand | Partly closed |
| A variant format the GD build cannot write | The upload succeeds with fewer variants and `<picture>` falls through; the dashboard reports which formats are available | Mitigated |
| Orphaned files after a crash between write and commit | Files without a row are invisible and cost a few hundred kilobytes; correctness never depends on cleaning them | Accepted |
| Case-study prose is seeded, not authored | The overview/problem/solution text is Phase 1 wording built from supplied scope. Editable from the CMS in Phase 6 | Tracked |
| Antivirus suspends `php.exe`, so the dev server and CLI scripts stall with no error | Documented in the README; `verify-local.ps1` reports the transport error and names antivirus as the first suspect rather than printing a bare `0` | Environment, documented |
