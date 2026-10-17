<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * S3 — a scan's progress (docs/70-radar-build.md R1).
 *
 * One row per pasted link, in the words of what actually happened to it:
 * waiting for discovery, recognised and queued, read and scored, not readable
 * (and why). No percentage bar — a bar implies an ETA we cannot honestly give
 * for a nightly read (40-ux-spec.md P2). The page refreshes itself while
 * anything is still moving, and works without JavaScript.
 *
 * Vars: $req, $items[], $skipped[], $watched_map
 */
$moving = false;
$state = function ($it) use (&$moving) {
    if (in_array($it->cand_status, ['new', 'probing'], true)) { $moving = true; return ['Waiting for discovery', 'low']; }
    if ($it->cand_status === 'rejected')     { return ['Not read — the owner asked not to be scanned', 'low']; }
    if ($it->cand_status === 'not_bookable') {
        return [$it->detected_platform_key ? 'Recognised (' . $it->detected_platform_key . '), not read yet' : 'No supported online booking found', 'low'];
    }
    if (!$it->business_id) { $moving = true; return ['Recognised — creating the listing', 'mid']; }
    if ($it->last_good_scan_at) { return ['Read ' . oc_data_age($it->last_good_scan_at), 'cool']; }
    if ($it->queue_status === 'failed') { return ['Read failed — will retry tomorrow', 'hot']; }
    $moving = true;
    return ['Queued for the next read', 'mid'];
};
$rows = [];
foreach ($items as $it) { $rows[] = [$it, $state($it)]; }
?>
<?php if ($moving): ?><meta http-equiv="refresh" content="20"><?php endif; ?>
<div class="page">
  <div class="pagehead">
    <div>
      <h1>Scan &mdash; <?= html_escape($req->city_label) ?></h1>
      <p><?= count($items) ?> link<?= count($items) === 1 ? '' : 's' ?> &middot; started <?= html_escape(oc_data_age($req->created_at)) ?>.
         <?= $moving ? 'This page refreshes itself while links are still moving.' : 'Everything here has settled.' ?></p>
    </div>
    <div class="spacer"></div>
    <a class="btn" href="<?= site_url('openchair/scan') ?>">New scan</a>
    <a class="btn btn-primary" href="<?= site_url('openchair/radar') ?>">Find businesses</a>
  </div>

  <?php if ($skipped): ?>
    <div class="notice"><span aria-hidden="true">&#9432;</span>
      <div><b><?= count($skipped) ?> line<?= count($skipped) === 1 ? ' was' : 's were' ?> skipped.</b>
        <ul style="margin:6px 0 0;padding-left:18px">
          <?php foreach (array_slice($skipped, 0, 10) as $b): ?><li><?= html_escape($b['line']) ?> &mdash; <?= html_escape($b['why']) ?></li><?php endforeach; ?>
        </ul></div></div>
  <?php endif; ?>

  <div class="panel">
    <div class="list" style="max-height:none">
      <?php foreach ($rows as $r): list($it, $st) = $r;
        $score = $it->business_id ? oc_chair_score_or_gap($it->latest_vacancy_bp, $it->latest_confidence) : '—';
        $band  = $it->business_id ? oc_chair_band(oc_chair_score($it->latest_vacancy_bp), $it->latest_days_to_next, $it->latest_confidence) : null;
        $cls   = oc_chair_band_class($band);
        $host  = parse_url($it->website, PHP_URL_HOST);
      ?>
        <div class="rowwrap">
          <<?= $it->business_id ? 'a href="' . site_url('openchair/business/' . (int) $it->business_id) . '"' : 'div' ?> class="row">
            <span class="score <?= $cls ?>" title="Chair Score"><?= html_escape($score) ?></span>
            <span>
              <span class="row-name"><?= html_escape($it->name ?: $host) ?>
                <span class="bucket <?= html_escape($st[1]) ?>"><?= html_escape($st[0]) ?></span></span>
              <span class="row-meta"><span class="plat"><?= html_escape($it->platform_name ?: ($it->detected_platform_key ?: $host)) ?></span>
                <span class="mono" style="overflow-wrap:anywhere"><?= html_escape(mb_strimwidth($it->website, 0, 90, '…')) ?></span></span>
            </span>
            <span class="row-money"><?= $band ? html_escape(oc_chair_band_label($band)) : '' ?></span>
          </<?= $it->business_id ? 'a' : 'div' ?>>
          <?php if ($it->business_id): $w = !empty($watched_map[(int) $it->business_id]); ?>
            <form method="post" class="rowact" action="<?= site_url('openchair/' . ($w ? 'unwatch' : 'watch')) ?>">
              <?= oc_csrf() ?><input type="hidden" name="business_id" value="<?= (int) $it->business_id ?>">
              <input type="hidden" name="back" value="<?= html_escape('openchair/scan/' . (int) $req->id) ?>">
              <button class="btn btn-sm<?= $w ? '' : ' btn-primary' ?>" type="submit"><?= $w ? '&#10003; Watching' : '+ Watch' ?></button>
            </form>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>
