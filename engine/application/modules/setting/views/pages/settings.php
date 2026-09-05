<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * SETTINGS — BYOK keys (saved via AJAX to setting/save-settings),
 * Compliance (read-only). Rendered in the IC dark shell via Ic_render_lib.
 * Vars: $openai_set, $gemini_set, $twilio_set, $eleven_set (bool), $bridge_base,
 *       $dnc_list (rows: id, phone, reason, created_at).
 */
$csrf_name = $this->security->get_csrf_token_name();
$csrf_hash = $this->security->get_csrf_hash();
$dnc_list  = isset($dnc_list) ? $dnc_list : [];
if (!function_exists('ic_dnc_when')) {
    function ic_dnc_when($ts)
    {
        if (empty($ts)) { return ''; }
        $t = strtotime($ts);
        return $t ? date('M j, Y', $t) : '';
    }
}
?>
<?php /* Rendered through the Agentic Affiliates dark shell (ic_shell/layout) by the
   Instantclose controller. The IC dark styles are ID-scoped to #ic-set so they
   style ONLY this panel. The scoped-CSS partial lives in THIS module
   (setting/views/pages/), so include it module-qualified — a bare
   'pages/_ic_settings_scoped' would resolve against the running controller's
   module (instantclose) and 404. */ ?>
<?php $this->load->view('setting/pages/_ic_settings_scoped'); ?>
<style>#ic-set{min-height:100vh;padding:32px 28px;box-sizing:border-box} @media(max-width:700px){#ic-set{padding:20px 14px}}</style>
<div id="ic-set">
<div class="pagehead set-anim">
  <h1>Settings</h1>
  <div class="psub">Your keys and compliance. Keys are stored per account and never shown back to you in full.</div>
</div>

<?php
// Connection overview — a quick read on what's wired up. Each card reflects the
// same $*_set flags the tabs below use.
$_build   = !empty($openai_set) || !empty($gemini_set);   // browser voice + build both need OpenAI OR Gemini
$overview = [
  ['ic' => 'browser', 'label' => 'Browser voice', 'state' => $_build ? 'ok' : 'warn', 'note' => $_build ? 'Ready' : 'Add a key'],
  ['ic' => 'brain',   'label' => 'AI builder',    'state' => $_build ? 'ok' : 'warn', 'note' => $_build ? 'Connected' : 'Add a key'],
  ['ic' => 'phone',   'label' => 'Phone + SMS',   'state' => !empty($twilio_set) ? 'ok' : 'warn', 'note' => !empty($twilio_set) ? 'Twilio live' : 'Add Twilio'],
  ['ic' => 'email',   'label' => 'Email sending',  'state' => !empty($smtp_set) ? 'ok' : 'off', 'note' => !empty($smtp_set) ? 'SMTP set' : 'Optional'],
];
?>
<div class="set-status set-anim d1">
  <?php foreach ($overview as $o): ?>
    <div class="set-stat <?= $o['state'] ?>">
      <div class="ss-ic"><?= ic_svg($o['ic'], 19) ?></div>
      <div class="ss-tx"><b><?= html_escape($o['label']) ?></b><small><?= html_escape($o['note']) ?></small></div>
    </div>
  <?php endforeach; ?>
</div>

<?php $ic_ok = $this->session->flashdata('ic_success'); $ic_err = $this->session->flashdata('ic_error'); ?>
<?php if (!empty($ic_ok)): ?><div class="card pad" style="margin-bottom:14px;border-color:var(--green)"><span class="chip green">Saved</span> &nbsp;<?= html_escape($ic_ok) ?></div><?php endif; ?>
<?php if (!empty($ic_err)): ?><div class="card pad" style="margin-bottom:14px;border-color:var(--coral)"><span class="chip coral">Error</span> &nbsp;<?= html_escape($ic_err) ?></div><?php endif; ?>

<div class="tabs set-anim d2">
  <a class="on" data-t="keys" onclick="icTab(this,'keys')"><?= ic_svg('key', 15) ?> AI &amp; Phone Keys</a>
  <a data-t="integrations" onclick="icTab(this,'integrations')"><?= ic_svg('link', 15) ?> Integrations</a>
  <a data-t="email" onclick="icTab(this,'email')"><?= ic_svg('email', 15) ?> Email</a>
  <a data-t="compliance" onclick="icTab(this,'compliance')"><?= ic_svg('shield', 15) ?> Compliance</a>
