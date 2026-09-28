# Open Questions

Information needed from Ramson, grouped by what it blocks. Answered items move to the
**Resolved** section at the bottom with their answer, so the decision history stays visible.

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
- Technology stack
- Current state — concept, landing page live, or functional product?
- Live URL, if any
- GitHub URL, if any
- Screenshots (the landing page at minimum)
- **What you personally built**
- The hardest technical problem you hit, and how you solved it

**6. School Management System.**
- Is `github.com/tebitramson69-netizen/School-Management-System` the right repository? *(Observed on your GitHub account — not assumed to be this project.)*
- Live URL, if any
- Screenshots — the report card output especially
- Is it used by a real school, or built as a project? **Either answer is fine and respectable; I just will not guess.**
- The report-card ranking logic in your own words
- The hardest problem you solved

**7. Further projects.**
Your GitHub account also shows `ai-script-to-video-studio`. Do you want it featured, listed
without a case study, or left out entirely?
Any other genuine projects to include?

*(Note: `CareerForge AI` is excluded by decision and will not appear anywhere.)*

---

## Blocking Phase 8 — Contact

**8. Contact channels.**
- Public email address
- WhatsApp number for the `wa.me` link
- LinkedIn URL
- Any other social profiles to show

**9. Response-time commitment.**
What can you honestly promise on the contact page? "Usually within 24 hours" / "within 2
business days" / no stated time.
*Only promise what you will actually meet — a missed promise here is worse than no promise.*

---

## Blocking Phase 10 — Launch

**10. Services.** Which three or four do you actually want to offer? Written in business
outcomes, not stack names.

**11. How I Work.** Your real process, in your own words — roughly four steps. *This is the
highest-trust, lowest-cost section on the site for client conversion, and it cannot be
invented.*

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
