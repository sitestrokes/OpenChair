<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * S9 — Coverage (40-ux-spec.md §2.2).
 *
 * The screen that says what we CANNOT see. FR-2.3 requires the coverage ratio be
 * shown honestly rather than silently dropping the businesses without online
 * booking — "only businesses with online booking show up" is the brief's risk #3,
 * and it is a feature only if the user is told about it up front.
 *
 * Vars: $platforms[], $funnel, $totals
 */
$found = (int) $funnel['found'];
$ver   = (int) $funnel['verified'];
?>
<div class="page">

  <div class="pagehead">
    <div>
      <h1>Coverage</h1>
      <p>What OpenChair can and cannot see in this market. Read this before you quote a number to
         anyone &mdash; it is the difference between a defensible claim and an argument.</p>
    </div>
  </div>

  <div class="notice">
    <span aria-hidden="true">&#9432;</span>
    <div><b>Only businesses with public online booking appear here.</b>
      That is a filter, not a flaw: a shop already paying for booking software has already decided to
      buy software. But it does mean this is not a list of every barbershop in Tampa.</div>
  </div>

  <div class="cards" style="padding:0 0 18px">
    <div class="mini">
      <h4>Discovered</h4>
      <div class="big"><?= $found ?: '&mdash;' ?></div>
      <div class="stat-note">Businesses found in this niche and city.</div>
    </div>
    <div class="mini">
      <h4>Bookable</h4>
      <div class="big"><?= (int) $totals['businesses'] ?></div>
      <div class="stat-note">Have a public booking page we can read.</div>
    </div>
    <div class="mini">
      <h4>Coverage</h4>
      <div class="big"><?= $found > 0 ? round(100 * $ver / $found) . '%' : '&mdash;' ?></div>
      <div class="stat-note"><?= $found > 0 ? 'Of everything we found.' : 'Discovery has not run yet — Phase 3.' ?></div>
    </div>
    <div class="mini">
      <h4>No score</h4>
      <div class="big"><?= (int) $totals['below_floor'] ?></div>
      <div class="stat-note">Read, but not confidently enough to publish a number.</div>
    </div>
  </div>

  <div class="panel">
    <div class="panel-head">
      <h2>Platforms</h2>
      <span class="sub">an adapter publishes only when its rollout state says <b>live</b></span>
    </div>
    <div class="tblwrap">
      <table class="data">
        <thead><tr><th>Platform</th><th>Vertical</th><th>Businesses</th><th>Scanned</th><th>Rollout</th><th>Tier</th></tr></thead>
        <tbody>
        <?php foreach ($platforms as $p):
          $r   = $p->rollout;
          $cls = $r === 'live' ? 'cool' : ($r === 'frozen' ? 'hot' : 'mid');
        ?>
          <tr>
            <td><b><?= html_escape($p->name) ?></b></td>
            <td><?= html_escape(str_replace('_', ' ', $p->vertical)) ?></td>
            <td class="mono"><?= (int) $p->businesses ?></td>
            <td class="mono"><?= (int) $p->scanned ?></td>
            <td><span class="bucket <?= $cls ?>"><?= html_escape($r) ?></span></td>
            <td class="mono"><?= html_escape(strtoupper($p->tier)) ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <div class="disclose" style="margin-top:16px">
    <h3><span aria-hidden="true">&#9673;</span> Platforms we will not read</h3>
    <ul>
      <li><b>OpenTable and Resy</b> &mdash; refused for v1. Highest legal exposure in the set, attached
          to our weakest confidence class.</li>
      <li><b>Mindbody</b> &mdash; deferred to its paid partner API rather than read from the page.</li>
      <li><b>Zocdoc and clinic listings</b> &mdash; deferred on health-data adjacency.</li>
    </ul>
    <p>These are decisions, not gaps waiting to be filled. Anything needing stealth browsers or
       residential proxy rotation is out of scope permanently &mdash; that ceiling is enforced in the
       database and at the egress broker, not by a setting anyone can change.</p>
  </div>

</div>
