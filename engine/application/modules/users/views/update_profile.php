<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * MY PROFILE — name/password edit + access tiers (route /myprofile).
 * Rendered inside the Agentic Affiliates dark shell (Ic_render_lib, legacy=true for
 * jQuery + SweetAlert2). The form IDs/names are unchanged so the AJAX submit to
 * /updateProfile keeps working.
 */
$uAccess = explode(",", $this->user->access);
?>

<div class="headrow">
  <div class="pagehead">
    <h1>My Profile</h1>
    <div class="psub">Update your name and password, and review the access tiers on your account.</div>
  </div>
</div>

<div class="grid12" style="margin-top:22px;align-items:start">
  <!-- Profile form -->
  <form id="profileForm" class="card pad">
    <div class="cardhead" style="margin-bottom:14px"><h3>Profile</h3></div>
    <input type="hidden" name="userID" value="<?= $user_row->id ?>">
    <div class="field">
      <label>First name</label>
      <input class="input" type="text" id="firstname" name="firstname" value="<?= html_escape($user_row->first_name) ?>">
    </div>
    <div class="field">
      <label>Last name</label>
      <input class="input" type="text" id="lastname" name="lastname" value="<?= html_escape($user_row->last_name) ?>">
    </div>
    <div class="field">
      <label>Email <span class="lbl" style="text-transform:none;color:var(--faint)">(read-only)</span></label>
      <input class="input" type="text" id="email" name="email" value="<?= html_escape($user_row->email) ?>" readonly style="opacity:.7">
    </div>
    <div class="field" style="margin-bottom:18px">
      <label>New password <span class="lbl" style="text-transform:none;color:var(--faint)">(leave blank to keep current)</span></label>
      <input class="input" type="text" id="password" name="password" placeholder="••••••••••••">
    </div>
    <button type="submit" class="btn pri">Update profile</button>
  </form>

  <!-- Access tiers -->
  <div>
    <?php
    $have_access    = explode(",", $user_row->access);
    $upgrade_access = array_diff(array(2, 3, 4, 7), $have_access);
    $product_access = $this->config->item('access_specifier');
    if (!in_array(833, $uAccess)) {
    ?>
      <div class="card pad">
        <div class="cardhead" style="margin-bottom:14px"><h3>My access</h3><span class="right chip green">Current</span></div>
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(150px,1fr));gap:10px">
          <?php foreach ($have_access as $key => $access) {
            $usertype = '';
            if ($access == 7 || $access == 71 || $access == 72) {
              $usertype = "$product_access[$access]";
              if (($key1 = array_search('7', $upgrade_access)) !== false) { unset($upgrade_access[$key1]); }
            } elseif ($access == 6) {
              $usertype = "$product_access[6]";
              if (($key = array_search('6', $upgrade_access)) !== false) { unset($upgrade_access[$key]); }
            } elseif ($access == 10) {
              // OTO5 — DFY Reviewed Page Vault. Was missing here, so buyers who
              // own it saw nothing for it in "My access".
              $usertype = "$product_access[10]";
            } elseif ($access == 4) {
              $usertype = "$product_access[4]";
              if (($key = array_search('4', $upgrade_access)) !== false) { unset($upgrade_access[$key]); }
            } elseif ($access == 3) {
              $usertype = "$product_access[3]";
              if (($key = array_search('3', $upgrade_access)) !== false) { unset($upgrade_access[$key]); }
            } elseif ($access == 2) {
              $usertype = "$product_access[2]";
              if (($key = array_search('2', $upgrade_access)) !== false) { unset($upgrade_access[$key]); }
            } elseif ($access == '11') { $usertype = "$product_access[11]"; }
            elseif ($access == '9')  { $usertype = "$product_access[9]"; $upgrade_access = array(); }
            elseif ($access == '91') { $usertype = "$product_access[91]"; }
            elseif ($access == '92') { $usertype = "$product_access[92]"; }
            elseif ($access == '911'){ $usertype = "$product_access[911]"; }
            elseif ($access == 1)    { $usertype = "$product_access[1]"; }
            else if ($access == '5') { $usertype = "Admin"; }
            else if ($access == '94'){ $usertype = "$product_access[94]"; $upgrade_access = array(); }
            if ($usertype === '') { continue; }
          ?>
            <div style="display:flex;align-items:center;gap:9px;padding:12px 14px;border:1px solid var(--line);border-radius:var(--r2);background:var(--bg)">
              <span style="color:var(--green);font-size:14px">✓</span>
              <b style="font-size:12.5px"><?= html_escape($usertype) ?></b>
            </div>
          <?php } ?>
        </div>
      </div>
    <?php }

    if (in_array("9", $have_access) || in_array("91", $have_access) || in_array("92", $have_access) || in_array("5", $have_access) || in_array("94", $have_access)) {
      $upgrade_access = [];
    }
    if (!empty($upgrade_access)) {
      $product_access = $this->config->item('access_specifier');
    ?>
      <div class="card pad" style="margin-top:16px">
        <div class="cardhead" style="margin-bottom:6px"><h3>Upgrade your access</h3></div>
        <div class="psub" style="color:var(--mute);font-size:12.5px;margin-bottom:14px">Tiers you don't own yet.</div>
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:10px">
          <?php foreach ($upgrade_access as $up_access) {
            $usertype = ''; $upgrade_link = '#';
            if ($up_access == '7')      { $usertype = "$product_access[7]"; $upgrade_link = "https://getagenticaffiliate.com/training"; }
            elseif ($up_access == '4')  { $usertype = "$product_access[4]"; $upgrade_link = "https://getagenticaffiliate.com/automated"; }
            elseif ($up_access == '3')  { $usertype = "$product_access[3]"; $upgrade_link = "https://getagenticaffiliate.com/pro"; }
            elseif ($up_access == '2')  { $usertype = "$product_access[2]"; $upgrade_link = "https://getagenticaffiliate.com/unlimited"; }
            elseif ($up_access == '1')  { $usertype = "$product_access[1]"; $upgrade_link = "https://getagenticaffiliate.com"; }
            if ($usertype === '') { continue; }
          ?>
            <div style="padding:14px;border:1px solid var(--line);border-radius:var(--r2);background:var(--bg);text-align:center">
              <b style="display:block;font-size:13px;margin-bottom:10px"><?= html_escape($usertype) ?></b>
              <a href="<?= html_escape($upgrade_link) ?>" target="_blank" rel="noopener" class="btn gho sm">Upgrade ↗</a>
            </div>
          <?php } ?>
        </div>
      </div>
    <?php } ?>
  </div>
</div>

<script type="text/javascript">
  $(document).ready(function() {
    $("#profileForm").on("submit", function() {
      var data1 = $(this).serialize();
      $.ajax({
        url: "<?= base_url('updateProfile') ?>",
        data: data1,
        type: "POST",
        dataType: 'JSON',
        success(ret) {
          if (ret.responseCode == 1) {
            icToast('Your account details were updated.');
          }
        }
      });
      return false;
    });
  });
</script>
