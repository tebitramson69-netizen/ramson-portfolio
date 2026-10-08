# 06 — Deployment

Written for **shared hosting with cPanel** (Apache, PHP, MySQL), which is the
stack this application was built for. Nothing here needs a VPS, Docker, a
build step or a deploy pipeline.

Fill in the blanks marked `<…>` as you go.

```
Host              <…>
Domain            <…>
cPanel URL        <…>
Database name     <…>
Database user     <…>
```

---

## 0. Before you pay — test the host, do not trust the feature list

A sales page is a claim. `bin/verify-production.php` is a measurement.

Most hosts have a refund window; it is only worth having if something
actually tests the server inside it. So, on a trial or on day one:

1. Upload the repository (FTP or cPanel File Manager — a full deploy is not
   needed for this, just the files).
2. Run it:

   ```
   php bin/verify-production.php
   ```

   On a host with no SSH, use **cPanel → Terminal**, or **cPanel → Cron Jobs**
   with a one-off schedule and your email in the notification field. The
   output arrives by mail.

**Never make this script reachable over the web.** It prints your database
user, your PHP configuration and your directory layout — exactly what someone
scanning your site would like to have. It refuses to run outside the CLI for
that reason, and that refusal is not a formality to work around.

### The answers that decide it

Section 1 of the output must be all PASS. In particular:

| Check | If it fails |
|---|---|
| PHP version ≥ 8.2 | Ask whether a newer PHP can be selected in cPanel → MultiPHP Manager. Most hosts allow it. If not, walk away |
| **GD image library** | **Walk away.** Every image on the site — each AVIF, WebP and JPEG variant, every crop — is produced by GD. Without it uploads cannot work at all |
| fileinfo | Walk away. Uploads cannot be validated safely without real MIME detection |
| exif | Negotiable. Without it a phone photo may be stored sideways |
| Image formats available | AVIF missing is survivable — `<picture>` falls through to WebP and JPEG |
| Upload size limits agree | Usually fixable yourself in cPanel → MultiPHP INI Editor |

Section 4 will probably say *Apache modules cannot be read under cgi-fcgi*.
That is honest, not a failure — PHP cannot see Apache's module list under
FastCGI. Verify those in step 8 instead, in a browser.

---

## 1. Create the database and a least-privilege user

**cPanel → MySQL Databases.**

1. Create a database. cPanel prefixes it with your account name, so you get
   something like `ramson_portfolio`.
2. Create a **new user**. Do not reuse an admin account, and do not use
   `root`.
3. Add the user to the database with these privileges and no others:

   ```
   SELECT  INSERT  UPDATE  DELETE  CREATE  DROP  INDEX  ALTER  REFERENCES
   ```

   `CREATE`, `DROP` and `ALTER` are there because the migration runner needs
   them. Everything above that — `GRANT OPTION`, `FILE`, `SUPER` — it does
   not.

Why this matters: with a `root` connection, a single SQL injection anywhere in
the application is a `DROP DATABASE`. With the grant above it is, at worst,
damage inside one database you have a backup of. `verify-production.php` fails
the run if it finds `root`.

---

## 2. Where the files go

The application expects the web root at **`public/`**. cPanel gives you
`public_html`. Three layouts, best first — and the third already works, so you
are not blocked whatever your host allows.

### A. Repo above `public_html`, document root moved (cleanest)

Put the repository at `~/ramson-portfolio`, then **cPanel → Domains** and set
the domain's document root to `/home/<user>/ramson-portfolio/public`.

Result: `https://yourdomain.com/`. Nothing above `public/` is served at all.

### B. `public_html` as a symlink

```
rm -rf ~/public_html
ln -s ~/ramson-portfolio/public ~/public_html
```

Same result as A. Needs shell access and a host that follows symlinks — some
disable `FollowSymLinks`.

### C. Repo inside `public_html` (always works)

Upload to `~/public_html/ramson-portfolio/`. The site is then at
`https://yourdomain.com/ramson-portfolio/public/`.

Ugly, and still **safe**: the root `.htaccess` is `Require all denied`, and
`public/.htaccess` grants access back for that one directory. This was
verified on Apache 2.4.58 against real exposure — without the root guard,
`/ramson-portfolio/.git/config` returned 200 and served the repository
configuration.

This is also the layout you already run under XAMPP, so it is the best-tested
of the three. Start here if the host makes A difficult, and move later.

### What NOT to upload

`config/config.php` is gitignored and must never be committed or uploaded from
your machine — you write it fresh on the server in step 3. Also skip `.git/`
and `storage/backups/`.

---

## 3. Configuration

Copy `config/config.example.php` to `config/config.php` **on the server** and
set:

```
APP_ENV       production
APP_DEBUG     false
APP_URL       https://<your domain>        ← https, and no trailing slash
DB_HOST       localhost
DB_DATABASE   <cpanel database name>
DB_USERNAME   <the user from step 1>
DB_PASSWORD   <its password>
```

