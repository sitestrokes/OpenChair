<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Chair Map card — every shop on the street, one number each (PDF p.3).
 *
 * "You scored 41. The shop 3 blocks away scored 97." The subject sits at the
 * centre of a ring the size of the radius Oc_visuals_lib::street() actually
 * used; every other shop is a pin carrying its Chair Score.
 *
 * Rules this card carries:
 *   - Other shops are NEVER named: "Shop B · 4" by distance (rule 10).
 *   - The map is a stylised street grid we draw ourselves — no third-party tiles,
 *     no real street names, no request that leaks the market to a map vendor
 *     (docs/60 decision D-3).
 *   - Shops we found but cannot read are drawn as "?" and counted in the
 *     footnote ("6 of 7 shops visible") — what we couldn't see (rule 5).
 *   - A shop below the confidence floor shows "—", not a number (rule 3).
 *
 * Vars: $card (with $card['street']), $size 'print'|'phone'
 */
$st    = $card['street'];
$biz   = $card['business'];
$you   = $card['score']['value'];
$band  = $card['score']['band'];
$best  = $st['best'];

// "barbershop" → "BARBERSHOP" — the kicker reads "EVERY BARBERSHOP WITHIN 1 MILE".
$niche = strtoupper(str_replace(['_', '-'], ' ', $biz['niche'] !== '' ? $biz['niche'] : 'business'));

$ordinal = function ($n) {
    $n = (int) $n;
    $s = 'th';
    if (!in_array($n % 100, [11, 12, 13], true)) {
        $m = $n % 10;
        $s = $m === 1 ? 'st' : ($m === 2 ? 'nd' : ($m === 3 ? 'rd' : 'th'));
    }
    return $n . $s;
};

// Pin colours by band. Colour is never the only signal: every pin prints its
// number, and the legend names each style.
$fill = function ($b) {
    switch ($b) {
        case 'hungry':  return '#C8391F';
        case 'steady':  return '#0E6B55';
        case 'slammed': return '#1E1915';
        default:        return '#B3A69D';
    }
};

// ── Geometry ────────────────────────────────────────────────────────────────
$W = 700; $H = 520; $cx = 350; $cy = 262; $R = 218;
$radius = max(0.25, (float) $st['radius']);
$k = $R / $radius;     // px per mile
$place = function ($dx, $dy) use ($cx, $cy, $R, $k, $W, $H) {
    $x = (float) $dx * $k;
    $y = -(float) $dy * $k;
    $d = sqrt($x * $x + $y * $y);
    if ($d > $R + 12) { $x *= ($R + 12) / $d; $y *= ($R + 12) / $d; }   // clamp to the ring
    // Keep clear of the subject's pin and name tag: a neighbour 0.1 mi away is
    // still drawn beside it, not under it. Position is indicative, not survey.
    $d = sqrt($x * $x + $y * $y);
    $min = 92;
    if ($d < $min) {
        if ($d < 1) { $x = $min * 0.7; $y = -$min * 0.7; }
        else { $x *= $min / $d; $y *= $min / $d; }
    }
    return [max(34, min($W - 34, $cx + $x)), max(30, min($H - 44, $cy + $y))];
};

