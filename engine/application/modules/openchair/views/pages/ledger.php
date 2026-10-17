<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * S8 — Ledger. One component, two jobs (40-ux-spec.md §8).
 *
 * The chart that won the client is the chart that keeps them. So this screen is
 * read first by a prospect and later by a paying client — and the second
 * audience knows their own busy season and will check every claim on it.
 *
 * Which is why: partial weeks are drawn as GAPS rather than points, the cohort
 * line sits on the same axis as theirs, and the attribution caveat is part of
 * the report rather than a footer a white-label customer can switch off.
 *
 * Vars: $b, $weeks[], $cohort, $zones[], $lift, $caveat, $watchlist[]
 */
$rows = array_reverse($weeks);          // oldest first for the chart
$usable = array_filter($rows, function ($w) { return (int) $w->scan_days >= 5; });

$W = 760; $H = 200; $PAD = 34;
$n = max(1, count($rows));
$xstep = $n > 1 ? ($W - 2 * $PAD) / ($n - 1) : 0;
$y = function ($bp) use ($H, $PAD) { return round($PAD + (1 - $bp / 10000) * ($H - 2 * $PAD), 1); };

$days = ['Mon','Tue','Wed','Thu','Fri','Sat','Sun'];
$blocks = [];
foreach ($zones as $z) { $blocks[(int) $z->block_start_hour] = true; }
ksort($blocks);
$blocks = array_keys($blocks);
$zmap = [];
foreach ($zones as $z) { $zmap[(int) $z->dow][(int) $z->block_start_hour] = $z; }
?>
<div class="page">

  <div class="pagehead">
    <div>
      <h1>Client report &mdash; <?= html_escape($b->name) ?></h1>
      <p>Eight weeks of Chair Score &mdash; the share of online chair-hours that got booked. The screen
         that wins the client is the screen that keeps them &mdash; same data, eight weeks on.</p>
    </div>
    <div class="spacer"></div>
    <a class="btn" href="<?= site_url('openchair/business/' . (int) $b->id) ?>">Back to the data</a>
    <?php if (count($usable) >= 2): ?>
      <a class="btn btn-primary" href="<?= site_url('openchair/card/' . (int) $b->id . '/progress') ?>">8-week report card</a>
    <?php endif; ?>
  </div>

  <?php if (count($watchlist) > 1): ?>
    <div class="filters" style="padding:0 0 14px;border:0">
      <?php foreach (array_slice($watchlist, 0, 8) as $w): ?>
        <a class="chip" href="<?= site_url('openchair/ledger/' . (int) $w->business_id) ?>"
           aria-pressed="<?= (int) $w->business_id === (int) $b->id ? 'true' : 'false' ?>">
          <?= html_escape($w->name) ?>
        </a>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <?php if (count($usable) < 2): ?>

    <div class="panel">
      <div class="empty">
        <div class="e" aria-hidden="true">&#9650;</div>
        <h4>Not enough weeks yet</h4>
        <p>The Ledger needs weeks of observations before it can show one, and it cannot be
           back-filled. A trend line assembled from data we never observed would be the one number in
           this product that is simply false &mdash; so this screen stays empty until the history is
           real.</p>
        <p style="margin-top:10px"><b><?= count($weeks) ?></b> week<?= count($weeks) === 1 ? '' : 's' ?> recorded,
           <b><?= count($usable) ?></b> with enough scan days to plot.</p>
      </div>
    </div>

  <?php else: ?>

    <div class="panel">
      <div class="panel-head">
        <h2>Chair Score</h2>
        <span class="sub">higher is busier &middot; a gap means we did not see enough of that week</span>
      </div>

      <div class="trend">
        <svg viewBox="0 0 <?= $W ?> <?= $H ?>" style="width:100%;height:auto" role="img"
             aria-label="Weekly Chair Score for <?= html_escape($b->name) ?>">
          <?php for ($g = 0; $g <= 4; $g++): $gy = $PAD + $g * ($H - 2 * $PAD) / 4; ?>
            <line x1="<?= $PAD ?>" y1="<?= $gy ?>" x2="<?= $W - $PAD ?>" y2="<?= $gy ?>"
                  stroke="var(--line-soft)" stroke-width="1"/>
            <text x="6" y="<?= $gy + 3 ?>" font-family="Space Mono" font-size="9"
                  fill="var(--tx3)"><?= 100 - $g * 25 ?>%</text>
          <?php endfor; ?>

          <?php
          // Cohort line first, underneath — it is context, not the subject.
          $cpts = [];
          foreach ($rows as $i => $w) {
              if (!isset($cohort[$w->week_start])) { continue; }
              $cpts[] = round($PAD + $i * $xstep, 1) . ',' . $y($cohort[$w->week_start]['fill_rate_bp']);
          }
          if (count($cpts) > 1): ?>
            <polyline points="<?= implode(' ', $cpts) ?>" fill="none" stroke="var(--tx3)"
                      stroke-width="2" stroke-dasharray="4 4" opacity=".8"/>
          <?php endif; ?>

          <?php
          // The client's line, broken at every unusable week. A continuous line
          // across a gap is a claim we did not observe.
          $seg = [];
          foreach ($rows as $i => $w) {
              if ((int) $w->scan_days >= 5) {
                  $seg[] = round($PAD + $i * $xstep, 1) . ',' . $y((int) $w->fill_rate_bp);
              } else {
                  if (count($seg) > 1) {
                      echo '<polyline points="' . implode(' ', $seg) . '" fill="none" stroke="var(--accent)" stroke-width="2.6" stroke-linejoin="round"/>';
                  }
                  $seg = [];
              }
          }
          if (count($seg) > 1) {
              echo '<polyline points="' . implode(' ', $seg) . '" fill="none" stroke="var(--accent)" stroke-width="2.6" stroke-linejoin="round"/>';
          }

          foreach ($rows as $i => $w):
              $x = round($PAD + $i * $xstep, 1);
              $partial = (int) $w->scan_days < 5;
          ?>
            <?php if ($partial): ?>
              <circle cx="<?= $x ?>" cy="<?= $H / 2 ?>" r="3" fill="none" stroke="var(--tx3)" stroke-dasharray="2 2"/>
            <?php else: ?>
              <circle cx="<?= $x ?>" cy="<?= $y((int) $w->fill_rate_bp) ?>" r="4" fill="var(--accent)"/>
            <?php endif; ?>
            <text x="<?= $x ?>" y="<?= $H - 8 ?>" text-anchor="middle" font-family="Space Mono"
                  font-size="8.5" fill="var(--tx3)"><?= html_escape(date('j M', strtotime($w->week_start))) ?></text>
          <?php endforeach; ?>
        </svg>
      </div>

      <div class="legend2">
        <span><i style="background:var(--accent)"></i> <?= html_escape($b->name) ?></span>
        <span><i style="background:var(--tx3)"></i> comparable businesses in this metro</span>
        <span><i style="background:transparent;box-shadow:inset 0 0 0 1px var(--tx3)"></i> week not fully observed</span>
      </div>
    </div>

    <?php if ($lift !== null): ?>
      <div class="panel" style="margin-top:16px">
        <div class="panel-head"><h2>What changed</h2></div>
        <?php if (!empty($lift['claimable'])): ?>
          <div class="cards">
            <div class="mini">
              <h4>Their Chair Score</h4>
              <div class="big" style="color:var(--accent)"><?= $lift['client_delta'] > 0 ? '+' : '' ?><?= $lift['client_delta'] ?> pts</div>
              <div class="stat-note">Before the work versus after.</div>
            </div>
            <div class="mini">
              <h4>The market</h4>
              <div class="big" style="color:var(--tx3)"><?= $lift['cohort_delta'] > 0 ? '+' : '' ?><?= $lift['cohort_delta'] ?> pts</div>
              <div class="stat-note"><?= (int) $lift['cohort_n'] ?> comparable businesses moved this much over the same weeks.</div>
            </div>
            <div class="mini">
              <h4>Net of the market</h4>
              <div class="big" style="color:var(--cool)"><?= $lift['net'] > 0 ? '+' : '' ?><?= $lift['net'] ?> pts</div>
              <div class="stat-note">This is the only figure worth putting your name to.</div>
            </div>
          </div>
        <?php else: ?>
          <div class="notice" style="margin:16px">
            <span aria-hidden="true">&#9432;</span>
            <div><b>No lift claim yet.</b> <?= html_escape($lift['why']) ?></div>
          </div>
        <?php endif; ?>
      </div>
    <?php endif; ?>

  <?php endif; ?>

  <?php if (!empty($zones)): ?>
    <div class="panel" style="margin-top:16px">
      <div class="panel-head">
        <h2>Dead-Zone Map</h2>
        <span class="sub">which blocks are chronically open &mdash; this is where the offer goes</span>
      </div>
      <div class="dz" style="grid-template-columns:66px repeat(7,1fr)">
        <div class="hd"></div>
        <?php foreach ($days as $d): ?><div class="hd"><?= $d ?></div><?php endforeach; ?>
        <?php foreach ($blocks as $hour): ?>
          <div class="lbl"><?= date('ga', mktime($hour, 0, 0)) ?></div>
          <?php for ($dow = 0; $dow < 7; $dow++):
            $cell = $zmap[$dow][$hour] ?? null;
            if (!$cell) { echo '<div class="cell" style="opacity:.25">&mdash;</div>'; continue; }
            $bp = (int) $cell->open_ratio_bp;
            // A cell needs enough observations before it is called chronic.
            $chronic = $bp >= 6000 && (int) $cell->obs_count >= 3;
            $alpha = min(0.85, $bp / 10000);
          ?>
            <div class="cell<?= $chronic ? ' flag' : '' ?>"
                 style="background:rgba(255,92,67,<?= round($alpha, 2) ?>)"
                 title="<?= $days[$dow] ?> <?= date('ga', mktime($hour,0,0)) ?> — <?= round($bp/100) ?>% open over <?= (int) $cell->obs_count ?> observations">
              <?= round($bp / 100) ?>
            </div>
          <?php endfor; ?>
        <?php endforeach; ?>
      </div>
      <div class="ctrl">
        <div class="stat-note" style="margin:0">Outlined cells are chronic &mdash; open across at least
          three separate observations, not one quiet afternoon. Cells with too few observations show a
          dash rather than a number.</div>
      </div>
    </div>
  <?php endif; ?>

  <!-- Ships verbatim on every client-facing report (FR-8.3). Not editable, and
       not switchable off by a white-label customer. -->
  <div class="disclose" style="margin-top:16px">
    <h3><span aria-hidden="true">&#9673;</span> How to read this</h3>
    <p style="margin-top:4px"><?= html_escape($caveat) ?></p>
  </div>

</div>
