# Project rules — Ramson Titih Portfolio

Read `docs/portfolio/00-PRD-AND-PLAN.md` before changing anything structural.

## Hard rules

1. **No invented content.** No achievements, clients, testimonials, statistics,
   certifications, user counts, revenue or business results. Every public claim must appear as
   ✅ Verified in `docs/portfolio/04-CONTENT-INVENTORY.md`. If it is not there, ask — do not
   write it.
2. **CareerForge AI is excluded** from the portfolio: not in templates, seeds, screenshots,
   copy or commit messages. (It is named in the docs only to record the exclusion.)
3. **No image filename in source.** Every image resolves through the `media` table.
   `grep` for an image filename in `public/` or `src/` must return nothing.
4. **PDO prepared statements for 100% of queries.** No string interpolation of user input into
   SQL, ever — not even integers.
5. **Every output escaped** through the `e()` helper, in the correct context (HTML, attribute,
   URL, JS).
6. **Every mutation is POST + CSRF token.** No GET ever changes state.
7. **No framework.** No Laravel, React, Bootstrap or Tailwind. Vanilla PHP, JS and hand-written
   CSS, as decided in PRD §S.1.
8. **Only `public/` is web-accessible.** Never place anything servable outside it.

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

## Workflow

- Phases are sequential — see `docs/portfolio/02-ROADMAP.md`. Do not skip ahead.
- Unanswered items in `docs/portfolio/03-OPEN-QUESTIONS.md` block the phases they are listed
  under. Ask rather than assume.