// Place every pin, then relax overlaps: pins (and their labels underneath) push
// apart until none sit on another or on the subject. Positions stay indicative —
// the card says "within 1.5 miles", not where each shop's door is.
$pins = [];
foreach ($st['shops'] as $i => $s) {
    list($x, $y) = $place($s['dx'], $s['dy']);
    $pins[] = ['x' => $x, 'y' => $y, 'r' => min(29, 15 + (int) $s['chairs'] * 1.8), 's' => $s, 'kind' => 'shop'];
}
foreach ($st['unseen'] as $u) {
    list($x, $y) = $place($u['dx'], $u['dy']);
    $pins[] = ['x' => $x, 'y' => $y, 'r' => 15, 's' => null, 'kind' => 'unseen'];
}
for ($iter = 0; $iter < 60; $iter++) {
    $moved = false;
    $n = count($pins);
    for ($i = 0; $i < $n; $i++) {
        // the subject's pin and name tag, as a fixed obstacle
        $ox = $pins[$i]['x'] - $cx; $oy = $pins[$i]['y'] - $cy;
        if (abs($ox) < 100 && $oy > -64 && $oy < 104) {
            $d = max(1, sqrt($ox * $ox + $oy * $oy));
            $pins[$i]['x'] += $ox / $d * 6; $pins[$i]['y'] += $oy / $d * 6; $moved = true;
        }
        for ($j = $i + 1; $j < $n; $j++) {
            $dx = $pins[$j]['x'] - $pins[$i]['x'];
            $dy = $pins[$j]['y'] - $pins[$i]['y'];
            $need = max($pins[$i]['r'] + $pins[$j]['r'] + 8, 66);
            // labels hang below a pin, so vertical neighbours need more room
            $dist = sqrt($dx * $dx + ($dy * 0.8) * ($dy * 0.8));
            if ($dist < $need) {
                if ($dist < 0.5) { $dx = 1; $dy = 0.3; $dist = 1; }
                $push = ($need - $dist) / 2;
                $ux = $dx / $dist; $uy = $dy / $dist;
                $pins[$i]['x'] -= $ux * $push; $pins[$i]['y'] -= $uy * $push;
                $pins[$j]['x'] += $ux * $push; $pins[$j]['y'] += $uy * $push;
                $moved = true;
            }
        }
    }
    foreach ($pins as &$p) {
        $p['x'] = max(34, min($W - 34, $p['x']));
        $p['y'] = max(30, min($H - 44, $p['y']));
    }
    unset($p);
    if (!$moved) { break; }
}

// Headline — honest whichever way the comparison falls.
if ($you === null) {
    $headline = null;
} elseif ($best && $best['score'] > $you) {
    $headline = 'best';
} else {
    $headline = 'top';
}

$aria = 'Map of ' . $st['visible'] . ' visible shops within ' . $st['radius_label'] . '. '
      . $biz['name'] . ': ' . ($you === null ? 'not enough data yet' : 'Chair Score ' . $you) . '. '
      . implode('; ', array_map(function ($s) {
            return $s['label'] . ', ' . $s['chairs'] . ' chairs, ' . ($s['score'] === null ? 'not enough data' : 'score ' . $s['score']);
        }, $st['shops']))
      . (count($st['unseen']) ? '; ' . count($st['unseen']) . ' without online booking.' : '.');

