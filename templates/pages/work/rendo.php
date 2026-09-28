<?php
/**
 * Case study — Rendo.
 *
 * PHASE 2 SCOPE: the approved Phase 1 markup, unchanged in appearance, moved
 * into the view layer. The routing and the 404 path around it are real; the
 * content becomes database-driven in Phase 6 when the projects and
 * project_sections tables exist. Nothing here is invented — the sections
 * awaiting input still render the designed pending state.
 *
 * @var \App\Domain\Profile\Profile|null $profile
 */
?>
<div class="progress" aria-hidden="true"></div>

<article>
<header class="cs-hero">
  <div class="container">

    <a class="cs-hero__back" href="<?= e(route_url('/')) ?>#work">
      <span aria-hidden="true">&larr;</span> All work
    </a>

    <p class="t-eyebrow u-mt-6">
      Business automation · SaaS
    </p>

    <h1 class="t-display-1 cs-hero__title">Rendo</h1>

    <p class="t-lead cs-hero__summary">
      A business and booking automation platform that helps clinics, dental
      practices and beauty studios handle client messaging, bookings and
      appointment reminders through WhatsApp.
    </p>

    <div class="cs-meta cs-hero__meta">
      <div>
        <p class="cs-meta__label">Role</p>
        <p class="cs-meta__value t-muted">Awaiting confirmation</p>
      </div>
      <div>
        <p class="cs-meta__label">Stack</p>
        <p class="cs-meta__value t-muted">Awaiting confirmation</p>
      </div>
      <div>
        <p class="cs-meta__label">Category</p>
        <p class="cs-meta__value">Business automation</p>
      </div>
      <div>
        <p class="cs-meta__label">Status</p>
        <p class="cs-meta__value t-muted">Awaiting confirmation</p>
      </div>
    </div>

    <!-- GitHub and Live buttons render ONLY when the CMS holds a URL.
         A dead link is worse than no link, so nothing is shown here yet. -->

  </div>
</header>


<!-- ======================================================================
     CASE STUDY BODY

     The full 18-section structure is specified in PRD §I.3. Every section is
     optional at the data layer and simply DOES NOT RENDER when empty — never
     an orphan heading, never "Coming soon", never lorem ipsum.

     Shown below: the sections that can be written from verified information,
     plus the reserved structure for those awaiting your input.
     ====================================================================== -->
