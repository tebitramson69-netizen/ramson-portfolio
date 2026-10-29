# Open Questions

Information needed from Ramson, grouped by what it blocks. Answered items move to the
**Resolved** section at the bottom with their answer, so the decision history stays visible.

> **Looking for what to do next? That is `07-WHAT-I-NEED-FROM-YOU.md`.**
> This file is the ledger — the record of what was asked and what was decided,
> including long-settled items. The worklist is ordered by what unblocks the
> most, says where each answer goes, and says what happens if you skip it.

---

## Blocking Phase 1 — Design Foundation

**1. Display typeface.**
Serif display + sans body is locked. Remaining choice: **Instrument Serif** (cleaner, more
neutral) or **Fraunces** (more characterful, variable optical sizing).
*Recommendation: set the hero name in both during Phase 1 and compare directly — this is a
five-minute decision made better with eyes than in the abstract.*

**2. Profile photograph.**
Do you have a professional portrait? Its orientation, lighting and background affect the hero
treatment. The design already tolerates an imperfect photo (slight desaturation plus a 4%
accent overlay harmonises any background), but knowing what we have shapes the crop and the
frame.
*If you do not have one yet, Phase 1 proceeds with the monogram fallback — which is a real,
designed state, not a placeholder.*

**3. Availability status.**
Which should the hero pill show at launch?
`Available for opportunities` · `Open to select projects` · `Not currently available`
*This is CMS-editable later; we just need a starting value.*

**4. Navigation labels.**
Confirm the public nav: `Work · About · Services · Contact`, or a different set?

---

## Blocking Phase 6 — Projects & Case Studies

**5. Rendo.**
- ~~Technology stack~~ — ✅ supplied as a layer-by-layer table
- ~~Current state~~ — ✅ landing page and waitlist live, booking flow in development
- ~~Live URL~~ — ✅ `rendo-cm.netlify.app`
- ~~GitHub URL~~ — ✅ `github.com/tebitramson69-netizen/Rendo`, verified publicly reachable
- Screenshots (the landing page at minimum)
- ~~**What you personally built**~~ — ✅ **owner and builder**, built with AI
  assistance. See the authorship rule in `04-CONTENT-INVENTORY.md`.

- ~~**Where did you hit a fork, and why did you go that way?**~~ — ✅ answered:
  Supabase over a self-hosted backend, with the constraint, the rejected
  alternative, the trade-off and the lock-in mitigation all stated; plus
  waitlist before booking flow. Published.
- ~~**What surprised you?**~~ — ✅ answered: stale documentation, the
  wrong-version deploy, and changing the sales approach so the product demos
  itself. Published.

**Q5 is closed except for screenshots.**

**6. School Management System.** *(Mostly answered by reading the code, 2026-10-29.)*
- ~~Is `github.com/tebitramson69-netizen/School-Management-System` the right repository?~~ — ✅ confirmed by its contents: 22 tables, PHP 8 MVC, the ranking engine described below. Entered as the project's GitHub URL.
- ~~The report-card ranking logic~~ — ✅ **read from the code** and written up in the Technical implementation section: `Result::assignPositions()`, a pure DB-free static function with a unit test; ties share a position; a student with no result gets `position = null` rather than last place; coefficient-weighted averages through the same engine the report card uses. *Correct anything that misrepresents what you meant — it was read, not explained.*
- ~~Technologies~~ — ✅ checked against the code. It also uses **Bootstrap 5.3.3** via CDN, which was missing from the five tagged in 0003. The seed adds the tag only if a `bootstrap` skill exists; **add one at `/admin/skills` and re-run the seed**, or the stack list stays incomplete.
- ~~**Is it used by a real school, or was it built as a project?**~~ — ✅ answered 2026-10-29: **built as a project.** `status_label` is now *Built as a project*, and **no Outcome section exists**, because nothing was deployed and there are therefore no users, no adoption and no results. Its absence is the answer, not an omission to fill later.
- ~~**Your role**~~ — ✅ answered: **owner and builder.** Published as the `role` field and the *My role* section.
- ~~**Key decisions**~~ — ✅ **sourced from your own comments** in `src/Models/Result.php` of that repository (lines 197, 222, 304, 305) and written up as four forks, each stating the alternative rejected. *Read it once and correct anything that misrepresents what you meant — the reasoning was read out of the code, not explained to me.*
- **Still yours — two questions, and nothing in a repository can answer either:**
  - **Why did you build it?** What made you start. This becomes the Context or Overview addition.
  - **What was the hardest problem?** My guess from the code is getting ties and no-result students right in the ranking. One or two sentences becomes the Lessons learned section — the last absent section on this case study.
