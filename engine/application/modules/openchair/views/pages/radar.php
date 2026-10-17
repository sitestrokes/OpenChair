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
// LAT1 < LAT0 (north is up), so the span is negative: guard its MAGNITUDE.
// max(1e-9, negative) used to return 1e-9 and fling every pin off the map.
$py = function ($lat) use ($LAT0, $LAT1, $H) {
    $span = $LAT1 - $LAT0;
    if (abs($span) < 1e-9) { $span = -1e-9; }
    return round(($lat - $LAT0) / $span * $H, 1);
};

// Pins speak the Chair Map's language (docs/60-signature-visuals.md, PDF p.3):
// red = empty chairs, green = busy, black = full and turning people away.
$pin_color = function ($b) {
    switch (oc_chair_band_for($b)) {
        case 'hungry':  return 'var(--hot)';
        case 'steady':  return 'var(--cool)';
        case 'slammed': return 'var(--tx)';
        default:        return 'var(--tx3)';
    }
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
      <h1>Find businesses &mdash; barbershop, Tampa</h1>
      <p><?= count($businesses) ?> businesses with public online booking. The number is each shop's
         <a href="<?= site_url('openchair/how-it-works') ?>">Chair Score</a>: out of every 100 chair-hours
         it put online, how many got booked.</p>
    </div>
    <div class="spacer"></div>
  </div>

  <div class="split">

    <div class="panel">
      <div class="panel-head">
        <h2>Map</h2>
        <span class="sub">red = empty chairs &middot; green = busy &middot; black = full</span>
      </div>
      <div class="mapwrap">
        <svg viewBox="0 0 <?= $W ?> <?= $H ?>" role="img" aria-label="Map of <?= count($businesses) ?> businesses, coloured by Chair Score band">
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
               aria-label="<?= html_escape($b->name . ' — Chair Score ' . oc_chair_score_or_gap($b->latest_vacancy_bp, $b->latest_confidence) . ', ' . oc_chair_band_label(oc_chair_band_for($b))) ?>">
              <circle cx="<?= $px((float) $b->lng) ?>" cy="<?= $py((float) $b->lat) ?>" r="<?= $r ?>"
                      fill="<?= $pin_color($b) ?>" fill-opacity=".92"
                      stroke="var(--surface)" stroke-width="1.4"/>
            </a>
          <?php endforeach; ?>
          </g>
        </svg>
        <div class="maplegend">
          <div class="legrow"><span style="width:9px;height:9px;border-radius:99px;background:var(--hot);display:inline-block"></span> Hungry &middot; under 50</div>
          <div class="legrow"><span style="width:9px;height:9px;border-radius:99px;background:var(--cool);display:inline-block"></span> Steady &middot; 50&ndash;84</div>
          <div class="legrow"><span style="width:9px;height:9px;border-radius:99px;background:var(--tx);display:inline-block"></span> Slammed &middot; 85+</div>
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
        <?php foreach ([['vacancy','Lowest Chair Score'],['revenue','Most unsold'],['next','Longest wait'],['name','Name']] as $s): ?>
          <a class="chip" href="<?= $qs(['sort' => $s[0]]) ?>" aria-pressed="<?= $f_sort === $s[0] ? 'true' : 'false' ?>"><?= html_escape($s[1]) ?></a>
        <?php endforeach; ?>
      </div>

      <div class="list">
        <?php if (empty($businesses)): ?>
          <div class="empty" style="padding:40px 20px;text-align:center;color:var(--tx3)">Nothing matches that filter.</div>
        <?php endif; ?>
        <?php foreach ($businesses as $b):
          $band  = oc_chair_band_for($b);
          $cls   = oc_chair_band_class($band);
          $score = oc_chair_score_or_gap($b->latest_vacancy_bp, $b->latest_confidence);
          $low   = oc_below_floor($b->latest_confidence);
        ?>
          <div class="rowwrap">
          <a class="row" href="<?= site_url('openchair/business/' . (int) $b->id) ?>">
            <span class="score <?= $cls ?>" title="Chair Score"><?= html_escape($score) ?></span>
            <span>
              <span class="row-name">
                <?= html_escape($b->name) ?>
                <span class="bucket <?= $cls ?>"><?= oc_chair_glyph($band) ?> <?= html_escape(oc_chair_band_label($band)) ?></span>
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
                <?= html_escape(oc_range_short($b->latest_empty_chair_cents, $b->latest_empty_chair_hi_cents)) ?><small>unsold this week</small>
              <?php endif; ?>
            </span>
          </a>
          <?php $is_watched = !empty($watched_map[(int) $b->id]); ?>
          <form method="post" class="rowact"
                action="<?= site_url('openchair/' . ($is_watched ? 'unwatch' : 'watch')) ?>">
            <?= oc_csrf() ?><input type="hidden" name="business_id" value="<?= (int) $b->id ?>">
            <input type="hidden" name="back" value="<?= html_escape(uri_string() . ($_SERVER['QUERY_STRING'] ? '?' . $_SERVER['QUERY_STRING'] : '')) ?>">
            <button class="btn btn-sm<?= $is_watched ? '' : ' btn-primary' ?>" type="submit"
                    title="<?= $is_watched ? 'Stop watching' : 'Watch this business' ?>">
              <?= $is_watched ? '&#10003; Watching' : '+ Watch' ?>
            </button>
          </form>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

  </div>
</div>
