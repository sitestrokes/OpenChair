<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * INSTANTCLOSE EDITOR SKIN — Solo Empire sidebar + dark re-theme for the page
 * editor (application/views/pages/editor.php), applied ONLY for Agentic Affiliates
 * Bridge templates (201..206).
 *
 * Why a skin layer instead of rewriting editor.php:
 *   editor.php is ~7000 lines of tightly-coupled AgenticAgency CSS/JS that
 *   assume the light "Bureau" shell from shared/header.php (the cream/coral top
 *   navbar) and a Bootstrap grid. Re-authoring it risks breaking the live
 *   preview iframe, autosave, typography picker, image handlers, etc. So we
 *   keep all that markup/JS intact and OVERLAY:
 *     1. the Agentic Affiliates dark sidebar (markup below),
 *     2. a scoped dark stylesheet that re-skins the existing navbar/cards/inputs
 *        to the Solo Empire design tokens.
 *   Everything is scoped under body.ic-edit-skin (set just before this partial)
 *   so no other engine page is affected.
 *
 * Loaded from editor.php AFTER shared/header (so the sidebar mounts in the body)
 * and the override <style> is emitted last so it wins the cascade.
 *
 * Expects: $count (template_id), $funnel_id. site_url()/base_url() available.
 */
// The editor uses the SAME top pill nav as the rest of the app
// (application/views/ic_shell/_ic_topnav.php) so the chrome matches Home/Bridges/
// etc. It self-computes its nav/gating from the flags Sites::edit_template already
// seeded via Ic_render_lib::sidebar_flags() (is_ic_admin / is_ic_superadmin /
// ic_can_agency / ic_can_whitelabel / ic_can_offer_finder / ic_can_telegram /
// ic_can_dfy / user_row). We mark 'campaigns' active (the
// editor is reached from a Bridge). The topnav's JS ships inside the partial; its
// CSS is provided below (scoped to body.ic-edit-skin) since the editor doesn't
// load ic_shell/layout.php.
$page = 'campaigns';
$this->load->view('ic_shell/_ic_topnav');
?>

<!-- ══════════════════════════════════════════════════════════════════════
     SOLO EMPIRE DARK SKIN — emitted last so it wins the cascade. Everything
     is scoped under body.ic-edit-skin so only this editor page is affected.
     ══════════════════════════════════════════════════════════════════════ -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&family=Manrope:wght@400;500;600;700;800&family=Space+Mono:wght@400;700&display=swap" rel="stylesheet">
<style id="ic-editor-skin-css">
body.ic-edit-skin{
  /* ── Nixtio LIGHT palette (P2 token flip). Was the dark Solo-Empire set;
       these mirror the app shell so the editor chrome matches Home/Bridges. ── */
  --ic-bg:#F3EAE4; --ic-surface:#FFFFFF; --ic-surface2:#F7F0EB; --ic-line:#EBD8CF; --ic-line2:#E1C9BD;
  --ic-txt:#1F1712; --ic-txt2:#5F5148; --ic-mute:#8E7E74; --ic-faint:#B9A79C;
  --ic-coral:#F0755E; --ic-coral2:#E75C43; --ic-coral-deep:#E75C43; --ic-coraldim:#F0755E1F; --ic-green:#12B981;
  --ic-sb:#2A1E18;        /* espresso — the active-pill / statement surface */
  --ic-disp:'Outfit',sans-serif; --ic-body:'Manrope',sans-serif; --ic-mono:'Space Mono',monospace;
  /* radius scale — mirrors layout.php (--r1..--r4) so controls round like the rest of IC */
  --ic-r1:8px; --ic-r2:11px; --ic-r3:15px; --ic-r4:20px;
  background:var(--ic-bg) !important;
  color:var(--ic-txt);
  font-family:var(--ic-body) !important;
}

/* ── 1. Replace the AA top header with the app's TOP PILL NAV ──────────────
   The editor now uses ic_shell/_ic_topnav.php (same as Home/Bridges/etc.). Its
   CSS lives in ic_shell/layout.php, which the editor doesn't load — so we mirror
   the .tn-* rules here, scoped to body.ic-edit-skin, driven by the editor's own
   --ic-* tokens (light after the P2 token flip). The topnav's JS ships inside the
   partial. */
/* Hide the AgenticAgency cream/coral top navbar entirely on this page. */
body.ic-edit-skin .c-aif.c-navbar{ display:none !important; }

body.ic-edit-skin .tn{ display:flex; align-items:center; gap:14px; padding:16px clamp(16px,3vw,34px) 10px;
  position:sticky; top:0; z-index:9000; font-family:var(--ic-body);
  background:linear-gradient(180deg, color-mix(in srgb, var(--ic-bg) 97%, transparent), color-mix(in srgb, var(--ic-bg) 90%, transparent));
  -webkit-backdrop-filter:blur(12px); backdrop-filter:blur(12px); }
/* Logo — kept in sync with the canonical rules in ic_shell/layout.php (~103-109).
   This page mounts _ic_topnav.php directly but NOT ic_shell/layout, so that
   stylesheet never loads here and these rules are the editor's only nav styling
   (they can't just be deleted — the nav renders unstyled). They must therefore be
   updated by hand whenever the real logo changes; it had drifted to an older
   design (22px logo in a white pill, round 26px mark, 16px/700 wordmark). */
body.ic-edit-skin .tn-logo{ display:inline-flex; align-items:center; gap:11px; background:transparent;
  border:0; border-radius:0; padding:0; box-shadow:none; flex-shrink:0; text-decoration:none; }
body.ic-edit-skin .tn-logo img{ height:42px; max-height:42px; max-width:210px; width:auto; object-fit:contain; display:block; }
body.ic-edit-skin .tn-mk{ width:38px; height:38px; border-radius:11px;
  background:linear-gradient(155deg,var(--ic-coral),var(--ic-coral2)); display:grid; place-items:center; }
