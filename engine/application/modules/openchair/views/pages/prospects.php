<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * S7 — Prospects. The watchlist and the pipeline (40-ux-spec.md §2.2, FR-5.3).
 *
 * This is the screen that turns a scan into a business. It is also the only
 * screen where the tenant layer is visible on its own: everything here is one
 * consultant's relationship to businesses the whole customer base shares.
 *
 * Vars: $watched[], $counts, $f_stage
 */
$stages = [
  'all'       => 'All',
  'watching'  => 'Watching',
  'contacted' => 'Contacted',
  'meeting'   => 'Meeting',
  'pitched'   => 'Pitched',
  'won'       => 'Client',
  'lost'      => 'Passed',
];
$total = array_sum($counts);
$back  = 'openchair/prospects' . ($f_stage !== 'all' ? '?stage=' . urlencode($f_stage) : '');
?>
<div class="page">

  <div class="pagehead">
    <div>
      <h1>My pipeline</h1>
      <p>Businesses you are watching, and where each one sits. Watching a business raises its scan
         priority and starts its Ledger trend &mdash; it never queues a second crawl.</p>
    </div>
    <div class="spacer"></div>
    <a class="btn" href="<?= site_url('openchair/radar') ?>">Find businesses</a>
  </div>

  <?php if ($total === 0): ?>

    <div class="panel">
      <div class="empty">
        <div class="e" aria-hidden="true">&#9634;</div>
        <h4>Nothing on the watchlist yet</h4>
        <p>Open Find businesses, find the reddest pin in your market, and add it. From then on it appears in your
           8am list, and eight weeks of its vacancy history become the report you show them once they
           are a client.</p>
        <a class="btn btn-primary" href="<?= site_url('openchair/radar') ?>">Find businesses</a>
      </div>
    </div>

  <?php else: ?>

    <div class="panel">
      <div class="filters">
        <?php foreach ($stages as $key => $label):
          $n = $key === 'all' ? $total : (int) ($counts[$key] ?? 0);
          if ($key !== 'all' && $n === 0 && $f_stage !== $key) { continue; }
        ?>
          <a class="chip" href="<?= site_url('openchair/prospects' . ($key === 'all' ? '' : '?stage=' . $key)) ?>"
             aria-pressed="<?= $f_stage === $key ? 'true' : 'false' ?>">
            <?= html_escape($label) ?> <span class="cnt"><?= $n ?></span>
          </a>
        <?php endforeach; ?>
      </div>

      <div class="list" style="max-height:none">
        <?php if (empty($watched)): ?>
          <div class="empty" style="padding:34px 20px;text-align:center;color:var(--tx3)">
            Nothing at this stage.</div>
        <?php endif; ?>

        <?php foreach ($watched as $p):
          $band  = oc_chair_band_for($p);
          $cls   = oc_chair_band_class($band);
          $score = oc_chair_score_or_gap($p->latest_vacancy_bp, $p->latest_confidence);
          $low   = oc_below_floor($p->latest_confidence);
          // The bucket at add time versus now. Six weeks on, "you added this when
          // it was Hungry and it is Slammed now" is the whole retention story —
          // and it is only tellable because bucket_at_add was frozen.
          $moved = $p->bucket_at_add && $p->latest_bucket && $p->bucket_at_add !== $p->latest_bucket;
        ?>
          <div class="row" style="cursor:default;grid-template-columns:42px 1fr auto auto;gap:12px">
            <a class="score <?= $cls ?>" href="<?= site_url('openchair/business/' . (int) $p->business_id) ?>"
               style="text-decoration:none" title="Chair Score"><?= html_escape($score) ?></a>

            <span>
              <span class="row-name">
                <a href="<?= site_url('openchair/business/' . (int) $p->business_id) ?>"><?= html_escape($p->name) ?></a>
                <span class="bucket <?= $cls ?>"><?= oc_chair_glyph($band) ?> <?= html_escape(oc_chair_band_label($band)) ?></span>
                <?php if ($moved): ?>
                  <span class="chip" style="font-size:10px;padding:2px 8px">was <?= html_escape(oc_bucket_label($p->bucket_at_add)) ?></span>
                <?php endif; ?>
              </span>
              <span class="row-meta">
                <span class="plat"><?= html_escape($p->platform_name ?: '—') ?></span>
                <?= html_escape(oc_slots_phrase($p->latest_open_slots)) ?>
                &middot; scanned <?= html_escape(oc_data_age($p->last_good_scan_at)) ?>
              </span>
            </span>

            <span class="row-money">
              <?php if ($low): ?>
                &mdash;<small>not enough data</small>
              <?php else: ?>
                <?php $sn = oc_second_number($p); ?><?= html_escape($sn[0]) ?><small><?= html_escape($sn[1]) ?></small>
              <?php endif; ?>
            </span>

            <span style="display:flex;gap:6px;align-items:center">
              <form method="post" action="<?= site_url('openchair/stage') ?>" style="display:inline">
                <?= oc_csrf() ?><input type="hidden" name="business_id" value="<?= (int) $p->business_id ?>">
                <input type="hidden" name="back" value="<?= html_escape($back) ?>">
                <select name="stage" class="input" onchange="this.form.submit()"
                        style="width:auto;padding:6px 10px;font-size:12px"
                        aria-label="Pipeline stage for <?= html_escape($p->name) ?>">
                  <?php foreach ($stages as $k => $l): if ($k === 'all') { continue; } ?>
                    <option value="<?= $k ?>"<?= $p->stage === $k ? ' selected' : '' ?>><?= html_escape($l) ?></option>
                  <?php endforeach; ?>
                </select>
              </form>
              <a class="btn btn-sm" href="<?= site_url('openchair/pitch/' . (int) $p->business_id) ?>">Pitch</a>
              <form method="post" action="<?= site_url('openchair/unwatch') ?>" style="display:inline"
                    onsubmit="return confirm('Stop watching <?= html_escape(addslashes($p->name)) ?>? Its trend history is kept.')">
                <?= oc_csrf() ?><input type="hidden" name="business_id" value="<?= (int) $p->business_id ?>">
                <input type="hidden" name="back" value="<?= html_escape($back) ?>">
                <button class="btn btn-sm" type="submit" title="Stop watching">&times;</button>
              </form>
            </span>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

  <?php endif; ?>
</div>
