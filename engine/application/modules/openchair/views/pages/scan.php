<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * S2/S3 — Scan setup and progress.
 *
 * Radar does not exist until Phase 3. 40-ux-spec.md P2 is explicit that a
 * full-screen spinner, or "your first results will be ready tomorrow morning",
 * is a refund — so this states plainly what is not built rather than animating a
 * progress bar that will never fill.
 *
 * Vars: $q
 */
?>
<div class="page">
  <div class="pagehead">
    <div>
      <h1>New scan</h1>
      <p>Pick a niche and a city. Results stream in one business at a time &mdash; you will be able to
         open the first pitch before the scan finishes.</p>
    </div>
  </div>

  <div class="panel">
    <div class="panel-head">
      <h2>Requested</h2>
      <span class="sub"><?= $q !== '' ? html_escape($q) : 'nothing entered' ?></span>
    </div>
    <div class="empty">
      <div class="e" aria-hidden="true">&#9673;</div>
      <h4>Radar is not built yet</h4>
      <p>Scanning arrives in Phase 3, and only after the spike proves the platforms can be read at a
         success rate and a cost that make the offer viable. Until then the seeded Tampa market is
         wired end to end, so every screen is walkable against real tables.</p>
      <a class="btn btn-primary" href="<?= site_url('openchair/radar') ?>">Open the sample market</a>
    </div>
  </div>
</div>