</div>

<!-- ───────── KEYS (BYOK) ───────── -->
<div class="card pad stab on" data-tab="keys">
  <div class="cardhead"><div class="ico"><?= ic_svg('key', 17) ?></div><h3>Keys &amp; connections</h3></div>
  <div style="font-size:12.5px;color:var(--mute);margin-bottom:14px;max-width:660px;line-height:1.6">
    <b style="color:var(--txt)">Bring your own keys.</b> Agentic Affiliates runs on your own AI and telephony
    accounts, so you stay in full control of your usage and billing. Add your OpenAI or Gemini key to
    power the live in-page voice and build Bridges, and your Twilio key to unlock the phone + SMS lanes.
    Keys are stored encrypted at rest.
  </div>

  <!-- At-a-glance: what each key unlocks (audit: one clear matrix vs contradictory copy). -->
  <div style="overflow-x:auto;margin-bottom:16px">
    <table style="width:100%;border-collapse:collapse;font-size:12.5px;min-width:420px">
      <thead><tr style="text-align:left">
        <th class="lbl" style="padding:7px 10px;border-bottom:1px solid var(--line)">What you want to do</th>
        <th class="lbl" style="padding:7px 10px;border-bottom:1px solid var(--line)">What you need</th>
      </tr></thead>
      <tbody>
        <tr style="border-bottom:1px solid var(--line)"><td style="padding:9px 10px">Live <b>browser voice</b> when a lead opts in</td><td style="padding:9px 10px"><span class="chip amber">OpenAI <i>or</i> Gemini key</span></td></tr>
        <tr style="border-bottom:1px solid var(--line)"><td style="padding:9px 10px"><b>Build a Bridge</b> (AI reads the offer + writes the page)</td><td style="padding:9px 10px"><span class="chip amber">OpenAI <i>or</i> Gemini key</span></td></tr>
        <tr style="border-bottom:1px solid var(--line)"><td style="padding:9px 10px"><b>Phone + SMS</b> follow-up lanes</td><td style="padding:9px 10px"><span class="chip amber">Twilio key</span></td></tr>
        <tr><td style="padding:9px 10px"><b>Premium voice</b> on AI calls (optional)</td><td style="padding:9px 10px"><span class="chip mute">ElevenLabs key</span></td></tr>
      </tbody>
    </table>
  </div>

  <?php
  $rows = [
    ['openai',     'OpenAI',     'brain', 'Powers your live in-page browser voice and builds your Bridges (reads the offer + writes the page/voice), on your own OpenAI account. Add OpenAI OR Gemini below — at least one is required.', !empty($openai_set), 'sk-...',             'build', ($openai_mask ?? '')],
    ['gemini',     'Google Gemini', 'sparkle', 'Alternative AI brain for the browser voice + building Bridges. If OpenAI is unset or out of quota, the builder falls back to your Gemini key. Add OpenAI OR Gemini — at least one is required.', !empty($gemini_set), 'AIza...',          'build', ($gemini_mask ?? '')],
    ['twilio',     'Twilio',     'phone', 'Needed for the phone + SMS lanes — you are the caller/sender of record on your own number.',                       !empty($twilio_set), 'Auth token',         'required', ($twilio_mask ?? '')],
    ['elevenlabs', 'ElevenLabs', 'mic', 'Optional. A premium voice for the AI phone calls.',                                                               !empty($eleven_set), 'ElevenLabs API key', 'optional', ($eleven_mask ?? '')],
  ];
  foreach ($rows as $r):
    list($type, $label, $icon, $desc, $is_set, $ph, $kind, $mask) = $r;
    if ($is_set)               { $chip_cls = 'green'; $chip_txt = 'Connected'; }
    elseif ($kind === 'build')    { $chip_cls = 'amber'; $chip_txt = 'Needed for voice + build'; }
    elseif ($kind === 'required') { $chip_cls = 'amber'; $chip_txt = 'Needed for phone/SMS'; }
    else                       { $chip_cls = 'mute';  $chip_txt = 'Optional'; }
  ?>
    <div class="conn<?= $is_set ? ' is-set' : '' ?>">
      <div class="c-ic"><?= ic_svg($icon, 22) ?></div>
      <div class="c-body">
        <div class="c-title"><b><?= html_escape($label) ?></b>
          <span class="chip <?= $chip_cls ?>" id="chip_<?= $type ?>"><?= html_escape($chip_txt) ?></span>
        </div>
        <div class="c-desc"><?= html_escape($desc) ?></div>
        <div style="display:flex;gap:10px;align-items:center;margin-top:12px;flex-wrap:wrap">
          <?php if ($type === 'twilio'): $sid_mask = $twilio_sid_mask ?? ''; ?>
            <input class="input" type="text" autocomplete="off" id="sid_twilio" aria-label="Twilio Account SID"
                   placeholder="<?= html_escape($is_set ? (($sid_mask !== '' ? $sid_mask : '••••••••') . ' — paste to replace') : 'Account SID (AC…)') ?>"
                   style="max-width:340px;flex:1;min-width:220px">
          <?php endif; ?>
          <input class="input" type="password" autocomplete="off" id="key_<?= $type ?>" aria-label="<?= html_escape($label) ?> API key"
                 placeholder="<?= html_escape($is_set ? (($mask !== '' ? $mask : '••••••••') . ' — paste to replace') : $ph) ?>"
                 style="max-width:340px;flex:1;min-width:220px">
          <button class="btn pri sm" onclick="icSaveKey('<?= $type ?>')">Save</button>
          <button class="btn gho sm" onclick="icTestKey('<?= $type ?>')" id="btnTest_<?= $type ?>">Test</button>
        </div>
        <?php if ($is_set && $mask !== ''): ?>
          <div style="font-size:12px;margin-top:8px;color:var(--mute)">
            Saved key:
            <?php if ($type === 'twilio'): ?>
              SID <code style="color:var(--txt2)"><?= html_escape($sid_mask !== '' ? $sid_mask : '—') ?></code>
              &nbsp;·&nbsp; Token <code style="color:var(--txt2)"><?= html_escape($mask) ?></code>
            <?php else: ?>
              <code style="color:var(--txt2)"><?= html_escape($mask) ?></code>
            <?php endif; ?>
          </div>
        <?php endif; ?>
        <div id="testResult_<?= $type ?>" style="font-size:12px;margin-top:7px"></div>
      </div>
    </div>
  <?php endforeach; ?>
