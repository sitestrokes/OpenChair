<?php $globaldata = $this->config->item('global_data');
$logo = base_url($globaldata['logo_url']);
$fav  = base_url($globaldata['fav_url']);
$site_title = $this->config->item('site_title', 'ion_auth');

// OpenChair-branded forgot-password. The engine's $whiteLabelData defaults to
// the generic agency global branding, which we intentionally do NOT use here —
// this is the OpenChair product surface. (A real per-tenant white-label logo,
// if ever passed, can still override the favicon.)
if (!empty($whiteLabelData['fav_url'])) $fav = $whiteLabelData['fav_url'];
$brand = 'OpenChair';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="icon" type="image/svg+xml" href="<?= $fav ?>">
<title>Forgot Password · <?= html_escape($brand) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<!-- Outfit (display) + Manrope (body) + Space Mono (micro-labels) — the same
     three faces the product uses, so the brand does not change between the
     login and the first screen behind it. -->
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&family=Manrope:wght@400;500;600;700;800&family=Space+Mono:wght@400;700&display=swap" rel="stylesheet">
<style>
/* ============================================================
   "BRIDGE STORY" — soft pink ground, form on the LEFT, a warm
   brand-story panel on the RIGHT. Matches the login exactly.
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
.hl{font-family:var(--disp);font-weight:600;font-size:clamp(32px,3.2vw,44px);line-height:1.04;letter-spacing:-1.1px;color:var(--ink)}
.hl em{font-style:normal;color:var(--coral)}
.lead{color:var(--ink2);font-size:15px;line-height:1.62;margin:16px 0 28px;font-weight:500;max-width:390px}

/* validation / auth message */
.msg{font-size:13px;line-height:1.5;padding:13px 16px;border-radius:12px;margin-bottom:18px;
  background:#FCE8E4;border:1px solid #F2BFB4;color:#B3402B;font-weight:600}
.msg .alert{background:none;border:0;padding:0;margin:0;color:inherit}
.msg p{margin:0}

.fld{margin-bottom:16px}
.fld-label{display:flex;justify-content:space-between;align-items:center;
  font-size:12px;font-weight:700;color:var(--ink);margin-bottom:8px}
.fld-label .link{font-size:12px;color:var(--coral-deep);font-weight:600;border-bottom:1px solid transparent;transition:.15s}
.fld-label .link:hover{border-color:var(--coral-deep)}
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

.btn{width:100%;margin-top:24px;padding:16px 22px;border:0;border-radius:var(--r-btn);cursor:pointer;
  background:linear-gradient(135deg,var(--coral),var(--coral-deep));color:#fff;
  font-family:var(--body);font-weight:800;font-size:15px;letter-spacing:.2px;
  display:flex;align-items:center;justify-content:center;gap:10px;box-shadow:var(--shadow-btn);
  transition:transform .2s,box-shadow .2s,filter .2s}
.btn:hover{transform:translateY(-2px);filter:brightness(1.05);box-shadow:0 20px 40px -12px rgba(231,92,67,.6)}
.btn:active{transform:translateY(0)}
.btn .arrow{transition:transform .2s}
.btn:hover .arrow{transform:translateX(4px)}

.foot{margin-top:34px;font-size:11px;color:var(--mute);letter-spacing:.3px}

/* ---------- RIGHT — brand-story panel ---------- */
.right{position:relative;overflow:hidden;display:flex;flex-direction:column;justify-content:center;
  padding:clamp(40px,5vw,80px)}
.right::before{content:'';position:absolute;inset:0;pointer-events:none;
  background:radial-gradient(58% 50% at 82% 16%,rgba(240,117,94,.20),transparent 66%),
             radial-gradient(46% 42% at 10% 92%,rgba(231,92,67,.12),transparent 70%)}
/* soft dotted texture */
.right::after{content:'';position:absolute;inset:0;pointer-events:none;opacity:.5;
  background-image:radial-gradient(rgba(231,92,67,.16) 1.2px,transparent 1.3px);
  background-size:22px 22px;mask-image:radial-gradient(80% 70% at 60% 40%,#000,transparent 78%)}
.story{position:relative;z-index:2;max-width:440px}
.pill{display:inline-flex;align-items:center;gap:8px;font-size:11px;font-weight:700;letter-spacing:2px;
  text-transform:uppercase;color:var(--coral-deep);background:#fff;border:1px solid var(--line);
  padding:7px 14px;border-radius:999px;margin-bottom:24px;box-shadow:0 12px 26px -18px rgba(31,23,18,.4)}
.pill .blip{width:7px;height:7px;border-radius:50%;background:var(--coral);
  box-shadow:0 0 0 0 rgba(240,117,94,.5);animation:blip 2.2s infinite}
@keyframes blip{0%{box-shadow:0 0 0 0 rgba(240,117,94,.5)}100%{box-shadow:0 0 0 10px rgba(240,117,94,0)}}
.story h2{font-family:var(--disp);font-weight:600;font-size:clamp(30px,3vw,40px);line-height:1.05;letter-spacing:-1px;color:var(--ink)}
.story h2 em{font-style:normal;color:var(--coral)}
.story p{color:var(--ink2);font-size:15px;line-height:1.62;margin-top:18px;font-weight:500;max-width:400px}
.lanes{display:flex;gap:10px;margin-top:30px;flex-wrap:wrap}
.lane{display:flex;align-items:center;gap:8px;font-size:13px;color:var(--ink2);font-weight:600;
  background:#fff;border:1px solid var(--line);border-radius:12px;padding:11px 15px;
  box-shadow:0 14px 30px -22px rgba(31,23,18,.5)}
.lane b{color:var(--ink);font-weight:700}
.lane .t{font-size:11px;color:var(--coral-deep);font-weight:700;background:var(--coral-soft);
  border-radius:20px;padding:2px 8px}

@keyframes fadeup{from{opacity:0;transform:translateY(12px)}to{opacity:1;transform:none}}

/* ---------- responsive ---------- */
@media(max-width:1080px){ .shell{grid-template-columns:.92fr 1.08fr} }
@media(max-width:860px){
  .shell{grid-template-columns:1fr;grid-template-rows:auto auto}
  .right{order:-1;min-height:auto;padding:34px 26px 8px}
  .right .story{max-width:440px;margin:0 auto}
  .lanes{margin-top:22px}
  .left{padding:24px 26px 40px}
  .form-wrap{margin:24px auto 0;max-width:440px}
  .brand{margin-bottom:0}
}
@media(max-width:520px){ .hl{font-size:30px} .story h2{font-size:27px} }
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
      <h1 class="hl">Locked out? <em>Reset</em> in seconds.</h1>
      <p class="lead">Drop in your email and we'll send a secure link to get you back to your Bridges, leads &amp; conversations.</p>

      <?php if (isset($message) && $message != ''): ?>
        <div class="msg"><?php echo $message; ?></div>
      <?php endif; ?>

      <form action="<?= base_url('auth/forgot_password') ?>" method="post">
        <input type="hidden" name="<?= $this->security->get_csrf_token_name(); ?>" value="<?= $this->security->get_csrf_hash(); ?>">

        <div class="fld">
          <label class="fld-label" for="identity">
            Email
            <a href="<?= base_url('login') ?>" class="link">Back to log in</a>
          </label>
          <div class="fld-box">
            <svg viewBox="0 0 24 24"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 7l9 6 9-6"/></svg>
            <input name="identity" id="identity" type="email" class="fld-input" placeholder="you@example.com" required autofocus>
          </div>
        </div>

        <button type="submit" class="btn">
          Send reset link
          <span class="arrow">→</span>
        </button>
      </form>
    </div>

    <div class="foot">© <?= date('Y') ?> <?= html_escape($brand) ?> · Live capacity prospecting</div>
  </div>

  <!-- ===== RIGHT — brand story ===== -->
  <div class="right">
    <div class="story">
      <div class="pill"><span class="blip"></span> Account Recovery</div>
      <h2>Your Bridges are <em>right where you left them.</em></h2>
      <p>Reset your access and pick up every lead, call, and conversation exactly where it stood.</p>
      <div class="lanes">
        <div class="lane">🎙️ <b>Browser</b> <span class="t">0s</span></div>
        <div class="lane">📞 <b>Phone</b> <span class="t">15s</span></div>
        <div class="lane">💬 <b>Pocket</b> <span class="t">60s</span></div>
      </div>
    </div>
  </div>

</div>

</body>
</html>
