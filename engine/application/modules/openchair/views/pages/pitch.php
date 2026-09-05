<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * S6 — Pitch Composer (40-ux-spec.md §7).
 *
 * The demo's punch line lives here: the same scan, flipped between the two
 * variants. Hungry sells customers; Slammed sells capacity. Nothing about the
 * observation changes — only what you offer.
 *
 * There is NO SEND BUTTON, and there is no code path from this screen into a
 * queue or telephony. 05-prd.md §8 rule 7: the product drafts, the consultant
 * sends. That is asserted in the module's tests, not just written here.
 *
 * Vars: $b, $variant
 */
$hungry = ($variant === 'hungry');
$low    = oc_below_floor($b->latest_confidence);
$money  = $low ? null : oc_revenue_range($b->latest_empty_chair_cents, $b->latest_empty_chair_hi_cents);
$other  = $hungry ? 'slammed' : 'hungry';
?>
<div class="page">

  <div class="pagehead">
    <div>
      <h1>Pitch &mdash; <?= html_escape($b->name) ?></h1>
      <p>One scan, two offers. Flip the variant to see the same evidence make the opposite argument.</p>
    </div>
    <div class="spacer"></div>
    <a class="btn" href="<?= site_url('openchair/business/' . (int) $b->id) ?>">Back to the data</a>
  </div>

  <div class="pitchwrap">

    <!-- The handable artifact. Print spec lands in Phase 6; this is the composer preview. -->
    <div class="sheet">
      <div class="eyebrow"><?= $hungry ? 'Prepared for' : 'Prepared for' ?> <?= html_escape($b->name) ?></div>

      <?php if ($hungry): ?>
        <h2>You have <?= number_format((int) $b->latest_open_slots) ?> open slots this week.<br>I can fill them.</h2>
        <p class="lede">Your booking page shows <?= html_escape(oc_slots_phrase($b->latest_open_slots)) ?>
          out of <?= number_format((int) $b->latest_bookable_slots) ?> across <?= (int) $b->staff_count ?> chairs
          over the next seven days &mdash; concentrated on weekday afternoons.</p>
      <?php else: ?>
        <h2>You are booked out <?= html_escape(oc_next_available($b->latest_days_to_next)) ?>.<br>That is costing you.</h2>
        <p class="lede">Your next online opening is <?= html_escape(oc_next_available($b->latest_days_to_next)) ?> away.
          Every customer who checks your page today and cannot get in this week goes to the shop down the
          road &mdash; and you never see them, so it never feels like a loss.</p>
      <?php endif; ?>

      <?php if ($money !== null): ?>
        <div class="fig">
          <div>
            <span><?= $hungry ? 'Unsold time, this week' : 'Demand you are turning away' ?></span>
            <b><?= html_escape($money) ?></b>
          </div>
          <div>
            <span>Priced from</span>
            <b><?= html_escape(oc_money($b->avg_price_cents)) ?></b>
          </div>
        </div>
      <?php else: ?>
        <div class="fig">
          <div><span>Figure withheld</span><b>&mdash;</b></div>
        </div>
        <p style="margin-top:10px">We could not read enough of this calendar to put a number on paper.
          Walk in with the observation, not the figure.</p>
      <?php endif; ?>

      <h3>What I would do</h3>
      <?php if ($hungry): ?>
        <ol>
          <li><b>Midweek offer</b> aimed squarely at the afternoons that sit empty.</li>
          <li><b>Google offer post</b> so the discount shows where people already search for you.</li>
          <li><b>Review velocity</b> &mdash; ask every filled chair, so the next week starts higher.</li>
          <li><b>Last-minute list</b> so a cancellation is refilled instead of lost.</li>
        </ol>
      <?php else: ?>
        <ol>
          <li><b>Waitlist automation</b> so the people you turn away are captured, not lost.</li>
          <li><b>Price review</b> &mdash; a <?= html_escape(oc_next_available($b->latest_days_to_next)) ?> wait is the market telling you something.</li>
          <li><b>Deposit gate</b> to kill the no-shows that cost you a booked chair.</li>
          <li><b>Second chair or a hire</b> &mdash; and the numbers to justify it.</li>
        </ol>
      <?php endif; ?>

      <hr>
      <div class="foot">
        Based on <?= html_escape(oc_unit()) ?> published on <?= html_escape($b->platform_name ?: 'your booking platform') ?>,
        observed <?= html_escape(oc_data_age($b->last_good_scan_at)) ?>. This does not include walk-ins, phone
        bookings, or time you blocked out yourself, and it is not a statement about how busy your shop is
        overall. Figures are a range, not a forecast.
      </div>
    </div>

    <div class="panel">
      <div class="variant">
        <a class="chip hot"  href="<?= site_url('openchair/pitch/' . (int) $b->id . '?variant=hungry') ?>"  aria-pressed="<?= $hungry ? 'true' : 'false' ?>">&#9650; Hungry</a>
        <a class="chip cool" href="<?= site_url('openchair/pitch/' . (int) $b->id . '?variant=slammed') ?>" aria-pressed="<?= $hungry ? 'false' : 'true' ?>">&#9679; Slammed</a>
      </div>

      <div class="ctrl">
        <label>Same scan, opposite offer</label>
        <div class="stat-note" style="margin:0">
          This business is bucketed <b><?= html_escape(oc_bucket_label($b->latest_bucket)) ?></b>.
          You are viewing the <b><?= html_escape($hungry ? 'Hungry' : 'Slammed') ?></b> variant &mdash;
          <a href="<?= site_url('openchair/pitch/' . (int) $b->id . '?variant=' . $other) ?>">flip it</a>
          to see the other argument from the same evidence.
        </div>
      </div>

      <div class="ctrl">
        <label>Delivery</label>
        <div class="stat-note" style="margin:0">
          <b>You send this, not the tool.</b> OpenChair drafts the one-pager, the email and the SMS;
          it has no send action and no connection to any outbound queue. One &ldquo;the tool contacted
          my customers&rdquo; story would end the product, so the capability does not exist.
        </div>
      </div>

      <div class="ctrl">
        <label>Coming in phase 6</label>
        <div class="stat-note" style="margin:0">Print-spec PDF, editable email and SMS variants, and the
          frozen evidence copy so a pitch keeps its grid after the raw snapshot is purged at 14 days.</div>
      </div>
    </div>

  </div>
</div>
