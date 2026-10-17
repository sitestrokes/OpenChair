<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * OpenChair — application config.
 *
 * Structural counterpart to the host engine's app_config.php, trimmed to what
 * OpenChair actually is. Everything voice/telephony/bridge is deliberately absent:
 * 05-prd.md §8 rule 7 requires that no code path exist from Pitch into
 * sequence_queue or telephony, and the cheapest way to guarantee that is for the
 * config those subsystems read not to exist.
 *
 * NO SECRETS IN THIS FILE. Per-user API keys live encrypted in `settings`
 * (meta_type), exactly as the host engine does it.
 */

// ── Brand defaults for the dark shell ────────────────────────────────────────
// App_Controller::_build_ic_brand() starts from these and lets a saved
// white-label row (settings.meta_type='whitelabel') override any field.
$config['global_data'] = [
    'logo_url'      => 'assets/img/oc-logo.svg',
    'fav_url'       => 'assets/img/oc-favicon.svg',
    'sidebar_logo'  => 'assets/img/oc-logo.svg',
    'footer_text'   => '© ' . date('Y') . ' OpenChair — walk in with their calendar.',
];

// ── openchair-service (Node, :4100) ──────────────────────────────────────────
// Same split the host engine uses for bridge-service:
//   public_base   → browser-facing links
//   internal_base → server-to-server curl from CI3, gated by X-Internal-Token
// The CI app has no .env, so these live here rather than in the environment.
$config['oc_service_public_base']   = 'http://localhost:4100';
$config['oc_service_internal_base'] = 'http://127.0.0.1:4100';

// Shared secret for the CI3 → openchair-service hop. EMPTY IN DEV = gate
// disabled, matching the host engine's BRIDGE_INTERNAL_TOKEN behaviour.
$config['oc_internal_token'] = '';

// ── Product integrity — read by the Meter display layer ──────────────────────
// These are not tunables in the ordinary sense. They are the numbers behind
// 05-prd.md §8, kept in one place so a change is visible in a diff.

// Below this confidence the product shows "insufficient data" and NO number
// (rule 3). 35-scoring-model.md §2.2.
$config['oc_confidence_floor'] = 45;

// Empty-Chair Revenue is always a RANGE, never a point estimate (rule 4).
// Sell-through band applied to open chair-minutes. 35-scoring-model.md §3.4.
$config['oc_sellthrough_low']  = 0.15;
$config['oc_sellthrough_high'] = 0.35;

// Data older than this is banner-labelled STALE (rule 6, FR-3.5).
$config['oc_stale_after_hours'] = 36;

// The unit, in words. Rule 2: never "empty shop". Anything user-facing that
// names the measure pulls this string rather than writing its own.
$config['oc_unit_label'] = 'online-bookable open slots';

// ── Chair Score v1.0 — the signature visuals (docs/60-signature-visuals.md) ──
// The one number on every card: out of every 100 chair-hours a shop puts online,
// how many got booked. Higher = busier. The method version is printed on every
// card so a before/after only ever compares like with like.
$config['oc_chair_method']        = 'v1.0';
$config['oc_chair_hungry_below']  = 50;   // Hungry  · under 50 — needs customers
$config['oc_chair_slammed_from']  = 85;   // Slammed · 85+      — needs capacity
// Lead time overrides the score (35-scoring-model.md §4.2): a shop a new client
// cannot get into for this many days is turning people away, whatever its score.
$config['oc_chair_wait_slammed_days'] = 10;
// "Not enough data yet" until this share of the week's hours has been seen.
$config['oc_chair_min_seen_pct']  = 70;
// Share links preview in iMessage/WhatsApp and expire after this many days.
$config['oc_share_ttl_days']      = 30;

// ── Tier gates (docs/70-radar-build.md R7) ───────────────────────────────────
// Which JVZoo access codes (users.access, comma-separated) unlock which gate.
// STRUCTURE.md §9 #3 is still open — new OpenChair codes or the InstantClose map —
// so enforcement is OFF and every gate is open until the founder sets the codes
// and flips this to TRUE. The gates are enforced in the controllers as well as
// hidden in the rail, so turning this on cannot leave a reachable URL ungated.
// The defaults mirror the host engine's map (Access_control_lib) so that turning
// it on with no edits behaves like the sister product.
$config['oc_access_enforce'] = FALSE;
$config['oc_access_codes'] = [
    'core'     => ['1', '2', '3', '4', '7', '10', '71', '9', '94', '91', '92', '93'], // may use OpenChair at all
    // README decision #2 recommends FE gets BOTH buckets; set to the OTO1 list to hold Slammed back.
    'slammed'  => ['1', '2', '3', '4', '7', '10', '71', '9', '94', '91', '92', '93'],
    'alerts'   => ['2', '9', '94', '91', '93'],   // OTO1: it runs nightly and alerts you
    'deadzone' => ['2', '9', '94', '91', '93'],   // OTO1
    'ledger'   => ['3', '9', '94', '91', '93'],   // OTO2: client reports, 8-week report card
    'kit'      => ['4', '9', '94', '91', '93'],   // OTO3
];
// Roles that see everything for review and support: superadmin (user 1) always,
// plus Admin, JV Admin, Beta and JV.
$config['oc_access_full'] = ['5', '51', '92', '93'];

// ── Anti-bot tier ceiling ────────────────────────────────────────────────────
// Tier 3 (stealth + residential rotation) is out of scope by decision, not by
// configuration — it is the largest legal exposure (DMCA §1201) and the largest
// COGS line. This value exists so the UI can state the ceiling honestly; the
// ceiling itself is enforced in the database (chk_scan_tier_ceiling, oc_007)
// and at the egress broker. Raising it here changes nothing.
$config['oc_max_tier'] = 2;

// ── Scan economics ───────────────────────────────────────────────────────────
// Budget, not a measurement. The circuit trips on observed cost_micros.
$config['oc_cost_budget_micros'] = 20000;   // $0.02/scan
$config['oc_scan_interval_hours'] = 24;     // one pass per business per day (FR-3.1)
