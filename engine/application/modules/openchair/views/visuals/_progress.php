<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * 8-week report card — "Chair Score 41 → 63" (PDF p.7).
 *
 * The retainer artifact: the same number, the same method, eight weeks apart.
 * It reports what changed and is explicit that it does not say why — the
 * Ledger's attribution rules (35-scoring-model.md §6) still own any claim of
 * lift, and this card makes none.
 *
 * Rules this card carries:
 *   - Weeks with too few scan days are GAPS in the line, never averaged in.
 *   - The method version is printed; before/after only compares like with like.
 *   - Money is a range at both ends of the comparison (rule 4).
 *   - A worse week reads as a worse week: "fewer", "up from", never spun.
 *
 * Vars: $card (with $card['progress']), $size 'print'|'phone'
 */
$pg   = $card['progress'];
$biz  = $card['business'];

$band_cls = function ($b) {
    switch ($b) {
        case 'hungry':  return 'occ-red';
        case 'steady':  return 'occ-green';
        default:        return '';
    }
};
$band_hex = function ($b) {
    switch ($b) {
        case 'hungry':  return '#C8391F';
        case 'steady':  return '#0E6B55';
        case 'slammed': return '#1E1915';
        default:        return '#8C7F76';
    }
};

// A mini week from the library → the shape the shared grid partial draws.
$mini_grid = function ($mini) {
    $days = [];
    foreach ($mini['days'] as $d) {
        $e = 0; $bk = 0;
        foreach ($d['rows'] as $row) {
            $e  += substr_count($row, 'e');
            $bk += substr_count($row, 'e') + substr_count($row, 'b');
        }
        $days[] = [
            'label' => $d['label'], 'name' => ucfirst(strtolower($d['label'])), 'shut' => $d['shut'],
            'rows' => $d['rows'], 'empty' => $e, 'bookable' => $bk, 'full' => !$d['shut'] && $bk > 0 && $e === 0,
        ];
    }
    $hours = range(9, 9 + count($mini['days'][0]['rows']) - 1);
    return [
        'hours'       => $hours,
        'hour_labels' => array_map(function ($h) { return ($h % 12 === 0 ? 12 : $h % 12) . ($h < 12 ? 'a' : 'p'); }, $hours),
        'chairs'      => (int) $mini['chairs'],
        'days'        => $days,
    ];
};
?>
<article class="occ occ--progress">
  <div class="occ-body">
  <?php if (empty($pg) || empty($pg['ready'])): ?>
    <p class="occ-kicker"><?= html_escape($biz['name']) ?> &middot; 8-week report</p>
    <h1 class="occ-title">Not enough weeks yet.</h1>
    <p class="occ-sub ocp-wait">This report compares a shop&rsquo;s Chair Score week by week, using the same method for every
      week. It needs <?= (int) (isset($pg['weeks_needed']) ? $pg['weeks_needed'] : 2) ?> more full
      week<?= (isset($pg['weeks_needed']) && (int) $pg['weeks_needed'] === 1) ? '' : 's' ?> of checks
      (5+ days each) before there is a before and an after to show.</p>
  <?php else:
    $first = $pg['first']; $last = $pg['last'];
    $changed = $first['band'] !== $last['band'];
    $more = (int) $pg['more_booked'];
    $ub = $pg['unsold_before']; $ua = $pg['unsold_after'];

    // ── Chart geometry ───────────────────────────────────────────────────
    $W = 640; $H = 230; $L = 46; $Rt = 620; $T = 16; $B = 192;
    $pts = $pg['points'];
    $n = count($pts);
    $xs = function ($i) use ($n, $L, $Rt) { return $n > 1 ? $L + 30 + ($Rt - $L - 60) * $i / ($n - 1) : ($L + $Rt) / 2; };
    // y spans 10..90 (the PDF's 20–80 gridlines with headroom); extremes clamp.
    $ys = function ($v) use ($T, $B) { $v = max(5, min(95, (float) $v)); return $B - ($B - $T) * ($v - 10) / 80; };

    // Line segments, broken at gaps.
    $segs = []; $cur = [];
    foreach ($pts as $i => $p) {
        if ($p['score'] === null) { if ($cur) { $segs[] = $cur; } $cur = []; continue; }
        $cur[] = round($xs($i), 1) . ',' . round($ys($p['score']), 1);
    }
    if ($cur) { $segs[] = $cur; }
    $fi = null; $li = null;
    foreach ($pts as $i => $p) { if ($p['score'] !== null) { if ($fi === null) { $fi = $i; } $li = $i; } }

    // Campaign marker: interpolate the date between the week starts.
    $camp_x = null;
    if (!empty($pg['campaign'])) {
        $ct = strtotime($pg['campaign']['date']);
        for ($i = 0; $i < $n - 1; $i++) {
            $a = strtotime($pts[$i]['week']); $b2 = strtotime($pts[$i + 1]['week']);
            if ($ct >= $a && $ct <= $b2) { $camp_x = $xs($i) + ($xs($i + 1) - $xs($i)) * ($ct - $a) / max(1, $b2 - $a); break; }
        }
    }

    // End-point labels sit on the side away from the area-average line, so the
    // two never print over each other.
    $avg_v = $pg['area_average'];
    $lab_y = function ($score, $y) use ($avg_v, $B) {
        if (($avg_v !== null && $score >= $avg_v) || $y + 26 > $B - 4) { return round($y - 15, 1); }
        return round($y + 26, 1);
    };
    // The average's own label goes on whichever side of its line is clearer.
    $avg_label_dy = ($avg_v !== null && $pg['last']['score'] >= $avg_v - 6 && $pg['last']['score'] <= $avg_v + 14) ? 17 : -7;

    $aria = 'Chair Score by week: ' . implode(', ', array_map(function ($p) {
        return $p['label'] . ' ' . ($p['score'] === null ? 'no data' : $p['score']);
    }, $pts)) . ($pg['area_average'] !== null ? '. Area average ' . (int) $pg['area_average'] . '.' : '.');
  ?>
    <div class="occ-head">
      <div>
        <p class="occ-kicker"><?= html_escape($biz['name']) ?> &middot; 8-week report &middot; <?= html_escape($pg['range_label']) ?></p>
        <h1 class="occ-title occ-title--xl">Chair Score <span class="<?= $band_cls($first['band']) ?>"><?= (int) $first['score'] ?></span><span class="ocp-arrow">&rarr;</span><span class="<?= $band_cls($last['band']) ?>"><?= (int) $last['score'] ?></span></h1>
      </div>
      <span class="ocp-pill ocp-pill--<?= html_escape($last['band'] ?: 'none') ?>">
        <?= $changed ? html_escape($first['band_label'] . ' → ' . $last['band_label']) : 'Still ' . html_escape($last['band_label']) ?>
      </span>
    </div>

    <p class="occ-sub ocp-lede">
      <?php if ($more > 0): ?><?= $more ?> more chair-hour<?= $more === 1 ? '' : 's' ?> booked a week.
      <?php elseif ($more < 0): ?><?= abs($more) ?> fewer chair-hour<?= abs($more) === 1 ? '' : 's' ?> booked a week.
      <?php else: ?>The same number of chair-hours booked a week.<?php endif; ?>
      <?php if ($ub && $ua): ?>
        <?php if ($ua['lo'] < $ub['lo']): ?>
          Unsold time down from <s><?= html_escape(oc_range_tight($ub['lo'], $ub['hi'])) ?></s> to <b><?= html_escape(oc_range_tight($ua['lo'], $ua['hi'])) ?></b>.
        <?php elseif ($ua['lo'] > $ub['lo']): ?>
          Unsold time up from <?= html_escape(oc_range_tight($ub['lo'], $ub['hi'])) ?> to <b><?= html_escape(oc_range_tight($ua['lo'], $ua['hi'])) ?></b>.
        <?php else: ?>
          Unsold time about the same: <b><?= html_escape(oc_range_tight($ua['lo'], $ua['hi'])) ?></b>.
        <?php endif; ?>
      <?php endif; ?>
    </p>

    <div class="ocp-chart">
      <svg viewBox="0 0 <?= $W ?> <?= $H ?>" role="img" aria-label="<?= html_escape($aria) ?>">
        <?php foreach ([20, 40, 60, 80] as $g): ?>
          <line x1="<?= $L ?>" y1="<?= round($ys($g), 1) ?>" x2="<?= $Rt ?>" y2="<?= round($ys($g), 1) ?>" stroke="#ECE2DB" stroke-width="1"/>
          <text x="<?= $L - 10 ?>" y="<?= round($ys($g) + 4, 1) ?>" text-anchor="end" class="ocp-axis"><?= $g ?></text>
        <?php endforeach; ?>

        <?php if ($camp_x !== null): ?>
          <line x1="<?= round($camp_x, 1) ?>" y1="<?= $T - 4 ?>" x2="<?= round($camp_x, 1) ?>" y2="<?= $B + 4 ?>" stroke="#E2BDB2" stroke-width="1.5"/>
          <text x="<?= round($camp_x + 6, 1) ?>" y="<?= $B + 4 ?>" class="ocp-note">Work started <?= html_escape($pg['campaign']['label']) ?></text>
        <?php endif; ?>

        <?php if ($pg['area_average'] !== null): $ay = round($ys($pg['area_average']), 1); ?>
          <line x1="<?= $L ?>" y1="<?= $ay ?>" x2="<?= $Rt ?>" y2="<?= $ay ?>" stroke="#4A403A" stroke-width="1.4" stroke-dasharray="5 5"/>
          <text x="<?= $Rt - 34 ?>" y="<?= $ay + $avg_label_dy ?>" text-anchor="end" class="ocp-note">area average <?= (int) $pg['area_average'] ?></text>
        <?php endif; ?>

        <?php foreach ($segs as $sg): ?>
          <polyline points="<?= implode(' ', $sg) ?>" fill="none" stroke="#1E1915" stroke-width="2.6" stroke-linejoin="round" stroke-linecap="round"/>
        <?php endforeach; ?>

        <?php foreach ($pts as $i => $p): if ($p['score'] === null) { continue; }
          $x = round($xs($i), 1); $y = round($ys($p['score']), 1);
          if ($i === $fi): ?>
            <circle cx="<?= $x ?>" cy="<?= $y ?>" r="8" fill="<?= $band_hex($first['band']) ?>"/>
            <text x="<?= $x ?>" y="<?= $lab_y($p['score'], $y) ?>" text-anchor="middle" class="ocp-big" fill="<?= $band_hex($first['band']) ?>"><?= (int) $p['score'] ?></text>
          <?php elseif ($i === $li): ?>
            <circle cx="<?= $x ?>" cy="<?= $y ?>" r="8" fill="<?= $band_hex($last['band']) ?>"/>
            <text x="<?= $x ?>" y="<?= $lab_y($p['score'], $y) ?>" text-anchor="middle" class="ocp-big" fill="<?= $band_hex($last['band']) ?>"><?= (int) $p['score'] ?></text>
          <?php else: ?>
            <circle cx="<?= $x ?>" cy="<?= $y ?>" r="4.5" fill="#1E1915"/>
          <?php endif; ?>
        <?php endforeach; ?>

        <text x="<?= round($xs(0), 1) ?>" y="<?= $H - 6 ?>" text-anchor="middle" class="ocp-axis"><?= html_escape(strtoupper($pts[0]['label'])) ?></text>
        <text x="<?= round($xs($n - 1), 1) ?>" y="<?= $H - 6 ?>" text-anchor="middle" class="ocp-axis"><?= html_escape(strtoupper($pts[$n - 1]['label'])) ?></text>
      </svg>
    </div>

    <div class="ocp-weeks">
      <?php foreach ([$first, $last] as $w): ?>
        <div class="ocp-week">
          <div class="ocp-week-head">
            <h2><?= html_escape($w['week']['label']) ?></h2>
            <span class="occ-red"><?= (int) $w['week']['empty'] ?> empty</span>
          </div>
          <?php $this->load->view('visuals/_grid', [
              'grid'      => $mini_grid($w['week']),
              'grid_opts' => ['size' => 'sm', 'hours' => false, 'counts' => 'none', 'skip_shut' => true],
          ]); ?>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
  </div>

  <?php $this->load->view('visuals/_card_foot', [
      'card' => $card,
      'foot_skip_platform' => true,
      'foot_note' => 'Same method (' . $card['score']['method'] . ') for every week shown. Online-bookable slots only; '
                   . 'walk-ins and phone bookings aren\'t visible. The chart shows what changed, not why.',
  ]); ?>
</article>
