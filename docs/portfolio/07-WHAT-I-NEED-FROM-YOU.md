# 07 — What I need from you

Every code phase is finished. Everything below is content or a decision, and
none of it can be written by anyone but you.

This is the **worklist**. `03-OPEN-QUESTIONS.md` is the **ledger** — it records
what was asked and why, including long-settled things. When a task here needs
background, it points there.

Admin URLs assume your local setup. Replace the prefix with your real domain
once the site is live:

```
http://localhost/ramson-portfolio/public
```

---

## First, the honest part

**Nothing here blocks launch.** The site is deployable today with every task
below still open — it would just read thinner than the work behind it.

**Some tasks say "confirm" rather than "do", and that is deliberate.** My
sandbox only ever held a seeded copy of the database, never your real data. So
whether your LinkedIn URL, your photograph and your availability note are
already set is genuinely unknown to me. Where I cannot see, I ask rather than
assume.

---

## Tier 1 — makes the site look finished

These four are the difference between a portfolio that reads complete and one
that reads abandoned halfway.

### 1.1 Services — three or four

**Where:** `/admin/services` → *Add a service*
**Time:** 20 minutes
**If you skip it:** the Services section does not appear on the site at all —
no heading, no empty grid. Nothing looks broken. The nav link disappears too.

Written in **business outcomes, not technology names**. The difference:

> ❌ "PHP & MySQL development"
> ✅ "Booking systems that stop double-bookings and no-shows"

Your two real projects already point at two strong candidates — booking
automation, and custom management systems for organisations running on paper.
I will not commit you to offering something you have not chosen.

### 1.2 Process — roughly four steps

**Where:** `/admin/process` → *Add a process step*
**Time:** 20 minutes
**If you skip it:** same as above — the section simply does not exist.

A client reads this to answer one question: *what is working with him actually
like?* Something in the shape of "we talk about the problem before any code" →
"I build the smallest version that works" → and so on.

Your words, not mine. A process written by someone else reads false
immediately, and that is the whole reason this section is worth having.

Step numbers come from the order, so moving a step renumbers the list for free.

### 1.3 Two answers about the School Management System — two down, two to go

**Where:** paste in chat — I write the sections and send a seed
**Time:** five minutes, one or two sentences each
**If you skip it:** the case study is no longer thin — the dashboard check now
passes — but it ends without Lessons learned, which is the section that shows
judgement most directly.

You answered two of the four: **built as a project**, and **owner and builder**.
Both are published, and the case-study header no longer says "Awaiting
confirmation" anywhere. That answer also settled a third thing by ruling it
out: **there is no Outcome section**, because nothing was deployed, so there are
no users and no results. Its absence is correct and deliberate.

Two left:

1. **Why did you build it?** What made you start — a school you knew, a course
   requirement, curiosity about the ranking problem.
2. **What was the hardest problem?** My guess from the code is getting ties and
   no-result students right in the ranking — correct me if it was something
   else. This becomes *Lessons learned*.

One other thing worth five minutes: **read the Key decisions section** at
`/work/school-management-system`. I wrote those four from comments you left in
`src/Models/Result.php`, so the reasoning is yours — but it was read, not
explained, and anything that misrepresents what you meant should be corrected.

### 1.4 A photograph of you

**Where:** `/admin/profile` → *Photograph*
**Time:** depends on whether you have one
**If you skip it:** the `RT` monogram shows. That is a real designed state, not
a placeholder — but a face on a portfolio is worth more than a monogram, and a
plain phone photo against a plain wall is enough.

**Confirm:** you mentioned having photos on your phone. If one is already
uploaded, tick this and move on.

---

## Tier 2 — quality before launch

### 2.1 About copy

**Where:** paste in chat, then `/admin/profile`
**Time:** 15 minutes
**If you skip it:** the About section shows its "awaiting content" block.

One short paragraph in your own voice. Rough is fine — write it badly and I
will tighten it without putting words in your mouth.

### 2.2 LinkedIn — swap the auto-generated URL

