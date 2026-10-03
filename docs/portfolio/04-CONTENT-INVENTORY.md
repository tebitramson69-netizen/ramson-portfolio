# Content Inventory

**Purpose: enforce the content-integrity rule.**

Every factual claim that will appear on the public site is listed here with its status. A
claim may only be published once it is marked ✅ **Verified** — supplied by Ramson.

> **No invented achievements, clients, testimonials, statistics, certifications, user counts,
> revenue or business results.** A recruiter who catches one fabricated number discards the
> credibility of the entire site.

---

## Identity

| Claim | Status | Source |
|---|---|---|
| Full name: **Tebit Ramson Titih** | ✅ Verified | Supplied. Corrected in Phase 2 — "Ramson Titih" must NOT be used as the displayed full name |
| Fallback monogram: RT | ✅ Verified | Explicitly approved. Stored on the profile row, because derived initials would give "TT" |
| Title: Software Engineer / Full-Stack Developer | ✅ Verified | Supplied |
| HND Software Engineering **student** | ✅ Verified | Supplied — note "student", not graduate |
| Saint Louis University Institute Douala | ✅ Verified | Supplied |
| Location: Cameroon | ✅ Verified | Supplied |
| Value proposition | ✅ Verified | Supplied verbatim |
| Technology line | ✅ Verified | Supplied verbatim |
| Profile photograph | ⬜ Outstanding | Q2 — the monogram fallback shows until one exists. Since Phase 5 it is uploaded at `/admin/profile`; no file or code change is needed |
| Availability status | ⬜ Outstanding | Q3 |
| About paragraph | ⬜ Outstanding | Q12 |
| Years of experience | 🚫 **Never state** | No figure supplied; do not infer one |

---

## Skills

✅ **Verified — supplied directly:**
HTML · CSS · JavaScript · PHP · MySQL · Git · GitHub · XAMPP · AI-assisted development ·
AI/automation exploration

✅ **Verified via Rendo's stack** — **approved for public display**, both as Rendo's project
tags and in the "What I work with" section:
TypeScript · Deno · PostgreSQL · Supabase · Netlify · WhatsApp Cloud API · VS Code

⚠️ Of those seven, **Deno** and the **WhatsApp Cloud API** are in progress on Rendo rather than
shipped (edge functions under way; a Meta test number, not a production one). Listing them
under "what I work with" is honest — they are genuinely in use. Do **not** let them drift into
language implying a delivered, production integration.

⚠️ **Framing note.** "AI-assisted development" and "AI/automation exploration" were supplied in
those words. The locked technology line says "AI & Automation". Present these honestly as
areas he works with and explores — **not** as machine-learning engineering or model training,
which were never claimed.

🚫 **Never add** a proficiency percentage, star rating or year count to any skill. None was
supplied, and none is verifiable.

---

## Project 1 — Rendo

| Element | Status |
|---|---|
| Name: Rendo | ✅ Verified |
| Category: business/booking automation, SaaS | ✅ Verified |
| Target businesses: clinics, dental practices, beauty studios and similar | ✅ Verified |
| Channel: WhatsApp | ✅ Verified |
| Scope: customer messaging, booking, appointment reminders, business onboarding, waitlist, automated workflow | ✅ Verified |
| Has a polished SaaS-style landing page | ✅ Verified |
| Technology stack | ✅ **Verified** — supplied as a layer-by-layer table. Live/in-progress only: TypeScript, Deno, Supabase, PostgreSQL, Netlify, WhatsApp Cloud API (Graph v25.0), HTML, CSS, JavaScript |
| Live URL: `rendo-cm.netlify.app` | ✅ Verified — supplied |
| Status: landing page and waitlist live, booking flow in development | ✅ Verified — from the supplied status column |
| Planned work (pg_cron reminders, owner dashboard, LLM understanding, MTN MoMo / Orange Money, coexistence numbers) | ⚠️ **Planned, never claimed as built** — appears only under "Next steps" |
| GitHub URL: `github.com/tebitramson69-netizen/Rendo` | ✅ **Verified** — supplied, and confirmed to load **while signed out**. That second check is the one that matters: the application renders whatever URL the database holds and never tests reachability (a network call per page render would be a poor trade), so if the repository is ever made private this link silently 404s for every visitor with nothing to warn you. Re-check if its visibility changes |
| Screenshots | ⬜ Outstanding (Q5) |
| His personal role | ✅ **Verified** — **owner and builder**, in his own words: *"I worked with Claude to realise that and I own ownership of it."* |
| Build method: AI-assisted | ✅ **Verified** — stated by him. See the authorship rule below the table |
| Key decisions · lessons learned | ✅ **Verified** — supplied in his own words and published via `database/seeds/0005_rendo_case_study.php`. Supabase over a self-hosted backend (constraint, rejected alternative, trade-off and lock-in mitigation all stated); waitlist before booking flow; three lessons |
| Users, clients, bookings processed, revenue | 🚫 **Never state** — nothing supplied |

