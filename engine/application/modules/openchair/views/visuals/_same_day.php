<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Card — "Same Tuesday. Three shops, half a mile apart." (Signature Visuals PDF p.5).
 *
 * This shop's worst day beside the busiest nearby shops that were open the same
 * day, every chair, hour by hour. It answers the objection before it is made:
 * "Tuesdays are just dead round here." Not on this street, they aren't.
 *
 * Other shops are "Shop A…" by distance, never by name (rule 10). The title
 * counts the columns honestly — two shops if only one neighbour was open — and
 * $card['same_day'] may be null (no open day, or nobody nearby to compare),
 * in which case the card says so rather than drawing a comparison of one.
 *
 * Vars: $card (payload, kind 'tuesday' — needs $card['same_day']), $size 'print'|'phone'
 */
$gap = !empty($card['gap']) || $card['score']['value'] === null || $card['money'] === null;
// Below the confidence floor this shop's own day is not something we would
// stand behind (rule 3), so there is no comparison to draw — only the gap.
$sd  = (!$gap && isset($card['same_day'])) ? $card['same_day'] : null;

$cols  = $sd ? $sd['columns'] : [];
$ncols = count($cols);
$words = [1 => 'One', 2 => 'Two', 3 => 'Three', 4 => 'Four'];

// "half a mile apart" from the farthest neighbour shown. The column sub-line
// starts with its distance ("0.3 mi · 8 chairs" / "1.5 miles · 4 chairs").
$far = 0.0;
foreach ($cols as $c) {
    if (!empty($c['you'])) { continue; }
    if (isset($c['miles'])) { $far = max($far, (float) $c['miles']); }
    elseif (preg_match('/^\s*([\d.]+)\s*mi/', (string) $c['sub'], $mm)) { $far = max($far, (float) $mm[1]); } // links frozen before `miles`
}
if ($far <= 0)        { $apart = 'on the same street'; }
elseif ($far <= 0.5)  { $apart = 'half a mile apart'; }
elseif ($far <= 1.0)  { $apart = 'within a mile'; }
else                  { $apart = 'within ' . rtrim(rtrim(number_format(ceil($far * 2) / 2, 1), '0'), '.') . ' miles'; }

// Kicker hours: "9AM–7PM" from the grid's first row to the end of its last.
$hrs   = $card['grid']['hours'];
$clock = function ($h) { return ($h % 12 === 0 ? 12 : $h % 12) . ($h < 12 ? 'AM' : 'PM'); };
$span  = $hrs ? $clock(reset($hrs)) . '–' . $clock(end($hrs) + 1) : '';
?>
<article class="occ occ--tuesday">
  <header>
    <?php if ($sd): ?>
      <p class="occ-kicker"><?= html_escape(strtoupper($sd['day'] . ' ' . $sd['date'])) ?> · <?= html_escape($span) ?> · every chair</p>
      <h1 class="occ-title occ-title--xl ocs-title">Same <?= html_escape($sd['day']) ?>.<br>
        <?php if ($ncols >= 2): ?><?= html_escape($words[$ncols] ?? (string) $ncols) ?> shops, <?= html_escape($apart) ?>.<?php else: ?>No one nearby to compare yet.<?php endif; ?></h1>
    <?php else: ?>
      <p class="occ-kicker">Same day · every chair</p>
      <h1 class="occ-title occ-title--xl ocs-title"><?= $gap ? 'Not enough data yet<br>to compare this week.' : 'Not enough nearby shops<br>open that day.' ?></h1>
    <?php endif; ?>
  </header>

  <div class="occ-body">
  <?php if ($sd && $ncols >= 1): ?>
    <div class="ocs-cols" style="--ocs-n:<?= (int) $ncols ?>">
      <?php if ($sd['hours']): ?>
      <div class="ocs-hours" aria-hidden="true">
        <span class="ocs-hhead"></span>
        <?php foreach ($sd['hours'] as $h): ?><span><?= html_escape($h) ?></span><?php endforeach; ?>
      </div>
      <?php endif; ?>

      <?php foreach ($cols as $c):
        $chairs = $c['rows'] ? strlen($c['rows'][0]) : 0; ?>
        <section class="ocs-col<?= !empty($c['you']) ? ' is-you' : '' ?>">
          <header class="ocs-hhead">
            <h2><?= html_escape($c['label']) ?></h2>
            <p><?= html_escape($c['sub']) ?></p>
          </header>
          <div class="ocs-tiles" style="--ocs-chairs:<?= max(1, $chairs) ?>" role="img"
               aria-label="<?= html_escape($c['label'] . ': ' . (int) $c['empty'] . ' of ' . (int) $c['seen'] . ' chair-hours empty') ?>">
            <?php foreach ($c['rows'] as $row): for ($i = 0; $i < strlen($row); $i++): ?><i class="ocg-<?= html_escape($row[$i]) ?>"></i><?php endfor; endforeach; ?>
          </div>
          <p class="ocs-count"><b><?= (int) $c['empty'] ?></b> of <?= (int) $c['seen'] ?><small>chair-hours empty</small></p>
        </section>
      <?php endforeach; ?>
    </div>

    <div class="ocs-callout">
      <?php if ($sd['busiest']): ?>
        <?= html_escape($sd['day']) ?> isn't a dead day on your street: <?= html_escape($sd['busiest']['label']) ?> filled
        <?= (int) $sd['busiest']['booked'] ?> of its <?= (int) $sd['busiest']['seen'] ?> chair-hours.
      <?php else: ?>
        This was your emptiest day of the week.
      <?php endif; ?>
      <?php if (!$gap): ?>
        <span class="ocs-hl">Your <?= html_escape($sd['day']) ?> alone: &asymp; <?= html_escape(oc_range_tight($sd['unsold_lo'], $sd['unsold_hi'])) ?> unsold.</span>
      <?php endif; ?>
    </div>
  <?php else: ?>
    <div class="ocs-empty">
      <?php if ($gap): ?>
        We compare a shop's emptiest day with the busiest shops nearby once its week is scored &mdash;
        5+ checked days and 70% of online hours seen. We'll draw it as soon as we have that.
      <?php else: ?>
        We compare a shop's emptiest day with the busiest shops nearby that were open the same day.
        We couldn't find one for this week &mdash; the Chair Week card tells this shop's story on its own.
      <?php endif; ?>
    </div>
  <?php endif; ?>
  </div>

  <?php $this->load->view('visuals/_card_foot', [
      'card'               => $card,
      'foot_note'          => 'Online-bookable slots only, read from each shop\'s public booking page. Other shops aren\'t named. Red = empty, grey = booked.',
      'foot_skip_platform' => true,
  ]); ?>
</article>