**Where:** LinkedIn settings, then `/admin/profile`
**Time:** 5 minutes
**If you skip it:** the link still works, but the stored value is LinkedIn's
auto-generated handle, which can change and break the link.

Set the custom URL (`linkedin.com/in/tebit-ramson-titih` or similar), then
replace the stored value.

**Confirm:** you may have done this already.

### 2.3 School Management System screenshots

**Where:** `/admin/projects` → School Management System → *Screenshots*
**Time:** 10 minutes
**If you skip it:** the project card shows the placeholder frame, and the
dashboard keeps flagging it.

**The report card output is the one worth having.** It is the thing the whole
system exists to produce, and the thing a visitor will understand instantly.

Landscape, roughly 16:10. The pipeline crops and converts — just do not send a
full-page screenshot, or the top gets cut.

### 2.4 Availability status

**Where:** `/admin/profile`
**Time:** 1 minute

`Available for opportunities` · `Open to select projects` · `Not currently
available`

**Confirm:** likely already set.

---

## Tier 3 — launch

### 3.1 Domain and hosting

**Where:** chat, then follow `06-DEPLOYMENT.md`
**Time:** an evening

Since money is the constraint right now:

- **Free domain** — the [GitHub Student Developer Pack](https://education.github.com/pack)
  gives a free `.me` for one year through Namecheap. You are a student at
  Saint Louis University Institute Douala; a student ID or school email
  verifies it.
- **Free hosting** — InfinityFree runs PHP 8.3 with free SSL and no card.
  Avoid AwardSpace: its free plan is still PHP 7, which this app rejects.
- **Test before trusting it** — upload the repo and run
  `php bin/verify-production.php`. **If GD is missing, walk away** — every
  image on your site is produced by GD.

Section 0 of `06-DEPLOYMENT.md` has the full checklist and which answers mean
walk away.

### 3.2 CV as a PDF

**Where:** chat
**Time:** 5 minutes if it exists
**If you skip it:** the download button stays hidden, so nothing 404s. No harm.

### 3.3 The reply-time promise

**Where:** chat
**Time:** 1 minute
**If you skip it:** the contact form says a reply goes to the address they
gave, and nothing about when. **That is the honest default, not a gap.**

Only promise what you will actually meet — "usually within 24 hours", "within
two business days", or nothing. A missed promise here is worse than no promise.

---

## Tier 4 — small, optional

### 4.1 Add a `bootstrap` skill and re-seed

**Where:** `/admin/skills` → add **Bootstrap** → then `php bin/migrate.php --seed`
**Time:** 2 minutes

The School Management System loads Bootstrap 5.3.3 from a CDN, but only five
technologies were tagged. Seed `0006` adds the tag **only if the skill
exists** — it will not invent vocabulary. Until then the stack list is
incomplete.

### 4.2 The School Management System README is empty

**Where:** that repository, not this one
**Time:** 15 minutes

It is 0 bytes. Your case study now links there, so anyone who follows the link
lands on a blank page. Even three paragraphs — what it does, how to run it,
what it is built with — is a large improvement.

I can draft it from the code if you want.

### 4.3 Should the video studio be a third project?

**Where:** chat

`ai-script-to-video-studio` is a stronger candidate than when this was first
asked: 451 tests, a real cost model with a hard budget cap, a provider-agnostic
adapter layer. Featured, listed without a case study, or left out — your call.

### 4.4 Do you have real experience entries?

**Where:** chat

An internship, paid freelance work, or the School Management System if a real
school engaged you. **Phase 7 deliberately built no Experience table**, because
inventing employment history is excluded and an empty screen feeding an empty
section is schema that exists for nothing.

If yes, it is a small addition. If no, the roadmap records why not and we move
on.

---

## Suggested order

If you only do three things, do **1.1**, **1.2** and **1.3** — twenty minutes,
twenty minutes, five minutes. That is the whole visible difference between a
site that looks finished and one that does not.

Then **3.1**, because a portfolio nobody can open does not do its job.

Everything else can land after launch, through the admin, which is exactly what
Phases 5 to 8 were built for.
