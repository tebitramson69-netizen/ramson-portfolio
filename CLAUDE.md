# Project rules — Tebit Ramson Titih Portfolio

Read `docs/portfolio/00-PRD-AND-PLAN.md` before changing anything structural.

## Hard rules

1. **The full name is "Tebit Ramson Titih".** Never display "Ramson Titih" as the full name.
   "RT" remains the approved fallback monogram and is stored on the profile row.
2. **No invented content.** No achievements, clients, testimonials, statistics,
   certifications, user counts, revenue or business results. Every public claim must appear as
   ✅ Verified in `docs/portfolio/04-CONTENT-INVENTORY.md`. If it is not there, ask — do not
   write it.
3. **CareerForge AI is excluded** from the portfolio: not in templates, seeds, screenshots,
   copy or commit messages. (It is named in the docs only to record the exclusion.)
4. **No image filename in source.** Every image resolves through the `media` table.
   `grep` for an image filename in `public/` or `src/` must return nothing.
5. **PDO prepared statements for 100% of queries.** No string interpolation of user input into
   SQL, ever — not even integers.
6. **Every output escaped** through the `e()` helper, in the correct context (HTML, attribute,
   URL, JS).
7. **Every mutation is POST + CSRF token.** No GET ever changes state.
8. **No framework.** No Laravel, React, Bootstrap or Tailwind. Vanilla PHP, JS and hand-written
   CSS, as decided in PRD §S.1.
9. **Only `public/` is web-accessible.** Never place anything servable outside it.

## Design invariants

- Accent `#2DD4A7`, on **at most three elements per viewport**.
- Section vertical rhythm `clamp(5rem, 10vw, 10rem)`.
- Serif display + sans body. Display type gets tight tracking (`-0.02em`→`-0.035em`) and
  leading (`1.0`–`1.1`).
- Hero portrait: rectangular 4:5 frame, `radius: 4px`. **Never a circle.**
- Elevation is hairline borders, not shadows.
- Scroll reveals fire **once**, and content is fully visible with JavaScript disabled.
- No skill percentage bars. No fake statistics. No stock developer illustrations.

Full token values: `docs/portfolio/01-DESIGN-SYSTEM.md`.
Things that would make this look like a student template: PRD §U.

## Architecture

- Only `public/` is web-accessible. Apache's document root is `public/`, never the repo root.
- Every image is a foreign key to `media`; `templates/components/portrait.php` is the only
  place that turns a photo into markup.
- Controllers contain no SQL. Reads go through a repository.
- `config/config.php` is git-ignored and holds the only credentials.
- Decisions, alternatives and trade-offs: `docs/portfolio/05-ARCHITECTURE.md`.

## Workflow

- Phases are sequential — see `docs/portfolio/02-ROADMAP.md`. Do not skip ahead.
- Unanswered items in `docs/portfolio/03-OPEN-QUESTIONS.md` block the phases they are listed
  under. Ask rather than assume.
