<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * S1 — Today. The habit screen (40-ux-spec.md §2.2).
 *
 * Two bucket columns side by side, because that IS the product's argument: the
 * same scan produces two opposite pitches, and the Slammed column is the half
 * nobody else sells to.
 *
 * Vars: $totals, $counts, $hungry[], $slammed[]
 */
$stale = oc_is_stale($totals['last_scan']);
?>
<div class="page">

  <div class="pagehead">
    <div>
      <h1>Home</h1>
      <p>What your market looks like this morning. One scan, two pitches &mdash; Hungry shops need
         customers, Slammed shops need capacity. Every number is a
         <a href="<?= site_url('openchair/how-it-works') ?>">Chair Score</a>: higher = busier.</p>
    </div>
  </div>

  <?php if ($stale): ?>
    <div class="stale">
      <span aria-hidden="true">&#9888;</span>
      <div><b>Data is <?= html_escape(oc_data_age($totals['last_scan'])) ?>.</b>
        Availability changes through the day; treat these as of the last observation, not this minute.</div>
    </div>
  <?php endif; ?>

  <!-- The number is the product (40-ux-spec P1): money first, on every screen. -->
  <div class="cards" style="padding:0 0 18px">
    <div class="mini">
      <h4>Unsold time this week</h4>
      <div class="big"><?= html_escape(oc_revenue_range($totals['ecr_cents'], $totals['ecr_hi_cents'])) ?></div>
      <div class="stat-note">Across <?= (int) $totals['businesses'] ?> businesses with public online booking.
        A range, never a single figure &mdash; priced from their own published menus.</div>
    </div>
    <div class="mini">
      <h4>Open slots</h4>
      <div class="big"><?= number_format($totals['open_slots']) ?></div>
      <div class="stat-note"><?= html_escape(oc_unit()) ?> in the next 7 days.</div>
    </div>
    <div class="mini">
      <h4>Hungry</h4>
      <div class="big" style="color:var(--hot)"><?= (int) $counts['hungry'] ?></div>
      <div class="stat-note">Sell them customers.</div>
    </div>
    <div class="mini">
      <h4>Slammed</h4>
      <div class="big" style="color:var(--cool)"><?= (int) $counts['slammed'] ?></div>
      <div class="stat-note">Sell them capacity. Nobody else is pitching these.</div>
    </div>
  </div>

  <?php if (!empty($totals['below_floor'])): ?>
    <div class="notice">
      <span aria-hidden="true">&#9432;</span>
      <div><b><?= (int) $totals['below_floor'] ?> businesses are not showing a score.</b>
        We could not read enough of their calendar to stand behind a number, so we are showing you the
        gap instead of a guess. They are excluded from the totals above.</div>
    </div>
  <?php endif; ?>

  <!-- The 8am list, as sent. Every figure here comes from the alert item row,
       not from a live re-read: a digest is a claim made at a moment, and a
       consultant clicking through three hours later must not find different
       numbers from the ones they read over breakfast. -->
  <div class="panel" style="margin-bottom:18px">
    <div class="panel-head">
      <h2>Your 8am list</h2>
      <?php if ($digest): ?>
        <span class="sub"><?= html_escape(date('j M', strtotime($digest->run_date))) ?>
          &middot; <?= (int) $digest->total_count ?> businesses
          &middot; <?= (int) $digest->hungry_count ?> hungry, <?= (int) $digest->slammed_count ?> slammed</span>
      <?php endif; ?>
      <span class="spacer"></span>
      <form method="post" action="<?= site_url('openchair/build-digest') ?>" style="margin:0">
        <?= oc_csrf() ?><button class="btn btn-sm" type="submit"><?= $digest ? 'Rebuild' : 'Build the list' ?></button>
      </form>
    </div>

    <?php if (!$digest || empty($digest_items)): ?>
      <div class="empty" style="padding:30px 20px;text-align:center;color:var(--tx3)">
        <?php if (!$digest): ?>
          No digest yet. It is built each morning from the businesses you watch &mdash;
          <a href="<?= site_url('openchair/radar') ?>">watch a few</a> and build one.
        <?php else: ?>
          Nothing met the bar today. Businesses below the confidence floor are left out rather than
          padded in: a list that leads with figures we would not stand behind one click later is worse
          than a short list.
        <?php endif; ?>
      </div>
    <?php else: ?>
      <div class="list">
        <?php foreach ($digest_items as $it):
          $cls = $it->bucket === 'hungry' ? 'hot' : 'cool';
          // As sent: the digest row's own vacancy, shown as a Chair Score.
        ?>
          <a class="row" href="<?= site_url('openchair/business/' . (int) $it->business_id) ?>">
            <span class="score <?= $cls ?>" title="Chair Score"><?= (int) oc_chair_score($it->vacancy_bp) ?></span>
            <span>
              <span class="row-name">
                <?= html_escape($it->name) ?>
                <span class="bucket <?= $cls ?>"><?= oc_bucket_glyph($it->bucket) ?> <?= html_escape(oc_bucket_label($it->bucket)) ?></span>
                <?php if ($it->is_new): ?><span class="chip coral" style="font-size:9.5px">new</span><?php endif; ?>
              </span>
              <span class="row-meta">
                <span class="plat"><?= html_escape($it->platform_name ?: '—') ?></span>
                <?php if ($it->bucket === 'slammed'): ?>
                  next available: <?= html_escape(oc_next_available($it->days_to_next)) ?>
                <?php else: ?>
                  <?= html_escape(oc_slots_phrase($it->open_slots)) ?>
                <?php endif; ?>
              </span>
            </span>
            <span class="row-money"><?= html_escape(oc_money($it->empty_chair_cents)) ?><small>unsold, low end</small></span>
          </a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>

  <div class="split">
    <?php
    $cols = [
      ['title' => 'Hungry',  'sub' => 'sell them customers', 'rows' => $hungry,  'cls' => 'hot',
       'empty' => 'No business in this market has a Chair Score under 50 today.'],
      ['title' => 'Slammed', 'sub' => 'sell them capacity',  'rows' => $slammed, 'cls' => 'cool',
       'empty' => 'Nobody is booked far enough out to qualify today.'],
    ];
    foreach ($cols as $col): ?>
      <div class="panel">
        <div class="panel-head">
          <h2><span class="bucket <?= $col['cls'] ?>"><?= oc_bucket_glyph($col['cls'] === 'hot' ? 'hungry' : 'slammed') ?> <?= html_escape($col['title']) ?></span></h2>
          <span class="sub"><?= html_escape($col['sub']) ?></span>
        </div>
        <?php if (empty($col['rows'])): ?>
          <div class="empty" style="padding:34px 20px;text-align:center;color:var(--tx3)"><?= html_escape($col['empty']) ?></div>
        <?php else: ?>
          <div class="list">
            <?php foreach ($col['rows'] as $b):
              $cls  = oc_chair_band_class(oc_chair_band_for($b));
              $score = oc_chair_score_or_gap($b->latest_vacancy_bp, $b->latest_confidence);
              $low  = oc_below_floor($b->latest_confidence);
            ?>
              <a class="row" href="<?= site_url('openchair/business/' . (int) $b->id) ?>">
                <span class="score <?= $cls ?>" title="Chair Score"><?= html_escape($score) ?></span>
                <span>
                  <span class="row-name"><?= html_escape($b->name) ?></span>
                  <span class="row-meta">
                    <span class="plat"><?= html_escape($b->platform_name ?: '—') ?></span>
                    <?php if ($col['cls'] === 'cool'): ?>
                      next available: <?= html_escape(oc_next_available($b->latest_days_to_next)) ?>
                    <?php else: ?>
                      <?= html_escape(oc_slots_phrase($b->latest_open_slots)) ?>
                    <?php endif; ?>
                  </span>
                </span>
                <span class="row-money">
                  <?php if ($low): ?>
                    &mdash;<small>not enough data</small>
                  <?php else: ?>
                    <?php $sn = oc_second_number($b); ?><?= html_escape($sn[0]) ?><small><?= html_escape($sn[1]) ?></small>
                  <?php endif; ?>
                </span>
              </a>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
        <div class="ctrl">
          <a class="btn btn-sm" href="<?= site_url('openchair/radar?bucket=' . ($col['cls'] === 'hot' ? 'hungry' : 'slammed')) ?>">See all <?= html_escape(strtolower($col['title'])) ?></a>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

</div>
