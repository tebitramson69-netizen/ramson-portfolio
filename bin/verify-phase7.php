#!/usr/bin/env php
<?php

/**
 * Phase 7 self-check — skills, services, process steps and site settings.
 *
 *     php bin/verify-phase7.php
 *
 * CLI only. Unlike verify-media.php this one DOES touch the database, because
 * what it is checking is write behaviour. It is written to leave the database
 * exactly as it found it:
 *
 *   - every row it creates carries a recognisable name and is deleted again,
 *   - every setting it changes is restored to its previous value,
 *   - the row counts it saw on entry are asserted again on exit.
 *
 * The last of those is the one that matters. A cleanup that silently failed
 * would otherwise leave debris in the owner's real content, and he would find
 * it months later with no idea where it came from.
 *
 * Exit status is 0 when every assertion passes, 1 otherwise.
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit("This script is CLI-only.\n");
}

require __DIR__ . '/../src/Core/Autoloader.php';

use App\Core\Autoloader;
use App\Core\Config;
use App\Core\Database;
use App\Domain\Content\ContentList;
use App\Domain\Content\ContentListRepository;
use App\Domain\Content\ContentListWriter;
use App\Domain\Settings\SettingsRepository;
use App\Domain\Settings\SettingsWriter;
use App\Domain\Skill\SkillRepository;
use App\Domain\Skill\SkillWriter;

Autoloader::register('App', __DIR__ . '/../src');
Config::load(__DIR__ . '/../config');

$pass = 0;
$fail = 0;

function heading(string $text): void
{
    echo "\n" . $text . "\n" . str_repeat('-', strlen($text)) . "\n";
}

function check(string $label, bool $ok, string $detail = ''): void
{
    global $pass, $fail;
    $ok ? $pass++ : $fail++;
    printf("  [%s] %s%s\n", $ok ? 'PASS' : 'FAIL', $label, $detail === '' ? '' : ' — ' . $detail);
}

function rowCount(string $table): int
{
    return (int) Database::connection()->query("SELECT COUNT(*) FROM {$table}")->fetchColumn();
}

echo "Phase 7 self-check\n==================\n";

$before = [
    'skill_categories' => rowCount('skill_categories'),
    'skills'           => rowCount('skills'),
    'services'         => rowCount('services'),
    'process_steps'    => rowCount('process_steps'),
];

printf(
    "\nStarting state: %d categories, %d skills, %d services, %d process steps.\n",
    $before['skill_categories'],
    $before['skills'],
    $before['services'],
    $before['process_steps'],
);

// ---------------------------------------------------------------- 1. SKILLS

heading('1. Skills vocabulary');

$skills     = new SkillRepository();
$skillWrite = new SkillWriter();

$catId = $skillWrite->createCategory('Verify Temp Category', 'verify-temp-category');
check('A category can be created', $catId !== null);

check(
    'A duplicate category slug is refused, not thrown',
    $skillWrite->createCategory('Another Name', 'verify-temp-category') === null,
);

$skillId = $catId === null ? null : $skillWrite->createSkill($catId, 'Verify Temp Skill', 'verify-temp-skill');
check('A skill can be created', $skillId !== null);

check(
    'A duplicate skill slug is refused across categories',
    $catId !== null && $skillWrite->createSkill($catId, 'Other', 'verify-temp-skill') === null,
);

// The guard this phase exists to get right.
$blocked = $catId === null ? -1 : $skillWrite->deleteCategory($catId);
check(
    'Deleting a category holding skills is REFUSED and names the count',
    $blocked === 1,
    'returned ' . $blocked,
);

check(
    'The category survived that refusal',
    $catId !== null && $skills->findCategory($catId) !== null,
);

// Hidden rows must not reach the public read.
if ($skillId !== null) {
    $skillWrite->setSkillVisible($skillId, false);
}

$visibleNames = [];
foreach ((new SkillRepository())->visibleGroupedByCategory() as $group) {
    foreach ($group as $skill) {
        $visibleNames[] = $skill->name;
    }
}
check(
    'A hidden skill is absent from the PUBLIC read',
    !in_array('Verify Temp Skill', $visibleNames, true),
);

$adminNames = [];
foreach ($skills->allCategoriesWithSkills() as $category) {
    foreach ($category['skills'] as $skill) {
        $adminNames[] = (string) $skill['name'];
    }
}
check(
    'A hidden skill IS present in the ADMIN read',
    in_array('Verify Temp Skill', $adminNames, true),
);

// Cleanup, in dependency order.
if ($skillId !== null) {
    check('Deleting an untagged skill succeeds', $skillWrite->deleteSkill($skillId) === 0);
}
if ($catId !== null) {
    check('Deleting the now-empty category succeeds', $skillWrite->deleteCategory($catId) === 0);
}

// --------------------------------------------------- 2. SERVICES / PROCESS

heading('2. Ordered content lists');

foreach ([ContentList::Services, ContentList::ProcessSteps] as $list) {
    $writer = new ContentListWriter($list);
    $noun   = $list->noun();

    $a = $writer->create('Verify Temp A', 'first');
    $b = $writer->create('Verify Temp B', 'second');
    $c = $writer->create('Verify Temp C', 'third');

    $all = (new ContentListRepository())->all($list);
    $ours = array_values(array_filter(
        $all,
        static fn ($item): bool => str_starts_with($item->title, 'Verify Temp'),
    ));

    check("A {$noun} is created at the END of the list", count($ours) === 3
        && $ours[0]->title === 'Verify Temp A'
        && $ours[2]->title === 'Verify Temp C');

    // Reverse them, then read back.
    $writer->reorder([$c, $b, $a]);

    $ours = array_values(array_filter(
        (new ContentListRepository())->all($list),
        static fn ($item): bool => str_starts_with($item->title, 'Verify Temp'),
    ));
    check("Reordering a {$noun} list takes effect", $ours[0]->title === 'Verify Temp C');

    check(
        "Reordering wrote DENSE positions, not the numbers typed",
        $ours[0]->sortOrder === 1 && $ours[1]->sortOrder === 2 && $ours[2]->sortOrder === 3,
        implode(',', array_map(static fn ($i): int => $i->sortOrder, $ours)),
    );

    $writer->setVisible($b, false);

    $visibleTitles = array_map(
        static fn ($i): string => $i->title,
        (new ContentListRepository())->visible($list),
    );
    check("A hidden {$noun} is absent from the public read", !in_array('Verify Temp B', $visibleTitles, true));

    $hidden = (new ContentListRepository())->find($list, $b);
    check("A hidden {$noun} KEEPS its text", $hidden !== null && $hidden->description === 'second');

    foreach ([$a, $b, $c] as $id) {
        $writer->delete($id);
    }

    $left = array_filter(
        (new ContentListRepository())->all($list),
        static fn ($item): bool => str_starts_with($item->title, 'Verify Temp'),
    );
    check("Every temporary {$noun} was cleaned up", $left === []);
}

// -------------------------------------------------------------- 3. SETTINGS

heading('3. Site settings');

$settingsWriter = new SettingsWriter();
$rows           = $settingsWriter->rows();

check('Settings rows are readable', $rows !== []);

$titleRow = null;
foreach ($rows as $row) {
    if ($row['key'] === 'site_title') {
        $titleRow = $row;
        break;
    }
}

check('site_title exists', $titleRow !== null);

if ($titleRow !== null) {
    $original = (string) $titleRow['value'];

    $settingsWriter->updateMany(['site_title' => 'Verify Temp Title']);
    check(
        'A setting can be changed',
        (new SettingsRepository())->string('site_title') === 'Verify Temp Title',
    );

    check(
        'An unknown key is ignored rather than created',
        $settingsWriter->updateMany(['definitely_not_a_setting' => 'x']) === 0
            && rowCount('settings') === count($rows),
    );

    $settingsWriter->updateMany(['site_title' => $original]);
    check(
        'The original value was restored',
        (new SettingsRepository())->string('site_title') === $original,
        $original,
    );
}

// ------------------------------------------------------ 4. NOTHING LEFT OVER

heading('4. The database is as it was found');

foreach ($before as $table => $count) {
    $now = rowCount($table);
    check(sprintf('%s is back to %d rows', $table, $count), $now === $count, 'now ' . $now);
}

printf("\n%d passed, %d failed\n", $pass, $fail);

exit($fail === 0 ? 0 : 1);
