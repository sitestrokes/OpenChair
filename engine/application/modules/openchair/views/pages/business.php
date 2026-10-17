<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * S5 — Business Detail (docs/60-signature-visuals.md, PDF p.8).
 *
 * The screen that makes the signature cards. In order, it must:
 *   1. Say where this business stands in one number — the Chair Score — with
 *      its band, its margin and its data age (rules 3, 6).
 *   2. Show the Chair Week REDRAWN from our own data, chair by chair. Never a
 *      capture of the platform's page (05-prd.md §4.2).
 *   3. Carry "What we could not see" as a first-class panel, not fine print
 *      (rule 5, FR-4.5).
 *   4. Turn all of it into a picture the consultant sends — a share link, a
 *      phone-size image or a one-page PDF. The consultant sends it; there is no
 *      Send button and nothing here contacts the business (rule 7).
 *
 * Every figure comes from $summary (Oc_visuals_lib::summary), the same payload
 * the cards and share links are built from.
 *
 * Vars: $b, $prospect, $watched, $summary, $shares, $is_demo, $team, $assignee,
 *       $shared_link, $share_error
 */
$s      = $summary;
$gap    = !empty($s['gap']);
$band   = $s['score']['band'];
$cls    = $s['score']['class'];
$stale  = !empty($s['checked']['stale']);
$street = $s['street'];
$id     = (int) $b->id;
$here   = 'openchair/business/' . $id;

// ── Pipeline stepper ─────────────────────────────────────────────────────────
$stage = $prospect ? $prospect->stage : null;
$order = ['watching' => 2, 'contacted' => 2, 'pitched' => 3, 'meeting' => 4, 'won' => 5];
// Not watched yet: Found and Scored are done, Watching is the next step.
$at    = ($watched && isset($order[$stage])) ? $order[$stage] : 2;
$steps = ['Found', 'Scored', 'Watching', 'Pitched', 'Meeting', 'Won'];
$next  = [
    'watching'  => 'send the pitch',
    'contacted' => 'send the pitch',
    'pitched'   => 'book the meeting',
    'meeting'   => 'close it',
    'won'       => 'send the 8-week report',
    'lost'      => 'look again in a month',
];
$next_action = $watched ? (isset($next[$stage]) ? $next[$stage] : 'send the pitch') : 'watch it to get it in your 8am list';
$due = ($prospect && !empty($prospect->next_due_on)) ? date('D, M j', strtotime($prospect->next_due_on)) : null;

// ── Cards on offer ───────────────────────────────────────────────────────────
// "Same Tuesday" is named for this shop's worst day, whichever day that is.
$worst = null;
foreach ($s['grid']['days'] as $d) {
    if ($d['shut'] || $d['bookable'] === 0) { continue; }
    if ($worst === null || $d['empty'] / $d['bookable'] > $worst['empty'] / $worst['bookable']) { $worst = $d; }
}
$kinds = [
    'week'    => 'Chair Week',
    'map'     => 'Chair Map',
    'receipt' => 'Receipt',
    'tuesday' => 'Same ' . ($worst ? $worst['name'] : 'Tuesday'),
];
if ($s['trend']['weeks'] >= 2) { $kinds['progress'] = '8-week report'; }
$card_url = function ($kind, $q = '') use ($id) {
    return site_url('openchair/card/' . $id . '/' . $kind) . ($q !== '' ? '?' . $q : '');
};

$ordinal = function ($n) {
    $n = (int) $n;
    $suf = (in_array($n % 100, [11, 12, 13], true)) ? 'th' : (['th', 'st', 'nd', 'rd'][$n % 10] ?? 'th');
    return $n . $suf;
};
$niche = ucfirst(str_replace('_', ' ', (string) $b->niche_key));
$css_v = '?v=' . @filemtime(FCPATH . 'assets/css/visuals/business.css');
?>
<link rel="stylesheet" href="<?= base_url('assets/css/oc-visuals.css') . $css_v ?>">
<link rel="stylesheet" href="<?= base_url('assets/css/visuals/business.css') . $css_v ?>">

