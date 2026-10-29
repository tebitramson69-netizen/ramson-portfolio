<?php

/**
 * Seed: the School Management System's technical case study.
 *
 * WHERE THIS TEXT COMES FROM, because the distinction matters here more than
 * anywhere else on the site.
 *
 * Every claim below was read out of the repository at
 * github.com/tebitramson69-netizen/School-Management-System and can be
 * checked against a named file. Nothing describes an OUTCOME — no schools
 * served, no hours saved, no error rate, no adoption. A repository can
 * establish what code does; it cannot establish what it achieved, and the
 * standing rule on this project is that unverifiable claims do not ship.
 *
 * The sections this seed deliberately does NOT write — role, decisions,
 * lessons, outcome — are the ones only Tebit Ramson Titih can answer. They
 * stay absent rather than being filled with plausible wording, and the public
 * template renders nothing at all for an absent section.
 *
 * AUTHORSHIP. The repository contains a CLAUDE.md, so this project was built
 * with AI assistance like Rendo. The copy below therefore describes the
 * SYSTEM and its behaviour, never how much of it was typed by hand, and the
 * words "hand-coded", "from scratch" and "wrote every line" must never appear.
 * See the authorship rule in docs/portfolio/04-CONTENT-INVENTORY.md.
 *
 * Section ORDER is stored, not derived — ProjectRepository::sectionsFor()
 * sorts by sort_order, so these land in the canonical SectionKey::ORDER
 * sequence beside the overview/problem/solution seeded in 0003:
 * overview 0, problem 1, solution 2, technical 8.
 *
 * Idempotent.
 *
 * @var PDO $pdo
 */

declare(strict_types=1);

$projectId = $pdo->query(
    "SELECT id FROM projects WHERE slug = 'school-management-system'"
)->fetchColumn();

if ($projectId === false) {
    // 0003 seeds the project itself. Without it there is nothing to attach
    // to, and that is a no-op rather than an error.
    return;
}

/*
 * ---------------------------------------------------------------------------
 * The repository URL.
 *
 * Only this. live_url stays null: no deployment was found in the repository,
 * and a link that 404s is worse than no link. status_label also stays empty —
 * whether this is running for a real school is his answer, not a fact in the
 * code.
 * ---------------------------------------------------------------------------
 */
// Two placeholders for one value, not one used twice: this project sets
// PDO::ATTR_EMULATE_PREPARES => false, and a real prepared statement binds
// each named parameter once. Reusing :url raises "Invalid parameter number".
$pdo->prepare(
    'UPDATE projects
     SET github_url = :url
     WHERE id = :id AND (github_url IS NULL OR github_url = :current)'
)->execute([
    'url'     => 'https://github.com/tebitramson69-netizen/School-Management-System',
    'current' => 'https://github.com/tebitramson69-netizen/School-Management-System',
    'id'      => (int) $projectId,
]);

/*
 * ---------------------------------------------------------------------------
 * Feature descriptions.
 *
 * The four titles were seeded in 0003 with empty descriptions. Each one below
 * says what the code actually does, named by the class that does it.
 * ---------------------------------------------------------------------------
 */
$features = [
    'Attendance, scores, results and ranking' =>
        'Attendance is marked per class assignment for a date in one pass rather than '
        . 'per student. Scores feed a results engine that produces each subject average, '
        . 'the coefficient-weighted overall, a letter grade from the school\'s own grade '
        . 'scale, and a pass or fail against a configurable pass mark.',

    'Automated report card generation' =>
        'A term report holds what the class master records — justified and unjustified '
        . 'absences, times late, conduct, sanctions, distinction and remarks — alongside '
        . 'the derived marks, so the printed card is assembled from stored data rather '
        . 'than re-entered.',

    'Role-based dashboards' =>
        'One middleware check guards every entry point: AuthMiddleware::requireRole() '
        . 'runs before a controller does any work, so a route cannot be reached by the '
        . 'wrong role even if a check inside it were forgotten.',

    'Administrator, teacher, student and parent access' =>
        'Four roles, each with its own view of the same data. A parent is linked to '
        . 'their children through a join table and sees those children only.',
];

