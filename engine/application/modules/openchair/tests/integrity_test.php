<?php
/**
 * OpenChair product-integrity tests.
 *
 * These are structural assertions over the source, not unit tests, and that is
 * deliberate: the rules in 05-prd.md §8 are about what the code CANNOT do, and
 * the honest way to test "there is no path from Pitch into telephony" is to prove
 * the path is absent rather than to call it and see what happens.
 *
 * Run:  php engine/application/modules/openchair/tests/integrity_test.php
 * Exit: 0 all green, 1 on any failure. Wire into CI as a release gate.
 */

// __DIR__ = application/modules/openchair/tests
$ROOT   = dirname(__DIR__, 3);            // → application
$MODULE = $ROOT . '/modules/openchair';
$VIEWS  = $MODULE . '/views/pages';

$pass = 0; $fail = 0;

/**
 * @param string $name
 * @param bool   $ok
 * @param string $detail
 */
function check($name, $ok, $detail = '')
{
    global $pass, $fail;
    if ($ok) { $pass++; echo "  PASS  $name\n"; }
    else     { $fail++; echo "  FAIL  $name" . ($detail ? " — $detail" : '') . "\n"; }
}

/**
 * Every .php file under $dir, EXCLUDING this tests directory.
 *
 * The suite has to name the symbols it forbids in order to search for them, so
 * scanning itself would make every rule-7 check fail on its own banned-list.
 *
 * @param string $dir
 * @return string[]
 */
function php_files($dir)
{
    $out = [];
    if (!is_dir($dir)) { return $out; }
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
    foreach ($it as $f) {
        if (!$f->isFile() || strtolower($f->getExtension()) !== 'php') { continue; }
        $path = str_replace('\\', '/', $f->getPathname());
        if (strpos($path, '/tests/') !== false) { continue; }
        $out[] = $f->getPathname();
    }
    return $out;
}

$module_files = php_files($MODULE);
echo "OpenChair integrity tests — " . count($module_files) . " module files\n\n";

// A suite that scans nothing reports all-green, which is worse than a failure:
// it says the rules hold when nothing was checked. Refuse to run instead.
if (count($module_files) < 5) {
    echo "  ABORT  found " . count($module_files) . " files under $MODULE — the suite\n";
    echo "         would pass vacuously. Check the path before trusting a green run.\n";
    exit(1);
}

// ── Rule 7 — the product drafts; the consultant sends. ───────────────────────
// This is the launch-ending one (PRD R4). No queue, no telephony, no send.
echo "Rule 7 — never auto-contact a business\n";

$banned_symbols = [
    'sequence_queue', 'twilio_sms_logs', 'twilio_call_logs', 'Twilio\\',
    'twilio_lib', 'sequence_lib', 'voice_bridge', 'send_sms', 'send_email',
];
foreach ($banned_symbols as $sym) {
    $hits = [];
    foreach ($module_files as $f) {
        if (stripos(file_get_contents($f), $sym) !== false) { $hits[] = basename($f); }
    }
    check("no reference to '$sym'", empty($hits), implode(', ', $hits));
}

// A Send button is the visible half of the same rule.
$send_hits = [];
foreach (php_files($VIEWS) as $f) {
    if (preg_match('/<button[^>]*>\s*Send\b|name=["\']send["\']|value=["\']Send["\']/i', file_get_contents($f))) {
        $send_hits[] = basename($f);
    }
}
check('no Send control in any view', empty($send_hits), implode(', ', $send_hits));

// ── Rule 2 — the unit is "online-bookable open slots", never "empty shop". ───
echo "\nRule 2 — wording lock\n";

$unit_ok = false;
$helper  = file_get_contents($MODULE . '/helpers/oc_format_helper.php');
$unit_ok = (strpos($helper, 'oc_unit') !== false);
check('oc_unit() is the single source of the unit string', $unit_ok);

