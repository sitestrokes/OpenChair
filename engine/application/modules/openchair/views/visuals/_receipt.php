<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Card — the Receipt: "Unsold chair time" (Signature Visuals PDF p.4).
 *
 * A till receipt for the week the shop didn't sell: one line per open day,
 * worst first, empty hours and an unsold RANGE per line, then the totals and a
 * "NOT ON THIS RECEIPT" block that says what we couldn't see (rule 5). The
 * barcode is the week itself, hour by hour — red where every chair sat empty,
 * dark where the hour sold out, pale in between.
 *
 * Below the confidence floor the receipt keeps its shape but prints no hours,
 * no money, no score and no barcode (rule 3): a receipt with made-up totals is the worst artifact
 * this product could hand an owner.
 *
 * Vars: $card (payload, kind 'receipt' — needs $card['receipt']), $size 'print'|'phone'
 */
$s    = $card['score'];
$t    = $card['totals'];
$m    = $card['money'];
$biz  = $card['business'];
$r    = isset($card['receipt']) ? $card['receipt'] : ['rows' => [], 'bars' => []];
$gap  = !empty($card['gap']) || $s['value'] === null || $m === null;

$checked_on = !empty($card['checked']['at']) ? strtoupper(date('M j', strtotime($card['checked']['at']))) : 'NOT YET';

$not_seen = ['WALK-INS', 'PHONE BOOKINGS'];
foreach ($biz['walkin_chairs'] as $c) { $not_seen[] = 'CHAIR ' . (int) $c . ' (WALK-IN ONLY)'; }

$rate = $m ? oc_money($m['rate_lo']) . '–' . ltrim(oc_money($m['rate_hi']), '$') . '/HR' : '';

$bar_class = function ($v) {
    if ($v >= 0.999) { return 'e'; }
    if ($v <= 0.001) { return 'b'; }
    return 'p';
};
?>
<article class="occ occ--receipt">
  <div class="ocr-paper">
    <header class="ocr-head">
      <h1><?= html_escape(strtoupper($biz['name'])) ?></h1>
      <p><?= html_escape(strtoupper(str_replace(',', ' ·', $biz['area']))) ?></p>
      <h2>Unsold chair time</h2>
      <p>WEEK OF <?= html_escape(strtoupper($card['week']['label'])) ?> · CHECKED <?= html_escape($checked_on) ?></p>
    </header>

    <hr class="ocr-dash">

    <table class="ocr-lines">
      <thead>
        <tr><th scope="col">Day</th><th scope="col" class="n">Empty hrs</th><th scope="col" class="n">Unsold</th></tr>
      </thead>
      <tbody>
      <?php foreach ($r['rows'] as $row): ?>
        <tr>
          <td><?= html_escape($row['day']) ?></td>
          <td class="n"><?= $gap ? '—' : (int) $row['empty'] ?></td>
          <td class="n m"><?= $gap ? '—' : html_escape(oc_range_tight($row['lo'], $row['hi'])) ?></td>
        </tr>
        <?php if (!$gap && !empty($row['note'])): ?>
        <tr class="ocr-note"><td colspan="3">&#8627; <?= html_escape($row['note']) ?></td></tr>
        <?php endif; ?>
      <?php endforeach; ?>
      </tbody>
    </table>

    <hr class="ocr-dash">

    <dl class="ocr-totals">
      <div><dt>Total empty</dt><dd><?= $gap ? '—' : (int) $t['empty'] . ' OF ' . (int) $t['bookable'] . ' HRS' ?></dd></div>
      <div class="ocr-grand"><dt>Total unsold</dt>
        <dd><?= $gap ? '<span class="ocr-gap">NOT ENOUGH DATA YET</span>' : html_escape(oc_range_tight($m['lo'], $m['hi'])) ?></dd></div>
      <div><dt>Chair score</dt>
        <dd><b><?= $gap ? 'NOT ENOUGH DATA YET' : (int) $s['value'] . ' / 100 · ' . html_escape(strtoupper($s['label'])) ?></b></dd></div>
    </dl>

    <hr class="ocr-dash">

    <p class="ocr-small">NOT ON THIS RECEIPT: <?= html_escape(implode(', ', $not_seen)) ?>.</p>
    <?php if (!$gap): ?>
      <p class="ocr-small">UNSOLD = YOUR <?= $m['rate_source'] === 'average' ? 'AVERAGE MENU PRICE' : 'MENU PRICES' ?> (<?= html_escape($rate) ?>)
        × THE OPEN HOURS THAT COULD REALISTICALLY SELL. ESTIMATE.</p>
    <?php else: ?>
      <p class="ocr-small">A TOTAL IS PRINTED ONCE 5+ DAYS ARE CHECKED AND 70% OF HOURS ARE SEEN.</p>
    <?php endif; ?>

    <?php if (!$gap && !empty($r['bars'])): ?>
    <div class="ocr-barcode" role="img" aria-label="Your week, hour by hour: red where every chair sat empty, dark where the hour sold out">
      <?php foreach ($r['bars'] as $v): ?><i class="<?= $bar_class((float) $v) ?>"></i><?php endforeach; ?>
    </div>
    <p class="ocr-cap">YOUR WEEK, HOUR BY HOUR · RED = EMPTY</p>
    <?php endif; ?>
  </div>

  <?php $this->load->view('visuals/_card_foot', [
      'card'              => $card,
      'foot_note'         => 'Online-bookable slots only.',
      'foot_prepared_for' => true,
  ]); ?>
</article>
