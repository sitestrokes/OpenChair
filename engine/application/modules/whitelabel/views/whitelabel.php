<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php
/**
 * WHITE-LABEL — re-brand the whole app (brand name, logo, favicon, footer).
 * Rendered inside the Agentic Affiliates dark shell (Ic_render_lib, legacy=true →
 * jQuery + SweetAlert). Saves to settings meta_type 'whitelabel' via
 * whitelabel/cURD; uploads go through whitelabel/image_upload (S3, local on
 * dev). App_Controller::_build_ic_brand() reads this row so the shell (sidebar
 * mark, <title>, favicon, footer) re-brands across every page.
 *
 * Vars: $whitelabel_Data (site_title, logo, fav_url, sidebar_logo, footer_text,
 *        tutorial_tab, support_tab, bonus_tab).
 */
$wld = isset($whitelabel_Data) && is_array($whitelabel_Data) ? $whitelabel_Data : [];
$wl_site   = isset($wld['site_title'])  ? $wld['site_title']  : '';
$wl_logo   = isset($wld['logo'])        ? $wld['logo']        : '';
$wl_fav    = isset($wld['fav_url'])     ? $wld['fav_url']     : '';
$wl_sbl    = isset($wld['sidebar_logo'])? $wld['sidebar_logo']: '';
$wl_foot   = isset($wld['footer_text']) ? $wld['footer_text'] : '';
$wl_tut    = !empty($wld['tutorial_tab']);
$wl_sup    = !empty($wld['support_tab']);
$wl_bon    = !empty($wld['bonus_tab']);
?>

<div class="headrow">
  <div class="pagehead">
    <h1>White-label</h1>
    <div class="psub">Forge the app's outward face. The brand name, logo, favicon and footer set here propagate across the <b>entire shell</b> — sidebar mark, browser tab + favicon, page title and footer.</div>
  </div>
</div>

<form id="whitelabelFOrm" class="card pad" style="margin-top:24px;max-width:920px">
  <div class="cardhead" style="margin-bottom:18px"><div class="ico">⭐</div><h3>Brand identity</h3></div>

  <div class="grid2" style="gap:18px 32px">

    <!-- Left column -->
    <div>
      <div class="field">
        <label>Brand name</label>
        <input name="site_title" value="<?= html_escape($wl_site) ?>" type="text" class="input" placeholder="Your agency name…">
        <div class="hint">Shown beside the sidebar mark, in the top-bar pill, and as the browser tab title.</div>
      </div>

      <div class="field" style="margin-bottom:0">
        <label>Logo URL <span class="lbl" style="text-transform:none;color:var(--faint)">(wide lockup)</span></label>
        <input name="logo" id="logo" value="<?= html_escape($wl_logo) ?>" type="text" class="input" placeholder="https://yourbrand.com/logo.svg">
        <div class="wl-specimen">
          <input accept="image/*" type="file" class="hiddenfile logo" style="display:none">
          <img src="<?= html_escape($wl_logo) ?>" title="Click to change" class="chnagethumb">
          <div>
            <a href="javascript:void(0)" class="btn gho sm refreshlogo">↺ Reset default</a>
            <span class="hint" style="display:block;margin-top:6px">Click the image to upload. Recommended ~160×40.</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Right column -->
    <div>
      <div class="field">
        <label>Footer text</label>
        <textarea name="footer_text" class="input" rows="3" style="resize:vertical" placeholder="Footer copy shown across the app…"><?= html_escape($wl_foot) ?></textarea>
        <div class="hint">Shown at the bottom of every page.</div>
      </div>

      <div class="field" style="margin-bottom:0">
        <label>Favicon / mark URL <span class="lbl" style="text-transform:none;color:var(--faint)">(square)</span></label>
        <input name="fav_url" id="logo1" value="<?= html_escape($wl_fav) ?>" type="text" class="input" placeholder="https://yourbrand.com/favicon.svg">
        <div class="wl-specimen">
          <input accept="image/*" type="file" class="hiddenfile logo" style="display:none">
          <img src="<?= html_escape($wl_fav) ?>" title="Click to change" class="chnagethumb" style="max-width:64px;max-height:64px">
          <div>
            <a href="javascript:void(0)" class="btn gho sm refreshlogo1">↺ Reset default</a>
            <span class="hint" style="display:block;margin-top:6px">Square icon — browser favicon + sidebar mark. ~64×64.</span>
          </div>
        </div>
      </div>
    </div>

  </div>

  <!-- Hidden fields preserved for form serialization -->
  <div style="display:none">
    <input name="sidebar_logo" id="logo2" value="<?= html_escape($wl_sbl) ?>" type="text">
    <input accept="image/*" type="file" class="hiddenfile logo" style="display:none">
    <img data-src="<?= html_escape($wl_sbl) ?>" class="chnagethumb">
    <a href="javascript:void(0)" class="refreshlogo2"></a>
    <input type="checkbox" name="tutorial_tab" id="tutorial_tab" <?= $wl_tut ? 'checked' : '' ?>>
    <input type="checkbox" name="support_tab" id="support_tab" <?= $wl_sup ? 'checked' : '' ?>>
    <input type="checkbox" name="bonus_tab" id="bonus_tab" <?= $wl_bon ? 'checked' : '' ?>>
  </div>

  <div style="margin-top:22px;display:flex;align-items:center;gap:12px">
    <input name="submit" type="submit" class="btn pri" value="Save brand">
    <span class="hint">Applies across the whole app the moment you save.</span>
  </div>
