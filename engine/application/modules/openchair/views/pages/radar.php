<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * S4 — Find businesses / search results, as cards.
 *
 * Two kinds of card, never mixed up:
 *   1. Tracked — OpenChair reads this shop's public booking page. Ring = Chair
 *      Score (out of every 100 chair-hours put online, how many got booked);
 *      "—" when below the confidence floor (rule 3: show the gap, not a guess).
 *   2. On Google, not tracked yet — a real shop from Places with no Chair Score,
 *      labelled with what discovery found about its booking (FR-2.3).
 * Every card prints the literal number beside any colour (NFR-8).
 *
 * Vars: $q, $search, $name_mode, $businesses[], $google[], $places, $has_key,
 *       $counts, $totals, $market_label, $f_bucket, $f_sort, $watched_map
 */
$qs = function ($over) use ($f_bucket, $f_sort) {
    $p = array_merge(['bucket' => $f_bucket, 'sort' => $f_sort], $over);
    $p = array_filter($p, function ($v) { return $v !== null && $v !== ''; });
    return site_url('openchair/radar') . ($p ? '?' . http_build_query($p) : '');
};
// Watch/unwatch return here. Only bucket/sort ride along: they are plain words,
// so the URL passes _safe_back(); the market itself lives in the session.
$back = 'openchair/radar' . (($f_bucket || $f_sort !== 'vacancy')
      ? '?' . http_build_query(array_filter(['bucket' => $f_bucket, 'sort' => $f_sort])) : '');

