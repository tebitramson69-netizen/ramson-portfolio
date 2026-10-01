<?php

/**
 * Seed: Rendo's role, key decisions and lessons learned.
 *
 * Supplied directly by Tebit Ramson Titih, in answer to "where did you hit a
 * fork, and why did you go that way" and "what surprised you". His wording is
 * preserved almost verbatim — paragraph breaks and contractions aside — because
 * the value of a case study is that it sounds like the person who built the
 * thing, not like a brochure.
 *
 * TWO PRESENTATION DECISIONS ARE ENCODED HERE, both his:
 *
 *   1. The tooling is not named. He worked with an AI assistant and says so
 *      freely when asked; the case study describes the DECISIONS, on the
 *      reasoning that no engineer lists their IDE either. The boundary that
 *      keeps that honest is absolute and is why the copy below says "the
 *      architecture, the product decisions and the setup were mine": nothing
 *      here states or implies that every line was written unaided, and
 *      "hand-coded", "from scratch" and "wrote every line" must never appear.
 *      See the authorship rule in docs/portfolio/04-CONTENT-INVENTORY.md.
 *
 *   2. The sales lesson keeps the decision and drops the self-criticism. The
 *      interesting part is that the product became its own demo; "I am not
 *      comfortable cold-calling" put a weakness in front of a reader who would
 *      not be there to hear the rest.
 *
 * Section ORDER is stored, not derived: ProjectRepository::sectionsFor() sorts
 * by sort_order, so these values are chosen to land in the canonical
 * SectionKey::ORDER sequence alongside the sections seeded in 0003 and 0004 —
 * overview 0, problem 1, solution 2, role 3, stack 10, decisions 12,
 * lessons 14, next 20.
 *
 * Idempotent.
 *
 * @var PDO $pdo
 */

declare(strict_types=1);

$projectId = $pdo->query("SELECT id FROM projects WHERE slug = 'rendo'")->fetchColumn();

if ($projectId === false) {
    // Nothing to attach to. 0003 seeds the project itself; if it has not run,
    // this seed is a no-op rather than an error.
    return;
}

$sections = [
    [
        'key'   => 'role',
        'order' => 3,
        'body'  => <<<'TEXT'
        I own Rendo and built it. The architecture, the product decisions and the setup were mine to make and to test.
        TEXT,
    ],
    [
        'key'   => 'decisions',
        'order' => 12,
        'body'  => <<<'TEXT'
        The biggest fork was using Supabase instead of building my own backend. My usual stack is PHP and MySQL on XAMPP, but Rendo is a WhatsApp booking assistant, and Meta has to reach my server at a public HTTPS address 24/7 to deliver every client message. My laptop cannot do that, and renting a VPS would have meant managing Linux, TLS certificates, backups and security patches before I had sent a single message. Supabase gave me Postgres, a REST API, Row Level Security, serverless functions and a cron scheduler on a free tier, so I had a live waitlist the same day.

        The trade-offs are a dependence on Supabase's quirks, and learning less about the servers underneath. I reduced the lock-in by keeping the data in plain Postgres and the webhook as standard TypeScript, so I can move it later.

        For the same reason I built the landing page and waitlist before the booking flow. A waitlist tests cheaply whether clinics actually want this, before I spend weeks building it.
        TEXT,
    ],
    [
        'key'   => 'lessons',
        'order' => 14,
        'body'  => <<<'TEXT'
        Documentation goes stale faster than I expected. Supabase had replaced its anon key with a publishable key that is sent differently, and Meta's setup screens did not match the guides I had read. Now I check the current official documentation before writing any integration code.

        I deployed the wrong version of my landing page. I had several downloaded copies with similar names, and the live site showed "storage not connected". The real fix was not better file naming — it was deploying from version control instead of from copies on my laptop, so the live site is whatever is on the main branch and the question stops existing.

        Direct outreach to businesses did not suit me, so I changed the approach rather than forcing it: the WhatsApp assistant became its own demo. A clinic owner scans a QR code and books a test appointment. Building the product well became the way to sell it.
        TEXT,
    ],
];

// Heading is left empty on purpose: SectionKey::heading() supplies the default,
// so a wording change is one edit in code rather than a data migration.
$upsert = $pdo->prepare(
    'INSERT INTO project_sections (project_id, section_key, heading, body, sort_order)
     VALUES (:project_id, :section_key, :heading, :body, :sort_order)
     ON DUPLICATE KEY UPDATE body = VALUES(body), sort_order = VALUES(sort_order)'
);

foreach ($sections as $section) {
    $upsert->execute([
        'project_id'  => (int) $projectId,
        'section_key' => $section['key'],
        'heading'     => '',
        // The heredocs above are indented to match the surrounding code; PHP
        // strips the closing marker's indentation, which is exactly the amount
        // added, so the stored text has no leading whitespace.
        'body'        => $section['body'],
        'sort_order'  => $section['order'],
    ]);
}