body.ic-edit-skin .tn-mk svg{ width:21px; height:21px; fill:#2A1610; }
body.ic-edit-skin .tn-logo b{ font-family:var(--ic-disp); font-weight:600; font-size:21px; letter-spacing:-.4px; color:var(--ic-txt); }
body.ic-edit-skin .tn-pills{ display:flex; align-items:center; gap:2px; background:var(--ic-surface);
  border:1px solid var(--ic-line); border-radius:999px; padding:5px; margin:0 auto;
  box-shadow:0 12px 26px -18px rgba(31,23,18,.4); overflow-x:auto; scrollbar-width:none; max-width:100%; }
body.ic-edit-skin .tn-pills::-webkit-scrollbar{ display:none; }
body.ic-edit-skin .tn-pill{ font-family:var(--ic-disp); font-size:13.5px; font-weight:500; color:var(--ic-txt2);
  padding:9px 15px; border-radius:999px; white-space:nowrap; transition:background .15s,color .15s; border:0;
  background:transparent; cursor:pointer; display:inline-flex; align-items:center; gap:6px; text-decoration:none; }
body.ic-edit-skin .tn-pill:hover{ background:var(--ic-surface2); color:var(--ic-txt); }
body.ic-edit-skin .tn-pill.on{ background:var(--ic-sb,#2A1E18); color:#fff; font-weight:600; }
body.ic-edit-skin .tn-more{ position:relative; }
body.ic-edit-skin .tn-menu{ position:absolute; top:calc(100% + 10px); left:0; min-width:214px; background:var(--ic-surface);
  border:1px solid var(--ic-line); border-radius:18px; box-shadow:0 30px 60px -30px rgba(31,23,18,.45); padding:8px;
  display:none; z-index:9600; max-height:70vh; overflow-y:auto; }
body.ic-edit-skin .tn-more.open .tn-menu, body.ic-edit-skin .tn-acct.open .tn-menu{ display:block; animation:tnIn .16s ease; }
@keyframes tnIn{ from{opacity:0;transform:translateY(-4px)} to{opacity:1;transform:none} }
body.ic-edit-skin .tn-mgroup{ font-family:var(--ic-mono); font-size:9px; font-weight:700; letter-spacing:1.3px;
  text-transform:uppercase; color:var(--ic-faint); padding:10px 12px 5px; }
body.ic-edit-skin .tn-mitem{ display:block; font-size:13px; font-weight:600; color:var(--ic-txt2); padding:9px 12px; border-radius:12px; transition:.13s; text-decoration:none; }
body.ic-edit-skin .tn-mitem:hover{ background:var(--ic-surface2); color:var(--ic-txt); }
body.ic-edit-skin .tn-mitem.on{ background:var(--ic-coraldim); color:var(--ic-coral2); }
body.ic-edit-skin .tn-mout{ color:#B3402B; }
body.ic-edit-skin .tn-mwho{ padding:10px 12px; border-bottom:1px solid var(--ic-line); margin-bottom:6px; }
body.ic-edit-skin .tn-mwho b{ display:block; font-size:13px; color:var(--ic-txt); }
body.ic-edit-skin .tn-mwho small{ display:block; font-family:var(--ic-mono); font-size:9.5px; color:var(--ic-mute); margin-top:2px; word-break:break-all; }
body.ic-edit-skin .tn-right{ display:flex; align-items:center; gap:8px; flex-shrink:0; }
body.ic-edit-skin .tn-ic{ width:40px; height:40px; border-radius:50%; background:var(--ic-surface); border:1px solid var(--ic-line);
  display:grid; place-items:center; color:var(--ic-txt2); transition:.14s; box-shadow:0 12px 26px -18px rgba(31,23,18,.4); text-decoration:none; }
body.ic-edit-skin .tn-ic svg{ width:18px; height:18px; }
body.ic-edit-skin .tn-ic:hover{ color:var(--ic-coral2); border-color:var(--ic-coral); }
body.ic-edit-skin .tn-acct{ position:relative; }
body.ic-edit-skin .tn-acct .tn-menu{ left:auto; right:0; }
body.ic-edit-skin .tn-av{ width:40px; height:40px; border-radius:50%; border:0; cursor:pointer; font-family:var(--ic-disp);
  font-weight:700; font-size:15px; color:#2A1610; background:linear-gradient(150deg,var(--ic-coral),var(--ic-coral2));
  box-shadow:0 10px 22px -10px rgba(240,117,94,.6); }
/* Matches the canonical wrap behaviour in ic_shell/layout.php (~269-272): the
   logo row wraps first, the pill bar drops to its own full-width scrolling row,
   and .tn-right keeps its margin-left:auto so the account cluster stays right. */
@media(max-width:820px){
  body.ic-edit-skin .tn{ flex-wrap:wrap; row-gap:10px; padding:12px 14px 8px; }
  body.ic-edit-skin .tn-pills{ order:3; flex-basis:100%; justify-content:flex-start; }
  body.ic-edit-skin .tn-right{ margin-left:auto; }
}

/* ── 2. Dark page surface + FULL token remap ──────────────────────────────
   The entire editor body theme (masthead, control-panel rail, accordion/channel
   cards, inputs, typography picker, social section, buttons) is driven by the
   --aa-* CSS variables defined on .aa-editor-page. Re-point every one of them to
   the Agentic Affiliates dark palette and the whole block recolors at once. Targeted
   overrides below handle the few spots that hardcode light values. */
body.ic-edit-skin .aa-editor-page{
  /* surfaces — Nixtio LIGHT (was the dark remap; flipped in P2) */
  --aa-cream:#F3EAE4; --aa-cream-2:#F7F0EB; --aa-paper:#FFFFFF; --aa-paper-2:#F7F0EB;
  /* ink / text */
  --aa-ink:#1F1712; --aa-ink-2:#5F5148; --aa-mute:#8E7E74;
  /* accent (Nixtio coral) */
  --aa-coral:#F0755E; --aa-coral-2:#E75C43; --aa-coral-soft:#F9C9BC;
  /* hairlines */
  --aa-line:#EBD8CF; --aa-line-2:#E1C9BD; --aa-line-strong:#D9BDB0;
  /* shadow + type — soft warm diffuse (was hard black) */
  --aa-shadow:0 1px 0 rgba(255,255,255,.6) inset, 0 22px 50px -34px rgba(31,23,18,.28), 0 2px 0 rgba(31,23,18,.04);
  --aa-serif:'Outfit',sans-serif; --aa-sans:'Manrope',sans-serif; --aa-mono:'Space Mono',ui-monospace,monospace;
  background:var(--ic-bg) !important;
  color:var(--ic-txt);
  border-color:var(--ic-line) !important;
}
/* Drop the cream radial-glow + paper-grain that assumed a light page. */
body.ic-edit-skin .aa-editor-page{
  background-image:none !important;
}
body.ic-edit-skin .aa-editor-page::before{ display:none !important; }
/* On dark, white-on-coral text stays correct; the saved/disabled states use the
   remapped vars. A couple of inner panels hardcode a cream gradient — flatten. */
body.ic-edit-skin .aa-editor-page .controlDiv,
body.ic-edit-skin .aa-editor-page #accordion1,
body.ic-edit-skin .aa-editor-page #accordion{ background:transparent !important; }
body.ic-edit-skin .aa-editor-page .controlDiv::-webkit-scrollbar-track{ background:var(--ic-surface2) !important; }

/* ── 3. Re-theme the customization panels (accordion cards) ────────────── */
body.ic-edit-skin .aa-editor-page .card{
  background:var(--ic-surface) !important; border:1px solid var(--ic-line) !important;
  border-radius:14px !important; color:var(--ic-txt) !important;
}
body.ic-edit-skin .aa-editor-page .card-header,
body.ic-edit-skin .aa-editor-page .card-header.omni-primary{
  background:var(--ic-surface2) !important; border:none !important; border-radius:13px 13px 0 0 !important;
}
body.ic-edit-skin .aa-editor-page .card-header a,
body.ic-edit-skin .aa-editor-page .card-header .text-white{ color:var(--ic-txt) !important; font-family:var(--ic-disp) !important; font-weight:700 !important; }
body.ic-edit-skin .aa-editor-page .card-body,
body.ic-edit-skin .aa-editor-page .card-block{ background:var(--ic-surface) !important; color:var(--ic-txt2) !important; }
body.ic-edit-skin .aa-editor-page label,
body.ic-edit-skin .aa-editor-page .form-check-label{ color:var(--ic-txt2) !important; }
body.ic-edit-skin .aa-editor-page .heading_color,
body.ic-edit-skin .aa-editor-page h1,
body.ic-edit-skin .aa-editor-page h2,
body.ic-edit-skin .aa-editor-page h3,
body.ic-edit-skin .aa-editor-page p{ color:var(--ic-txt) !important; }

/* Inputs / selects / textareas */
body.ic-edit-skin .aa-editor-page .form-input,
body.ic-edit-skin .aa-editor-page .form-control,
body.ic-edit-skin .aa-editor-page input[type="text"],
body.ic-edit-skin .aa-editor-page input[type="url"],
body.ic-edit-skin .aa-editor-page input[type="number"],
body.ic-edit-skin .aa-editor-page textarea,
body.ic-edit-skin .aa-editor-page select{
  background:var(--ic-bg) !important; border:1px solid var(--ic-line2) !important;
  color:var(--ic-txt) !important; border-radius:10px !important;
}
body.ic-edit-skin .aa-editor-page input::placeholder,
body.ic-edit-skin .aa-editor-page textarea::placeholder{ color:var(--ic-faint) !important; }
body.ic-edit-skin .aa-editor-page .form-control:focus,
body.ic-edit-skin .aa-editor-page input:focus,
body.ic-edit-skin .aa-editor-page textarea:focus,
body.ic-edit-skin .aa-editor-page select:focus{
  border-color:var(--ic-coral) !important; box-shadow:0 0 0 3px var(--ic-coraldim) !important;
}

/* ── 4. Buttons → Solo Empire coral / ghost ───────────────────────────── */
body.ic-edit-skin .aa-editor-page .db-button,
body.ic-edit-skin .aa-editor-page .btn_31,
body.ic-edit-skin .aa-editor-page .save_action,
body.ic-edit-skin .aa-editor-page .updateThis{
  background:linear-gradient(135deg,var(--ic-coral),var(--ic-coral2)) !important;
  color:#2A1610 !important; border:none !important; border-radius:11px !important;
  font-family:var(--ic-body) !important; font-weight:700 !important;
  box-shadow:0 8px 20px -12px rgba(255,106,77,.8) !important;
}
body.ic-edit-skin .aa-editor-page .btn_4,
body.ic-edit-skin .aa-editor-page .btn_5,
body.ic-edit-skin .aa-editor-page .btn_6,
body.ic-edit-skin .aa-editor-page .page_controls{
  background:var(--ic-surface2) !important; color:var(--ic-txt2) !important;
  border:1px solid var(--ic-line2) !important; border-radius:10px !important;
}
body.ic-edit-skin .aa-editor-page .page_controls.active,
body.ic-edit-skin .aa-editor-page .btn_6.active{
  background:var(--ic-coraldim) !important; color:var(--ic-coral) !important; border-color:var(--ic-coral) !important;
}

/* Typography picker chips/tabs */
body.ic-edit-skin .aa-editor-page .aa-typo-tab,
body.ic-edit-skin .aa-editor-page .aa-typo-cat{
  background:var(--ic-bg) !important; color:var(--ic-txt2) !important; border:1px solid var(--ic-line2) !important;
}
body.ic-edit-skin .aa-editor-page .aa-typo-tab.active,
body.ic-edit-skin .aa-editor-page .aa-typo-cat.active{
  background:var(--ic-coraldim) !important; color:var(--ic-coral) !important; border-color:var(--ic-coral) !important;
}

/* ── 5. The live preview iframe stays on its own white "paper" ─────────── */
/* Do NOT recolor #templateEditor's inner content — that's the real landing
   page being edited. Just give the canvas a soft dark frame around it. */
body.ic-edit-skin .aa-editor-page .editorDiv{ background:transparent !important; }

/* ── 5b. Drop AgenticAgency editorial branding/labels on this page ─────── */
/* The "Press Console" masthead eyebrow + meta, the rotated "AGENTIC AGENCY"
   edge label, the "N°09 · CONTROL PANEL" rail tag and the "CREDENTIALS …"
   footer strip are all decorative ::before/::after content from the Bureau
   theme. Suppress them so the page reads as Agentic Affiliates. */
body.ic-edit-skin .aa-press-mast .aa-eyebrow,
body.ic-edit-skin .aa-press-mast .aa-meta{ display:none !important; }
body.ic-edit-skin .aa-press-mast h1{
  font-family:var(--ic-disp) !important; font-weight:800 !important; color:var(--ic-txt) !important;
  letter-spacing:-.6px !important;
}
body.ic-edit-skin .aa-press-mast h1 .u{ color:var(--ic-coral) !important; }
body.ic-edit-skin .aa-press-mast h1 .u::after{ background:var(--ic-coral) !important; }
body.ic-edit-skin .aa-press-mast .aa-tag{ color:var(--ic-mute) !important; }
/* Editorial rail label ("—— …  N°09 · CONTROL PANEL") above the controls. */
body.ic-edit-skin .aa-editor-page #accordion1 > .heading_color::before,
body.ic-edit-skin .aa-editor-page #accordion1 > .heading_color::after{ content:"" !important; }
body.ic-edit-skin .aa-editor-page #accordion1 > .heading_color{ color:var(--ic-txt) !important; border-bottom-color:var(--ic-line) !important; }

/* ── 5c. High-specificity #accordion1 channel cards + their inputs ────────
   These rules in editor.php win over the generic .card/.input overrides above
   (deeper selector), and several hardcode cream gradients / bright borders that
   the --aa-* remap can't reach. Re-skin them explicitly to IC dark tokens. */
/* Channel cards: real surface fill (the generic #accordion1{background:transparent}
   above only flattens the rail wrapper, not the cards). */
body.ic-edit-skin .aa-editor-page #accordion1 .card{
  background:var(--ic-surface) !important; border:1px solid var(--ic-line) !important; border-radius:12px !important;
}
body.ic-edit-skin .aa-editor-page #accordion1 .card:hover,
body.ic-edit-skin .aa-editor-page #accordion1 .card:has(.collapse.show){
  background:var(--ic-surface2) !important;
}
body.ic-edit-skin .aa-editor-page #accordion1 .card-header,
body.ic-edit-skin .aa-editor-page #accordion1 .card-header.omni-primary,
body.ic-edit-skin .aa-editor-page #accordion1 .card-body{ background:transparent !important; border-color:var(--ic-line) !important; }
body.ic-edit-skin .aa-editor-page #accordion1 .card-header > a,
body.ic-edit-skin .aa-editor-page #accordion1 .card-header a.text-white{ color:var(--ic-txt) !important; }
/* Channel-card inputs/textareas/selects — were hardcoded cream (unreadable on dark). */
body.ic-edit-skin .aa-editor-page #accordion1 .card-body .form-control,
body.ic-edit-skin .aa-editor-page #accordion1 .card-body input[type=text],
body.ic-edit-skin .aa-editor-page #accordion1 .card-body input[type=number],
body.ic-edit-skin .aa-editor-page #accordion1 .card-body input[type=url],
body.ic-edit-skin .aa-editor-page #accordion1 .card-body input[type=email],
body.ic-edit-skin .aa-editor-page #accordion1 .card-body select,
body.ic-edit-skin .aa-editor-page #accordion1 .card-body textarea,
body.ic-edit-skin .aa-editor-page #accordion1 .card-body textarea.form-control{
  background:var(--ic-bg) !important; border:1px solid var(--ic-line2) !important;
  color:var(--ic-txt) !important; border-radius:10px !important;
}
body.ic-edit-skin .aa-editor-page #accordion1 .card-body input::placeholder,
body.ic-edit-skin .aa-editor-page #accordion1 .card-body textarea::placeholder{ color:var(--ic-faint) !important; }

/* Kill stray bright-white solid borders left from --aa-ink-referencing rules. */
body.ic-edit-skin .aa-editor-page .controlEditor,
body.ic-edit-skin .aa-editor-page .controlEditor > .d-flex,
body.ic-edit-skin .aa-editor-page .heading_color.f20{ border-color:var(--ic-line) !important; }
body.ic-edit-skin .aa-editor-page .heading_color.f20{ color:var(--ic-txt) !important; }

/* Autosave toggle track + misc chrome to dark. */
body.ic-edit-skin .aa-editor-page .autosave-track{ background:var(--ic-surface2) !important; border:1px solid var(--ic-line2) !important; }
/* GREEN when autosave is ON. This skin rule uses !important, so the base
   (non-skin) :checked rule can't turn the track green on its own — override it
   here too, or the toggle stays grey when enabled. */
body.ic-edit-skin .aa-editor-page .autosave-toggle input:checked ~ .autosave-track{
	background:linear-gradient(135deg, #34C77B 0%, #12B981 100%) !important;
	border-color:#12B981 !important;
}
body.ic-edit-skin .aa-editor-page .autosave-label,
body.ic-edit-skin .aa-editor-page .autosave-status{ color:var(--ic-txt2) !important; }

/* Nested light surfaces revealed when a card expands: the collapse panels,
   the thank-you redirect toggle, the appointment-redirect card, and any bare
   inputs. (Found hidden at #fffaf7 / #fff — would flash light when opened.) */
body.ic-edit-skin .aa-editor-page .collapse,
body.ic-edit-skin .aa-editor-page .collapsing,
body.ic-edit-skin .aa-editor-page .appt-redirect-card,
body.ic-edit-skin .aa-editor-page #thankyoupage .thankyou-toggle{
  background:transparent !important;
}
body.ic-edit-skin .aa-editor-page #thankyoupage .thankyou-toggle-opt{
  background:var(--ic-surface2) !important; color:var(--ic-txt2) !important; border-color:var(--ic-line2) !important;
}
body.ic-edit-skin .aa-editor-page #thankyoupage .thankyoucheck:checked + .thankyou-toggle-opt{
  background:var(--ic-coraldim) !important; color:var(--ic-coral) !important; border-color:var(--ic-coral) !important;
}
body.ic-edit-skin .aa-editor-page .appt-redirect-card{ border:1px solid var(--ic-line) !important; border-radius:10px !important; }
/* Any bare input not caught by the typed-selectors above. */
body.ic-edit-skin .aa-editor-page input:not([type=checkbox]):not([type=radio]):not([type=range]){
  background:var(--ic-bg) !important; color:var(--ic-txt) !important; border-color:var(--ic-line2) !important;
}

