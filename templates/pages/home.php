<?php
/**
 * Home page.
 *
 * The approved Phase 1 composition, unchanged in appearance. What changed in
 * Phase 2: identity content now comes from the profile table, inline style
 * attributes became utility classes so the CSP needs no 'unsafe-inline', and
 * the project cards link to real routes.
 *
 * Sections still carrying static content (Selected Work, Services, Process)
 * keep the approved markup until their tables exist — Phase 3 for projects,
 * Phase 7 for the rest. Forcing them through a database before they have one
 * would be scaffolding, not architecture.
 *
 * @var \App\Domain\Profile\Profile|null $profile
 * @var \App\Core\View                   $view
 */
$p = $profile;

$pending = static function (array $data): void {
    extract($data, EXTR_SKIP);
    require dirname(__DIR__) . '/components/pending.php';
};
?>

<!-- ======================= HERO ======================= -->
<section class="hero" id="top">
  <div class="container">
    <div class="hero__grid">

      <div class="hero__content">

        <?php if ($p !== null && $p->availabilityNote !== ''): ?>
          <p class="availability">
            <span class="availability__dot" aria-hidden="true"></span>
            <?= e($p->availabilityNote) ?>
          </p>
        <?php endif; ?>

        <!-- The h1 is the name: it is what people search for, and visual
             dominance is a styling decision, not a semantic one. -->
        <h1 class="hero__name"><?= e($p?->fullName ?? '') ?></h1>

        <p class="hero__role"><?= e($p?->professionalTitle ?? '') ?></p>

        <p class="hero__statement">
          <?php
          // The italic emphasis is a presentation decision applied to the
          // stored sentence, not extra copy: the phrase is matched in the
          // value proposition and wrapped. If it is not found, the sentence
          // simply renders plain.
          $statement = $p?->valueProposition ?? '';
          $emphasis  = 'simple digital experiences';
          $position  = mb_strpos($statement, $emphasis);

          if ($position === false) {
              echo e($statement);
          } else {
              echo e(mb_substr($statement, 0, $position))
                 . '<em>' . e($emphasis) . '</em>'
                 . e(mb_substr($statement, $position + mb_strlen($emphasis)));
          }
          ?>
        </p>

        <?php if ($p !== null && $p->technologyLine !== ''): ?>
          <p class="hero__tech"><?= e($p->technologyLine) ?></p>
        <?php endif; ?>

        <div class="btn-group hero__actions">
          <a class="btn btn--primary btn--lg" href="#work">
            View selected work
            <span class="btn__arrow" aria-hidden="true">&rarr;</span>
          </a>
          <a class="btn btn--secondary btn--lg" href="#contact">Get in touch</a>
        </div>

        <ul class="meta-list hero__facts">
          <?php if ($p !== null && $p->education !== ''): ?>
            <li><?= e($p->education) ?></li>
          <?php endif; ?>
          <?php if ($p !== null && $p->institution !== ''): ?>
            <li><?= e($p->institution) ?></li>
          <?php endif; ?>
        </ul>

        <p class="hero__cue" aria-hidden="true">Scroll</p>

      </div>

      <div class="hero__media">
        <?php require dirname(__DIR__) . '/components/portrait.php'; ?>
      </div>

    </div>
  </div>
</section>


<!-- ================= TECHNOLOGY STRIP ================= -->
<section class="techstrip" aria-label="Core technologies">
  <div class="container techstrip__inner">
    <p class="techstrip__label">Working with</p>
    <ul class="techstrip__list">
      <?php foreach ($techStrip as $technology): ?>
        <li class="techstrip__item"><?= e($technology) ?></li>
      <?php endforeach; ?>
    </ul>
  </div>
</section>


<!-- ================== SELECTED WORK ================== -->
<section class="section" id="work" aria-labelledby="work-title">
  <div class="container">

    <header class="section-head" data-reveal>
      <p class="section-index">01 — Selected work</p>
      <h2 class="t-display-2" id="work-title">Systems I have built</h2>
      <p class="t-lead">
        Projects that solve real operational problems — client bookings handled
        through the channel people already use, and school administration that
        ends in a printed report card.
      </p>
    </header>

    <div class="work">
      <?php if ($featured === []): ?>
        <?php $pending([
          'label' => 'No published projects',
          'title' => 'Projects are being added',
          'body'  => 'Published projects appear here automatically. Nothing is hard-coded in this template.',
          'ref'   => 'ProjectRepository::findPublishedForHome()',
        ]); ?>
      <?php else: ?>
        <?php foreach ($featured as $index => $project): ?>
          <?php require dirname(__DIR__) . '/components/project-featured.php'; ?>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>
</section>


