<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700&family=Manrope:wght@400;500;600;700;800&family=Space+Mono:wght@400;700&display=swap" rel="stylesheet">
<!-- OpenChair settings styles, ID-scoped to #ic-set so this theme styles ONLY the settings
     panel and never bleeds onto the global engine header/sidebar. Tokens mirror the light
     "Bridge Story × Nixtio" shell (ic_shell/layout.php). -->
<style>

/* OpenChair port. This block used to hard-code the host's CREAM hex values,
   which painted the entire Settings page as a bright light island bolted into
   the dark app -- the most visible theme conflict in the product.

   It now ALIASES the shell's tokens instead of restating colours. Because
   custom properties resolve lazily, Settings follows light, dark and the
   explicit toggle with no second palette to maintain. The local names are kept
   so the 117 scoped rules below need no edits, and so this file can still be
   diffed against its upstream. */
#ic-set{
  /* --surface and --line are NOT redeclared: they share their names with the
     shell's tokens, so `--surface: var(--surface)` would be a self-reference,
     which CSS treats as invalid and resolves to nothing. They inherit from
     :root already. */
  --bg:var(--ground); --surface2:var(--sunken);
  --raise:var(--raised); --line2:var(--line-soft);
  --txt:var(--tx); --txt2:var(--tx2); --mute:var(--tx3); --faint:var(--tx3);
  --coral:var(--accent); --coral2:var(--accent-tx); --coraldim:var(--accent-soft);
  --green:var(--cool); --amber:var(--mid); --blue:#4B9BE8; --violet:#9B87F5;
  --r1:12px; --r2:16px; --r3:24px; --r4:30px;
  --disp:'Outfit',system-ui,sans-serif; --body:'Manrope',system-ui,sans-serif; --mono:'Space Mono',monospace;
  --sw:236px;
}
#ic-set *{box-sizing:border-box;margin:0;padding:0}
#ic-set ::selection{background:var(--coraldim)}
#ic-set{font-family:var(--body);color:var(--tx);background:transparent;-webkit-font-smoothing:antialiased;font-size:14px;line-height:1.5}
#ic-set a{color:inherit;text-decoration:none}
#ic-set button,#ic-set input,#ic-set select,#ic-set textarea{font-family:inherit}
#ic-set ::-webkit-scrollbar{width:9px;height:9px}#ic-set ::-webkit-scrollbar-thumb{background:var(--line2);border-radius:6px}#ic-set ::-webkit-scrollbar-track{background:transparent}

#ic-set .navgroup{font-family:var(--mono);font-size:9px;font-weight:700;letter-spacing:1.2px;text-transform:uppercase;color:var(--faint);padding:14px 10px 7px}
#ic-set .navitem{display:flex;align-items:center;gap:11px;padding:9px 10px;border-radius:var(--r1);color:var(--txt2);font-size:13.5px;font-weight:500;cursor:pointer;transition:.13s;position:relative}
#ic-set .navitem svg{width:17px;height:17px;opacity:.75;flex-shrink:0;stroke:currentColor;fill:none;stroke-width:1.8;stroke-linecap:round;stroke-linejoin:round}
#ic-set .navitem:hover{background:var(--surface2);color:var(--txt)}
#ic-set .navitem.active{background:var(--coraldim);color:var(--coral)}#ic-set .navitem.active svg{opacity:1}
#ic-set .side-foot{margin-top:auto;border-top:1px solid var(--line);padding-top:12px}
#ic-set .acct{display:flex;align-items:center;gap:10px;padding:8px 10px;border-radius:var(--r1)}
#ic-set .acct .av{width:30px;height:30px;border-radius:50%;background:radial-gradient(circle at 35% 30%,var(--coral),var(--coral2));display:grid;place-items:center;flex-shrink:0;font-weight:700;color:#fff;font-size:12px}
#ic-set .acct .nm{font-size:12.5px;font-weight:600;line-height:1.2;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
#ic-set .acct .nm small{display:block;font-family:var(--mono);font-size:9px;color:var(--mute);font-weight:400;overflow:hidden;text-overflow:ellipsis}
#ic-set .logout{display:flex;align-items:center;gap:9px;margin-top:6px;padding:8px 10px;border-radius:var(--r1);color:var(--mute);font-size:12.5px;cursor:pointer;transition:.13s}
#ic-set .logout:hover{background:var(--surface2);color:var(--coral)}

