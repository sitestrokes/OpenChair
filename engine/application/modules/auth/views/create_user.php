<?php $globaldata = $this->config->item('global_data');
$logo = base_url($globaldata['logo_url']);
$fav  = base_url($globaldata['fav_url']);
$site_title = $this->config->item('site_title', 'ion_auth');

// Agentic Affiliates-branded create-user. The engine's $whiteLabelData defaults to the
// generic agency global branding, which we intentionally do NOT use here — this
// is the Agentic Affiliates product surface. (A real per-tenant white-label logo, if
// ever passed, can still override the favicon.)
if (!empty($whiteLabelData['fav_url'])) $fav = $whiteLabelData['fav_url'];
$brand = 'Agentic Affiliates';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="icon" type="image/svg+xml" href="<?= $fav ?>">
<title><?php echo lang('create_user_heading');?> · <?= html_escape($brand) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Archivo:wght@600;700;800;900&family=Inter:wght@400;500;600;700&family=Space+Mono:wght@400;700&display=swap" rel="stylesheet">
<style>
:root{
  --bg:#080b12; --surface:#0f1320; --surface2:#141a2b; --line:#1f2740; --line2:#2c3658;
  --txt:#eef1fb; --txt2:#aab2cd; --mute:#7e89a9; --faint:#5a647f;
  --coral:#ff6a4d; --coral2:#ff8b73; --coraldim:#ff6a4d22; --green:#34e3a4; --blue:#5ac8ff;
  --disp:'Archivo',sans-serif; --body:'Inter',sans-serif; --mono:'Space Mono',monospace;
}
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
html,body{height:100%}
body{font-family:var(--body);color:var(--txt);background:var(--bg);-webkit-font-smoothing:antialiased;
  display:grid;grid-template-columns:1.1fr .9fr;min-height:100vh}
::selection{background:var(--coraldim)}
a{color:inherit;text-decoration:none}

