<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * S10 — Adapters. Internal ops view (FR-9.1). Admin only.
 *
 * Real health — success rate, block rate, circuit state, canary status — arrives
 * with the ops console in Phase 8. This is the rollout ledger until then.
 *
 * Vars: $platforms[]
 */
?>
<div class="page">
  <div class="pagehead">
    <div>
      <h1>Adapters</h1>
      <p>Per-platform rollout state. An adapter must survive <b>shadow</b> &mdash; scanned, stored,
         published to nobody &mdash; before a buyer ever sees a number it produced.</p>
    </div>
  </div>

  <div class="notice">
    <span aria-hidden="true">&#9432;</span>
    <div><b>No adapter has run yet.</b> Everything below sits in <code>research</code> until the spike
      returns. Success rate, block rate, circuit state and canary status land with the ops console in
      Phase 8.</div>
  </div>

  <div class="panel">
    <div class="tblwrap">
      <table class="data">
        <thead><tr><th>Platform</th><th>Key</th><th>Vertical</th><th>Rollout</th><th>Enabled</th><th>Tier</th></tr></thead>
        <tbody>
        <?php foreach ($platforms as $p):
          $cls = $p->rollout === 'live' ? 'cool' : ($p->rollout === 'frozen' ? 'hot' : 'mid'); ?>
          <tr>
            <td><b><?= html_escape($p->name) ?></b></td>
            <td class="mono"><?= html_escape($p->platform_key) ?></td>
            <td><?= html_escape(str_replace('_', ' ', $p->vertical)) ?></td>
            <td><span class="bucket <?= $cls ?>"><?= html_escape($p->rollout) ?></span></td>
            <td class="mono"><?= $p->enabled ? 'yes' : 'no' ?></td>
            <td class="mono"><?= html_escape(strtoupper($p->tier)) ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
