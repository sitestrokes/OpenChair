<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * OpenChair dark shell — the engine-wide "capacity radar" chrome.
 *
 * Ported from ui/prototype.html, which is the agreed UI. Structure: a 220px
 * sticky left rail (grouped Prospect / Retain / Operate + quota meters) beside a
 * main column with a search + Scan topbar. The espresso ink ramp is the default;
 * the warm cream palette is the light variant, both driven by tokens in
 * assets/css/oc-shell.css.
 *
 * Lives at application/views/ (NOT inside modules/openchair/) so whitelabel/,
 * setting/, users/ and config/ render in identical chrome — the same reason the
 * host engine keeps ic_shell here.
 *
 * Dependency-free: own CSS, vanilla JS, no jQuery/Bootstrap. A ported legacy page
 * opts into the jQuery/SweetAlert2 bundle via Oc_render_lib's ['legacy' => true].
 *
 * Expects: $page (active rail key), $page_title, $content_view.
 * Optional: $page_head, $page_script, $oc_quota, $oc_can_* gates.
 */
$page       = isset($page) ? $page : 'today';
$page_title = isset($page_title) ? $page_title : 'OpenChair';

// Shared SVG icon map — ic_icon() / ic_svg(). Ported from the host engine and
// loaded here (not in the rail partial) because the ported common-module views
// call ic_svg() directly and must not depend on which partial ran first.
require_once __DIR__ . '/_icons.php';

$ic_brand    = isset($ic_brand) && is_array($ic_brand) ? $ic_brand : [];
$brand_name  = !empty($ic_brand['name'])       ? $ic_brand['name']       : 'OpenChair';
$brand_logo  = !empty($ic_brand['logo'])       ? $ic_brand['logo']       : '';
$brand_fav   = !empty($ic_brand['favicon'])    ? $ic_brand['favicon']    : '';
$brand_title = !empty($ic_brand['site_title']) ? $ic_brand['site_title'] : $brand_name;
$brand_foot  = !empty($ic_brand['footer'])     ? $ic_brand['footer']     : '';
?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= html_escape($page_title) ?> · <?= html_escape($brand_title) ?></title>
<?php if ($brand_fav !== ''): ?>
<link rel="icon" href="<?= html_escape($brand_fav) ?>">
<?php endif; ?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&family=Manrope:wght@400;500;600;700;800&family=Space+Mono:wght@400;700&display=swap">
<link rel="stylesheet" href="<?= base_url('assets/css/oc-shell.css') ?>">
<?= isset($page_head) ? $page_head : '' ?>
</head>
<body>
<a href="#oc-main" class="oc-skip-link">Skip to main content</a>

<div class="app">
  <?php $this->load->view('oc_shell/_oc_rail'); ?>

  <div class="main">
    <?php $this->load->view('oc_shell/_oc_topbar'); ?>

    <main id="oc-main" tabindex="-1">
      <?php $this->load->view($content_view, $this->data); ?>
    </main>

    <?php if ($brand_foot !== ''): ?>
    <div class="oc-foot"><?= html_escape($brand_foot) ?></div>
    <?php endif; ?>
  </div>
</div>

<script>
// Theme toggle. Default is the viewer's OS preference; an explicit choice is
// stamped on <html> and remembered. Wrapped because a browser with site data
// blocked throws on localStorage rather than returning null.
(function () {
  var KEY = 'oc-theme';
  try {
    var saved = localStorage.getItem(KEY);
    if (saved === 'light' || saved === 'dark') { document.documentElement.setAttribute('data-theme', saved); }
  } catch (e) { /* storage unavailable — fall back to prefers-color-scheme */ }

  window.ocToggleTheme = function () {
    var el = document.documentElement;
    var now = el.getAttribute('data-theme');
    if (!now) {
      now = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
    }
    var next = now === 'dark' ? 'light' : 'dark';
    el.setAttribute('data-theme', next);
    try { localStorage.setItem(KEY, next); } catch (e) { /* not fatal */ }
  };
})();
</script>
<?= isset($page_script) ? $page_script : '' ?>
</body>
</html>