$name_tag = mb_strtoupper(mb_strlen($biz['name']) > 22 ? rtrim(mb_substr($biz['name'], 0, 21)) . '…' : $biz['name']) . ' · YOU';
?>
<article class="occ occ--map">
  <div class="occ-body">
    <p class="occ-kicker">Every <?= html_escape(strtolower($niche)) ?> within <?= html_escape($st['radius_label']) ?> &middot; week of <?= html_escape($card['week']['label']) ?></p>

    <?php if ($headline === 'best'): ?>
      <h1 class="occ-title ocm-headline">You scored <span class="<?= $band === 'hungry' ? 'occ-red' : ($band === 'steady' ? 'occ-green' : '') ?>"><?= (int) $you ?></span>. The shop <?= html_escape($best['distance']) ?> scored <?= (int) $best['score'] ?>.</h1>
    <?php elseif ($headline === 'top'): ?>
      <h1 class="occ-title ocm-headline">You scored <span class="<?= $band === 'hungry' ? 'occ-red' : ($band === 'steady' ? 'occ-green' : '') ?>"><?= (int) $you ?></span>. That&rsquo;s <?= $st['rank'] === 1 ? 'the busiest' : html_escape($ordinal($st['rank'])) ?> of <?= (int) $st['ranked'] ?> shops we can see around you.</h1>
    <?php else: ?>
      <h1 class="occ-title ocm-headline">Not enough data yet to score <?= html_escape($biz['name']) ?>. Here&rsquo;s the street.</h1>
    <?php endif; ?>

    <div class="ocm-map">
      <svg viewBox="0 0 <?= $W ?> <?= $H ?>" role="img" aria-label="<?= html_escape($aria) ?>">
        <rect x="0" y="0" width="<?= $W ?>" height="<?= $H ?>" fill="#EFE5DE"/>
        <!-- block texture -->
        <g stroke="#E4D7CE" stroke-width="1.2">
          <?php for ($x = 30; $x < $W; $x += 74): ?><line x1="<?= $x ?>" y1="0" x2="<?= $x ?>" y2="<?= $H ?>"/><?php endfor; ?>
          <?php for ($y = 22; $y < $H; $y += 58): ?><line x1="0" y1="<?= $y ?>" x2="<?= $W ?>" y2="<?= $y ?>"/><?php endfor; ?>
        </g>
        <!-- a river, for orientation only -->
        <path d="M150 -10 C 108 70, 175 120, 128 205 S 40 300, 70 380 S 10 470, -10 500"
              fill="none" stroke="#BFD6D8" stroke-width="15" stroke-linecap="round" opacity=".85"/>
        <!-- an expressway -->
        <path d="M232 -10 L 262 180 L 248 330 L 278 530" fill="none" stroke="#E2CDBF" stroke-width="13"/>
        <!-- main roads (unnamed by design) -->
        <g stroke="#FFFFFF" stroke-width="9">
          <line x1="0" y1="62" x2="<?= $W ?>" y2="62"/>
          <line x1="0" y1="148" x2="<?= $W ?>" y2="148"/>
          <line x1="0" y1="312" x2="<?= $W ?>" y2="312"/>
          <line x1="322" y1="0" x2="322" y2="<?= $H ?>"/>
          <line x1="468" y1="0" x2="468" y2="<?= $H ?>"/>
        </g>

        <!-- the radius ring -->
        <circle cx="<?= $cx ?>" cy="<?= $cy ?>" r="<?= $R ?>" fill="none" stroke="#8C7F76" stroke-width="1.4" stroke-dasharray="5 6"/>
        <rect x="<?= $cx - 34 ?>" y="<?= $cy - $R - 11 ?>" width="68" height="20" rx="4" fill="#EFE5DE"/>
        <text x="<?= $cx ?>" y="<?= $cy - $R + 4 ?>" text-anchor="middle" class="ocm-ring"><?= html_escape(strtoupper($st['radius_label'])) ?></text>

        <!-- shops without online booking, then neighbours -->
        <?php foreach ($pins as $p): $x = round($p['x'], 1); $y = round($p['y'], 1); ?>
          <?php if ($p['kind'] === 'unseen'): ?>
          <g class="ocm-unseen">
            <circle cx="<?= $x ?>" cy="<?= $y ?>" r="15" fill="#EFE5DE" stroke="#8C7F76" stroke-width="1.5" stroke-dasharray="3 3"/>
            <text x="<?= $x ?>" y="<?= $y + 5 ?>" text-anchor="middle" class="ocm-q">?</text>
          </g>
          <?php else: $s = $p['s']; $r = $p['r']; ?>
          <g class="ocm-pin">
            <?php if ($s['band'] === 'slammed'): ?>
              <circle cx="<?= $x ?>" cy="<?= $y ?>" r="<?= $r + 6 ?>" fill="none" stroke="#1E1915" stroke-width="2" stroke-dasharray="2.5 3"/>
            <?php endif; ?>
            <circle cx="<?= $x ?>" cy="<?= $y ?>" r="<?= $r ?>" fill="<?= $fill($s['band']) ?>"/>
            <text x="<?= $x ?>" y="<?= $y + 6 ?>" text-anchor="middle" class="ocm-n"><?= $s['score'] === null ? '—' : (int) $s['score'] ?></text>
            <text x="<?= $x ?>" y="<?= round($y + $r + ($s['band'] === 'slammed' ? 22 : 16), 1) ?>" text-anchor="middle" class="ocm-label"><?= html_escape($s['label'] . ' · ' . $s['chairs']) ?></text>
          </g>
          <?php endif; ?>
        <?php endforeach; ?>

        <!-- the subject -->
        <g class="ocm-you">
          <?php if ($band === 'slammed'): ?>
            <circle cx="<?= $cx ?>" cy="<?= $cy ?>" r="52" fill="none" stroke="#1E1915" stroke-width="2.4" stroke-dasharray="3 3.5"/>
          <?php endif; ?>
          <circle cx="<?= $cx ?>" cy="<?= $cy ?>" r="44" fill="#1E1915"/>
          <circle cx="<?= $cx ?>" cy="<?= $cy ?>" r="38" fill="<?= $fill($band) ?>" stroke="#FAF5F1" stroke-width="3"/>
          <text x="<?= $cx ?>" y="<?= $cy + 10 ?>" text-anchor="middle" class="ocm-n ocm-n--you"><?= $you === null ? '—' : (int) $you ?></text>
          <?php $tw = max(120, (int) round(mb_strlen($name_tag) * 8.6 + 26)); ?>
          <rect x="<?= $cx - $tw / 2 ?>" y="<?= $cy + 52 ?>" width="<?= $tw ?>" height="26" rx="13" fill="#1E1915"/>
          <text x="<?= $cx ?>" y="<?= $cy + 70 ?>" text-anchor="middle" class="ocm-tag"><?= html_escape($name_tag) ?></text>
        </g>
      </svg>
    </div>

    <div class="occ-tiles ocm-tiles">
      <div class="occ-tile">
        <p class="occ-kicker">Your rank</p>
        <?php if ($st['rank'] !== null): ?>
          <b class="<?= $st['rank'] > 1 && $band === 'hungry' ? 'occ-red' : '' ?>"><?= html_escape($ordinal($st['rank'])) ?> of <?= (int) $st['ranked'] ?></b>
          <small>visible shops</small>
        <?php else: ?>
          <b class="ocm-gap">&mdash;</b><small>not enough data yet</small>
        <?php endif; ?>
      </div>
      <div class="occ-tile">
        <p class="occ-kicker">Area average</p>
        <b><?= $st['average'] === null ? '&mdash;' : (int) $st['average'] ?></b>
        <small>Chair Score</small>
      </div>
      <div class="occ-tile">
        <?php if ($band === 'slammed'): ?>
          <p class="occ-kicker">Wait</p>
          <b><?= $card['wait']['days'] === null ? '&mdash;' : (int) $card['wait']['days'] . ' day' . ((int) $card['wait']['days'] === 1 ? '' : 's') ?></b>
          <small>first opening, new client</small>
        <?php else: ?>
          <p class="occ-kicker">Unsold this week</p>
          <?php if (!empty($card['money'])): ?>
            <b class="occ-red"><?= html_escape(oc_range_short($card['money']['lo'], $card['money']['hi'])) ?></b>
            <small>estimate, your prices</small>
          <?php else: ?>
            <b class="ocm-gap">&mdash;</b><small>not enough data yet</small>
          <?php endif; ?>
        <?php endif; ?>
      </div>
    </div>

    <div class="occ-legend ocm-legend">
      <span><i class="ocm-dot" style="background:#C8391F"></i> empty chairs</span>
      <span><i class="ocm-dot" style="background:#0E6B55"></i> busy</span>
      <span><i class="ocm-dot ocm-dot--full"></i> full, turning people away</span>
      <span><i class="ocm-dot ocm-dot--unseen"></i> no online booking</span>
    </div>
  </div>

  <?php $this->load->view('visuals/_card_foot', [
      'card' => $card,
      'foot_skip_platform' => true,
      'foot_note' => 'Number in each pin = Chair Score, the share of its online-bookable chair-hours that got booked. '
                   . (int) $st['visible'] . ' of ' . (int) $st['found'] . ' shops visible; other shops aren\'t named.',
  ]); ?>
</article>