#ic-set .main{display:flex;flex-direction:column;min-width:0}
#ic-set .crumb{font-family:var(--mono);font-size:11px;color:var(--mute);letter-spacing:.4px}
#ic-set .crumb b{color:var(--txt2)}
#ic-set .pill{font-family:var(--mono);font-size:11px;color:var(--txt2);background:var(--surface);border:1px solid var(--line);padding:7px 12px;border-radius:30px;white-space:nowrap}
#ic-set .pill b{color:var(--green)}
#ic-set .content{padding:30px 26px 60px;max-width:1180px;width:100%}

#ic-set .pagehead h1{font-family:var(--disp);font-weight:600;font-size:26px;letter-spacing:-.6px;margin-bottom:5px}
#ic-set .pagehead .psub{color:var(--mute);font-size:14px;max-width:640px}
#ic-set .pagehead .psub b{color:var(--txt2)}
#ic-set .headrow{display:flex;align-items:flex-end;justify-content:space-between;gap:18px;flex-wrap:wrap}

#ic-set .btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;border:none;border-radius:var(--r2);font-size:13px;font-weight:600;padding:10px 16px;cursor:pointer;transition:.14s;white-space:nowrap}
#ic-set .btn.pri{background:linear-gradient(135deg,var(--coral),var(--coral2));color:#fff;box-shadow:0 8px 20px -12px rgba(240,117,94,.6)}
#ic-set .btn.pri:hover{filter:brightness(1.05)}
#ic-set .btn.pri:disabled{background:var(--line2);color:var(--faint);cursor:not-allowed;box-shadow:none;filter:none}
#ic-set .btn.gho{background:transparent;color:var(--txt2);border:1px solid var(--line2)}#ic-set .btn.gho:hover{color:var(--txt);border-color:var(--mute);background:var(--surface)}
#ic-set .btn.sub{background:var(--surface2);color:var(--txt2)}#ic-set .btn.sub:hover{color:var(--txt)}
#ic-set .btn.sm{padding:7px 12px;font-size:12px;border-radius:var(--r1)}

#ic-set .card{background:var(--surface);border:1px solid var(--line);border-radius:var(--r3)}
#ic-set .card.pad{padding:20px 22px}
#ic-set .cardhead{display:flex;align-items:center;gap:11px;margin-bottom:16px}
#ic-set .cardhead .ico{width:30px;height:30px;border-radius:9px;background:var(--surface2);display:grid;place-items:center;font-size:15px}
#ic-set .cardhead h3{font-family:var(--disp);font-weight:700;font-size:15px}
#ic-set .cardhead .right{margin-left:auto}
#ic-set .lbl{font-family:var(--mono);font-size:10px;font-weight:700;letter-spacing:1.3px;text-transform:uppercase;color:var(--mute)}

#ic-set .field{margin-bottom:16px}
#ic-set .field label{display:block;font-size:12.5px;font-weight:600;color:var(--txt2);margin-bottom:7px}
#ic-set .field .hint{font-size:11.5px;color:var(--mute);margin-top:6px}
#ic-set .input{width:100%;background:var(--bg);border:1px solid var(--line2);color:var(--txt);border-radius:var(--r2);padding:12px 14px;font-size:14px;outline:none;transition:.15s}
#ic-set .input:focus{border-color:var(--coral);box-shadow:0 0 0 3px var(--coraldim)}
#ic-set .input::placeholder{color:var(--faint)}

#ic-set .kpis{display:grid;grid-template-columns:repeat(4,1fr);gap:14px}
#ic-set .kpi{background:var(--surface);border:1px solid var(--line);border-radius:var(--r3);padding:18px 20px;position:relative;overflow:hidden}
#ic-set .kpi:before{content:"";position:absolute;left:0;top:0;bottom:0;width:3px;background:var(--coral);opacity:.7}
#ic-set .kpi .l{font-family:var(--mono);font-size:10px;font-weight:700;letter-spacing:1px;text-transform:uppercase;color:var(--mute)}
#ic-set .kpi .n{font-family:var(--disp);font-weight:600;font-size:32px;letter-spacing:-1.2px;margin:10px 0 5px;line-height:1}
#ic-set .kpi .n.c{color:var(--coral)}#ic-set .kpi .n.g{color:var(--green)}
#ic-set .kpi .cap{font-family:var(--mono);font-size:10px;color:var(--faint)}
#ic-set .kpi .cap b{color:var(--green)}

