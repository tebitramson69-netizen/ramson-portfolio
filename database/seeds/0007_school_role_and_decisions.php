<?php

/**
 * Seed: the School Management System's role, status and key decisions.
 *
 * WHY THIS IS A SEPARATE FILE FROM 0006.
 *
 * 0006's header makes a narrow promise — every claim in it was read out of the
 * repository and can be checked against a named file. The two fields at the
 * top of this file cannot be: they are Tebit Ramson Titih's answers, given in
 * his own words, to questions no repository can answer. Mixing the two sources
 * into one seed would make that promise unverifiable, so they stay apart. It
 * is the same split 0003 and 0005 use for Rendo: structure in one file, his
 * words in another.
 *
 * HIS ANSWER, verbatim: "it was built as a project, role is owner and builder."
 *
 * That answer settles two stored fields and rules out a third section:
 *
 *   - projects.role          'Owner and builder'
 *   - projects.status_label  'Built as a project'
 *   - NO outcome section. Built as a project means nothing was deployed to a
 *     school, so there are no users, no adoption and no results. An Outcome
 *     section here could only be filled by inventing one, and the standing
 *     rule on this project is that unverifiable claims do not ship. Its
 *     absence is an answer, not a gap — the template renders nothing at all
 *     for a section that does not exist.
 *
 * THE DECISIONS SECTION IS SOURCED, NOT WRITTEN FOR HIM. Each of the four
 * below is a fork already recorded, with its reasoning, in a comment he wrote
 * in src/Models/Result.php of that repository:
 *
 *   line 305  "DB-free so it can be unit-tested"
 *   line 222  "ranking == displayed report-card average by construction"
 *   line 197  "so existing callers that cannot supply a class id keep their
 *              old behaviour"
 *   line 304  "equal averages share a position and unranked students get
 *              position null"
 *
 * The prose states the alternative each comment implies was rejected. Where it
 * goes beyond the comment it says what the code does, never what he felt or
 * intended — and it is worth him reading once to confirm it matches what he
 * meant.
 *
 * OVERLAP WITH 0006's TECHNICAL SECTION IS DELIBERATE, not an oversight.
 * Technical says what the code does; decisions says what else it could have
 * done and why it does not. A reader who skips one still gets a complete
 * section, which is the point of case-study sections being separable.
 *
 * AUTHORSHIP. That repository contains a CLAUDE.md, so it was built with AI
 * assistance like Rendo. The copy below therefore describes decisions and
 * their consequences, never how much was typed unaided, and the words
 * "hand-coded", "from scratch" and "wrote every line" must never appear. See
 * the authorship rule in docs/portfolio/04-CONTENT-INVENTORY.md.
 *
 * NOT WRITTEN YET: the lessons section, which needs his answer to "what was
 * the hardest problem" — the last of the four questions in
 * docs/portfolio/07-WHAT-I-NEED-FROM-YOU.md.
 *
 * Section ORDER is stored, not derived — ProjectRepository::sectionsFor()
 * sorts by sort_order, so these values land in the canonical
 * SectionKey::ORDER sequence beside the sections seeded in 0003 and 0006:
 * overview 0, problem 1, solution 2, role 3, technical 8, decisions 12. The
 * same values 0005 uses for Rendo.
 *
 * Idempotent, and it will not overwrite him: both column updates require the
 * column to be empty, so anything typed at /admin/projects survives a re-run.
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
 * Role and status.
 *
 * Guarded on the column being empty rather than written unconditionally. 0004
 * sets Rendo's status_label with a plain UPDATE, and that is the behaviour
 * worth not copying: once the admin screens exist, a seed that asserts a value
 * over an edited one is a seed that silently discards work.
 *
 * Both run as one statement each, with the guard in the WHERE clause, so there
 * is no read-then-write window.
 * ---------------------------------------------------------------------------
 */
$pdo->prepare(
    "UPDATE projects
     SET role = :role
     WHERE id = :id AND role = ''"
)->execute([
    'role' => 'Owner and builder',
    'id'   => (int) $projectId,
]);

$pdo->prepare(
    "UPDATE projects
     SET status_label = :status
     WHERE id = :id AND status_label = ''"
)->execute([
    // His framing, not a judgement about the project. "Built as a project"
    // says what it is without implying it was abandoned or that a school
    // rejected it, both of which "Prototype" or "Unused" would imply.
    'status' => 'Built as a project',
    'id'     => (int) $projectId,
]);

/*
 * ---------------------------------------------------------------------------
 * The two sections.
 * ---------------------------------------------------------------------------
 */
$sections = [
    [
        'key'   => 'role',
        'order' => 3,
        'body'  => <<<'TEXT'
        I own the School Management System and built it. It was built as a project rather than for a paying client, so the scope, the data model and every decision below were mine to make.
        TEXT,
    ],
    [
        'key'   => 'decisions',
        'order' => 12,
        'body'  => <<<'TEXT'
        Ranking was kept out of the database so it could be tested. Result::assignPositions() takes an array of results and returns it sorted and numbered without touching the database at all. The easier version reads the students, ranks them and writes the positions back in one pass — fewer moving parts, and untestable except by running the whole system against real data. Ranking is the one place in this project where a quiet mistake becomes a child's position on a report card, so separating the arithmetic from the fetching was worth the extra indirection.

        The rank and the printed average come from one engine rather than two. Both are derived from Score::overallAverageForStudentTerm(). Ranking could have done its own averaging — it already holds the subject scores, and a second implementation would have been a few lines. But a later change to either one could then leave a report card ranking a student second while printing an average below the student ranked third, with nothing in the code looking obviously wrong. Sharing the engine makes that disagreement impossible rather than unlikely.

        Missing coefficients fall back to a plain mean instead of failing. Overall averages are coefficient-weighted from a per-class table, and a class whose coefficients have not been entered yet is the normal state at the start of a year. Refusing that case would have been defensible, and would have stopped results working for every class until an administrator finished a configuration screen. Instead every subject defaults to a coefficient of 1, which is exactly an unweighted average — so the weighting could be added without changing the behaviour existing callers already relied on.

        Not ranked and ranked last are recorded as different facts. A student with no results is sorted to the end of the list but given no position at all, rather than the next number. Equal averages share a position, so two students on 15.0 are both second rather than second and third. Writing an absent result as last place would have been easier to display, and would have stated something untrue on a document a family keeps.
        TEXT,
    ],
];

// Heading left empty on purpose — SectionKey::heading() supplies the default,
// so changing "My role" or "Key decisions" is one edit in code rather than a
// data migration.
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
