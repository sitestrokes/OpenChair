<?php $globaldata = $this->config->item('global_data');
$logo       = base_url($globaldata['logo_url']);
$fav        = base_url($globaldata['fav_url']);
$site_title = $this->config->item('site_title', 'ion_auth');

// OpenChair-branded login. The engine's $whiteLabelData defaults to the global
// branding; a per-tenant white-label favicon may still override below.
if (!empty($whiteLabelData['fav_url'])) $fav = $whiteLabelData['fav_url'];
$brand = 'OpenChair';

// Artwork is inline SVG below rather than a bitmap: one less asset to ship,
// one less 404 when a deployment forgets it, and it stays sharp at any size.
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="icon" type="image/svg+xml" href="<?= $fav ?>">
<title><?= ucfirst($PageTitle) ?> · <?= html_escape($brand) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<!-- Outfit (display) + Manrope (body) + Space Mono (micro-labels) — the same
     three faces the product uses, so the brand does not change between the
     login and the first screen behind it. -->
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&family=Manrope:wght@400;500;600;700;800&family=Space+Mono:wght@400;700&display=swap" rel="stylesheet">
<style>
/* ============================================================
   OPENCHAIR LOGIN — warm blush ground, form on the LEFT, an
   inline week-grid illustration on the RIGHT.

   The login stays in the house CREAM palette rather than the
   product's dark ramp. Deliberate: it is the surface a buyer
   meets first and shares a house identity with the rest of the
   suite; the espresso ramp starts once you are inside, where
   the map has to glow.
   ============================================================ */
:root{
  --pink:#FBEBE6; --pink2:#F9E2DA; --blush:#F6D3C8;
  --ink:#1F1712; --ink2:#5F5148; --mute:#8E7E74; --faint:#B9A79C;
  --coral:#F0755E; --coral-deep:#E75C43; --coral-soft:#F9C9BC;
  --surface:#FFFFFF; --line:#EBD8CF; --line2:#E1C9BD;
  --disp:'Outfit','Manrope',system-ui,sans-serif;
  --body:'Manrope',system-ui,sans-serif;
  --r-field:12px; --r-btn:12px;
  --shadow-btn:0 14px 30px -12px rgba(231,92,67,.5);
}
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
html,body{height:100%}
body{font-family:var(--body);color:var(--ink);background:var(--pink);
  -webkit-font-smoothing:antialiased;text-rendering:optimizeLegibility;min-height:100vh;overflow-x:hidden}
::selection{background:var(--coral-soft);color:var(--ink)}
a{color:inherit;text-decoration:none}

.shell{display:grid;grid-template-columns:.82fr 1.18fr;min-height:100vh;width:100%;
  background:linear-gradient(120deg,var(--pink) 0%, var(--pink2) 46%, var(--blush) 100%)}

/* ---------- LEFT — brand + form ---------- */
.left{position:relative;z-index:2;display:flex;flex-direction:column;
  padding:clamp(30px,3.4vw,52px) clamp(30px,3.8vw,68px)}
.brand{display:flex;align-items:center;gap:13px;margin-bottom:auto}
.brand .mk{width:46px;height:46px;border-radius:14px;display:grid;place-items:center;
  background:var(--ink);box-shadow:0 10px 22px -10px rgba(31,23,18,.55)}
