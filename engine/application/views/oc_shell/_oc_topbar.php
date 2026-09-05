<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * The topbar, from ui/prototype.html: the niche+city search box, the Scan
 * button, and a demo-data flag.
 *
 * The search box is the product's front door — the demo starts by typing
 * "barbershop, Tampa" — so it lives in the chrome rather than on one screen.
 * It GETs /openchair/scan, which is a page, so a scan is a linkable, refreshable
 * URL rather than a piece of hidden state.
 *
 * The sample flag is a first-class element, not a debug leftover: FR-1.4 puts a
 * demo dataset in front of the user on first login so time-to-first-value is not
 * gated on a crawl, and 05-prd.md §8 rule 6 says data provenance and age are
 * always visible. A number the user cannot tell apart from a real one is the
 * exact failure mode that loses the room.
 */
$q = isset($oc_query) ? (string) $oc_query : '';
$is_demo = !empty($oc_is_demo);
?>
<header class="topbar">
  <form method="get" action="<?= site_url('openchair/scan') ?>" role="search"
        style="display:contents">
    <div class="search">
      <svg width="15" height="15" viewBox="0 0 16 16" fill="none" aria-hidden="true">
        <circle cx="7" cy="7" r="5" stroke="var(--tx3)" stroke-width="1.5"/>
        <path d="M10.8 10.8 14 14" stroke="var(--tx3)" stroke-width="1.5" stroke-linecap="round"/>
      </svg>
      <input type="text" name="q" value="<?= html_escape($q) ?>" autocomplete="off"
             placeholder="barbershop, Tampa" aria-label="Niche and city">
      <span class="kbd" aria-hidden="true">&#8629;</span>
    </div>
    <button type="submit" class="btn btn-primary">Scan</button>
  </form>

  <?php if ($is_demo): ?>
    <span class="sample-flag" title="Seeded sample data — not a live scan">
      <span class="dot" aria-hidden="true"></span> Sample data
    </span>
  <?php endif; ?>
</header>
