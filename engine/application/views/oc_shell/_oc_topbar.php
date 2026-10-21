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

// The scan box belongs to the PRODUCT screens. Showing "barbershop, Tampa" with
// a Scan button on the White-label or Manage Users page is chrome that invites
// the wrong action — those pages are account settings, not prospecting. They get
// the page title instead, so the topbar still says where you are.
$product_pages = ['today', 'radar', 'business', 'pitch', 'prospects', 'ledger', 'coverage', 'adapters'];
$is_product = in_array(isset($page) ? (string) $page : '', $product_pages, true);
?>
<header class="topbar">
<?php if (!$is_product): ?>
  <div class="topbar-title"><?= html_escape(isset($page_title) ? $page_title : '') ?></div>
  <span class="spacer" style="flex:1"></span>
  <a class="btn btn-sm" href="<?= site_url('openchair') ?>">&larr; Back to OpenChair</a>
<?php else: ?>
  <form method="get" action="<?= site_url('openchair/radar') ?>" role="search"
        style="display:contents">
    <div class="search">
      <svg width="15" height="15" viewBox="0 0 16 16" fill="none" aria-hidden="true">
        <circle cx="7" cy="7" r="5" stroke="var(--tx3)" stroke-width="1.5"/>
        <path d="M10.8 10.8 14 14" stroke="var(--tx3)" stroke-width="1.5" stroke-linecap="round"/>
      </svg>
      <input type="search" name="q" value="<?= html_escape($q) ?>" autocomplete="off" required maxlength="120"
             placeholder="barbershop, Tampa, FL — or a shop name" aria-label="Niche and city, or a shop name">
      <span class="kbd" aria-hidden="true">&#8629;</span>
    </div>
    <button type="submit" class="btn btn-primary">Search</button>
  </form>
  <a class="btn" href="<?= site_url('openchair/scan') ?>" title="Paste booking links to read">+ New scan</a>

  <?php if ($is_demo): ?>
    <span class="sample-flag" title="Seeded sample data — not a live scan">
      <span class="dot" aria-hidden="true"></span> Sample data
    </span>
  <?php endif; ?>
<?php endif; ?>
</header>