/* ── 5d. Detail tokens — radius, padding, type — to match Agentic Affiliates ────
   Not just colour: round corners and pad controls on the IC radius scale
   (--ic-r1 8 / --ic-r2 11 / --ic-r3 15) so the editor's controls feel like the
   rest of the app (cards r3, inputs/buttons r2, chips/small buttons r1). */
/* Cards on --r3 (15px), headers match the top corners. */
body.ic-edit-skin .aa-editor-page #accordion1 .card,
body.ic-edit-skin .aa-editor-page .card{ border-radius:var(--ic-r3) !important; }
body.ic-edit-skin .aa-editor-page #accordion1 .card-header,
body.ic-edit-skin .aa-editor-page .card-header{ border-radius:var(--ic-r3) var(--ic-r3) 0 0 !important; }

/* Inputs / selects / textareas → --r2 radius + comfortable padding (they were
   pinched to 4px horizontal). */
body.ic-edit-skin .aa-editor-page input:not([type=checkbox]):not([type=radio]):not([type=range]),
body.ic-edit-skin .aa-editor-page select,
body.ic-edit-skin .aa-editor-page textarea,
body.ic-edit-skin .aa-editor-page .form-control,
body.ic-edit-skin .aa-editor-page #accordion1 .card-body .form-control,
body.ic-edit-skin .aa-editor-page #accordion1 .card-body input[type=text],
body.ic-edit-skin .aa-editor-page #accordion1 .card-body input[type=number],
body.ic-edit-skin .aa-editor-page #accordion1 .card-body input[type=url],
body.ic-edit-skin .aa-editor-page #accordion1 .card-body input[type=email],
body.ic-edit-skin .aa-editor-page #accordion1 .card-body select,
body.ic-edit-skin .aa-editor-page #accordion1 .card-body textarea{
  border-radius:var(--ic-r2) !important;
  padding:11px 14px !important;
  font-size:14px !important;
  line-height:1.45 !important;
}

