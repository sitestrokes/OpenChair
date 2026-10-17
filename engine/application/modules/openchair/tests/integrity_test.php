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
/**
 * Source with comments stripped.
 *
 * These checks are about CODE PATHS. The files deliberately EXPLAIN the rules
 * they follow — "there is no path from here into sequence_queue" is the rule
 * being documented, not broken — so scanning raw text would fail a file for
 * being well commented, and the fix would be to delete the explanation. That is
 * the wrong incentive to build into a test.
 *
 * @param string $path
 * @return string
 */
function code_of($path)
{
    $src = file_get_contents($path);
    $src = preg_replace('!/\*.*?\*/!s', '', $src);   // block comments
    $src = preg_replace('!^\s*//.*$!m', '', $src);    // line comments
    return $src;
}

foreach ($banned_symbols as $sym) {
    $hits = [];
    foreach ($module_files as $f) {
        if (stripos(code_of($f), $sym) !== false) { $hits[] = basename($f); }
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
    // oc_chair_score_or_gap() is the Chair Score's gate (higher = busier); the
    // Vacancy Score's oc_score_or_gap() is still accepted where it survives.
    if (strpos($src, 'latest_vacancy_bp') !== false && strpos($src, 'oc_score_or_gap') === false
        && strpos($src, 'oc_chair_score_or_gap') === false) {
        $ungated[] = basename($f);
    }
}
check('every score-printing view uses a gated score helper', empty($ungated), implode(', ', $ungated));

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


// -- Generated copy: the artifact is what leaves the building. --------------
echo "
Generated pitch copy
";

$lib = $MODULE . '/libraries/Oc_pitch_lib.php';
check('one library generates every user-facing sentence', file_exists($lib));

if (file_exists($lib)) {
    $lsrc = file_get_contents($lib);
    check('banned_phrases() is declared as data, not a comment',
          strpos($lsrc, 'function banned_phrases') !== false);

    foreach (['empty shop', 'guaranteed', 'you will make', 'risk-free'] as $bad) {
        check("'" . $bad . "' is on the banned list", strpos($lsrc, "'" . $bad . "'") !== false);
    }

    // An earnings claim in generated copy is an FTC problem for the vendor as
    // much as for the user (50-risk A7.3). The banned-list array is the one
    // place these strings may legitimately appear, so it is removed first.
    $body = code_of($lib);
    $body = preg_replace('/return \[[^\]]*\];/s', '', $body);
    foreach (['guaranteed', 'you will make', 'risk-free', 'double your'] as $bad) {
        check("copy never emits '" . $bad . "'", stripos($body, $bad) === false);
    }

    check('a caveat is attached to every artifact', strpos($lsrc, 'function caveat') !== false);
    check('the email is a DRAFT -- nothing here dispatches',
          strpos($lsrc, 'function email') !== false && !preg_match('/\bmail\s*\(/', $body));
}

$pm = $MODULE . '/models/Oc_pitch_model.php';
if (file_exists($pm)) {
    $psrc = file_get_contents($pm);
    check('pitch model has mark_sent() -- a log of what a human did',
          strpos($psrc, 'function mark_sent') !== false);
    check('pitch model has NO send()', !preg_match('/function\s+send\s*\(/', code_of($pm)));
    check('pitch freezes its evidence rather than re-reading it',
          strpos($psrc, 'gzencode') !== false);
}

// -- Alerts reach the consultant, never the business (FR-6.3). --------------
echo "
Alerts
";

$am = $MODULE . '/models/Oc_alert_model.php';
if (file_exists($am)) {
    $asrc  = file_get_contents($am);
    $acode = code_of($am);

    // oc_business carries a public phone and email. Nothing here may read them:
    // a digest that could reach a scanned business would be the product
    // contacting a stranger about its own observations of them.
    check('the digest never reads a business phone number', !preg_match('/b\.phone/i', $acode));
    check('the digest never reads a business email', !preg_match('/b\.email/i', $acode));
    check('alert items store the numbers AS SENT', strpos($asrc, "'empty_chair_cents'") !== false);
    check('the digest excludes businesses below the confidence floor',
          strpos($acode, 'latest_confidence >=') !== false);
}

// -- Signature visuals: the cards are what leave the building. ---------------
// docs/60-signature-visuals.md §5. A card is forwarded, screenshotted and read
// by an owner who knows their own week; each rule below is one way a card could
// embarrass the consultant who sent it.
echo "
Signature visuals
";

$VIS   = $MODULE . '/views/visuals';
$CARDS = ['_chair_week.php', '_chair_map.php', '_receipt.php', '_same_day.php', '_progress.php'];
$vlib  = $MODULE . '/libraries/Oc_visuals_lib.php';

foreach ($CARDS as $c) {
    check("card $c exists", file_exists($VIS . '/' . $c));
}

// Rule 6 (data age) and rule 5 (what we couldn't see) ride in the shared
// footer, so every card must end with it rather than writing its own.
$footless = [];
foreach ($CARDS as $c) {
    if (!file_exists($VIS . '/' . $c)) { continue; }
    if (strpos(file_get_contents($VIS . '/' . $c), "visuals/_card_foot") === false) { $footless[] = $c; }
}
check('every card ends with the shared footer (data age, method, agency)', empty($footless), implode(', ', $footless));
$foot = file_exists($VIS . '/_card_foot.php') ? file_get_contents($VIS . '/_card_foot.php') : '';
check('the card footer prints the data age', strpos($foot, "['checked']['phrase']") !== false);

// Rule 4 on the cards: money is a range from the helpers, never hand-formatted.
$vis_money = [];
foreach (php_files($VIS) as $f) {
    if (preg_match('/[\'"]\$[\'"]\s*\.\s*number_format|number_format\([^)]*\)\s*\.\s*[\'"]\s*USD/i', file_get_contents($f))) {
        $vis_money[] = basename($f);
    }
}
check('no card formats currency by hand', empty($vis_money), implode(', ', $vis_money));

// Rule 2 on the cards.
$vis_shop = [];
foreach (php_files($VIS) as $f) {
    if (stripos(file_get_contents($f), 'empty shop') !== false) { $vis_shop[] = basename($f); }
}
check('no card says "empty shop"', empty($vis_shop), implode(', ', $vis_shop));

// "Other shops are never named on a card" (PDF p.8). The street payload is
// built in one place; it must carry a label ("Shop C"), never a shop's name.
if (file_exists($vlib)) {
    $vcode = code_of($vlib);
    check('the street payload never carries another shop\'s name',
          !preg_match('/\$s->name|\$nb->name|\$c->name/', $vcode));
    check('the street payload labels neighbours "Shop A…"', strpos($vcode, "'Shop '") !== false);
    // Rule 3 on the cards: below the floor the score is null, and so is the money.
    check('a card below the confidence floor carries no score', strpos($vcode, "'value'      => \$gap ? null") !== false);
    check('a card below the confidence floor carries no money', strpos($vcode, "'money' => \$gap ? null") !== false);
    // Rule 4: the weekly range is the Meter's own band, not re-derived.
    check('card money starts from the Meter\'s stored range', strpos($vcode, 'latest_empty_chair_hi_cents') !== false);
}

// The Chair Score has ONE definition. The SQL band and the PHP band read the
// same config keys, so a chip count and a card pill cannot disagree.
$bm = file_exists($gm) ? file_get_contents($gm) : '';
foreach (['oc_chair_hungry_below', 'oc_chair_slammed_from', 'oc_chair_wait_slammed_days'] as $key) {
    check("band rule '$key' is shared by SQL and PHP",
          strpos($bm, $key) !== false && strpos($helper, $key) !== false);
}

// Rule 10: a share page is unlisted. Rule 7: it is read-only.
$page_src = file_exists($VIS . '/card_page.php') ? file_get_contents($VIS . '/card_page.php') : '';
check('a shared card is served noindex', stripos($page_src, 'noindex') !== false);
$share_ctl = $MODULE . '/controllers/Oc_share.php';
if (file_exists($share_ctl)) {
    $sc = code_of($share_ctl);
    check('the public share controller does not extend the authenticated base', strpos($sc, 'extends App_Controller') === false);
    check('the public share controller accepts no POST', stripos($sc, "->post(") === false);
    // The throttle may READ the address, but only to hash it into a short-lived
    // cache key. It must never reach the database or the model.
    check('the public share controller stores no IP or user agent',
          !preg_match('/user_agent\(|REMOTE_ADDR|HTTP_USER_AGENT/', $sc)
          && (!preg_match('/ip_address\(/', $sc) || preg_match("/hash\('sha256',[^;]*\\\$ip/", $sc))
          && !preg_match('/(insert|update|record_open)\([^;]*\$ip/', $sc));
}
$share_model = $MODULE . '/models/Oc_share_model.php';
if (file_exists($share_model)) {
    $smc = code_of($share_model);
    check('share tokens come from a CSPRNG', strpos($smc, 'random_bytes') !== false);
    check('a share link freezes its card', strpos($smc, 'payload') !== false);
}

// The image export renders OUR markup in the browser; it is not a capture of a
// booking platform. Keep it library-free so that stays obvious.
$exp = dirname($ROOT) . '/assets/js/oc-card-export.js';
if (file_exists($exp)) {
    check('the card export uses no screenshot library',
          !preg_match('/html2canvas|puppeteer|playwright|dom-to-image/i', file_get_contents($exp)));
}

// -- Radar build: tenancy, gating, redirects, CLI, headers (docs/70) --------
echo "
Radar build — tenancy, gating, safety
";

$ctl = code_of($MODULE . '/controllers/Openchair.php');

// R6: per-agency rows are keyed by the TENANT. Passing the acting user's id to a
// tenant model would split a team's pipeline per person again.
$tenant_models = ['oc_prospect_model', 'oc_market_model', 'oc_alert_model', 'oc_scan_model'];
$leaks = [];
foreach ($tenant_models as $m) {
    if (preg_match_all('/' . $m . '->\w+\(\s*\$this->user_id/', $ctl, $mm)) { $leaks = array_merge($leaks, $mm[0]); }
}
if (preg_match('/oc_share_model->(for_business|find_own)\(\s*\$this->user_id/', $ctl, $mm)) { $leaks[] = $mm[0]; }
check('tenant models are called with the tenant id, not the acting user', empty($leaks), implode(' | ', $leaks));
check('the team for assignment is the agency (owner + sub-users)',
      strpos($ctl, "where('id', \$owner)->or_where('parent_user_id', \$owner)") !== false);

// R7: gates are enforced by URL, not only hidden in the rail.
$rl = code_of($ROOT . '/libraries/Oc_render_lib.php');
check('one can() answers the rail and the controllers', strpos($rl, 'public function can(') !== false);
check('an unknown gate is closed, not open', strpos($rl, "if (!isset(\$map[\$gate])) { return false; }") !== false);
foreach (["function ledger(" => 'ledger', "function build_digest(" => 'alerts'] as $fn => $gate) {
    $at = strpos($ctl, $fn);
    check("$fn is gated on '$gate'", $at !== false && strpos(substr($ctl, $at, 400), "_gate('$gate')") !== false);
}
check('the Slammed bucket and variant are gated', substr_count($ctl, "_gate('slammed')") >= 2);
check('the whole module is gated on core access', strpos($ctl, "_gate('core')") !== false);

// §6: no posted value goes straight into redirect() (open redirect).
check('no redirect() takes a raw posted value', !preg_match('/redirect\(\s*\$this->input->(post|get)\(/', $ctl));
check('posted back-targets go through _safe_back()', strpos($ctl, 'function _safe_back(') !== false);

// R5: the cron controller is CLI-only and refuses the web before any method runs.
$cron = $MODULE . '/controllers/Cron.php';
check('cron controller exists', file_exists($cron));
if (file_exists($cron)) {
    $cc = code_of($cron);
    check('cron refuses non-CLI requests in its constructor', (bool) preg_match('/__construct\(\)\s*\{[^}]*is_cli\(\)/s', $cc));
    check('cron does not extend the logged-in base', strpos($cc, 'extends App_Controller') === false);
}

// §6: security headers ship on every response.
$cfg = file_get_contents($ROOT . '/config/config.php');
check('hooks are enabled', (bool) preg_match("/\\\$config\['enable_hooks'\]\s*=\s*TRUE/", $cfg));
$hooks = file_get_contents($ROOT . '/config/hooks.php');
check('the security-headers hook is registered', strpos($hooks, 'Oc_security_headers') !== false);
$hk = file_exists($ROOT . '/hooks/Oc_security_headers.php') ? file_get_contents($ROOT . '/hooks/Oc_security_headers.php') : '';
foreach (['X-Frame-Options', 'X-Content-Type-Options', 'Referrer-Policy', "frame-ancestors 'self'"] as $h) {
    check("header sent: $h", strpos($hk, $h) !== false);
}
check('a share page sends no Referer (the token is the credential)', strpos($hk, "'no-referrer'") !== false);

// R1: what New scan accepts — behaviour, not just shape.
if (!defined('BASEPATH')) { define('BASEPATH', __DIR__); }
if (!class_exists('CI_Model')) { eval('class CI_Model {}'); }
require_once $MODULE . '/models/Oc_scan_model.php';
$sm = new Oc_scan_model();
$cases = [
    ['https://booksy.com/en-us/100001_x_tampa', true],
    ['http://booksy.com/en-us/100001_x', false],
    ['https://127.0.0.1/admin', false],
    ['https://[::1]/x', false],
    ['https://localhost/x', false],
    ['https://user:pw@booksy.com/x', false],
    ['javascript:alert(1)', false],
    ['https://intranet/x', false],
    ['https://' . str_repeat('a', 600) . '.com', false],
];
foreach ($cases as $c) {
    $ok = $sm->url_problem($c[0]) === null;
    check('New scan ' . ($c[1] ? 'accepts ' : 'refuses ') . substr($c[0], 0, 40), $ok === $c[1]);
}
$city = $sm->parse_city('tampa,  fl');
check('city "tampa, fl" → us-fl-tampa', $city && $city['city_key'] === 'us-fl-tampa');
check('a city without a state is refused, not guessed', $sm->parse_city('Tampa') === null);

// -- CSRF: the shell has no JS prefilter, so forms carry the token. ---------
echo "
Forms
";

$formless = [];
foreach (php_files($VIEWS) as $f) {
    $src = file_get_contents($f);
    if (stripos($src, 'method="post"') === false) { continue; }
    if (strpos($src, 'oc_csrf()') === false) { $formless[] = basename($f); }
}
check('every POST form emits a CSRF token server-side', empty($formless), implode(', ', $formless));


// -- UI: no page stylesheet may restyle the shell. ---------------------------
echo "
Shell isolation
";

// The mechanism behind "this page looks fine but it broke the sidebar": a view
// brings its own <style>, writes a BARE .app or .rail rule, and every other page
// inherits it. The settings page was doing exactly this with .app, .sidebar and
// .nav-backdrop inside a media query, where a line-start check does not see them.
$SHELL_CLASSES = [
    'app','rail','main','topbar','brand','brand-mark','brand-name','brand-sub',
    'navbtn','rail-group','rail-foot','quota','page','search','oc-foot',
    'oc-skip-link','sidebar','nav-backdrop','row','rowwrap','list','panel',
    'score','bucket','pin','mapwrap','pagehead',
];

$leaks = [];
$view_dirs = glob($ROOT . '/modules/*/views', GLOB_ONLYDIR);
$view_files = [];
foreach ($view_dirs as $d) { $view_files = array_merge($view_files, php_files($d)); }

foreach ($view_files as $f) {
    $src = file_get_contents($f);

    // Only views rendered INSIDE the shell can leak into it. A standalone
    // document (login, the one-pager) brings its own <html> and never loads
    // oc-shell.css, so its .brand is its own.
    if (preg_match('/<!doctype html|<html/i', $src)) { continue; }
    if (!preg_match_all('/<style[^>]*>(.*?)<\/style>/is', $src, $blocks)) { continue; }

    foreach ($blocks[1] as $css) {
        if (!preg_match_all('/(?:^|[
{}])\s*([.#][^{}
]{0,120}?)\s*\{/', $css, $sels)) { continue; }
        foreach ($sels[1] as $sel) {
            $sel = trim($sel);
            // Anchored on an ID -> cannot reach the shell. Only a bare class is global.
            if ($sel === '' || $sel[0] === '#' || $sel[0] === '@') { continue; }
            if (!preg_match_all('/\.([A-Za-z][\w-]*)/', $sel, $cls)) { continue; }
            foreach ($cls[1] as $c) {
                if (in_array($c, $SHELL_CLASSES, true)) {
                    $leaks[] = basename($f) . ' -> .' . $c;
                    break 2;
                }
            }
        }
    }
}
check('no page stylesheet restyles a shell selector', empty($leaks), implode(', ', array_unique($leaks)));

// The settings page carries its own palette. It must ALIAS the shell tokens
// rather than restate hex values, or it renders as a light island in a dark app.
$scoped = $ROOT . '/modules/setting/views/pages/_ic_settings_scoped.php';
if (file_exists($scoped)) {
    $ssrc = file_get_contents($scoped);
    check('the settings palette aliases the shell, it does not hard-code colours',
          strpos($ssrc, '--txt:var(--tx)') !== false && strpos($ssrc, '--bg:var(--ground)') !== false);
    // A self-reference (--surface: var(--surface)) is invalid CSS and silently
    // resolves to nothing, which is worse than a wrong colour.
    check('no self-referential custom property',
          !preg_match('/--([a-z0-9-]+)\s*:\s*var\(\s*--\s*\)/', $ssrc));
}

echo "\n" . str_repeat('─', 52) . "\n";
echo ($fail === 0 ? "ALL GREEN" : "FAILURES") . ": $pass passed, $fail failed\n";
exit($fail === 0 ? 0 : 1);