<div class="page ocb">

  <nav class="ocb-crumb" aria-label="Breadcrumb">
    <?php if ($watched): ?>
      <a href="<?= site_url('openchair/prospects') ?>">My pipeline</a>
    <?php else: ?>
      <a href="<?= site_url('openchair/radar') ?>">Find businesses</a>
    <?php endif; ?>
    <span aria-hidden="true">&rsaquo;</span> <span aria-current="page"><?= html_escape($b->name) ?></span>
  </nav>

  <header class="ocb-head">
    <div class="ocb-head-main">
      <h1><?= html_escape($b->name) ?></h1>
      <p class="ocb-meta"><?= html_escape($niche) ?> &middot; <?= html_escape($s['business']['area']) ?>
        &middot; <?= (int) $s['business']['chairs'] ?> chairs<?= $s['business']['online_chairs'] !== $s['business']['chairs'] ? ' (' . (int) $s['business']['online_chairs'] . ' online)' : '' ?>
        &middot; <?= html_escape($b->platform_name ?: 'unknown platform') ?></p>
      <div class="ocb-chips">
        <span class="ocb-chip ocb-chip--<?= $cls ?>"><?= html_escape($s['score']['label']) ?><?= $band ? ' &middot; ' . html_escape($s['score']['need']) : '' ?></span>
        <span class="ocb-chip ocb-chip--line">Checked <?= html_escape($s['checked']['age']) ?></span>
        <?php if ($watched): ?><span class="ocb-chip ocb-chip--watch"><span class="ocb-half" aria-hidden="true"></span> Watching</span><?php endif; ?>
        <?php if ($is_demo): ?><span class="ocb-chip ocb-chip--line">Sample data</span><?php endif; ?>
      </div>
    </div>
    <div class="ocb-head-actions">
      <?php if ($watched): ?>
        <span class="ocb-assigned">Assigned to <b><?= html_escape($assignee) ?></b></span>
      <?php else: ?>
        <form method="post" action="<?= site_url('openchair/watch') ?>">
          <?= oc_csrf() ?>
          <input type="hidden" name="business_id" value="<?= $id ?>">
          <input type="hidden" name="back" value="<?= html_escape($here) ?>">
          <button type="submit" class="ocb-btn">Watch</button>
        </form>
      <?php endif; ?>
      <a class="ocb-btn" href="<?= site_url('openchair/pitch/' . $id) ?>">Make pitch</a>
      <a class="ocb-btn ocb-btn--ink" href="#share">Share card</a>
    </div>
  </header>

  <?php if ($stale): ?>
    <div class="stale"><span aria-hidden="true">&#9888;</span>
      <div><b>Last good read was <?= html_escape(oc_data_age($b->last_good_scan_at)) ?>.</b>
        Showing the last observation we trust rather than a fresh guess.</div></div>
  <?php endif; ?>

  <section class="ocb-card ocb-steps" aria-label="Pipeline">
    <ol class="ocb-stepper">
      <?php foreach ($steps as $i => $label):
        if ($i === 1 && $gap) { $state = 'todo'; }
        elseif ($i < $at || ($i === 5 && $stage === 'won')) { $state = 'done'; }
        elseif ($i === $at && $watched) { $state = 'now'; }
        else { $state = 'todo'; }
        $glyph = ['done' => '&#10003;', 'now' => '<span class="ocb-half"></span>', 'todo' => '&#9675;'][$state]; ?>
        <li class="is-<?= $state ?>"<?= $state === 'now' ? ' aria-current="step"' : '' ?>><span aria-hidden="true"><?= $glyph ?></span> <?= $label ?></li>
      <?php endforeach; ?>
      <?php if ($stage === 'lost'): ?><li class="is-lost"><span aria-hidden="true">&#10005;</span> Lost</li><?php endif; ?>
    </ol>
    <p class="ocb-next"><b>Next:</b> <?= html_escape($next_action) ?><?= $due ? ' &middot; due ' . html_escape($due) : '' ?><?= $watched ? ' &middot; ' . html_escape($assignee) : '' ?></p>

    <?php if ($watched): ?>
    <form class="ocb-assign" method="post" action="<?= site_url('openchair/assign') ?>">
      <?= oc_csrf() ?>
      <input type="hidden" name="business_id" value="<?= $id ?>">
      <input type="hidden" name="back" value="<?= html_escape($here) ?>">
      <label>Owner
        <select name="assigned_user_id">
          <?php foreach ($team as $uid => $label): ?>
            <option value="<?= (int) $uid ?>"<?= $assignee === $label ? ' selected' : '' ?>><?= html_escape($label) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <label>Due <input type="date" name="next_due_on" value="<?= html_escape($prospect->next_due_on ?? '') ?>"></label>
      <button type="submit" class="ocb-btn ocb-btn--sm">Save</button>
    </form>
    <?php endif; ?>
  </section>

  <div class="ocb-cols">
    <div class="ocb-main">

      <div class="ocb-tiles">
        <div class="ocb-card ocb-tile">
          <div class="ocb-k">Chair score</div>
          <?php if ($gap): ?>
            <div class="ocb-v ocb-v--gap">Not enough data yet</div>
            <div class="ocb-n">needs 5+ days checked and 70% of hours seen</div>
          <?php else: ?>
            <div class="ocb-v ocb-v--<?= $cls ?>"><?= (int) $s['score']['value'] ?> <small>/100</small></div>
            <div class="ocb-n">&plusmn;<?= (int) $s['score']['margin'] ?> &middot; <?= html_escape($s['score']['confidence']) ?> confidence</div>
          <?php endif; ?>
        </div>

        <div class="ocb-card ocb-tile">
          <?php if (!$gap && $band === 'slammed'): ?>
            <div class="ocb-k">Wait for a new client</div>
            <div class="ocb-v"><?= $s['wait']['days'] === null ? '&mdash;' : (int) $s['wait']['days'] . ' <small>days</small>' ?></div>
            <div class="ocb-n">first opening online<?= $s['wait']['area_days'] !== null ? ' &middot; nearby: ' . (int) $s['wait']['area_days'] . ' days' : '' ?></div>
          <?php else: ?>
            <div class="ocb-k">Unsold this week</div>
            <?php if ($gap || empty($s['money'])): ?>
              <div class="ocb-v ocb-v--gap">&mdash;</div>
              <div class="ocb-n">no figure below the confidence floor</div>
            <?php else: ?>
              <div class="ocb-v ocb-v--money"><?= html_escape(oc_range_tight($s['money']['lo'], $s['money']['hi'])) ?></div>
              <div class="ocb-n"><?= (int) $s['totals']['empty'] ?> empty chair-hours</div>
            <?php endif; ?>
          <?php endif; ?>
        </div>

        <div class="ocb-card ocb-tile">
          <div class="ocb-k">On the street</div>
          <?php if ($street['rank'] === null): ?>
            <div class="ocb-v ocb-v--gap">&mdash;</div>
          <?php else: ?>
            <div class="ocb-v"><?= html_escape($ordinal($street['rank'])) ?> <small>of <?= (int) $street['ranked'] ?></small></div>
          <?php endif; ?>
          <div class="ocb-n">area average <?= $street['average'] === null ? '&mdash;' : (int) $street['average'] ?> &middot; <?= html_escape($street['radius_label']) ?></div>
        </div>

        <div class="ocb-card ocb-tile">
          <div class="ocb-k">Trend</div>
          <?php if ($s['trend']['delta'] === null): ?>
            <div class="ocb-v ocb-v--gap">&mdash;</div>
            <div class="ocb-n">first full week; needs 2</div>
          <?php else: $dl = (int) $s['trend']['delta']; ?>
            <div class="ocb-v <?= $dl > 0 ? 'ocb-v--up' : ($dl < 0 ? 'ocb-v--down' : '') ?>"><?= $dl > 0 ? '+' . $dl : ($dl < 0 ? '&minus;' . abs($dl) : '&plusmn;0') ?></div>
            <div class="ocb-n">Chair Score vs last week &middot; <?= (int) $s['trend']['weeks'] ?> weeks seen</div>
          <?php endif; ?>
        </div>
      </div>

      <section class="ocb-card ocb-week" aria-labelledby="ocb-week-h">
        <div class="ocb-card-head">
          <h2 id="ocb-week-h">Chair Week &middot; <?= html_escape($s['week']['range_label']) ?></h2>
          <span class="ocb-legend">
            <span><i class="ocg-t ocg-e"></i> empty</span>
            <span><i class="ocg-t ocg-b"></i> booked</span>
            <span><i class="ocg-t ocg-u"></i> can&rsquo;t see</span>
          </span>
        </div>
        <?php $this->load->view('visuals/_grid', [
            'grid'      => $s['grid'],
            'grid_opts' => ['size' => 'md', 'counts' => 'head', 'skip_shut' => true],
        ]); ?>
        <?php if ($s['source'] === 'derived'): ?>
          <p class="ocb-fine">Grid is illustrative until Radar runs: the number of empty chair-hours matches the
            measured score, the placement is derived. Redrawn by us &mdash; never a screenshot of their booking page.</p>
        <?php endif; ?>
      </section>

      <!-- Rule 5. First-class, always visible, never behind a tooltip. -->
      <section class="ocb-card ocb-disclose" aria-labelledby="ocb-disclose-h">
        <h2 id="ocb-disclose-h">What we could not see</h2>
        <ul>
          <?php foreach ($s['unseen_notes'] as $note): ?>
            <li><?= html_escape($note) ?></li>
          <?php endforeach; ?>
        </ul>
        <p>This is why the unit is <b><?= html_escape(oc_unit()) ?></b> and never &ldquo;empty shop&rdquo;.
           The figure is directionally honest and the owner will recognise it &mdash; but say it as what
           it is, or the first objection ends the conversation.</p>
      </section>
    </div>

    <aside class="ocb-card ocb-share" id="share" aria-labelledby="ocb-share-h">
      <h2 id="ocb-share-h">Share card</h2>

      <?php if ($gap): ?>
        <p class="ocb-note">Not enough data yet to put a number on a card for this business. We need 5+ days
          checked and 70% of its hours seen &mdash; until then there is nothing here we would stand behind
          in their shop.</p>
      <?php else: ?>
      <form method="post" action="<?= site_url('openchair/share') ?>" class="ocb-share-form" data-ocb-share>
        <?= oc_csrf() ?>
        <input type="hidden" name="business_id" value="<?= $id ?>">
        <fieldset>
          <legend>Picture to send</legend>
          <div class="ocb-kinds">
            <?php $first = true; foreach ($kinds as $k => $label): ?>
              <label class="ocb-kind">
                <input type="radio" name="kind" value="<?= $k ?>"<?= $first ? ' checked' : '' ?>>
                <span><?= html_escape($label) ?></span>
              </label>
            <?php $first = false; endforeach; ?>
          </div>
        </fieldset>
        <label class="ocb-check"><input type="checkbox" name="show_logo" value="1" checked> Show my agency logo</label>

        <p class="ocb-note">Other shops are never named on a card. Ranges, data age and
          &ldquo;online-bookable only&rdquo; are always printed.</p>

        <?php if ($share_error === 'gap'): ?>
          <p class="ocb-error" role="alert">Not enough data yet to make a card for this business.</p>
        <?php endif; ?>

        <div class="ocb-made" data-ocb-made<?= $shared_link ? '' : ' hidden' ?>>
          <label for="ocb-link">Your link</label>
          <div class="ocb-linkrow">
            <input id="ocb-link" type="text" readonly value="<?= $shared_link ? html_escape(site_url('c/' . $shared_link->token)) : '' ?>">
            <button type="button" class="ocb-btn ocb-btn--sm" data-ocb-copy>Copy</button>
          </div>
          <small data-ocb-status><?= $shared_link ? 'Link made. Paste it into your own message to the owner.' : '' ?></small>
        </div>

        <button type="submit" class="ocb-btn ocb-btn--ink ocb-btn--block">Copy share link</button>
        <a class="ocb-btn ocb-btn--block" data-ocb-href="size=phone&amp;export=png" href="<?= html_escape($card_url('week', 'size=phone&export=png')) ?>">Download image (phone size)</a>
        <a class="ocb-btn ocb-btn--block" data-ocb-href="print=1" href="<?= html_escape($card_url('week', 'print=1')) ?>" target="_blank" rel="noopener">Download one-pager (PDF)</a>
        <a class="ocb-preview" data-ocb-href="" href="<?= html_escape($card_url('week')) ?>">Preview the card &rarr;</a>
      </form>
      <?php endif; ?>

      <p class="ocb-fine">Links expire in 30 days and preview in iMessage and WhatsApp. You send it;
        OpenChair never contacts the business.</p>

      <div class="ocb-activity">
        <h3>Activity</h3>
        <ul>
          <?php if (!$shares): ?>
            <li>No share links yet.</li>
          <?php endif; ?>
          <?php foreach ($shares as $sh):
            $who = trim((string) $sh->first_name) !== ''
                ? trim($sh->first_name) . (trim((string) $sh->last_name) !== '' ? ' ' . mb_substr(trim($sh->last_name), 0, 1) . '.' : '')
                : 'You';
            $expired = $sh->revoked_at !== null || strtotime($sh->expires_at) < time(); ?>
            <li><?= html_escape($who) ?> made a share link &middot; <?= html_escape(isset($kinds[$sh->kind]) ? $kinds[$sh->kind] : ucfirst($sh->kind)) ?>
                &middot; <?= html_escape(date('M j', strtotime($sh->created_at))) ?><?= $expired ? ' &middot; expired' : '' ?></li>
            <li class="ocb-sub"><?= (int) $sh->open_count > 0
                ? 'Link opened ' . (int) $sh->open_count . ' time' . ((int) $sh->open_count === 1 ? '' : 's') . ' &middot; last ' . html_escape(oc_data_age($sh->last_opened_at))
                : 'Not opened yet' ?></li>
          <?php endforeach; ?>
          <li class="ocb-mono"><?= html_escape($s['score']['method']) ?> &middot; <?= (int) $s['totals']['checks'] ?> checks &middot; <?= (int) $s['totals']['seen_pct'] ?>% of hours seen</li>
        </ul>
      </div>
    </aside>
  </div>