/* Primary / save buttons → --r2, generous hit area. */
body.ic-edit-skin .aa-editor-page .db-button,
body.ic-edit-skin .aa-editor-page .btn_31,
body.ic-edit-skin .aa-editor-page .save_action,
body.ic-edit-skin .aa-editor-page .updateThis{
  border-radius:var(--ic-r2) !important; padding:11px 18px !important; font-size:13px !important; letter-spacing:.2px !important;
}
/* Secondary / pill buttons + tabs → --r1. The #accordion1-scoped and
   .aa-typo-actions-scoped variants in editor.php hardcode 6px !important at a
   higher specificity, so match that scope here to win. */
body.ic-edit-skin .aa-editor-page .btn_4,
body.ic-edit-skin .aa-editor-page .btn_5,
body.ic-edit-skin .aa-editor-page .btn_6,
body.ic-edit-skin .aa-editor-page .page_controls,
body.ic-edit-skin .aa-editor-page .aa-typo-cat,
body.ic-edit-skin .aa-editor-page .aa-typo-card,
body.ic-edit-skin .aa-editor-page #accordion1 .btn_4,
body.ic-edit-skin .aa-editor-page .aa-typo-actions .btn_4,
body.ic-edit-skin .aa-editor-page .aa-typo-actions .aa-reset-btn,
body.ic-edit-skin .aa-editor-page .aa-typo-actions .aa-save-btn{
  border-radius:var(--ic-r1) !important;
}
/* Typography font cards get a touch of padding + radius so they read as tiles. */
body.ic-edit-skin .aa-editor-page .aa-typo-card{ padding:12px 12px !important; }
/* The save-bar buttons share a consistent gap. */
body.ic-edit-skin .aa-editor-page .save_btn_section,
body.ic-edit-skin .aa-editor-page .update_btn_section{ gap:10px !important; }

/* Checkboxes pick up the coral accent (consistency with IC inputs). */
body.ic-edit-skin .aa-editor-page input[type=checkbox],
body.ic-edit-skin .aa-editor-page input[type=radio]{ accent-color:var(--ic-coral) !important; }

/* ════════════════════════════════════════════════════════════════════════
   7. LAYOUT POLISH — IC page header, framed preview canvas, fit-to-viewport.
   ════════════════════════════════════════════════════════════════════════ */

/* ── 7a. Top action bar — title (left) + the moved toolbar (right) ─────── */
/* Top-align the two sides so the actions line up with the TITLE row, not the
   center of the taller title+subtitle block. A little top breathing room under
   the sticky nav. */
body.ic-edit-skin .ic-edit-head{
  display:flex; align-items:flex-start; justify-content:space-between; gap:22px; flex-wrap:wrap; margin-bottom:16px; border-bottom:1px solid var(--ic-line);
}
body.ic-edit-skin .ic-edit-head-l{ min-width:0; flex:1 1 300px; }
body.ic-edit-skin .ic-edit-title{
  font-family:var(--ic-disp) !important; font-weight:800 !important; font-size:23px !important;
  letter-spacing:-.5px !important; color:var(--ic-txt) !important; line-height:1.2 !important; margin:0 !important;
}
body.ic-edit-skin .ic-edit-title span{ color:var(--ic-coral) !important; }
body.ic-edit-skin .ic-edit-sub{ font-size:12.5px; color:var(--ic-mute); margin-top:4px; }

/* Right cluster: page toggle · autosave · save, centered on the TITLE line
   (min-height matches the ~28px title so it sits level, not dropped low). */
body.ic-edit-skin .ic-edit-head-r{
  display:flex; align-items:center; gap:14px; flex-wrap:wrap; justify-content:flex-end; flex:0 1 auto;
  min-height:28px;
}
/* Funnel / Thank-You → a proper segmented pill control. The base theme styles
   these as folder-TABS (border-bottom:0, gradient, an injected "I."/"II." prefix);
   override all of that here (.aa-editor-page prefix to beat base specificity). */
body.ic-edit-skin .aa-editor-page .ic-eh-pages{
  gap:4px !important; background:var(--ic-surface2) !important; border:1px solid var(--ic-line) !important;
  border-radius:999px !important; padding:4px !important;
}
body.ic-edit-skin .aa-editor-page .ic-eh-pages .page_controls{
  width:auto !important; min-width:0 !important; font-family:var(--ic-body) !important; font-weight:600 !important;
  font-size:12.5px !important; letter-spacing:0 !important; text-transform:none !important;
  color:var(--ic-txt2) !important; background:none !important;
  border:0 !important; border-bottom:0 !important; border-radius:999px !important;
  clip-path:none !important;   /* base theme trapezoids these (polygon tab shape) — kill it */
  padding:8px 18px !important; margin:0 !important; box-shadow:none !important; cursor:pointer;
  transition:background .14s,color .14s; white-space:nowrap; display:inline-flex; align-items:center; gap:6px;
}
/* Drop the injected "I." / "II." roman-numeral prefix — it made them read odd. */
body.ic-edit-skin .aa-editor-page .ic-eh-pages .page_controls::before{ content:none !important; }
body.ic-edit-skin .aa-editor-page .ic-eh-pages .page_controls:hover:not(.active){
  color:var(--ic-txt) !important; background:none !important;
}
body.ic-edit-skin .aa-editor-page .ic-eh-pages .page_controls.active{
  background:var(--ic-surface) !important; color:var(--ic-coral) !important; border-color:transparent !important;
  /* soft EVEN halo (not a downward drop-shadow) so the pill reads symmetric */
  box-shadow:0 1px 3px rgba(31,23,18,.14), 0 0 0 1px rgba(31,23,18,.03) !important;
}
/* #4: mute the AUTOSAVE label so the toggle reads as secondary to Save. */
body.ic-edit-skin .ic-edit-head-r .autosave-label{
  font-family:var(--ic-mono) !important; font-size:10px !important; letter-spacing:1px !important;
  text-transform:uppercase !important; color:var(--ic-mute) !important; font-weight:600 !important;
}
/* Save-and-Publish / Save-Changes → coral pill. */
body.ic-edit-skin .ic-edit-head-r .save_btn_section .db-button,
body.ic-edit-skin .ic-edit-head-r .update_btn_section .btn_31{
  padding:11px 22px !important; font-size:14px !important;
}
/* #3: attach the split-dropdown caret to the Save button as ONE merged pill —
   Save gets a rounded LEFT end, the caret a rounded RIGHT end, no gap between.
   :not(.d-none) so Bootstrap's d-none (used to hide this on an UNPUBLISHED page,
   where only "Save and Publish" should show) still wins — otherwise this
   higher-specificity !important forced BOTH publish buttons visible. */
body.ic-edit-skin .ic-edit-head-r .update_btn_section:not(.d-none){
  display:inline-flex !important; gap:0 !important; height:auto !important; align-items:stretch !important;
}
/* Belt-and-suspenders: d-none always hides, beating the flex rule above. */
body.ic-edit-skin .ic-edit-head-r .update_btn_section.d-none,
body.ic-edit-skin .ic-edit-head-r .save_btn_section.d-none{ display:none !important; }
body.ic-edit-skin .ic-edit-head-r .update_btn_section .btn_31{
  border-radius:999px 0 0 999px !important; margin:0 !important;
}
body.ic-edit-skin .ic-edit-head-r .update_btn_section .btn_32{
  background:linear-gradient(135deg,var(--ic-coral),var(--ic-coral2)) !important; border:0 !important;
  border-left:1px solid rgba(255,255,255,.4) !important; border-radius:0 999px 999px 0 !important;
  margin:0 !important; padding:0 14px !important; display:flex !important; align-items:center !important; cursor:pointer;
}
body.ic-edit-skin .ic-edit-head-r .update_btn_section .btn_32 img{ filter:brightness(0) invert(1); opacity:.95; }
/* the single-button (unpublished) Save-and-Publish stays a full pill */
body.ic-edit-skin .ic-edit-head-r .save_btn_section .db-button{ border-radius:999px !important; }

/* Split-button dropdown menu (View Page / Publish Option) — solid light card so
   it's legible over the dark preview. The JS portals it to <body> and pins it
   fixed to escape the preview's stacking context, so it's tagged .ic-eh-menu
   (works whether it's still in the header or reparented to body). */