$initials = function ($name) {
    $w = preg_split('/[^A-Za-z0-9]+/', (string) $name, -1, PREG_SPLIT_NO_EMPTY);
    $s = '';
    foreach ($w as $x) { $s .= strtoupper($x[0]); if (strlen($s) === 2) { break; } }
    return $s !== '' ? $s : '?';
};
$host = function ($url) {
    $h = parse_url((string) $url, PHP_URL_HOST);
    return $h ? preg_replace('/^www\./', '', $h) : '';
};
$ring = function ($score, $cls, $label) {
    $circ = 2 * M_PI * 19;
    $num  = is_numeric($score) ? max(0, min(100, (int) $score)) : 0;
    $off  = $circ * (1 - $num / 100);
    return '<svg class="occ-ring ' . $cls . '" viewBox="0 0 46 46" role="img" aria-label="' . html_escape($label) . '">'
         . '<circle class="t" cx="23" cy="23" r="19"/>'
         . (is_numeric($score) ? '<circle class="v" cx="23" cy="23" r="19" stroke-dasharray="' . round($circ, 2) . '" stroke-dashoffset="' . round($off, 2) . '"/>' : '')
         . '<text x="23" y="27.5" text-anchor="middle">' . html_escape((string) $score) . '</text></svg>';
};
$ico = [
    'pin'   => '<svg viewBox="0 0 16 16" aria-hidden="true"><path d="M8 14s4.5-4.2 4.5-7.5a4.5 4.5 0 0 0-9 0C3.5 9.8 8 14 8 14z" fill="none" stroke="currentColor" stroke-width="1.4"/><circle cx="8" cy="6.5" r="1.6" fill="currentColor"/></svg>',
    'phone' => '<svg viewBox="0 0 16 16" aria-hidden="true"><path d="M4 2h2.5l1 3-1.5 1a8 8 0 0 0 4 4l1-1.5 3 1V12a2 2 0 0 1-2 2A10 10 0 0 1 2 4a2 2 0 0 1 2-2z" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linejoin="round"/></svg>',
    'web'   => '<svg viewBox="0 0 16 16" aria-hidden="true"><circle cx="8" cy="8" r="6" fill="none" stroke="currentColor" stroke-width="1.4"/><path d="M2 8h12M8 2c2 2 2 10 0 12M8 2c-2 2-2 10 0 12" fill="none" stroke="currentColor" stroke-width="1.2"/></svg>',
    'cal'   => '<svg viewBox="0 0 16 16" aria-hidden="true"><rect x="2.5" y="3.5" width="11" height="10" rx="2" fill="none" stroke="currentColor" stroke-width="1.4"/><path d="M2.5 6.5h11M5.5 2v3M10.5 2v3" stroke="currentColor" stroke-width="1.4"/></svg>',
    'star'  => '<svg viewBox="0 0 16 16" aria-hidden="true"><path d="M8 1.8l1.9 3.9 4.3.6-3.1 3 .7 4.3L8 11.6l-3.8 2 .7-4.3-3.1-3 4.3-.6z" fill="currentColor"/></svg>',
];
$total_tracked = count($businesses);
?>
<div class="page">

  <div class="pagehead">
    <div>
      <?php if ($name_mode): ?>
        <h1>Results for &ldquo;<?= html_escape($q) ?>&rdquo;</h1>
        <p><?= $total_tracked ?> tracked <?= $total_tracked === 1 ? 'business' : 'businesses' ?> and
           <?= count($google) ?> more on Google match that name or address.</p>
      <?php else: ?>
        <h1>Find businesses &mdash; <?= html_escape($market_label) ?></h1>
        <p><?= array_sum($counts) ?> tracked with public online booking<?= $google ? ', ' . count($google) . ' more found on Google' : '' ?>.
           The ring is each shop's <a href="<?= site_url('openchair/how-it-works') ?>">Chair Score</a>: out of every
           100 chair-hours it put online, how many got booked.</p>
      <?php endif; ?>
    </div>
    <div class="spacer"></div>
    <?php if (!$name_mode && $has_key): ?>
      <a class="btn btn-sm" href="<?= site_url('openchair/radar?refresh=1') ?>" title="Look this market up on Google again">&#8635; Refresh from Google</a>
    <?php endif; ?>
    <a class="btn btn-sm btn-primary" href="<?= site_url('openchair/scan') ?>">+ New scan</a>
  </div>

  <?php if ($search && $search['problem']): ?>
    <div class="notice" role="status"><span aria-hidden="true">&#9432;</span><div><?= html_escape($search['problem']) ?>
      Showing name matches meanwhile.</div></div>
  <?php endif; ?>
  <?php if ($places['status'] === 'fetched'): ?>
    <div class="notice occ-ok" role="status"><span aria-hidden="true">&#10003;</span>
      <div>Looked this market up on Google: <b><?= (int) $places['added'] ?> new</b> <?= (int) $places['added'] === 1 ? 'business' : 'businesses' ?>.
        Shops whose website is a booking page we read (Booksy, Square) join the nightly read automatically.</div></div>
  <?php elseif ($places['status'] === 'error'): ?>
    <div class="notice" role="alert"><span aria-hidden="true">&#9888;</span>
      <div><b>Google lookup failed:</b> <?= html_escape($places['error']) ?>
        &mdash; check the key under <a href="<?= site_url('settings') ?>">Settings &rarr; Google Places</a>.</div></div>
  <?php elseif (!$has_key && !$name_mode): ?>
    <div class="notice" role="status"><span aria-hidden="true">&#9432;</span>
      <div>Only shops OpenChair already tracks are listed. Add a key under
        <a href="<?= site_url('settings') ?>">Settings &rarr; Google Places</a> to also list every shop Google knows in this market.</div></div>
  <?php endif; ?>

  <?php if (!$name_mode): ?>
  <div class="occ-tools">
    <a class="chip" href="<?= $qs(['bucket' => null]) ?>" aria-pressed="<?= $f_bucket ? 'false' : 'true' ?>">All <span class="cnt"><?= array_sum($counts) ?></span></a>
    <a class="chip hot"  href="<?= $qs(['bucket' => 'hungry'])  ?>" aria-pressed="<?= $f_bucket === 'hungry'  ? 'true' : 'false' ?>">&#9650; Hungry <span class="cnt"><?= (int) $counts['hungry'] ?></span></a>
    <a class="chip mid"  href="<?= $qs(['bucket' => 'neutral']) ?>" aria-pressed="<?= $f_bucket === 'neutral' ? 'true' : 'false' ?>">&#9632; Steady <span class="cnt"><?= (int) $counts['neutral'] ?></span></a>
    <a class="chip cool" href="<?= $qs(['bucket' => 'slammed']) ?>" aria-pressed="<?= $f_bucket === 'slammed' ? 'true' : 'false' ?>">&#9679; Slammed <span class="cnt"><?= (int) $counts['slammed'] ?></span></a>
    <span class="occ-sep" aria-hidden="true"></span>
    <span class="sub">Sort</span>
    <?php foreach ([['vacancy','Lowest Chair Score'],['revenue','Most unsold'],['next','Longest wait'],['name','Name']] as $s): ?>
      <a class="chip" href="<?= $qs(['sort' => $s[0]]) ?>" aria-pressed="<?= $f_sort === $s[0] ? 'true' : 'false' ?>"><?= html_escape($s[1]) ?></a>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <h2 class="occ-h">Tracked by OpenChair <span class="cnt"><?= $total_tracked ?></span></h2>
  <?php if (!$businesses): ?>
    <div class="occ-empty">
      <b><?= $f_bucket ? 'Nothing matches that filter.' : 'No tracked shops here yet.' ?></b>
      <span><?= $f_bucket ? 'Try another band.' : 'Paste their booking links on New scan, or let the Google list below feed discovery.' ?></span>
    </div>
  <?php else: ?>
    <div class="occ-grid">
      <?php foreach ($businesses as $b):
        $band  = oc_chair_band_for($b);
        $cls   = oc_chair_band_class($band);
        $score = oc_chair_score_or_gap($b->latest_vacancy_bp, $b->latest_confidence);
        $low   = oc_below_floor($b->latest_confidence);
        $sn    = $low ? ['—', 'not enough data yet'] : oc_second_number($b);
        $is_watched = !empty($watched_map[(int) $b->id]);
        $place = trim((string) ($b->address_line ?: trim(($b->city ?: '') . ', ' . ($b->region_code ?: ''), ', ')));
      ?>
        <article class="occ-card is-<?= $cls ?>">
          <div class="occ-top">
            <span class="occ-mono" aria-hidden="true"><?= html_escape($initials($b->name)) ?></span>
            <span class="occ-id">
              <a class="occ-name" href="<?= site_url('openchair/business/' . (int) $b->id) ?>"><?= html_escape($b->name) ?></a>
              <span class="occ-meta"><?= html_escape($b->platform_name ?: 'Booking platform') ?><?= $place !== '' ? ' · ' . html_escape($place) : '' ?></span>
            </span>
            <?= $ring($score, $cls, 'Chair Score ' . $score . ($band ? ', ' . oc_chair_band_label($band) : ', not enough data')) ?>
          </div>

          <div class="occ-fig">
            <span class="occ-big"><?= html_escape($sn[0]) ?></span>
            <span class="occ-small"><?= html_escape($sn[1]) ?></span>
          </div>

          <div class="occ-lines">
            <span class="occ-line"><?= $ico['cal'] ?><?= html_escape(oc_slots_phrase($b->latest_open_slots)) ?> of <?= number_format((int) $b->latest_bookable_slots) ?></span>
            <span class="occ-line"><span class="conf"><?= oc_confidence_bars($b->latest_confidence) ?></span><?= html_escape(oc_confidence_band($b->latest_confidence)) ?> confidence<?= $b->last_good_scan_at ? ' · read ' . html_escape(oc_data_age($b->last_good_scan_at)) : ' · not read yet' ?></span>
            <?php if ($b->phone): ?><span class="occ-line"><?= $ico['phone'] ?><?= html_escape($b->phone) ?></span><?php endif; ?>
          </div>

          <div class="occ-foot">
            <span class="bucket <?= $cls ?>"><?= oc_chair_glyph($band) ?> <?= html_escape($band ? oc_chair_band_label($band) : 'No score yet') ?></span>
            <form method="post" action="<?= site_url('openchair/' . ($is_watched ? 'unwatch' : 'watch')) ?>" style="margin:0">
              <?= oc_csrf() ?><input type="hidden" name="business_id" value="<?= (int) $b->id ?>">
              <input type="hidden" name="back" value="<?= html_escape($back) ?>">
              <button class="btn btn-sm<?= $is_watched ? '' : ' btn-primary' ?>" type="submit"
                      title="<?= $is_watched ? 'Stop watching' : 'Watch this business' ?>"><?= $is_watched ? '&#10003; Watching' : '+ Watch' ?></button>
            </form>
            <a class="occ-open" href="<?= site_url('openchair/business/' . (int) $b->id) ?>">Open &rarr;</a>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <?php if ($google || $has_key): ?>
    <h2 class="occ-h">On Google, not tracked yet <span class="cnt"><?= count($google) ?></span>
      <span class="sub">Real shops with no Chair Score &mdash; we have not read a booking page for them.</span></h2>
    <?php if (!$google): ?>
      <div class="occ-empty"><b>Nothing new on Google here.</b><span>Every shop Google returned is already tracked above, or the lookup found none.</span></div>
    <?php else: ?>
      <div class="occ-grid">
        <?php foreach ($google as $c):
          if ($c->status === 'verified' && $c->detected_platform_key) {
              $state = ['mid', 'Books on ' . ucwords(str_replace('_', ' ', $c->detected_platform_key)) . ' · no reader yet'];
          } elseif ($c->status === 'not_bookable') {
              $state = ['low', 'No online booking found'];
          } elseif ($c->status === 'rejected') {
              $state = ['low', 'No website to check'];
          } else {
              $state = ['low', 'Checking for online booking…'];
          }
        ?>
          <article class="occ-card is-google">
            <div class="occ-top">
              <span class="occ-mono" aria-hidden="true"><?= html_escape($initials($c->name)) ?></span>
              <span class="occ-id">
                <?php if ($c->maps_url): ?>
                  <a class="occ-name" href="<?= html_escape($c->maps_url) ?>" target="_blank" rel="noopener noreferrer"><?= html_escape($c->name ?: 'Unnamed place') ?></a>
                <?php else: ?>
                  <span class="occ-name"><?= html_escape($c->name ?: 'Unnamed place') ?></span>
                <?php endif; ?>
                <span class="occ-meta">Google<?= $c->primary_type ? ' · ' . html_escape(str_replace('_', ' ', $c->primary_type)) : '' ?></span>
              </span>
              <?php if ($c->rating !== null): ?>
                <span class="occ-rating" title="Google rating"><?= $ico['star'] ?><b><?= number_format((float) $c->rating, 1) ?></b>
                  <small><?= number_format((int) $c->review_count) ?> reviews</small></span>
              <?php endif; ?>
            </div>
            <div class="occ-lines">
              <span class="occ-line<?= $c->address_line ? '' : ' is-empty' ?>"><?= $ico['pin'] ?><?= html_escape($c->address_line ?: 'No address listed') ?></span>
              <span class="occ-line<?= $c->phone ? '' : ' is-empty' ?>"><?= $ico['phone'] ?><?= html_escape($c->phone ?: 'No phone listed') ?></span>
              <?php if ($c->website): ?>
                <a class="occ-line" href="<?= html_escape($c->website) ?>" target="_blank" rel="noopener noreferrer"><?= $ico['web'] ?><?= html_escape($host($c->website)) ?></a>
              <?php else: ?>
                <span class="occ-line is-empty"><?= $ico['web'] ?>No website listed</span>
              <?php endif; ?>
            </div>
            <div class="occ-foot">
              <span class="bucket <?= $state[0] ?>"><?= html_escape($state[1]) ?></span>
              <?php if ($c->maps_url): ?>
                <a class="occ-open" href="<?= html_escape($c->maps_url) ?>" target="_blank" rel="noopener noreferrer">Maps &nearr;</a>
              <?php endif; ?>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  <?php endif; ?>

</div>