// The banned phrase may appear ONLY where the copy explicitly tells the user not
// to say it. Anywhere else it would be the product making the claim itself.
$bad = [];
foreach (php_files($VIEWS) as $f) {
    $src = file_get_contents($f);
    if (stripos($src, 'empty shop') === false) { continue; }
    // Allowed form: "... and never “empty shop”" — the phrase negated in the
    // integrity disclosure. Anything else is the product saying it.
    if (!preg_match('/never\s*&ldquo;empty shop&rdquo;|never "empty shop"/i', $src)) {
        $bad[] = basename($f);
    }
}
check('"empty shop" appears only where it is explicitly disavowed', empty($bad), implode(', ', $bad));

// ── Rule 4 — always a range, never a point estimate. ────────────────────────
echo "\nRule 4 — money is always a range\n";

check('oc_revenue_range() exists', strpos($helper, 'function oc_revenue_range') !== false);

// Views must not hand-format currency; that is how a point estimate sneaks in.
$raw_money = [];
foreach (php_files($VIEWS) as $f) {
    if (preg_match('/[\'"]\$[\'"]\s*\.\s*number_format|number_format\([^)]*\)\s*\.\s*[\'"]\s*USD/i', file_get_contents($f))) {
        $raw_money[] = basename($f);
    }
}
check('no view formats currency by hand', empty($raw_money), implode(', ', $raw_money));

// ── Rule 3 — never show a number below the confidence floor. ────────────────
echo "\nRule 3 — the confidence floor\n";

check('oc_below_floor() exists', strpos($helper, 'function oc_below_floor') !== false);
check('oc_score_or_gap() returns a gap, not a number', strpos($helper, 'function oc_score_or_gap') !== false);

// Every view that prints a vacancy score must go through the gate.
$ungated = [];
foreach ([$VIEWS . '/radar.php', $VIEWS . '/today.php', $VIEWS . '/business.php'] as $f) {
    if (!file_exists($f)) { $ungated[] = basename($f) . ' (missing)'; continue; }
    $src = file_get_contents($f);
    if (strpos($src, 'latest_vacancy_bp') !== false && strpos($src, 'oc_score_or_gap') === false) {
        $ungated[] = basename($f);
    }
}
check('every score-printing view uses oc_score_or_gap()', empty($ungated), implode(', ', $ungated));

// ── Rule 5 — the "what we could not see" block is first-class. ──────────────
echo "\nRule 5 — visible disclosure\n";

$detail = file_exists($VIEWS . '/business.php') ? file_get_contents($VIEWS . '/business.php') : '';
check('Business Detail carries the disclosure block',
      strpos($detail, 'What we could not see') !== false);
check('disclosure is not hidden behind a tooltip or <details>',
      !preg_match('/<details|data-tip=["\'][^"\']*could not see/i', $detail));

// ── §4.2 — we render our own grid; we never screenshot theirs. ──────────────
echo "\nNon-goal — no screen capture of a booking platform\n";

// Machinery, not the word: Business Detail says in plain English that its grid is
// "redrawn from observed data — not a screenshot of their page", and that
// sentence is the promise, not a violation of it.
$shot = [];
foreach ($module_files as $f) {
    if (preg_match('/puppeteer|playwright|html2canvas|imagegrabscreen|->screenshot\s*\(/i', file_get_contents($f))) {
        $shot[] = basename($f);
    }
}
check('no screenshot machinery in the module', empty($shot), implode(', ', $shot));

// ── Tenancy — the global scan layer must never be user-scoped. ──────────────
echo "\nTenancy — the global/tenant split\n";

$gm = $MODULE . '/models/Oc_business_model.php';
$gsrc = file_exists($gm) ? file_get_contents($gm) : '';
// Strip comments before looking for user_id: the file explains at length WHY it
// must not scope by user, and that prose should not fail its own test.
$gcode = preg_replace('!/\*.*?\*/!s', '', $gsrc);
$gcode = preg_replace('!//.*!', '', $gcode);
check('Oc_business_model (global layer) never scopes by user_id',
      stripos($gcode, 'user_id') === false);

echo "\n" . str_repeat('─', 52) . "\n";
echo ($fail === 0 ? "ALL GREEN" : "FAILURES") . ": $pass passed, $fail failed\n";
exit($fail === 0 ? 0 : 1);
