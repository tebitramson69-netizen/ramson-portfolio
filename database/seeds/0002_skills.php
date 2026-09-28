<?php

/**
 * Seed: skill categories and skills.
 *
 * Every entry is a technology Tebit Ramson Titih listed himself. Nothing is
 * inferred from the projects, and there is no proficiency column to fill.
 *
 * Idempotent.
 *
 * @var PDO $pdo
 */

declare(strict_types=1);

$catalogue = [
    ['Frontend',  'frontend',  ['HTML', 'CSS', 'JavaScript']],
    ['Backend',   'backend',   ['PHP']],
    ['Database',  'database',  ['MySQL']],
    ['Tools',     'tools',     ['Git', 'GitHub', 'XAMPP']],
    ['Practices', 'practices', ['AI-assisted development', 'AI & automation exploration']],
];

$slugify = static function (string $value): string {
    $slug = strtolower(trim($value));
    $slug = preg_replace('/[^a-z0-9]+/', '-', $slug) ?? '';

    return trim($slug, '-');
};

$insertCategory = $pdo->prepare(
    'INSERT INTO skill_categories (name, slug, sort_order)
     VALUES (:name, :slug, :sort_order)
     ON DUPLICATE KEY UPDATE name = VALUES(name), sort_order = VALUES(sort_order)'
);

$findCategory = $pdo->prepare('SELECT id FROM skill_categories WHERE slug = ?');

$insertSkill = $pdo->prepare(
    'INSERT INTO skills (category_id, name, slug, sort_order)
     VALUES (:category_id, :name, :slug, :sort_order)
     ON DUPLICATE KEY UPDATE category_id = VALUES(category_id),
                             name        = VALUES(name),
                             sort_order  = VALUES(sort_order)'
);

foreach ($catalogue as $categoryOrder => [$categoryName, $categorySlug, $skills]) {
    $insertCategory->execute([
        'name'       => $categoryName,
        'slug'       => $categorySlug,
        'sort_order' => $categoryOrder,
    ]);

    $findCategory->execute([$categorySlug]);
    $categoryId = (int) $findCategory->fetchColumn();

    foreach ($skills as $skillOrder => $skillName) {
        $insertSkill->execute([
            'category_id' => $categoryId,
            'name'        => $skillName,
            'slug'        => $slugify($skillName),
            'sort_order'  => $skillOrder,
        ]);
    }
}
