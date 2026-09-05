<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * S4 — Radar. The hero screen (40-ux-spec.md §5).
 *
 * Map beside a ranked list. Pin encoding is position + radius + colour, and the
 * list always prints the literal number next to the visual, so nothing here means
 * anything by colour alone (NFR-8).
 *
 * Vars: $businesses[], $counts, $totals, $f_bucket, $f_sort
 */
// Project lat/lng into the SVG viewBox. Tampa's bounds, padded.
$LAT0 = 28.0126; $LAT1 = 27.8886; $LNG0 = -82.5292; $LNG1 = -82.3852;
$W = 620; $H = 420;
$px = function ($lng) use ($LNG0, $LNG1, $W) { return round(($lng - $LNG0) / max(1e-9, $LNG1 - $LNG0) * $W, 1); };
$py = function ($lat) use ($LAT0, $LAT1, $H) { return round(($lat - $LAT0) / max(1e-9, $LAT1 - $LAT0) * $H, 1); };

// The 7-stop vacancy scale from 40-ux-spec.md §10.4.
$stops = ['#0E8570','#22A98A','#5FC9A8','#E8C766','#FFA94D','#FF8352','#FF5C43'];
$pin_color = function ($b) use ($stops) {
    if (oc_below_floor($b->latest_confidence)) { return 'var(--tx3)'; }
    $i = (int) floor(((int) $b->latest_vacancy_bp) / 10000 * (count($stops) - 1));
    return $stops[max(0, min(count($stops) - 1, $i))];
};
$qs = function ($over) use ($f_bucket, $f_sort) {
    $p = array_merge(['bucket' => $f_bucket, 'sort' => $f_sort], $over);
    $p = array_filter($p, function ($v) { return $v !== null && $v !== ''; });
    return site_url('openchair/radar') . ($p ? '?' . http_build_query($p) : '');
};
?>
<div class="page">

  <div class="pagehead">
    <div>
      <h1>Radar &mdash; barbershop, Tampa</h1>
      <p><?= count($businesses) ?> businesses with public online booking, ranked by how much unsold
         time is on their calendar this week.</p>
    </div>
    <div class="spacer"></div>
  </div>

  <div class="split">

    <div class="panel">
      <div class="panel-head">
        <h2>Map</h2>
        <span class="sub">reddest = most open time</span>
      </div>
      <div class="mapwrap">
        <svg viewBox="0 0 <?= $W ?> <?= $H ?>" role="img" aria-label="Map of <?= count($businesses) ?> businesses, coloured by vacancy">
          <rect width="<?= $W ?>" height="<?= $H ?>" fill="var(--map-land)"/>
          <!-- Schematic street grid + the river. Geography is indicative: the pin
               positions are real lat/lng, the basemap is not a survey. -->
          <g stroke="var(--map-street)" stroke-width="7" opacity=".55">
            <path d="M0 120 H<?= $W ?>"/><path d="M0 232 H<?= $W ?>"/><path d="M0 330 H<?= $W ?>"/>
            <path d="M148 0 V<?= $H ?>"/><path d="M330 0 V<?= $H ?>"/><path d="M478 0 V<?= $H ?>"/>
          </g>
          <path d="M255 <?= $H ?> C 262 300, 232 250, 250 190 C 268 130, 240 60, 252 0"
                stroke="var(--map-water)" stroke-width="26" fill="none" opacity=".9"/>
          <g>
          <?php foreach ($businesses as $b):
            if ($b->lat === null || $b->lng === null) { continue; }
            $r = oc_below_floor($b->latest_confidence) ? 5
               : 5 + round(((int) $b->latest_vacancy_bp) / 10000 * 6, 1);
          ?>
            <a class="pin" href="<?= site_url('openchair/business/' . (int) $b->id) ?>"
               aria-label="<?= html_escape($b->name . ' — ' . oc_bucket_label($b->latest_bucket)) ?>">
              <circle cx="<?= $px((float) $b->lng) ?>" cy="<?= $py((float) $b->lat) ?>" r="<?= $r ?>"
                      fill="<?= $pin_color($b) ?>" fill-opacity=".92"
                      stroke="var(--surface)" stroke-width="1.4"/>
            </a>
          <?php endforeach; ?>
          </g>
        </svg>
        <div class="maplegend">
          <div class="legrow"><span>Booked</span><span class="legscale"></span><span>Open</span></div>
          <div class="legrow"><span style="width:9px;height:9px;border-radius:99px;background:var(--tx3);display:inline-block"></span> below confidence floor &mdash; no score</div>
        </div>
        <div class="mapnote">pin size = open time</div>
      </div>
    </div>

    <div class="panel">
      <div class="filters">
        <a class="chip" href="<?= $qs(['bucket' => null]) ?>" aria-pressed="<?= $f_bucket ? 'false' : 'true' ?>">All <span class="cnt"><?= array_sum($counts) ?></span></a>
        <a class="chip hot"  href="<?= $qs(['bucket' => 'hungry'])  ?>" aria-pressed="<?= $f_bucket === 'hungry'  ? 'true' : 'false' ?>">&#9650; Hungry <span class="cnt"><?= (int) $counts['hungry'] ?></span></a>
        <a class="chip mid"  href="<?= $qs(['bucket' => 'neutral']) ?>" aria-pressed="<?= $f_bucket === 'neutral' ? 'true' : 'false' ?>">&#9632; Steady <span class="cnt"><?= (int) $counts['neutral'] ?></span></a>
        <a class="chip cool" href="<?= $qs(['bucket' => 'slammed']) ?>" aria-pressed="<?= $f_bucket === 'slammed' ? 'true' : 'false' ?>">&#9679; Slammed <span class="cnt"><?= (int) $counts['slammed'] ?></span></a>
      </div>
      <div class="filters" style="border-bottom:1px solid var(--line-soft)">
        <span class="sub" style="align-self:center;margin-right:4px">Sort</span>
        <?php foreach ([['vacancy','Most open'],['revenue','Most money'],['next','Longest wait'],['name','Name']] as $s): ?>
          <a class="chip" href="<?= $qs(['sort' => $s[0]]) ?>" aria-pressed="<?= $f_sort === $s[0] ? 'true' : 'false' ?>"><?= html_escape($s[1]) ?></a>
        <?php endforeach; ?>
      </div>

      <div class="list">
        <?php if (empty($businesses)): ?>
          <div class="empty" style="padding:40px 20px;text-align:center;color:var(--tx3)">Nothing matches that filter.</div>
        <?php endif; ?>
        <?php foreach ($businesses as $b):
          $cls   = oc_bucket_class($b->latest_bucket, $b->latest_confidence);
          $score = oc_score_or_gap($b->latest_vacancy_bp, $b->latest_confidence);
          $low   = oc_below_floor($b->latest_confidence);
        ?>
          <a class="row" href="<?= site_url('openchair/business/' . (int) $b->id) ?>">
            <span class="score <?= $cls ?>"><?= html_escape($score) ?></span>
            <span>
              <span class="row-name">
                <?= html_escape($b->name) ?>
                <span class="bucket <?= $cls ?>"><?= oc_bucket_glyph($b->latest_bucket) ?> <?= html_escape(oc_bucket_label($low ? null : $b->latest_bucket)) ?></span>
              </span>
              <span class="row-meta">
                <span class="plat"><?= html_escape($b->platform_name ?: '—') ?></span>
                <?= html_escape(oc_slots_phrase($b->latest_open_slots)) ?> of <?= number_format((int) $b->latest_bookable_slots) ?>
                <span class="conf"><?= oc_confidence_bars($b->latest_confidence) ?> <?= html_escape(oc_confidence_band($b->latest_confidence)) ?></span>
              </span>
            </span>
            <span class="row-money">
              <?php if ($low): ?>
                &mdash;<small>not enough data</small>
              <?php else: ?>
                <?= html_escape(oc_money($b->latest_empty_chair_cents)) ?><small>unsold, low end</small>
              <?php endif; ?>
            </span>
          </a>
        <?php endforeach; ?>
      </div>
    </div>

  </div>
</div>
