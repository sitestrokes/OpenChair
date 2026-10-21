<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * The 220px left rail, from ui/prototype.html.
 *
 * Groups follow the prototype: Prospect / Retain / Operate. Two departures, both
 * deliberate:
 *   1. Prospects is added under Prospect — it is a v1 FE screen in 40-ux-spec.md
 *      §2.2 that the prototype's rail omits.
 *   2. Account (Settings / Users / White-label / Log out) is added at the foot,
 *      because the prototype is a standalone demo with nowhere to go and this is
 *      a real app with the common modules in it.
 *
 * Gating follows the host engine's anti-mugging rule: an item the user has not
 * bought is NOT rendered as bait. The $oc_can_* flags come from Oc_render_lib.
 */
$page = isset($page) ? (string) $page : '';

$can_ledger = !isset($oc_can_ledger) || $oc_can_ledger;
$is_admin   = !empty($is_oc_admin);

$brand_name = 'OpenChair';
$brand_logo = '';
if (isset($ic_brand) && is_array($ic_brand)) {
    if (!empty($ic_brand['name'])) { $brand_name = (string) $ic_brand['name']; }
    if (!empty($ic_brand['logo'])) { $brand_logo = (string) $ic_brand['logo']; }
}

// Use the <img> ONLY for a genuine white-label override. The stock OpenChair
// logo is drawn inline instead, because an external SVG loaded through <img> is
// its own document: it cannot inherit `currentColor` from this page, so the
// wordmark would paint black and vanish against the dark rail. Inline, it tracks
// the theme. App_Controller pre-fills ic_brand['logo'] with the stock asset for
// every request, so identity is by comparison against that default.
$g          = $this->config->item('global_data');
$stock_logo = (!empty($g['logo_url'])) ? base_url($g['logo_url']) : '';
$is_custom  = ($brand_logo !== '' && $brand_logo !== $stock_logo);

/**
 * Rail item: [key, label, url, badge|null, badge-style|null]
 *
 * Grouping and labels follow the signature-visuals business screen (PDF p.8,
 * docs/60-signature-visuals.md §4.7): WORK is the consultant's day, AGENCY is
 * how the product and the account are set up. Keys are unchanged from the nav
 * contract, so every controller's $page still lights the right item.
 */
$due      = isset($oc_due_count) ? (int) $oc_due_count : 0;
$pipeline = isset($oc_pipeline_count) ? (int) $oc_pipeline_count : 0;
$is_super = !empty($is_oc_superadmin);
$is_demo  = !empty($oc_is_demo);

$groups = [];
$groups['Work'] = [
    ['today',     'Home',            site_url('openchair'),           $due ? $due . ' due' : null, 'due'],
    ['radar',     'Find businesses', site_url('openchair/radar'),     null, null],
    ['prospects', 'My pipeline',     site_url('openchair/prospects'), $pipeline ?: null, 'count'],
];
if ($can_ledger) {
    $groups['Work'][] = ['ledger', 'Client reports', site_url('openchair/ledger'), null, null];
}
$groups['Agency'] = [
    ['how',      'How it works',    site_url('openchair/how-it-works'), null, null],
    ['coverage', 'What we can see', site_url('openchair/coverage'),     null, null],
];
if ($is_admin) {
    $groups['Agency'][] = ['users',      'Team & roles',        site_url('users'),      null, null];
    $groups['Agency'][] = ['whitelabel', 'Brand & white-label', site_url('whitelabel'), null, null];
}
if ($is_super) {
    $groups['Agency'][] = ['config', 'API & webhooks', site_url('api-config'), null, null];
}
if ($is_admin) {
    $groups['Agency'][] = ['adapters', 'Adapters', site_url('openchair/adapters'), null, null];
}