.brand .mk svg{width:23px;height:23px;fill:#fff}
.brand b{font-family:var(--disp);font-weight:700;font-size:22px;letter-spacing:-.4px;color:var(--ink)}
.brand small{display:block;font-size:9px;font-weight:700;color:var(--coral-deep);letter-spacing:3px;text-transform:uppercase;margin-top:2px}

.form-wrap{margin-top:34px;margin-bottom:auto;max-width:410px;width:100%;animation:fadeup .6s ease both}
.hl{font-family:var(--disp);font-weight:600;font-size:clamp(34px,3.4vw,46px);line-height:1.02;letter-spacing:-1.2px;color:var(--ink)}
.lead{color:var(--ink2);font-size:15px;line-height:1.62;margin:16px 0 28px;font-weight:500;max-width:390px}

/* validation / auth error message */
.msg{font-size:13px;line-height:1.5;padding:13px 16px;border-radius:12px;margin-bottom:18px;
  background:#FCE8E4;border:1px solid #F2BFB4;color:#B3402B;font-weight:600}
.msg p{margin:0}

.fld{margin-bottom:16px}
.fld-label{display:block;font-size:12px;font-weight:700;color:var(--ink);margin-bottom:8px}
.fld-box{display:flex;align-items:center;gap:11px;background:var(--surface);border:1.5px solid var(--line);
  border-radius:var(--r-field);padding:0 15px;transition:border-color .2s,box-shadow .2s}
.fld-box:focus-within{border-color:var(--coral);box-shadow:0 0 0 4px rgba(240,117,94,.16)}
.fld-box svg{width:18px;height:18px;stroke:var(--mute);fill:none;stroke-width:1.7;flex-shrink:0;transition:stroke .2s}
.fld-box:focus-within svg{stroke:var(--coral)}
.fld-input{flex:1;border:0;background:transparent;color:var(--ink);font-family:var(--body);
  font-size:14.5px;font-weight:600;padding:14px 0;outline:none;min-width:0}
.fld-input::placeholder{color:var(--faint);font-weight:500}
.fld-input:-webkit-autofill{-webkit-text-fill-color:var(--ink)!important;
  -webkit-box-shadow:0 0 0 1000px #fff inset!important;transition:background-color 9999s}
.eye{cursor:pointer;color:var(--mute);display:grid;place-items:center;border:0;background:none;padding:0}
.eye:hover{color:var(--coral)}
.eye svg{stroke:currentColor}

.row{display:flex;align-items:center;justify-content:space-between;margin-top:16px;gap:12px;flex-wrap:wrap}
.rem{display:flex;align-items:center;gap:9px}
.rem input[type=checkbox]{appearance:none;-webkit-appearance:none;width:19px;height:19px;border:1.5px solid var(--line2);
  border-radius:6px;cursor:pointer;position:relative;flex-shrink:0;background:var(--surface);transition:.15s}
.rem input[type=checkbox]:checked{background:var(--coral);border-color:transparent}
.rem input[type=checkbox]:checked::after{content:'';position:absolute;top:2px;left:6px;width:4px;height:9px;
  border:1.7px solid #fff;border-top:0;border-left:0;transform:rotate(45deg)}
.rem label{font-size:13px;color:var(--ink2);cursor:pointer;font-weight:500}
.forgot{font-size:13px;color:var(--coral-deep);font-weight:600;border-bottom:1px solid transparent;transition:.15s}
.forgot:hover{border-color:var(--coral-deep)}

.btn{width:100%;margin-top:24px;padding:16px 22px;border:0;border-radius:var(--r-btn);cursor:pointer;
  background:linear-gradient(135deg,var(--coral),var(--coral-deep));color:#fff;
  font-family:var(--body);font-weight:800;font-size:15px;letter-spacing:.2px;
  display:flex;align-items:center;justify-content:center;gap:10px;box-shadow:var(--shadow-btn);
  transition:transform .2s,box-shadow .2s,filter .2s}
.btn:hover{transform:translateY(-2px);filter:brightness(1.05);box-shadow:0 20px 40px -12px rgba(231,92,67,.6)}
.btn:active{transform:translateY(0)}

.foot{margin-top:34px;font-size:11px;color:var(--mute);letter-spacing:.3px}

/* ---------- RIGHT — the Bridge-story illustration ---------- */
.right{position:relative;overflow:hidden;display:flex;align-items:center;justify-content:center}
.right .art{position:absolute;inset:0;display:grid;place-items:center;padding:6%}
.right .art svg{width:100%;max-width:520px;height:auto}
/* feather the left edge of the art into the form ground so there's no seam */
.right::before{content:'';position:absolute;inset:0;pointer-events:none;
  background:linear-gradient(90deg, var(--blush) 0%, rgba(246,211,200,0) 12%)}

@keyframes fadeup{from{opacity:0;transform:translateY(12px)}to{opacity:1;transform:none}}

/* ---------- responsive ---------- */
@media(max-width:1080px){ .shell{grid-template-columns:.92fr 1.08fr} }
@media(max-width:860px){
  .shell{grid-template-columns:1fr;grid-template-rows:auto auto}
  .right{order:-1;min-height:40vh}
  .right .art{background-size:contain;background-position:center bottom}
  .left{padding:30px 26px 40px}
  .form-wrap{margin:24px auto 0;max-width:440px}
  .brand{margin-bottom:0}
}
@media(max-width:520px){ .right{min-height:32vh} .hl{font-size:32px} }
@media(prefers-reduced-motion:reduce){*{animation:none!important}}
</style>
</head>
<body>

<div class="shell">

  <!-- ===== LEFT — brand + form (live mechanics preserved) ===== -->
  <div class="left">
    <div class="brand">
      <div class="mk">
        <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3.2" y="3.2" width="7.6" height="7.6" rx="2.1"/><rect x="13.2" y="3.2" width="7.6" height="7.6" rx="2.1" opacity=".45"/><rect x="3.2" y="13.2" width="7.6" height="7.6" rx="2.1"/><rect x="13.2" y="13.2" width="7.6" height="7.6" rx="2.1" opacity=".18"/></svg>
      </div>
      <div><b><?= html_escape($brand) ?></b><small>Capacity radar</small></div>
    </div>

    <div class="form-wrap">
      <h1 class="hl">Walk in with their calendar.</h1>
      <p class="lead">OpenChair reads the public booking pages of local businesses and turns unsold
        time into a number — so your opener is their own week, not a cold pitch.</p>

      <?php if (isset($message) && $message != ''): ?>
        <div class="msg"><?php echo $message; ?></div>
      <?php endif; ?>

      <form action="<?= base_url("auth/login"); ?>" method="post" autocomplete="on">
        <?php if (function_exists('csrf_token_name') || $this->config->item('csrf_protection')): ?>
          <input type="hidden" name="<?= $this->security->get_csrf_token_name(); ?>" value="<?= $this->security->get_csrf_hash(); ?>">
        <?php endif; ?>

        <div class="fld">
          <label class="fld-label" for="identity">Email</label>
          <div class="fld-box">
            <svg viewBox="0 0 24 24"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 7l9 6 9-6"/></svg>
            <input name="identity" id="identity" type="email" class="fld-input"
              placeholder="you@example.com" required autocomplete="email" autofocus
              value="<?php echo html_escape(set_value('identity')); ?>">
          </div>
        </div>

        <div class="fld">
          <label class="fld-label" for="password">Password</label>
          <div class="fld-box">
            <svg viewBox="0 0 24 24"><rect x="4" y="10" width="16" height="11" rx="2"/><path d="M8 10V7a4 4 0 018 0v3"/></svg>
            <input name="password" type="password" id="password" class="fld-input"
              placeholder="Enter your password" required autocomplete="current-password" value="">
            <button type="button" class="eye" id="pw-toggle" aria-label="Show password" title="Show password">
              <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke-width="1.7"><path d="M2 12s4-7 10-7 10 7 10 7-4 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/></svg>
            </button>
          </div>
        </div>

        <div class="row">
          <div class="rem">
            <?php echo form_checkbox('remember','1',FALSE,'id="remember-check"');?>
            <label for="remember-check">Remember me</label>
          </div>
          <a href="<?= base_url();?>forgot_password" class="forgot">Forgot password?</a>
        </div>

        <button type="submit" class="btn">Log in</button>
      </form>
    </div>

    <div class="foot">© <?= date('Y') ?> <?= html_escape($brand) ?> · Live capacity prospecting</div>
  </div>

  <!-- ===== RIGHT — Bridge-story illustration (placeholder art) ===== -->
  <div class="right">
    <div class="art" role="img" aria-label="A week of booking slots: most booked, a block of Tuesday afternoons open, priced as unsold time">
      <svg viewBox="0 0 420 320" xmlns="http://www.w3.org/2000/svg">
        <defs>
          <linearGradient id="lgCoral" x1="0" y1="0" x2="1" y2="1">
            <stop offset="0" stop-color="#F0755E"/><stop offset="1" stop-color="#E75C43"/>
          </linearGradient>
        </defs>

        <!-- A week's grid. Booked cells are solid; the open ones are outlined and
             cluster in the midweek afternoons — which is the whole pitch: the gap
             has a shape, and the shape is where the offer goes. -->
        <g font-family="'Space Mono', ui-monospace, monospace" font-size="9" fill="#A78477">
          <text x="8"  y="30">Mon</text><text x="62" y="30">Tue</text><text x="116" y="30">Wed</text>
          <text x="170" y="30">Thu</text><text x="224" y="30">Fri</text><text x="278" y="30">Sat</text>
          <text x="332" y="30">Sun</text>
        </g>
        <?php
        // Deterministic: same picture on every render, no JS, no flicker.
        $open = [
          [0,0,0,0,0,0,1], [0,1,0,0,0,0,1], [0,1,1,0,0,0,1], [1,1,1,1,0,0,1],
          [0,1,1,0,0,0,1], [0,1,0,0,0,0,1], [0,0,0,0,0,1,1], [0,0,0,0,0,0,1],
        ];
        foreach ($open as $r => $row) {
            foreach ($row as $c => $is_open) {
                $x = 8 + $c * 54; $y = 42 + $r * 30;
                echo $is_open
                    ? '<rect x="' . $x . '" y="' . $y . '" width="46" height="22" rx="5" fill="#F0755E" fill-opacity=".16" stroke="#E75C43" stroke-opacity=".55" stroke-width="1.3"/>'
                    : '<rect x="' . $x . '" y="' . $y . '" width="46" height="22" rx="5" fill="#E3C9BE" fill-opacity=".75"/>';
            }
        }
        ?>

        <!-- The number is the product. -->
        <g transform="translate(8 292)">
          <rect x="0" y="-24" width="212" height="34" rx="17" fill="url(#lgCoral)"/>
          <text x="18" y="-2" font-family="'Space Mono', ui-monospace, monospace"
                font-size="14" font-weight="700" fill="#fff">$1,860 unsold</text>
        </g>
        <text x="232" y="290" font-family="'Space Mono', ui-monospace, monospace"
              font-size="9" fill="#A78477">38 open slots · this week</text>
      </svg>
    </div>
  </div>

</div>

<script>
// Password show/hide (no dependencies)
(function(){
  var t = document.getElementById('pw-toggle'), p = document.getElementById('password');
  if (!t || !p) return;
  t.addEventListener('click', function(){
    var show = p.type === 'password';
    p.type = show ? 'text' : 'password';
    t.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
    t.title = show ? 'Hide password' : 'Show password';
    t.style.color = show ? 'var(--coral-deep)' : '';
    p.focus();
  });
})();
</script>
</body>
</html>