#ic-set .grid12{display:grid;grid-template-columns:1.4fr 1fr;gap:18px}
#ic-set .grid2{display:grid;grid-template-columns:1fr 1fr;gap:14px}

#ic-set .live{display:inline-flex;align-items:center;gap:7px;font-family:var(--mono);font-size:11px;color:var(--green)}
#ic-set .live .blip{width:7px;height:7px;border-radius:50%;background:var(--green);box-shadow:0 0 0 0 rgba(31,162,116,.45);animation:blip 1.6s infinite}
@keyframes blip{0%{box-shadow:0 0 0 0 rgba(31,162,116,.45)}70%{box-shadow:0 0 0 7px rgba(31,162,116,0)}100%{box-shadow:0 0 0 0 rgba(31,162,116,0)}}

#ic-set .feed{display:flex;flex-direction:column;gap:2px}
#ic-set .feedrow{display:flex;align-items:center;gap:12px;padding:11px 6px;border-bottom:1px solid var(--line)}
#ic-set .feedrow:last-child{border-bottom:none}
#ic-set .feedrow .av{width:30px;height:30px;border-radius:9px;background:var(--surface2);display:grid;place-items:center;font-size:13px;flex-shrink:0}
#ic-set .feedrow .who{font-size:13px;font-weight:600;line-height:1.25}
#ic-set .feedrow .who small{display:block;font-family:var(--mono);font-size:10px;color:var(--mute);font-weight:400}
#ic-set .feedrow .meta{margin-left:auto;text-align:right;font-family:var(--mono);font-size:10px;color:var(--faint)}
#ic-set .chip{font-family:var(--mono);font-size:10px;font-weight:700;padding:3px 9px;border-radius:20px;letter-spacing:.3px;white-space:nowrap}
#ic-set .chip.green{background:rgba(31,162,116,.13);color:var(--green)}
#ic-set .chip.amber{background:rgba(192,122,18,.14);color:var(--amber)}
#ic-set .chip.blue{background:rgba(46,116,181,.13);color:var(--blue)}
#ic-set .chip.mute{background:var(--surface2);color:var(--mute)}
#ic-set .chip.coral{background:var(--coraldim);color:var(--coral)}

#ic-set .empty{text-align:center;padding:46px 24px}
#ic-set .empty .e{font-size:38px;margin-bottom:12px}
#ic-set .empty h4{font-family:var(--disp);font-weight:700;font-size:18px;margin-bottom:8px}
#ic-set .empty p{color:var(--mute);font-size:13.5px;max-width:440px;margin:0 auto 18px}

#ic-set .tabs{display:flex;gap:6px;border-bottom:1px solid var(--line);margin:20px 0 22px;flex-wrap:wrap}
#ic-set .tabs a{font-size:12.5px;font-weight:600;color:var(--mute);padding:9px 14px;border-bottom:2px solid transparent;cursor:pointer;transition:.13s;margin-bottom:-1px}
#ic-set .tabs a:hover{color:var(--txt2)}
#ic-set .tabs a.on{color:var(--coral);border-bottom-color:var(--coral)}
#ic-set .stab{display:none}#ic-set .stab.on{display:block}

#ic-set .setrow{display:flex;align-items:center;gap:14px;padding:14px 2px;border-bottom:1px solid var(--line)}
#ic-set .setrow:last-child{border-bottom:none}
#ic-set .setrow .si{display:flex;align-items:center;gap:13px;flex:1;min-width:0}
#ic-set .setrow .ic{width:34px;height:34px;border-radius:10px;background:var(--surface2);display:grid;place-items:center;font-size:16px;flex-shrink:0}
#ic-set .setrow b{display:block;font-size:13.5px;font-weight:600}
#ic-set .setrow span{display:block;font-size:11.5px;color:var(--mute);margin-top:1px}
#ic-set .setrow .right{margin-left:auto}