/* .ic-eh-menu carries the FULL layout too (base .dropdown-* rules are scoped to
   .aa-editor-page, which no longer wraps the menu once it's portalled to body). */
body.ic-edit-skin .ic-eh-menu.dropdown-section,
body.ic-edit-skin .ic-edit-head .dropdown-section{
  background:var(--ic-surface) !important; border:1px solid var(--ic-line) !important;
  border-radius:14px !important; box-shadow:0 30px 60px -28px rgba(31,23,18,.5) !important;
  padding:6px !important; min-width:210px !important; height:auto !important; overflow:visible !important;
}
body.ic-edit-skin .ic-eh-menu .dropdown-options,
body.ic-edit-skin .ic-edit-head .dropdown-options{
  background:transparent !important; padding:0 !important; display:flex !important; flex-direction:column !important;
  width:auto !important; position:static !important;
}
body.ic-edit-skin .ic-eh-menu .dropdown-options > div,
body.ic-edit-skin .ic-edit-head .dropdown-options > div{
  display:flex !important; align-items:center; gap:10px; padding:10px 12px !important; border:0 !important;
  border-radius:10px !important; transition:background .13s; white-space:nowrap;
}
body.ic-edit-skin .ic-eh-menu .dropdown-options > div:hover,
body.ic-edit-skin .ic-edit-head .dropdown-options > div:hover{ background:var(--ic-surface2) !important; }
body.ic-edit-skin .ic-eh-menu .dropdown-options a,
body.ic-edit-skin .ic-edit-head .dropdown-options a{
  color:var(--ic-txt) !important; font-family:var(--ic-body) !important; font-weight:600 !important;
  font-size:13px !important; text-decoration:none !important;
}
body.ic-edit-skin .ic-eh-menu .dropdown-options img,
body.ic-edit-skin .ic-edit-head .dropdown-options img{ width:14px !important; height:14px !important; opacity:.7; }
/* The page itself owns the page background — drop the panel border/shadow/radius
   so it reads as a full screen, not a floating card. */
body.ic-edit-skin .aa-editor-page{
  border:0 !important; border-radius:0 !important; box-shadow:none !important;
}

/* ── 7b. Kill leftover editorial labels + ornaments ───────────────────── */
/* "── PASTE-UP BOARD ──" eyebrow + skewed coral underline on "Edit here". */
body.ic-edit-skin .aa-editor-page .controlEditor .heading_color.f20::before,
body.ic-edit-skin .aa-editor-page .controlEditor .heading_color.f20::after{ content:none !important; }
body.ic-edit-skin .aa-editor-page .controlEditor .heading_color.f20{
  font-family:var(--ic-disp) !important; font-weight:700 !important; font-size:18px !important;
  color:var(--ic-txt) !important; letter-spacing:-.3px !important; margin:0 0 10px !important;
}
/* "── PROOF · LIVE PREVIEW · DO NOT DISTURB ──" band above the canvas. */
body.ic-edit-skin .aa-editor-page .editorDiv::before{
  content:"Live preview" !important;
  background:var(--ic-surface2) !important; color:var(--ic-txt2) !important;
  font-family:var(--ic-mono) !important; font-size:10px !important; letter-spacing:1.2px !important;
  text-transform:uppercase !important; border-bottom:1px solid var(--ic-line) !important;
  padding:8px 14px !important; text-align:left !important;
}
/* Kill the stray orange fullscreen-toggle "dot" (an AA affordance that floated at
   the preview's top-left). The editor no longer uses the fullscreen collapse UX. */
body.ic-edit-skin .aa-editor-page .toggleEditor.showfullcontrolls,
body.ic-edit-skin .aa-editor-page .toggleEditor.showfullscreen{ display:none !important; }
/* "Controls" rail title. */
body.ic-edit-skin .aa-editor-page #accordion1 > .heading_color{
  font-family:var(--ic-disp) !important; font-weight:700 !important; color:var(--ic-txt) !important;
}

/* ── 7c. Frame the preview as a neutral "device" ──────────────────────── */
/* The live page is itself DARK; on the dark editor it blended in. Wrap it in an
   inset surface with a clear border + radius + soft shadow so it sits ON a frame.
   The preview's own #templateEditor was hard-white (#fff) — make the mat dark so
   only the page's real background shows. */
body.ic-edit-skin .aa-editor-page .editorDiv{
  background:var(--ic-surface) !important;
  border:1px solid var(--ic-line2) !important;
  border-radius:var(--ic-r3) !important;
  box-shadow:0 24px 60px -28px rgba(31,23,18,.22), inset 0 0 0 1px rgba(255,255,255,.6) !important;
  overflow:hidden !important;
  margin-top:0 !important;
}
body.ic-edit-skin .aa-editor-page .editorDiv #templateEditor{
  background:var(--ic-bg) !important;   /* mat behind the page (page paints its own bg) */
  position:relative !important;          /* contain the preview's fixed bg layers */
}
/* The Solo Empire pages use position:fixed for their .bg/.grid/.grain atmosphere.
   Rendered inline (not in an iframe) those attach to the EDITOR viewport and tint
   the whole admin UI (the amber glow washing everything). Pin them INSIDE the
   preview canvas instead so the page looks right and the editor stays clean. */
body.ic-edit-skin .aa-editor-page #templateEditor .bg,
body.ic-edit-skin .aa-editor-page #templateEditor .grid,
body.ic-edit-skin .aa-editor-page #templateEditor .grain{
  position:absolute !important;
}
/* Tame the inner scrollbar to IC tokens. */
body.ic-edit-skin .aa-editor-page .editorDiv #templateEditor::-webkit-scrollbar-track{ background:var(--ic-surface2) !important; }
body.ic-edit-skin .aa-editor-page .editorDiv #templateEditor::-webkit-scrollbar-thumb{ background:var(--ic-line2) !important; }
body.ic-edit-skin .aa-editor-page .editorDiv #templateEditor::-webkit-scrollbar-thumb:hover{ background:var(--ic-coral) !important; }

/* ── 7d. Fit the editor to the viewport — no whole-page scroll ─────────── */
/* The page is a vertical stack: the sticky top pill nav, then the editor which
   fills the REMAINING height (100vh minus the nav) with its two columns scrolling
   internally. The body is a flex column so the nav takes its natural height and
   the editor gets the rest — no fixed nav-height magic number needed. */
body.ic-edit-skin{ height:100vh; overflow:hidden; display:flex; flex-direction:column; }
body.ic-edit-skin .tn{ flex:0 0 auto; }
body.ic-edit-skin .app{ flex:1 1 auto; min-height:0; overflow:hidden; display:flex; flex-direction:column; }
body.ic-edit-skin .mid_wrap{
  flex:1 1 auto; min-height:0; overflow:hidden; padding:0 !important; display:flex; flex-direction:column;
}
/* .mid_wrap's children are .loaderautomater, .sec3 (empty header bits) and
   .autoresp (the real editor). Only .autoresp should grow to fill; .sec3 stays
   its natural height so it doesn't steal half the viewport. */
body.ic-edit-skin .mid_wrap > .sec3{ flex:0 0 auto; }
body.ic-edit-skin .mid_wrap > .autoresp{ flex:1 1 auto; min-height:0; display:flex; }
body.ic-edit-skin .autoresp > .container-fluid{ flex:1; min-height:0; display:flex; width:100%; }
body.ic-edit-skin .aa-editor-page{
  flex:1; min-height:0; display:flex; flex-direction:column;
  padding:22px 26px 18px !important; overflow:hidden;
}
body.ic-edit-skin .aa-editor-page > .container-fluid,
body.ic-edit-skin .aa-editor-page > div:not(.ic-edit-head){ flex:1; min-height:0; }
body.ic-edit-skin .aa-editor-page .row{ height:100%; min-height:0; margin:0; }
/* Left controls column scrolls on its own. */
body.ic-edit-skin .aa-editor-page .controlDiv{
  height:100%; overflow-y:auto; padding-top:0 !important; padding-bottom:8px !important;
}
body.ic-edit-skin .aa-editor-page #accordion1{ height:auto !important; }
/* Right canvas column: toolbar fixed, canvas fills the rest + scrolls inside. */
body.ic-edit-skin .aa-editor-page .controlEditor{
  height:100%; display:flex; flex-direction:column; min-height:0;
}
body.ic-edit-skin .aa-editor-page .controlEditor > .d-flex{ flex:0 0 auto; }
body.ic-edit-skin .aa-editor-page .editorDiv{ flex:1; min-height:0; display:flex; flex-direction:column; }
body.ic-edit-skin .aa-editor-page .editorDiv #templateEditor{ flex:1; min-height:0; max-height:none !important; }

