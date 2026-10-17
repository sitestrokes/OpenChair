<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Card — "Your week, chair by chair" (Signature Visuals PDF p.1 and p.2).
 *
 * One card, four faces, chosen by the band:
 *   hungry   the second number is money: "≈ $2,900 – $4,100 unsold this week"
 *   slammed  the second number is wait: "first opening online is 9 days away"
 *   steady   the second number is quiet hours: the worst two-hour stretch
 *   gap      below the confidence floor — "Not enough data yet": no score, no
 *            band colour, no money, and no grid (rule 3: show the gap, not a
 *            number we would not stand behind)
 *
 * Renders from the payload only (Oc_visuals_lib::card()), so a frozen share
 * link draws exactly what the consultant saw.
 *
 * Vars: $card (payload, kind 'week'), $size 'print'|'phone'
 */
$s     = $card['score'];
$t     = $card['totals'];
$m     = $card['money'];
$biz   = $card['business'];
$band  = $s['band'];
$gap   = !empty($card['gap']) || $s['value'] === null;

// "≈ $2,900 – $4,100": the headline rounds both ends to $100 — still a range.
$r100 = function ($c) { return (int) (round($c / 10000) * 10000); };

// Days as words: "today", "1 day", "9 days".
$days_phrase = function ($n) {
    $n = (int) $n;
    return $n <= 0 ? 'today' : ($n === 1 ? '1 day' : $n . ' days');
};

// Steady's second number: the quietest two-hour stretch, read off the grid.
$quiet = null;
if ($band === 'steady') {
    $online = (int) $biz['online_chairs'];
    foreach ($card['grid']['days'] as $d) {
        if ($d['shut']) { continue; }
        $rows = $d['rows'];
        for ($i = 0; $i + 1 < count($rows); $i++) {
            $e = substr_count(substr($rows[$i], 0, $online), 'e') + substr_count(substr($rows[$i + 1], 0, $online), 'e');
            $seen = $e + substr_count(substr($rows[$i], 0, $online), 'b') + substr_count(substr($rows[$i + 1], 0, $online), 'b');
            if ($quiet === null || $e > $quiet['empty']) {
                $quiet = ['empty' => $e, 'seen' => $seen, 'day' => $d['name'], 'from' => $card['grid']['hours'][$i]];
            }
        }
    }
}
$clock = function ($h) { return ($h % 12 === 0 ? 12 : $h % 12) . ($h < 12 ? 'am' : 'pm'); };

$walkin = $biz['walkin_chairs'];
$timed_out = false;
foreach ($card['unseen_notes'] as $n) { if (stripos($n, 'timed out') !== false) { $timed_out = true; } }