</div>

<!-- ───────── INTEGRATIONS (ESP autoresponders) — SET-2 ───────── -->
<div class="card pad stab" data-tab="integrations">
  <div class="cardhead"><div class="ico"><?= ic_svg('link', 17) ?></div><h3>Email service providers</h3></div>
  <div style="font-size:12.5px;color:var(--mute);margin-bottom:14px;max-width:680px;line-height:1.6">
    Connect your autoresponder so every lead a Bridge catches is added to your list automatically.
    Paste your API key, then pick the list or tag new leads should land on. Your keys are encrypted at rest.
  </div>
  <?php
  $esp_state = isset($esp_state) && is_array($esp_state) ? $esp_state : [];
  $esp_cards = [
    'convertkit'     => ['label' => 'ConvertKit',     'ph' => 'ConvertKit API key',          'url' => false],
    'mailchimp'      => ['label' => 'Mailchimp',      'ph' => 'Mailchimp API key (…-us21)',  'url' => false],
    'getresponse'    => ['label' => 'GetResponse',    'ph' => 'GetResponse API key',         'url' => false],
    'activecampaign' => ['label' => 'ActiveCampaign', 'ph' => 'ActiveCampaign API key',      'url' => true],
  ];
  foreach ($esp_cards as $type => $c):
    $conn = !empty($esp_state[$type]['connected']);
  ?>
    <div class="conn<?= $conn ? ' is-set' : '' ?>" data-esp="<?= $type ?>">
      <div class="c-ic"><?= ic_svg('email', 22) ?></div>
      <div class="c-body">
        <div class="c-title"><b><?= html_escape($c['label']) ?></b>
          <span class="chip <?= $conn ? 'green' : 'mute' ?>" id="espchip_<?= $type ?>"><?= $conn ? 'Connected' : 'Not connected' ?></span>
        </div>
        <div style="display:flex;gap:10px;align-items:center;margin-top:12px;flex-wrap:wrap">
          <?php if ($c['url']): ?>
            <input class="input" type="text" autocomplete="off" id="espurl_<?= $type ?>" aria-label="<?= html_escape($c['label']) ?> account API URL"
                   placeholder="<?= $conn ? '•••• (saved)' : 'Account API URL (https://you.api-us1.com)' ?>"
                   style="max-width:300px;flex:1;min-width:200px">
          <?php endif; ?>
          <input class="input" type="password" autocomplete="off" id="espkey_<?= $type ?>" aria-label="<?= html_escape($c['label']) ?> API key"
                 placeholder="<?= $conn ? '•••••••• (saved — paste to replace)' : html_escape($c['ph']) ?>"
                 style="max-width:300px;flex:1;min-width:200px">
          <button class="btn pri sm" onclick="icSaveEsp('<?= $type ?>')">Connect</button>
          <?php if ($conn): ?><button class="btn gho sm" onclick="icDisconnectEsp('<?= $type ?>')">Disconnect</button><?php endif; ?>
        </div>
        <div id="espmsg_<?= $type ?>" style="font-size:12px;margin-top:7px;color:var(--mute)"></div>
      </div>
    </div>
  <?php endforeach; ?>
  <div style="font-size:11.5px;color:var(--faint);margin-top:12px">Leads sync the moment a Bridge catches them — only when the lead gave email consent.</div>
