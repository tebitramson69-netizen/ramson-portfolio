<?php

/**
 * Seed: profile singleton + site settings.
 *
 * Idempotent — safe to re-run. Every value here is information Tebit Ramson
 * Titih supplied directly. Nothing is inferred, and fields he has not yet
 * provided (email, WhatsApp, LinkedIn) are left NULL so the templates render
 * their designed "awaiting" state rather than a fabricated value.
 *
 * @var PDO $pdo provided by bin/migrate.php
 */

declare(strict_types=1);

$profile = [
    'full_name'          => 'Tebit Ramson Titih',

    // The approved visual mark. Kept explicit because derived initials
    // would give 'TT'. Editable from the CMS in Phase 5.
    'monogram'           => 'RT',
    'professional_title' => 'Software Engineer / Full-Stack Developer',
    'value_proposition'  => 'I build practical web systems that turn manual workflows into simple digital experiences.',
    'technology_line'    => 'Full-stack development · PHP · JavaScript · MySQL · AI & Automation',

    'short_intro'        => 'Software engineer and full-stack developer building practical web systems. Cameroon.',

    'biography'          => "I am a software engineer and full-stack developer based in Cameroon, currently studying for an HND in Software Engineering at Saint Louis University Institute Douala.\n\nMy work centres on the same problem in different settings: an organisation runs on paper, spreadsheets and scattered messages, and the people inside it spend their time on administration instead of the work itself. I build the web systems that close that gap.",

    'location'           => 'Cameroon',
    'education'          => 'HND Software Engineering',
    'institution'        => 'Saint Louis University Institute Douala',

    'availability_status' => 'selective',
    'availability_note'   => 'Open to select projects & opportunities',

    // Not yet supplied — see docs/portfolio/03-OPEN-QUESTIONS.md Q8.
    'email'        => null,
    'whatsapp'     => null,
    'linkedin_url' => null,

    'github_url'   => 'https://github.com/tebitramson69-netizen',
];

$columns      = array_keys($profile);
$placeholders = array_map(static fn (string $c): string => ':' . $c, $columns);
$updates      = array_map(static fn (string $c): string => "{$c} = VALUES({$c})", $columns);

$sql = 'INSERT INTO profile (id, ' . implode(', ', $columns) . ')'
     . ' VALUES (1, ' . implode(', ', $placeholders) . ')'
     . ' ON DUPLICATE KEY UPDATE ' . implode(', ', $updates);

$pdo->prepare($sql)->execute($profile);

$settings = [
    ['site_title',       'Tebit Ramson Titih — Software Engineer & Full-Stack Developer', 'string', 'Site title'],
    ['meta_description', 'Tebit Ramson Titih is a software engineer and full-stack developer in Cameroon, building practical web systems that turn manual workflows into simple digital experiences.', 'text', 'Default meta description'],
    ['site_locale',      'en', 'string', 'Site language'],
];

$statement = $pdo->prepare(
    'INSERT INTO settings (setting_key, setting_value, value_type, label)
     VALUES (?, ?, ?, ?)
     ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value),
                             value_type    = VALUES(value_type),
                             label         = VALUES(label)'
);

foreach ($settings as $row) {
    $statement->execute($row);
}
