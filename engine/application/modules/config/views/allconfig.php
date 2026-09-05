<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * API CONFIG — encrypted platform API keys (superadmin / user 1 only).
 *
 * Rendered inside the Agentic Affiliates dark shell via Ic_render_lib, so it carries
 * the IC sidebar + topbar (NOT the legacy Agentic Agency chrome). Dependency-
 * free: vanilla JS + fetch() + the shell's icToast(); no jQuery / Bootstrap.
 *
 * Vars: $configData (rows of ['Data' => config row]).
 * Posts to config/saveKey and config/deleteKey.
 */
$csrf_name = $this->security->get_csrf_token_name();
$csrf_hash = $this->security->get_csrf_hash();
$rows = isset($configData) && is_array($configData) ? $configData : [];
?>

<div class="headrow">
  <div class="pagehead">
    <h1>API Config</h1>
    <div class="psub">Encrypted platform API keys. Stored AES-encrypted in the <b>config</b> table and read everywhere via <b>Config_lib</b> — never hardcode a key. Visible to the superadmin only.</div>
  </div>
  <button type="button" class="btn pri" onclick="cfgOpen()">+ Add key</button>
</div>

<div class="card" style="margin-top:22px;overflow:hidden">
  <table style="width:100%;border-collapse:collapse">
    <thead>
      <tr style="border-bottom:1px solid var(--line)">
        <th style="text-align:left;padding:13px 16px" class="lbl">#</th>
        <th style="text-align:left;padding:13px 16px" class="lbl">Name</th>
        <th style="text-align:left;padding:13px 16px" class="lbl">Value</th>
        <th style="text-align:left;padding:13px 16px" class="lbl">Created</th>
        <th style="text-align:right;padding:13px 16px" class="lbl">Action</th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($rows)): ?>
        <tr><td colspan="5" style="padding:30px 16px;text-align:center;color:var(--mute)">No keys yet. Click <b style="color:var(--txt2)">Add key</b> to store one.</td></tr>
      <?php else: foreach ($rows as $i => $value):
          $config = $value['Data'];
          $name = (string) $config->key_name;
          $val  = substr((string) $config->key_value, 0, 40) . '…';
          $created_on = $this->app_lib->utc_to_local($config->doe, 'd-m-Y');
      ?>
        <tr style="border-bottom:1px solid var(--line)">
          <td style="padding:13px 16px;color:var(--faint);font-family:var(--mono);font-size:12px"><?= (int) $i + 1 ?></td>
          <td style="padding:13px 16px;font-weight:600"><?= html_escape($name) ?></td>
          <td style="padding:13px 16px;color:var(--mute);font-family:var(--mono);font-size:12px"><?= html_escape($val) ?></td>
          <td style="padding:13px 16px;color:var(--txt2);font-size:12.5px"><?= html_escape($created_on) ?></td>
          <td style="padding:13px 16px;text-align:right;white-space:nowrap">
            <button type="button" class="btn gho sm" onclick="cfgEdit(this)" data-id="<?= (int) $config->id ?>" data-key="<?= html_escape($name) ?>">Edit</button>
            <button type="button" class="btn gho sm" style="border-color:var(--hot);color:var(--hot)" onclick="cfgDelete(<?= (int) $config->id ?>)">Delete</button>
          </td>
        </tr>
      <?php endforeach; endif; ?>
    </tbody>
  </table>
</div>

