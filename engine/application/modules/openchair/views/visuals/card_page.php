<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * A card on its own page — the thing that is printed, saved as a PDF, exported
 * as a phone-size image, or opened from a share link.
 *
 * Own chrome, like the one-pager: no rail, no topbar, because this is an
 * artifact, not an app screen. Rendered two ways:
 *   app    — /openchair/card/{id}/{kind}: a toolbar to switch card, size, print,
 *            download; the consultant's preview of what they are about to send.
 *   share  — /c/{token}: the frozen copy a business owner opens. No toolbar,
 *            no links back into the app, noindex.
 *
 * Vars: $card (Oc_visuals_lib payload), $card_mode 'app'|'share', $size 'print'|'phone',
 *       $card_urls (kind => url, app mode), $back_url (app mode), $share_meta (share mode)
 */
$mode  = isset($card_mode) ? $card_mode : 'app';
$size  = (isset($size) && $size === 'phone') ? 'phone' : 'print';
$kind  = $card['kind'];
$parts = [
    'week'     => ['_chair_week', 'week'],
    'map'      => ['_chair_map', 'map'],
    'receipt'  => ['_receipt', 'receipt'],
    'tuesday'  => ['_same_day', 'sameday'],
    'progress' => ['_progress', 'progress'],
];
$part  = isset($parts[$kind]) ? $parts[$kind] : $parts['week'];
$names = ['week' => 'Chair Week', 'map' => 'Chair Map', 'receipt' => 'Receipt', 'tuesday' => 'Same day', 'progress' => '8-week report'];
$title = $card['business']['name'] . ' — ' . $names[$kind];
$css_v = '?v=' . @filemtime(FCPATH . 'assets/css/oc-visuals.css');
?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= html_escape($title) ?></title>
<?php if ($mode === 'share'): ?>
<meta name="robots" content="noindex,nofollow,noarchive">
<meta property="og:type" content="website">
<meta property="og:title" content="<?= html_escape($share_meta['og_title']) ?>">
<meta property="og:description" content="<?= html_escape($share_meta['og_description']) ?>">
<meta name="twitter:card" content="summary">
<?php endif; ?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&family=Manrope:wght@400;500;600;700;800&family=Space+Mono:wght@400;700&display=swap">
<link rel="stylesheet" href="<?= base_url('assets/css/oc-visuals.css') . $css_v ?>">
<link rel="stylesheet" href="<?= base_url('assets/css/visuals/' . $part[1] . '.css') . $css_v ?>">
</head>
<body class="occ-page<?= $size === 'phone' ? ' occ-phone' : '' ?>">

<?php if ($mode === 'app'): ?>
<nav class="occ-bar" aria-label="Card">
  <a class="back" href="<?= html_escape($back_url) ?>">&larr; <?= html_escape($card['business']['name']) ?></a>
  <span class="spacer"></span>
  <?php foreach ($card_urls as $k => $u): ?>
    <a href="<?= html_escape($u . ($size === 'phone' ? '?size=phone' : '')) ?>"<?= $k === $kind ? ' aria-current="true"' : '' ?>><?= html_escape($names[$k]) ?></a>
  <?php endforeach; ?>
</nav>
<nav class="occ-bar" aria-label="Export">
  <a href="<?= html_escape($card_urls[$kind]) ?>"<?= $size === 'print' ? ' aria-current="true"' : '' ?>>Page size</a>
  <a href="<?= html_escape($card_urls[$kind] . '?size=phone') ?>"<?= $size === 'phone' ? ' aria-current="true"' : '' ?>>Phone size</a>
  <span class="spacer"></span>
  <button type="button" data-oc-export="png" data-oc-name="<?= html_escape(url_title($title, '-', true)) ?>">Download image</button>
  <button type="button" onclick="window.print()">Download one-pager (PDF)</button>
</nav>
<?php endif; ?>

<div class="occ-shadow" id="oc-card">
  <?php $this->load->view('visuals/' . $part[0], ['card' => $card, 'size' => $size]); ?>
</div>

<?php if ($mode === 'app'): ?>
  <p class="occ-note">This is exactly what the business will see. Other shops are never named on a card; ranges,
     data age and &ldquo;online-bookable only&rdquo; are always printed. You send it &mdash; OpenChair never contacts the business.</p>
  <script src="<?= base_url('assets/js/oc-card-export.js') . $css_v ?>" defer></script>
  <?php if ($this->input->get('print')): ?><script>addEventListener('load', function () { setTimeout(function () { window.print(); }, 400); });</script><?php endif; ?>
<?php else: ?>
  <p class="occ-note"><?= html_escape($share_meta['note']) ?></p>
<?php endif; ?>
</body>
</html>