</div>

<!-- ───────── EMAIL (SMTP — your own sending domain) — SET-5 ───────── -->
<div class="card pad stab" data-tab="email">
  <div class="cardhead"><div class="ico"><?= ic_svg('email', 17) ?></div><h3>Sending domain (SMTP)</h3>
    <span class="chip <?= !empty($smtp_set) ? 'green' : 'mute' ?> right" id="smtpChip"><?= !empty($smtp_set) ? 'Connected' : 'Not set' ?></span>
  </div>
  <div style="font-size:12.5px;color:var(--mute);margin-bottom:14px;max-width:680px;line-height:1.6">
    Send your follow-up email from your own domain (better deliverability and branding than a shared sender).
    Add your SMTP details, then send yourself a test. For best inbox placement, set up SPF and DKIM on your domain.
  </div>
  <div class="grid2" style="grid-template-columns:1fr 1fr;gap:12px;max-width:680px">
    <div class="field"><label for="sender_name">Sender name</label><input class="input" id="sender_name" type="text" placeholder="Your Brand" value="<?= html_escape($smtp_sender_name ?? '') ?>"></div>
    <div class="field"><label for="sender_email">Sender email (your domain)</label><input class="input" id="sender_email" type="email" placeholder="hello@yourdomain.com" value="<?= html_escape($smtp_from ?? '') ?>"></div>
    <div class="field"><label for="smtp_host">SMTP host</label><input class="input" id="smtp_host" type="text" placeholder="smtp.yourprovider.com" value="<?= html_escape($smtp_host ?? '') ?>"></div>
    <div class="field"><label for="smtp_port">Port</label><input class="input" id="smtp_port" type="number" placeholder="587" value="<?= html_escape($smtp_port ?? '587') ?>"></div>
    <div class="field"><label for="smtp_username">SMTP username</label><input class="input" id="smtp_username" type="text" autocomplete="off" placeholder="username" value="<?= html_escape($smtp_username ?? '') ?>"></div>
    <div class="field"><label for="smtp_password">SMTP password</label><input class="input" id="smtp_password" type="password" autocomplete="off" placeholder="<?= !empty($smtp_set) ? '•••••••• (saved — paste to replace)' : 'password' ?>"></div>
  </div>
  <div style="display:flex;gap:10px;align-items:center;margin-top:14px;flex-wrap:wrap">
    <button class="btn pri sm" onclick="icSaveSmtp()">Save SMTP</button>
    <input class="input" id="smtp_test_email" type="email" placeholder="send a test to…" aria-label="Send test email to" style="max-width:240px">
    <button class="btn gho sm" onclick="icTestSmtp()">Send test email</button>
  </div>
  <div id="smtpMsg" style="font-size:12px;margin-top:9px;color:var(--mute)"></div>
  <div style="font-size:11px;color:var(--faint);margin-top:10px">Port 587 uses STARTTLS; 465 uses SMTPS. Your password is encrypted and never shown back.</div>
</div>

