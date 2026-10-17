<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * How it works — the Chair Score explained (PDF p.6). Rail key: `how`.
 *
 * The one number every card leads with, in the words a consultant says out loud
 * in a shop. Thresholds are read from config (app_config.php, Chair Score v1.0)
 * rather than typed here, so this page can never disagree with the code that
 * draws the bands on Radar, the business screen and every card.
 *
 * The scale markers are an illustrative example and say so; this page reads no
 * business data.
 *
 * Vars: none (config only)
 */
$low   = (int) ($this->config->item('oc_chair_hungry_below') ?: 50);
$high  = (int) ($this->config->item('oc_chair_slammed_from') ?: 85);
$wait  = (int) ($this->config->item('oc_chair_wait_slammed_days') ?: 10);
$seen  = (int) ($this->config->item('oc_chair_min_seen_pct') ?: 70);
$ver   = oc_method_version();
$v     = filemtime(FCPATH . 'assets/css/visuals/how.css');

// Illustrative markers on the scale — an example street, not a live read.
$example = [
    ['v' => 41, 'label' => 'Hungry shop'],
    ['v' => 70, 'label' => 'area average'],
    ['v' => 94, 'label' => 'Slammed shop'],
];
?>
<link rel="stylesheet" href="<?= base_url('assets/css/oc-visuals.css') . '?v=' . @filemtime(FCPATH . 'assets/css/oc-visuals.css') ?>">
<link rel="stylesheet" href="<?= base_url('assets/css/visuals/how.css') . '?v=' . $v ?>">

<div class="page och">

  <div class="pagehead">
    <div>
      <h1>How it works</h1>
      <p>One number leads every card you send. Here is what it means, how to say it, and what we
         refuse to count.</p>
    </div>
  </div>

  <article class="occ occ--dark och-card" aria-labelledby="och-title">
    <p class="occ-kicker">The one number &middot; OpenChair&rsquo;s &ldquo;SoLV&rdquo;</p>
    <h2 class="och-title" id="och-title">Chair Score</h2>
    <p class="och-def">Out of every 100 hours a shop puts online, how many got booked.</p>

    <div class="och-quote">
      <p class="occ-kicker">How it sounds in the shop</p>
      <p>&ldquo;Your Chair Score is 41. Last week, 59 of every 100 hours you put online went empty.
         The shop down the street is at 97.&rdquo;</p>
    </div>

    <div class="och-scale" role="img"
         aria-label="Scale from 0 to 100: Hungry under <?= $low ?>, Steady <?= $low ?> to <?= $high - 1 ?>, Slammed <?= $high ?> and up. Example markers: 41, 70, 94.">
      <div class="och-marks">
        <?php foreach ($example as $m): ?>
          <span style="left:<?= (int) $m['v'] ?>%"><b><?= (int) $m['v'] ?></b><i></i></span>
        <?php endforeach; ?>
      </div>
      <div class="och-bar">
        <span class="och-seg och-seg--hungry"  style="width:<?= $low ?>%"></span>
        <span class="och-seg och-seg--steady"  style="width:<?= $high - $low ?>%"></span>
        <span class="och-seg och-seg--slammed" style="width:<?= 100 - $high ?>%"></span>
      </div>
      <div class="och-ticks">
        <span style="left:0">0</span>
        <span style="left:<?= $low ?>%"><?= $low ?></span>
        <span style="left:<?= $high ?>%"><?= $high ?></span>
        <span style="left:100%">100</span>
      </div>
      <p class="och-example">Example: <?= implode(' · ', array_map(function ($m) { return $m['label'] . ' ' . $m['v']; }, $example)) ?></p>
    </div>

    <div class="och-bands">
      <div class="och-band">
        <h3 class="och-hungry">Hungry &middot; under <?= $low ?></h3>
        <p>Needs customers. Second number:</p>
        <b>Unsold this week</b>
        <small>a $ range from their own menu</small>
      </div>
      <div class="och-band">
        <h3 class="och-steady">Steady &middot; <?= $low ?>&ndash;<?= $high - 1 ?></h3>
        <p>Uneven week. Second number:</p>
        <b>Quiet hours</b>
        <small>the Dead-Zone Map, in hours</small>
      </div>
      <div class="och-band">
        <h3>Slammed &middot; <?= $high ?>+</h3>
        <p>Needs capacity. Second number:</p>
        <b>Wait days</b>
        <small>first opening for a new client &mdash; <?= $wait ?>+ days is Slammed whatever the score</small>
      </div>
    </div>

    <ul class="och-rules">
      <li><b>Counted at the last check</b> before each slot starts, so a slot open 6 days out doesn&rsquo;t count as empty.</li>
      <li><b>Walk-in chairs are left out</b> and listed under &ldquo;what we couldn&rsquo;t see&rdquo;.</li>
      <li><b>&ldquo;Not enough data yet&rdquo;</b> until 5+ days are checked and <?= $seen ?>% of hours are seen.</li>
      <li><b>Method version on every card</b> (<?= html_escape($ver) ?>). Before/after only compares like with like.</li>
    </ul>

    <p class="och-foot">Higher = busier, so a full shop is proud of its number and a quiet one hears a plain fact.
      Rejected: &ldquo;Chair Share&rdquo; (share of all bookings on the street), because a 2-chair shop always loses
      to an 8-chair one.</p>
  </article>

  <section class="och-use" aria-labelledby="och-use-title">
    <h2 id="och-use-title">How to use the cards</h2>
    <p class="och-use-sub">Open any business, pick a card under <b>Share card</b>, and send the link or the image
      yourself. OpenChair never contacts the business.</p>
    <ol>
      <li><b>Chair Week</b> &mdash; their week, chair by chair. Opens the Hungry conversation with their own
        Tuesday afternoon, or the Slammed one with how many days a new client waits.</li>
      <li><b>Chair Map</b> &mdash; every shop around them, one number each. &ldquo;You scored 41; the shop 3 blocks
        away scored 97.&rdquo; Other shops are never named.</li>
      <li><b>Receipt</b> &mdash; unsold chair time as a till receipt, worst day first. The money is a range from
        their own menu.</li>
      <li><b>Same day</b> &mdash; their worst day beside two busier shops nearby, same hours, every chair. Shows the
        day isn&rsquo;t dead on their street.</li>
      <li><b>8-week report</b> &mdash; the score then and now, same method every week. Shows what changed,
        not why; use it once you are working with them.</li>
    </ol>
  </section>
</div>