/* ════════════════════════════════════════════════════════════════════════
   8. PUBLISH MODAL (#popup2) — dark Solo Empire re-theme.
   The "Publishing Option" modal is the shared AgenticAgency .SmsModal (cream
   "dossier" theme: white card, black/coral tabs + DONE button). It's appended
   to <body>, so it sits under body.ic-edit-skin — re-skin it to IC dark tokens.
   ════════════════════════════════════════════════════════════════════════ */
body.ic-edit-skin #popup2 .modal-content{
  background:var(--ic-surface) !important; border:1px solid var(--ic-line2) !important;
  border-radius:var(--ic-r4) !important; color:var(--ic-txt) !important;
  box-shadow:0 40px 100px -40px rgba(31,23,18,.26) !important; overflow:hidden;
}
body.ic-edit-skin #popup2 .modal-content::before{ display:none !important; } /* drop coral left-edge bar */
body.ic-edit-skin #popup2 .modal-header{
  background:transparent !important; border-bottom:1px solid var(--ic-line) !important;
  border-radius:0 !important; padding:20px 24px 16px !important;
}
body.ic-edit-skin #popup2 .modal-header h5{
  font-family:var(--ic-disp) !important; font-weight:800 !important; font-size:20px !important;
  color:var(--ic-txt) !important; letter-spacing:-.4px !important;
}
/* Close button → dark circle */
body.ic-edit-skin #popup2 .modal-header .close{
  color:var(--ic-mute) !important; background:transparent !important; border:1px solid var(--ic-line2) !important;
  border-radius:50% !important; width:32px !important; height:32px !important; opacity:1 !important; text-shadow:none !important;
  display:flex !important; align-items:center !important; justify-content:center !important;
}
body.ic-edit-skin #popup2 .modal-header .close:hover{ color:var(--ic-coral) !important; border-color:var(--ic-coral) !important; background:var(--ic-coraldim) !important; }

/* Tabs — pill row on the surface2, coral active. */
body.ic-edit-skin #popup2 .nav-tabs{ background:transparent !important; border:0 !important; gap:7px; padding:14px 24px 0 !important; flex-wrap:wrap; }
body.ic-edit-skin #popup2 .nav-tabs li{ margin:0 !important; }
body.ic-edit-skin #popup2 .nav-tabs a{
  background:var(--ic-surface2) !important; color:var(--ic-txt2) !important; border:1px solid var(--ic-line2) !important;
  border-radius:var(--ic-r1) !important; font-family:var(--ic-mono) !important; font-size:10.5px !important;
  letter-spacing:.6px !important; text-transform:uppercase !important; padding:9px 13px !important; box-shadow:none !important;
  transform:none !important; transition:.13s !important;
}
body.ic-edit-skin #popup2 .nav-tabs a:hover{ color:var(--ic-txt) !important; border-color:var(--ic-mute) !important; }
body.ic-edit-skin #popup2 .nav-tabs a.active{
  background:var(--ic-coraldim) !important; color:var(--ic-coral) !important; border-color:var(--ic-coral) !important;
}
body.ic-edit-skin #popup2 .pubmodal-beta{
  background:var(--ic-coral) !important; color:#2A1610 !important; border-radius:5px !important; font-weight:700 !important;
}

/* Tab body + content text */
body.ic-edit-skin #popup2 .tab-content{ padding:24px !important; }
body.ic-edit-skin #popup2 .tab-pane,
body.ic-edit-skin #popup2 .modal-body,
body.ic-edit-skin #popup2 p,
body.ic-edit-skin #popup2 label,
body.ic-edit-skin #popup2 h5,
body.ic-edit-skin #popup2 .publish-note{ color:var(--ic-txt) !important; }
body.ic-edit-skin #popup2 .publish-note{ font-family:var(--ic-disp) !important; }
body.ic-edit-skin #popup2 a.publish_link_url{ color:var(--ic-coral) !important; }

/* Slug editor + URL preview → dark inputs/chips. */
body.ic-edit-skin #popup2 .slug-editor-label,
body.ic-edit-skin #popup2 .slug-preview-label{
  font-family:var(--ic-mono) !important; font-size:10px !important; letter-spacing:1.2px !important;
  text-transform:uppercase !important; color:var(--ic-faint) !important;
}
body.ic-edit-skin #popup2 .slug-editor-row{
  background:var(--ic-bg) !important; border:1px solid var(--ic-line2) !important; border-radius:var(--ic-r2) !important; overflow:hidden;
}
body.ic-edit-skin #popup2 .slug-editor-prefix{
  background:var(--ic-surface2) !important; color:var(--ic-mute) !important; border:0 !important;
  border-right:1px solid var(--ic-line) !important; font-family:var(--ic-mono) !important;
}
body.ic-edit-skin #popup2 .slug-input{
  background:transparent !important; color:var(--ic-txt) !important; border:0 !important; border-radius:0 !important;
}
body.ic-edit-skin #popup2 .slug-input:focus{ box-shadow:none !important; }
body.ic-edit-skin #popup2 #copylink{ color:var(--ic-txt2) !important; }
body.ic-edit-skin #popup2 #copylink:hover{ color:var(--ic-coral) !important; }
body.ic-edit-skin #popup2 .slug-preview-row{ margin-top:12px; }
body.ic-edit-skin #popup2 .slug-preview-url,
body.ic-edit-skin #popup2 code{
  background:var(--ic-bg) !important; color:var(--ic-txt2) !important; border:1px solid var(--ic-line2) !important;
  border-radius:var(--ic-r1) !important; font-family:var(--ic-mono) !important;
}
body.ic-edit-skin #popup2 .slug-status{ color:var(--ic-mute) !important; }

/* Generic inputs/selects/textareas inside the modal. */
body.ic-edit-skin #popup2 input[type=text],
body.ic-edit-skin #popup2 input[type=url],
body.ic-edit-skin #popup2 input[type=number],
body.ic-edit-skin #popup2 .form-control,
body.ic-edit-skin #popup2 select,
body.ic-edit-skin #popup2 textarea{
  background:var(--ic-bg) !important; color:var(--ic-txt) !important; border:1px solid var(--ic-line2) !important;
  border-radius:var(--ic-r2) !important;
}
body.ic-edit-skin #popup2 input:focus,
body.ic-edit-skin #popup2 textarea:focus,
body.ic-edit-skin #popup2 select:focus{ border-color:var(--ic-coral) !important; box-shadow:0 0 0 3px var(--ic-coraldim) !important; }

/* DONE / primary button → coral; CANCEL → ghost. */
body.ic-edit-skin #popup2 .publishDone,
body.ic-edit-skin #popup2 .btn_1,
body.ic-edit-skin #popup2 button[type=submit]{
  background:linear-gradient(135deg,var(--ic-coral),var(--ic-coral2)) !important; color:#2A1610 !important;
  border:0 !important; border-radius:var(--ic-r2) !important; box-shadow:0 8px 20px -12px rgba(255,106,77,.8) !important;
  font-family:var(--ic-body) !important; font-weight:700 !important; letter-spacing:.2px !important; transform:none !important;
}
body.ic-edit-skin #popup2 .publishDone:hover,
body.ic-edit-skin #popup2 .btn_1:hover{ filter:brightness(1.05) !important; transform:none !important; }
body.ic-edit-skin #popup2 .text-color,
body.ic-edit-skin #popup2 a[data-dismiss="modal"],
body.ic-edit-skin #popup2 .publishClose{ color:var(--ic-txt2) !important; }
body.ic-edit-skin #popup2 .text-color:hover,
body.ic-edit-skin #popup2 a[data-dismiss="modal"]:hover{ color:var(--ic-coral) !important; }

/* The toggle rows / cards inside the Embed-Widget + HTML-Form tabs. */
body.ic-edit-skin #popup2 .ew-toggle-row,
body.ic-edit-skin #popup2 .pubmodal-card,
body.ic-edit-skin #popup2 .cd-card{
  background:var(--ic-surface2) !important; border:1px solid var(--ic-line) !important; border-radius:var(--ic-r2) !important; color:var(--ic-txt) !important;
}
body.ic-edit-skin #popup2 .ew-toggle-text{ color:var(--ic-txt) !important; }
body.ic-edit-skin #popup2 .ew-toggle-status.on{ color:var(--ic-green) !important; }
body.ic-edit-skin #popup2 .ew-toggle-status.off{ color:var(--ic-mute) !important; }

/* Modal footer (DONE / Cancel band) — was cream. */
body.ic-edit-skin #popup2 .modal-footer{
  background:transparent !important; border-top:1px solid var(--ic-line) !important; border-radius:0 !important;
  padding:16px 24px !important;
}

/* Embed-Widget + Custom-Domain tab components (admin-only) — their many bespoke
   cards/badges/notes hardcode cream/amber/red light fills. Darken them so those
   tabs read on-brand too. */