<!-- Add / Edit modal — vanilla overlay (no Bootstrap). -->
<div id="cfgModal" style="display:none;position:fixed;inset:0;z-index:90;background:rgba(4,6,12,.66);align-items:center;justify-content:center">
  <div class="card pad" style="width:440px;max-width:92vw;animation:tin .2s ease">
    <div class="cardhead" style="margin-bottom:14px">
      <h3 id="cfgTitle">Add key</h3>
      <button type="button" class="btn gho sm right" onclick="cfgClose()">✕</button>
    </div>
    <input type="hidden" id="cfg_cid">
    <div class="field">
      <label>Key name</label>
      <input class="input" type="text" id="cfg_key" placeholder="e.g. openai_api_key" autocomplete="off">
    </div>
    <div class="field" style="margin-bottom:0">
      <label>Key value</label>
      <input class="input" type="text" id="cfg_value" placeholder="Paste the secret value" autocomplete="off">
      <div class="hint">Stored encrypted. Leave blank when editing only the name.</div>
    </div>
    <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:20px">
      <button type="button" class="btn gho" onclick="cfgClose()">Cancel</button>
      <button type="button" class="btn pri" id="cfgSave" onclick="cfgSubmit()">Save</button>
    </div>
  </div>
</div>

<script>
(function(){
  var SAVE_URL = '<?= site_url('config/saveKey') ?>';
  var DEL_URL  = '<?= site_url('config/deleteKey') ?>';
  var CSRF_NAME = '<?= $csrf_name ?>', CSRF_HASH = '<?= $csrf_hash ?>';

  function post(url, payload){
    var body = new URLSearchParams();
    Object.keys(payload).forEach(function(k){ body.append(k, payload[k]); });
    body.append(CSRF_NAME, (window.__csrfHash || CSRF_HASH));
    return fetch(url, {
      method:'POST', credentials:'same-origin',
      headers:{ 'Content-Type':'application/x-www-form-urlencoded', 'X-Requested-With':'XMLHttpRequest' },
      body: body.toString()
    }).then(function(r){ return r.json(); });
  }

  window.cfgOpen = function(){
    document.getElementById('cfgTitle').textContent = 'Add key';
    document.getElementById('cfg_cid').value = '';
    document.getElementById('cfg_key').value = '';
    document.getElementById('cfg_value').value = '';
    document.getElementById('cfg_key').removeAttribute('readonly');
    document.getElementById('cfgModal').style.display = 'flex';
    document.getElementById('cfg_key').focus();
  };
  window.cfgEdit = function(btn){
    document.getElementById('cfgTitle').textContent = 'Edit key';
    document.getElementById('cfg_cid').value = btn.getAttribute('data-id') || '';
    document.getElementById('cfg_key').value = btn.getAttribute('data-key') || '';
    document.getElementById('cfg_value').value = '';
    document.getElementById('cfgModal').style.display = 'flex';
    document.getElementById('cfg_value').focus();
  };
  window.cfgClose = function(){ document.getElementById('cfgModal').style.display = 'none'; };

  window.cfgSubmit = function(){
    var key = document.getElementById('cfg_key').value.trim();
    var value = document.getElementById('cfg_value').value.trim();
    var cid = document.getElementById('cfg_cid').value;
    if (!key || !value){ icToast('Please fill both fields.', true); return; }
    var b = document.getElementById('cfgSave'); b.disabled = true;
    post(SAVE_URL, { key:key, value:value, cid:cid }).then(function(d){
      b.disabled = false;
      if (d && String(d.status) === '200'){ icToast('Key saved.'); setTimeout(function(){ location.reload(); }, 500); }
      else { icToast((d && d.msg) ? d.msg : 'Something went wrong.', true); }
    }).catch(function(){ b.disabled = false; icToast('Request failed.', true); });
  };

  window.cfgDelete = function(id){
    icConfirm({ title:'Delete this key?', message:'This cannot be undone.',
                confirmText:'Delete key', danger:true }).then(function(ok){
      if (!ok) return;
      post(DEL_URL, { id:id }).then(function(d){
        if (d && String(d.status) === '200'){ icToast('Key deleted.'); setTimeout(function(){ location.reload(); }, 500); }
        else { icToast('Something went wrong.', true); }
      }).catch(function(){ icToast('Request failed.', true); });
    });
  };

  // Close on backdrop click / Escape.
  document.getElementById('cfgModal').addEventListener('click', function(e){ if (e.target === this) cfgClose(); });
  document.addEventListener('keydown', function(e){ if (e.key === 'Escape') cfgClose(); });
})();
</script>