⚠️ **Authorship rule for Rendo.** He has decided the case study **describes the
decisions and outcomes, and does not name the tooling** — the reasoning being
that no engineer lists their IDE or Stack Overflow, and AI assistance sits in
that same category. That is his call and it is legitimate.

The boundary it must not cross: **no sentence may state or imply that the code
was written unaided.** Describing what he chose and why is true. "Hand-coded",
"built from scratch", "wrote every line" and anything of that shape are not,
and nobody has claimed them. Both halves of this rule travel together — the
first is the decision, the second is what keeps the decision honest.

What he owns, and what the case study is actually made of, is unaffected by
tooling: choosing Supabase over a hand-rolled backend, choosing WhatsApp over
an app, putting RLS on the database, shipping a landing page and waitlist
before the booking flow.

**Two further presentation decisions, both his, both already applied in
`0005_rendo_case_study.php`:**

1. The sentence *"I worked through this with an AI assistant (Claude) as a
   mentor and pair-programmer, but the decisions and the setup were mine to
   make and test"* stays **off the page** and is his interview answer. Do not
   add it to the case study without asking him again.
2. The sales lesson keeps the **decision** and drops the self-criticism. "I am
   not comfortable cold-calling" was his phrasing to me; the published text
   leads with the QR-code demo insight, because a reader who sees the weakness
   first will not be in the room to hear the rest.

One edit of mine is in the published lessons and he accepted it: his stated fix
for the wrong-version deploy was a consistent filename plus a visible version
number, which helps him *notice* the fault rather than prevent it. The text now
names the real cause — deploying from downloaded copies rather than from
version control.

---

## Project 2 — School Management System with Automated Report Card Generation

| Element | Status |
|---|---|
| Full name | ✅ Verified |
| Category: education, full-stack web application | ✅ Verified |
| Designed around secondary-school workflows | ✅ Verified |
| Scope: students, teachers, classes, subjects, attendance, scores, results, ranking, report cards | ✅ Verified |
| Role-based dashboards: administrator, teacher, student, parent | ✅ Verified |
| Stack: PHP, MySQL, JavaScript, HTML, CSS | ✅ Verified |
| GitHub repository | ⬜ Outstanding (Q6) — a `School-Management-System` repo was *observed* on the account but not confirmed as this project |
| Live URL | ⬜ Outstanding (Q6) |
| Screenshots | ⬜ Outstanding (Q6) |
| Used by a real school? | ⬜ Outstanding (Q6) — **do not assume either way** |
| Ranking logic explanation | ⬜ Outstanding (Q6) |
| Challenges · key decisions · lessons | ⬜ Outstanding (Q6) |
| Number of schools, students or report cards generated | 🚫 **Never state** — nothing supplied |

---

## Further projects

| Item | Status |
|---|---|
| `ai-script-to-video-studio` | ⬜ Outstanding (Q7) — observed on GitHub; inclusion not decided |
| Student Registration System | ⬜ Outstanding — a real early project, but not yet chosen for inclusion |
| **CareerForge AI** | 🚫 **EXCLUDED BY DECISION** — must not appear in any document, template, seed, screenshot or commit message |

---

## Contact

| Element | Status |
|---|---|
| Email | ✅ Verified — supplied and entered at `/admin/profile` |
| WhatsApp number | ✅ Verified — supplied and entered at `/admin/profile` |
| GitHub: `github.com/tebitramson69-netizen` | ✅ Verified — the account this repository lives in |
| LinkedIn | ⚠️ **Present but unstable — action needed.** A URL is stored, but it is LinkedIn's auto-generated handle (name + dash + random characters). It stops resolving the moment a custom URL is set, and LinkedIn issues no redirect, so the site would link to a 404 silently. Fix: set the custom URL on LinkedIn (`linkedin.com/in/tebit-ramson-titih`), then replace the value at `/admin/profile`. Stays flagged until that swap is confirmed |
| Response-time commitment | ⬜ Outstanding (Q9) |

---

## Services, Process, Experience

| Element | Status |
|---|---|
| Services offered | ⬜ Outstanding (Q10) — **must not be invented** |
| "How I Work" process steps | ⬜ Outstanding (Q11) — **must not be invented** |
| Work experience entries | ⬜ Outstanding — none supplied. If there are none, the timeline shows education only, which is honest and fine |
| Certifications | 🚫 **Never state** — none supplied |
| Awards | 🚫 **Never state** — none supplied |
| Testimonials | 🚫 **Never state** — none exist |

---

## Legend

| | Meaning |
|---|---|
| ✅ Verified | Supplied by Ramson. Safe to publish |
| ⬜ Outstanding | Needed. Section renders empty or omitted until provided |
| 🚫 Never state | No source exists. **Must not appear**, in any wording, however plausible |
