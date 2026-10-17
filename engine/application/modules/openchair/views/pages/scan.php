<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * S2 — New scan (docs/70-radar-build.md R1).
 *
 * A niche, a city, and the public booking links to read. Submitting fetches
 * nothing: the links become global discovery candidates, the service recognises
 * each platform from the URL's shape, and the business joins the nightly read.
 * 40-ux-spec.md P2 still holds — no fake progress bar; the status page shows
 * each link's real state.
 *
 * Vars: $q, $niches, $recent[], $errors[], $old[]
 */
$old_city  = isset($old['city']) ? $old['city'] : '';
$old_niche = isset($old['niche']) ? $old['niche'] : 'barbershop';
$old_urls  = isset($old['urls']) ? $old['urls'] : '';
if ($old_city === '' && $q !== '' && strpos($q, ',') !== false) {
    // "barbershop, Tampa" from a search box → niche guess + city.
    list($n0, $c0) = array_map('trim', explode(',', $q, 2));
    $guess = strtolower(str_replace(' ', '_', $n0));
    if (isset($niches[$guess])) { $old_niche = $guess; }
    $old_city = $c0;
}
?>
<div class="page">
  <div class="pagehead">
    <div>
      <h1>New scan</h1>
      <p>Pick a niche and a city, then paste the public &ldquo;Book now&rdquo; links of the shops you want to
         read. Booksy and Square Appointments are read today; other platforms are recognised and listed as
         &ldquo;not read yet&rdquo;.</p>
    </div>
  </div>

  <?php if ($errors): ?>
    <div class="notice" role="alert">
      <span aria-hidden="true">&#9888;</span>
      <div><b>Fix these and send again.</b>
        <ul style="margin:6px 0 0;padding-left:18px">
          <?php foreach ($errors as $e): ?><li><?= html_escape($e) ?></li><?php endforeach; ?>
        </ul></div>
    </div>
  <?php endif; ?>

  <div class="split">
    <div class="panel">
      <div class="panel-head"><h2>What to read</h2></div>
      <form method="post" action="<?= site_url('openchair/scan/submit') ?>" style="padding:16px;display:grid;gap:14px">
        <?= oc_csrf() ?>
        <label style="display:grid;gap:6px">
          <span class="stat-label">Niche</span>
          <select class="input" name="niche" required>
            <?php foreach ($niches as $k => $label): ?>
              <option value="<?= html_escape($k) ?>"<?= $k === $old_niche ? ' selected' : '' ?>><?= html_escape($label) ?></option>
            <?php endforeach; ?>
          </select>
        </label>
        <label style="display:grid;gap:6px">
          <span class="stat-label">City</span>
          <input class="input" type="text" name="city" value="<?= html_escape($old_city) ?>" required
                 placeholder="Tampa, FL" autocomplete="off" maxlength="80" pattern="[A-Za-z][A-Za-z .'\-]{1,60},\s*[A-Za-z]{2}">
        </label>
        <label style="display:grid;gap:6px">
          <span class="stat-label">Public booking links &middot; one per line, up to 50</span>
          <textarea class="input" name="urls" rows="9" required spellcheck="false"
                    placeholder="https://booksy.com/en-us/…&#10;https://book.squareup.com/appointments/…"
                    style="font-family:'Space Mono',monospace;font-size:12.5px"><?= html_escape($old_urls) ?></textarea>
        </label>
        <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap">
          <button class="btn btn-primary" type="submit">Start scan</button>
          <span class="stat-note" style="margin:0">Read once a night, read-only. OpenChair never books, holds or
            contacts anyone.</span>
        </div>
      </form>
    </div>

    <div class="panel">
      <div class="panel-head"><h2>Your recent scans</h2></div>
      <?php if (!$recent): ?>
        <div class="empty" style="padding:30px 20px;text-align:center;color:var(--tx3)">
          No scans yet. The sample Tampa market is already loaded &mdash;
          <a href="<?= site_url('openchair/radar') ?>">open it</a> while your first scan runs.</div>
      <?php else: ?>
        <div class="list">
          <?php foreach ($recent as $r): ?>
            <a class="row" href="<?= site_url('openchair/scan/' . (int) $r->id) ?>" style="grid-template-columns:1fr auto">
              <span>
                <span class="row-name"><?= html_escape($r->city_label) ?> &middot; <?= html_escape(isset($niches[$r->niche_key]) ? $niches[$r->niche_key] : $r->niche_key) ?></span>
                <span class="row-meta"><?= (int) $r->items ?> link<?= (int) $r->items === 1 ? '' : 's' ?>
                  &middot; <?= (int) $r->verified ?> recognised
                  <?php if ((int) $r->pending): ?>&middot; <?= (int) $r->pending ?> waiting<?php endif; ?>
                  &middot; <?= html_escape(oc_data_age($r->created_at)) ?></span>
              </span>
              <span class="row-money">&rarr;</span>
            </a>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>
