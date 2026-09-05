<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * S5 — Business Detail, the rail's "Meter" (40-ux-spec.md §6).
 *
 * Three things this screen must do, in order:
 *   1. Put the money figure first, as a range (rules 1 and 4).
 *   2. Show the availability grid REDRAWN from observed data. Never a screen
 *      capture of the platform's page — that copies their layout, imagery and
 *      staff photos (05-prd.md §4.2).
 *   3. Carry the "what we could not see" block as a first-class element, not
 *      fine print (rule 5, FR-4.5). If a consultant repeats a number that turns
 *      out to be a staff holiday, they lose the room and blame us.
 *
 * Vars: $b
 */
$low   = oc_below_floor($b->latest_confidence);
$score = oc_score_or_gap($b->latest_vacancy_bp, $b->latest_confidence);
$cls   = oc_bucket_class($b->latest_bucket, $b->latest_confidence);
$stale = oc_is_stale($b->last_good_scan_at);

// The grid is DERIVED, not observed — Phase 4 replaces this with the real
// snapshot. It is deterministic per business so the screen does not reshuffle
// on refresh, and it is labelled below as illustrative.
$seed = (int) $b->id * 7919;
$rnd = function () use (&$seed) { $seed = ($seed * 1103515245 + 12345) % 2147483648; return $seed / 2147483648; };
$hours = range(9, 18);
$days  = ['Mon','Tue','Wed','Thu','Fri','Sat','Sun'];
$vac   = ((int) $b->latest_vacancy_bp) / 10000;
?>
<div class="page">

  <div class="pagehead">
    <div>
      <h1><?= html_escape($b->name) ?></h1>
      <p><?= html_escape(trim($b->address_line . ', ' . $b->city)) ?>
         &middot; <?= html_escape($b->platform_name ?: 'unknown platform') ?>
         &middot; <?= (int) $b->staff_count ?> chairs
         &middot; scanned <?= html_escape(oc_data_age($b->last_good_scan_at)) ?></p>
    </div>
    <div class="spacer"></div>
    <a class="btn btn-primary" href="<?= site_url('openchair/pitch/' . (int) $b->id) ?>">Make the pitch</a>
  </div>

  <?php if ($stale): ?>
    <div class="stale"><span aria-hidden="true">&#9888;</span>
      <div><b>Last good read was <?= html_escape(oc_data_age($b->last_good_scan_at)) ?>.</b>
        Showing the last observation we trust rather than a fresh guess.</div></div>
  <?php endif; ?>

  <div class="detail-grid">

    <div class="stack">
      <div class="panel">
        <div class="dial">
          <svg width="96" height="96" viewBox="0 0 96 96" role="img" aria-label="Vacancy score <?= html_escape($score) ?> out of 100">
            <circle cx="48" cy="48" r="39" fill="none" stroke="var(--sunken)" stroke-width="10"/>
            <?php if (!$low): ?>
            <circle cx="48" cy="48" r="39" fill="none" stroke="var(--<?= $cls === 'hot' ? 'hot' : ($cls === 'cool' ? 'cool' : 'mid') ?>)" stroke-width="10"
                    stroke-linecap="round" transform="rotate(-90 48 48)"
                    stroke-dasharray="<?= round(245 * $vac, 1) ?> 245"/>
            <?php endif; ?>
            <text x="48" y="54" text-anchor="middle" fill="var(--tx)" font-family="Outfit" font-weight="800" font-size="27"><?= html_escape($score) ?></text>
          </svg>
          <div>
            <div class="stat-label">Vacancy score</div>
            <div style="margin-top:7px"><span class="bucket <?= $cls ?>"><?= oc_bucket_glyph($b->latest_bucket) ?> <?= html_escape(oc_bucket_label($low ? null : $b->latest_bucket)) ?></span></div>
            <div class="conf" style="margin-top:9px"><?= oc_confidence_bars($b->latest_confidence) ?> <?= html_escape(oc_confidence_band($b->latest_confidence)) ?> confidence</div>
          </div>
        </div>

        <div class="stat" style="border-top:1px solid var(--line-soft)">
          <div class="stat-label">Empty-chair revenue, this week</div>
          <?php if ($low): ?>
            <div class="money" style="color:var(--tx3)">Not enough data</div>
            <div class="stat-note">We could not read enough of this calendar to stand behind a figure.
              Showing you the gap is more useful than showing you a guess you would have to defend
              in their shop.</div>
          <?php else: ?>
            <div class="money"><?= html_escape(oc_revenue_range($b->latest_empty_chair_cents, $b->latest_empty_chair_hi_cents)) ?></div>
            <div class="rangebar"><span style="left:12%;right:26%"></span></div>
            <div class="stat-note">Range, not a point estimate. Priced from <b>their own published menu</b>
              (average <?= html_escape(oc_money($b->avg_price_cents)) ?> per booking), assuming 15&ndash;35% of open
              time could realistically be sold.</div>
          <?php endif; ?>
        </div>

        <dl style="margin:0">
          <div class="kv"><dt>Open slots</dt><dd class="mono"><?= number_format((int) $b->latest_open_slots) ?> of <?= number_format((int) $b->latest_bookable_slots) ?></dd></div>
          <div class="kv"><dt>Next available</dt><dd><?= html_escape(oc_next_available($b->latest_days_to_next)) ?></dd></div>
          <div class="kv"><dt>Chairs seen</dt><dd class="mono"><?= (int) $b->staff_count ?></dd></div>
          <div class="kv"><dt>Platform</dt><dd><?= html_escape($b->platform_name ?: '—') ?></dd></div>
          <div class="kv"><dt>Observed</dt><dd><?= html_escape($b->latest_observed_on ?: '—') ?></dd></div>
        </dl>
      </div>

      <!-- Rule 5. First-class, always visible, never behind a tooltip. -->
      <div class="disclose">
        <h3><span aria-hidden="true">&#9673;</span> What we could not see</h3>
        <ul>
          <li>Slots blocked by the owner for admin, training or personal time.</li>
          <li>Staff days off that were never entered into the booking system.</li>
          <li>Walk-ins &mdash; which for a barbershop can be a large share of real trade.</li>
          <li>Bookings taken by phone, text or in person and not recorded online.</li>
          <li>Any service the shop does not publish on this platform.</li>
        </ul>
        <p>This is why the unit is <b><?= html_escape(oc_unit()) ?></b> and never &ldquo;empty shop&rdquo;.
           The figure is directionally honest and the owner will recognise it &mdash; but say it as what
           it is, or the first objection ends the conversation.</p>
      </div>
    </div>

    <div class="stack">
      <div class="panel">
        <div class="panel-head">
          <h2>Availability, next 7 days</h2>
          <span class="sub">redrawn from observed data &mdash; not a screenshot of their page</span>
        </div>
        <div class="gridscroll">
          <table class="slots" aria-label="Availability by time and day">
            <thead>
              <tr><th class="t"></th><?php foreach ($days as $d): ?><th><?= $d ?></th><?php endforeach; ?></tr>
            </thead>
            <tbody>
            <?php foreach ($hours as $h):
              $label = date('g a', mktime($h, 0, 0)); ?>
              <tr>
                <th class="t"><?= $label ?></th>
                <?php foreach ($days as $di => $d):
                  $closed = ($d === 'Sun') || ($h >= 17 && $d === 'Sat');
                  $open   = !$closed && $rnd() < $vac;
                  // A chronic afternoon gap is where the offer goes (the Dead-Zone
                  // Map in Phase 7 makes this real rather than illustrative).
                  $dead   = $open && $d === 'Tue' && $h >= 13 && $h <= 15;
                  $c = $closed ? 'closed' : ($dead ? 'dead' : ($open ? 'open' : ''));
                ?>
                  <td class="<?= $c ?>"></td>
                <?php endforeach; ?>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <div class="gridkey">
          <span><i style="background:var(--grid-open);box-shadow:inset 0 0 0 1px var(--grid-open-line)"></i> open</span>
          <span><i style="background:var(--grid-book)"></i> booked</span>
          <span><i style="background:var(--grid-open);box-shadow:inset 0 0 0 1.5px var(--hot)"></i> chronic gap</span>
          <span><i style="box-shadow:inset 0 0 0 1px var(--line-soft)"></i> closed</span>
        </div>
        <div class="ctrl">
          <div class="stat-note" style="margin:0">This grid is <b>illustrative</b> until Radar runs.
            Phase 4 replaces it with the observed snapshot; the rendering does not change, only the
            source. The product never captures the booking platform's own page.</div>
        </div>
      </div>
    </div>

  </div>
</div>
