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

      <h2><?= html_escape($copy['headline']) ?></h2>
      <p class="lede"><?= html_escape($copy['opening']) ?></p>

      <?php if (!empty($copy['money'])): ?>
        <div class="fig">
          <div>
            <span><?= $hungry ? 'Unsold time, this week' : 'Demand turned away, weekly' ?></span>
            <b><?= html_escape($copy['money']) ?></b>
          </div>
          <?php if (!empty($copy['offer'])): ?>
          <div>
            <span>Realistically recoverable</span>
            <b><?= html_escape($copy['offer']) ?></b>
          </div>
          <?php endif; ?>
        </div>
      <?php else: ?>
        <div class="fig">
          <div><span>Figure withheld</span><b>&mdash;</b></div>
        </div>
        <p style="margin-top:10px">We could not read enough of this calendar to put a number on
          paper. Walk in with the observation, not the figure.</p>
      <?php endif; ?>

      <h3>What I would do</h3>
      <ol>
        <?php foreach ($copy['actions'] as $act): ?>
          <li><b><?= html_escape($act[0]) ?>.</b> <?= html_escape($act[1]) ?></li>
        <?php endforeach; ?>
      </ol>

      <hr>
      <div class="foot">
        <?= html_escape($copy['caveat']) ?>
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
        <form method="post" action="<?= site_url('openchair/make-pitch') ?>">
          <?= oc_csrf() ?><input type="hidden" name="business_id" value="<?= (int) $b->id ?>">
          <input type="hidden" name="variant" value="<?= html_escape($variant) ?>">
          <button class="btn btn-primary" type="submit" style="width:100%">Generate the one-pager</button>
        </form>
        <div class="stat-note" style="margin-top:9px">Freezes these exact numbers and this grid onto the
          artifact. Raw snapshots are purged at 14 days; a pitch keeps its evidence for good, so the
          paper never disagrees with itself later.</div>
      </div>

      <div class="ctrl">
        <label>Email draft</label>
        <div class="stat-note" style="margin:0 0 7px"><b>Subject:</b> <?= html_escape($copy['email']['subject']) ?></div>
        <textarea rows="9" readonly onclick="this.select()"
                  style="font-size:12px;line-height:1.55"><?= html_escape($copy['email']['body']) ?></textarea>
      </div>

      <div class="ctrl">
        <label>SMS draft &mdash; <?= mb_strlen($copy['sms']) ?> characters</label>
        <textarea rows="4" readonly onclick="this.select()"
                  style="font-size:12px;line-height:1.55"><?= html_escape($copy['sms']) ?></textarea>
        <div class="stat-note" style="margin-top:7px">You send this from your own number, having checked
          your own obligations. There is no send button here and no queue behind one.</div>
      </div>

      <?php if (!empty($previous)): ?>
      <div class="ctrl">
        <label><?= count($previous) ?> previously generated</label>
        <?php foreach (array_slice($previous, 0, 5) as $prev): ?>
          <div style="font-size:12px;margin-bottom:5px">
            <a href="<?= site_url('openchair/onepager/' . (int) $prev->id) ?>">
              <?= html_escape(oc_bucket_label($prev->kind === 'slammed' ? 'slammed' : 'hungry')) ?>
              &middot; <?= html_escape(date('j M', strtotime($prev->created_at))) ?>
            </a>
          </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>

  </div>
</div>
