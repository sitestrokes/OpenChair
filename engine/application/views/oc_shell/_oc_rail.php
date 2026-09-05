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

/** Rail item: [key, label, url, tier-tag|null] */
$groups = [];
$groups['Prospect'] = [
    ['today',     'Today',     site_url('openchair'),           null],
    ['radar',     'Radar',     site_url('openchair/radar'),     null],
    ['business',  'Meter',     site_url('openchair/business'),  null],
    ['pitch',     'Pitch',     site_url('openchair/pitch'),     null],
    ['prospects', 'Prospects', site_url('openchair/prospects'), null],
];
if ($can_ledger) {
    $groups['Retain'] = [
        ['ledger', 'Ledger', site_url('openchair/ledger'), 'OTO2'],
    ];
}
$groups['Operate'] = [
    ['coverage', 'Coverage', site_url('openchair/coverage'), null],
];
if ($is_admin) {
    $groups['Operate'][] = ['adapters', 'Adapters', site_url('openchair/adapters'), null];
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
        'adapters'  => '<rect x="2" y="2.5" width="5" height="5" rx="1.4" stroke="currentColor" stroke-width="1.4"/><rect x="9" y="8.5" width="5" height="5" rx="1.4" stroke="currentColor" stroke-width="1.4"/><path d="M7 5h3.5a1.5 1.5 0 011.5 1.5v2" stroke="currentColor" stroke-width="1.4"/>',
    ];
    return isset($ic[$key]) ? $ic[$key] : '<circle cx="8" cy="8" r="5" stroke="currentColor" stroke-width="1.4"/>';
}
?>
<nav class="rail" aria-label="Main">
  <a class="brand" href="<?= site_url('openchair') ?>">
    <?php if ($brand_logo !== ''): ?>
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

  <?php foreach ($groups as $label => $items): ?>
    <div class="rail-group"><?= html_escape($label) ?></div>
    <?php foreach ($items as $it): ?>
      <a class="navbtn" href="<?= $it[2] ?>"<?= $page === $it[0] ? ' aria-current="page"' : '' ?>>
        <svg class="ico" viewBox="0 0 16 16" fill="none" aria-hidden="true"><?= oc_rail_icon($it[0]) ?></svg>
        <?= html_escape($it[1]) ?>
        <?php if ($it[3] !== null): ?><span class="tag"><?= html_escape($it[3]) ?></span><?php endif; ?>
      </a>
    <?php endforeach; ?>
  <?php endforeach; ?>

  <div class="rail-group">Account</div>
  <a class="navbtn" href="<?= site_url('settings') ?>"<?= $page === 'settings' ? ' aria-current="page"' : '' ?>>
    <svg class="ico" viewBox="0 0 16 16" fill="none" aria-hidden="true"><circle cx="8" cy="8" r="2.1" stroke="currentColor" stroke-width="1.4"/><path d="M8 1.8v1.6M8 12.6v1.6M14.2 8h-1.6M3.4 8H1.8M12.4 3.6l-1.1 1.1M4.7 11.3l-1.1 1.1M12.4 12.4l-1.1-1.1M4.7 4.7 3.6 3.6" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/></svg>
    Settings
  </a>
  <?php if ($is_admin): ?>
  <a class="navbtn" href="<?= site_url('users') ?>"<?= $page === 'users' ? ' aria-current="page"' : '' ?>>
    <svg class="ico" viewBox="0 0 16 16" fill="none" aria-hidden="true"><circle cx="6" cy="5.5" r="2.4" stroke="currentColor" stroke-width="1.4"/><path d="M1.8 13.4c0-2.3 1.9-3.8 4.2-3.8s4.2 1.5 4.2 3.8" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/><path d="M11 4.2a2.2 2.2 0 010 4.3M12.3 13.4c0-1.6-.5-2.6-1.3-3.3" stroke="currentColor" stroke-width="1.3" stroke-linecap="round"/></svg>
    Manage Users
  </a>
  <a class="navbtn" href="<?= site_url('whitelabel') ?>"<?= $page === 'whitelabel' ? ' aria-current="page"' : '' ?>>
    <svg class="ico" viewBox="0 0 16 16" fill="none" aria-hidden="true"><path d="M8 1.8l1.9 3.9 4.3.6-3.1 3 .7 4.3L8 11.6l-3.8 2 .7-4.3-3.1-3 4.3-.6z" stroke="currentColor" stroke-width="1.3" stroke-linejoin="round"/></svg>
    White-label
  </a>
  <?php endif; ?>
  <a class="navbtn" href="<?= site_url('auth/logout') ?>">
    <svg class="ico" viewBox="0 0 16 16" fill="none" aria-hidden="true"><path d="M6.2 2.5H3.4A1.4 1.4 0 002 3.9v8.2a1.4 1.4 0 001.4 1.4h2.8" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/><path d="M10.4 11 13.5 8l-3.1-3M13.5 8H6.3" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/></svg>
    Log out
  </a>

  <div class="rail-foot">
    <?php if (!empty($oc_quota) && is_array($oc_quota)): ?>
      <div class="quota">
        <?php foreach ($oc_quota as $q):
          $used = (int) $q['used']; $cap = max(1, (int) $q['cap']);
          $pct  = min(100, (int) round(100 * $used / $cap));
          // Amber at the cap, so "1 / 1 cities" reads as a ceiling, not progress.
          $col  = $pct >= 100 ? 'var(--mid)' : 'var(--accent)';
        ?>
          <div class="quota-row"><span><?= html_escape($q['label']) ?></span><span class="mono"><?= $used ?> / <?= (int) $q['cap'] ?></span></div>
          <div class="quota-bar"><div class="quota-fill" style="width:<?= $pct ?>%;background:<?= $col ?>"></div></div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
    <button type="button" class="navbtn" onclick="ocToggleTheme()" style="margin-top:8px">
      <svg class="ico" viewBox="0 0 16 16" fill="none" aria-hidden="true"><path d="M13.2 9.4A5.6 5.6 0 016.6 2.8a5.6 5.6 0 106.6 6.6z" stroke="currentColor" stroke-width="1.4" stroke-linejoin="round"/></svg>
      Theme
    </button>
  </div>
</nav>