</div>

<script>
// Share panel. Everything works without JavaScript (a normal POST and
// redirect); with it, the link is made in place and copied to the clipboard.
(function () {
  var form = document.querySelector('[data-ocb-share]');
  if (!form) { return; }
  var base = <?= json_encode(site_url('openchair/card/' . $id)) ?>;
  var made = form.querySelector('[data-ocb-made]');
  var input = form.querySelector('#ocb-link');
  var status = form.querySelector('[data-ocb-status]');

  function kind() { var r = form.querySelector('input[name=kind]:checked'); return r ? r.value : 'week'; }
  function logo() { return form.querySelector('input[name=show_logo]').checked; }
  function sync() {
    form.querySelectorAll('[data-ocb-href]').forEach(function (a) {
      var q = a.getAttribute('data-ocb-href');
      if (!logo()) { q = q ? q + '&logo=0' : 'logo=0'; }
      a.href = base + '/' + kind() + (q ? '?' + q : '');
    });
  }
  form.addEventListener('change', sync);
  sync();

  function copy(text) {
    if (navigator.clipboard && window.isSecureContext) { return navigator.clipboard.writeText(text); }
    input.focus(); input.select();
    try { document.execCommand('copy'); return Promise.resolve(); } catch (e) { return Promise.reject(e); }
  }
  form.querySelector('[data-ocb-copy]').addEventListener('click', function () {
    copy(input.value).then(function () { status.textContent = 'Copied. Paste it into your own message to the owner.'; },
                           function () { status.textContent = 'Select the link and copy it.'; });
  });

  form.addEventListener('submit', function (ev) {
    if (!window.fetch) { return; }
    ev.preventDefault();
    var btn = form.querySelector('button[type=submit]');
    btn.disabled = true;
    fetch(form.action, { method: 'POST', body: new FormData(form), credentials: 'same-origin',
                         headers: { 'X-Requested-With': 'XMLHttpRequest' } })
      .then(function (r) { return r.json(); })
      .then(function (j) {
        made.hidden = false;
        if (!j.ok) { input.value = ''; status.textContent = j.error || 'Could not make the link.'; return; }
        input.value = j.url;
        return copy(j.url).then(function () { status.textContent = 'Link made and copied. Paste it into your own message to the owner.'; },
                                function () { status.textContent = 'Link made. Select it and copy.'; });
      })
      .catch(function () { form.submit(); })
      .then(function () { btn.disabled = false; });
  });
})();
</script>
