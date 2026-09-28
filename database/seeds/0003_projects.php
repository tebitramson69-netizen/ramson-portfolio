<?php

/**
 * Seed: the two real featured projects.
 *
 * CONTENT INTEGRITY. Every value below is drawn from what Tebit Ramson Titih
 * supplied. Where he has not supplied something it is left NULL or empty, so
 * the templates render their designed "pending" state rather than a
 * fabricated value. Specifically absent, on purpose:
 *
 *   - Rendo's technology stack. He has not stated it, so Rendo gets NO
 *     technology rows. It would be trivial and wrong to assume PHP.
 *   - Both projects' role, GitHub URL, live URL, status and outcome.
 *   - Any user count, client, adoption figure or result. None exists.
 *
 * The prose in the overview, problem and solution sections is the wording
 * approved in Phase 1, built strictly from the supplied scope. It is seeded so
 * the case study is readable today, and is editable from the CMS in Phase 6.
 *
 * Idempotent: re-running updates in place rather than duplicating.
 *
 * @var PDO $pdo
 */

declare(strict_types=1);

$projects = [
    [
        'slug'              => 'rendo',
        'title'             => 'Rendo',
        'category_label'    => 'Business automation · SaaS',
        'problem_statement' => 'Clinics, dental practices and beauty studios lose bookings to missed messages and no-shows, because client conversations happen on WhatsApp while their calendar lives somewhere else.',
        'summary'           => 'Rendo is a business and booking automation platform that handles client messaging, bookings and appointment reminders through WhatsApp — the channel these businesses and their customers already use every day.',
        'is_featured'       => 1,
        'sort_order'        => 0,

        // Not supplied — see Q5 in 03-OPEN-QUESTIONS.md.
        'role'              => '',
        'status_label'      => '',
        'github_url'        => null,
        'live_url'          => null,
        'technologies'      => [],

        'features' => [
            ['Customer messaging through WhatsApp', ''],
            ['Booking and appointment reminders', ''],
            ['Business onboarding and waitlist', ''],
            ['Automated workflow', ''],
        ],

        'sections' => [
            'overview' => "Rendo is a business and booking automation platform built for appointment-driven businesses — clinics, dental practices, beauty studios and similar. It brings customer messaging, booking and appointment reminders into one automated workflow, operating through WhatsApp rather than asking clients to learn a new app.\n\nThe product includes business onboarding and a waitlist, and is presented through a SaaS-style landing page.",
            'problem'  => "Small appointment-based businesses coordinate with customers through the messaging app both sides already use. But that conversation is not connected to any booking system, so appointments are recorded by hand, reminders depend on someone remembering to send them, and a missed message is a lost booking.",
            'solution' => "Rather than moving businesses onto a new platform, Rendo automates the channel they already work in. Customer messages, bookings and reminders run through WhatsApp as a single workflow, with onboarding and a waitlist handling the business side.",
        ],
    ],

    [
        'slug'              => 'school-management-system',
        'title'             => 'School Management System',
        'category_label'    => 'Education · Full-stack web application',
        'problem_statement' => 'Secondary schools assemble termly results by hand — collecting scores per subject, averaging them, ranking the class, and transcribing everything onto report cards.',
        'summary'           => 'A full-stack platform built around secondary-school workflows, covering students, teachers, classes, subjects, attendance, scores and results — ending in automated report card generation with class ranking.',
        'is_featured'       => 1,
        'sort_order'        => 1,

        'role'              => '',
        'status_label'      => '',
        'github_url'        => null,
        'live_url'          => null,

        // Supplied directly: "Technologies include PHP, MySQL, JavaScript,
        // HTML and CSS."
        'technologies'      => ['php', 'mysql', 'javascript', 'html', 'css'],

        'features' => [
            ['Attendance, scores, results and ranking', ''],
            ['Automated report card generation', ''],
            ['Role-based dashboards', ''],
            ['Administrator, teacher, student and parent access', ''],
        ],

        'sections' => [
            'overview' => "A full-stack school management platform designed around secondary-school workflows. It covers students, teachers, classes, subjects, attendance, scores, results and ranking, with role-based dashboards for administrators, teachers, students and parents.",
            'problem'  => "Termly results are assembled by hand: scores collected per subject, averaged, ranked across the class, then transcribed onto individual report cards. It is slow, repetitive work, and every transcription is a chance to introduce an error.",
            'solution' => "The platform holds the school's structure — classes, subjects, teachers, students — and the scores recorded against it, then derives results, ranking and report cards from that data rather than from manual re-entry.",
        ],
    ],
];

