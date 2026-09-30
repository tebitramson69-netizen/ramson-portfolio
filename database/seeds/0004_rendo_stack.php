<?php

/**
 * Seed: Rendo's real technology stack, live URL and status.
 *
 * Supplied directly by Tebit Ramson Titih as a layer-by-layer table with a
 * status column. That status column is honoured strictly:
 *
 *   Live or in progress  ->  recorded as a technology and described
 *   Planned (later)      ->  named only under "Next steps", never claimed
 *
 * So pg_cron, the owner dashboard, Supabase Auth, the LLM integration, mobile
 * money and the Supabase CLI deployment flow appear ONLY as planned work.
 * Tagging them would claim capability that does not exist yet.
 *
 * Idempotent.
 *
 * @var PDO $pdo
 */

declare(strict_types=1);

// --- new vocabulary -------------------------------------------------------
// Categories and skills Rendo demonstrably uses. Added to the shared skills
// table so the project tags, the skills section and the JSON-LD knowsAbout
// all stay one vocabulary.
$categories = [
    ['Platform & services', 'platform-services', 35],
];

$skills = [
    // slug                  name                     category slug        order
    ['typescript',           'TypeScript',            'backend',           1],
    ['deno',                 'Deno',                  'backend',           2],
    ['postgresql',           'PostgreSQL',            'database',          1],
    ['supabase',             'Supabase',              'platform-services', 0],
    ['netlify',              'Netlify',               'platform-services', 1],
    ['whatsapp-cloud-api',   'WhatsApp Cloud API',    'platform-services', 2],
    ['vs-code',              'VS Code',               'tools',             3],
];

$insertCategory = $pdo->prepare(
    'INSERT INTO skill_categories (name, slug, sort_order) VALUES (:name, :slug, :sort_order)
     ON DUPLICATE KEY UPDATE name = VALUES(name), sort_order = VALUES(sort_order)'
);
foreach ($categories as [$name, $slug, $order]) {
    $insertCategory->execute(['name' => $name, 'slug' => $slug, 'sort_order' => $order]);
}

$findCategory = $pdo->prepare('SELECT id FROM skill_categories WHERE slug = ?');
$insertSkill  = $pdo->prepare(
    'INSERT INTO skills (category_id, name, slug, sort_order) VALUES (:cid, :name, :slug, :sort_order)
     ON DUPLICATE KEY UPDATE category_id = VALUES(category_id), name = VALUES(name),
                             sort_order  = VALUES(sort_order)'
);

foreach ($skills as [$slug, $name, $categorySlug, $order]) {
    $findCategory->execute([$categorySlug]);
    $categoryId = $findCategory->fetchColumn();

    if ($categoryId !== false) {
        $insertSkill->execute([
            'cid' => (int) $categoryId, 'name' => $name, 'slug' => $slug, 'sort_order' => $order,
        ]);
    }
}

// --- Rendo ----------------------------------------------------------------
$pdo->prepare(
    "UPDATE projects
     SET live_url     = :live,
         status_label = :status,
         year_label   = :year
     WHERE slug = 'rendo'"
)->execute([
    'live'   => 'https://rendo-cm.netlify.app',
    // Honest: the landing page and waitlist are live, the booking flow is not.
    'status' => 'Landing page and waitlist live · booking flow in development',
    'year'   => '2026',
]);

$projectId = (int) $pdo->query("SELECT id FROM projects WHERE slug = 'rendo' AND deleted_at IS NULL")
    ->fetchColumn();

if ($projectId === 0) {
    return;
}

// Primary tags are the handful shown on the card; the rest appear on the case
// study. Only live or in-progress technologies are tagged at all.
$tags = [
    ['typescript',         1],
    ['supabase',           1],
    ['postgresql',         1],
    ['whatsapp-cloud-api', 1],
    ['netlify',            1],
    ['javascript',         0],
    ['html',               0],
    ['css',                0],
    ['deno',               0],
];

$pdo->prepare('DELETE FROM project_technologies WHERE project_id = ?')->execute([$projectId]);

$findSkill  = $pdo->prepare('SELECT id FROM skills WHERE slug = ?');
$insertTech = $pdo->prepare(
    'INSERT INTO project_technologies (project_id, skill_id, is_primary, sort_order)
     VALUES (?, ?, ?, ?)'
);

foreach ($tags as $order => [$slug, $isPrimary]) {
    $findSkill->execute([$slug]);
    $skillId = $findSkill->fetchColumn();

    if ($skillId !== false) {
        $insertTech->execute([$projectId, (int) $skillId, $isPrimary, $order]);
    }
}

// --- case-study sections --------------------------------------------------
$sections = [
    'stack' => <<<'TEXT'
    Rendo runs on a serverless stack, chosen so the product can ship and operate without anyone administering servers.

    The landing page is static HTML, CSS and vanilla JavaScript, served by Netlify, with the waitlist form writing directly to the database.

    That database is Supabase Postgres, in the London region. Pages read and write it through the Supabase REST API, and Row Level Security policies decide which rows a given caller is allowed to touch — the access rules live in the database itself rather than in each page that talks to it.

    The backend logic runs as Supabase Edge Functions on the Deno runtime: the webhook that receives WhatsApp messages and decides what to reply. Messaging goes through the WhatsApp Cloud API on Meta's Graph API v25.0, currently against a test number.

    TypeScript is used for the backend rather than plain JavaScript, so type errors surface before the code runs instead of inside a customer conversation.
    TEXT,

    'next' => <<<'TEXT'
    Planned, and deliberately not claimed as built:

    Scheduled reminders, using pg_cron and pg_net inside Supabase to run a job every few minutes and send a message before each appointment.

    An owner dashboard on Netlify with Supabase Auth, so a clinic can see its bookings and take over a conversation.

    Free-text understanding through a large language model, so a message like "can I come Saturday afternoon?" is handled without a rigid menu.

    Mobile-money subscriptions through MTN MoMo and Orange Money, once the pilot is done and a payment provider has been chosen.

    A move to a real Rendo number and to clinics' own numbers through WhatsApp coexistence, replacing the current test number.
    TEXT,
];

$upsertSection = $pdo->prepare(
    'INSERT INTO project_sections (project_id, section_key, body, sort_order)
     VALUES (:project_id, :section_key, :body, :sort_order)
     ON DUPLICATE KEY UPDATE body = VALUES(body), sort_order = VALUES(sort_order)'
);

// The canonical display order lives in App\Domain\Project\SectionKey; these
// values only break ties within it.
$order = ['stack' => 10, 'next' => 20];

foreach ($sections as $key => $body) {
    $upsertSection->execute([
        'project_id'  => $projectId,
        'section_key' => $key,
        // Strip the heredoc indentation.
        'body'        => preg_replace('/^[ \t]+/m', '', trim($body)),
        'sort_order'  => $order[$key],
    ]);
}