<!-- ───────── COMPLIANCE (read-only) ───────── -->
<div class="card pad stab" data-tab="compliance">
  <div class="cardhead"><div class="ico"><?= ic_svg('shield', 17) ?></div><h3>Compliance</h3></div>
  <div style="font-size:12.5px;color:var(--mute);margin-bottom:8px;max-width:620px">
    The platform enforces these protections on every contact. We're explicit about what is active today versus what is configurable or coming, so you always know exactly what's running.
  </div>

  <!-- SET-7: configurable — spam-score (Nomorobo) check. (Default caller-ID was
       removed: every Bridge picks its own caller-ID on the review step, and the
       live call path resolves per-Bridge from_number → platform default.) -->
  <div class="setrow">
    <div class="si"><div><b>Spam-score check (Nomorobo)</b><span>Screen each number's spam reputation before dialing (US numbers; requires a paid Twilio account with the Nomorobo add-on). Off by default.</span></div></div>
    <label class="right" style="display:inline-flex;align-items:center;gap:10px;cursor:pointer">
      <span class="chip <?= !empty($dnc_check_enabled) ? 'green' : 'mute' ?>" id="dncChip"><?= !empty($dnc_check_enabled) ? 'On' : 'Off' ?></span>
      <span class="sw"><input type="checkbox" id="dncToggle" onchange="icToggleDnc()" aria-label="Enable spam-score check" <?= !empty($dnc_check_enabled) ? 'checked' : '' ?>><span class="track"></span></span>
    </label>
  </div>

  <div class="setrow"><div class="si"><div><b>AI disclosure at call start</b><span>"This is an AI assistant…" announced on every call.</span></div></div><span class="chip green right">Always on</span></div>
  <div class="setrow"><div class="si"><div><b>Consent required before contact</b><span>Only leads who consented to calls/texts are contacted; STOP replies and call opt-outs are honored.</span></div></div><span class="chip green right">Enforced</span></div>
  <div class="setrow"><div class="si"><div><b>Quiet hours</b><span>No outbound AI calls outside 8am–9pm in the lead's local time, when their timezone is known.</span></div></div><span class="chip green right">Enforced</span></div>
  <div class="setrow"><div class="si"><div><b>Internal suppression list</b><span>Numbers on your Do-Not-Contact list are checked before every call and text and blocked when the check succeeds. If the lookup is briefly unavailable it fails open (the contact proceeds) and is retried, so it's a strong filter rather than a hard legal guarantee.</span></div></div><span class="chip green right">Active</span></div>
  <div class="setrow"><div class="si"><div><b>Recording consent</b><span>Where call recording is used, two-party-consent regions get the required notice.</span></div></div><span class="chip blue right">Where applicable</span></div>
</div>

<!-- ───────── DO-NOT-CONTACT LIST (manage the internal suppression list) ───────── -->
<div class="card pad stab" data-tab="compliance" style="margin-top:18px">
  <div class="cardhead"><div class="ico"><?= ic_svg('ban', 17) ?></div><h3>Do-Not-Contact list</h3>
    <span class="right lbl"><?= number_format(count($dnc_list)) ?> number<?= count($dnc_list) === 1 ? '' : 's' ?></span>
  </div>
  <div style="font-size:12.5px;color:var(--mute);margin-bottom:14px;max-width:660px;line-height:1.6">
    Numbers here are <b style="color:var(--txt2)">blocked before every AI call and text</b> across all your Bridges —
    checked the instant a lane is about to fire. Add anyone who asks not to be contacted (a STOP reply already opts a
    lead out automatically; this is for manual, account-wide blocks). Enter the number as it appears on the lead,
    e.g. <code>+14155551234</code>.
  </div>

  <form method="post" action="<?= site_url('setting/dnc-add') ?>" autocomplete="off"
        style="display:flex;gap:10px;flex-wrap:wrap;align-items:flex-start;margin-bottom:16px">
    <input type="hidden" name="<?= html_escape($csrf_name) ?>" value="<?= html_escape($csrf_hash) ?>">
    <input class="input" type="text" name="phone" placeholder="+14155551234" required aria-label="Phone number to block"
           inputmode="tel" maxlength="32" style="max-width:220px;flex:1;min-width:170px">
    <input class="input" type="text" name="reason" maxlength="255" placeholder="Reason (optional) — e.g. requested removal" aria-label="Reason for blocking"
           style="flex:2;min-width:200px">
    <button class="btn pri sm" type="submit">＋ Block number</button>
  </form>

  <?php if (empty($dnc_list)): ?>
    <div class="empty" style="padding:26px 10px">
      <div class="e" style="color:var(--faint)"><?= ic_svg('ban', 34) ?></div>
      <h4>No blocked numbers</h4>
      <p>Your Do-Not-Contact list is empty. Anyone you add above will never be called or texted by your Bridges, on top of the consent and quiet-hours protections already enforced.</p>
    </div>
  <?php else: ?>
    <div style="overflow-x:auto">
      <table class="tbl" style="width:100%;border-collapse:collapse;font-size:13px">
        <thead>
          <tr style="text-align:left;color:var(--mute)">
            <th style="padding:8px 10px;font-weight:600">Number</th>
            <th style="padding:8px 10px;font-weight:600">Reason</th>
            <th style="padding:8px 10px;font-weight:600">Added</th>
            <th style="padding:8px 10px"></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($dnc_list as $d): ?>
            <tr style="border-top:1px solid var(--line)">
              <td style="padding:9px 10px;font-variant-numeric:tabular-nums;white-space:nowrap"><b><?= html_escape($d->phone) ?></b></td>
              <td style="padding:9px 10px;color:var(--txt2)"><?= $d->reason !== null && $d->reason !== '' ? html_escape($d->reason) : '<span style="color:var(--mute)">—</span>' ?></td>
              <td style="padding:9px 10px;color:var(--mute);white-space:nowrap"><?= html_escape(ic_dnc_when($d->created_at)) ?></td>
              <td style="padding:9px 10px;text-align:right;white-space:nowrap">
                <form method="post" action="<?= site_url('setting/dnc-remove') ?>" style="display:inline"
                      onsubmit="return icConfirmSubmit(this, 'Remove <?= html_escape($d->phone) ?> from your Do-Not-Contact list? It can be contacted again, subject to consent + quiet hours.', {title:'Remove from Do-Not-Contact', confirmText:'Remove number', danger:true});">
                  <input type="hidden" name="<?= html_escape($csrf_name) ?>" value="<?= html_escape($csrf_hash) ?>">
                  <input type="hidden" name="id" value="<?= (int) $d->id ?>">
                  <button class="btn gho sm" type="submit">Remove</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>

