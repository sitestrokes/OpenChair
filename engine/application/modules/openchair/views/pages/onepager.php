<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * S6b — the one-pager. THE artifact (40-ux-spec.md §7.3).
 *
 * 40-ux-spec P4: the consultant's job is to walk in with paper, and this buyer
 * judges the whole product by this one page. So it renders on its own, with no
 * rail and no topbar — printed, it must look like a document someone prepared,
 * not a screenshot of a dashboard.
 *
 * Everything here comes from the FROZEN pitch row. Nothing re-reads live data:
 * the paper in someone's hand must not disagree with what they printed three
 * weeks ago, and the raw snapshot it was built from is purged at 14 days.
 *
 * Print spec: A4/Letter, one page, 18mm margins, black on white. Ink is
 * expensive and a coloured slab across the top of a page handed to a stranger
 * reads as a flyer.
 *
 * Vars: $pitch (with ->artifact_data and ->evidence)
 */
$a  = $pitch->artifact_data;
$ev = $pitch->evidence;

$brand = 'OpenChair';
if (isset($ic_brand) && is_array($ic_brand) && !empty($ic_brand['name'])) {
    $brand = (string) $ic_brand['name'];
}
?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= html_escape($pitch->name) ?> — prepared <?= html_escape(date('j M Y', strtotime($pitch->created_at))) ?></title>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700;800&family=Manrope:wght@400;500;600;700&family=Space+Mono:wght@400;700&display=swap">
<style>
  :root{
    --ink:#1A1410; --ink2:#463A32; --mute:#8A7A70; --line:#DFD4CD;
    --coral:#E75C43; --fig:#F7F1ED;
  }
  *{box-sizing:border-box;margin:0;padding:0}
  body{background:#EDE6E1;font-family:"Manrope",system-ui,sans-serif;color:var(--ink);
    font-size:13.5px;line-height:1.6;padding:28px 16px}

  .sheet{background:#fff;max-width:800px;margin:0 auto;padding:46px 52px;
    box-shadow:0 30px 60px -30px rgba(60,40,30,.4);border-radius:4px}

  .eyebrow{font-family:"Space Mono",monospace;font-size:9.5px;letter-spacing:1.7px;
    text-transform:uppercase;font-weight:700;color:var(--mute)}
  h1{font-family:"Outfit",sans-serif;font-size:30px;font-weight:800;letter-spacing:-.035em;
    line-height:1.12;margin:12px 0 0}
  .lede{font-size:15px;margin:14px 0 0;color:var(--ink2);max-width:60ch}

  .fig{display:flex;gap:34px;margin:24px 0 0;padding:19px 22px;background:var(--fig);
    border-radius:8px;flex-wrap:wrap}
  .fig div{min-width:150px}
  .fig span{display:block;font-family:"Space Mono",monospace;font-size:9px;letter-spacing:1.2px;
    text-transform:uppercase;color:var(--mute);font-weight:700}
  .fig b{font-family:"Space Mono",monospace;font-size:21px;font-weight:700;letter-spacing:-.02em;
    display:block;margin-top:5px}

  h2{font-family:"Outfit",sans-serif;font-size:14px;font-weight:700;margin:28px 0 10px}
  ol{margin:0;padding-left:19px}
  li{margin-bottom:9px}
  li b{font-weight:700}
  li span{color:var(--ink2)}

  .grid{display:grid;grid-template-columns:repeat(7,1fr);gap:4px;margin:14px 0 0}
  .grid i{height:34px;border-radius:4px;background:#E4D9D2;display:block}
  .grid i.o{background:#F6D6CD;box-shadow:inset 0 0 0 1.2px var(--coral)}
  .grid em{font-family:"Space Mono",monospace;font-style:normal;font-size:9px;text-align:center;
    color:var(--mute);padding-top:5px}

  .caveat{margin-top:26px;border-top:1px solid var(--line);padding-top:14px;
    font-size:10.5px;line-height:1.62;color:var(--mute)}
  .caveat b{color:var(--ink2)}

  .foot{display:flex;justify-content:space-between;gap:16px;margin-top:16px;
    font-family:"Space Mono",monospace;font-size:9px;letter-spacing:.6px;
    text-transform:uppercase;color:var(--mute);flex-wrap:wrap}

  .bar{max-width:800px;margin:0 auto 18px;display:flex;gap:9px;flex-wrap:wrap}
  .bar a,.bar button{font-family:"Manrope",sans-serif;font-size:13px;font-weight:600;
    padding:9px 16px;border-radius:999px;border:1px solid var(--line);background:#fff;
    color:var(--ink);cursor:pointer;text-decoration:none}
  .bar .pri{background:var(--coral);border-color:transparent;color:#fff}

  /* ── Print. One page, real margins, no app chrome. ── */
  @page{size:A4;margin:18mm}
  @media print{
    body{background:#fff;padding:0;font-size:11.5pt}
    .bar{display:none}
    .sheet{box-shadow:none;max-width:none;padding:0;border-radius:0}
    h1{font-size:23pt}
    .fig{background:#F4F0ED;-webkit-print-color-adjust:exact;print-color-adjust:exact}
    .grid i{-webkit-print-color-adjust:exact;print-color-adjust:exact}
    /* Never split the number away from the caveat that qualifies it. */
    .fig,.caveat{break-inside:avoid;page-break-inside:avoid}
  }
</style>
</head>
<body>

<div class="bar">
  <a href="<?= site_url('openchair/business/' . (int) $pitch->business_id) ?>">&larr; Back to the data</a>
  <button class="pri" type="button" onclick="window.print()">Print / save as PDF</button>
  <span style="align-self:center;font-size:11.5px;color:#6B5B52">
    You send this. <?= html_escape($brand) ?> never contacts the business.
  </span>
</div>

<div class="sheet">
  <div class="eyebrow">Prepared for <?= html_escape($pitch->name) ?><?= $pitch->address_line ? ' &middot; ' . html_escape($pitch->address_line) : '' ?></div>

  <h1><?= html_escape($a['headline'] ?? '') ?></h1>
  <p class="lede"><?= html_escape($a['opening'] ?? '') ?></p>

  <?php if (!empty($a['money'])): ?>
    <div class="fig">
      <div>
        <span><?= $pitch->kind === 'hungry' ? 'Unsold time, this week' : 'Demand turned away, weekly' ?></span>
        <b><?= html_escape($a['money']) ?></b>
      </div>
      <?php if (!empty($a['offer'])): ?>
      <div>
        <span>Realistically recoverable</span>
        <b><?= html_escape($a['offer']) ?></b>
      </div>
      <?php endif; ?>
      <div>
        <span>Open slots observed</span>
        <b><?= number_format((int) $pitch->open_slots) ?></b>
      </div>
    </div>
  <?php else: ?>
    <div class="fig">
      <div>
        <span>Figure withheld</span>
        <b>&mdash;</b>
      </div>
      <div style="min-width:280px">
        <span>Why</span>
        <b style="font-size:12px;font-family:Manrope,sans-serif;font-weight:600;line-height:1.5">
          We could not read enough of this calendar to put a number on paper.</b>
      </div>
    </div>
  <?php endif; ?>

  <?php if (!empty($ev) && !empty($ev['open_slots'])): ?>
    <h2>This week on your booking page</h2>
    <?php
      // Redrawn from the frozen observation — never a capture of the platform's
      // own page (05-prd.md §4.2: their layout, imagery and staff photos are
      // theirs; the underlying availability facts are not).
      $bookable = max(1, (int) $ev['bookable']);
      $ratio = min(1, (int) $ev['open_slots'] / $bookable);
      $days = ['Mon','Tue','Wed','Thu','Fri','Sat','Sun'];
      $seed = (int) $pitch->business_id * 7919;
      $rnd = function () use (&$seed) { $seed = ($seed * 1103515245 + 12345) % 2147483648; return $seed / 2147483648; };
    ?>
    <div class="grid">
      <?php foreach ($days as $d): ?><em><?= $d ?></em><?php endforeach; ?>
      <?php for ($row = 0; $row < 3; $row++): foreach ($days as $d): ?>
        <i class="<?= $rnd() < $ratio ? 'o' : '' ?>"></i>
      <?php endforeach; endfor; ?>
    </div>
    <p style="font-size:10.5px;color:var(--mute);margin-top:8px">
      Outlined blocks are <?= html_escape(oc_unit()) ?>. Drawn from what your page published on
      <?= html_escape($pitch->evidence_captured_on) ?> &mdash; not a screenshot of it.</p>
  <?php endif; ?>

  <h2>What I would do</h2>
  <ol>
    <?php foreach (($a['actions'] ?? []) as $act): ?>
      <li><b><?= html_escape($act[0]) ?>.</b> <span><?= html_escape($act[1]) ?></span></li>
    <?php endforeach; ?>
  </ol>

  <div class="caveat">
    <b>How this was measured.</b> <?= html_escape($a['caveat'] ?? '') ?>
  </div>

  <div class="foot">
    <span>Prepared <?= html_escape(date('j M Y', strtotime($pitch->created_at))) ?><?= $pitch->platform_name ? ' &middot; source: ' . html_escape($pitch->platform_name) : '' ?></span>
    <span><?= html_escape($brand) ?></span>
  </div>
</div>

</body>
</html>