#ic-set .steps{display:flex;flex-direction:column;gap:14px;margin-top:8px}
#ic-set .step{display:flex;gap:16px;background:var(--surface);border:1px solid var(--line);border-radius:var(--r3);padding:20px 22px;align-items:flex-start}
#ic-set .step .num{width:34px;height:34px;border-radius:50%;background:var(--coraldim);color:var(--coral);font-family:var(--disp);font-weight:700;display:grid;place-items:center;flex-shrink:0}
#ic-set .step.done .num{background:rgba(31,162,116,.14);color:var(--green)}
#ic-set .step .body{flex:1}
#ic-set .step h4{font-family:var(--disp);font-weight:700;font-size:15px;margin-bottom:5px}
#ic-set .step p{color:var(--txt2);font-size:13px;line-height:1.6;margin-bottom:14px}

#ic-set .lede{font-size:15px;color:var(--txt2);line-height:1.65;max-width:660px}
#ic-set .lede b{color:var(--txt)}

#ic-set .toasts{position:fixed;right:22px;bottom:22px;display:flex;flex-direction:column;gap:10px;z-index:80}
#ic-set .toast{background:var(--surface2);border:1px solid var(--line2);border-radius:var(--r2);padding:12px 16px;font-size:13px;color:var(--txt);box-shadow:0 14px 34px -16px rgba(90,50,35,.35);max-width:340px;animation:tin .25s ease}
#ic-set .toast.err{background:#FDEDEA;border-color:#F2C4B9;color:#B3402B}
@keyframes tin{from{opacity:0;transform:translateY(8px)}to{opacity:1;transform:none}}

#ic-set .disclaimer{text-align:center;font-size:11px;color:var(--faint);margin-top:30px;max-width:580px;margin-left:auto;margin-right:auto;line-height:1.6}

/* Mobile menu button — hidden on desktop, shown in the topbar on mobile. */
#ic-set .navtoggle{display:none;align-items:center;justify-content:center;width:38px;height:38px;flex:0 0 auto;
  border:1px solid var(--line);border-radius:10px;background:var(--surface);color:var(--txt);cursor:pointer;padding:0}
#ic-set .navtoggle:hover{border-color:var(--line2)}
#ic-set .nav-backdrop{display:none}
@media(max-width:880px){
  #ic-set .app{grid-template-columns:1fr}
  /* Sidebar becomes a slide-in overlay instead of vanishing. */
  #ic-set .sidebar{position:fixed;left:0;top:0;width:var(--sw);max-width:84vw;height:100%;
    transform:translateX(-102%);transition:transform .22s ease;z-index:60;box-shadow:0 18px 50px rgba(90,50,35,.35)}
  #ic-set .app.nav-open .sidebar{transform:translateX(0)}
  #ic-set .nav-backdrop{display:block;position:fixed;inset:0;background:rgba(31,20,14,.45);
    opacity:0;pointer-events:none;transition:opacity .22s;z-index:55}
  #ic-set .app.nav-open .nav-backdrop{opacity:1;pointer-events:auto}
  #ic-set .navtoggle{display:inline-flex}
  #ic-set .kpis{grid-template-columns:repeat(2,1fr)}
  #ic-set .grid12{grid-template-columns:1fr}
}


<?php
/* White-label accent override (M3.3): re-point the coral accent tokens —
   ONLY when a valid brand accent is set. The shell (ic_shell/layout.php)
   guards this same block with `if ($brand_accent !== '')`; without the guard
   an empty accent emits `--coral: ;`, which BLANKS the coral tokens and makes
   the coral Save buttons invisible (dark text, no gradient). Resolve the
   accent locally so this partial is self-sufficient in either render path. */