<!-- ====================== ABOUT ====================== -->
<section class="section section--raised" id="about" aria-labelledby="about-title">
  <div class="container">

    <header class="section-head" data-reveal>
      <p class="section-index">02 — About</p>
      <h2 class="t-display-2" id="about-title">Background</h2>
    </header>

    <div class="about__grid" data-reveal>

      <div class="t-prose">
        <?php foreach (($p?->biographyParagraphs() ?? []) as $paragraph): ?>
          <p><?= e($paragraph) ?></p>
        <?php endforeach; ?>

        <?php $pending([
          'label' => 'Awaiting copy',
          'title' => 'Your own words',
          'body'  => 'The paragraphs above are drafted strictly from what you have told me — nothing is invented. Replace or edit them from the CMS so the voice is genuinely yours.',
          'ref'   => '03-OPEN-QUESTIONS · Q12',
          'class' => 'u-mt-6',
        ]); ?>
      </div>

      <dl class="about__facts">
        <?php
        $facts = array_filter([
            'Location'     => $p?->location,
            'Education'    => $p?->education,
            'Institution'  => $p?->institution,
            'Focus'        => 'Full-stack web systems, business automation',
            'Availability' => $p?->availabilityNote,
        ]);
        ?>
        <?php foreach ($facts as $term => $value): ?>
          <div class="about__fact">
            <dt><?= e($term) ?></dt>
            <dd>
              <?= e($value) ?><?php if ($term === 'Education'): ?> <span class="t-muted">(in progress)</span><?php endif; ?>
            </dd>
          </div>
        <?php endforeach; ?>
      </dl>

    </div>
  </div>
</section>


<!-- ====================== SKILLS ======================
     Names and groupings only. No proficiency bars, no percentages, no star
     ratings — they are unverifiable and read as junior.
     Moves to the skills table in Phase 7.                                  -->
<section class="section" id="skills" aria-labelledby="skills-title">
  <div class="container">

    <header class="section-head" data-reveal>
      <p class="section-index">03 — Technology</p>
      <h2 class="t-display-2" id="skills-title">What I work with</h2>
    </header>

    <div class="skills__grid" data-reveal>
      <?php foreach ($skillGroups as $group => $skills): ?>
        <div class="skills__group">
          <h3 class="skills__group-title"><?= e($group) ?></h3>
          <ul class="skills__list">
            <?php foreach ($skills as $skill): ?>
              <li><?= e($skill->name) ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>


<!-- ==================== HOW I WORK ==================== -->
<section class="section section--raised" id="process" aria-labelledby="process-title">
  <div class="container container--md">

    <header class="section-head" data-reveal>
      <p class="section-index">04 — How I work</p>
      <h2 class="t-display-2" id="process-title">Process</h2>
      <p class="t-lead">
        For a client, this is the section that answers the real question:
        what is working with me actually like?
      </p>
    </header>

    <div data-reveal>
      <?php $pending([
        'label' => 'Awaiting content',
        'title' => 'Your process, in your own words — roughly four steps',
        'body'  => 'This is the highest-trust, lowest-cost section on the site for turning a visitor into a client, and it is the one thing here I will not draft for you: a process written by someone else reads false immediately.',
        'ref'   => '03-OPEN-QUESTIONS · Q11',
      ]); ?>

      <ol class="process__list u-mt-7" aria-hidden="true">
        <?php foreach (['Step one', 'Step two', 'Step three', 'Step four'] as $step): ?>
          <li class="process__step">
            <span class="process__num" aria-hidden="true"></span>
            <h3 class="t-heading-2 t-muted"><?= e($step) ?></h3>
            <p class="t-body-sm t-muted">Structure reserved — awaiting your content.</p>
          </li>
        <?php endforeach; ?>
      </ol>
    </div>

  </div>
</section>


<!-- ===================== SERVICES ===================== -->
<section class="section" id="services" aria-labelledby="services-title">
  <div class="container">

    <header class="section-head" data-reveal>
      <p class="section-index">05 — Services</p>
      <h2 class="t-display-2" id="services-title">What I can build for you</h2>
      <p class="t-lead">
        Written in business outcomes rather than technology names — this
        section is read by people who do not care what the stack is.
      </p>
    </header>

    <div data-reveal>
      <?php $pending([
        'label' => 'Awaiting content',
        'title' => 'Which three or four services do you actually want to offer?',
        'body'  => 'Your real projects already suggest two strong candidates — business and booking automation, and custom management systems — but I will not commit you to offering something you have not chosen.',
        'ref'   => '03-OPEN-QUESTIONS · Q10',
      ]); ?>

      <div class="services__grid u-mt-6" aria-hidden="true">
        <?php for ($i = 0; $i < 4; $i++): ?>
          <div class="card">
            <h3 class="t-heading-2 t-muted">Service</h3>
            <p class="t-body-sm t-muted u-mt-3">Card structure reserved — awaiting your content.</p>
          </div>
        <?php endfor; ?>
      </div>
    </div>

  </div>