`APP_DEBUG=false` is not cosmetic. With it on, an uncaught exception renders a
stack trace containing file paths and configuration to whoever triggered it.

`APP_URL` must be `https://`. Session cookies take their `secure` flag from the
request, so the whole site has to be on HTTPS for that to hold.

---

## 4. Migrate and create the admin

```
php bin/migrate.php
php bin/create-admin.php
```

Use a **fresh, strong password** for the live admin — not the one from your
laptop. There is no public sign-up and no password reset, so this account is
the only way in.

---

## 5. HTTPS, then HSTS — in that order

1. **cPanel → SSL/TLS Status** and run AutoSSL. Free, renews automatically.
2. Confirm `https://yourdomain.com/` loads with a valid certificate.
3. Force HTTPS — cPanel → Domains has a toggle, or add the redirect to
   `public/.htaccess`.
4. Only now does HSTS apply.

You do not have to do anything for step 4. `SecurityHeaders` sends
`Strict-Transport-Security` only when the request is already secure **and**
`APP_ENV` is production, so the ordering is enforced in code rather than left
to you remembering it.

The reason it matters: HSTS tells every browser that visited to refuse plain
HTTP for `max-age` — a year. Send it before the certificate works and you lock
visitors out of your own site, and removing the header does not take it back.

---

## 6. Backups, and a restore you have actually performed

```
php bin/backup.php
```

Writes a `.sql` dump and a `.zip` of both upload directories into
`storage/backups/`, keeping the newest seven runs. `storage/.htaccess` denies
the whole directory to the web.

Set it to run nightly — **cPanel → Cron Jobs**:

```
0 3 * * *   /usr/local/bin/php /home/<user>/ramson-portfolio/bin/backup.php --keep=14
```

Then download a copy off the server periodically. A backup on the same disk as
the thing it backs up protects you from your own mistakes, not from the host's.

### Test the restore. Once, now, not during an emergency.

A backup nobody has read back is a belief about a file.

```sql
CREATE DATABASE restore_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
GRANT SELECT, INSERT, UPDATE, DELETE ON restore_test.* TO '<app user>'@'localhost';
FLUSH PRIVILEGES;
```

**That `GRANT` is not optional, and it is the step people miss.** The
application user from step 1 is scoped to one database by design, so it cannot
see `restore_test` without being told.

Found the hard way while writing this procedure: the dump loaded perfectly,
all nine table counts matched the source, and the site still returned 500 on
every page — because the user had no grant on the database it had just been
pointed at. In a real emergency that looks exactly like a corrupt backup, and
you would go looking for the wrong problem.

Then:

```
mysql restore_test < storage/backups/<newest>.sql
DB_DATABASE=restore_test php -S 127.0.0.1:8200 -t public bin/dev-server.php
```

Open `http://127.0.0.1:8200/` and check your own name, a project card and a
case study are all there. **That** is when it becomes a backup.

Verified 2026-10-08: dump restored, nine table counts matched, and the home
page and both case studies rendered from the restored copy.

---

## 7. Deploying an update later

```
cd ~/ramson-portfolio
php bin/backup.php
git pull
php bin/migrate.php
php bin/verify-production.php
```

Back up *before* pulling, not after. Migrations are forward-only; the backup
is the way back.

---

## 8. The checks a script cannot do

From a browser, on the live domain:

| URL | Expected |
|---|---|
| `/` | 200, correct fonts (not Times or Georgia) |
| `/.git/config` | **403 or 404 — never the file** |
| `/config/config.php` | **403 or 404** |
| `/storage/backups/` | **403** |
| `/work/<a draft slug>` | 404, not a page |
| `/admin` | redirect to the login form |
| `/sitemap.xml` | XML, listing only published projects |
| `/robots.txt` | the `Sitemap:` line shows your **real domain** |

If `/` works but `/work/rendo` 404s, `mod_rewrite` is off or `AllowOverride`
is not permitting `.htaccess`. That is the single most common shared-hosting
problem, and the host can switch it on.

Then check the response headers for `Content-Security-Policy` and
`Strict-Transport-Security`, and paste the home page URL into a social-card
validator to confirm the Open Graph image resolves.

---

## 9. Launch checklist

- [ ] `verify-production.php` — zero FAIL
- [ ] `verify-phase7.php` — 31 passed
- [ ] `verify-phase9.php` — 22 passed
- [ ] HTTPS valid, HTTP redirects to it
- [ ] Every row in section 8 behaves as stated
- [ ] Admin password unique to the server, stored in a password manager
- [ ] Nightly backup cron running, and one copy downloaded
- [ ] **A restore performed, and the site rendered from it**
- [ ] Opened on a real phone, not a narrow browser window
- [ ] Social card preview correct