/** Stroked 16x16 glyphs, lifted from the prototype's rail. */
function oc_rail_icon($key)
{
    static $ic = [
        'today'     => '<path d="M4 7a4 4 0 118 0c0 3 1 4 1 4H3s1-1 1-4z" stroke="currentColor" stroke-width="1.4" stroke-linejoin="round"/><path d="M6.5 13.2a1.7 1.7 0 003 0" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/>',
        'radar'     => '<circle cx="7" cy="7" r="5" stroke="currentColor" stroke-width="1.5"/><path d="M10.8 10.8 14 14" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>',
        'business'  => '<rect x="2" y="3" width="12" height="11" rx="2.5" stroke="currentColor" stroke-width="1.5"/><path d="M2 6.5h12M6 3v11" stroke="currentColor" stroke-width="1.2"/>',
        'pitch'     => '<path d="M3.5 2h6L13 5.5V14H3.5z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/><path d="M9 2v4h4" stroke="currentColor" stroke-width="1.2"/>',
        'prospects' => '<path d="M2.5 4.5h11M2.5 8h11M2.5 11.5h7" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>',
        'ledger'    => '<path d="M2 12l3.5-4 3 2.5L13 4" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>',
        'coverage'  => '<circle cx="8" cy="8" r="6" stroke="currentColor" stroke-width="1.4"/><path d="M8 4.5v3.8l2.4 1.4" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/>',
        'how'       => '<circle cx="8" cy="8" r="6" stroke="currentColor" stroke-width="1.4"/><path d="M6.3 6.2a1.8 1.8 0 113 1.4c-.6.4-1.3.8-1.3 1.6M8 11.4v.1" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/>',
        'users'     => '<circle cx="6" cy="5.5" r="2.4" stroke="currentColor" stroke-width="1.4"/><path d="M1.8 13.4c0-2.3 1.9-3.8 4.2-3.8s4.2 1.5 4.2 3.8" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/><path d="M11 4.2a2.2 2.2 0 010 4.3M12.3 13.4c0-1.6-.5-2.6-1.3-3.3" stroke="currentColor" stroke-width="1.3" stroke-linecap="round"/>',
        'whitelabel'=> '<path d="M8 1.8l1.9 3.9 4.3.6-3.1 3 .7 4.3L8 11.6l-3.8 2 .7-4.3-3.1-3 4.3-.6z" stroke="currentColor" stroke-width="1.3" stroke-linejoin="round"/>',
        'config'    => '<path d="M5.5 4.5 2 8l3.5 3.5M10.5 4.5 14 8l-3.5 3.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>',
        'adapters'  => '<rect x="2" y="2.5" width="5" height="5" rx="1.4" stroke="currentColor" stroke-width="1.4"/><rect x="9" y="8.5" width="5" height="5" rx="1.4" stroke="currentColor" stroke-width="1.4"/><path d="M7 5h3.5a1.5 1.5 0 011.5 1.5v2" stroke="currentColor" stroke-width="1.4"/>',
    ];
    return isset($ic[$key]) ? $ic[$key] : '<circle cx="8" cy="8" r="5" stroke="currentColor" stroke-width="1.4"/>';
}
?>
<nav class="rail" aria-label="Main">
  <a class="brand" href="<?= site_url('openchair') ?>">
    <?php if ($is_custom): ?>
      <img src="<?= html_escape($brand_logo) ?>" alt="<?= html_escape($brand_name) ?>" style="max-height:34px;max-width:150px">
    <?php else: ?>
      <span class="brand-mark" aria-hidden="true">
        <svg width="19" height="19" viewBox="0 0 20 20" fill="none">
          <rect x="1.5" y="1.5" width="17" height="17" rx="4" stroke="#fff" stroke-width="1.5" opacity=".9"/>
          <rect x="4.6" y="4.6" width="4.4" height="4.4" rx="1.2" fill="#fff"/>
          <rect x="11" y="11" width="4.4" height="4.4" rx="1.2" fill="#fff" opacity=".45"/>
          <rect x="4.6" y="11" width="4.4" height="4.4" rx="1.2" fill="#fff"/>
        </svg>
      </span>
      <div>
        <div class="brand-name"><?= html_escape($brand_name) ?></div>
        <div class="brand-sub">Capacity radar</div>
      </div>
    <?php endif; ?>
  </a>

  <a class="oc-newscan" href="<?= site_url('openchair/scan') ?>"<?= $page === 'scan' ? ' aria-current="page"' : '' ?>>+ New scan</a>
  <?php // The search box: "niche, city" sets the market, anything else searches names (Openchair::radar). ?>
  <form class="oc-railsearch" method="get" action="<?= site_url('openchair/radar') ?>" role="search">
    <svg viewBox="0 0 16 16" fill="none" aria-hidden="true"><circle cx="7" cy="7" r="5" stroke="currentColor" stroke-width="1.5"/><path d="M10.8 10.8 14 14" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
    <input type="search" name="q" required maxlength="120" autocomplete="off"
           data-city-ac="query" data-src="<?= site_url('openchair/cities') ?>"
           value="<?= html_escape(($page === 'radar' && isset($q) && $q !== '') ? $q : '') ?>"
           placeholder="<?= html_escape(isset($oc_query) && $oc_query !== '' ? $oc_query : 'barbershop, Tampa, FL') ?>"
           aria-label="Search: niche and city, or a shop name" title="Type a niche and a city (dentist, Miami, FL) or a shop name, then Enter">
  </form>
  <?php if ($is_demo): ?>
    <span class="oc-sample" title="Seeded sample data — not a live scan"><span class="dot" aria-hidden="true"></span> Sample data</span>
  <?php endif; ?>

  <?php foreach ($groups as $label => $items): ?>
    <div class="rail-group"><?= html_escape($label) ?></div>
    <?php foreach ($items as $it): ?>
      <a class="navbtn" href="<?= $it[2] ?>"<?= $page === $it[0] ? ' aria-current="page"' : '' ?>>
        <svg class="ico" viewBox="0 0 16 16" fill="none" aria-hidden="true"><?= oc_rail_icon($it[0]) ?></svg>
        <?= html_escape($it[1]) ?>
        <?php if ($it[3] !== null): ?><span class="oc-badge<?= $it[4] === 'due' ? ' is-due' : '' ?>"><?= html_escape((string) $it[3]) ?></span><?php endif; ?>
      </a>
    <?php endforeach; ?>
  <?php endforeach; ?>

  <div class="rail-group">Account</div>
  <a class="navbtn" href="<?= site_url('settings') ?>"<?= $page === 'settings' ? ' aria-current="page"' : '' ?>>
    <svg class="ico" viewBox="0 0 16 16" fill="none" aria-hidden="true"><circle cx="8" cy="8" r="2.1" stroke="currentColor" stroke-width="1.4"/><path d="M8 1.8v1.6M8 12.6v1.6M14.2 8h-1.6M3.4 8H1.8M12.4 3.6l-1.1 1.1M4.7 11.3l-1.1 1.1M12.4 12.4l-1.1-1.1M4.7 4.7 3.6 3.6" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/></svg>
    Settings
  </a>
  <a class="navbtn" href="<?= site_url('auth/logout') ?>">
    <svg class="ico" viewBox="0 0 16 16" fill="none" aria-hidden="true"><path d="M6.2 2.5H3.4A1.4 1.4 0 002 3.9v8.2a1.4 1.4 0 001.4 1.4h2.8" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/><path d="M10.4 11 13.5 8l-3.1-3M13.5 8H6.3" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/></svg>
    Log out
  </a>

  <div class="rail-foot">
    <?php if (!empty($oc_quota) && is_array($oc_quota)): ?>
      <div class="quota">
        <div class="oc-plan"><b>Agency plan</b><?php foreach ($oc_quota as $i => $q): ?><?= $i ? " &middot; " : "" ?><?= (int) $q['used'] ?> of <?= (int) $q['cap'] ?> <?= html_escape(strtolower($q['label'])) ?><?php endforeach; ?></div>
      </div>
    <?php endif; ?>
    <button type="button" class="navbtn" onclick="ocToggleTheme()" style="margin-top:8px">
      <svg class="ico" viewBox="0 0 16 16" fill="none" aria-hidden="true"><path d="M13.2 9.4A5.6 5.6 0 016.6 2.8a5.6 5.6 0 106.6 6.6z" stroke="currentColor" stroke-width="1.4" stroke-linejoin="round"/></svg>
      Theme
    </button>
  </div>
</nav>