- Live URL — none found in the repository, and nothing is deployed. Left null; a link that 404s is worse than no link.
- Screenshots — the report card output especially. Still the most valuable missing asset.
- ⚠️ **The README is empty (0 bytes).** The case study now links to the repository, so anyone who follows that link lands on a page with no explanation at all.

**7. Further projects.**
Your GitHub account also shows `ai-script-to-video-studio`. Do you want it featured, listed
without a case study, or left out entirely?
Any other genuine projects to include?

*(Note: `CareerForge AI` is excluded by decision and will not appear anywhere.)*

**8. Rendo's `role` field.** Filled as **Owner and builder** on 2026-10-29 — not
a new claim, but the same fact the approved *My role* section already published
in prose ("I own Rendo and built it"). Until then the case-study header read
*"Role — Awaiting confirmation"* directly above that sentence. Say the word and
it comes back out.

---

## Blocking Phase 8 — Contact

**8. Contact channels.**
- ~~Public email address~~ — ✅ supplied and entered
- ~~WhatsApp number for the `wa.me` link~~ — ✅ supplied and entered
- **LinkedIn URL — ⚠️ added, but the stored value is unstable.** It is
  LinkedIn's auto-generated handle. Set the custom URL
  (`linkedin.com/in/tebit-ramson-titih`), then replace the value at
  `/admin/profile`. The open item is the *swap*, not the decision.
- Any other social profiles to show

**9. Response-time commitment.**
What can you honestly promise on the contact page? "Usually within 24 hours" / "within 2
business days" / no stated time.
*Only promise what you will actually meet — a missed promise here is worse than no promise.*
**Phase 8 built the form and deliberately put NO time on it.** The success message says the
reply goes to the address they gave and nothing about when. Adding a promise is a one-line
change to `ContactController::thanks()` once you have chosen one you will keep; leaving it out
is the honest default, not a gap.

**9b. Experience — do you have real entries?**
Phase 7 deliberately built no `experience` table, because inventing employment history is
excluded and an empty CRUD is unused schema rather than a feature. The question is simply
whether there is anything genuine to list: an internship, freelance work, or the School
Management System if it was a real engagement rather than a project. *Either answer is fine.*
If yes, the table and screen are a small addition. If no, the home page's Experience section
stays as it is and the roadmap records why.

---

## Blocking Phase 10 — Launch

**10. Services.** Which three or four do you actually want to offer? Written in business
outcomes, not stack names.
**Phase 7 built the screen: `/admin/services`.** Nothing is seeded, and the section does not
appear on the site at all while the list is empty — so this is now yours to type, not mine to
build.

**11. How I Work.** Your real process, in your own words — roughly four steps. *This is the
highest-trust, lowest-cost section on the site for client conversion, and it cannot be
invented.*
**Phase 7 built the screen: `/admin/process`.** Step numbers come from the order, so there is
nothing to renumber when you move one.

**12. About copy.** A short paragraph in your own voice. I can draft from what you have given
me and you edit, if that is easier than writing from blank.

**13. CV.** Do you have a PDF? *(The download button stays hidden until one is uploaded, so it
can never 404.)*

**14. Domain and hosting.** Decided, or to be chosen? This affects the Phase 10 deployment
steps — shared cPanel hosting, a VPS and a platform host each differ.

---

## Resolved

| # | Question | Decision |
|---|---|---|
| R1 | Repository | `ramson-portfolio` — new, dedicated, official home |
| R2 | Accent colour | `#2DD4A7` |
| R3 | Typography direction | Serif display + modern sans body |
| R4 | Value proposition | *"I build practical web systems that turn manual workflows into simple digital experiences."* |
| R5 | Technology line | *"Full-stack development · PHP · JavaScript · MySQL · AI & Automation"* |
| R6 | Hero portrait treatment | Rectangular editorial frame, 4:5, `radius: 4px` — not a circular avatar |
| R7 | Stack | PHP 8.2+, MySQL, vanilla JS, hand-written CSS, server-rendered, no framework |
| R8 | CMS scope | Single administrator; no public registration; CLI password reset |
| R9 | CareerForge AI | Excluded from the portfolio entirely |
| R10 | CAPTCHA | Start without one — honeypot + timing + rate limiting first. Revisit only if spam materialises |
| R11 | Skill proficiency bars | Excluded — unverifiable and read as junior |
| R12 | Testimonials / statistics | Excluded — none exist, and inventing them is disqualifying |