<div class="section">
  <div class="container">
    <div class="cs-layout">

      <!-- Table of contents -->
      <nav class="cs-toc" aria-label="On this page">
        <h2 class="cs-toc__heading">On this page</h2>
        <ul class="cs-toc__list">
          <li><a class="cs-toc__link" href="#overview"  data-spy-link>Overview</a></li>
          <li><a class="cs-toc__link" href="#problem"   data-spy-link>The problem</a></li>
          <li><a class="cs-toc__link" href="#context"   data-spy-link>Context</a></li>
          <li><a class="cs-toc__link" href="#solution"  data-spy-link>The solution</a></li>
          <li><a class="cs-toc__link" href="#features"  data-spy-link>Features</a></li>
          <li><a class="cs-toc__link" href="#role"      data-spy-link>My role</a></li>
          <li><a class="cs-toc__link" href="#technical" data-spy-link>Technical</a></li>
          <li><a class="cs-toc__link" href="#decisions" data-spy-link>Key decisions</a></li>
          <li><a class="cs-toc__link" href="#outcome"   data-spy-link>Outcome</a></li>
          <li><a class="cs-toc__link" href="#lessons"   data-spy-link>Lessons</a></li>
        </ul>
      </nav>

      <div class="cs-body">

        <section class="cs-section" id="overview" data-reveal>
          <p class="cs-section__index">01</p>
          <h2 class="t-display-3 cs-section__title">Overview</h2>
          <div class="cs-section__body t-prose">
            <p>
              Rendo is a business and booking automation platform built for
              appointment-driven businesses — clinics, dental practices, beauty
              studios and similar. It brings customer messaging, booking and
              appointment reminders into one automated workflow, operating
              through WhatsApp rather than asking clients to learn a new app.
            </p>
            <p>
              The product includes business onboarding and a waitlist, and is
              presented through a SaaS-style landing page.
            </p>
          </div>
        </section>

        <section class="cs-section" id="problem" data-reveal>
          <p class="cs-section__index">02</p>
          <h2 class="t-display-3 cs-section__title">The problem</h2>
          <div class="cs-section__body t-prose">
            <blockquote class="cs-quote">
              Client conversations happen on WhatsApp. The calendar lives
              somewhere else. Bookings fall through the gap.
            </blockquote>
            <p>
              Small appointment-based businesses coordinate with customers
              through the messaging app both sides already use. But that
              conversation is not connected to any booking system, so
              appointments are recorded by hand, reminders depend on someone
              remembering to send them, and a missed message is a lost booking.
            </p>
          </div>
        </section>

        <section class="cs-section" id="context" data-reveal>
          <p class="cs-section__index">03</p>
          <h2 class="t-display-3 cs-section__title">Context</h2>
          <div class="cs-section__body">
            <div class="pending">
              <p class="pending__label">Awaiting content</p>
              <p class="pending__body">
                Who this was built for, the constraints you were working
                within, and the environment it runs in.
              </p>
              <p class="pending__ref">03-OPEN-QUESTIONS · Q5</p>
            </div>
          </div>
        </section>

        <section class="cs-section" id="solution" data-reveal>
          <p class="cs-section__index">04</p>
          <h2 class="t-display-3 cs-section__title">The solution</h2>
          <div class="cs-section__body t-prose">
            <p>
              Rather than moving businesses onto a new platform, Rendo
              automates the channel they already work in. Customer messages,
              bookings and reminders run through WhatsApp as a single workflow,
              with onboarding and a waitlist handling the business side.
            </p>
            <div class="pending u-mt-6">
              <p class="pending__label">Awaiting detail</p>
              <p class="pending__body">
                How the automation is actually implemented — this is where the
                engineering shows.
              </p>
              <p class="pending__ref">03-OPEN-QUESTIONS · Q5</p>
            </div>
          </div>
        </section>

        <section class="cs-section" id="features" data-reveal>
          <p class="cs-section__index">05</p>
          <h2 class="t-display-3 cs-section__title">Features</h2>
          <div class="cs-section__body">
            <ul class="work__features">
              <li><strong>Customer messaging</strong> — conversations handled through WhatsApp</li>
              <li><strong>Booking</strong> — appointments captured from the conversation</li>
              <li><strong>Appointment reminders</strong> — sent automatically</li>
              <li><strong>Business onboarding</strong> — getting a business set up</li>
              <li><strong>Waitlist</strong> — capturing demand beyond current capacity</li>
              <li><strong>Automated workflow</strong> — tying the above together</li>
            </ul>
          </div>
        </section>

        <section class="cs-section" id="role" data-reveal>
          <p class="cs-section__index">06</p>
          <h2 class="t-display-3 cs-section__title">My role</h2>
          <div class="cs-section__body">
            <div class="pending">
              <p class="pending__label">Awaiting content</p>
              <p class="pending__title">What you personally designed and built</p>
              <p class="pending__body">
                Recruiters look for this specifically. Be precise about what
                was yours — it is more convincing than a broader claim.
              </p>
              <p class="pending__ref">03-OPEN-QUESTIONS · Q5</p>
            </div>
          </div>
        </section>

        <section class="cs-section" id="technical" data-reveal>
          <p class="cs-section__index">07</p>
          <h2 class="t-display-3 cs-section__title">Technical implementation</h2>
          <div class="cs-section__body">
            <div class="pending">
              <p class="pending__label">Awaiting content</p>
              <p class="pending__title">The section hiring managers actually read</p>
              <p class="pending__body">
                Architecture, data model, how the messaging integration works,
                and the security decisions you made.
              </p>
              <p class="pending__ref">03-OPEN-QUESTIONS · Q5</p>
            </div>
          </div>
        </section>

        <section class="cs-section" id="decisions" data-reveal>
          <p class="cs-section__index">08</p>
          <h2 class="t-display-3 cs-section__title">Key decisions</h2>
          <div class="cs-section__body">
            <div class="pending">
              <p class="pending__label">Awaiting content</p>
              <p class="pending__title">The highest-signal section on the whole site</p>
              <p class="pending__body">
                Trade-offs you made, naming the alternative you rejected and
                why. This is the only place that demonstrates engineering
                judgement rather than output — and judgement is what gets
                people hired.
              </p>
              <p class="pending__ref">03-OPEN-QUESTIONS · Q5</p>
            </div>
          </div>
        </section>

        <section class="cs-section" id="outcome" data-reveal>
          <p class="cs-section__index">09</p>
          <h2 class="t-display-3 cs-section__title">Outcome</h2>
          <div class="cs-section__body">
            <div class="pending">
              <p class="pending__label">Awaiting content</p>
              <p class="pending__body">
                The current state, stated honestly. "Functional prototype" and
                "in development" are entirely respectable. No adoption or
                revenue figures will be written here unless you supply real
                ones.
              </p>
              <p class="pending__ref">03-OPEN-QUESTIONS · Q5</p>
            </div>
          </div>
        </section>

        <section class="cs-section" id="lessons" data-reveal>
          <p class="cs-section__index">10</p>
          <h2 class="t-display-3 cs-section__title">Lessons learned</h2>
          <div class="cs-section__body">
            <div class="pending">
              <p class="pending__label">Awaiting content</p>
              <p class="pending__body">
                What you would do differently. Counter-intuitively a strength
                signal — senior engineers recognise honest retrospection
                immediately.
              </p>
              <p class="pending__ref">03-OPEN-QUESTIONS · Q5</p>
            </div>
          </div>
        </section>

        <!-- Previous / next -->
        <nav class="cs-nav" aria-label="More projects">
          <div class="card card--link cs-nav__item">
            <p class="cs-nav__dir">Next project</p>
            <h2 class="t-heading-1 cs-nav__title">
              <a href="<?= e(route_url('/work/rendo')) ?>">School Management System</a>
            </h2>
            <p class="t-caption u-mt-2">
              Education · Full-stack web application
            </p>
          </div>
          <div class="card cs-nav__item">
            <p class="cs-nav__dir">Get in touch</p>
            <h2 class="t-heading-1 cs-nav__title">
              <a href="<?= e(route_url('/')) ?>#contact">Start a conversation</a>
            </h2>
            <p class="t-caption u-mt-2">
              Open to select projects &amp; opportunities
            </p>
          </div>
        </nav>

      </div>
    </div>
  </div>
</div>
</article>

<footer class="footer">
  <div class="container">
    <div class="footer__bottom footer__bottom--bare">
      <p>&copy; <span data-year><?= e(date('Y')) ?></span> <?= e($profile?->fullName ?? '') ?>. All rights reserved.</p>
      <p><a class="t-link" href="<?= e(route_url('/')) ?>">Back to portfolio</a></p>
    </div>
  </div>
</footer>