</section>


<!-- ==================== EXPERIENCE ==================== -->
<section class="section section--raised" id="experience" aria-labelledby="experience-title">
  <div class="container container--md">

    <header class="section-head" data-reveal>
      <p class="section-index">06 — Background</p>
      <h2 class="t-display-2" id="experience-title">Education &amp; experience</h2>
    </header>

    <ol class="timeline" data-reveal>
      <li class="timeline__item">
        <p class="timeline__period">Current</p>
        <h3 class="t-heading-1 timeline__title">HND, Software Engineering</h3>
        <p class="timeline__org">
          <?= e(trim(($p?->institution ?? '') . ' · ' . ($p?->location ?? ''), ' ·')) ?>
        </p>
        <p class="timeline__body">
          Studying software engineering while building full-stack web systems
          in PHP, MySQL and JavaScript.
        </p>
      </li>
    </ol>

    <div class="u-mt-7" data-reveal>
      <?php $pending([
        'label' => 'Optional',
        'body'  => 'If you have work experience, internships or freelance engagements to add, they go here. If you do not, this section stays as it is — one honest entry is more credible than a padded timeline.',
        'ref'   => '03-OPEN-QUESTIONS · Experience',
      ]); ?>
    </div>

  </div>
</section>


<!-- ===================== CONTACT ===================== -->
<section class="section contact" id="contact" aria-labelledby="contact-title">
  <div class="container container--sm contact__inner" data-reveal>

    <p class="section-index">07 — Contact</p>
    <h2 class="t-display-2 contact__title" id="contact-title">Let's talk about your project</h2>

    <p class="t-lead contact__lead">
      Whether you are hiring, or you run a business with a process that still
      happens on paper — tell me what you are trying to solve.
    </p>

    <?php if ($p !== null && $p->availabilityNote !== ''): ?>
      <p class="availability u-mt-6">
        <span class="availability__dot" aria-hidden="true"></span>
        <?= e($p->availabilityNote) ?>
      </p>
    <?php endif; ?>

    <?php
    // Contact channels render only when the profile actually holds them.
    // A dead mailto: or an empty wa.me link is worse than no button.
    $hasEmail    = ($p?->email ?? null) !== null;
    $hasWhatsapp = ($p?->whatsapp ?? null) !== null;
    ?>

    <?php if (!$hasEmail && !$hasWhatsapp): ?>
      <?php $pending([
        'label' => 'Awaiting details',
        'title' => 'Contact channels',
        'body'  => 'Email address, WhatsApp number and LinkedIn URL. WhatsApp matters especially here — for a Cameroonian client audience it is the primary business channel. A working contact form arrives in Phase 8.',
        'ref'   => '03-OPEN-QUESTIONS · Q8, Q9',
        'class' => 'u-mt-7 u-text-left',
      ]); ?>
    <?php endif; ?>

    <div class="btn-group contact__actions">
      <?php if ($hasEmail): ?>
        <a class="btn btn--primary btn--lg" href="<?= e_url('mailto:' . $p->email) ?>">
          Email me <span class="btn__arrow" aria-hidden="true">&rarr;</span>
        </a>
      <?php else: ?>
        <span class="btn btn--primary btn--lg" aria-disabled="true">
          Email me <span class="btn__arrow" aria-hidden="true">&rarr;</span>
        </span>
      <?php endif; ?>

      <?php if ($hasWhatsapp): ?>
        <a class="btn btn--secondary btn--lg"
           href="<?= e_url('https://wa.me/' . preg_replace('/\D+/', '', $p->whatsapp)) ?>"
           rel="noopener">WhatsApp</a>
      <?php else: ?>
        <span class="btn btn--secondary btn--lg" aria-disabled="true">WhatsApp</span>
      <?php endif; ?>
    </div>

    <ul class="meta-list contact__channels">
      <?php if (($p?->githubUrl ?? null) !== null): ?>
        <li><a class="t-link" href="<?= e_url($p->githubUrl) ?>" rel="me noopener">GitHub</a></li>
      <?php endif; ?>
      <?php if (($p?->linkedinUrl ?? null) !== null): ?>
        <li><a class="t-link" href="<?= e_url($p->linkedinUrl) ?>" rel="me noopener">LinkedIn</a></li>
      <?php else: ?>
        <li><span class="t-muted">LinkedIn — awaiting URL</span></li>
      <?php endif; ?>
    </ul>

  </div>
</section>