body.ic-edit-skin #popup2 .ew-toggle-icon.off{ background:var(--ic-surface2) !important; color:var(--ic-mute) !important; }
body.ic-edit-skin #popup2 .ew-toggle-icon.on{ background:var(--ic-coraldim) !important; color:var(--ic-coral) !important; }
body.ic-edit-skin #popup2 .ew-disabled-note,
body.ic-edit-skin #popup2 .ew-help,
body.ic-edit-skin #popup2 .cd-url-block,
body.ic-edit-skin #popup2 .cd-summary-row,
body.ic-edit-skin #popup2 .cd-dns-card,
body.ic-edit-skin #popup2 .cd-dns-row{
  background:var(--ic-surface2) !important; border:1px solid var(--ic-line) !important; color:var(--ic-txt2) !important;
  border-radius:var(--ic-r1) !important;
}
body.ic-edit-skin #popup2 .cd-dns-type,
body.ic-edit-skin #popup2 .cd-dns-value,
body.ic-edit-skin #popup2 .cd-dns-name{
  background:var(--ic-bg) !important; color:var(--ic-txt) !important; border:1px solid var(--ic-line2) !important;
  font-family:var(--ic-mono) !important; border-radius:6px !important;
}
/* Status badges/hints — keep semantic colour as text on a dark tint. */
body.ic-edit-skin #popup2 .cd-badge,
body.ic-edit-skin #popup2 .cd-hint{
  background:var(--ic-surface2) !important; border:1px solid var(--ic-line) !important; border-radius:20px !important;
}
body.ic-edit-skin #popup2 .cd-badge-pending,
body.ic-edit-skin #popup2 .cd-hint-pending{ color:var(--ic-amber) !important; }
body.ic-edit-skin #popup2 .cd-badge-active,
body.ic-edit-skin #popup2 .cd-hint-active{ color:var(--ic-green) !important; }
body.ic-edit-skin #popup2 .cd-badge-broken,
body.ic-edit-skin #popup2 .cd-hint-broken{ color:var(--ic-coral) !important; }
/* Catch-all: any remaining cream/white block inside the modal panes. */
body.ic-edit-skin #popup2 .tab-pane [class*="cd-"],
body.ic-edit-skin #popup2 .tab-pane [class*="ew-"]{ color:var(--ic-txt2); }

/* ── 8b. Published-Link tab — the redesigned "launch console" layout ──────
   New structure (markup in shared/ContentModal.php, Bridge-only): a live status
   row, a terminal-style URL "readout" card where the slug is the only editable
   segment with an inline copy button, and a hint line. Not a recolor — a
   different composition from the legacy centered note. */
body.ic-edit-skin #popup2 .ic-pub{ padding:4px 2px 2px; }

/* Status row — live pulse + caption + open-link, on a recessed strip. */
body.ic-edit-skin #popup2 .ic-pub-status{
  display:flex; align-items:center; gap:12px; padding:13px 16px; margin-bottom:18px;
  background:linear-gradient(180deg,var(--ic-surface2),var(--ic-surface)); border:1px solid var(--ic-line);
  border-radius:var(--ic-r2); position:relative; overflow:hidden;
}
body.ic-edit-skin #popup2 .ic-pub-status::before{
  content:""; position:absolute; left:0; top:0; bottom:0; width:3px; background:var(--ic-green);
}
body.ic-edit-skin #popup2 .ic-pub-live{
  display:inline-flex; align-items:center; gap:7px; font-family:var(--ic-mono); font-size:11px; font-weight:700;
  letter-spacing:1.4px; text-transform:uppercase; color:var(--ic-green);
}
body.ic-edit-skin #popup2 .ic-pub-dot{ width:8px; height:8px; border-radius:50%; background:var(--ic-green);
  box-shadow:0 0 0 0 rgba(52,227,164,.5); animation:icpubpulse 1.8s infinite; }
@keyframes icpubpulse{0%{box-shadow:0 0 0 0 rgba(52,227,164,.5)}70%{box-shadow:0 0 0 8px rgba(52,227,164,0)}100%{box-shadow:0 0 0 0 rgba(52,227,164,0)}}
body.ic-edit-skin #popup2 .ic-pub-statustxt{ font-size:13px; color:var(--ic-txt2); }
body.ic-edit-skin #popup2 .ic-pub-open{
  margin-left:auto; font-family:var(--ic-mono); font-size:11px; letter-spacing:.4px; color:var(--ic-coral);
  border:1px solid var(--ic-line2); border-radius:var(--ic-r1); padding:6px 11px; white-space:nowrap; transition:.13s;
}
body.ic-edit-skin #popup2 .ic-pub-open:hover{ border-color:var(--ic-coral); background:var(--ic-coraldim); }

/* URL readout card — the hero. */
body.ic-edit-skin #popup2 .ic-pub-urlcard{
  background:var(--ic-bg); border:1px solid var(--ic-line2); border-radius:var(--ic-r3); padding:14px 16px 13px;
  box-shadow:inset 0 0 0 1px rgba(255,255,255,.6), 0 18px 40px -28px rgba(31,23,18,.22);
}
body.ic-edit-skin #popup2 .ic-pub-urlhead{ display:flex; align-items:center; justify-content:space-between; margin-bottom:10px; }
body.ic-edit-skin #popup2 .ic-pub-urllabel{
  font-family:var(--ic-mono); font-size:9.5px; font-weight:700; letter-spacing:1.5px; text-transform:uppercase; color:var(--ic-faint);
}
body.ic-edit-skin #popup2 .ic-pub-copy{
  display:inline-flex; align-items:center; gap:6px; background:var(--ic-surface2); color:var(--ic-txt2);
  border:1px solid var(--ic-line2); border-radius:var(--ic-r1); padding:6px 11px; font-family:var(--ic-mono); font-size:11px;
  letter-spacing:.4px; cursor:pointer; transition:.13s;
}
body.ic-edit-skin #popup2 .ic-pub-copy:hover{ color:var(--ic-coral); border-color:var(--ic-coral); background:var(--ic-coraldim); }
body.ic-edit-skin #popup2 .ic-pub-copy svg{ flex-shrink:0; }

/* The URL line: mono host prefix + the slug as the only editable segment. */
body.ic-edit-skin #popup2 .ic-pub-urlline{
  display:flex; align-items:center; gap:0; font-family:var(--ic-mono); font-size:14px; line-height:1.3;
  background:var(--ic-surface2); border:1px solid var(--ic-line); border-radius:var(--ic-r2); padding:11px 12px;
}
body.ic-edit-skin #popup2 .ic-pub-host{ color:var(--ic-mute); white-space:nowrap; user-select:all; }
body.ic-edit-skin #popup2 input.ic-pub-slug{
  background:transparent !important; border:0 !important; border-bottom:1.5px dashed var(--ic-line2) !important;
  border-radius:0 !important; color:var(--ic-coral) !important; font-family:var(--ic-mono) !important; font-size:14px !important;
  font-weight:700 !important; padding:1px 4px !important; min-width:60px; flex:1; outline:none !important; transition:.14s;
}
body.ic-edit-skin #popup2 input.ic-pub-slug:hover{ border-bottom-color:var(--ic-mute) !important; }
body.ic-edit-skin #popup2 input.ic-pub-slug:focus{
  border-bottom-color:var(--ic-coral) !important; box-shadow:none !important; background:var(--ic-coraldim) !important;
}
body.ic-edit-skin #popup2 .ic-pub-edit{ color:var(--ic-faint); margin-left:6px; display:inline-flex; align-items:center; }

/* Slug availability message — coloured by .ok / .bad (set by the existing JS). */
body.ic-edit-skin #popup2 .ic-pub-status-msg{
  font-family:var(--ic-mono); font-size:11px; letter-spacing:.3px; margin-top:10px; min-height:16px; color:var(--ic-mute);
}
body.ic-edit-skin #popup2 .ic-pub-status-msg.ok,
body.ic-edit-skin #popup2 .slug-status.ok{ color:var(--ic-green) !important; }
body.ic-edit-skin #popup2 .ic-pub-status-msg.bad,
body.ic-edit-skin #popup2 .slug-status.bad{ color:var(--ic-coral) !important; }

body.ic-edit-skin #popup2 .ic-pub-hint{
  font-size:12px; color:var(--ic-mute); margin:14px 2px 0; text-align:center;
}
body.ic-edit-skin #popup2 .ic-pub-hint b{ color:var(--ic-txt2); }

/* Dark backdrop. */
body.ic-edit-skin .modal-backdrop.show{ background:rgba(31,20,14,.55) !important; opacity:1 !important; }

/* Loader dots → coral (the base editor.php uses teal #00acc7 which clashes with
   the light coral system). Scoped so non-Bridge templates keep their default. */
