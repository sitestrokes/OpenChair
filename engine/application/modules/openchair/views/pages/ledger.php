<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * S8 — Ledger. One component, two jobs (40-ux-spec.md §8): the prospecting
 * artifact and the retainer report are the same screen.
 *
 * Vars: $b
 */
?>
<div class="page">
  <div class="pagehead">
    <div>
      <h1>Ledger</h1>
      <p>Eight weeks of vacancy per business. The screen that won the client is the screen that keeps
         them &mdash; same data, eight weeks on.</p>
    </div>
  </div>

  <div class="panel">
    <div class="empty">
      <div class="e" aria-hidden="true">&#9650;</div>
      <h4>No trend yet</h4>
      <p>The Ledger needs weeks of observations before it can show one. It cannot be seeded and it
         cannot be back-filled &mdash; a trend line assembled from data we never observed would be the
         one number in this product that is simply false.</p>
    </div>
  </div>

  <div class="disclose" style="margin-top:16px">
    <h3><span aria-hidden="true">&#9673;</span> What the Ledger will and will not claim</h3>
    <p style="margin-top:4px">It measures online-bookable availability on the booking platform, not total
      business. It cannot separate the effect of your work from seasonality, weather, pricing, staffing
      or anything the owner did themselves. Where enough comparable businesses in the metro were scanned
      over the same weeks, their movement is shown alongside, so the owner can judge how much of the
      change was simply the market.</p>
    <p><b>Phase 7</b> &mdash; weekly rollups, the dead-zone matrix, white-label reports and share tokens.
      Nothing goes in front of a paying client until there are 8 real weeks on at least 50 businesses.</p>
  </div>
</div>
