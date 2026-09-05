<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * S7 — Prospects / watchlist. Pipeline lands in Phase 5.
 * Vars: $watched[]
 */
?>
<div class="page">
  <div class="pagehead">
    <div>
      <h1>Prospects</h1>
      <p>Businesses you are watching, and where each sits: Watching &rarr; Pitched &rarr; Client &rarr; Passed.</p>
    </div>
  </div>

  <div class="panel">
    <div class="empty">
      <div class="e" aria-hidden="true">&#9634;</div>
      <h4>Nothing on the watchlist yet</h4>
      <p>Watching a business is what makes it yours: it raises that business&rsquo;s scan priority, feeds
         your 8am list, and starts the 8-week Ledger trend that later becomes the retainer report.</p>
      <a class="btn btn-primary" href="<?= site_url('openchair/radar') ?>">Open Radar</a>
    </div>
  </div>

  <div class="notice" style="margin-top:16px">
    <span aria-hidden="true">&#9432;</span>
    <div><b>Phase 5.</b> Watchlist, pipeline stage, per-prospect notes and CSV export. The scan layer is
      shared &mdash; watching a business raises its priority; it never queues a second crawl of the same
      shop, however many people are watching it.</div>
  </div>
</div>