$upsertFeature = $pdo->prepare(
    'UPDATE project_features
     SET description = :description
     WHERE project_id = :project_id AND title = :title AND description = \'\''
);

foreach ($features as $title => $description) {
    $upsertFeature->execute([
        'description' => $description,
        'project_id'  => (int) $projectId,
        'title'       => $title,
    ]);
}

/*
 * ---------------------------------------------------------------------------
 * The technical section.
 *
 * The ranking engine leads it, because that is the part of this project with
 * real engineering judgement in it and the part a reader who builds software
 * will recognise.
 * ---------------------------------------------------------------------------
 */
$sections = [
    [
        'key'   => 'technical',
        'order' => 8,
        'body'  => <<<'TEXT'
        PHP 8 on MySQL, in a custom MVC structure with no framework and no package manager — twenty-two tables, a front controller, and eight forward-only migrations alongside the schema. Bootstrap 5.3.3 is loaded from a CDN for the interface.

        The ranking engine is the part worth reading. Result::assignPositions() is a pure static function that takes an array and returns it ordered and numbered, with no database access at all, specifically so it can be unit-tested — and tests/core_maths_test.php tests it. Equal averages share a position, so two students on 15.0 are both second. A student with no result is sorted last and given a position of null rather than zero or last place, because "not ranked" and "ranked last" are different facts about a child.

        Overall averages are coefficient-weighted: each subject carries a per-class coefficient, defaulting to 1 so a class with none configured falls back to a plain mean instead of failing. The ranking and the average printed on the report card are produced by the same engine, Score::computeWeightedAverage(), so a student's position can never disagree with the figure beside it. The pass mark is configurable rather than hard-coded, defaulting to 10 out of 20.

        Terms are modelled the way the school year actually runs: Term::globalSequence() maps a term name and a sequence number onto a single ordering, so "Term 2, sequence 1" sorts as the third assessment of the year rather than the first.

        Security was a deliberate pass rather than an afterthought, and the project log records it as its own phase: CSRF tokens on every form with validation on all POSTs, session hardening with httponly and SameSite=Strict, database credentials moved out of the code into .env, password-strength rules, login rate limiting, and a forced password change on first sign-in.
        TEXT,
    ],
];

// Heading left empty on purpose — SectionKey::heading() supplies the default,
// so changing the wording is one edit in code rather than a data migration.
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
        // The heredoc is indented to match the surrounding code; PHP strips
        // the closing marker's indentation, which is exactly the amount
        // added, so the stored text carries no leading whitespace.
        'body'        => $section['body'],
        'sort_order'  => $section['order'],
    ]);
}

/*
 * ---------------------------------------------------------------------------
 * Bootstrap, which the project uses and the portfolio did not know about.
 *
 * 0003 tagged five technologies from his one-line description. The repository
 * loads Bootstrap 5.3.3 from a CDN, so the tag list was incomplete — the
 * stack strip and the JSON-LD knowsAbout property both read from this table,
 * which is why it is worth being right.
 *
 * Added only if a 'bootstrap' skill already exists, so this seed never
 * invents vocabulary; 0002 owns the skills list.
 * ---------------------------------------------------------------------------
 */
$bootstrapId = $pdo->query("SELECT id FROM skills WHERE slug = 'bootstrap'")->fetchColumn();

if ($bootstrapId !== false) {
    $pdo->prepare(
        'INSERT IGNORE INTO project_technologies (project_id, skill_id, is_primary, sort_order)
         VALUES (:project_id, :skill_id, 0, 90)'
    )->execute([
        'project_id' => (int) $projectId,
        'skill_id'   => (int) $bootstrapId,
    ]);
}