if ($band === 'slammed') {
    $foot = 'Online-bookable slots only. Walk-ins and phone bookings aren\'t visible. '
          . 'Wait time is the next opening for a new client, standard service.';
} elseif ($gap) {
    $foot = 'Online-bookable slots only. We print a Chair Score once 5+ days are checked and 70% of hours are seen.';
} else {
    $rate = $m ? oc_money($m['rate_lo']) . '–' . ltrim(oc_money($m['rate_hi']), '$') . '/hr' : '';
    $foot = 'Online-bookable slots only. Walk-ins and phone bookings aren\'t visible. Money is '
          . ($m && $m['rate_source'] === 'average' ? 'your average menu price' : 'your own menu prices')
          . ($rate !== '' ? ' (' . $rate . ')' : '')
          . ' on the open hours that could realistically sell — an estimate.';
}
?>
<article class="occ occ--week occw--<?= html_escape($gap ? 'gap' : $band) ?>">
  <header class="occ-head">
    <div>
      <p class="occ-kicker">Your week, chair by chair</p>
      <h1 class="occ-title occ-title--xl"><?= html_escape($biz['name']) ?></h1>
      <p class="occ-sub"><?= html_escape($biz['area']) ?> · <?= (int) $biz['chairs'] ?> chair<?= $biz['chairs'] === 1 ? '' : 's' ?> · week of <?= html_escape($card['week']['label']) ?></p>
    </div>
    <div class="occ-band">
      <span class="occ-pill <?= html_escape($s['class']) ?>"><?= html_escape(strtoupper($gap ? 'Checking' : $s['label'])) ?></span>
      <small><?= html_escape($gap ? 'not enough data yet' : $s['need']) ?></small>
    </div>
  </header>

  <hr class="occ-rule">

  <section class="occw-hero">
    <div class="occw-score">
      <p class="occ-kicker">Chair score</p>
      <?php if ($gap): ?>
        <div class="occ-score is-gap">Not enough<br>data yet</div>
      <?php else: ?>
        <div class="occ-score <?= html_escape($s['class']) ?>"><?= (int) $s['value'] ?><small>/100</small></div>
      <?php endif; ?>
    </div>

    <div class="occw-lede">
      <?php if ($gap): ?>
        <h2>We haven't seen enough of this week to score it.</h2>
        <p>A score needs 5+ checked days and 70% of online hours seen. We'll show it as soon as we have it &mdash; a guess would be easy to argue with.</p>
      <?php elseif ($band === 'slammed'): ?>
        <h2>A new client's first opening online is <span class="occ-red"><?= html_escape($days_phrase($card['wait']['days'])) ?></span> away.</h2>
        <?php $ad = $card['wait']['area_days']; ?>
        <p><?php if ($ad === null): ?>Similar shops nearby open sooner.
           <?php elseif ((int) $ad <= 0): ?>Similar shops nearby have openings <b>today</b>.
           <?php else: ?>Similar shops nearby: <b><?= html_escape($days_phrase($ad)) ?></b>.<?php endif; ?>
           People who can't wait book somewhere else.</p>
      <?php elseif ($band === 'steady' && $quiet): ?>
        <h2><?= (int) $t['empty'] ?> of your <?= (int) $t['bookable'] ?> online&#8209;bookable chair-hours sat empty.</h2>
        <p>Quietest stretch: <b class="occ-red"><?= html_escape($quiet['day'] . ' ' . $clock($quiet['from']) . '–' . $clock($quiet['from'] + 2)) ?></b>, <?= (int) $quiet['empty'] ?> of <?= (int) $quiet['seen'] ?> chair-hours empty. That's where an offer goes.</p>
      <?php else: ?>
        <h2><?= (int) $t['empty'] ?> of your <?= (int) $t['bookable'] ?> online&#8209;bookable chair-hours sat empty.</h2>
        <?php if ($m): ?>
          <p>&asymp; <b class="occ-red"><?= html_escape(oc_money($r100($m['lo']))) ?> &ndash; <?= html_escape(oc_money($r100($m['hi']))) ?></b> unsold this week</p>
        <?php endif; ?>
      <?php endif; ?>
    </div>
  </section>

  <div class="occ-body">
    <?php if ($gap): ?>
      <div class="occw-gapbox">Grid hidden until the week is scored &mdash; <?= (int) $t['seen_pct'] ?>% of online hours seen so far.</div>
    <?php else: ?>
      <?php $this->load->view('visuals/_grid', ['grid' => $card['grid'], 'grid_opts' => ['counts' => 'fraction', 'size' => 'lg']]); ?>

      <div class="occ-legend">
        <span><i class="occ-key e"></i>empty</span>
        <span><i class="occ-key b"></i>booked</span>
        <?php if ($walkin): ?>
          <span><i class="occ-key u"></i>chair <?= html_escape(implode(', ', $walkin)) ?> <?= count($walkin) === 1 ? 'is' : 'are' ?> walk-in only, we can't see it</span>
        <?php endif; ?>
        <?php if ($timed_out): ?>
          <span><i class="occ-key u"></i><?= $walkin ? 'hatched hours: a check timed out' : 'a check timed out, we can\'t see it' ?></span>
        <?php endif; ?>
        <?php if ($band === 'slammed'): ?>
          <span class="occw-only">Only <?= (int) $t['empty'] ?> of <?= (int) $t['bookable'] ?> online chair-hours went unbooked all week.</span>
        <?php endif; ?>
      </div>
    <?php endif; ?>
  </div>

  <?php $this->load->view('visuals/_card_foot', ['card' => $card, 'foot_note' => $foot]); ?>
</article>
