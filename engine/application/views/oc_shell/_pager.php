<?php defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Shared pager (dark theme). Server-side, URL-based (?page=N, preserving ?q=).
 * Expects $pg: ['page'=>int, 'pages'=>int, 'total'=>int, 'base'=>url, 'q'=>str].
 * `base` is the page URL WITHOUT query (e.g. site_url('app/saved-offers')).
 * Renders nothing when there is 0 or 1 page.
 */
$pg    = isset($pg) && is_array($pg) ? $pg : [];
$page  = max(1, (int) ($pg['page'] ?? 1));
$pages = max(1, (int) ($pg['pages'] ?? 1));
$total = (int) ($pg['total'] ?? 0);
$base  = (string) ($pg['base'] ?? '');
$q     = trim((string) ($pg['q'] ?? ''));

if ($pages <= 1) { return; }

// Build a URL for a given page number, preserving the search term.
$link = function ($n) use ($base, $q) {
    $params = ['page' => $n];
    if ($q !== '') { $params['q'] = $q; }
    // `base` may already carry query params (e.g. Follow-ups bakes ?funnel=&tz=
    // into it so paging preserves the selected Bridge) — use & in that case.
    $sep = (strpos($base, '?') !== false) ? '&' : '?';
    return $base . $sep . http_build_query($params);
};

// A compact window of page numbers around the current page (with … gaps).
$window = 2;
$nums = [];
$nums[] = 1;
for ($i = $page - $window; $i <= $page + $window; $i++) {
    if ($i > 1 && $i < $pages) { $nums[] = $i; }
}
$nums[] = $pages;
$nums = array_values(array_unique(array_filter($nums, function ($n) use ($pages) { return $n >= 1 && $n <= $pages; })));
sort($nums);
?>
<style>
  .ic-pager{display:flex;align-items:center;gap:6px;flex-wrap:wrap;margin-top:22px;justify-content:center}
  .ic-pager .pg-info{font-family:var(--mono);font-size:11px;color:var(--faint);margin-right:auto}
  .ic-pg{min-width:34px;height:34px;padding:0 10px;display:inline-flex;align-items:center;justify-content:center;
    border:1px solid var(--line2);border-radius:9px;background:var(--bg);color:var(--txt2);font-size:13px;font-weight:600;
    text-decoration:none;transition:border-color .14s,color .14s,background .14s}
  .ic-pg:hover{border-color:var(--coral);color:var(--txt)}
  .ic-pg.on{border-color:var(--coral);color:#fff;background:linear-gradient(90deg,#ff7a59,#ff5a36);cursor:default}
  .ic-pg.dis{opacity:.4;pointer-events:none}
  .ic-pg-gap{color:var(--faint);padding:0 2px}
  @media(max-width:560px){ .ic-pager .pg-info{width:100%;margin:0 0 8px;text-align:center} }
</style>
<div class="ic-pager">
  <span class="pg-info"><?= number_format($total) ?> total · page <?= $page ?> of <?= $pages ?></span>

  <a class="ic-pg<?= $page <= 1 ? ' dis' : '' ?>" href="<?= $page <= 1 ? '#' : html_escape($link($page - 1)) ?>" rel="prev">‹ Prev</a>

  <?php $last = 0; foreach ($nums as $n): ?>
    <?php if ($last && $n > $last + 1): ?><span class="ic-pg-gap">…</span><?php endif; ?>
    <?php if ($n === $page): ?>
      <span class="ic-pg on"><?= $n ?></span>
    <?php else: ?>
      <a class="ic-pg" href="<?= html_escape($link($n)) ?>"><?= $n ?></a>
    <?php endif; ?>
    <?php $last = $n; ?>
  <?php endforeach; ?>

  <a class="ic-pg<?= $page >= $pages ? ' dis' : '' ?>" href="<?= $page >= $pages ? '#' : html_escape($link($page + 1)) ?>" rel="next">Next ›</a>
</div>