body.ic-edit-skin .lds-ellipsis div{ background:var(--ic-coral) !important; }

/* ── 6. Responsive: on small screens let the columns stack + scroll ─────── */
@media(max-width:880px){
  /* Drop the viewport-fit lock so the control column + canvas stack and the
     page scrolls normally (the top pill bar already wraps via its own rule). */
  body.ic-edit-skin, body.ic-edit-skin .app, body.ic-edit-skin .mid_wrap,
  body.ic-edit-skin .aa-editor-page, body.ic-edit-skin .aa-editor-page .controlDiv,
  body.ic-edit-skin .aa-editor-page .controlEditor{ height:auto !important; overflow:visible !important; }
}
</style>

<script>
/* Tag the document so the scoped skin above applies, and keep the editor's
   own FOUC reveal working (it removes html.aa-editor-loading). */
document.body.classList.add('ic-edit-skin');
/* (The old collapsible-sidebar script was removed with the sidebar — the editor
   now uses the shared top pill nav, whose own JS ships inside _ic_topnav.php.) */

/* ── Split-button dropdown escape ──────────────────────────────────────────
   The View-Page / Publish-Option menu (.dropdown-section) lives in the header,
   which sits inside .aa-editor-page{overflow:hidden} (the viewport-fit clip), so
   an absolutely-positioned menu gets clipped BEHIND the preview canvas. Pin it to
   position:fixed at the caret's coordinates when it opens so it escapes the clip.
   Runs AFTER the base .btn_32 click handler (which toggles .show/display). */
(function () {
  var homeParent = null, homeNext = null;   // where the dropdown normally lives
  function place(ds, btn) {
    var r = btn.getBoundingClientRect();
    // PORTAL to <body>: the preview column builds its own stacking context, so a
    // position:fixed menu still painted UNDER it. Reparenting to <body> removes any
    // transformed/clipping ancestor so z-index actually wins.
    if (ds.parentElement !== document.body) {
      homeParent = ds.parentElement; homeNext = ds.nextSibling;
      document.body.appendChild(ds);
    }
    ds.classList.add('ic-eh-menu');
    ds.style.setProperty('position', 'fixed', 'important');
    ds.style.setProperty('top', Math.round(r.bottom + 8) + 'px', 'important');
    ds.style.setProperty('right', Math.round(window.innerWidth - r.right) + 'px', 'important');
    ds.style.setProperty('left', 'auto', 'important');
    ds.style.setProperty('z-index', '100000', 'important');
  }
  function unplace(ds) {
    ['position','top','right','left','z-index'].forEach(function (pp) { ds.style.removeProperty(pp); });
    if (homeParent && ds.parentElement === document.body) {
      homeParent.insertBefore(ds, homeNext); homeParent = homeNext = null;
    }
  }
  // CAPTURE-phase: the base .btn_32 handler calls stopPropagation(), so a bubbling
  // listener never fires. Defer so the base .show()/.hide() has run first.
  document.addEventListener('click', function (e) {
    var btn = e.target.closest && e.target.closest('.ic-edit-head .btn_32');
    if (btn) {
      var ds = btn.parentElement.querySelector('.dropdown-section') ||
               document.querySelector('.dropdown-section'); // (may already be portalled)
      setTimeout(function () {
        if (ds && getComputedStyle(ds).display !== 'none') { place(ds, btn); }
        else if (ds) { unplace(ds); }
      }, 0);
      return;
    }
    // click elsewhere → the base handler hides it; make sure we un-portal.
    setTimeout(function () {
      document.querySelectorAll('.dropdown-section').forEach(function (ds) {
        if (getComputedStyle(ds).display === 'none') { unplace(ds); }
      });
    }, 0);
  }, true);
})();

/* ══════════════════════════════════════════════════════════════════════════
   SAVE-SAFE PREVIEW CSS SCOPING
   The editor renders BOTH the Funnel (#templateEditor1) and Thank-You
   (#templateEditor2) pages into one document. Each ships its own GLOBAL <style>
   (e.g. :root{--bg:#fff} and bare .panel{…}) which bleed across pages — the
   thank-you's light :root + .panel were turning the funnel's CLAIM_FORM white.

   We CANNOT rewrite those <style> blocks in place: Save serializes
   #funnelrh/#funnelrb innerHTML back into funnels.content, so any edit to the
   <style> text gets persisted and then breaks the LIVE page (which has no
   #templateEditor wrapper). Instead, at runtime we:
     1. read each preview's original <style> text,
     2. write a SCOPED copy into <head> (this does the actual styling), and
     3. disable the original sheet for RENDERING via styleSheet.disabled — which
        does NOT change the element's innerHTML, so Save still captures the
        original, unscoped CSS. Result: correct preview + clean save round-trip.
   ══════════════════════════════════════════════════════════════════════════ */
(function () {
  // ---- tiny CSS scoper (mirror of PHP ic_scope_css) ----
  function readBlock(css, openPos) {
    var depth = 0, i = openPos, innerStart = openPos + 1, n = css.length;
    for (; i < n; i++) {
      if (css[i] === '{') depth++;
      else if (css[i] === '}') { depth--; if (depth === 0) return [innerStart, i, i + 1]; }
    }
    return [innerStart, n, n];
  }
  function scopeSelectorList(selectors, scope) {
    return selectors.split(',').map(function (sel) {
      var t = sel.trim();
      if (!t) return sel;
      if (/^(:root|html|body)\b/i.test(t)) return scope + t.replace(/^(:root|html|body)/i, '');
      return scope + ' ' + t;
    }).join(',');
  }
  function scopeCss(css, scope) {
    var out = '', i = 0, n = css.length;
    while (i < n) {
      var ch = css[i];
      if (/\s/.test(ch)) { out += ch; i++; continue; }
      if (ch === '/' && css[i + 1] === '*') {
        var end = css.indexOf('*/', i + 2);
        if (end === -1) { out += css.slice(i); break; }
        out += css.slice(i, end + 2); i = end + 2; continue;
      }
      if (ch === '@') {
        var j = i;
        while (j < n && css[j] !== '{' && css[j] !== ';') j++;
        var prelude = css.slice(i, j);
        var atword = (prelude.match(/^@([a-z-]+)/i) || [,''])[1].toLowerCase();
        if (j < n && css[j] === ';') { out += css.slice(i, j + 1); i = j + 1; continue; }
        if (j >= n) { out += css.slice(i); break; }
        var blk = readBlock(css, j), inner = css.slice(blk[0], blk[1]);
        if (['keyframes','font-face','page','counter-style','font-feature-values'].indexOf(atword) !== -1) {
          out += prelude + '{' + inner + '}';
        } else {
          out += prelude + '{' + scopeCss(inner, scope) + '}';
        }
        i = blk[2]; continue;
      }
      var brace = css.indexOf('{', i);
      if (brace === -1) { out += css.slice(i); break; }
      var sels = css.slice(i, brace), b = readBlock(css, brace), body = css.slice(b[0], b[1]);
      out += scopeSelectorList(sels, scope) + '{' + body + '}';
      i = b[2];
    }
    return out;
  }

  /* Track already-scoped <style> elements in a Set, NOT a DOM attribute — Save
     serializes #funnelrh/#funnelrb innerHTML, so any attribute we stamp on the
     original <style> would be persisted. The Set keeps the saved markup pristine. */
  var seen = (typeof WeakSet !== 'undefined') ? new WeakSet() : null;
  var seenArr = [];
  function markSeen(el){ if (seen) seen.add(el); else seenArr.push(el); }
  function isSeen(el){ return seen ? seen.has(el) : seenArr.indexOf(el) !== -1; }

  function scopePreview(containerId) {
    var container = document.getElementById(containerId);
    if (!container) return;
    var scope = '#' + containerId;
    var styles = container.querySelectorAll('style');
    var combined = '';
    for (var k = 0; k < styles.length; k++) {
      var el = styles[k];
      if (isSeen(el)) continue;
      combined += '\n' + scopeCss(el.textContent || '', scope);
      markSeen(el);
      // Disable the ORIGINAL for rendering without touching its text (save-safe).
      try { if (el.sheet) el.sheet.disabled = true; } catch (e) {}
    }
    if (combined.trim()) {
      var out = document.createElement('style');
      out.setAttribute('data-ic-scoped', containerId); // lives in <head>, never saved
      out.textContent = combined;
      document.head.appendChild(out);
    }
  }

  function run() { scopePreview('templateEditor1'); scopePreview('templateEditor2'); }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', run);
  else run();
})();
</script>

<!-- (Removed the antispam-dark override: it existed only to force the AUP modal
     dark over the OLD dark editor. The editor is light now, so the modal's own
     light styling matches — no override needed.) -->