<script>
function icTab(el, t){
  var tabs = el.parentNode.querySelectorAll('a');
  for (var i=0;i<tabs.length;i++){ tabs[i].classList.remove('on'); }
  el.classList.add('on');
  var panels = document.querySelectorAll('.stab');
  for (var j=0;j<panels.length;j++){
    panels[j].classList.toggle('on', panels[j].getAttribute('data-tab') === t);
  }
}

function icSaveKey(type){
  var inp  = document.getElementById('key_' + type);
  var chip = document.getElementById('chip_' + type);
  var val  = inp.value;
  // Twilio carries TWO secrets — Account SID + Auth Token. Either field being
  // filled counts as an update; require both before the first save.
  var sidInp = (type === 'twilio') ? document.getElementById('sid_twilio') : null;
  var sid = sidInp ? sidInp.value.trim() : '';
  if (type === 'twilio'){
    if (val === '' && sid === ''){ icToast('Paste your Twilio SID and Auth Token', true); return; }
  } else if (val === ''){ icToast('Paste a key first', true); return; }

  var body = new URLSearchParams();
  body.append('meta_type', type);
  body.append('value', val);
  if (sidInp){ body.append('sid', sid); }
  if (window.__csrfName && window.__csrfHash){ body.append(window.__csrfName, window.__csrfHash); }

  fetch('<?= site_url('setting/save-settings') ?>', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
    credentials: 'same-origin',
    body: body.toString()
  })
  .then(function(r){ return r.json(); })
  .then(function(d){
    if (d && d.ok){
      inp.value = '';
      inp.placeholder = '•••••••• (saved — paste to replace)';
      if (sidInp){ sidInp.value = ''; sidInp.placeholder = '•••••••• (saved — paste to replace)'; }
      if (d.isSet){ chip.className = 'chip green right'; chip.textContent = 'Connected'; }
      icToast(type.toUpperCase() + ' key saved');
    } else {
      icToast((d && d.msg) ? d.msg : 'Could not save', true);
    }
  })
  .catch(function(){ icToast('Network error saving key', true); });
}

// ── Shared JSON POST/GET helpers (CSRF via window.__csrf*). ──
function icPost(url, fields){
  var body = new URLSearchParams();
  Object.keys(fields).forEach(function(k){ body.append(k, fields[k]); });
  if (window.__csrfName && window.__csrfHash){ body.append(window.__csrfName, window.__csrfHash); }
  return fetch(url, { method:'POST', headers:{ 'Content-Type':'application/x-www-form-urlencoded','X-Requested-With':'XMLHttpRequest' }, credentials:'same-origin', body: body.toString() })
    .then(function(r){ return r.json(); })
    .catch(function(){ return { ok:false, msg:'Network error.' }; });
}
function icGetJson(url){
  return fetch(url, { credentials:'same-origin' }).then(function(r){ return r.json(); }).catch(function(){ return { ok:false }; });
}
function icSetMsg(id, text, isErr){ var el = document.getElementById(id); if(el){ el.textContent = text; el.style.color = isErr ? 'var(--coral)' : 'var(--green,#3ddc97)'; } }