$upsertProject = $pdo->prepare(
    'INSERT INTO projects
        (slug, title, category_label, problem_statement, summary, role,
         status_label, publication, is_featured, sort_order, github_url, live_url)
     VALUES
        (:slug, :title, :category_label, :problem_statement, :summary, :role,
         :status_label, :publication, :is_featured, :sort_order, :github_url, :live_url)
     ON DUPLICATE KEY UPDATE
        title             = VALUES(title),
        category_label    = VALUES(category_label),
        problem_statement = VALUES(problem_statement),
        summary           = VALUES(summary),
        role              = VALUES(role),
        status_label      = VALUES(status_label),
        publication       = VALUES(publication),
        is_featured       = VALUES(is_featured),
        sort_order        = VALUES(sort_order),
        github_url        = VALUES(github_url),
        live_url          = VALUES(live_url)'
);

$findProject   = $pdo->prepare('SELECT id FROM projects WHERE slug = ? AND deleted_at IS NULL');
$findSkill     = $pdo->prepare('SELECT id FROM skills WHERE slug = ?');
$clearFeatures = $pdo->prepare('DELETE FROM project_features WHERE project_id = ?');
$clearTech     = $pdo->prepare('DELETE FROM project_technologies WHERE project_id = ?');

$insertFeature = $pdo->prepare(
    'INSERT INTO project_features (project_id, title, description, sort_order)
     VALUES (?, ?, ?, ?)'
);
$insertTech = $pdo->prepare(
    'INSERT INTO project_technologies (project_id, skill_id, is_primary, sort_order)
     VALUES (?, ?, 1, ?)'
);
$upsertSection = $pdo->prepare(
    'INSERT INTO project_sections (project_id, section_key, body, sort_order)
     VALUES (:project_id, :section_key, :body, :sort_order)
     ON DUPLICATE KEY UPDATE body = VALUES(body), sort_order = VALUES(sort_order)'
);

foreach ($projects as $project) {
    $upsertProject->execute([
        'slug'              => $project['slug'],
        'title'             => $project['title'],
        'category_label'    => $project['category_label'],
        'problem_statement' => $project['problem_statement'],
        'summary'           => $project['summary'],
        'role'              => $project['role'],
        'status_label'      => $project['status_label'],
        'publication'       => 'published',
        'is_featured'       => $project['is_featured'],
        'sort_order'        => $project['sort_order'],
        'github_url'        => $project['github_url'],
        'live_url'          => $project['live_url'],
    ]);

    $findProject->execute([$project['slug']]);
    $projectId = (int) $findProject->fetchColumn();

    // Children are replaced wholesale so a re-run cannot accumulate rows.
    $clearFeatures->execute([$projectId]);
    foreach ($project['features'] as $order => [$title, $description]) {
        $insertFeature->execute([$projectId, $title, $description, $order]);
    }

    $clearTech->execute([$projectId]);
    foreach ($project['technologies'] as $order => $skillSlug) {
        $findSkill->execute([$skillSlug]);
        $skillId = $findSkill->fetchColumn();

        if ($skillId !== false) {
            $insertTech->execute([$projectId, (int) $skillId, $order]);
        }
    }

    foreach (array_values($project['sections']) as $order => $body) {
        $upsertSection->execute([
            'project_id'  => $projectId,
            'section_key' => array_keys($project['sections'])[$order],
            'body'        => $body,
            'sort_order'  => $order,
        ]);
    }
}