/* ===== LEFT — brand / value prop ===== */
.left{position:relative;overflow:hidden;padding:48px 56px;display:flex;flex-direction:column;
  background:linear-gradient(160deg,#10131f,#0a0d15 55%,#0b0f1a)}
.left::before{content:'';position:absolute;inset:0;opacity:.5;pointer-events:none;
  background:radial-gradient(60% 50% at 85% 12%,rgba(255,106,77,.16),transparent 70%),
             radial-gradient(50% 40% at 8% 92%,rgba(90,200,255,.10),transparent 70%)}
.left::after{content:'';position:absolute;inset:0;opacity:.06;pointer-events:none;
  background-image:linear-gradient(var(--line) 1px,transparent 1px),linear-gradient(90deg,var(--line) 1px,transparent 1px);
  background-size:34px 34px;mask-image:radial-gradient(80% 70% at 50% 30%,#000,transparent 75%)}
.brand{display:flex;align-items:center;gap:12px;position:relative;z-index:2}
.brand .mk{width:40px;height:40px;border-radius:12px;display:grid;place-items:center;
  background:linear-gradient(155deg,var(--coral),#d4452a);box-shadow:0 8px 22px -8px rgba(255,106,77,.7)}
.brand .mk svg{width:21px;height:21px;fill:#1a0e08}
.brand .mk img{width:40px;height:40px;border-radius:12px;object-fit:cover}
.brand b{font-family:var(--disp);font-weight:900;font-size:20px;letter-spacing:-.5px}
.brand small{display:block;font-family:var(--mono);font-size:8.5px;color:var(--mute);letter-spacing:1.6px;text-transform:uppercase;margin-top:2px}
.hero{margin-top:auto;margin-bottom:auto;position:relative;z-index:2;max-width:460px}
.pill{display:inline-flex;align-items:center;gap:8px;font-family:var(--mono);font-size:10px;letter-spacing:2px;
  text-transform:uppercase;color:var(--coral2);background:var(--coraldim);border:1px solid #ff6a4d33;
  padding:6px 12px;border-radius:30px;margin-bottom:22px}
.pill .blip{width:7px;height:7px;border-radius:50%;background:var(--coral);box-shadow:0 0 0 0 rgba(255,106,77,.6);animation:blip 2s infinite}
@keyframes blip{0%{box-shadow:0 0 0 0 rgba(255,106,77,.5)}100%{box-shadow:0 0 0 9px rgba(255,106,77,0)}}
.hero h1{font-family:var(--disp);font-weight:900;font-size:42px;line-height:1.04;letter-spacing:-1.5px}
.hero h1 em{font-style:normal;color:var(--coral)}
.hero p{color:var(--txt2);font-size:15px;line-height:1.6;margin-top:18px;max-width:400px}
.lanes{display:flex;gap:10px;margin-top:28px;flex-wrap:wrap}
.lane{display:flex;align-items:center;gap:8px;font-size:12px;color:var(--txt2);
  background:var(--surface);border:1px solid var(--line);border-radius:10px;padding:9px 13px}
.lane b{color:var(--txt);font-weight:600}
.lane .t{font-family:var(--mono);font-size:10px;color:var(--faint)}
.foot{position:relative;z-index:2;font-size:11px;color:var(--faint);font-family:var(--mono);letter-spacing:.5px}

/* ===== RIGHT — form ===== */
.right{display:flex;align-items:center;justify-content:center;padding:48px 56px;background:var(--bg)}
.card{width:100%;max-width:420px}
.eyebrow{font-family:var(--mono);font-size:10px;letter-spacing:2.5px;text-transform:uppercase;color:var(--coral);
  display:inline-flex;align-items:center;gap:8px;margin-bottom:14px}
.eyebrow::before{content:'';width:22px;height:1.5px;background:var(--coral)}
.title{font-family:var(--disp);font-weight:800;font-size:30px;line-height:1.12;letter-spacing:-.8px}
.title em{font-style:normal;color:var(--coral);display:block}
.sub{color:var(--mute);font-size:13.5px;line-height:1.55;margin:8px 0 26px}
.msg{font-size:12.5px;line-height:1.5;padding:11px 14px;border-radius:11px;margin-bottom:16px;
  background:#ff6a4d14;border:1px solid #ff6a4d33;color:#ffb9a8}
.msg:empty{display:none}
.msg .alert{background:none;border:0;padding:0;margin:0;color:inherit}

/* CI form-helper fields: label + control come through as native markup, so we
   style by structure (the <p> wrappers) and by input tag, not by class. */
.ic-form p{margin-bottom:15px;display:flex;flex-direction:column;gap:8px}
.ic-form label{font-size:11px;font-weight:600;letter-spacing:1px;text-transform:uppercase;color:var(--txt2)}
.ic-form .text-danger,.ic-form .error{display:block;font-size:11.5px;color:#ffb9a8;font-weight:500;letter-spacing:.2px}
.ic-form input[type=text],
.ic-form input[type=email],
.ic-form input[type=tel],
.ic-form input[type=password]{
  width:100%;background:var(--surface);border:1.5px solid var(--line);border-radius:12px;
  color:var(--txt);font-family:var(--body);font-size:14px;font-weight:500;padding:14px;outline:none;transition:.2s}
.ic-form input:focus{border-color:var(--coral);background:var(--surface2);box-shadow:0 0 0 4px var(--coraldim)}
.ic-form input::placeholder{color:var(--faint)}
.ic-form input:-webkit-autofill{-webkit-text-fill-color:var(--txt)!important;-webkit-box-shadow:0 0 0 1000px var(--surface) inset!important;transition:background-color 9999s}
.ic-form input[type=submit],
.ic-form button[type=submit]{
  width:100%;margin-top:9px;padding:15px 22px;border:0;border-radius:12px;cursor:pointer;
  background:linear-gradient(135deg,var(--coral),#d4452a);color:#fff;
  font-family:var(--disp);font-weight:800;font-size:13px;letter-spacing:.8px;transition:.2s;
  box-shadow:0 12px 30px -10px rgba(255,106,77,.65)}
.ic-form input[type=submit]:hover,
.ic-form button[type=submit]:hover{transform:translateY(-2px);box-shadow:0 18px 38px -12px rgba(255,106,77,.7)}
.backlink{display:inline-flex;align-items:center;gap:6px;margin-top:20px;font-size:12px;
  font-family:var(--mono);letter-spacing:.5px;color:var(--coral)}
.backlink:hover{text-decoration:underline}
.copy{margin-top:22px;text-align:center;font-size:10.5px;color:var(--faint);font-family:var(--mono);letter-spacing:.4px}
.copy strong{color:var(--txt2);font-weight:600}

@media(max-width:900px){
  body{grid-template-columns:1fr}
  .left{display:none}
  .right{padding:40px 24px}
}
</style>
</head>
<body>

<!-- ===== LEFT — Agentic Affiliates value prop ===== -->
<div class="left">
  <div class="brand">
    <div class="mk">
      <svg viewBox="0 0 24 24"><path d="M12 2a7 7 0 00-7 7v3.5L3.5 16a1 1 0 00.9 1.4H8a4 4 0 008 0h3.6a1 1 0 00.9-1.4L19 12.5V9a7 7 0 00-7-7z"/></svg>
    </div>
    <div><b><?= html_escape($brand) ?></b><small>Talks back</small></div>
  </div>

  <div class="hero">
    <div class="pill"><span class="blip"></span> New Account</div>
    <h1>Create your <em>account</em> and start closing on autopilot.</h1>
    <p>Spin up a new operator, paste an affiliate link, and Agentic Affiliates builds a Bridge that catches every lead with a live conversation.</p>
    <div class="lanes">
      <div class="lane">🎙️ <b>Browser</b> <span class="t">0s</span></div>
      <div class="lane">📞 <b>Phone</b> <span class="t">15s</span></div>
      <div class="lane">💬 <b>Pocket</b> <span class="t">60s</span></div>
    </div>
  </div>

  <div class="foot">© <?= date('Y') ?> <?= html_escape($brand) ?> · Triple‑Trigger Bridge</div>
</div>

<!-- ===== RIGHT — create user form (mechanics preserved) ===== -->
<div class="right">
  <div class="card">
    <div class="eyebrow">Provision Account</div>
    <h1 class="title"><?php echo lang('create_user_heading');?></h1>
    <p class="sub"><?php echo lang('create_user_subheading');?></p>

    <div id="infoMessage" class="msg"><?php echo $message;?></div>

    <div class="ic-form">
    <?php echo form_open("auth/create_user");?>

          <p>
                <?php echo lang('create_user_fname_label', 'first_name');?>
                <?php echo form_input($first_name);?>
          </p>

          <p>
                <?php echo lang('create_user_lname_label', 'last_name');?>
                <?php echo form_input($last_name);?>
          </p>

          <?php
          if($identity_column!=='email') {
              echo '<p>';
              echo lang('create_user_identity_label', 'identity');
              echo form_error('identity');
              echo form_input($identity);
              echo '</p>';
          }
          ?>

          <p>
                <?php echo lang('create_user_company_label', 'company');?>
                <?php echo form_input($company);?>
          </p>

          <p>
                <?php echo lang('create_user_email_label', 'email');?>
                <?php echo form_input($email);?>
          </p>

          <p>
                <?php echo lang('create_user_phone_label', 'phone');?>
                <?php echo form_input($phone);?>
          </p>

          <p>
                <?php echo lang('create_user_password_label', 'password');?>
                <?php echo form_input($password);?>
          </p>

          <p>
                <?php echo lang('create_user_password_confirm_label', 'password_confirm');?>
                <?php echo form_input($password_confirm);?>
          </p>


          <p><?php echo form_submit('submit', lang('create_user_submit_btn'));?></p>

    <?php echo form_close();?>
    </div>

    <a href="<?= base_url('login') ?>" class="backlink">← Back to Login</a>

    <p class="copy">© <?php echo date('Y');?> <strong><?= html_escape($brand) ?></strong> — All Rights Reserved</p>
  </div>
</div>

</body>
</html>