</form>

<style>
  /* dark specimen row for the logo/favicon upload preview */
  .wl-specimen{display:flex;align-items:center;gap:16px;margin-top:12px;padding:14px 16px;border:1px dashed var(--line2);border-radius:var(--r2);background:var(--bg);}
  .wl-specimen img.chnagethumb{max-width:160px;max-height:60px;object-fit:contain;cursor:pointer;border:1px solid var(--line);border-radius:8px;padding:8px;background:#fff;}
  .wl-specimen img.chnagethumb:hover{border-color:var(--coral);}
</style>

<script type="text/javascript">
  $(document).ready(function(){
    var DEF_LOGO = <?= json_encode(base_url($this->config->item('global_data')['logo_url'])) ?>;
    var DEF_FAV  = <?= json_encode(base_url($this->config->item('global_data')['fav_url'])) ?>;

    // The thumbnail for a given .field follows its URL input (set src, or hide if blank).
    function syncThumb($field){
      var url = $field.find('input.input').val().trim();
      var $img = $field.find('img.chnagethumb');
      if (url){ $img.attr('src', url).css('display',''); }
      else { $img.removeAttr('src').css('display','none'); }
    }

    // 1) Live preview — typing/pasting a URL updates that field's thumbnail.
    $('#logo, #logo1').on('input', function(){ syncThumb($(this).closest('.field')); });
    // Initial sync (hide thumbnails whose URL is blank; show those that have one).
    $('#logo, #logo1').each(function(){ syncThumb($(this).closest('.field')); });

    // 2) Click the thumbnail to pick a file (its sibling file input).
    $(".chnagethumb").click(function(){
      $(this).closest('.wl-specimen').find('input.hiddenfile').click();
    });

    // 3) Reset to the platform default logo / favicon.
    $(".refreshlogo").click(function(){
      $("#logo").val(DEF_LOGO); syncThumb($("#logo").closest('.field'));
    });
    $(".refreshlogo1").click(function(){
      $("#logo1").val(DEF_FAV); syncThumb($("#logo1").closest('.field'));
    });

    // 4) Upload → fill that field's URL input + thumbnail with the returned URL.
    $(".hiddenfile").on("change", function(){
      var $file = $(this), $field = $file.closest('.field');
      if (!this.files || !this.files[0]) return;
      var fd = new FormData();
      fd.append('userFile', this.files[0]);
      // CSRF (FormData) — the global prefilter also appends it; harmless if duplicated.
      if (window.__csrfName && window.__csrfHash) fd.append(window.__csrfName, window.__csrfHash);

      $.ajax({
        url: "<?= base_url('whitelabel/image_upload') ?>",
        data: fd, processData: false, contentType: false,
        type: "POST", dataType: 'text',   // parse manually — CI can prepend whitespace
        success: function(raw){
          var data = null;
          try { data = JSON.parse(String(raw).trim()); } catch(e){ data = null; }
          if (data && data.success && data.URL){
            $field.find('input.input').val(data.URL);
            syncThumb($field);
            if (typeof icToast === 'function') icToast('Image uploaded.');
          } else {
            var msg = (data && data.error) ? data.error : 'Upload failed.';
            icToast(msg, true);
          }
        },
        error: function(){ icToast('Upload failed.', true); }
      });
      this.value = ''; // allow re-selecting the same file
    });

    // 5) Save — then reload so the new brand (sidebar/title/favicon) takes effect.
    $("#whitelabelFOrm").on("submit", function(){
      var $btn = $(this).find('[type="submit"]');
      $btn.prop('disabled', true);
      $.ajax({
        url: "<?= base_url('whitelabel/cURD') ?>",
        data: $(this).serialize(), type: "POST",
        success: function(){
          icToast('Brand saved — reloading to apply your branding…');
          // Always reload.
          setTimeout(function(){ location.reload(); }, 900);
        },
        error: function(){
          $btn.prop('disabled', false);
          icToast('Save failed. Please try again.', true);
        }
      });
      return false;
    });
  });
</script>