// ── SET-6: per-key live Test ──
function icTestKey(type){
  var btn = document.getElementById('btnTest_' + type); if (btn) btn.disabled = true;
  icSetMsg('testResult_' + type, 'Testing…', false);
  icPost('<?= site_url('setting/test-key') ?>', { type: type }).then(function(d){
    icSetMsg('testResult_' + type, (d.ok ? '✓ ' : '✗ ') + (d.msg || (d.ok ? 'Valid.' : 'Failed.')), !d.ok);
  }).finally(function(){ if (btn) btn.disabled = false; });
}

// ── SET-2: ESP connect / disconnect / lists / binding ──
function icSaveEsp(type){
  var key = (document.getElementById('espkey_' + type)||{}).value || '';
  var urlEl = document.getElementById('espurl_' + type);
  var fields = { type: type, apikey: key };
  if (urlEl) fields.appurl = urlEl.value || '';
  if (!key){ icSetMsg('espmsg_' + type, 'Paste your API key first.', true); return; }
  icPost('<?= site_url('setting/save-esp') ?>', fields).then(function(d){
    icSetMsg('espmsg_' + type, d.msg || (d.ok ? 'Connected.' : 'Failed.'), !d.ok);
    if (d.ok){ var chip = document.getElementById('espchip_' + type); if(chip){ chip.className='chip green right'; chip.textContent='Connected'; } }
  });
}
function icDisconnectEsp(type){
  if (!window.confirm('Disconnect this autoresponder? New leads will stop syncing to it.')) return;
  icPost('<?= site_url('setting/disconnect-esp') ?>', { type: type }).then(function(d){
    icToast(d.msg || (d.ok ? 'Disconnected.' : 'Failed.'), !d.ok);
    if (d.ok){ setTimeout(function(){ location.reload(); }, 600); }
  });
}
// ── SET-5: SMTP save + test ──
function icSaveSmtp(){
  var f = ['smtp_host','smtp_port','smtp_username','smtp_password','sender_name','sender_email'];
  var fields = {}; f.forEach(function(k){ fields[k] = (document.getElementById(k)||{}).value || ''; });
  icPost('<?= site_url('setting/save-smtp') ?>', fields).then(function(d){
    icSetMsg('smtpMsg', d.msg || (d.ok ? 'Saved.' : 'Failed.'), !d.ok);
    if (d.ok){ var c=document.getElementById('smtpChip'); if(c){ c.className='chip green right'; c.textContent='Connected'; }
      var p=document.getElementById('smtp_password'); if(p){ p.value=''; p.placeholder='•••••••• (saved — paste to replace)'; } }
  });
}
function icTestSmtp(){
  var to = (document.getElementById('smtp_test_email')||{}).value || '';
  if (!to){ icSetMsg('smtpMsg', 'Enter a test recipient first.', true); return; }
  icSetMsg('smtpMsg', 'Sending test…', false);
  icPost('<?= base_url('settings/test-smtp') ?>', { test_email: to }).then(function(d){
    if (d.ok){ icSetMsg('smtpMsg', '✓ Test email sent to ' + (d.to || to) + '.', false); }
    else { icSetMsg('smtpMsg', '✗ ' + (d.msg || 'Send failed.'), true); }
  });
}

// ── SET-7: default caller-ID + DNC toggle ──
function icToggleDnc(){
  var cb = document.getElementById('dncToggle');
  icPost('<?= site_url('setting/toggle-dnc') ?>', { enabled: cb && cb.checked ? '1' : '0' }).then(function(d){
    var chip = document.getElementById('dncChip');
    if (chip && d.ok){ chip.className = 'chip ' + (d.enabled ? 'green' : 'mute'); chip.textContent = d.enabled ? 'On' : 'Off'; }
    icToast(d.msg || (d.ok ? 'Saved.' : 'Failed.'), !d.ok);
  });
}
</script>
</div><!-- /#ic-set -->