$ic_set_accent = '';
if (isset($brand_accent) && is_string($brand_accent) && $brand_accent !== '') {
    $ic_set_accent = $brand_accent;
} elseif (!empty($ic_brand['accent']) && preg_match('/^#[0-9a-fA-F]{6}$/', $ic_brand['accent'])) {
    $ic_set_accent = $ic_brand['accent'];
}
if ($ic_set_accent !== ''):
?>
#ic-set{
  --coral:<?= $ic_set_accent ?>;
  --coral2:<?= $ic_set_accent ?>;
  --coraldim:<?= $ic_set_accent ?>22;
}
#ic-set .acct .av{background:radial-gradient(circle at 35% 30%,<?= $ic_set_accent ?>,<?= $ic_set_accent ?>cc)}
<?php endif; ?>


  /* Reusable searchable Bridge picker — replaces dropdown "pick a Bridge" UIs. */
  #ic-set .bp-search{max-width:440px;margin-bottom:12px}
  #ic-set .bp-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(210px,1fr));gap:10px}
  #ic-set .bp-card{display:flex;flex-direction:column;gap:5px;align-items:flex-start;text-align:left;
    padding:13px 15px;border:1.5px solid var(--line);border-radius:12px;background:var(--bg);
    text-decoration:none;cursor:pointer;transition:border-color .14s,background .14s;width:100%}
  #ic-set .bp-card:hover{border-color:var(--coral);background:var(--coraldim)}
  #ic-set .bp-card.is-active{border-color:var(--coral);background:var(--coraldim)}
  #ic-set .bp-ico{font-size:18px;line-height:1}
  #ic-set .bp-name{font-weight:700;color:var(--txt);font-size:13.5px;max-width:100%;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
  #ic-set .bp-go{font-family:var(--mono);font-size:11px;color:var(--mute)}
  #ic-set .bp-card:hover .bp-go,#ic-set .bp-card.is-active .bp-go{color:var(--coral2)}
  #ic-set .bp-empty{display:none;color:var(--mute);font-size:13px;padding:8px 2px}
  /* Filter chips + sortable table headers (Vault, Calls, …). */
  #ic-set .tchip{font-size:12px;font-weight:600;padding:6px 12px;border:1px solid var(--line);border-radius:999px;background:var(--bg);color:var(--txt2);cursor:pointer;transition:border-color .14s,color .14s,background .14s}
  #ic-set .tchip:hover{border-color:var(--line2)}
  #ic-set .tchip.is-active{border-color:var(--coral);color:var(--coral2);background:var(--coraldim)}
  #ic-set .ic-sortable{cursor:pointer;user-select:none}
  #ic-set .ic-sortable:hover{color:var(--txt)}
  #ic-set .sort-i{color:var(--coral2);font-size:9px}

  /* ═══════════ Settings redesign (polish + restructure) ═══════════ */

  /* Page-load stagger */
  @keyframes setIn{from{opacity:0;transform:translateY(10px)}to{opacity:1;transform:none}}
  #ic-set .set-anim{opacity:0;animation:setIn .5s cubic-bezier(.22,1,.36,1) forwards}
  #ic-set .set-anim.d1{animation-delay:.04s}
  #ic-set .set-anim.d2{animation-delay:.10s}
  #ic-set .set-anim.d3{animation-delay:.16s}

  /* Status-summary strip — connection overview */
  #ic-set .set-status{display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin:22px 0 4px}
  #ic-set .set-stat{position:relative;display:flex;align-items:center;gap:12px;padding:15px 16px;
    background:var(--surface);border:1px solid var(--line);border-radius:var(--r2);overflow:hidden;
    box-shadow:0 14px 34px -30px rgba(90,50,35,.5)}
  #ic-set .set-stat .ss-ic{width:38px;height:38px;border-radius:11px;display:grid;place-items:center;flex-shrink:0;
    background:var(--surface2);color:var(--mute)}
  #ic-set .set-stat .ss-ic svg{width:19px;height:19px;stroke:currentColor;fill:none;stroke-width:1.8;stroke-linecap:round;stroke-linejoin:round}
  #ic-set .set-stat .ss-tx{min-width:0}
  #ic-set .set-stat .ss-tx b{display:block;font-size:13px;font-weight:700;color:var(--txt);line-height:1.2}
  #ic-set .set-stat .ss-tx small{display:block;font-family:var(--mono);font-size:9.5px;letter-spacing:.4px;text-transform:uppercase;margin-top:3px}
  #ic-set .set-stat.ok{border-color:#BFE6D5}
  #ic-set .set-stat.ok .ss-ic{background:rgba(31,162,116,.13);color:var(--green)}
  #ic-set .set-stat.ok .ss-tx small{color:var(--green)}
  #ic-set .set-stat.ok:before{content:"";position:absolute;left:0;top:0;bottom:0;width:3px;background:var(--green)}
  #ic-set .set-stat.off .ss-tx small{color:var(--faint)}
  #ic-set .set-stat.warn .ss-ic{background:rgba(192,122,18,.14);color:var(--amber)}
  #ic-set .set-stat.warn .ss-tx small{color:var(--amber)}
  #ic-set .set-stat.warn:before{content:"";position:absolute;left:0;top:0;bottom:0;width:3px;background:var(--amber)}

  /* Segmented pill tab bar (replaces the underline .tabs styling) */
  #ic-set .tabs{display:inline-flex;gap:3px;border-bottom:none;background:var(--surface2);
    border:1px solid var(--line);border-radius:999px;padding:4px;margin:22px 0;flex-wrap:wrap}
  #ic-set .tabs a{display:inline-flex;align-items:center;gap:8px;font-size:13px;font-weight:600;color:var(--txt2);
    padding:9px 16px;border-radius:999px;border-bottom:none;margin-bottom:0;cursor:pointer;transition:.16s;white-space:nowrap}
  #ic-set .tabs a svg{width:15px;height:15px;stroke:currentColor;fill:none;stroke-width:1.9;stroke-linecap:round;stroke-linejoin:round;opacity:.7}
  #ic-set .tabs a:hover{color:var(--txt);background:var(--raise)}
  #ic-set .tabs a.on{color:#fff;background:linear-gradient(135deg,var(--coral),var(--coral2));
    box-shadow:0 8px 18px -9px rgba(240,117,94,.65)}
  #ic-set .tabs a.on svg{opacity:1}

  /* Connection card (upgraded .setrow for keys + ESPs) */
  #ic-set .conn{display:flex;align-items:flex-start;gap:14px;padding:16px;border:1px solid var(--line);
    border-radius:var(--r2);background:var(--surface);margin-bottom:12px;transition:border-color .16s,box-shadow .16s;border-bottom:1px solid var(--line)}
  #ic-set .conn:last-child{margin-bottom:0}
  #ic-set .conn:hover{border-color:var(--line2);box-shadow:0 16px 34px -28px rgba(90,50,35,.5)}
  #ic-set .conn.is-set{border-color:#CDEBDD;background:linear-gradient(180deg,rgba(31,162,116,.035),transparent)}
  #ic-set .conn .c-ic{width:42px;height:42px;border-radius:12px;display:grid;place-items:center;flex-shrink:0;
    background:var(--surface2);color:var(--txt2)}
  #ic-set .conn .c-ic svg{width:22px;height:22px;stroke:currentColor;fill:none;stroke-width:1.7;stroke-linecap:round;stroke-linejoin:round}
  #ic-set .conn.is-set .c-ic{background:rgba(31,162,116,.12);color:var(--green)}
  #ic-set .conn .c-body{flex:1;min-width:0}
  #ic-set .conn .c-title{display:flex;align-items:center;gap:9px;flex-wrap:wrap}
  #ic-set .conn .c-title b{font-family:var(--disp);font-size:15px;font-weight:700;color:var(--txt)}
  #ic-set .conn .c-desc{font-size:12.5px;color:var(--mute);margin-top:5px;line-height:1.55;max-width:640px}

  /* Toggle switch (compliance Nomorobo) */
  #ic-set .sw{position:relative;display:inline-block;width:44px;height:25px;flex-shrink:0}
  #ic-set .sw input{opacity:0;width:0;height:0;position:absolute}
  #ic-set .sw .track{position:absolute;inset:0;background:var(--line2);border-radius:999px;transition:.2s;cursor:pointer}
  #ic-set .sw .track:before{content:"";position:absolute;left:3px;top:3px;width:19px;height:19px;border-radius:50%;background:#fff;transition:.2s;box-shadow:0 1px 3px rgba(0,0,0,.25)}
  #ic-set .sw input:checked + .track{background:linear-gradient(135deg,var(--coral),var(--coral2))}
  #ic-set .sw input:checked + .track:before{transform:translateX(19px)}

</style>
