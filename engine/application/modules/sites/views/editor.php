<?php

//  print_r($templatedata[0]['Data']); exit;

// ── Agentic Affiliates dark skin gate (Bridge templates 201..207) ───────────────
// Compute BEFORE loading shared/header so the header can emit the FOUC guard
// from its very first <head> bytes — otherwise the engine's "Agentic Affiliates"
// navbar + full-screen loader paint for a frame before the dark skin applies.
$ic_tid = isset($count) ? (int) $count : 0;
$ic_use_skin = ($ic_tid >= 201 && $ic_tid <= 210);
// Flag consumed at the top of shared/header.php's <head>.
$this->load->vars(array('ic_hide_engine_chrome' => $ic_use_skin));

$this->load->view("shared/header");
$accessArr=$this->ion_auth->get_current_user_access();

// ── Agentic Affiliates dark skin (Solo Empire sidebar + dark re-theme) ──────────
// Mounts the dark sidebar and emits a scoped override stylesheet (see
// sites/_ic_editor_skin.php). Leaves the rest of the editor markup/JS untouched.
if ($ic_use_skin) { $this->load->view('sites/_ic_editor_skin'); }

// CSS-scoping helper — keeps the Funnel (#templateEditor1) and Thank-You
// (#templateEditor2) previews from bleeding each other's global :root vars and
// bare class rules into one another. Editor-only; see sites/_ic_scope_preview.php.
require_once APPPATH . 'modules/sites/views/_ic_scope_preview.php';
?>
<style>
/* Prevent FOUC: hide the editor body until the agentic-agency stylesheet
   (defined ~line 1771) has been parsed. The reveal flag is added at the
   bottom of that style block. */
html.aa-editor-loading body { visibility: hidden !important; }
</style>
<script>document.documentElement.classList.add('aa-editor-loading');</script>
<link rel="stylesheet" href="<?php echo base_url('assets/app/ui/js/tags/jquery.tag-editor.css'); ?>">


	<link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Abhaya+Libre:wght@400;500;600;700;800&family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
    <!-- Webfonts available to the customization sidebar (heading + body font pickers).
         Loaded once at editor load so previews are instant. -->
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;500;600;700&family=DM+Sans:wght@400;500;600;700&family=Inter:wght@400;500;600;700&family=Outfit:wght@400;500;600;700&family=Playfair+Display:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- <link rel="stylesheet" href="../css/bootstrap.min.css" /> -->
    <!-- <link rel="stylesheet" href="../css/font-awesome.min.css" /> -->
    <!-- <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css"> -->

    <!-- landing-pages/css/style.css + responsive.css removed: that css/ dir doesn't
         exist (the bridge templates are self-styled via their own inline <style>),
         so these only produced 404s. -->


<style>

	#footer{
		display: none;
	}
	.notice {
		display: none;
	}

	.lds-ellipsis {
	display: inline-block;
	position: relative;
	width: 80px;
	height: 80px;
	}
	.lds-ellipsis div {
	position: absolute;
	top: 33px;
	width: 13px;
	height: 13px;
	border-radius: 50%;
	background: #00acc7;
	animation-timing-function: cubic-bezier(0, 1, 1, 0);
	}
	.lds-ellipsis div:nth-child(1) {
	left: 8px;
	animation: lds-ellipsis1 0.6s infinite;
	}
	.lds-ellipsis div:nth-child(2) {
	left: 8px;
	animation: lds-ellipsis2 0.6s infinite;
	}
	.lds-ellipsis div:nth-child(3) {
	left: 32px;
	animation: lds-ellipsis2 0.6s infinite;
	}
	.lds-ellipsis div:nth-child(4) {
	left: 56px;
	animation: lds-ellipsis3 0.6s infinite;
	}
	@keyframes lds-ellipsis1 {
	0% {
		transform: scale(0);
	}
	100% {
		transform: scale(1);
	}
	}
	@keyframes lds-ellipsis3 {
	0% {
		transform: scale(1);
	}
	100% {
		transform: scale(0);
	}
	}
	@keyframes lds-ellipsis2 {
	0% {
		transform: translate(0, 0);
	}
	100% {
		transform: translate(24px, 0);
	}
	}
	.tag-editor {
		height: 120px !important;
		padding: 15px;
		border-radius: 10px;
	}
	.Button_buttonClass__1gaEF.Button_secondary__kDwX_, .Button_buttonClass__1gaEF.Button_secondary__kDwX_:visited {
		color: #4c515d;
		background-color: #e4e7ed;
	}
	.Button_buttonClass__1gaEF {
		font-family: 'Akkurat';
		font-size: 15px;
		font-weight: 400;
		line-height: 24px;
		letter-spacing: 0;
		color: #4c515d;
		position: relative;
		display: inline-block;
		padding: 6px 30px;
		cursor: pointer;
		-webkit-transition: all 0.25s ease;
		/* transition: all 0.25s ease; */
		text-align: center;
		white-space: nowrap;
		text-decoration: none;
		color: #ffffff;
		border: 0;
		border-radius: 18px;
		outline: 0;
		overflow: hidden;
		background-color: #0069ff;
	}
	#accordion .card-header{
		cursor:pointer;
	}
	.loaderautomater{
		display:none;
		position: fixed;
		left: 0px;
		top: 0px;
		width: 100%;
		height: 100%;
		/* Above the image modal (z 100050) — this loader shows during the Pixabay
		   "Use This Image" re-host while the modal is still open, so it must sit on
		   top of it, not behind it. */
		z-index: 100060;
		background: rgba(0, 0, 0, 0.29);
	}


	.bg-background{cursor: pointer;}
</style>

<style>

	/* new theme css  */

	.controlDiv{
		background: #ffffff;
		padding: 30px 25px;
		border-radius: 35px;
	}
	#templateEditor{
		background: white;
		margin: 0px 5px;
		/* padding: 23px;
		border-radius: 31px; */
	}
	.editorDiv{
		background: #ffffff;
		padding: 13px;
		border-radius: 31px;
		height: 500px;
    	overflow: hidden;
	}

	#templateEditor{ height: 474px; z-index: 999; overflow: scroll; scroll-behavior: smooth; overflow-x: hidden; zoom: 0.8;}


	.showfullscreen{
		right: -6px;
    	top: 15px;
	}
	.showfullscreen img{
		background: #FF6200;
		padding: 6px;
		border-radius: 10px;
	}

	.showfullcontrolls{
		top: 70px;
		left: 8px;
		display: none;
		z-index: 9999;
	}

	.showfullcontrolls img{
		background: #FF6200;
		padding: 6px;
		border-radius: 10px;
		rotate: 180deg;
	}
	.dropdown-options{
		display: flex;
		flex-direction: column;
		position: absolute;
		top: 40px;
		left: -40px;
		background: white;
		padding: 10px 5px 10px 15px;
		z-index: 9999;
		width: 140px;
		border-radius: 10px;
	}

	.dropdown-options a{
		color: #220E01;
		font-size: 14px;
		line-height: 35px;
		font-family: 'sf-pro-display', sans-serif;
	}

	.dropdown-section{
		display: none;
	}
	.controlDiv .card{ border: none; position: relative; }
	.controlDiv .card-header { 
		height: 44px;
    	background: #FFF3EB;
		border: none;
		outline: none;
		border-radius: 15px;
		display: flex;
		justify-content: space-between;
		cursor: pointer;
	}
	.controlDiv .card-header a{ 
		color: #FF6200!important;
		font-size: 18px;
		margin-top: 2px;
		line-height: 25px;
		font-family: 'sf-pro-display', sans-serif;
		cursor: pointer;
	}

	.controlDiv .card-header img {
		position: absolute;
		top: 15px;
		right: 15px;
		width: 15px;
		height: 15px;
		cursor: pointer;
	}

	.controlDiv .card .collapse, .controlDiv .card .collapsing {
		background: #FFFAF7;
		border: none;
		outline: none;
		border-radius: 15px;
	}
	.controlDiv .card{
		background: #FFF3EB;
		border-radius: 15px;
		margin-bottom: 15px;
	}

	.controlDiv .label{
		background: #A59891!important;
		font-size: 12px;
		line-height: 22px;
		font-family: 'sf-pro-display', sans-serif;
	}
	.controlDiv select{
		border: 1px solid #F2EBE6;
		border-radius: 8px;
		background: #ffffff00  url(../../../assets/new-images/dropdown.svg) no-repeat right center;
		font-family: 'sf-pro-display', sans-serif;
		font-size: 17px;
	}

	.controlDiv textarea{
		height: 100px;
		border: 1px solid #F2EBE6;
		border-radius: 8px;
		font-family: 'sf-pro-display', sans-serif;
		font-size: 16px;
		background: none;
	}

	.controlDiv input[type="text"]{
		border: 1px solid #F2EBE6;
		border-radius: 8px;
		font-family: 'sf-pro-display', sans-serif;
		font-size: 17px;
		background: none;
		height:50px;
	}
	

	.db-button {
            background-color: #fff1eb;
            border-radius: 25px;
            background: #FFEEE3;
            border: none;
            padding: 17px 27px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: space-between;
            transition: background-color 0.3s ease;
            color: #06164C;
            font-family: "SF Pro Display";
            font-size: 16px;
            font-style: normal;
            font-weight: 400;
            line-height: 26px;
            letter-spacing: -0.8px;
        }

        .db-button:hover {
            background-color: #ffe4d6;
        }

        .db-button svg {
            width: 1rem;
            height: 1rem;
            transition: transform 0.3s ease;
        }

        .db-button:hover svg {
            transform: translateX(4px);
        }

</style>


<style>
	/* ── Voice Preview Cards ── */
	.vc-voice-grid {
		display: grid;
		grid-template-columns: repeat(3, 1fr);
		gap: 8px;
		margin-top: 8px;
	}
	.vc-voice-card {
		position: relative;
		background: #f9f7f4;
		border: 2px solid #e7e0d8;
		border-radius: 8px;
		padding: 10px 10px 36px;
		cursor: pointer;
		transition: all 0.2s ease;
	}
	.vc-voice-card:hover {
		border-color: #FF6200;
		background: #fff8f0;
	}
	.vc-voice-card.active {
		border-color: #FF6200;
		background: #fff3e6;
		box-shadow: 0 0 0 1px #FF6200;
	}
	.vc-voice-card-top {
		display: flex;
		justify-content: space-between;
		align-items: center;
		margin-bottom: 2px;
	}
	.vc-voice-name {
		font-weight: 700;
		font-size: 13px;
		color: #1a1a1a;
	}
	.vc-voice-gender {
		font-size: 10px;
		font-weight: 600;
		color: #fff;
		background: #a8a29e;
		border-radius: 3px;
		padding: 1px 5px;
		line-height: 1.4;
	}
	.vc-voice-desc {
		font-size: 11px;
		color: #78716c;
	}
	.vc-voice-play {
		position: absolute;
		bottom: 6px;
		right: 6px;
		width: 26px;
		height: 26px;
		border-radius: 50%;
		border: none;
		background: #FF6200;
		color: #fff;
		font-size: 10px;
		display: flex;
		align-items: center;
		justify-content: center;
		cursor: pointer;
		transition: transform 0.15s ease, background 0.15s ease;
	}
	.vc-voice-play:hover {
		transform: scale(1.15);
		background: #e55800;
	}
	.vc-voice-play.playing {
		background: #d44a00;
		animation: vc-pulse 1s infinite;
	}
	@keyframes vc-pulse {
		0%, 100% { box-shadow: 0 0 0 0 rgba(255,98,0,0.4); }
		50% { box-shadow: 0 0 0 6px rgba(255,98,0,0); }
	}
	@media (max-width: 768px) {
		.vc-voice-grid { grid-template-columns: repeat(2, 1fr); }
	}

	/* ─────────────────────────────────────────────────────────────────
	   Customization sidebar controls (Brand Colors, Typography, Image
	   Size, Section Visibility). Scoped to the existing accordion so the
	   new sections inherit card / form styling and only add the bits
	   that don't exist yet — colour swatches, range readouts, etc.
	   ───────────────────────────────────────────────────────────────── */


	/* ═══════════════════════════════════════════════════════════════
	   TYPOGRAPHY PANEL — editorial type drawer
	   Lives inside the dossier accordion. Reads as a hand-set printer's
	   catalogue: hairline rules, mono-caps eyebrows as taxonomy, coral
	   used sparingly as the only saturated accent. Theme tokens only.
	   ═══════════════════════════════════════════════════════════════ */

	/* Body opens with a deliberate band of breathing room so the first
	   control (Heading/Body tabs) doesn't crowd the accordion header,
	   and closes with terminal cushion that mirrors the column's outer
	   30px rhythm — the reset button reads as a finished line, not as
	   chrome amputated by the card edge. */
	.aa-typo-body { padding:22px 16px 30px; background:var(--aa-cream); }

	/* ── Heading / Body switch — ledger tab pair ─────────────────────
	   Two mono-caps labels under a hairline. Active gets a 2px coral
	   underline; the inactive sits flush with no chrome at all. The
	   absence of a pill background is the point — it reads like a
	   typographic anchor, not a UI control. */
	.aa-typo-tabs {
		display:flex; gap:0; margin:0 0 18px;
		border-bottom:1px solid rgba(26,20,16,.10);
	}
	.aa-typo-tab {
		flex:1; padding:9px 4px 11px; margin-bottom:-1px;
		border:none; background:transparent;
		font-family:var(--aa-mono);
		font-size:10px; font-weight:700; letter-spacing:.26em;
		text-transform:uppercase; color:rgba(59,47,38,.55);
		cursor:pointer; border-bottom:2px solid transparent;
		transition:color .22s ease, border-color .22s ease;
	}
	.aa-typo-tab:hover { color:var(--aa-ink-2); }
	.aa-typo-tab.active {
		color:var(--aa-coral-2);
		border-bottom-color:var(--aa-coral);
	}

	/* ── "Currently" caption ─────────────────────────────────────────
	   Editorial caption: mono-caps eyebrow on its own line, font name
	   beneath in the dossier's serif weight. No coloured surface, no
	   border. The typographic hierarchy carries the meaning. */
	.aa-typo-current {
		padding:0 0 16px; margin-bottom:14px;
		border-bottom:1px solid rgba(26,20,16,.06);
	}
	.aa-typo-current-label {
		display:block;
		font-family:var(--aa-mono);
		font-size:9px; font-weight:700; letter-spacing:.30em;
		text-transform:uppercase; color:rgba(59,47,38,.55);
		margin-bottom:4px;
	}
	#aaTypoCurrentName {
		display:block;
		font-family:var(--aa-serif);
		font-size:15px; font-weight:600; color:var(--aa-ink);
		letter-spacing:-.005em; line-height:1.25;
	}

	/* ── Search field — hairline, no pill ───────────────────────────
	   A single bottom rule expands on focus. Reads like a printer's
	   index-card field, not a SaaS search bar. */
	/* Search field — flex row so the magnifying-glass and the placeholder
	   are siblings on the same baseline. Avoids absolute-positioning
	   overlap entirely; icon and input each own their own column. The
	   bottom rule lives on the WRAP and lights up coral on focus-within. */
	.aa-typo-search-wrap {
		display:flex; align-items:center; gap:10px;
		padding:0 2px;
		border-bottom:1px solid rgba(26,20,16,.14);
		margin-bottom:14px;
		transition:border-color .22s ease;
	}
	.aa-typo-search-wrap:focus-within { border-bottom-color:var(--aa-coral); }
	.aa-typo-search-icon {
		flex-shrink:0; color:rgba(59,47,38,.5);
		font-size:11px; line-height:1; pointer-events:none;
	}
	.aa-typo-search-wrap:focus-within .aa-typo-search-icon { color:var(--aa-coral); }
	.aa-typo-search {
		flex:1 1 auto; min-width:0;
		padding:9px 0; border:none; background:transparent;
		font-family:var(--aa-serif);
		font-size:13px; color:var(--aa-ink);
		outline:none;
	}
	.aa-typo-search::placeholder {
		font-family:var(--aa-mono); font-size:10.5px;
		letter-spacing:.18em; text-transform:uppercase;
		color:rgba(59,47,38,.4);
	}

	/* ── Category tags ───────────────────────────────────────────────
	   Hairline rectangles with mono-caps copy. Active state inverts to
	   ink fill (not coral) so the coral remains the rare accent — it's
	   the underline on the active tab and the rule under search focus.
	   The chips are dossier classification marks, not buttons. */
	.aa-typo-cats {
		display:flex; flex-wrap:wrap; gap:5px; margin-bottom:14px;
	}
	.aa-typo-cat {
		padding:5px 10px 4px; border:1px solid rgba(26,20,16,.18);
		border-radius:2px; background:transparent;
		font-family:var(--aa-mono);
		font-size:9px; font-weight:700; letter-spacing:.20em;
		text-transform:uppercase; color:var(--aa-ink-2);
		cursor:pointer;
		transition:background-color .18s ease, color .18s ease, border-color .18s ease;
	}
	.aa-typo-cat:hover {
		border-color:var(--aa-ink); color:var(--aa-ink);
		background:rgba(26,20,16,.04);
	}
	.aa-typo-cat.active {
		background:var(--aa-ink); color:var(--aa-cream);
		border-color:var(--aa-ink);
	}

	/* ── Font grid — type specimen plates ────────────────────────────
	   Each card is a small specimen: a generous "Aa" preview in the
	   font's own face, the family name set as a mono caption beneath
	   it. Hover deepens the cream and thickens the rule by 0.5px (no
	   transform — that read as gimmicky here). Selected gets a coral
	   left rule, the way a librarian flags a card with a coloured tab. */
	.aa-typo-grid {
		display:grid; grid-template-columns:1fr 1fr; gap:8px;
		max-height:320px; overflow-y:auto;
		padding:2px 6px 2px 0;
		margin-bottom:14px;
	}
	.aa-typo-grid::-webkit-scrollbar { width:6px; }
	.aa-typo-grid::-webkit-scrollbar-track { background:transparent; }
	.aa-typo-grid::-webkit-scrollbar-thumb { background:rgba(26,20,16,.18); border-radius:3px; }
	.aa-typo-grid::-webkit-scrollbar-thumb:hover { background:var(--aa-coral); }
	.aa-typo-card {
		position:relative;
		padding:12px 12px 11px 14px;
		border:1px solid rgba(26,20,16,.10);
		border-radius:3px;
		background:#FFFDF7;
		cursor:pointer; text-align:left;
		display:flex; flex-direction:column; gap:6px;
		transition:background-color .18s ease, border-color .18s ease, box-shadow .22s ease;
	}
	.aa-typo-card::before {
		content:""; position:absolute; left:0; top:10%; bottom:10%; width:2px;
		background:transparent; transition:background-color .22s ease;
	}
	.aa-typo-card:hover {
		background:var(--aa-cream-2);
		border-color:rgba(26,20,16,.20);
	}
	.aa-typo-card.selected {
		background:#FFFDF7;
		border-color:rgba(26,20,16,.16);
		box-shadow:0 1px 0 rgba(26,20,16,.04), 0 6px 16px -10px rgba(199,46,46,.30);
	}
	.aa-typo-card.selected::before { background:var(--aa-coral); }
	.aa-typo-card-preview {
		/* Hardcoded dark ink — the card bg is cream, but the IC dark skin flips
		   --aa-ink to a light value (#eef1fb), which would make the "Aa" preview
		   near-invisible on the cream card. Use a fixed dark like the card name. */
		font-size:26px; line-height:1; color:#1A1410 !important;
		font-weight:500; letter-spacing:-.01em;
	}
	.aa-typo-card-name {
		font-family:var(--aa-mono);
		font-size:9px; font-weight:600; letter-spacing:.08em;
		text-transform:uppercase;
		color:rgba(59,47,38,.62);
		white-space:nowrap; overflow:hidden; text-overflow:ellipsis;
	}
	.aa-typo-card.selected .aa-typo-card-name { color:var(--aa-coral-2); }

	.aa-typo-empty {
		grid-column:1 / -1; padding:32px 12px;
		text-align:center; color:rgba(59,47,38,.5);
		font-family:var(--aa-mono);
		font-size:10px; letter-spacing:.30em; text-transform:uppercase;
	}

	/* Reset / Save action row beneath the font grid. Mono caps on both;
	   Save fills with coral as the singular committed action, Reset stays
	   ghost so the visual weight signals which is destructive. */
	.aa-typo-actions {
		display:flex; gap:8px; margin-top:4px;
	}
	.aa-typo-actions .btn_4 {
		flex:1; padding:10px 14px;
		border-radius:3px; cursor:pointer;
		font-family:var(--aa-mono);
		font-size:10.5px; font-weight:700;
		letter-spacing:.22em; text-transform:uppercase;
		display:inline-flex; align-items:center; justify-content:center;
		gap:6px; transition:all .18s ease;
	}
	.aa-typo-actions .aa-reset-btn {
		background:transparent !important;
		color:var(--aa-ink-2) !important;
		border:1px solid rgba(26,20,16,.18) !important;
	}
	.aa-typo-actions .aa-reset-btn:hover {
		background:rgba(26,20,16,.04) !important;
		border-color:var(--aa-ink) !important;
		color:var(--aa-ink) !important;
	}
	.aa-typo-actions .aa-save-btn {
		background:var(--aa-coral) !important;
		color:var(--aa-cream) !important;
		border:1px solid var(--aa-coral) !important;
	}
	.aa-typo-actions .aa-save-btn:hover {
		background:var(--aa-coral-2) !important;
		border-color:var(--aa-coral-2) !important;
	}
	.aa-typo-actions .aa-save-btn:disabled,
	.aa-typo-actions .aa-save-btn.is-saving {
		opacity:.55; cursor:wait;
	}
	.aa-typo-save-status {
		margin-top:10px; min-height:14px;
		font-family:var(--aa-mono);
		font-size:10px; letter-spacing:.20em; text-transform:uppercase;
		color:rgba(59,47,38,.55);
	}
	.aa-typo-save-status.is-success { color:var(--aa-ok, #5BAE7E); }
	.aa-typo-save-status.is-error   { color:var(--aa-coral-2); }

	

	/* Always-visible visibility section in Social Media Controls. Sits
	   above the (conditional) per-platform link editors. */
	.aa-social-vis-section {
		padding-bottom:14px; margin-bottom:14px;
		border-bottom:1px solid rgba(26,20,16,.08);
	}
	.aa-social-vis-section:last-child { border-bottom:none; margin-bottom:0; padding-bottom:0; }
	.aa-social-vis-label {
		font-family:var(--aa-mono);
		font-size:9.5px; font-weight:700; letter-spacing:.30em;
		text-transform:uppercase; color:rgba(59,47,38,.55);
		margin-bottom:10px;
	}

	/* Visibility row — flex pair with the checkbox sitting in its own
	   leading column. Bootstrap's .form-check-input is absolute-positioned
	   with negative margin by default which collides into the label, so
	   the !important reset below puts it back into normal flow. */
	.aa-visibility-row {
		display:flex; align-items:flex-start; gap:11px;
		padding:11px 14px; margin-bottom:12px;
		background:transparent;
		border:1px solid rgba(26,20,16,.10);
		border-left:2px solid var(--aa-coral);
		border-radius:2px;
	}
	.aa-visibility-row .form-check-input {
		position:static !important;
		margin:1px 0 0 0 !important;
		padding:0 !important;
		cursor:pointer; flex-shrink:0;
		width:15px; height:15px;
		accent-color:var(--aa-coral);
	}
	.aa-visibility-row .form-check-label {
		margin:0; padding:0;
		flex:1 1 auto; min-width:0;
		font-size:12.5px; font-weight:500; color:var(--aa-ink-2);
		cursor:pointer; user-select:none; line-height:1.4;
	}

	.aa-reset-btn { margin-top:8px; }
	.aa-danger-btn {
		background:var(--aa-coral-2) !important;
		color:var(--aa-cream) !important;
		border:1px solid var(--aa-coral-2) !important;
		font-family:var(--aa-mono) !important;
		font-size:10.5px !important; font-weight:700 !important;
		letter-spacing:.20em !important; text-transform:uppercase !important;
	}
	.aa-danger-btn:hover { background:var(--aa-ink) !important; border-color:var(--aa-ink) !important; }

	/* ─── Niche template — inline placeholder action popover ───────────
	   Surfaces ONLY when the user clicks the .niche-service-upload-container
	   block in the Default Niche (#124) template. Two choices: upload an
	   image (delegates to the existing image picker) or remove the whole
	   placeholder section. Cream paper, hairline border, mono caps —
	   reads as part of the dossier system, not a stock browser dialog. */
	.aa-niche-pop {
		position:absolute; top:8px; right:8px; z-index:50;
		min-width:240px; padding:14px 16px 12px;
		background:var(--aa-cream);
		border:1px solid rgba(26,20,16,.18);
		border-left:3px solid var(--aa-coral);
		border-radius:4px;
		box-shadow:
			0 1px 0 rgba(255,255,255,.7) inset,
			0 8px 24px -10px rgba(60,30,15,.30),
			0 18px 38px -22px rgba(60,30,15,.20);
		animation:aaNichePopIn .22s ease-out;
	}
	@keyframes aaNichePopIn {
		from { opacity:0; transform:translateY(-4px); }
		to   { opacity:1; transform:translateY(0); }
	}
	.aa-niche-pop-eyebrow {
		font-family:var(--aa-mono);
		font-size:9px; font-weight:700;
		letter-spacing:.30em; text-transform:uppercase;
		color:var(--aa-coral-2);
		margin-bottom:4px;
	}
	.aa-niche-pop-title {
		font-family:var(--aa-serif);
		font-size:13.5px; font-weight:600;
		color:var(--aa-ink);
		margin-bottom:11px; line-height:1.35;
	}
	.aa-niche-pop-actions {
		display:flex; flex-direction:column; gap:6px;
	}
	.aa-niche-pop-upload,
	.aa-niche-pop-remove {
		display:inline-flex; align-items:center; gap:9px;
		padding:9px 12px;
		font-family:var(--aa-mono);
		font-size:10.5px; font-weight:700;
		letter-spacing:.20em; text-transform:uppercase;
		border-radius:2px; cursor:pointer;
		transition:background .18s ease, color .18s ease, border-color .18s ease;
	}
	.aa-niche-pop-upload {
		background:var(--aa-coral); color:var(--aa-cream);
		border:1px solid var(--aa-coral);
	}
	.aa-niche-pop-upload:hover {
		background:var(--aa-coral-2); border-color:var(--aa-coral-2);
	}
	.aa-niche-pop-remove {
		background:transparent; color:var(--aa-ink-2);
		border:1px solid rgba(26,20,16,.18);
	}
	.aa-niche-pop-remove:hover {
		color:var(--aa-coral-2); border-color:var(--aa-coral-2);
		background:rgba(232,72,72,.05);
	}
	.aa-niche-pop-close {
		position:absolute; top:6px; right:8px;
		width:22px; height:22px; padding:0;
		background:transparent; border:none;
		font-size:18px; line-height:1;
		color:rgba(59,47,38,.55); cursor:pointer;
		transition:color .18s ease;
	}
	.aa-niche-pop-close:hover { color:var(--aa-ink); }

	/* When the popover is open, dim the placeholder text so attention
	   shifts to the action chooser. Subtle — just opacity. */
	.niche-service-upload-container:has(.aa-niche-pop) > :not(.aa-niche-pop) {
		opacity:.55;
		transition:opacity .22s ease;
	}
</style>

<section class="mid_wrap p-3">
	<div class="loaderautomater">
		<div class="text-center mt-5">
			<div class="lds-ellipsis mt-5"><div></div><div></div><div></div></div>
			<h1 class="text-center white text_inherit">Please Wait...</h1>
		</div>
	</div>
	<div class="sec3 ">
		<div class="container-fluid">
			<div class="update_menu" style="float: right;position: relative;">
				<div class="">

					<?php
					if(isset($funnel_id))
					{
					?>
						
						<input type="hidden" id="funnel_id" value="<?= $funnel_id ?>">
						<input type="hidden" id="template_id" value="<?= $count ?>">
						<input type="hidden" id="thankyou_page_link" value="<?= $templatedata[0]['Data']->thankyou_page_link ?>">
						

					<?php
					}

					$thankyou_site_uid = str_ireplace("=","",base64_encode($camp_id.'|'.$user_id.'|thank'));
					$__funnel_slug = isset($templatedata[0]['Data']->slug) ? trim($templatedata[0]['Data']->slug) : '';
					if ($__funnel_slug !== '') {
						$default_thankyou_page_link = base_url("webpages")."/".$__funnel_slug."/thankyou";
					} else {
						$default_thankyou_page_link = base_url("webpages")."/thankyou/".$thankyou_site_uid;
					}


					// print_r($templatedata); exit;
					if(isset($templatedata) && !empty($templatedata)){
						// print_r($templatedata); exit;


						// if(isset($funnel_type) && $funnel_type == 'landing'){
						// 	// echo '1'; exit;
						// 	$status=$templatedata[0]['Data']->status;
						// 	$published_link=$templatedata[0]['Data']->publish_link;
						// 	$templateContent=$templatedata[0]['Data']->content;
						// 	// $brand_name=$templatedata[0]['Data']->brand_name;
						// }else{
						// 	// echo '2'; exit;
						// 	$status=$templatedata[0]['Data']->thankpage_status;
						// 	$published_link=$templatedata[0]['Data']->thankyou_page_link;
						// 	$templateContent=$templatedata[0]['Data']->thankyou_content;
						// 	// $brand_name=$templatedata[0]['Data']->brand_name;
						// }

						
						
						$status=$templatedata[0]['Data']->status;
						$published_link=$templatedata[0]['Data']->publish_link;
						$templateContent=$templatedata[0]['Data']->content;
						
						$thankyou_status=$templatedata[0]['Data']->thankpage_status;
						$thankyouPage_link=$templatedata[0]['Data']->thankyou_page_link;
						$thankyou_templateContent=$templatedata[0]['Data']->thankyou_content;

						$script_code = $templatedata[0]['Data']->script_code;
						$script_code = json_decode($script_code, true);
						$chatbotEmbedCode = '';
						if(!empty($script_code) && isset( $script_code['chatbotEmbedCode']) ){
							$chatbotEmbedCode = $script_code['chatbotEmbedCode'];
						}

					}
					
						if( empty($thankyouPage_link)){
							$thankyouPage_link = $default_thankyou_page_link;
						}


					?>
				</div>
			</div>
			<div class="clearfix"></div>
		</div>
	</div>
	<div class="autoresp">
		<div class="container-fluid p-0">
			<div class="bx_all_mid aa-editor-page">

				<?php
				// Agentic Affiliates page header (replaces the old "Press Console" masthead).
				// Only for Bridge templates (the dark skin); other templates unaffected.
				if (isset($count) && (int) $count >= 201 && (int) $count <= 210):
					$ic_bridge_name = (isset($templatedata[0]['Data']->campaign_name) && $templatedata[0]['Data']->campaign_name !== '')
						? $templatedata[0]['Data']->campaign_name
						: (isset($templatedata[0]['Data']->brand_name) ? $templatedata[0]['Data']->brand_name : 'Your Bridge');
				?>
				<header class="ic-edit-head p-2 px-3">
					<div class="ic-edit-head-l">
						<h1 class="ic-edit-title">Edit page · <span><?= html_escape($ic_bridge_name) ?></span></h1>
						<div class="ic-edit-sub">Change the copy, layout &amp; styling — the live preview updates as you edit.</div>
					</div>
					<div class="ic-edit-head-r">
						<div class="ic-eh-pages d-flex gap-3">
							<button type="button" id="funnel_page_controls" class="page_controls btn_6 active" data-page="templateEditor1">Funnel Page</button>
							<button type="button" id="thankyou_page_controls" class="page_controls btn_5 inactive" data-page="templateEditor2">Thank You Page</button>
						</div>
						<div class="autosave-toggle-wrap">
							<label class="autosave-toggle">
								<input type="checkbox" id="autosaveToggle" <?= !empty($autosave_enabled) ? 'checked' : '' ?>>
								<span class="autosave-track"><span class="autosave-thumb"></span></span>
								<span class="autosave-label">Autosave</span>
							</label>
							<span class="autosave-status" id="autosaveStatus"></span>
						</div>
						<div class="save_btn_section <?= ($status== 0)?'':'d-none' ?>">
							<button type="button" class="db-button save_action">
								<span>Save and Publish</span>
							</button>
						</div>
						<div class="btn-group position-relative update_btn_section <?= ($status== 1)?'':'d-none' ?>">
							<button type="button" class="btn_31 updateThis px-2">Save Changes</button>
							<button type="button" class="btn_32">
								<img src="<?= base_url('assets/new-images/dropdown.svg') ?>" alt="save" class="img-fluid">
							</button>
							<div class="dropdown-section">
								<div class="dropdown-options">
									<div>
										<img src="<?= base_url('assets/new-images/arrow-orange.svg'); ?>" alt="copy" class="img-fluid">
										<a href="<?= $published_link; ?>" target="_blank">View Page</a>
									</div>
									<div>
										<img src="<?= base_url('assets/new-images/arrow-orange.svg'); ?>" alt="copy" class="img-fluid">
										<a href="#" class="" data-target="#popup2" data-toggle="modal">Publish Option</a>
									</div>
								</div>
							</div>
						</div>
					</div>
				</header>
				<?php endif; ?>

				<div class="">
					<div class="row">
						<!-- <div class="col-sm-12 pb-2">
							<button class="btn  toggleEditor brcolor" data-status="1">Show Full screen</button>
						</div> -->
						<div class="col-xl-3 col-lg-3 col-md-3 col-4 controlDiv position-relative">
							<a href="#" class="toggleEditor position-absolute showfullscreen" 
							style="display: none!important;" data-status="1">
							 	<img src="<?= base_url('assets/new-images/arrow2.svg'); ?>" alt="fullscreen" class="img-fluid">
							</a>
							<div id="accordion1" class="h-100">
								<div class="heading_color f25 lh35 sf-pro-display pb-2" >Controls</div>

								<?php if ($ic_use_skin): /* Bridge templates (201–207) only: they are the ones authored with <section data-label>. */ ?>
								<!-- Page Sections — show/hide any labelled block of the opt-in page. The
								     list is built from the live preview DOM on open (see icSections* below),
								     so it needs no per-template config and picks up new templates for free. -->
								<div class="card mt-2 ic-sections-card" id="icSectionsPanel">
									<div class="card-header omni-primary" data-toggle="collapse" href="#icSections">
										<a class="text-white">Page Sections</a>
										<img src="<?= base_url('assets/new-images/dropdown.svg'); ?>" alt="dropdown" class="img-fluid">
									</div>
									<div id="icSections" class="collapse" data-parent="#accordion">
										<div class="card-body">
											<div class="card-block px-2 mb-2">
												<p class="ic-sections-hint">Turn a section off to drop it from the live page. Nothing is deleted, so you can turn it back on any time.</p>
												<p class="ic-sections-grouptitle">Opt-in page</p>
												<ul class="ic-sections-list" id="icSectionsList" data-ic-group="funnel"></ul>
												<p class="ic-sections-note">
													<i class="fa fa-lock" aria-hidden="true"></i>
													<span>The opt-in form can’t be turned off. It’s what captures the lead and starts the call, so a Bridge without it can’t do anything.</span>
												</p>
												<hr class="ic-sections-div">
												<p class="ic-sections-grouptitle">Thank-you page</p>
												<ul class="ic-sections-list" id="icSectionsListTy" data-ic-group="thankyou"></ul>
												<p class="ic-sections-note">
													<i class="fa fa-lock" aria-hidden="true"></i>
													<span>The AI voice call can’t be turned off. It’s the whole point of the thank-you page.</span>
												</p>
											</div>
										</div>
									</div>
								</div>
								<?php endif; ?>

								<?php if(isset($funnel_type) && $funnel_type == 'landing') { ?>
								<div class="card mt-2" id="Autoresponderpanel">
									<div class="card-header omni-primary " data-toggle="collapse" href="#ars">
										<a class="text-white">
											Autoresponder Setting
										</a>
										<img src="<?= base_url('assets/new-images/dropdown.svg'); ?>" alt="dropdown" class="img-fluid">
									</div>
									<div id="ars" class="collapse" data-parent="#accordion">
										<div class="card-body">
											<div class="card-block px-2 mb-2">
												<div class="form-group mb-2">
													<label>API or Copy/Paste</label>
													<select id="autoresponderType" class="form-control select2me">
														<option value="api">- Using Api -</option>
														<option value="copypaste">- Using Copy/Paste -</option>
													</select>
												</div>
												<div id="apiForm">
													<?php if(!empty($autoresponders)){ ?>
													<div class="form-group mb-2">
														<label>Choose Autoresponder</label>
														<select id="mailerType" class="form-control select2me">
															<option value="">- Select AutoResponder -</option>
															<?php
															foreach($autoresponders as $autoresponder)
															{  
																$rtype=$autoresponder['Data']->respondertype;
																$param1=$autoresponder['Data']->param1;//apikey
																$param2=$autoresponder['Data']->param2;
																$param3=$autoresponder['Data']->param3;//listid
																if(isset($rtype) && $rtype=='6'){
																	//$aweberlistname=$autoresponder['responderSetData']->param3;
																	$name='Goto Webinar';
																}
																if(isset($rtype) && $rtype=='5'){
																	//$aweberlistname=$autoresponder['responderSetData']->param3;
																	$name='icontact';
																}if(isset($rtype) && $rtype=='4'){
																	$aweberlistname=$autoresponder['Data']->param3;
																	$name='Aweber';
																}else if(isset($rtype) && $rtype=='3'){
																	$name='Mailchimp';
																}else if(isset($rtype) && $rtype=='2'){
																	$name='Active campaign';
																}else if(isset($rtype) && $rtype=='1'){
																	$name='Getresponse';
																}else if(isset($rtype) && $rtype=='7'){
																	$name='Convertkit';
																}
																echo '<option value="'.$rtype.'">'.$name.'</option>';
																
															}
															?> 
														</select>
													</div>
													<div class="form-group mb-2" style="display:none;" id="divParameter4">
														<label>List ID</label>
														<select id="mailerParameter3List" name="actlistid" class="form-control" ></select>
													</div>
													<div class="form-group mb-2" style="display:none;" id="divParameter5">
														<label>List ID</label>
														<select id="mailerParameter5List" name="actlistid" class="form-control" ></select>
													</div>
													<div class="form-group mb2" id="loaderlist" style="display:none;">
														<div class="text-center">
															<p>Retrieving Lists</p>
															<div class="lds-ellipsis">
																<div></div><div></div><div></div><p></p>
															</div>
														</div>
													</div>
													<div class="form-group mb2" id="errormsg" style="display:none;">
														<div class="text-center">
															
														</div>
													</div>
													<input type="hidden" class="form-control" id="mailerParameter1"/>
													<input type="hidden" class="form-control" id="mailerParameter2"/>
													<input type="hidden" class="form-control" id="mailerParameter3"/>
													<div class="form-group mb-2">
														<button type="button" class="btn_4 saveAutoStyling mb-2" style="float: right;">
															<span class="fui-check-inverted"></span> 
															Apply Changes
														</button>
													</div>
													<?php }else{ ?>	
													<div class="form-group mb-2">
														<p>It seems you haven't setup your Autoresponder. 
														   <a href="<?= base_url('setting');?>" target="_blank" >Click Here</a> to Setup Your Autoresponder</p>
													</div>
													<?php } ?>														
												</div>
												<div id="copypasteForm" style="display:none;">
													<div class="form-group mb-2" id="divParameter5">
														<label>Paste Your Code Here</label>
														<textarea name="" cols="" rows="4" id="copypaste_code" class="form-control txt1 mb-4"></textarea>
													</div>
													<div class="form-group mb-2">
														<button type="button" class="btn_4 saveAutoStyling mb-2" style="float: right;">
															<span class="fui-check-inverted"></span> 
															Apply Changes
														</button>
													</div>
												</div>
											</div>
										</div>
									</div>
								</div>
								<?php } ?>
								<?php if(isset($funnel_type) && $funnel_type == 'landing') { ?>								
								<div class="card mt-2" id="iconeditorPanel">
									<div class="card-header omni-primary" data-toggle="collapse" href="#analytics_editor">
										<a class="text-white">
											Script Code
										</a>
										<img src="<?= base_url('assets/new-images/dropdown.svg'); ?>" alt="dropdown" class="img-fluid">
									</div>
									<div id="analytics_editor" class="collapse" data-parent="#accordion">
										<div class="card-body">
											<div class="card-block px-2 mb-2">
												<div class="form-group mb-2">
													<label>Head Code <small class="text-muted">(added before &lt;/head&gt;)</small></label>
													<textarea id="head_code" name="head_code" cols="" rows="5" class="form-control txt1 mb-2" placeholder="Scripts for the &lt;head&gt; — e.g. analytics, pixels, verification tags"></textarea>
												</div>
											</div>

											<div class="card-block px-2 mb-2">
												<div class="form-group mb-2">
													<label>Body Code <small class="text-muted">(added before &lt;/body&gt;)</small></label>
													<textarea id="body_code" name="body_code" cols="" rows="5" class="form-control txt1 mb-2" placeholder="Scripts for the end of &lt;body&gt; — e.g. chat widget, conversion scripts"></textarea>
												</div>
											</div>
										</div>
									</div>
								</div>
								<?php } ?>
								<?php if(isset($funnel_type) && $funnel_type == 'landing') { ?>
								<div class="card mt-2">
									<div class="card-header omni-primary" data-toggle="collapse" href="#seoacc">
										<a class="text-white" >
											SEO Options
										</a>
										<img src="<?= base_url('assets/new-images/dropdown.svg'); ?>" alt="dropdown" class="img-fluid">
									</div>
									<div id="seoacc" class="collapse" data-parent="#accordion">
										<div class="card-body">
											<div class="card-block px-2 mb-2">
												<div class="form-group mb-2">
													<label>Enter your Page Title Here</label>
													<input type="text" class="form-control" name="page_title" id="page_title" onChange="putPageTitle()"/>
												</div>
												<div class="form-group mb-2">
													<label>Enter your Seo Meta Description</label>
													<input type="text" class="form-control" name="meta_desc" id="meta_desc"/>
												</div>
												<div class="form-group mb-2">
													<label>Enter your Seo Meta Keywords</label>
													<textarea id="meta_keywords" name="meta_keywords" cols="" rows="3" class="form-control txt1 mb-4" placeholder="Enter Keywords separated by comma"></textarea>
												</div>
											</div>
										</div>
									</div>
								</div>
								<?php } ?>
								<div class="card mt-2" id="inputcontrols">
									<div class="card-header omni-primary" data-toggle="collapse" href="#inputeditor">
										<a class="text-white">
											Form/Button Controls
										</a>
										<img src="<?= base_url('assets/new-images/dropdown.svg'); ?>" alt="dropdown" class="img-fluid">
									</div>
									<div id="inputeditor" class="collapse" data-parent="#accordion">
										<div class="card-body">
											<div class="card-block px-2 mb-2">
												<div id="thanyoubtncontrols" style="display:none;">
													<div class="form-group mb-2">
														<label>Button Text</label>
														<input type="text" class="form-control" id="btntext" />
														<!-- <input type="text" class="form-control" id="btntext" onkeyup="putbtntext();"/> -->
													</div>
													<div class="form-group mb-2 link_change">
														<label>Button Link</label>
														<input type="text" class="form-control" id="btnlink" />
														<!-- <input type="text" class="form-control" id="btnlink" onkeyup="putbtnlink();"/> -->
													</div>
													<?php if(isset($funnel_type) && $funnel_type != 'landing') { ?>
													<!-- <div class="form-group mb-2 p-4">
														<input class="form-check-input hidebtn" type="checkbox" id="hidebtn">
												    	<label class="form-check-label" for="hidebtn">Hide Button
												    	</label>
													</div>
													<div class="form-group mb-2 p-4">
														<input class="form-check-input hidebtn1" type="checkbox" id="hidebtn1">
												    	<label class="form-check-label" for="hidebtn1">Hide Division
												    	</label>
													</div> -->
													<?php } ?>
												</div>
												
												<div id="formbtncontrols" style="display:none;">
													<div class="form-group mb-2">
														<label>Button Text</label>
														<input type="text" class="form-control" id="formbtntext" onkeyup="putformbtntext();"/>
													</div>
													<div class="form-group mb-2" id="nameplaceholdermain" style="display:block;">
														<label>Name field label</label>
														<input type="text" class="form-control" id="nameplaceholder" onkeyup="putnameplaceholder();"/>
													</div>
													<div class="form-group mb-2">
														<label>Email field label</label>
														<input type="text" class="form-control" id="emailplaceholder" onkeyup="putemailplaceholder();"/>
													</div>
													<div class="form-group mb-2">
														<label>Phone field label</label>
														<input type="text" class="form-control" id="phoneplaceholder" value="Phone" onkeyup="putphoneplaceholder();"/>
													</div>
												</div>
								
											</div>
										</div>
									</div>
								</div>




								<!-- Accordion 8 — Social Media Controls.
								     Always visible in the accordion stack (no display:none on
								     the card) so the visibility toggles are reachable even
								     after the user has hidden every social icon. The
								     per-platform Link inputs still surface contextually when
								     the user clicks an icon in the canvas. -->
								<div id="socialmediacontrols" class="card mt-2 d-none">
									<div class="card-header omni-primary socialmedia_switcher"  data-toggle="collapse"  data-target="#socialControlEditor">
										<a class="text-white"> Social Media Controls </a>
										<img src="<?= base_url('assets/new-images/dropdown.svg'); ?>" alt="dropdown" class="img-fluid">
									</div>
									<div id="socialControlEditor"  class="collapse px-3 py-2" data-parent="#accordion" >

										<!-- Always-visible visibility toggles. One row per platform.
										     Lets the user re-show a previously hidden icon without
										     needing to find it on the page first. -->
										<div class="aa-social-vis-section">
											<div class="aa-social-vis-label">Visibility</div>
											<div class="form-group mb-2 aa-visibility-row">
												<input class="form-check-input" type="checkbox" id="aaSocialFbVisible" data-platform="facebook" checked/>
												<label class="form-check-label" for="aaSocialFbVisible">Show Facebook icon</label>
											</div>
											<div class="form-group mb-2 aa-visibility-row">
												<input class="form-check-input" type="checkbox" id="aaSocialTwitVisible" data-platform="twitter" checked/>
												<label class="form-check-label" for="aaSocialTwitVisible">Show Twitter icon</label>
											</div>
											<div class="form-group mb-2 aa-visibility-row">
												<input class="form-check-input" type="checkbox" id="aaSocialPintVisible" data-platform="pintrest" checked/>
												<label class="form-check-label" for="aaSocialPintVisible">Show Pinterest icon</label>
											</div>
											<div class="form-group mb-2 aa-visibility-row">
												<input class="form-check-input" type="checkbox" id="aaSocialInstaVisible" data-platform="instagram" checked/>
												<label class="form-check-label" for="aaSocialInstaVisible">Show Instagram icon</label>
											</div>
											<div class="form-group mb-2 aa-visibility-row">
												<input class="form-check-input" type="checkbox" id="aaSocialYtVisible" data-platform="youtube" checked/>
												<label class="form-check-label" for="aaSocialYtVisible">Show YouTube icon</label>
											</div>
										</div>

										<!-- Per-platform link editors. Each section opens when the
										     matching social icon is clicked in the canvas, via the
										     existing handlers in this file (.facebook .click(...) etc.). -->
										<div id="fblinkcontrol"  style="display:none;">
											<label for="facebook-link">Facebook Link</label>
											<input type="text" id="fblink" onkeyup="putfblink();" class="form-input">
											<p> <span class="note">Note: </span>URL must start with https://.</p>
										</div>

										<div id="twitlinkcontrol"  style="display:none;">
											<label for="twitter-link">Twitter Link</label>
											<input type="text" id="twitlink" onkeyup="puttwitlink();" class="form-input">
											<p> <span class="note">Note: </span>URL must start with https://.</p>
										</div>

										<div id="pintlinkcontrol"  style="display:none;">
											<label for="pinterest-link">Pinterest Link</label>
											<input type="text" id="pintlink" onkeyup="putpintlink();" class="form-input">
											<p> <span class="note">Note: </span>URL must start with https://.</p>
										</div>

										<div id="instalinkcontrol"  style="display:none;">
											<label for="instagram-link">Instagram Link</label>
											<input type="text" id="instalink" onkeyup="putinstalink();" class="form-input">
											<p> <span class="note">Note: </span>URL must start with https://.</p>
										</div>

										<div id="youtubelinkcontrol"  style="display:none;">
											<label for="youtube-link">YouTube Link</label>
											<input type="text" id="youtubelink" onkeyup="putyoutubelink();" class="form-input">
											<p> <span class="note">Note: </span>URL must start with https://.</p>
										</div>
									</div>
								</div>

								
								<!-- <div class="card mt-2" id="videocontrols" style="display:none;">
									<div class="card-header omni-primary" data-toggle="collapse" href="#videoeditor">
										<a class="text-white">
											Video Controls
										</a>
									</div>
									<div id="videoeditor" class="collapse" data-parent="#accordion">
										<div class="card-body">
											<div class="card-block px-2 mb-2">
												<div id="videobtncontrols" style="display:block;">
													<div class="form-group mb-2">
														<label>Select Video Source</label>
														<select id="video_selector" class="form-control"  onChange="showVideoTr()">
															<option value="select">Select</option>
															<option value="youtube">YouTube</option>
															<option value="vimeo">Vimeo</option>
														</select>
													</div>
													<div id="youtube_id_tr" style="display:none">
														<div class="form-group mb-2">
															<label>Enter YouTube Video Link</label>
															<input type="text" id="video_link1" name="video_link1" class="form-control" placeholder="E.g https://www.youtube.com/watch?v=DWcJFNfaw9c" onkeyup="changeVideolinkYt()"/>
														</div>
													</div>

													<div id="vimeo_id_tr" style="display:none">
														<div class="form-group mb-2">
															<label>Enter Vimeo Video Link</label>
															<input type="text" id="video_link2" name="video_link2" class="form-control" placeholder="E.g https://vimeo.com/123456" onkeyup="changeVideolinkVimeo()"/>
														</div>
													</div>
												</div>
												
												<div id="formbtncontrols" style="display:none;">
													<div class="form-group mb-2">
														<label>Button Text</label>
														<input type="text" class="form-control" id="formbtntext" onkeyup="putformbtntext();"/>
													</div>
													<div class="form-group mb-2" id="nameplaceholdermain" style="display:none;">
														<label>Name PlaceHolder</label>
														<input type="text" class="form-control" id="nameplaceholder" onkeyup="putnameplaceholder();"/>
													</div>
													<div class="form-group mb-2">
														<label>Email PlaceHolder</label>
														<input type="text" class="form-control" id="emailplaceholder" onkeyup="putemailplaceholder();"/>
													</div>
													
												</div>
								
											</div>
										</div>
									</div>
								</div> -->
								
								<div class="card mt-2" id="stylingoption" style="display:none;">
									<div class="card-header omni-primary" data-toggle="collapse" href="#stylingopt">
										<a class="text-white" >
											Styling Options
										</a>
										<img src="<?= base_url('assets/new-images/dropdown.svg'); ?>" alt="dropdown" class="img-fluid">
									</div>
									<div id="stylingopt" class="collapse" data-parent="#accordion">
										<div class="card-body">
											<div class="card-block px-2 mb-2">
												<div class="backgroundImgDiv" style="display: none;">
													<div class="form-group mb-2">
														<label>Click to Change Background Image</label>
														<button class="btn_4" type="button" name="headerImg" id="headerImg" onchange=""><i class="fa fa-picture-o mr-2"></i>Browse Image</button>
														<input type="hidden" id="selectedbgimage"/>
													</div>
													<div class="fittoblockHide" style="display: none;">
														<div class="form-group mb-2">
															<input class="form-check-input" type="checkbox" id="HeaderImgfitImage"/>
															<label class="form-check-label" for="HeaderImgfitImage">
																Enable/Disable Fit to block
															</label>
														</div>
													</div>
													<div class="headerImgFilterhide" style="display: none;">
														<div class="form-group mb-2">
															<input class="form-check-input" type="checkbox" id="headerImgFilter"/>
															<label class="form-check-label" for="headerImgFilter">
																Enable/Disable Filter
															</label>
														</div>
													</div>
													<div class="headeImgFilterdenHide" style="display: none;">
														<div class="form-group mb-2">
															<label>Enter Image Filter Density</label>
															<input type="range" name="" class="" style="" id="headerImgfilterDensity" value="0"  min="0.0" max="1.0" step="0.01">
														</div>
													</div>
													<code id="recomandDimHeaderImg"></code>
												</div>
											</div>
										</div>
									</div>
								</div>

								

								<!-- Typography — heading + body font selectors -->
								<div class="card mt-2" id="typographycontrols">
									<div class="card-header omni-primary" data-toggle="collapse" href="#typographyopt">
										<a class="text-white">Typography</a>
										<img src="<?= base_url('assets/new-images/dropdown.svg'); ?>" alt="dropdown" class="img-fluid">
									</div>
									<div id="typographyopt" class="collapse" data-parent="#accordion">
										<div class="card-body aa-typo-body">
											<!-- Heading vs Body target switch -->
											<div class="aa-typo-tabs" role="tablist">
												<button type="button" class="aa-typo-tab active" data-typo-target="heading" role="tab">Headings</button>
												<button type="button" class="aa-typo-tab" data-typo-target="body" role="tab">Body</button>
											</div>

											<div class="aa-typo-current">
												<span class="aa-typo-current-label">Currently</span>
												<strong id="aaTypoCurrentName">Template Default</strong>
											</div>

											<div class="aa-typo-search-wrap">
												<i class="fa fa-search aa-typo-search-icon"></i>
												<input type="text" class="aa-typo-search" id="aaTypoSearch" placeholder="Search fonts..." autocomplete="off"/>
											</div>

											<div class="aa-typo-cats" role="tablist">
												<button type="button" class="aa-typo-cat active" data-cat="all">All</button>
												<button type="button" class="aa-typo-cat" data-cat="sans">Sans</button>
												<button type="button" class="aa-typo-cat" data-cat="serif">Serif</button>
												<button type="button" class="aa-typo-cat" data-cat="display">Display</button>
												<button type="button" class="aa-typo-cat" data-cat="mono">Mono</button>
												<button type="button" class="aa-typo-cat" data-cat="hand">Script</button>
											</div>

											<div class="aa-typo-grid" id="aaTypoGrid"></div>

											<div class="aa-typo-actions">
												<button type="button" class="btn_4 aa-reset-btn" id="aaResetFonts"><i class="fa fa-undo mr-2"></i>Reset</button>
												<button type="button" class="btn_4 aa-save-btn" id="aaSaveFonts"><i class="fa fa-check mr-2"></i>Save</button>
											</div>
											<div class="aa-typo-save-status" id="aaTypoSaveStatus"></div>
										</div>
									</div>
								</div>

								

								<?php if (isset($ic_tid) && $ic_tid >= 201 && $ic_tid <= 210): ?>
								<!-- Color scheme — recolor the whole :root palette (v2 templates 201-210) -->
								<div class="card mt-2" id="colorschemecontrols">
									<div class="card-header omni-primary" data-toggle="collapse" href="#colorschemeopt">
										<a class="text-white">Color scheme</a>
										<img src="<?= base_url('assets/new-images/dropdown.svg'); ?>" alt="dropdown" class="img-fluid">
									</div>
									<div id="colorschemeopt" class="collapse" data-parent="#accordion">
										<div class="card-body aa-typo-body aa-scheme-body">
											<div class="aa-typo-current">
												<span class="aa-typo-current-label">Palette</span>
												<strong id="aaSchemeCurrentName">Template Default</strong>
											</div>
											<div class="aa-scheme-presets" id="aaSchemePresets"></div>
											<?php
												$ic_src_site = (isset($templatedata[0]['Data']->website)) ? trim((string) $templatedata[0]['Data']->website) : '';
												$ic_src_host = ($ic_src_site !== '') ? preg_replace('#^https?://(www\.)?#i', '', rtrim($ic_src_site, '/')) : '';
											?>
											<?php if ($ic_src_site !== ''): ?>
											<div class="aa-scheme-fetch">
												<button type="button" class="btn_4 aa-scheme-fetch-btn" id="aaSchemeFetch"><i class="fa fa-magic mr-2"></i>Match my source website</button>
												<div class="aa-scheme-fetch-hint" id="aaSchemeFetchHint">Pull the palette from <strong><?= html_escape($ic_src_host) ?></strong> with AI, then autofill the swatches below.</div>
											</div>
											<?php endif; ?>
											<div class="aa-scheme-roles">
												<label class="aa-scheme-role" data-role="brand">
													<input type="color" class="aa-scheme-input" data-role="brand" value="#000000" aria-label="Brand colour">
													<span class="aa-scheme-role-meta"><span class="aa-scheme-role-name">Brand</span><span class="aa-scheme-role-hex" data-hex="brand">#000000</span></span>
												</label>
												<label class="aa-scheme-role" data-role="accent">
													<input type="color" class="aa-scheme-input" data-role="accent" value="#000000" aria-label="Accent colour">
													<span class="aa-scheme-role-meta"><span class="aa-scheme-role-name">Accent</span><span class="aa-scheme-role-hex" data-hex="accent">#000000</span></span>
												</label>
												<label class="aa-scheme-role" data-role="ink">
													<input type="color" class="aa-scheme-input" data-role="ink" value="#000000" aria-label="Text colour">
													<span class="aa-scheme-role-meta"><span class="aa-scheme-role-name">Text</span><span class="aa-scheme-role-hex" data-hex="ink">#000000</span></span>
												</label>
												<label class="aa-scheme-role" data-role="page">
													<input type="color" class="aa-scheme-input" data-role="page" value="#ffffff" aria-label="Background colour">
													<span class="aa-scheme-role-meta"><span class="aa-scheme-role-name">Background</span><span class="aa-scheme-role-hex" data-hex="page">#FFFFFF</span></span>
												</label>
											</div>
											<div class="aa-typo-actions">
												<button type="button" class="btn_4 aa-reset-btn" id="aaResetScheme"><i class="fa fa-undo mr-2"></i>Reset</button>
												<button type="button" class="btn_4 aa-save-btn" id="aaSaveScheme"><i class="fa fa-check mr-2"></i>Save</button>
											</div>
											<div class="aa-typo-save-status" id="aaSchemeSaveStatus"></div>
										</div>
									</div>
								</div>
								<style>
									.aa-scheme-presets{ display:grid; grid-template-columns:repeat(4,1fr); gap:8px; margin:2px 0 18px; }
									.aa-scheme-swatch{ cursor:pointer; padding:0; overflow:hidden; border:1px solid rgba(26,20,16,.14); border-radius:8px; background:var(--aa-cream,#fff); }
									.aa-scheme-swatch:hover{ border-color:var(--aa-coral,#c72e2e); }
									.aa-scheme-swatch.selected{ border-color:var(--aa-coral-2,#c72e2e); box-shadow:0 0 0 1px var(--aa-coral-2,#c72e2e) inset; }
									.aa-scheme-swatch-bars{ display:flex; height:26px; }
									.aa-scheme-swatch-bars i{ flex:1; display:block; }
									.aa-scheme-swatch-name{ display:block; font-family:var(--aa-mono,monospace); font-size:8.5px; font-weight:700; letter-spacing:.10em; text-transform:uppercase; color:rgba(59,47,38,.7); text-align:center; padding:4px 2px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
									.aa-scheme-roles{ display:grid; grid-template-columns:1fr 1fr; gap:9px; margin-bottom:16px; }
									.aa-scheme-role{ display:flex; flex-direction:column; align-items:stretch; gap:7px; margin:0; padding:7px; border:1px solid rgba(26,20,16,.10); border-radius:12px; background:var(--aa-cream-2,#fbf6ef); cursor:pointer; transition:border-color .18s ease, box-shadow .18s ease, transform .18s ease; }
										.aa-scheme-role:hover{ border-color:var(--aa-coral,#c72e2e); box-shadow:0 5px 16px -11px rgba(199,46,46,.55); transform:translateY(-1px); }
										.aa-scheme-role-meta{ display:flex; flex-direction:column; gap:3px; min-width:0; padding:4px 2px 2px 2px; }
										.aa-scheme-role-name{ font-family:var(--aa-mono,monospace); font-size:8px; font-weight:700; letter-spacing:.16em; text-transform:uppercase; color:rgba(59,47,38,.55); line-height:1; }
										.aa-scheme-role-hex{ font-family:var(--aa-mono,monospace); font-size:11px; font-weight:600; letter-spacing:.03em; text-transform:uppercase; color:var(--aa-ink,#1a1410); line-height:1; }
									/* Colour role pickers. !important + explicit size because a native /
									   framework default was inflating the input and insetting the swatch to a
									   thin strip; appearance:none must win so the swatch can FILL the box. */
									.aa-scheme-input{ display:block; width:100% !important; height:48px !important; padding:0 !important; border:none !important; border-radius:9px !important; background:none !important; cursor:pointer; overflow:hidden; -webkit-appearance:none !important; -moz-appearance:none !important; appearance:none !important; box-shadow:0 0 0 1px rgba(26,20,16,.14), inset 0 0 0 1px rgba(255,255,255,.25); }
									.aa-scheme-input::-webkit-color-swatch-wrapper{ padding:0 !important; border-radius:6px; }
									.aa-scheme-input::-webkit-color-swatch{ border:none !important; border-radius:9px !important; }
									.aa-scheme-input::-moz-color-swatch{ border:none !important; border-radius:9px !important; }
									#aaSchemeCurrentName{ display:block; font-family:var(--aa-serif,Georgia,serif); font-size:15px; font-weight:600; color:var(--aa-ink,#1a1410); letter-spacing:-.005em; line-height:1.25; }
									.aa-scheme-fetch{ margin:0 0 16px; }
									.aa-scheme-fetch-btn{ width:100%; display:flex; align-items:center; justify-content:center; gap:6px; padding:9px 12px; border:1px solid rgba(26,20,16,.16); border-radius:8px; background:var(--aa-cream,#fff); font-family:var(--aa-mono,monospace); font-size:10px; font-weight:700; letter-spacing:.10em; text-transform:uppercase; color:var(--aa-ink,#1a1410); cursor:pointer; transition:border-color .15s, background .15s; }
									.aa-scheme-fetch-btn:hover{ border-color:var(--aa-coral,#c72e2e); background:var(--aa-coral-soft,#fff5f2); }
									.aa-scheme-fetch-btn:disabled{ opacity:.6; cursor:default; }
									.aa-scheme-fetch-hint{ margin-top:7px; font-size:11px; line-height:1.4; color:rgba(59,47,38,.62); }
									.aa-scheme-fetch-hint strong{ color:rgba(59,47,38,.85); font-weight:600; }
									.aa-scheme-fetch-hint.is-success{ color:#0f766e; }
									.aa-scheme-fetch-hint.is-error{ color:#c72e2e; }
								</style>
<?php endif; ?>

								<!-- Block Removal — appears when a removable block is clicked (image placeholder, decorative section, etc.) -->
							</div>
						</div>
						<div class="col-xl-9 col-lg-9 col-md-9 col-8 controlEditor">

							<a href="#" class="toggleEditor position-absolute showfullcontrolls" data-status="1">
								<img src="<?= base_url('assets/new-images/arrow2.svg'); ?>" alt="fullscreen" class="img-fluid">
							</a>

							<!-- The "Edit here" action toolbar (Funnel/Thank-You · Autosave · Save)
							     moved UP into the .ic-edit-head top bar. The preview is now the
							     first thing in this column. -->

							<div class="position-relative editorDiv">
							<div id="templateEditor">

								<div id="templateEditor1">
									<?php
										// Isolated preview — capture the funnel-page markup and mount it
										// in a real <iframe srcdoc> below so NO editor/global CSS can leak in.
										ob_start();
										// $status=$templatedata[0]['Data']->status;
									if(isset($status) && $status== 0 && !empty($templateContent)){
										// Draft but AI-populated content exists — use it
										echo $templateContent;
									}else if(isset($status) && $status== 0){

										$global_data=$this->config->item('global_data');
										$faviconimg=base_url($global_data['fav_url']);
										$data=file_get_contents($templatefile);
										// echo $templatefile;exit;
										// $affiurl=$this->session->userdata('affi_url');
										$data=str_ireplace('../images/',base_url('landing-pages/images/'),$data );
										$data=str_ireplace('../js/',base_url('landing-pages/js/'),$data ); $data=ic_template_rewrite_assets($data); /* landing-pages2 art ("assets/…"); no-op for the default set */
										// Strip the public-page runtime includes (../client.js, ../bridge-launch.js):
										// they live at the template ROOT (not ../js/), don't exist here, and the
										// publish path (Bridge_build_lib) drops them anyway — leaving them only 404s
										// inside the preview iframe.
										$data=preg_replace('#<script\b[^>]*src=["\']\.\./(?:client|bridge-launch)\.js["\'][^>]*></script>#i','',$data);
										echo '<funnelrh id="funnelrh"><meta charset="utf-8"><meta http-equiv="X-UA-Compatible" content="IE=edge"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Localeads</title><meta name="description" class="description" content=""><meta name="keywords" class="keywords" content=""><link rel="icon" id="favadd" href="'.$faviconimg.'"/><style id="scrollcss">::-webkit-scrollbar-thumb { background:#8e4599;border-radius: 2px;}::-webkit-scrollbar-button {display: none;}::-webkit-scrollbar {background-color:#d78b79a6;height: 4px;width: 7px;}.alertDiv{position: fixed;bottom: 12px;right: 10px;background: #8e4599;color:#ffffff;display: none;padding: 10px 59px;}</style><div class="analyticsCode" style="display:none;"></div><div class="retarget_analyticsCode" style="display:none;"></div><div class="chatbotEmbedCode" style="display:none;"></div></funnelrh><funnelrb id="funnelrb">';
										echo $data;
										echo '<div id="paste_html_code" style="display:none"></div>
										<input type="hidden" id="submissiontype" value="redirect_user">
										<input type="hidden" id="redirect_url">
										<input type="hidden" id="redirect_userto" value="_self">
										<input type="hidden" id="messageaftersuccess">
										<input type="hidden" id="messageafterfailed"></funnelrb>';
									}else if(isset($published_link)){
										echo $templateContent;
									}
										$ic_funnel_inner = ob_get_clean();
										// sandbox: the preview is an editing CANVAS — nothing inside it
										// may navigate. A srcdoc frame has no base URL of its own, so a
										// template's href="#" (or ../x) resolved against the EDITOR's URL
										// and loaded the editor inside its own preview. allow-same-origin
										// is required (the editor's JS reaches into contentDocument to
										// type/edit); omitting allow-top-navigation + allow-forms is what
										// kills the navigation.
										echo '<iframe id="templateFrame1" class="ic-preview-frame" sandbox="allow-same-origin" srcdoc="'
											. htmlspecialchars('<!DOCTYPE html><html><head><meta charset="utf-8"></head><body>' . $ic_funnel_inner . '</body></html>', ENT_QUOTES)
											. '"></iframe>';
									?>

								</div>
								<div id="templateEditor2" style="display:none;">
									<?php
										// Isolated preview — capture the thank-you markup and mount it
										// in a real <iframe srcdoc> below so NO editor/global CSS can leak in.
										ob_start();
									
										
										
										if( isset($thankyou_status) && $thankyou_status == 0 && !empty($thankyou_templateContent)){
											// Draft but AI-populated thank-you content exists — use it
											echo $thankyou_templateContent;
										}else if( isset($thankyou_status) && $thankyou_status == 0 ){

											$global_data=$this->config->item('global_data');
											$faviconimg=base_url($global_data['fav_url']);
											$data=file_get_contents($thankyou_templatefile);
											// echo $templatefile;exit;
											// $affiurl=$this->session->userdata('affi_url');
											$data=str_ireplace('../images/',base_url('landing-pages/images/'),$data );
											$data=str_ireplace('../js/',base_url('landing-pages/js/'),$data ); $data=ic_template_rewrite_assets($data); /* landing-pages2 art ("assets/…"); no-op for the default set */
											// Strip ../client.js + ../bridge-launch.js (see funnel frame above) — dead 404s in the preview.
											$data=preg_replace('#<script\b[^>]*src=["\']\.\./(?:client|bridge-launch)\.js["\'][^>]*></script>#i','',$data);
											echo '<funnelrh1 id="funnelrh1"><meta charset="utf-8"><meta http-equiv="X-UA-Compatible" content="IE=edge"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Localeads</title><meta name="description" class="description" content=""><meta name="keywords" class="keywords" content="Thanks"><link rel="icon" id="favadd" href="'.$faviconimg.'"/><style id="scrollcss">::-webkit-scrollbar-thumb { background:#8e4599;border-radius: 2px;}::-webkit-scrollbar-button {display: none;}::-webkit-scrollbar {background-color:#d78b79a6;height: 4px;width: 7px;}.alertDiv{position: fixed;bottom: 12px;right: 10px;background: #8e4599;color:#ffffff;display: none;padding: 10px 59px;}</style><div class="analyticsCode" style="display:none;"></div><div class="retarget_analyticsCode" style="display:none;"></div><div class="chatbotEmbedCode" style="display:none;"></div></funnelrh1><funnelrb1 id="funnelrb1">';
											echo $data;
											echo '</funnelrb1>';
										}else if(isset($published_link)){
											echo $thankyou_templateContent;
										}
										$ic_ty_inner = ob_get_clean();
										// sandbox — same reason as the funnel frame above: clicking the thank-you
										// CTA (href="#") navigated this frame to the editor's URL and rendered
										// the entire editor inside its own preview.
										echo '<iframe id="templateFrame2" class="ic-preview-frame" sandbox="allow-same-origin" srcdoc="'
											. htmlspecialchars('<!DOCTYPE html><html><head><meta charset="utf-8"></head><body>' . $ic_ty_inner . '</body></html>', ENT_QUOTES)
											. '"></iframe>';
										
										
										
										
										
										
										?>

											

								</div>
							</div>

							<?php 
							if ($status == 0) {
							?>
								<div class="progress_div" >
									<div class="progress_innr">
										<span class="iconRotate">
										<i class="fa-solid fa-spinner" ></i>
										</span>
										<span class="spark"></span>
										<span class="taskText"></span>
									</div>
								</div>
							<?php 
							}
							?>
							
							</div>


						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</section>

						
<input type="hidden" id="imageIdTemp" value=""/>
<div class="alertsaveDiv bg-sucess">
	<!-- <span style="color: #fff;">Your page has been updated</span>  -->
	<!-- <a target='_blank' style="color: yellow;border-bottom:1px dotted yellow;" href=" $published_link ?>">View Page.</a> -->
</div>
<?php $this->load->view("shared/footer");?>
<?php $this->load->view("sites/ContentModal");?>
<script src="<?php echo base_url('assets/app/ui/js/tags/jquery.caret.min.js');?>"></script>
<script src="<?php echo base_url('assets/app/ui/js/tags/jquery.tag-editor.js');?>"></script>
<link rel="stylesheet" type="text/css" href="https://js.api.here.com/v3/3.0/mapsjs-ui.css?dp-version=1542186754" />
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" integrity="sha512-z3gLpd7yknf1YoNbCzqRKc4qyor8gaKU1qmn+CShxbuBusANI9QpRohGBreCFkKxLhei6S9CQXFEbbKuqLg0DA==" crossorigin="anonymous" referrerpolicy="no-referrer" />
<script src="https://cdn.jsdelivr.net/npm/party-js@latest/bundle/party.min.js"></script>



<?php if(isset($status) && $status== 0){ ?>
	
	<style type="text/css">


		#funnelrb, #funnelrb1{
        	zoom: 0.75; 
			-moz-transform: scale(0.75);
		    -moz-transform-origin: 0 0;
		    -o-transform: scale(0.75);
		    -o-transform-origin: 0 0;
		    -webkit-transform: scale(0.75);
		    -webkit-transform-origin: 0 0;
        }

		.prev_click::after{   content: ''; position: absolute; width: 100%; height: 100%; left: 0px; top: 0px; bottom: 0px;  right: 0px;  z-index: 999999; }

		
		.goup{position: absolute; bottom: 35px; right: 30px; z-index: 99999; display: block; }
        .goup img{ width: 45px; background: #c2c2c25c; border-radius: 100%;  padding: 8px;cursor: pointer; }
        .goup{ display: none; }
		
		
        /* ── First-load "building your page" overlay (only when $status==0) ──
           Restyled from the old white/blue box into a soft, on-brand scrim with a
           centered card, a coral progress ring, and calmer copy. Colours use the
           coral accent but are self-contained so it reads well on any template. */
        .iconRotate{ display:inline-flex; width:34px; height:34px; margin:0 0 14px; }
        .iconRotate i{ display:none; } /* drop the old FontAwesome spinner glyph */
        .iconRotate::before{
            content:""; width:34px; height:34px; border-radius:50%;
            border:3px solid rgba(240,117,94,.22); border-top-color:#F0755E;
            animation:rotate .8s linear infinite;
        }
        /* Scoped to the PREVIEW column (.editorDiv is position:relative), not the
           whole viewport — the card sits centered over the page being written, and
           the overlay is CLEAR so the content is visible filling in live behind it.
           pointer-events:none lets it sit on top without blocking; only the card
           itself needs no interaction. */
        .editorDiv{ position:relative; }
        .progress_div{
            /* z-index must beat the preview-iframe wrapper (position:relative; z-index:999),
               which is a sibling inside .editorDiv — at z:50 the iframe painted OVER the
               loader card and hid it. 1000 puts the card on top of the writing preview. */
            position:absolute; inset:0; z-index:1000;
            background:transparent;
            display:flex; align-items:flex-start; justify-content:center; text-align:center;
            pointer-events:none;
        }
        .progress_div .progress_innr{ pointer-events:auto; margin-top:120px; }
        .progress_innr{
            position:static; transform:none; margin:0; max-width:380px;
            background:#fff; border:1px solid #EBD8CF; border-radius:18px;
            padding:30px 34px; line-height:1.55;
            box-shadow:0 24px 60px -24px rgba(42,30,24,.35);
            display:flex; flex-direction:column; align-items:center;
        }
        .progress_innr .taskText{
            font-family:'Outfit','Manrope',sans-serif; font-size:16px; font-weight:600;
            color:#1F1712; letter-spacing:-.2px;
        }
        .progress_innr .spark{ display:none; }

        @keyframes rotate {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

	</style>
<?php } ?>

<!-- ============================================================
     AGENTIC AGENCY · N°09 · THE PRESS CONSOLE · DOSSIER RESKIN
     Scoped under .aa-editor-page so the live preview content
     (#templateEditor1 / #templateEditor2) is left untouched.
     ============================================================ -->
<link href="https://fonts.googleapis.com/css2?family=family=JetBrains+Mono:wght@400;500;600&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style>
.aa-editor-page{
	--aa-cream:#FBF6EE;
	--aa-cream-2:#F4EBD8;
	--aa-paper:#FFFDF7;
	--aa-paper-2:#FBF4E2;
	--aa-ink:#1A1410;
	--aa-ink-2:#3B2F26;
	--aa-mute:#8B7B6E;
	--aa-coral:#E84848;
	--aa-coral-2:#C72E2E;
	--aa-coral-soft:#FF9E7A;
	--aa-line:rgba(26,20,16,.14);
	--aa-line-2:rgba(26,20,16,.22);
	--aa-line-strong:rgba(26,20,16,.32);
	--aa-shadow:0 1px 0 rgba(255,255,255,.7) inset, 0 22px 50px -32px rgba(60,30,15,.4), 0 2px 0 rgba(26,20,16,.04);
	--aa-serif:'Poppins',sans-serif;
	--aa-mono:'JetBrains Mono', ui-monospace, Menlo, monospace;
	--aa-sans:'Poppins', system-ui, sans-serif;
	font-family:var(--aa-sans);
	color:var(--aa-ink);
	position:relative;
	background:
		radial-gradient(1200px 700px at 6% -10%, #FFE6D8 0%, transparent 55%),
		radial-gradient(1000px 600px at 100% 0%, #FCE4CF 0%, transparent 50%),
		linear-gradient(180deg, var(--aa-cream) 0%, var(--aa-cream-2) 100%);
	padding:0px;
	border-radius:14px;
	border:1px solid var(--aa-line);
	box-shadow:var(--aa-shadow);
	overflow:visible;
}
body.ic-edit-skin .aa-editor-page{
	padding: 0px 0px 0px 0px !important;
}
.aa-editor-page::before{
	content:"";
	position:absolute;
	inset:0;
	pointer-events:none;
	background-image:url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='220' height='220'><filter id='n'><feTurbulence type='fractalNoise' baseFrequency='1.05' numOctaves='2' stitchTiles='stitch'/><feColorMatrix values='0 0 0 0 0.16  0 0 0 0 0.10  0 0 0 0 0.06  0 0 0 0.34 0'/></filter><rect width='100%25' height='100%25' filter='url(%23n)'/></svg>");
	opacity:.30;
	mix-blend-mode:multiply;
	border-radius:14px;
	z-index:0;
}
.aa-editor-page > *{ position:relative; z-index:1; }

/* ───────────────────────────── EDITORIAL MASTHEAD ───────────────────────────── */
.aa-press-mast{
	display:flex;
	align-items:flex-end;
	justify-content:space-between;
	gap:32px;
	padding:6px 4px 28px;
	border-bottom:1px solid var(--aa-line);
	margin-bottom:30px;
}
.aa-press-mast .aa-eyebrow{
	font-family:var(--aa-mono);
	font-size:11px;
	letter-spacing:.22em;
	color:var(--aa-ink-2);
	text-transform:uppercase;
	display:flex;
	align-items:center;
	gap:14px;
	margin-bottom:14px;
}
.aa-press-mast .aa-eyebrow em{
	font-family:var(--aa-serif);
	font-style:normal;
	font-size:18px;
	letter-spacing:0;
	color:var(--aa-coral-2);
	text-transform:none;
	margin:0 4px;
}
.aa-press-mast h1{
	font-family:var(--aa-serif);
	font-style:normal;
	font-weight:400;
	font-size:clamp(38px,4.6vw,62px);
	line-height:.95;
	margin:0;
	color:var(--aa-ink);
	letter-spacing:-.012em;
}
.aa-press-mast h1 .u{ position:relative; display:inline-block; }
.aa-press-mast h1 .u::after{
	content:"";
	position:absolute;
	left:-2px; right:-2px; bottom:.06em;
	height:.07em;
	background:var(--aa-coral);
	transform:skewX(-8deg);
	opacity:.42;
}
.aa-press-mast .aa-tag{
	display:block;
	font-family:var(--aa-serif);
	font-style:normal;
	font-size:18px;
	color:var(--aa-mute);
	margin-top:10px;
	letter-spacing:0;
}
.aa-press-mast .aa-meta{
	text-align:right;
	font-family:var(--aa-mono);
	font-size:10px;
	letter-spacing:.2em;
	color:var(--aa-mute);
	text-transform:uppercase;
	line-height:1.7;
	flex-shrink:0;
}
.aa-press-mast .aa-meta strong{
	display:block;
	font-family:var(--aa-serif);
	font-style:normal;
	font-weight:400;
	font-size:24px;
	letter-spacing:0;
	color:var(--aa-coral-2);
	text-transform:none;
	margin-bottom:4px;
}
.aa-press-mast .aa-meta-rule{
	display:inline-block;
	width:42px;
	height:1px;
	background:var(--aa-line-2);
	vertical-align:middle;
	margin:0 6px;
}

/* ───────────────────────────── LEFT RAIL · CONTROL PANEL ───────────────────────────── */
.aa-editor-page .controlDiv{
	padding-right:18px;
	overflow-y:auto;
	overflow-x:hidden;
	scroll-behavior:smooth;
	/* JS sets max-height to match the live preview column. Fallback below
	   keeps things sensible if the JS runs late or fails. */
	max-height:80vh;
}
.aa-editor-page .controlDiv::-webkit-scrollbar{ width:10px; height:10px; }
.aa-editor-page .controlDiv::-webkit-scrollbar-track{ background:var(--aa-cream-2); }
.aa-editor-page .controlDiv::-webkit-scrollbar-thumb{ background:var(--aa-coral); border-radius:4px; }
.aa-editor-page .controlDiv::-webkit-scrollbar-thumb:hover{ background:var(--aa-coral-2); }
.aa-editor-page #accordion1{ counter-reset:aa-card; }

/* Terminal cushion at the foot of the accordion stack.
   Approach: a pseudo-element block AFTER the last card. Padding on
   #accordion1 was invisible because of .h-100 (the stack stretches to fill
   the column so internal padding gets absorbed). Padding on .controlDiv
   was invisible because Chrome's scroll containers swallow padding-bottom.
   A block-level ::after with explicit height is rendered as real content
   inside the scroll viewport — the browser has to give it physical space,
   so the visible cream beneath the last visible card is guaranteed. */
.aa-editor-page #accordion1{ padding-bottom:0; }
.aa-editor-page #accordion1::after{
	content:"";
	display:block;
	height:36px;
	width:100%;
}
.aa-editor-page .controlDiv{ scroll-padding-bottom:48px; }

.aa-editor-page #accordion1 > .heading_color{
	font-family:var(--aa-mono) !important;
	font-size:11px !important;
	font-weight:500 !important;
	letter-spacing:.22em !important;
	text-transform:uppercase !important;
	color:var(--aa-ink-2) !important;
	padding:0 0 16px 2px !important;
	margin:0 0 16px !important;
	border-bottom:1px dashed var(--aa-line) !important;
	display:flex !important;
	align-items:center !important;
	gap:12px;
	line-height:1 !important;
}
.aa-editor-page #accordion1 > .heading_color::before{
	content:"——";
	color:var(--aa-coral);
	letter-spacing:0;
	font-weight:400;
}
.aa-editor-page #accordion1 > .heading_color::after{
	content:"N°09 · CONTROL PANEL";
	margin-left:auto;
	font-size:9.5px;
	color:var(--aa-mute);
	letter-spacing:.22em;
}

/* ── Channel cards (manila tab-cards) ── */
.aa-editor-page #accordion1 .card{
	background:linear-gradient(180deg, var(--aa-cream-2) 0%, var(--aa-paper-2) 100%) !important;
	border:1px solid var(--aa-line) !important;
	border-radius:8px !important;
	box-shadow:0 1px 0 rgba(255,255,255,.85) inset, 0 8px 18px -16px rgba(60,30,15,.28) !important;
	overflow:hidden;
	position:relative;
	margin-top:10px !important;
	counter-increment:aa-card;
	transition:transform .25s ease, box-shadow .25s ease;
}
.aa-editor-page #accordion1 .card:hover{
	background:linear-gradient(180deg, var(--aa-paper) 0%, var(--aa-paper-2) 100%) !important;
}
.aa-editor-page #accordion1 .card::before{
	content:"";
	position:absolute;
	left:0; top:0; bottom:0;
	width:3px;
	background:linear-gradient(180deg, var(--aa-coral-soft), var(--aa-coral-2));
	opacity:0;
	transition:opacity .35s ease;
}
.aa-editor-page #accordion1 .card:has(.collapse.show)::before{ opacity:1; }
.aa-editor-page #accordion1 .card:has(.collapse.show){
	background:linear-gradient(180deg, var(--aa-paper) 0%, var(--aa-paper-2) 100%) !important;
	box-shadow:0 1px 0 rgba(255,255,255,.9) inset, 0 14px 30px -20px rgba(60,30,15,.4) !important;
}

/* ── Card headers ── */
.aa-editor-page #accordion1 .card-header,
.aa-editor-page #accordion1 .card-header.omni-primary{
	background:transparent !important;
	border:0 !important;
	border-bottom:1px solid var(--aa-line) !important;
	padding:14px 18px 14px 22px !important;
	display:flex !important;
	align-items:center !important;
	gap:14px;
	cursor:pointer;
	position:relative;
	min-height:56px;
}
/* Index: an Outfit numeral inside a hairline ring. Replaces the mono upper-roman
   numeral + its hairline separator rule (the vertical line that sat between the
   number and the title). The ring is the whole treatment — no divider. */
.aa-editor-page #accordion1 .card-header::before{
	content:counter(aa-card, decimal);
	font-family:var(--aa-serif);
	font-size:13px;
	font-weight:600;
	color:var(--aa-mute);
	width:24px;
	height:24px;
	border-radius:50%;
	display:flex;
	align-items:center;
	justify-content:center;
	border:1.5px solid var(--aa-line-2);
	flex-shrink:0;
	transition:color .3s ease, border-color .3s ease, background .3s ease;
}
.aa-editor-page #accordion1 .card:has(.collapse.show) .card-header::before{
	color:var(--aa-coral-2);
	border-color:var(--aa-coral);
	background:rgba(240,117,94,.09);
}
.aa-editor-page #accordion1 .card-header > a,
.aa-editor-page #accordion1 .card-header a.text-white{
	color:var(--aa-ink) !important;
	font-family:var(--aa-serif) !important;
	font-style:normal !important;
	font-size:19px !important;
	font-weight:400 !important;
	letter-spacing:0 !important;
	text-transform:none !important;
	text-decoration:none !important;
	flex:1;
	line-height:1.2 !important;
	background:none !important;
	position:relative;
}
.aa-editor-page #accordion1 .card:has(.collapse.show) .card-header > a::after{
	content:"";
	position:absolute;
	left:-1px;
	right:-1px;
	bottom:.05em;
	height:.07em;
	background:var(--aa-coral);
	transform:skewX(-8deg);
	opacity:.42;
}
.aa-editor-page #accordion1 .card-header img{ display:none !important; }
/* Marker: a ringed plus drawn from two gradient bars. On open the ring turns 90°
   while the VERTICAL bar collapses to zero height — so the plus physically
   becomes a minus rather than swapping one glyph for another. */
.aa-editor-page #accordion1 .card-header::after{
	content:"";
	margin-left:auto;
	flex-shrink:0;
	width:20px;
	height:20px;
	border-radius:50%;
	border:1px solid var(--aa-line);
	background-image:
		linear-gradient(var(--aa-mute), var(--aa-mute)),
		linear-gradient(var(--aa-mute), var(--aa-mute));
	background-size:9px 1.5px, 1.5px 9px;
	background-position:center, center;
	background-repeat:no-repeat;
	transition:transform .34s cubic-bezier(.4,0,.2,1),
	           background-size .34s cubic-bezier(.4,0,.2,1),
	           border-color .3s ease,
	           background-color .3s ease;
}
.aa-editor-page #accordion1 .card:has(.collapse.show) .card-header::after{
	border-color:var(--aa-coral);
	background-color:rgba(240,117,94,.09);
	transform:rotate(90deg);
	background-image:
		linear-gradient(var(--aa-coral-2), var(--aa-coral-2)),
		linear-gradient(var(--aa-coral-2), var(--aa-coral-2));
	background-size:9px 1.5px, 1.5px 0px;
}

/* ── Card body ── */
.aa-editor-page #accordion1 .card-body{
	background:transparent !important;
	padding:18px 18px 20px 22px !important;
}
.aa-editor-page #accordion1 .card-block{ padding:0 !important; }

.aa-editor-page #accordion1 .card-body label,
.aa-editor-page #accordion1 .card-body .form-group > label{
	font-family:var(--aa-mono) !important;
	font-size:9.5px !important;
	letter-spacing:.18em !important;
	text-transform:uppercase !important;
	color:var(--aa-mute) !important;
	font-weight:500 !important;
	margin:0 0 6px !important;
	display:block;
}
.aa-editor-page #accordion1 .card-body label::before{
	content:"· ";
	color:var(--aa-coral);
	font-weight:600;
}

.aa-editor-page #accordion1 .card-body .form-control,
.aa-editor-page #accordion1 .card-body input[type=text],
.aa-editor-page #accordion1 .card-body input[type=number],
.aa-editor-page #accordion1 .card-body input[type=url],
.aa-editor-page #accordion1 .card-body input[type=email],
.aa-editor-page #accordion1 .card-body select{
	background:rgba(255,253,247,.65) !important;
	border:0 !important;
	border-bottom:1px solid var(--aa-line-2) !important;
	border-radius:0 !important;
	font-family:var(--aa-sans) !important;
	font-size:13px !important;
	color:var(--aa-ink) !important;
	padding:8px 4px !important;
	box-shadow:none !important;
	transition:border-color .25s ease, background .25s ease;
}
.aa-editor-page #accordion1 .card-body textarea,
.aa-editor-page #accordion1 .card-body textarea.form-control{
	background:rgba(255,253,247,.85) !important;
	border:1px solid var(--aa-line) !important;
	border-radius:6px !important;
	font-family:var(--aa-sans) !important;
	font-size:13px !important;
	color:var(--aa-ink) !important;
	padding:10px 12px !important;
	box-shadow:none !important;
	transition:border-color .25s ease, background .25s ease;
}
.aa-editor-page #accordion1 .card-body .form-control:focus,
.aa-editor-page #accordion1 .card-body input:focus,
.aa-editor-page #accordion1 .card-body select:focus,
.aa-editor-page #accordion1 .card-body textarea:focus{
	border-color:var(--aa-coral) !important;
	background:rgba(255,253,247,1) !important;
	outline:none !important;
}
.aa-editor-page #accordion1 .card-body .form-group{ margin-bottom:14px !important; }

/* Apply Changes → text-link CTA */
.aa-editor-page #accordion1 .btn_4,
.aa-editor-page #accordion1 .saveAutoStyling{
	background:transparent !important;
	border:1px solid var(--aa-line-strong) !important;
	color:var(--aa-coral-2) !important;
	font-family:var(--aa-mono) !important;
	font-size:10px !important;
	letter-spacing:.18em !important;
	text-transform:uppercase !important;
	font-weight:500 !important;
	padding:9px 16px !important;
	border-radius:6px !important;
	transition:all .3s ease;
	display:inline-flex !important;
	align-items:center;
	gap:10px;
	box-shadow:none !important;
}
.aa-editor-page #accordion1 .btn_4 .fui-check-inverted{ display:none !important; }
.aa-editor-page #accordion1 .btn_4::after,
.aa-editor-page #accordion1 .saveAutoStyling::after{
	content:"→";
	font-family:var(--aa-serif);
	font-style:normal;
	font-size:16px;
	color:var(--aa-coral);
	line-height:1;
}
.aa-editor-page #accordion1 .btn_4:hover{
	background:var(--aa-coral) !important;
	color:#fff !important;
	border-color:var(--aa-coral) !important;
}
.aa-editor-page #accordion1 .btn_4:hover::after{ color:#fff; }

/* Inline links inside card bodies */
.aa-editor-page #accordion1 .card-body a{
	color:var(--aa-coral-2) !important;
	font-family:var(--aa-sans) !important;
	font-size:12px !important;
	text-decoration:underline !important;
	text-decoration-color:var(--aa-coral) !important;
	text-underline-offset:3px;
}
.aa-editor-page #accordion1 .card-body p{
	font-family:var(--aa-sans) !important;
	font-size:12.5px !important;
	color:var(--aa-ink-2) !important;
	line-height:1.55 !important;
}

/* Checkbox labels */
.aa-editor-page #accordion1 .form-check-label{
	font-family:var(--aa-sans) !important;
	font-size:12.5px !important;
	color:var(--aa-ink) !important;
	text-transform:none !important;
	letter-spacing:0 !important;
	font-weight:400 !important;
	margin-left:6px;
}
.aa-editor-page #accordion1 .form-check-label::before{ content:"" !important; }

/* ───────────────────────────── RIGHT RAIL · PASTE-UP BOARD ───────────────────────────── */
.aa-editor-page .controlEditor{ padding-left:8px; }

/* ── Editor canvas WYSIWYG fit ──
   When the controls sidebar is open (.controlEditor sits on .col-xl-9),
   the canvas column is narrower than the published page width, which
   forces premature wraps inside template blocks (e.g. testimonial stars).
   `zoom` shrinks both the painted pixels and the layout box, so the
   canvas matches the published-page look without a transform-scale +
   width-reclaim hack (which previously created a horizontal scrollbar
   into empty whitespace). When the sidebar is hidden via "Show Full
   Screen" (.controlEditor flips to .col-xl-12), the canvas returns to 1:1. */
.ic-preview-frame{
	display:block;
	width:100%;
	border:0;
	background:#ffffff;
	min-height:480px;
}
/* The Bridge templates paint their atmosphere with `position:fixed; inset:0`
   background layers — under various class names across the 6 designs:
   .glow .grain .bg .grid .vignette .dots. That is correct on the LIVE page, but
   inside the editor those fixed layers escape their preview pane and cover the
   whole viewport — hiding the controls (worst on the Thank-You pane). Make each
   pane a positioning context and pin those layers to it instead. Live pages are
   unaffected (this rule is scoped under #templateEditor1/2). */
/* (Removed: the zoom:0.8 fit hack and the position:absolute atmosphere-override.
   Both existed only because the inline preview shared the editor's document; the
   <iframe srcdoc> now isolates the template, so its own position:fixed layers
   stay within their frame and the canvas is 1:1 with the published page.) */
/* Hide the global Support + Facebook side-rail (shared/footer.php) ONLY on the
   page editor, where its fixed right-edge tabs overlap the canvas/controls. It
   still shows on every other admin page. The editor sets body.ic-edit-skin, so
   we scope to that (works in every browser); the :has() form is a fallback. */
body.ic-edit-skin .aa-siderail,
body:has(.aa-editor-page) .aa-siderail{ display:none !important; }

/* ── Page Sections panel ───────────────────────────────────────────────────
   Show/hide any labelled block of the opt-in page. Styled entirely from the
   editor skin's own tokens (--ic-*, set in _ic_editor_skin.php) rather than
   literal colours, so it follows the theme instead of fighting it. Reads as a
   stack of switch rows: section name left, switch right, state obvious at a
   glance down a narrow (col-xl-3) rail. */
/* The skin paints .card-block white (_ic_editor_skin.php ~line 165). Its
   #accordion1 override next to it flips .card-body transparent but NOT
   .card-block, so inside this panel the hint sat on a white slab while the rows
   below sat on cream. Scope the fix to THIS card: that shared rule serves all
   ten .card-block users in this sidebar, so editing it would restyle every other
   panel to fix this one. The selector must also out-specify the skin's own
   (body.ic-edit-skin .aa-editor-page .card-block, itself !important) — when both
   are !important, specificity decides, and a bare .ic-sections-card .card-block
   loses and silently does nothing. */
body.ic-edit-skin .aa-editor-page .ic-sections-card .card-block{ background:transparent !important; }

.ic-sections-hint{
	font-family:var(--ic-body, 'Manrope', sans-serif);
	font-size:11px; line-height:1.5; color:var(--ic-mute, #8E7E74);
	margin:2px 0 12px;
}
.ic-sections-grouptitle{
	font-family:var(--ic-body, 'Manrope', sans-serif);
	font-size:10px; font-weight:700; letter-spacing:.09em; text-transform:uppercase;
	color:var(--ic-faint, #B9A79C); margin:0 0 8px;
}
.ic-sections-div{ border:0; border-top:1px solid var(--ic-line, #EAD9D0); margin:16px 0 12px; }
.ic-sections-list{ list-style:none; margin:0; padding:0; }
.ic-sections-list li{
	display:flex; align-items:center; gap:10px;
	padding:9px 10px; margin:0 0 6px;
	border-radius:10px;
	border:1px solid var(--ic-line, #EBD8CF);
	background:var(--ic-surface2, #F7F0EB);
	transition:border-color .15s ease, background .15s ease;
}
.ic-sections-list li:hover{ border-color:var(--ic-line2, #E1C9BD); }
.ic-section-name{
	flex:1 1 auto; min-width:0;
	font-family:var(--ic-disp, 'Outfit', sans-serif);
	font-size:12.5px; font-weight:600; letter-spacing:.01em;
	color:var(--ic-txt, #1F1712);
	/* A long data-label must never push the switch out of the row. */
	overflow:hidden; text-overflow:ellipsis; white-space:nowrap;
	transition:color .15s ease;
}

/* The switch. A button (not a checkbox) because the row state is driven from
   the preview DOM, not from form state — aria-checked carries it for a11y. */
.ic-section-toggle{
	flex:0 0 auto; position:relative;
	width:32px; height:18px; padding:0;
	border:0; border-radius:999px;
	background:var(--ic-faint, #B9A79C);
	cursor:pointer;
	transition:background .18s ease;
}
.ic-section-toggle::after{
	content:''; position:absolute; top:2px; left:2px;
	width:14px; height:14px; border-radius:50%;
	background:#fff;
	box-shadow:0 1px 2px rgba(31,23,18,.28);
	transition:transform .18s cubic-bezier(.4,0,.2,1);
}
/* ON = section visible on the live page. Coral is the skin's accent. */
.ic-section-toggle[aria-checked="true"]{ background:var(--ic-coral, #F0755E); }
.ic-section-toggle[aria-checked="true"]::after{ transform:translateX(14px); }
.ic-section-toggle:focus-visible{ outline:2px solid var(--ic-coral, #F0755E); outline-offset:2px; }

/* OFF rows recede — the name dims so a glance down the rail reads the page. */
.ic-sections-list li.is-hidden{ background:transparent; }
.ic-sections-list li.is-hidden .ic-section-name{ color:var(--ic-faint, #B9A79C); }

/* The capture section is locked (see icSectionIsLocked): a Bridge whose opt-in
   form is hidden captures nothing, so the switch is shown ON but inert. The
   reason is spelled out in .ic-sections-note rather than hidden in a tooltip. */
.ic-sections-list li.is-locked{
	background:var(--ic-surface, #fff);
	border-style:dashed;
	border-color:var(--ic-line2, #E1C9BD);
}
.ic-sections-list li.is-locked .ic-section-name{ color:var(--ic-txt2, #5F5148); }
.ic-section-lock{
	flex:0 0 auto; font-size:9.5px; line-height:1;
	color:var(--ic-mute, #8E7E74);
}
/* Locked reads as a confident ON, not a broken control — the dashed border and
   the padlock carry "you can't change this". A washed-out switch here looked
   like a rendering fault. */
.ic-section-toggle.is-locked{
	cursor:not-allowed;
	background:var(--ic-coral, #F0755E);
	opacity:.75;
}

.ic-sections-note{
	display:flex; align-items:flex-start; gap:7px;
	margin:12px 0 2px; padding:9px 10px;
	border-radius:10px;
	background:var(--ic-surface2, #F7F0EB);
	border:1px solid var(--ic-line, #EBD8CF);
	font-family:var(--ic-body, 'Manrope', sans-serif);
	font-size:10.5px; line-height:1.55; color:var(--ic-txt2, #5F5148);
}
.ic-sections-note .fa{ color:var(--ic-coral-2, #E75C43); font-size:10px; margin-top:2px; flex:0 0 auto; }
.ic-sections-empty{
	font-family:var(--ic-body, 'Manrope', sans-serif);
	font-size:11px; line-height:1.5; color:var(--ic-mute, #8E7E74);
	padding:4px 2px;
}
.aa-editor-page .controlEditor > .d-flex.px-3.pb-3{
	background:linear-gradient(180deg, var(--aa-paper) 0%, var(--aa-paper-2) 100%);
	border:1px solid var(--aa-line);
	border-radius:10px;
	padding:20px 24px !important;
	margin-bottom:0 !important;
	box-shadow:0 1px 0 rgba(255,255,255,.85) inset, 0 10px 24px -18px rgba(60,30,15,.32);
	position:relative;
	align-items:flex-end !important;
}
.aa-editor-page .controlEditor .heading_color.f20{
	font-family:var(--aa-serif) !important;
	font-style:normal !important;
	font-weight:400 !important;
	font-size:32px !important;
	color:var(--aa-ink) !important;
	margin:0 0 14px !important;
	line-height:.95 !important;
	letter-spacing:-.012em !important;
	position:relative;
	display:inline-block;
}
.aa-editor-page .controlEditor .heading_color.f20::before{
	content:"── PASTE-UP BOARD ──";
	display:block;
	font-family:var(--aa-mono);
	font-style:normal;
	font-size:9.5px;
	letter-spacing:.22em;
	color:var(--aa-mute);
	text-transform:uppercase;
	margin-bottom:8px;
	font-weight:500;
}
.aa-editor-page .controlEditor .heading_color.f20::after{
	content:"";
	position:absolute;
	left:-2px;
	right:-2px;
	bottom:.04em;
	height:.06em;
	background:var(--aa-coral);
	transform:skewX(-8deg);
	opacity:.42;
}

/* ── Manila file tabs (Funnel Page / Thank You Page) ── */
.aa-editor-page .controlEditor .d-flex.gap-3{
	gap:0 !important;
	margin-top:6px;
	position:relative;
	z-index:2;
}
.aa-editor-page .page_controls{
	background:linear-gradient(180deg, var(--aa-cream-2) 0%, #ECDFC8 100%) !important;
	border:1px solid var(--aa-line-2) !important;
	border-bottom:0 !important;
	color:var(--aa-mute) !important;
	font-family:var(--aa-mono) !important;
	font-size:10px !important;
	letter-spacing:.18em !important;
	text-transform:uppercase !important;
	font-weight:600 !important;
	padding:12px 22px 14px !important;
	width:auto !important;
	min-width:160px;
	position:relative;
	transition:all .3s ease;
	box-shadow:none !important;
	clip-path:polygon(8px 0, calc(100% - 8px) 0, 100% 100%, 0 100%);
	margin-right:-6px;
	display:inline-flex !important;
	align-items:center;
	gap:10px;
}
.aa-editor-page .page_controls::before{
	content:"I.";
	font-family:var(--aa-serif);
	font-style:normal;
	font-size:18px;
	color:var(--aa-coral);
	letter-spacing:0;
	text-transform:none;
	line-height:1;
}
.aa-editor-page #thankyou_page_controls.page_controls::before{ content:"II."; }
.aa-editor-page .page_controls.active{
	background:linear-gradient(180deg, var(--aa-paper) 0%, var(--aa-paper-2) 100%) !important;
	color:var(--aa-coral-2) !important;
	border-color:var(--aa-ink) !important;
	z-index:3;
}
.aa-editor-page .page_controls.active::before{ color:var(--aa-coral-2); }
.aa-editor-page .page_controls:hover:not(.active){ color:var(--aa-ink) !important; }

/* ── Wax-seal Update CTA ── */
.aa-editor-page .save_btn_section,
.aa-editor-page .update_btn_section{ height:auto !important; }

/* ── Autosave toggle (sits above Save Changes) ── */
.aa-editor-page .autosave-toggle-wrap{
	display:flex;
	align-items:center;
	justify-content:flex-end;
	gap:14px;
	margin-bottom:8px;
	padding-right:4px;
	min-height:22px;
}
.aa-editor-page .autosave-toggle{
	position:relative;
	display:inline-flex;
	align-items:center;
	gap:8px;
	cursor:pointer;
	user-select:none;
	margin:0;
	order:2;
	flex-shrink:0;
}
.aa-editor-page .autosave-toggle input{
	position:absolute;
	inset:0;
	width:100%;
	height:100%;
	opacity:0;
	margin:0;
	cursor:pointer;
	z-index:1;
}
.aa-editor-page .autosave-track{
	width:34px;
	height:18px;
	background:rgba(26,20,16,.18);
	border-radius:999px;
	position:relative;
	transition:background .25s ease;
	flex-shrink:0;
}
.aa-editor-page .autosave-thumb{
	position:absolute;
	top:2px;
	left:2px;
	width:14px;
	height:14px;
	border-radius:50%;
	background:#FFFDF7;
	box-shadow:0 1px 3px rgba(58,35,14,.25);
	transition:transform .25s ease;
}
/* Green when enabled — autosave being ON is a "safe/go" state, so the toggle
   reads green rather than the coral used for primary actions. */
.aa-editor-page .autosave-toggle input:checked ~ .autosave-track{
	background:linear-gradient(135deg, #34C77B 0%, #12B981 100%);
}
.aa-editor-page .autosave-toggle input:checked ~ .autosave-track .autosave-thumb{
	transform:translateX(16px);
}
.aa-editor-page .autosave-label{
	font-family:var(--aa-mono);
	font-size:10px;
	letter-spacing:.18em;
	text-transform:uppercase;
	font-weight:600;
	color:var(--aa-ink-2, #3B2F26);
}
.aa-editor-page .autosave-status{
	font-family:var(--aa-mono);
	font-size:10px;
	letter-spacing:.08em;
	color:var(--aa-mute, #8B7B6E);
	min-height:12px;
	transition:color .35s ease, opacity .2s ease;
	order:1;
	flex:0 0 auto;
	min-width:120px;
	text-align:right;
	white-space:nowrap;
	display:inline-flex;
	align-items:center;
	justify-content:flex-end;
	gap:5px;
}
.aa-editor-page .autosave-status .autosave-tick{
	display:inline-flex;
	align-items:center;
	justify-content:center;
	width:11px;
	height:11px;
	border-radius:50%;
	background:var(--aa-ok, #5BAE7E);
	color:#FFFDF7;
	font-size:8px;
	line-height:1;
	flex-shrink:0;
}
.aa-editor-page .autosave-status.is-success{ color:var(--aa-ok, #5BAE7E); }
.aa-editor-page .autosave-status.is-error{ color:var(--aa-c2, #E84848); }

.aa-editor-page .save_action,
.aa-editor-page .db-button{
	background:linear-gradient(135deg, var(--aa-coral-soft) 0%, var(--aa-coral) 50%, var(--aa-coral-2) 100%) !important;
	color:#FFF6EE !important;
	border:1px solid var(--aa-coral-2) !important;
	font-family:var(--aa-mono) !important;
	font-size:11px !important;
	letter-spacing:.2em !important;
	text-transform:uppercase !important;
	font-weight:600 !important;
	padding:14px 24px !important;
	border-radius:8px !important;
	box-shadow:0 1px 0 rgba(255,255,255,.3) inset, 0 10px 28px -12px rgba(232,72,72,.7), 0 0 0 1px rgba(199,46,46,.4) !important;
	transition:all .3s ease;
	display:inline-flex !important;
	align-items:center;
	gap:10px;
}
.aa-editor-page .save_action span,
.aa-editor-page .db-button span{ font-family:var(--aa-serif); font-style:normal; font-size:18px; font-weight:400; letter-spacing:0; text-transform:none; color:#FFF6EE; }
.aa-editor-page .save_action img,
.aa-editor-page .db-button img{ display:none !important; }

.aa-editor-page .btn_31{
	background:linear-gradient(135deg, var(--aa-coral-soft) 0%, var(--aa-coral) 50%, var(--aa-coral-2) 100%) !important;
	color:#FFF6EE !important;
	border:1px solid var(--aa-coral-2) !important;
	border-right:1px solid rgba(255,255,255,.25) !important;
	font-family:var(--aa-serif) !important;
	font-size:18px !important;
	letter-spacing:0 !important;
	text-transform:none !important;
	font-weight:400 !important;
	padding:14px 22px !important;
	border-radius:8px 0 0 8px !important;
	box-shadow:0 1px 0 rgba(255,255,255,.3) inset, 0 10px 28px -12px rgba(232,72,72,.7) !important;
	transition:all .3s ease;
	display:inline-flex !important;
	align-items:center;
	gap:10px;
}
.aa-editor-page .btn_31:hover{
	transform:translateY(-1px);
	box-shadow:0 1px 0 rgba(255,255,255,.4) inset, 0 14px 32px -10px rgba(232,72,72,.85) !important;
}
.aa-editor-page .btn_32{
	background:linear-gradient(135deg, var(--aa-coral) 0%, var(--aa-coral-2) 100%) !important;
	border:1px solid var(--aa-coral-2) !important;
	border-left:0 !important;
	border-radius:0 8px 8px 0 !important;
	padding:14px 16px !important;
	box-shadow:0 1px 0 rgba(255,255,255,.3) inset, 0 10px 28px -12px rgba(232,72,72,.7) !important;
	transition:all .3s ease;
	position:relative;
	font-size:0 !important;
	min-width:42px;
}
.aa-editor-page .btn_32 img{ display:none !important; }
.aa-editor-page .btn_32::after{
	content:"[ ▾ ]";
	font-family:var(--aa-mono);
	font-size:11px;
	font-weight:600;
	color:#FFF6EE;
	letter-spacing:0;
}
.aa-editor-page .btn_32:hover{ transform:translateY(-1px); }

/* Dropdown menu */
.aa-editor-page .dropdown-section{
	background:linear-gradient(180deg, var(--aa-paper) 0%, var(--aa-paper-2) 100%) !important;
	border:1px solid var(--aa-line) !important;
	border-radius:8px !important;
	box-shadow:0 22px 50px -22px rgba(60,30,15,.45) !important;
	overflow:visible;
	min-width:200px;
	position:absolute !important;
	top:calc(100% + 8px) !important;
	right:0 !important;
	left:auto !important;
	z-index:1050;
}
.aa-editor-page .dropdown-options{
	padding:6px 0 !important;
	position:static !important;
	top:auto !important;
	left:auto !important;
	width:auto !important;
	background:transparent !important;
	border-radius:0 !important;
}
.aa-editor-page .dropdown-options > div{
	padding:11px 18px !important;
	border-bottom:1px dashed var(--aa-line);
	display:flex !important;
	align-items:center;
	gap:12px;
	transition:background .25s ease;
}
.aa-editor-page .dropdown-options > div:last-child{ border-bottom:0; }
.aa-editor-page .dropdown-options > div:hover{ background:rgba(232,72,72,.07); }
.aa-editor-page .dropdown-options img{ display:none !important; }
.aa-editor-page .dropdown-options > div::before{
	content:"→";
	font-family:var(--aa-serif);
	font-style:normal;
	font-size:17px;
	color:var(--aa-coral);
	line-height:1;
}
.aa-editor-page .dropdown-options a{
	font-family:var(--aa-mono) !important;
	font-size:10px !important;
	letter-spacing:.16em !important;
	text-transform:uppercase !important;
	color:var(--aa-ink) !important;
	text-decoration:none !important;
	font-weight:500 !important;
	flex:1;
}
.aa-editor-page .dropdown-options a:hover{ color:var(--aa-coral-2) !important; }

/* ── Light-table iframe wrapper ── */
.aa-editor-page .editorDiv{
	background:#fff;
	border:1px solid var(--aa-ink);
	border-top:0;
	border-radius:0 0 6px 6px;
	padding:0;
	box-shadow:inset 0 0 0 1px rgba(0,0,0,.06), 0 28px 60px -22px rgba(26,20,16,.4);
	position:relative;
	margin-top:-1px;
}
.aa-editor-page .editorDiv::before{
	content:"── PROOF · LIVE PREVIEW · DO NOT DISTURB ──";
	display:block;
	font-family:var(--aa-mono);
	font-size:9px;
	font-weight:500;
	letter-spacing:.22em;
	color:var(--aa-mute);
	text-transform:uppercase;
	background:var(--aa-ink);
	color:#FBF6EE;
	padding:9px 18px;
	text-align:center;
	border-bottom:1px solid #000;
}
.aa-editor-page .editorDiv #templateEditor{
	background:#fff;
	overflow:auto;
	max-height:80vh;
}
.aa-editor-page .editorDiv #templateEditor::-webkit-scrollbar{ width:10px; height:10px; }
.aa-editor-page .editorDiv #templateEditor::-webkit-scrollbar-track{ background:var(--aa-cream-2); }
.aa-editor-page .editorDiv #templateEditor::-webkit-scrollbar-thumb{ background:var(--aa-coral); border-radius:4px; }
.aa-editor-page .editorDiv #templateEditor::-webkit-scrollbar-thumb:hover{ background:var(--aa-coral-2); }

/* ── Retone any orange-themed inner cards (Voice Engine launch card, appt toggle, etc.) ── */
.aa-editor-page .vc-launch-card{
	background:linear-gradient(180deg, var(--aa-cream-2) 0%, var(--aa-paper-2) 100%) !important;
	border:1px solid var(--aa-line) !important;
	border-radius:8px !important;
	box-shadow:0 1px 0 rgba(255,255,255,.85) inset, 0 6px 14px -12px rgba(60,30,15,.3) !important;
}
.aa-editor-page .vc-launch-card:hover{
	border-color:var(--aa-coral) !important;
	box-shadow:0 1px 0 rgba(255,255,255,.85) inset, 0 10px 22px -14px rgba(232,72,72,.4) !important;
}
.aa-editor-page .vc-launch-icon{
	background:linear-gradient(135deg, var(--aa-coral-soft), var(--aa-coral-2)) !important;
	box-shadow:0 4px 12px -4px rgba(232,72,72,.45) !important;
	border-radius:8px !important;
}
.aa-editor-page .vc-launch-title{
	font-family:var(--aa-serif) !important;
	font-style:normal;
	font-weight:400 !important;
	font-size:17px !important;
	color:var(--aa-ink) !important;
}
.aa-editor-page .vc-launch-meta{
	font-family:var(--aa-mono) !important;
	font-size:9.5px !important;
	letter-spacing:.14em !important;
	text-transform:uppercase !important;
	color:var(--aa-mute) !important;
}
.aa-editor-page .appt-toggle-row{
	background:linear-gradient(180deg, var(--aa-cream-2) 0%, var(--aa-paper-2) 100%) !important;
	border:1px solid var(--aa-line) !important;
	border-radius:8px !important;
}
.aa-editor-page .appt-toggle-row.is-on{
	background:linear-gradient(180deg, var(--aa-paper) 0%, var(--aa-paper-2) 100%) !important;
	border-color:var(--aa-coral) !important;
}
.aa-editor-page .appt-toggle-icon.on{
	background:linear-gradient(135deg, var(--aa-coral-soft), var(--aa-coral-2)) !important;
	box-shadow:0 4px 12px -4px rgba(232,72,72,.45) !important;
	border-radius:8px !important;
}
.aa-editor-page .appt-toggle-icon.off{
	background:var(--aa-cream-2) !important;
	color:var(--aa-mute) !important;
	border-radius:8px !important;
}
.aa-editor-page .appt-toggle-label{
	font-family:var(--aa-serif) !important;
	font-style:normal;
	font-weight:400 !important;
	font-size:17px !important;
	color:var(--aa-ink) !important;
}
.aa-editor-page .appt-toggle-status{
	font-family:var(--aa-mono) !important;
	font-size:9.5px !important;
	letter-spacing:.18em !important;
	text-transform:uppercase !important;
	font-weight:500 !important;
}
.aa-editor-page .appt-toggle-status.on{ color:var(--aa-coral-2) !important; }
.aa-editor-page .appt-toggle-status.off{ color:var(--aa-mute) !important; }

/* Toggle switch */
.aa-editor-page .appt-switch .slider{ background:var(--aa-line-2) !important; }
.aa-editor-page .appt-switch input:checked + .slider{
	background:linear-gradient(135deg, var(--aa-coral), var(--aa-coral-2)) !important;
}

/* Sheet config panel */
.aa-editor-page .appt-sheet-panel{
	background:linear-gradient(180deg, var(--aa-paper) 0%, var(--aa-paper-2) 100%) !important;
	border:1px dashed var(--aa-line-2) !important;
	border-radius:8px !important;
}
.aa-editor-page .appt-sheet-label{
	font-family:var(--aa-mono) !important;
	font-size:9.5px !important;
	letter-spacing:.18em !important;
	text-transform:uppercase !important;
	color:var(--aa-mute) !important;
	font-weight:500 !important;
}
.aa-editor-page .appt-sheet-label::before{
	content:"· ";
	color:var(--aa-coral);
	font-weight:600;
}
.aa-editor-page .appt-sheet-input{
	background:rgba(255,253,247,.85) !important;
	border:0 !important;
	border-bottom:1px solid var(--aa-line-2) !important;
	border-radius:0 !important;
	font-family:var(--aa-sans) !important;
	font-size:13px !important;
	color:var(--aa-ink) !important;
	padding:8px 4px !important;
	box-shadow:none !important;
	transition:border-color .25s ease;
}
.aa-editor-page .appt-sheet-input:focus{
	border-color:var(--aa-coral) !important;
	box-shadow:none !important;
	outline:none !important;
}
.aa-editor-page .appt-sheet-help{
	font-family:var(--aa-serif) !important;
	font-style:normal;
	font-size:12.5px !important;
	color:var(--aa-mute) !important;
	line-height:1.55 !important;
}
.aa-editor-page .appt-sheet-email-row{
	background:rgba(232,72,72,.06) !important;
	border:1px dashed var(--aa-line) !important;
	border-radius:6px !important;
	padding:9px 12px !important;
}
.aa-editor-page .appt-sheet-email{
	font-family:var(--aa-mono) !important;
	font-size:10px !important;
	color:var(--aa-ink-2) !important;
	letter-spacing:.02em !important;
}
.aa-editor-page .appt-copy-btn{
	color:var(--aa-coral-2) !important;
	font-family:var(--aa-mono) !important;
	font-size:12px !important;
	border-radius:4px !important;
	transition:background .25s ease;
}
.aa-editor-page .appt-copy-btn:hover{ background:rgba(232,72,72,.1) !important; }

/* Test Connection / Save buttons */
.aa-editor-page .appt-test-btn{
	background:transparent !important;
	border:1px solid var(--aa-line-strong) !important;
	color:var(--aa-coral-2) !important;
	font-family:var(--aa-mono) !important;
	font-size:10px !important;
	letter-spacing:.18em !important;
	text-transform:uppercase !important;
	font-weight:500 !important;
	padding:9px 16px !important;
	border-radius:6px !important;
	transition:all .3s ease;
}
.aa-editor-page .appt-test-btn:hover{
	background:var(--aa-coral) !important;
	color:#fff !important;
	border-color:var(--aa-coral) !important;
}
.aa-editor-page .appt-save-btn{
	background:linear-gradient(135deg, var(--aa-coral-soft) 0%, var(--aa-coral) 50%, var(--aa-coral-2) 100%) !important;
	color:#FFF6EE !important;
	border:1px solid var(--aa-coral-2) !important;
	font-family:var(--aa-mono) !important;
	font-size:10px !important;
	letter-spacing:.18em !important;
	text-transform:uppercase !important;
	font-weight:600 !important;
	padding:9px 16px !important;
	border-radius:6px !important;
	box-shadow:0 1px 0 rgba(255,255,255,.3) inset, 0 8px 18px -10px rgba(232,72,72,.6) !important;
	transition:all .3s ease;
}
.aa-editor-page .appt-save-btn:hover{
	transform:translateY(-1px);
	box-shadow:0 1px 0 rgba(255,255,255,.4) inset, 0 12px 24px -10px rgba(232,72,72,.75) !important;
}

/* ── Fullscreen toggle pills ── */
.aa-editor-page .toggleEditor.showfullscreen,
.aa-editor-page .toggleEditor.showfullcontrolls{
	background:var(--aa-coral) !important;
	border:1px solid var(--aa-coral-2) !important;
	border-radius:50% !important;
	width:32px; height:32px;
	display:flex !important;
	align-items:center;
	justify-content:center;
	box-shadow:0 6px 14px -8px rgba(232,72,72,.6);
	z-index:5;
	top:-12px !important;
	right:-12px !important;
}
.aa-editor-page .toggleEditor img{ filter:brightness(0) invert(1); width:14px; }

/* ── Inputs inside cards: select2 fix ── */
.aa-editor-page #accordion1 .select2-container--default .select2-selection--single{
	background:rgba(255,253,247,.65) !important;
	border:0 !important;
	border-bottom:1px solid var(--aa-line-2) !important;
	border-radius:0 !important;
	height:38px !important;
	padding:6px 4px !important;
}
.aa-editor-page #accordion1 .select2-selection__rendered{
	font-family:var(--aa-sans) !important;
	color:var(--aa-ink) !important;
	font-size:13px !important;
	line-height:24px !important;
	padding-left:0 !important;
}
.aa-editor-page #accordion1 .select2-selection__arrow b{ border-color:var(--aa-coral) transparent transparent transparent !important; }

/* ── Checkboxes inside cards ── */
.aa-editor-page #accordion1 .form-check-input{
	accent-color:var(--aa-coral);
	margin-right:8px;
}

/* ── Responsive ── */
@media (max-width: 1199px){
	.aa-editor-page{ padding:0px; }
	.aa-press-mast{ flex-direction:column; align-items:flex-start; gap:14px; }
	.aa-press-mast .aa-meta{ text-align:left; }
	.aa-editor-page .controlDiv{ padding-right:0; }
	.aa-editor-page .controlEditor{ padding-left:0; margin-top:18px; }
}
@media (max-width: 767px){
	.aa-editor-page .controlEditor > .d-flex.px-3.pb-3{ flex-direction:column !important; align-items:stretch !important; gap:14px; }
	.aa-editor-page .page_controls{ min-width:auto; }
}
</style>
<script>document.documentElement.classList.remove('aa-editor-loading');</script>


<script>
	// ── Isolated-preview frame helpers ───────────────────────────────────
	// The Funnel (#templateFrame1) and Thank-You (#templateFrame2) previews are
	// real <iframe srcdoc> documents, so editor/global CSS can't leak in. Every
	// editing handler uses `iframe = $(ic_frameDocs())` — a jQuery set over BOTH
	// frame documents — so the existing `iframe.find(...)` calls keep working
	// unchanged across the frame boundary.
	function ic_frameDocs(){
		var docs = [];
		['templateFrame1','templateFrame2'].forEach(function(id){
			var f = document.getElementById(id);
			if (f && f.contentDocument && f.contentDocument.body) docs.push(f.contentDocument);
		});
		return docs.length ? docs : [document];
	}
	// The FUNNEL frame only (#templateFrame1). Use this for things that exist in
	// BOTH frames but must be read/written from ONE — e.g. the tracking-code divs
	// (.analyticsCode/.retarget_analyticsCode/.chatbotEmbedCode). Reading those
	// across both frames concatenates them and doubles the stored base64, which
	// then decodes to garbage on the public page.
	function ic_funnelDoc(){
		var f = document.getElementById('templateFrame1');
		return (f && f.contentDocument && f.contentDocument.body) ? [f.contentDocument] : [document];
	}
	// Auto-size each preview frame to its content so the canvas reads like the
	// real published page (no inner scrollbar, no zoom hack needed).
	function ic_resizeFrames(){
		['templateFrame1','templateFrame2'].forEach(function(id){
			var f = document.getElementById(id);
			if (f && f.contentDocument && f.contentDocument.body){
				try { f.style.height = f.contentDocument.body.scrollHeight + 'px'; } catch(e){}
			}
		});
	}
	// Keep frames sized as content/edits change.
	setInterval(ic_resizeFrames, 600);

	/* ──────────────────────────────────────────────────────────────────────────
	   Sections show/hide (Bridge opt-in page).

	   State lives ON the section in the preview DOM, not in a side table: the
	   save path serialises #funnelrb's live HTML straight into funnels.content
	   (see the payload builder below), so an inline display:none rides along and
	   the PUBLISHED page hides the section with no renderer change. data-ic-hidden
	   is the semantic flag we read back; the inline style is what actually hides.
	   Hiding is non-destructive — the copy stays in the DOM, so unhiding restores
	   it and autopopulate still fills it on a rebuild.
	   ────────────────────────────────────────────────────────────────────────── */
	// v1 templates label every <section data-label>; v2 (landing-pages2) templates
	// don't — they use class/id/data-reveal. Match ALL <section>s and derive a
	// readable name (icSectionDerivedLabel) when data-label is absent, so the panel
	// works on bridges already baked on a v2 template, with no rebuild.
	var IC_SECTION_SEL = 'section';

	// The section holding the capture form can never be hidden: a Bridge that
	// captures nothing is a dead Bridge (no lead → no Triple-Trigger). Identified
	// by the capture contract's form id rather than by label text, which is
	// authored per-template and would drift.
	function icSectionIsLocked($sec){
		return $sec.find('#ic-optin-form').length > 0;
	}

	// The thank-you page's mandatory part is the live AI voice-call widget: it is
	// the whole reason the page exists, mirroring how the opt-in form is mandatory
	// on the capture page. Identified by the callcard / ic-call-section markers.
	function icTySectionIsLocked($sec){
		return $sec.find('.callcard').length > 0 || $sec.hasClass('ic-call-section');
	}

	// data-label is authored per-template and is inconsistent: most templates use
	// "Opt-in Form", affiliate-v2 uses slugs ("optin-form", "bonus-stack"). It is
	// also a STYLING HOOK there (section[data-label="faq"] rules), so the labels
	// can't simply be renamed in the templates. Prettify slugs for DISPLAY only —
	// the DOM attribute is never touched.
	function icSectionLabel(raw){
		var s = $.trim(raw || '');
		if (!s) return '';
		if (s.indexOf(' ') !== -1) return s;   // already prose ("Opt-in Form") — leave alone
		if (s === s.toUpperCase()) return s;   // acronym ("FAQ", "CTA") — don't touch the case
		// slug or bare lowercase word: "bonus-stack" → "Bonus Stack", "hero" → "Hero"
		return s.replace(/[-_]+/g, ' ').replace(/\b\w/g, function(c){ return c.toUpperCase(); });
	}

	// v2 templates carry no data-label, so derive a readable name from the section's
	// own markers (form id, a known content class, aria-label). DISPLAY only — the DOM
	// is never touched. Falls through to "Section N" (handled by the caller).
	function icSectionDerivedLabel($sec){
		if ($sec.find('#ic-optin-form').length) return 'Opt-in Form';
		var alias = {
			'hero':'Hero', 'offer-capture':'Opt-in Form', 'optin-form':'Opt-in Form',
			'quick-proof':'Highlights', 'quick-stats':'Highlights', 'scorecard':'Scorecard',
			'fit':"Who It's For", 'quote-section':'Testimonials', 'testimonials':'Testimonials',
			'bonus':'Bonus', 'bonus-stack':'Bonus', 'final':'Final CTA', 'faq':'FAQ',
			'logos':'Logos', 'stats':'Stats', 'benefits':'Benefits', 'features':'Features',
			'pricing':'Pricing', 'footer':'Footer', 'what-happens-next':'What Happens Next'
		};
		var cls = ($sec.attr('class') || '').toLowerCase().split(/\s+/);
		var generic = { 'section':1, 'container':1, 'block':1, 'wrap':1, '':1 };
		var i;
		for (i = 0; i < cls.length; i++){ if (alias[cls[i]]) return alias[cls[i]]; }
		for (i = 0; i < cls.length; i++){ if (!generic[cls[i]]) return icSectionLabel(cls[i]); }
		var aria = $.trim($sec.attr('aria-label') || '');
		return aria || '';
	}

	function icSectionsDoc(){
		// Opt-in (funnel) preview frame.
		var docs = ic_funnelDoc();
		return docs && docs.length ? docs[0] : null;
	}

	function icSectionsThankyouDoc(){
		// Thank-you preview frame (#templateFrame2). Null until it has loaded.
		var f = document.getElementById('templateFrame2');
		return (f && f.contentDocument && f.contentDocument.body) ? f.contentDocument : null;
	}

	function icSectionSetHidden($sec, hidden){
		if (hidden){
			$sec.attr('data-ic-hidden', '1').css('display', 'none');
		} else {
			// Remove rather than set display:'' so we don't leave an empty style
			// attribute overriding the template's own display rule.
			$sec.removeAttr('data-ic-hidden');
			$sec[0].style.removeProperty('display');
			if ($sec.attr('style') === '') $sec.removeAttr('style');
		}
	}

	// Render one section group (opt-in OR thank-you) into its <ul>. Parameterised by
	// the preview doc, the target list, the lock predicate for that page, and the
	// title shown on a locked row — so both groups share one renderer.
	function icRenderSectionGroup(doc, $list, lockFn, lockTitle){
		if (!$list.length) return;
		$list.empty();
		if (!doc){ return; }   // frame not loaded yet — a later render (on open) fills it

		var $secs = $(doc).find(IC_SECTION_SEL);
		if (!$secs.length){
			// No <section> blocks at all (a very old or custom page). Say so instead
			// of rendering an empty box that reads as broken.
			$list.append('<li class="ic-sections-empty">This page has no sections to manage. Rebuild it on a current template to manage sections here.</li>');
			return;
		}

		$secs.each(function(i){
			var $sec   = $(this);
			var label  = icSectionLabel($sec.attr('data-label')) || icSectionDerivedLabel($sec) || ('Section ' + (i + 1));
			var hidden = $sec.attr('data-ic-hidden') === '1';
			var locked = lockFn($sec);

			var $li = $('<li></li>')
				.toggleClass('is-hidden', hidden)
				.toggleClass('is-locked', locked)
				.attr('data-ic-index', i);

			$('<span class="ic-section-name"></span>').text(label).appendTo($li);

			// A locked row carries a small padlock so the dashed row explains itself
			// even before you reach the note underneath.
			if (locked) $li.append('<i class="fa fa-lock ic-section-lock" aria-hidden="true"></i>');

			// Switch semantics are the inverse of the old eye: ON = section is ON the
			// page. A locked row shows ON (it is always published) but never toggles.
			var on = locked || !hidden;
			var $btn = $('<button type="button" class="ic-section-toggle" role="switch"></button>')
				.attr('aria-checked', on ? 'true' : 'false')
				.attr('aria-label', label)
				.toggleClass('is-locked', locked)
				.attr('title', locked ? lockTitle : (hidden ? 'Turn this section on' : 'Turn this section off'));
			if (locked) $btn.prop('disabled', true);
			$btn.appendTo($li);

			$list.append($li);
		});
	}

	function icSectionsRender(){
		icRenderSectionGroup(icSectionsDoc(),         $('#icSectionsList'),   icSectionIsLocked,
			'The opt-in form is what captures the lead — it can’t be turned off.');
		icRenderSectionGroup(icSectionsThankyouDoc(), $('#icSectionsListTy'), icTySectionIsLocked,
			'The AI voice call is the point of the thank-you page — it can’t be turned off.');
	}

	// Delegated: the list is rebuilt on every open, so per-row handlers would leak.
	// The row's owning <ul data-ic-group> picks the page (opt-in vs thank-you) and
	// its matching doc + lock predicate.
	$(document).on('click', '.ic-section-toggle', function(){
		var $li  = $(this).closest('li');
		var i    = parseInt($li.attr('data-ic-index'), 10);
		var grp  = $li.closest('ul').attr('data-ic-group');
		var isTy = (grp === 'thankyou');
		var doc    = isTy ? icSectionsThankyouDoc() : icSectionsDoc();
		var lockFn = isTy ? icTySectionIsLocked     : icSectionIsLocked;
		if (!doc || isNaN(i)) return;

		var $sec = $(doc).find(IC_SECTION_SEL).eq(i);
		if (!$sec.length || lockFn($sec)) return;

		icSectionSetHidden($sec, $sec.attr('data-ic-hidden') !== '1');
		icSectionsRender();
		ic_resizeFrames();   // the page just got shorter/taller
	});

	// Build from the live DOM each time the panel opens: the frame loads async and
	// the user may have rebuilt/autopopulated since, so a cached list would go stale.
	$(document).on('show.bs.collapse', '#icSections', icSectionsRender);

	var img_status = 0;
$(document).ready(function(){
	var editUrl = "<?php echo site_url('template/'.$count.'/'.$funnel_id); ?>";
	var baseUrl = "<?php echo base_url(); ?>";
	var funnel_id = "<?= $funnel_id ?>"; 
	var landingpageurl = "<?= $templatedata[0]['Data']->publish_link ?>";

	<?php 
	
		$auto_data = $templatedata[0]['Data']->autoresponder_data;
		if(!empty($auto_data)){
			$auto_data = $this->encryption->decrypt($auto_data);
		}
	?>

	var autoresponder_data = '<?= $auto_data ?>';
	if( typeof autoresponder_data == 'string' && autoresponder_data != ''){
		autoresponder_data = JSON.parse(autoresponder_data);
	}

	// ── Match left rail height to the full right column (toolbar + preview).
	// .controlEditor wraps the Funnel/Thank You tabs, autosave row, Save
	// Changes button, AND .editorDiv. Measuring just .editorDiv leaves the
	// sidebar shorter than the right side; measuring .controlEditor counts
	// every row above the proof frame too.
	function syncControlDivHeight(){
		var $rail  = $('.controlDiv');
		var $right = $('.controlEditor');
		if (!$rail.length || !$right.length) return;
		var h = $right.outerHeight();
		if (h && h > 200) {
			$rail.css('max-height', h + 'px');
		}
	}
	$(window).on('load resize', syncControlDivHeight);
	// Also re-sync when the editor toggles fullscreen (the right column resizes).
	$(document).on('click', '.toggleEditor', function(){ setTimeout(syncControlDivHeight, 350); });
	// First pass — once the DOM is parsed, before images finish loading.
	setTimeout(syncControlDivHeight, 200);

	// ── Sidebar accordion: scroll the opened section into view inside .controlDiv.
	// When a card near the bottom is expanded, the user can lose track of where
	// the new fields live. Wait for Bootstrap's shown event so the full height is
	// known, then center the card header in the scrollable rail.
	$('#accordion1').on('shown.bs.collapse', function(e){
		var $panel  = $(e.target);                  // the .collapse element that opened
		var $card   = $panel.closest('.card');
		var $rail   = $('.controlDiv');
		if (!$card.length || !$rail.length) return;

		var railTop      = $rail.offset().top;
		var cardTop      = $card.offset().top;
		var currentScroll = $rail.scrollTop();
		var railHeight   = $rail.height();
		var cardHeight   = $card.outerHeight();

		// If the card is taller than the rail, align its header to the top.
		// Otherwise, try to keep ~24px of breathing room above it.
		var targetOffset = (cardHeight > railHeight - 48)
			? (cardTop - railTop) + currentScroll - 12
			: (cardTop - railTop) + currentScroll - 24;

		$rail.stop().animate({ scrollTop: Math.max(0, targetOffset) }, 320);
	});

	// ── Voice Engine Configuration link: confirm before navigating away.
	// The Continue button is rendered as a real anchor so the browser
	// performs the navigation natively (no JS redirect needed).
	$(document).on('click', '.vc-launch-card', function(e){
		var href = $(this).attr('href');
		if (!href) return;

		e.preventDefault();
		e.stopPropagation();

		if (typeof Swal === 'undefined') {
			icConfirm({
				title: 'Open Voice Engine Configuration?',
				message: 'Save your work first. You are about to leave the editor.',
				confirmText: 'Continue'
			}).then(function(ok){
				if (ok) {
					window.__skipBeforeUnload = true;
					window.location.href = href;
				}
			});
			return;
		}

		var safeHref = $('<div>').text(href).html();
		if (!document.getElementById('vc-aa-swal-style')) {
			var style = document.createElement('style');
			style.id = 'vc-aa-swal-style';
			style.textContent =
				'.vc-aa-popup{' +
					'background:var(--aa-paper, #FFF8F0)!important;' +
					'border:1px solid var(--aa-line, #E8DFD3)!important;' +
					'border-radius:6px!important;' +
					'box-shadow:0 24px 60px -24px rgba(60,30,15,.35), 0 2px 0 rgba(255,255,255,.6) inset!important;' +
					'padding:28px 28px 24px!important;' +
					'font-family:"Poppins", system-ui, -apple-system, sans-serif!important;' +
				'}' +
				'.vc-aa-title{' +
					'font-family:"Poppins", system-ui, sans-serif!important;' +
					'font-weight:600!important;' +
					'font-size:22px!important;' +
					'color:var(--aa-ink, #1F1B16)!important;' +
					'letter-spacing:-.01em!important;' +
					'margin-bottom:6px!important;' +
				'}' +
				'.vc-aa-html{' +
					'color:var(--aa-ink-2, #5A5046)!important;' +
					'font-size:14.5px!important;' +
					'line-height:1.55!important;' +
				'}' +
				'.vc-aa-eyebrow{' +
					'font-family:"JetBrains Mono", ui-monospace, monospace;' +
					'font-size:10.5px;' +
					'letter-spacing:.22em;' +
					'text-transform:uppercase;' +
					'color:var(--aa-c2, #E84848);' +
					'margin-bottom:10px;' +
					'display:block;' +
				'}' +
				'.vc-aa-actions{' +
					'display:flex;justify-content:center;gap:10px;' +
					'margin-top:22px;flex-wrap:wrap;' +
				'}' +
				'.swal2-container .vc-aa-popup .vc-aa-swal-actions{' +
					'display:flex!important;justify-content:center!important;gap:10px!important;' +
					'margin-top:10px!important;flex-wrap:wrap!important;' +
				'}' +
				'.swal2-container .vc-aa-popup .vc-aa-btn,' +
				'.swal2-container .vc-aa-popup button.vc-aa-btn{' +
					'background:transparent;' +
					'font-family:"JetBrains Mono", ui-monospace, monospace!important;' +
					'font-size:12px!important;' +
					'font-weight:600!important;' +
					'letter-spacing:.20em!important;' +
					'text-transform:uppercase!important;' +
					'padding:13px 22px!important;' +
					'border-radius:2px!important;' +
					'border:1px solid var(--aa-ink, #1F1B16)!important;' +
					'cursor:pointer!important;' +
					'transition:background .18s ease, color .18s ease, border-color .18s ease, transform .18s ease!important;' +
					'display:inline-flex!important;align-items:center!important;gap:10px!important;' +
					'text-decoration:none!important;' +
					'line-height:1!important;' +
					'box-shadow:none!important;' +
				'}' +
				'.swal2-container .vc-aa-popup .vc-aa-btn-primary{' +
					'background:var(--aa-ink, #1F1B16)!important;' +
					'color:var(--aa-cream, #FAF3E7)!important;' +
				'}' +
				'.swal2-container .vc-aa-popup .vc-aa-btn-primary:hover{' +
					'background:var(--aa-c2, #E84848)!important;' +
					'border-color:var(--aa-c2, #E84848)!important;' +
					'color:#fff!important;' +
				'}' +
				'.swal2-container .vc-aa-popup .vc-aa-btn-primary .vc-aa-arrow{transition:transform .18s ease;display:inline-block;}' +
				'.swal2-container .vc-aa-popup .vc-aa-btn-primary:hover .vc-aa-arrow{transform:translateX(4px);}' +
				'.swal2-container .vc-aa-popup button.vc-aa-btn-ghost,' +
				'.swal2-container .vc-aa-popup .swal2-cancel.vc-aa-btn-ghost{' +
					'background:transparent!important;' +
					'color:var(--aa-ink, #1F1B16)!important;' +
				'}' +
				'.swal2-container .vc-aa-popup button.vc-aa-btn-ghost:hover,' +
				'.swal2-container .vc-aa-popup .swal2-cancel.vc-aa-btn-ghost:hover{' +
					'background:var(--aa-ink, #1F1B16)!important;' +
					'color:var(--aa-cream, #FAF3E7)!important;' +
					'border-color:var(--aa-ink, #1F1B16)!important;' +
				'}' +
				'.swal2-container .vc-aa-popup .swal2-icon{' +
					'border-color:var(--aa-line, #E8DFD3)!important;' +
					'color:var(--aa-c2, #E84848)!important;' +
					'margin:0 auto 14px!important;' +
				'}' +
				'.swal2-container .vc-aa-popup .swal2-icon .swal2-icon-content{' +
					'color:var(--aa-c2, #E84848)!important;' +
				'}';
			document.head.appendChild(style);
		}

		Swal.fire({
			title: 'Open Voice Engine Configuration?',
			html:
				'<span class="vc-aa-eyebrow">Heads up</span>' +
				'<div class="vc-aa-html">' +
					'You are about to leave the editor.<br>' +
					'Save your work first so nothing gets lost.' +
				'</div>' +
				'<div class="vc-aa-actions">' +
					'<a href="' + safeHref + '" id="vc-confirm-link" class="vc-aa-btn vc-aa-btn-primary">' +
						'Continue<span class="vc-aa-arrow">→</span>' +
					'</a>' +
				'</div>',
			showConfirmButton: false,
			showCancelButton: true,
			cancelButtonText: 'Stay Here',
			allowOutsideClick: false,
			buttonsStyling: false,
			customClass: {
				popup: 'vc-aa-popup',
				title: 'vc-aa-title',
				htmlContainer: 'vc-aa-html-container',
				actions: 'vc-aa-swal-actions',
				cancelButton: 'vc-aa-btn vc-aa-btn-ghost'
			},
			didOpen: function(){
				var link = document.getElementById('vc-confirm-link');
				if (link) {
					link.addEventListener('click', function(ev){
						ev.stopPropagation();
						window.__skipBeforeUnload = true;
						if (typeof window.resetAutosaveBaseline === 'function') {
							try { window.resetAutosaveBaseline(); } catch(_){}
						}
					});
				}
			}
		});
	});

	// start of editor functionallity scrips
		
		// $("#templateEditor a").each(function(){

		// 	if($(this).attr('data-link') != '')
		// 	{
		// 		$(this).removeAttr('href');

		// 	}
		// });


		// ── Autosave toggle: persist preference to settings table ──
		// window.autosaveEnabled is the single source of truth read by the
		// autosave engine (added in a follow-up phase).
		window.autosaveEnabled = <?= !empty($autosave_enabled) ? 'true' : 'false' ?>;

		$('#autosaveToggle').on('change', function(){
			var enabled = $(this).is(':checked') ? 1 : 0;
			var $status = $('#autosaveStatus');
			$status.text('Saving preference…');

			$.ajax({
				type: 'POST',
				url: '<?= site_url('save_autosave_toggle') ?>',
				data: { enabled: enabled },
				success: function(resp){
					try {
						var r = (typeof resp === 'string') ? JSON.parse(resp) : resp;
						if (r && r.ok) {
							window.autosaveEnabled = (enabled === 1);
							// Autosave only runs once the page has been published at least
							// once. If it's on but not yet published, say so, so the user
							// isn't misled into thinking edits are being saved.
							var pagePublished = (<?= (isset($status) && (int)$status === 1) ? 'true' : 'false' ?>);
							if (enabled && !pagePublished) {
								$status.text('Autosave on — starts after you publish');
							} else {
								$status.text(enabled ? 'Autosave on' : 'Autosave off');
							}
							setTimeout(function(){ $status.text(''); }, 3500);
						} else {
							$('#autosaveToggle').prop('checked', !enabled);
							$status.text('Could not save — try again');
						}
					} catch(e) {
						$('#autosaveToggle').prop('checked', !enabled);
						$status.text('Could not save — try again');
					}
				},
				error: function(){
					// Revert the checkbox so UI matches actual server state
					$('#autosaveToggle').prop('checked', !enabled);
					$status.text('Could not save — try again');
				}
			});
		});

		// ─────────────────────────────────────────────────────────────────
		// Autosave engine
		// Layer 1: MutationObserver on editor roots
		// Layer 2: Explicit hooks for non-DOM state (autoresponder, analytics)
		// Layer 3: Flush triggers (tab switch, beforeunload, manual save)
		// Layer 4: Debounce 3s + snapshot dedupe + silent save
		// ─────────────────────────────────────────────────────────────────
		(function(){
			var DEBOUNCE_MS = 3000;
			var autosaveTimer = null;
			var isAutosaving = false;
			var inflightWatchdog = null;   // safety timer so isAutosaving can't wedge
			var lastSnapshot = null;
			var $status = $('#autosaveStatus');

			// Published status from PHP — autosave only runs for already-published pages.
			// This is set to 1 once the user has hit "Save and Publish" at least once;
			// after a first manual save in this session we flip it to true in JS too.
			var isPublished = (<?= (isset($status) && (int)$status === 1) ? 'true' : 'false' ?>);

			function buildSnapshot(){
				return [
					$(ic_frameDocs()).find('#funnelrh').html() || '',
					$(ic_frameDocs()).find('#funnelrb').html() || '',
					$(ic_frameDocs()).find('#funnelrh1').html() || '',
					$(ic_frameDocs()).find('#funnelrb1').html() || '',
					$(ic_funnelDoc()).find('.analyticsCode').text() || '',
					$(ic_funnelDoc()).find('.retarget_analyticsCode').text() || '',
					$(ic_funnelDoc()).find('.chatbotEmbedCode').text() || '',
					(typeof autoresponder_data === 'object') ? JSON.stringify(autoresponder_data) : (autoresponder_data || '')
				].join('');
			}

			function timeNow(){
				var d = new Date();
				var h = d.getHours();
				var m = d.getMinutes();
				var ampm = h >= 12 ? 'pm' : 'am';
				h = h % 12; if (h === 0) h = 12;
				return h + ':' + (m < 10 ? '0'+m : m) + ' ' + ampm;
			}

			function setStatus(text, fade, kind){
				// kind: 'success' | 'error' | undefined (neutral)
				$status.removeClass('is-success is-error');
				if (kind === 'success') {
					$status.addClass('is-success').html('<span class="autosave-tick">&#10003;</span>' + $('<div>').text(text).html());
				} else if (kind === 'error') {
					$status.addClass('is-error').text(text);
				} else {
					$status.text(text);
				}
				if (fade) {
					setTimeout(function(){
						// Only clear if the message is still the same (avoid wiping a newer status)
						if ($status.text().replace(/^\s*✓\s*/, '') === text || $status.text() === text) {
							$status.removeClass('is-success is-error').empty();
						}
					}, 3000);
				}
			}

			function buildPayload(){
				var autoresponder_data1 = (typeof autoresponder_data === 'object')
					? JSON.stringify(autoresponder_data)
					: autoresponder_data;

				return {
					body1: $(ic_frameDocs()).find('#funnelrb').html(),
					header1: $(ic_frameDocs()).find('#funnelrh').html(),
					thankyou_header: $(ic_frameDocs()).find('#funnelrh1').html(),
					thankyou_body: $(ic_frameDocs()).find('#funnelrb1').html(),
					thankyou_template_id: '<?= $thankyou_template_id ?>',
					analyticsCode: $(ic_funnelDoc()).find('.analyticsCode').text(),
					retarget_analyticsCode: $(ic_funnelDoc()).find('.retarget_analyticsCode').text(),
					chatbotEmbedCode: $(ic_funnelDoc()).find('.chatbotEmbedCode').text(),
					funnel_id: $('#funnel_id').val(),
					template_id: $('#template_id').val(),
					autoresponder_data: autoresponder_data1,
					slug: $('.slug-input').val() || ''
				};
			}

			function silentSave(opts){
				opts = opts || {};
				var trigger = opts.trigger || 'unknown';
				// Customization changes (typography / brand colours / image
				// size / section visibility) live entirely in editor chrome —
				// the user expects them to persist whether or not the page has
				// been published. We let those bypass the publish gate.
				var isCustomization = trigger.indexOf('aa-customization') !== -1;
				// Explicit user-initiated saves (e.g. the typography panel's
				// "Save" button) skip every guard — gate, diff, and in-flight.
				var force = !!opts.force;

				if (isAutosaving && !force) {
					// A save is in flight. DON'T drop this change — re-schedule it so it
					// saves once the current one finishes. Without this, any edit made
					// while a save is mid-flight is silently lost ("works once, then
					// never" once a save overlaps an edit).
					console.log('[autosave] in flight — re-scheduling (trigger: ' + trigger + ')');
					clearTimeout(autosaveTimer);
					autosaveTimer = setTimeout(function(){
						silentSave({ trigger: 'retry-after-inflight (' + trigger + ')' });
					}, 1200);
					return;
				}

				// Hard gate: only autosave for pages that have been published at
				// least once. A brand-new draft must go through "Save and Publish"
				// manually so the user has explicit control over the first commit.
				// Exception above for aa-customization, which is purely chrome
				// state and shouldn't be lost on refresh.
				// Never autosave while the AI is still writing the page — even a
				// forced/customization flush would capture a half-filled page.
				if (window.icAutoPopulating) {
					console.log('[autosave] skipped — page still auto-populating (trigger: ' + trigger + ')');
					return;
				}
				if (!isPublished && !isCustomization && !force) {
					console.log('[autosave] skipped — page not yet published (trigger: ' + trigger + ')');
					return;
				}

				var snap = buildSnapshot();
				if (snap === lastSnapshot && !force) {
					console.log('[autosave] skipped — no changes since last save (trigger: ' + trigger + ')');
					return;
				}

				console.log('[autosave] saving — trigger:', trigger);
				isAutosaving = true;
				setStatus('Saving…', false);

				// Watchdog: if the request never completes (tab backgrounded, connection
				// dropped, navigation), clear the in-flight flag anyway so autosave can
				// never get permanently wedged ("works once, then never").
				clearTimeout(inflightWatchdog);
				inflightWatchdog = setTimeout(function(){ isAutosaving = false; }, 20000);

				$.ajax({
					type: 'POST',
					url: '<?= site_url('submitAction') ?>',
					data: buildPayload(),
					timeout: 15000,
					// IMPORTANT: silent — no .loaderautomater, no modal, no alertMsg
					success: function(data){
						try {
							var r = JSON.parse(data);
							if (r && r.status == '200') {
								lastSnapshot = snap;
								if (r.publish_link) {
									$('.publish_link').val(r.publish_link);
									$('.publish_link_url').attr('href', r.publish_link);
								}
								if (r.slug) {
									$('.slug-input').val(r.slug).attr('data-original', r.slug);
								}
								setStatus('Saved · ' + timeNow(), true, 'success');
							} else {
								setStatus('Save failed', true, 'error');
							}
						} catch(e) {
							setStatus('Save failed', true, 'error');
						}
					},
					error: function(){
						setStatus('Save failed', true, 'error');
					},
					complete: function(){
						isAutosaving = false;
						clearTimeout(inflightWatchdog);
						// If edits arrived during this save, a retry timer is already
						// pending (see the in-flight branch) and will now go through.
					}
				});
			}

			function scheduleAutosave(trigger){
				trigger = trigger || 'unknown';
				var isCustomization = trigger.indexOf('aa-customization') !== -1;
				if (!window.autosaveEnabled) return;
				if (!isPublished && !isCustomization) return;
				clearTimeout(autosaveTimer);
				autosaveTimer = setTimeout(function(){
					silentSave({ trigger: 'debounce-fire (queued by: ' + trigger + ')' });
				}, DEBOUNCE_MS);
			}

			function flushAutosave(trigger){
				trigger = trigger || 'unknown';
				var isCustomization = trigger.indexOf('aa-customization') !== -1;
				if (!window.autosaveEnabled) return;
				if (!isPublished && !isCustomization) return;
				// Save NOW, whether or not a debounce timer is pending. silentSave's own
				// snapshot-dedup skips it when nothing actually changed, so an explicit
				// flush after a real edit (e.g. Apply Changes) always persists without
				// waiting on the throttleable timer.
				clearTimeout(autosaveTimer);
				autosaveTimer = null;
				silentSave({ trigger: 'flush (' + trigger + ')' });
			}

			// Expose for explicit hooks elsewhere in the file
			window.scheduleAutosave = scheduleAutosave;
			window.flushAutosave = flushAutosave;
			// Explicit user-initiated save (used by the customization panels'
			// "Save" buttons). Bypasses the publish gate, diff check, and any
			// in-flight save so the click always submits.
			window.forceSaveNow = function(triggerName){
				silentSave({ trigger: triggerName || 'aa-customization-force', force: true });
			};
			// Refreshes the baseline so a just-completed manual save doesn't re-fire as autosave
			window.resetAutosaveBaseline = function(){
				lastSnapshot = buildSnapshot();
				clearTimeout(autosaveTimer);
				autosaveTimer = null;
			};
			// Called after the first successful "Save and Publish" so autosave kicks in mid-session
			window.markPagePublished = function(){ isPublished = true; };

			// ── Layer 1: MutationObserver on editor roots ──
			// The roots (#funnelrh/#funnelrb/#funnelrh1/#funnelrb1) live INSIDE the
			// <iframe srcdoc> previews, not in this document — every other reader
			// here goes through ic_frameDocs() for exactly that reason. This used
			// document.getElementById(), which returns null for all four, so
			// roots.length was 0 and the observer returned without ever attaching:
			// autosave had no trigger and silently never ran. Look inside the frames,
			// and keep retrying until they exist (attachObserver runs at DOM-ready,
			// before the frames have parsed their srcdoc).
			var observerAttached = false;
			function attachObserver(){
				if (observerAttached) return true;
				if (typeof MutationObserver === 'undefined') return true;   // nothing to retry

				var rootIds = ['funnelrh', 'funnelrb', 'funnelrh1', 'funnelrb1'];
				var roots = [];
				ic_frameDocs().forEach(function(doc){
					rootIds.forEach(function(id){
						var el = doc.getElementById ? doc.getElementById(id) : null;
						if (el) roots.push(el);
					});
				});
				if (!roots.length) return false;   // frames not ready yet — caller retries

				var observer = new MutationObserver(function(mutations){
					// Filter out cosmetic mutations that aren't real edits
					var triggerDetail = null;
					var meaningful = mutations.some(function(m){
						var t = m.target;
						if (!t) return false;
						// Skip selection/hover overlay nodes the editor itself may inject
						if (t.classList && (t.classList.contains('selection-handle') || t.classList.contains('hover-outline'))) return false;
						if (m.attributeName === 'data-selected' || m.attributeName === 'data-hover') return false;
						// `title`/`data-link` are tooltip/hover chrome the editor injects on
						// hover — NOT user content. They fired autosave repeatedly only to
						// be deduped as "no changes", so ignore them as edit signals.
						if (m.attributeName === 'title' || m.attributeName === 'data-link') return false;
						// Capture a useful description of the first meaningful mutation
						if (!triggerDetail) {
							var rootId = (function(node){
								while (node && node.id !== 'funnelrh' && node.id !== 'funnelrb' && node.id !== 'funnelrh1' && node.id !== 'funnelrb1') {
									node = node.parentNode;
								}
								return node ? node.id : 'editor';
							})(t);
							triggerDetail = m.type + ' on ' + rootId
								+ (m.attributeName ? (' [attr: ' + m.attributeName + ']') : '')
								+ ((t.tagName ? (' <' + t.tagName.toLowerCase() + '>') : ''));
						}
						return true;
					});
					if (meaningful) scheduleAutosave('MutationObserver: ' + triggerDetail);
				});

				roots.forEach(function(root){
					observer.observe(root, {
						childList: true,
						subtree: true,
						attributes: true,
						characterData: true,
						attributeFilter: ['style', 'class', 'href', 'src', 'alt', 'title', 'data-link']
					});
				});
				observerAttached = true;
				console.log('[autosave] observer attached to ' + roots.length + ' root(s)');
				return true;
			}
			// The srcdoc frames parse asynchronously, so the roots usually aren't
			// there on the first try. Poll briefly rather than racing a load event
			// (which may already have fired by the time we get here).
			if (!attachObserver()) {
				var attachTries = 0;
				var attachPoll = setInterval(function(){
					if (attachObserver() || ++attachTries > 40) clearInterval(attachPoll);   // ~10s ceiling
				}, 250);
			}

			// ── Layer 2: Explicit hooks for things outside the editor DOM ──
			// Analytics / retarget / chatbot textarea inputs
			$(document).on('input change',
				'#templateEditor1 .analyticsCode, #templateEditor1 .retarget_analyticsCode, #templateEditor1 .chatbotEmbedCode',
				function(){
					var name = ($(this).attr('class') || '').split(' ')[0] || 'analytics-textarea';
					scheduleAutosave('input on .' + name);
				}
			);

			// ── Layer 3: Flush triggers ──
			// Tab switch between Funnel Page <-> Thank You Page
			$(document).on('click', '.page_controls', function(){
				var tab = $(this).attr('data-page') || $(this).attr('id') || 'page-tab';
				flushAutosave('tab switch -> ' + tab);
			});

			// Manual Save Changes click — flush any pending autosave first so we
			// don't race with the explicit save
			$('.save_action, .updateThis').on('mousedown', function(){
				if (autosaveTimer) {
					clearTimeout(autosaveTimer);
					autosaveTimer = null;
				}
			});

			// Browser tab close / navigation
			window.addEventListener('beforeunload', function(e){
				if (window.__skipBeforeUnload) return;
				if (!window.autosaveEnabled || !isPublished) return;
				if (autosaveTimer) {
					clearTimeout(autosaveTimer);
					autosaveTimer = null;
					console.log('[autosave] saving — trigger: beforeunload (sync)');
					// Synchronous-ish: fire the request; browsers don't guarantee delivery,
					// but we try. sendBeacon would be ideal but our endpoint expects the
					// jQuery-encoded payload shape, so we keep $.ajax with async:false.
					try {
						$.ajax({
							type: 'POST',
							url: '<?= site_url('submitAction') ?>',
							data: buildPayload(),
							async: false
						});
					} catch(_) {}
				}
			});

			// Initialise the snapshot baseline so the first autosave only fires after a real edit
			lastSnapshot = buildSnapshot();

			// Universal "are there pending edits?" probe — true when the current
			// editor state differs from the last saved/baseline snapshot.
			// Works regardless of autosave on/off.
			window.hasUnsavedChanges = function(){
				try { return buildSnapshot() !== lastSnapshot; }
				catch(_) { return false; }
			};
			// Trigger a manual save (Save Changes button) and resolve when done.
			window.requestManualSave = function(){
				var $btn = $('.save_action, .updateThis').filter(':visible').first();
				if (!$btn.length) $btn = $('.save_action, .updateThis').first();
				if ($btn.length) $btn.trigger('click');
			};
		})();

		// Publish-modal slug editor: debounced live duplicate check + URL sync
		(function(){
			var $slug = $('.slug-input');
			if (!$slug.length) return;

			var prefix    = '<?= base_url("webpages/") ?>';
			var $status   = $('.slug-status');
			var $hidden   = $('.publish_link');
			var $viewLink = $('.publish_link_url');
			var $preview  = $('.slug-preview-url');
			var debounceTimer = null;

			function syncFullUrl(value){
				var url = prefix + value;
				$hidden.val(url);
				$viewLink.attr('href', url);
				$preview.text(url);

				// Keep the Thank You Page reset-to-default URL in sync with
				// the current slug, so "Reset to Default" always returns the
				// freshest /webpages/{slug}/thankyou link.
				if (value) {
					var newDefault = prefix + value + '/thankyou';
					var $reset = $('.resetthankyou');
					if ($reset.length) {
						var oldDefault = $reset.attr('data-url') || '';
						$reset.attr('data-url', newDefault);
						// If the redirect input is still pointing at the old
						// default, update it too — otherwise leave the user's
						// custom URL alone.
						var $redirectInput = $('#redirectUrl');
						if ($redirectInput.length && $redirectInput.val() === oldDefault) {
							$redirectInput.val(newDefault);
							if (typeof putredirectUrl === 'function') { putredirectUrl(); }
						}
					}
				}
			}

			$slug.on('input', function(){
				var val = $(this).val();
				var original = $(this).attr('data-original') || '';
				$status.removeClass('ok bad').text('');

				// Optimistically rewrite the URL the copy button + View link target.
				syncFullUrl(val);

				if (val === original) return;

				clearTimeout(debounceTimer);
				debounceTimer = setTimeout(function(){
					$.ajax({
						type: 'POST',
						url: '<?= site_url("sites/checkSlug") ?>',
						data: { slug: val, funnel_id: $slug.data('funnel-id') },
						dataType: 'json',
						success: function(r){
							if (!r) return;
							if (r.clean && r.clean !== val) {
								$slug.val(r.clean);
								syncFullUrl(r.clean);
							}
							if (r.status === 'available') {
								$status.removeClass('bad').addClass('ok').text('Available');
							} else if (r.status === 'taken') {
								$status.removeClass('ok').addClass('bad').text('Already taken');
							} else {
								$status.removeClass('ok').addClass('bad').text('Enter a valid slug');
							}
						}
					});
				}, 300);
			});

			// Block save while the slug is in a bad state.
			$('.save_action, .updateThis').on('click', function(e){
				if ($status.hasClass('bad')) {
					e.preventDefault();
					e.stopImmediatePropagation();
					if (typeof alertMsg === 'function') {
						alertMsg('Choose an available URL slug before saving.');
					}
				}
			});

			// Done button persists slug-only changes without re-saving the page.
			$('#popup2 .publishDone').on('click', function(e){
				var current  = $slug.val() || '';
				var original = $slug.attr('data-original') || '';
				if (current === original) return;            // nothing to save
				if ($status.hasClass('bad')) {
					e.preventDefault();
					e.stopImmediatePropagation();
					if (typeof alertMsg === 'function') {
						alertMsg('Choose an available URL slug before closing.');
					}
					return;
				}

				$.ajax({
					type: 'POST',
					url: '<?= site_url("sites/update_slug") ?>',
					data: { slug: current, funnel_id: $slug.data('funnel-id') },
					dataType: 'json',
					success: function(r){
						if (!r) return;
						if (r.status === '200') {
							$slug.val(r.slug).attr('data-original', r.slug);
							syncFullUrl(r.slug);
							if (typeof alertMsg === 'function') {
								alertMsg('Page URL updated');
							}
						} else if (r.message && typeof alertMsg === 'function') {
							alertMsg(r.message);
						}
					}
				});
			});
		})();

		// Save Template Data
		$(".save_action,.updateThis").click(function(){

			// Refuse to save while the AI is still auto-populating the page — a
			// mid-fill save would persist an empty or partial page. Covers real
			// clicks (button is also disabled) and any programmatic trigger.
			if (window.icAutoPopulating) {
				try { alertMsg('Please wait, your page is still being written.'); } catch (_){}
				return false;
			}

			var thisbt = $(this);
			if(thisbt.hasClass('updateThis')){
				thisbt.text("Updating..");
			}else{
				thisbt.find('span').text("Saving..");
			}


			var header = $(ic_frameDocs()).find("#funnelrh").html();
			var body = $(ic_frameDocs()).find("#funnelrb").html();

			var thankyou_header = $(ic_frameDocs()).find("#funnelrh1").html();
			var thankyou_body = $(ic_frameDocs()).find("#funnelrb1").html();
			var thankyou_template_id = '<?= $thankyou_template_id ?>';
			
			
			var funnel_id=$("#funnel_id").val();
			var template_id=$("#template_id").val();

			var analyticsCode = $(ic_funnelDoc()).find(".analyticsCode").text();
			var retarget_analyticsCode = $(ic_funnelDoc()).find(".retarget_analyticsCode").text();
			var chatbotEmbedCode = $(ic_funnelDoc()).find(".chatbotEmbedCode").text();
			


			// make it json string
			autoresponder_data1 = JSON.stringify(autoresponder_data);

			var posted_slug = $('.slug-input').val() || '';

			$.ajax({
				type: "POST",
				url: "<?php echo site_url('submitAction');?>",
				data: {body1:body,header1:header,thankyou_header, thankyou_body, thankyou_template_id, analyticsCode, retarget_analyticsCode, chatbotEmbedCode, funnel_id:funnel_id,template_id:template_id, autoresponder_data: autoresponder_data1, slug: posted_slug},
				// cache: false,
				// No full-screen "Please Wait..." overlay on publish — the button's own
				// "Saving.."/"Updating.." text (set above) is enough feedback. The shared
				// .loaderautomater is still used by other flows, so only this handler skips it.
				success: function(data){

					var data1 = JSON.parse(data);

					if( data1.status == '200' ){

						$('.publish_link').val(data1.publish_link);
						$('.publish_link_url').attr('href',data1.publish_link);
						if (data1.slug) {
							$('.slug-input').val(data1.slug).attr('data-original', data1.slug);
							$('.slug-status').removeClass('ok bad').text('');
						}
						if (data1.publish_link) {
							$('.slug-preview-url').text(data1.publish_link);
						}

						$('.updateThis').text("Save Changes");
						$("#popup2").modal('show');
						// alertMsg('Page saved successfully');
						if(thisbt.hasClass('updateThis')){
							alertMsg('Page Updated successfully');
						}else{
							alertMsg('Page saved successfully');
							$('.save_btn_section').addClass('d-none');
							$('.update_btn_section').removeClass('d-none');

							// First publish complete — flip the autosave gate on so
							// further edits this session can autosave silently.
							if (typeof window.markPagePublished === 'function') {
								window.markPagePublished();
							}
						}

						// Refresh the autosave baseline so we don't immediately re-save the same content
						if (typeof window.resetAutosaveBaseline === 'function') {
							window.resetAutosaveBaseline();
						}
					}
				}
			});
		});
		
		

		// The editor skin PORTALS this menu to <body> when it opens (it has to —
		// .aa-editor-page{overflow:hidden} would otherwise clip it behind the
		// preview canvas; see _ic_editor_skin.php). Once portalled it is no longer
		// a sibling of the caret, so .siblings() found nothing on the SECOND click
		// and .hide() was a no-op — the menu opened and then refused to close.
		// Resolve it wherever it currently lives instead.
		function icSaveDropdown($btn){
			var $d = $btn.siblings('.dropdown-section');
			if (!$d.length) $d = $btn.closest('.btn-group').find('.dropdown-section');
			if (!$d.length) $d = $('body > .dropdown-section');   // portalled
			return $d;
		}
		$('.btn_32').on('click', function(e){
			e.stopPropagation();
			var $btn = $(this);
			var $drop = icSaveDropdown($btn);
			if($btn.hasClass('active1')){
				$btn.removeClass('active1');
				$drop.hide();
				$btn.find('img').css('transform','rotate(0deg)');
			}else{
				$btn.addClass('active1');
				$drop.show();
				$btn.find('img').css('transform','rotate(180deg)');
			}
		});

		// Keep dropdown open while cursor is inside it; close only on outside click.
		$(document).on('click', '.dropdown-section', function(e){
			e.stopPropagation();
		});
		$(document).on('click', function(){
			$('.dropdown-section').hide();
			$('.btn_32').removeClass('active1');
			$('.btn_32 img').css('transform','rotate(0deg)');
		});

		// card header
		$('.card-header').on('click', async function(){

			var thisbt = $(this);
			if($(this).hasClass('active')){
				$(this).removeClass('active');
				$(this).parents('.card').removeClass('active');
				$(this).find('img').css('transform','rotate(0deg)');

				//collapse the accordion
				// $(this).next().removeClass('show');
				// $(this).next().addClass('hide');
				return true;

			}else{

				await close_all_accordions2();

				thisbt.addClass('active');
				thisbt.parents('.card').addClass('active');
				thisbt.find('img').css('transform','rotate(180deg)');

				//collapse the accordion
				// $(this).next().removeClass('hide');
				// $(this).next().addClass('show');
			}
		});

		function close_all_accordions2() {
			return new Promise((resolve) => {
				$(".card-header").each(function() {
					if ($(this).hasClass("active")) {
						$(this).click();
					}
				});
				resolve();
			});
		}
		
		//copy link
		$('#copylink').on('click', function() {
			document.addEventListener('copy', handler_link, true);
			document.execCommand('copy');
			// $(this).text('Copied!');
			// setTimeout('$(\'#copylink\').text(\'Copy Link\')', 1000);
			alertMsg('Link Copied');
		});

		// (Iframe-embed copy handler removed — Iframe embed moved to Share & Publish.)

		//Generateads
		$("body").delegate(".generateads","click",function(){
			var brandname=$(this).attr("data-brand");
			if(brandname == ''){
				var action="<?php echo base_url('generateads'); ?>";
			}else{
				var action="<?php echo base_url('displayads'); ?>";
			}
			
			var camp_id=$("#camp_id").val();
		
			var form = $("<form/>", 
				{ action:action,'id':'generate_ads_form','method':'post'}
			);
			form.append( 
				$("<input>", 
					{ type:'hidden', 
					name:'camp_id', 
					value:camp_id }
				)
			);
			//console.log(form);
			$('body').append(form);
			$("#generate_ads_form").submit();
		});
		
		function image_to_dimention(id, img_url, type){
			
			var img = new Image();
			img.src = img_url;
			img.onload = function() {
				var width = img.width;
				var height = img.height;
				$(id).text(height+' x '+width+', A.R. '+(width/height).toFixed(2));
				if(type == 1){
					$('.image-upload-message').text('Recommended Image Dimension: '+height+' x '+width+', A.R. '+(width/height).toFixed(2));
				}
			}
		}
		

		
		//add selectd image
		$("body").delegate(".addselectedimage","click",function(){
			var imgsrc=$(this).attr("data-src");                 // webformatURL (re-host source)
			var previewSrc=$(this).attr("data-preview") || imgsrc; // cdn.pixabay.com (hotlinkable)
			var width=$(this).attr("data-width");
			var height=$(this).attr("data-height");
			var alttext=$(this).attr("data-tag");
			var image_name=$(this).attr("data-image_name");
			// Reveal the selected-image preview (hidden until now so the tab doesn't
			// show an empty preview block before a search).
			$("#selectedDiv").removeClass('is-empty');
			$("#PremiumImages .pix-sel-sep").removeClass('is-empty');
			// Preview with the hotlinkable previewURL; keep the real webformatURL in
			// data-pixurl for the server-side re-host on "Use This Image".
			$("#PremiumImages .slct_img_sec").find(".currentimage")
				.attr("src",previewSrc)
				.attr("data-pixurl",imgsrc);
			$(".cu_imageSizediv").show();
			$('.current_image_size').text(width+'x'+height+', A.R. '+(width/height).toFixed(2));
			$("#PremiumImages .palttext").val(alttext);
			$(".pimagename").text(image_name);
			$("#PremiumImages .ptitle").val(image_name);
		});

		
		
		//search images
		$(".searchpixabayimage").click(function(){
			var keyword=$("#pixkeyword").val();
			if(keyword==''){
				alertMsg("Please Enter Your Keyword","danger");
				return;
			}
			var $row = $("#PremiumImages .myimg_gallery .row");
			// Hide the selected-image preview again on a new search.
			$("#selectedDiv").addClass('is-empty');
			$("#PremiumImages .pix-sel-sep").addClass('is-empty');
			$row.html('<div class="col-sm-12 text-center py-4"><div class="lds-ellipsis"><div></div><div></div><div></div></div><p style="margin-top:6px;color:#8E7E74">Searching Pixabay…</p></div>');
			$.ajax({
				url: "<?php echo site_url('sites/get_pixabay_images'); ?>",
				data:{keyword:keyword},
				dataType:'json',
				success: function(data)
				{
					// Empty or throttled result → clear message instead of a blank,
					// stuck-looking grid.
					if (!data || !data.html || data.html.indexOf('<img') === -1) {
						$row.html('<div class="col-sm-12 text-center py-4" style="color:#8E7E74">No images found. Try a different keyword, or wait a moment and search again.</div>');
						return;
					}
					$row.html(data.html);
				},
				error: function()
				{
					$row.html('<div class="col-sm-12 text-center py-4" style="color:#8E7E74">Search is temporarily unavailable. Please try again in a moment.</div>');
				}
			});

		});

		$("body").delegate(".loadmore","click",function(){

			var page_no = $(this).attr('data-page');
			var keyword=$("#pixkeyword").val();
			$(".loadmoreDiv").html('<div class="text-center"><p>Please Wait ..<div class="lds-ellipsis"><div></div><div></div><div></div></p></div>');
			$.ajax({
				url: "<?php echo site_url('sites/get_pixabay_images'); ?>",
				data:{keyword,page_no},
				dataType:'json',
				success: function(data)
				{
					$(".loadmoreDiv").remove();
					$("#PremiumImages .myimg_gallery .row").append(data.html);
				},
				error: function() 
				{
					
				}           
			});

		});
		
		$("#pixkeyword").keypress(function (e) {
		var key = e.which;
		if(key == 13)  // the enter key code
		{
			$('.searchpixabayimage').click();
			return false;  
		}
		});


		$("#pexelKeyword").keypress(function (e) {
		var key = e.which;
		if(key == 13)  // the enter key code
		{
			$('.searchpexelSearch').click();
			return false;  
		}
		});

		//autoresponder functions
		$( "#autoresponderType" ).change(function(){
			if ($(this).val() == "api"){
				$('#apiForm').show();
				$('#copypasteForm').hide();
			}
			if ($(this).val() == "copypaste"){
				$('#apiForm').hide();
				$('#copypasteForm').show();
			}
		});
		
		$( "#mailerType" ).change(function(){
							
			if ($(this).val() != ""){
				hideMailerParameter();
				/* 1 - GetResponse,2 -ActiveCampaign , 3 - MailChimp 4 - Aweber,5 -icontact */
				var mailer=$(this).val();
				$.ajax({
					type:'POST',
					url: '<?php echo site_url("setting/getautoresponder")?>',
					data:{rtype:mailer},
					dataType:'json',
					success:function(data){
						//alert(data);
						/* 1 - GetResponse, 2 -Active campaign , 3 - MailChimp,4 - Aweber, 5-icontact*/
							$('#mailerParameter1').val('');//api key
							$('#mailerParameter2').val('');//app url
							$('#mailerParameter3').val('');//listId
						if(mailer == 1){ 
							//show parameters
							$('#mailerParameter1').val(data.param1);//api key
							getResponselist(data.param1);
				
						}else if(mailer == 2){
							$('#mailerParameter1').val(data.param1);//api key
							$('#mailerParameter2').val(data.param2);//app url
							getActCampaignlist(data.param2,data.param1);
							//$('#mailerParameter3').val(data.param3);//list Id
						}else if(mailer == 3){
							$('#mailerParameter1').val(data.param1);//api key
							getMailchimplist(data.param1);
						}else if(mailer == 4){
							$('#mailerParameter1').val(data.param1);////refreshToken
							$('#mailerParameter2').val(data.param2);//accessToken
							getAweberlist(data.param1,data.param2);
						}else if(mailer == 5){
							$('#mailerParameter1').val(data.param1);//app id
							$('#mailerParameter2').val(data.param2);//api user
							$('#mailerParameter3').val(data.param3);//api pass
							geticontactlist(data.param1,data.param2,data.param3);
						}else if(mailer == 6){
							$('#mailerParameter1').val(data.param1);//organizer_key
							$('#mailerParameter2').val(data.param2);//accessToken
							$('#mailerParameter3').val(data.param3);//refreshToken

							getwebinarlists(data.param1,data.param2,data.param3);
						}else if(mailer == 7){
							$('#mailerParameter1').val(data.param1);//api key
							getConvertKitlist(data.param1);
						}
					}
				});
				
			}else{
				hideMailerParameter();
			}
		});
		
		// Build autoresponder_data from the form, PRESERVING creds already saved in
		// autoresponder_data. The on-load re-hydration repopulates the list dropdown but
		// NOT the hidden #mailerParameter1/2 inputs, so after a page reload those read
		// blank; falling back to the existing binding keeps a list change (or Apply)
		// from wiping the saved API key / account URL / token for any mailer.
		function buildAutoresponderData(){
			var prev = (typeof autoresponder_data === 'object' && autoresponder_data) ? autoresponder_data : {};
			var keep = function(domVal, prevVal){ return (domVal !== '' && domVal != null) ? domVal : (prevVal || ''); };
			var d = {
				"mailer"     : $('#mailerType').val(),
				"parameter1" : keep($('#mailerParameter1').val(), prev.parameter1),
				"parameter2" : keep($('#mailerParameter2').val(), prev.parameter2),
				"parameter3" : $('#mailerParameter3List').val(),
				"parameter4" : keep($('#mailerParameter3').val(), prev.parameter4),
			};
			if ($('#mailerType').val() == 5){
				var lv = $('#mailerParameter5List').val();
				d.parameter5 = $("#mailerParameter5List option[value='" + lv + "']").text().trim();
			}
			return d;
		}

		$('button.saveAutoStyling').unbind('click').bind('click', function(){
			var iframe=$(ic_frameDocs());
			
			if($("#autoresponderType").val()=='api'){

				var auto_data = buildAutoresponderData();

				console.log(auto_data);

				autoresponder_data = auto_data;

				// Autosave: autoresponder_data lives outside the editor DOM, so the
				// MutationObserver won't see this change. Trigger explicitly.
				if (typeof window.scheduleAutosave === 'function') window.scheduleAutosave('autoresponder_data assigned');
			}
			else
			{
				var div = $("<div/>", 
						{ id:'copyhiddenAutoResponser'}
					);
				if(iframe.find("#copyhiddenAutoResponser").length > 0 && $("#copypaste_code").val() !=''){
						iframe.find("#copyhiddenAutoResponser").remove();
				}
				iframe.find('form').append(div);
				var copypastecode=$("#copypaste_code").val().replaceAll('<script','<funnelr');
				iframe.find('#paste_html_code').html(copypastecode.replaceAll('script>','funnelr>'));
				
			}
			alertMsg("Autoresponder Settings Applied Successfully !!","success");
		});

		// Capture a list/tag selection into autoresponder_data the moment it changes, so the
		// binding persists on the next save even if the owner never clicks "Apply Changes"
		// (picking a tag is enough). Silent — no alert. A save is still needed (autosave, or the
		// Save/Publish button posts the captured value).
		$(document).on('change', '#mailerParameter3List, #mailerParameter5List', function(){
			if ($("#autoresponderType").val() !== 'api' || !$('#mailerType').val()) return;
			autoresponder_data = buildAutoresponderData();
			if (typeof window.scheduleAutosave === 'function') window.scheduleAutosave('autoresponder list changed');
		});

		$('.thankyoucheck').on('change', function() {
			$('.thankyoucheck').not(this).prop('checked', false);  
			var check=$(this).attr("data-id");
			if(check==1){
				$("#successmsgdiv").show();
				$("#failedmsgdiv").hide();
			}else{
				$("#failedmsgdiv").show();
				$("#successmsgdiv").hide();
			}
		});

		// $('.textarea').tagEditor({
		// 	initialTags: [],
		// 	placeholder: 'Enter tags separated by comma...'
		// });
		

		
		$(".exportHtml").click(function(){
			var funnel_id=$("#funnel_id").val();
			var action="<?php echo site_url('sites/exportHTML');?>";
			var form = $("<form/>", 
				{ action:action,'id':'generate_ads_form','method':'post'}
				);
				form.append( 
					$("<input>", 
						{ type:'hidden', 
						name:'funnel_id', 
						value:funnel_id }
					)
				);
				$('body').append(form);
				$("#generate_ads_form").submit();
		});

		$(".uploadImg").click(function(){
			$(".hiddenfile").click();
		});


		$("body").on("change",".hiddenfile",function(e){
			var iframe=$(ic_frameDocs());


			$(".loaderautomater").show();
			var thisbt = $(this);


			// alert(thisbt.parent().parent().find('img').attr('src'));

			var myFormData = new FormData();
			myFormData.append('userFile', $(this).prop('files')[0]);


			$.ajax({
			url : "<?= base_url('imageUpload') ?>",
			data : myFormData,
			processData: false, // important
			contentType: false, // important
			type : "POST",
			dataType : 'JSON',
			success(data)
			{
				if(data.success)
				{


					if(img_status == 0)
					{
						var currentImageId = $('#imageIdTemp').val();
						$(ic_frameDocs()).find("#"+currentImageId).attr('src',data.URL);

					}
					else{
						iframe.find(".bg-selected").css("background", "url("+data.URL+") no-repeat center top");
					}
					// new upload is now in the user's gallery — refresh so it shows
					// under "My Images" next time the picker opens.
					myImagesLoaded = false;
					$("#popup3").modal('hide');

					

					// alert('Hello');
					// thisbt.parent().parent().find('img').attr('src',data.URL);  
					// thisbt.parent().prev().find("input").val(data.URL);        			

				}
				else{
					icToast(data.error, true);
				}

				$(".loaderautomater").hide();
				// console.log(data);
			}
			})
		});

		// $("body").delegate(".selectImage","click",function(){
		// 	var iframe=$(ic_frameDocs());

		// 	var img_url = $(this).find("img").attr('src')

		// 	if(img_status == 0)
		// 	{
		// 		var currentImageId = $('#imageIdTemp').val();
		// 		$("#"+currentImageId).attr('src',img_url);

		// 	}
		// 	else{
		// 		iframe.find(".bg-selected").css("background", "url("+img_url+") no-repeat center top");
		// 	}
		// 	$("#popup3").modal('hide');
		// });



		// Derive a clean, human-readable image name from any src. A plain URL
		// yields its filename; a data: URI (e.g. the Bridge templates' inline
		// SVG illustrations) has no filename, so splitting on '/' would dump the
		// whole encoded blob into the Title field — return a friendly label
		// instead. Also strips query strings and URL-decodes for readability.
		function icCleanImageName(src){
			src = (src || '').toString();
			if (src.indexOf('data:') === 0) {
				var m = src.match(/^data:image\/([a-z0-9.+-]+)/i);
				return m ? ('image.' + m[1].replace('svg+xml','svg')) : 'image';
			}
			var name = src.split('#')[0].split('?')[0].split('/').pop();
			try { name = decodeURIComponent(name); } catch (e) {}
			return name || 'image';
		}

		$("body").delegate(".selectImage","click",function(){

			var iframe=$(ic_frameDocs());
			var img_url = $(this).find("img").attr('src');



			var image_name = icCleanImageName(img_url);


			$(".slct_img_sec").find(".currentimage").attr("src",img_url);
			$(".cu_imageSizediv").show();

			image_to_dimention('.current_image_size', img_url, 0);


			$(".palttext").val('');
			$(".pimagename").text(image_name);
			$(".ptitle").val(image_name);

			var height = $('.myimg_gallery').height();
			$('.tab-content').animate({
				scrollTop: height
			}, 500);

		});

		// ── "My Images" gallery: fetch the user's previously-uploaded images and
		//    render them into the modal's #MyImages tab. Called when the picker
		//    opens (and after a successful upload) so the tab isn't empty.
		var myImagesLoaded = false;
		function loadMyImages(force){
			var $wrap = $('.my_uploaded_images');
			if(!$wrap.length) return;
			if(myImagesLoaded && !force) return;           // fetch once per session unless forced
			$wrap.html('<div class="col-sm-12 text-center py-3 text-muted">Loading your images…</div>');
			$.ajax({
				url: "<?php echo site_url('sites/fetchMyImages'); ?>",
				dataType: 'json',
				success: function(data){
					myImagesLoaded = true;
					var html = (data && data.html) ? data.html : '';
					if(!html || /haven't\s+Uploaded/i.test(html)){
						$wrap.html('<div class="col-sm-12 text-center py-4 text-muted">No uploaded images yet — use the <b>Upload</b> tab to add one.</div>');
						return;
					}
					// fetchimages() returns <ul><li><a><img></a></li></ul>; render as a
					// responsive thumbnail grid inside the row.
					$wrap.html('<div class="col-sm-12 my_img_grid">'+html+'</div>');
				},
				error: function(){
					$wrap.html('<div class="col-sm-12 text-center py-3 text-danger">Could not load your images. Please try again.</div>');
				}
			});
		}

		// Click a gallery thumbnail → load it into the current-image preview + title.
		$('body').on('click', '.my_uploaded_images li a', function(e){
			e.preventDefault();
			var img_url = $(this).find('img').attr('src');
			if(!img_url) return;
			var alttext = $(this).find('img').attr('alt') || '';
			var image_name = icCleanImageName(img_url);
			$("#MyImages .slct_img_sec").find(".currentimage").attr("src", img_url);
			$("#MyImages .cu_imageSizediv").show();
			image_to_dimention('#MyImages .current_image_size', img_url, 0);
			$("#MyImages .palttext, #MyImages .alttext").val(alttext);
			$("#MyImages .pimagename").text(image_name);
			$("#MyImages .ptitle").val(image_name);
			$('#MyImages li').removeClass('is_active').addClass('is_not_active');
			$(this).closest('li').removeClass('is_not_active').addClass('is_active');
		});

		// Refresh the gallery whenever the "My Images" tab is shown.
		$('body').on('click', '#galimages', function(){ loadMyImages(false); });








		$('body').on('click','.close_image_gallery',function(){
			$("#popup3").modal('hide');
		});

		

		$(".toggleEditor").click(function(){

			var status = $(this).attr('data-status');

			if(status == 1)
			{
				$('.toggleEditor').attr('data-status',0);
				$(".controlDiv").hide();
				$(".controlEditor").removeClass('col-xl-9');
				$(".controlEditor").addClass('col-xl-12');
				// $(this).text('Show Controls');
				$('.showfullcontrolls').show();

			}
			else{
				$('.toggleEditor').attr('data-status',1);
				$(".controlDiv").show();
				$(".controlEditor").removeClass('col-xl-12');
				$(".controlEditor").addClass('col-xl-9');
				// $(this).text('Show Full Screen');
				$('.showfullcontrolls').hide();
			}
		});

		// page controls
		$(".page_controls").click(function(){
			
			var page = $(this).attr('data-page');
			
			if($(this).hasClass('inactive')){

				var thisbt = $('.page_controls.active');
				thisbt.removeClass('active');
				thisbt.addClass('inactive');
				thisbt.addClass('btn_5');
				thisbt.removeClass('btn_6');
				var page = thisbt.attr('data-page');
				$("#"+page).hide();

				$(this).removeClass('inactive');
				$(this).addClass('active');
				$(this).addClass('btn_6');
				$(this).removeClass('btn_5');
				page = $(this).attr('data-page');
				$("#"+page).show();
			}

			
		});

	// end of editor functionallity scrips


	//start of twilio section script

		// (Removed: the AI Voice Calling / Call Purpose editor cards and their
		// voice-name auto-replace helpers — voice config is managed on the AI
		// Agent page, not in the page editor.)

	//end of twilio section script


	// Start of page creating scripts
	
		$(".goup").click(function(){
			$("#templateEditor").scrollTop(0);
		});

	// End of page creating scripts


	// Onload function call
	window.onload=function(){
		var iframe=$(ic_frameDocs());

		

        // The preview is an editing CANVAS: no anchor in it may ever navigate.
        // Belt-and-braces against a click that reaches an <a> without hitting a
        // handler that preventDefaults (an in-frame "#start" jump still repaints
        // the frame and leaves it looking blank). Capture phase so it wins even
        // when a child's handler stops the bubble.
        ic_frameDocs().forEach(function(doc){
            if (!doc || !doc.addEventListener || doc.__icNavGuard) return;
            doc.__icNavGuard = true;
            doc.addEventListener('click', function(ev){
                var a = ev.target && ev.target.closest ? ev.target.closest('a') : null;
                if (a) ev.preventDefault();
            }, true);
        });

        iframe.find("a").each(function(){
            if($(this).attr("id") =="quantity_left" || $(this).attr("id") =="orderbtn" || $(this).attr("id") =="orderbt1" || $(this).attr("data-block-editible")=="false" || $(this).hasClass("facebook") || $(this).hasClass("twitter") || $(this).hasClass("pintrest") || $(this).hasClass("instagram") || $(this).hasClass("youtube"))
            {
				$(this).removeClass("editLink");
            }else{
				$(this).addClass("editLink");
            }
			

            if($(this).attr("data-class") == 'fuseddprivacy' || $(this).attr("data-class") == 'fuseddterms'){
            	if(!$(this).hasClass('omni-white')){
            		$(this).addClass('omni-white');
            	}
            	
            }
        }); 
	
		// Back-compat: legacy niche funnels saved before the upload-caption
		// rewrite have the caption as <div class="niche-service-upload-text">.
		// Promote those to <p> so they pick up the same inline-edit + styling
		// path as freshly-created funnels.
		iframe.find('div.niche-service-upload-text').each(function(){
			var $d = $(this);
			var $p = $('<p class="niche-service-upload-text"></p>').html($d.html());
			$d.replaceWith($p);
		});

		// Make star ratings + benefit/pillar emojis editable on EVERY funnel — old
		// pages have baked HTML with plain `.stars` / `.ic` (no alf- class), so we
		// tag them here at load time rather than relying only on the template files.
		// The alf- class is what the editability selector below matches. Indexed so
		// each element gets a stable, unique slot class.
		iframe.find('.stars').each(function(i){
			var $s = $(this);
			if (!/alf-/.test($s.attr('class') || '')) { $s.addClass('alf-testi' + (i + 1) + '-stars'); }
		});
		iframe.find('.ic, .pi, .lci').each(function(i){
			var $e = $(this);
			// Only tag ones that hold a short glyph/emoji (not big content blocks).
			var txt = ($e.text() || '').trim();
			if (!/alf-/.test($e.attr('class') || '') && txt.length > 0 && txt.length <= 6) {
				$e.addClass('alf-benefit' + (i + 1) + '-icon');
			}
		});

		// Content lists (<ul>) get a DEDICATED add/edit/delete editor instead of the
		// free-text modal (which couldn't add/remove <li>s). Tag the eligible ones —
		// simple content lists only: not in nav/footer/marquee, and every <li> is
		// inline (no block child) so a one-line-per-item editor is lossless. Complex
		// lists (<li><div>title+subtext</div>) are left on the normal per-field path.
		function aaListEligible($ul){
			if (!$ul || !$ul.length) return false;
			if ($ul.closest('nav, footer, .ticker, .strip').length) return false;
			var $lis = $ul.children('li');
			if ($lis.length < 1) return false;
			var ok = true;
			$lis.each(function(){
				// A block-level child => complex layout; keep it on the per-field path.
				if ($(this).children('div,p,ul,ol,section,table,form,figure,article,h1,h2,h3,h4,h5,h6').length) { ok = false; return; }
				// More than ONE content slot (alf-*) in the item = a title + subtext style
				// item (e.g. ul.checklist: <strong class="alf-*-title"> + <span class="alf-*-desc">).
				// A single-text row can't represent two fields, so leave those on the
				// per-field editor (each slot stays individually click-editable).
				if (this.querySelectorAll('[class*="alf-"]').length > 1) { ok = false; return; }
			});
			return ok;
		}
		iframe.find('ul').each(function(){ if (aaListEligible($(this))) $(this).addClass('aa-ed-list'); });

		// Price ledgers (item + $value rows: .lrow/.trow/.panel-row/.drow/.receipt-r …)
		// get a DEDICATED two-field editor. Detect generically: a row holds a title/label
		// slot AND an alf-*value slot whose text carries a currency symbol, and has NO
		// link/button (that skips value+CTA rows like editorial-thankyou). Consecutive such
		// rows sharing a parent form one ledger; its total row (alf-*total) is found too.
		// Tag rows + total .aa-ed-price so the edit_content loop skips them and a click
		// opens #popupPrice. Groups are stored so the handler can rebuild in place.
		function aaPriceText(el){ return el ? (el.textContent || '').replace(/\s+/g, ' ').trim() : ''; }
		function aaPriceIsValue(el){ var c = el.getAttribute('class') || ''; return /alf-[\w-]*value/.test(c) && !/total/.test(c) && /[$£€]/.test(el.textContent || ''); }
		function aaPriceRowTitle(row){ var t = row.querySelector('[class*="alf-"][class*="title"], [class*="alf-"][class*="label"]'); return (t && !/total|value/.test(t.getAttribute('class') || '')) ? t : null; }
		function aaPriceRowValue(row){ var a = row.querySelectorAll('[class*="alf-"]'); for (var i = 0; i < a.length; i++){ if (aaPriceIsValue(a[i])) return a[i]; } return null; }
		function aaPriceIsRow(row){ if (!row || row.querySelector('a, button')) return false; return !!(aaPriceRowValue(row) && aaPriceRowTitle(row)); }
		function aaPriceFindTotal(rowsParent){
			var scopes = [rowsParent, rowsParent.parentElement];
			for (var s = 0; s < scopes.length; s++){
				var sc = scopes[s]; if (!sc) continue;
				var els = [].slice.call(sc.querySelectorAll('[class*="alf-"][class*="total"]'));
				if (!els.length) continue;
				var valueEl = null, labelEl = null;
				els.forEach(function(e){ var c = e.getAttribute('class') || ''; if (!/label|title/.test(c) && /[$£€\d]/.test(e.textContent || '')) valueEl = valueEl || e; });
				els.forEach(function(e){ if (/label|title/.test(e.getAttribute('class') || '')) labelEl = labelEl || e; });
				if (!valueEl) valueEl = els[0];
				if (valueEl) return { rowEl: valueEl.parentElement, labelEl: labelEl, valueEl: valueEl };
			}
			return null;
		}
		var aaPriceGroups = [];
		ic_frameDocs().forEach(function(doc){
			var seen = [], vals = doc.querySelectorAll('[class*="alf-"]');
			for (var i = 0; i < vals.length; i++){
				var v = vals[i]; if (!aaPriceIsValue(v)) continue;
				var row = v.parentElement; if (!aaPriceIsRow(row)) continue;
				var rp = row.parentElement; if (!rp || seen.indexOf(rp) >= 0) continue;
				var rows = [].slice.call(rp.children).filter(aaPriceIsRow);
				if (rows.length < 2) continue;
				seen.push(rp);
				var g = { rowsParent: rp, rows: rows, total: aaPriceFindTotal(rp) };
				var idx = aaPriceGroups.push(g) - 1;
				rows.forEach(function(r){ r.classList.add('aa-ed-price'); r.setAttribute('data-aa-price', idx); });
				if (g.total && g.total.rowEl){ g.total.rowEl.classList.add('aa-ed-price'); g.total.rowEl.setAttribute('data-aa-price', idx); }
			}
		});

		// Text-edit is class-driven: the original tag whitelist PLUS any element
		// carrying an `alf-*` slot class, regardless of tag — so blockquote,
		// small, em, b, i, label, li, td, figcaption, etc. that hold real copy
		// become click-editable. These already auto-populate via
		// _apply_content_to_template (its regex is tag-agnostic), so the only gap
		// was the editor's tag whitelist; widening it to `[class*="alf-"]` closes it.
		iframe.find('h1,h2,h3,h4,h5,h6,p,span,div[class*="alf-"],[class*="alf-"],div.niche-service-upload-text').each(function(){
            var $el = $(this);
            // Scrolling bars (ticker/strip marquees) are edited via their own
            // dedicated modal (#popupMarquee), NEVER the free-text one — inline
            // editing only touched one of the two loop copies and mangled the item
            // separators. Skip every node inside a marquee.
            if ($el.closest('.ticker, .strip').length) { $el.removeClass("edit_content"); return; }
            // Nodes inside an eligible content list are edited via #popupList, not the
            // free-text modal (which can't add/remove <li>s). Skip them.
            if ($el.closest('.aa-ed-list').length) { $el.removeClass("edit_content"); return; }
            // Price-ledger rows/total are edited via #popupPrice (name + price fields).
            if ($el.closest('.aa-ed-price').length) { $el.removeClass("edit_content"); return; }
            var id  = $el.attr("id") || "";
            var blocked = (id == "quantity_left" || id == "brandnamereplace" || id == "brandnamereplace1" ||
                id == "countdown" || $el.attr("data-block-editible") == "false" || id == "noneed");

            var tag = (this.tagName || "").toLowerCase();

            // ANCHORS + IMAGES are NEVER text-editable here — they have their own
            // dedicated handlers: <a> → .editLink (link control, line ~4017),
            // <img> → the image picker (line ~4966). Without this exclusion an
            // element like <a class="alf-cta"> or <img class="alf-photo"> would get
            // BOTH edit_content AND its own handler, so a single click fires the
            // text modal AND the link/image control at once. Put editable copy in a
            // child <span class="alf-*"> instead — that span still gets edit_content.
            if (tag === "a" || tag === "img") {
                $el.removeClass("edit_content");
                return;
            }

            // Leaf guard: for a wrapper <div> (original behaviour) OR any alf-*
            // element (new), only enable inline-text editing when it doesn't wrap
            // other block-level children — otherwise we'd turn a whole section
            // into one editable blob.
            var isAlf = /(^|\s)alf-/.test($el.attr("class") || "");
            if (!blocked && (tag === "div" || isAlf)) {
                var hasBlockChildren = $el.children("h1,h2,h3,h4,h5,h6,p,ul,ol,form,section,div").length > 0;
                if (hasBlockChildren) blocked = true;
            }

            if (blocked) {
                $el.removeClass("edit_content");
            } else {
                $el.addClass("edit_content");
            }
        });
		
		//alert(style);
		if(iframe.find("#paste_html_code1").length == 0){
			
			iframe.find('form').wrap("<div id='paste_html_code1'></div>");
			
		}


		/* Social Media Start */
		$(".facebook").click(function(e){
			e.stopPropagation();
			e.preventDefault();

			close_all_accordions();

			$("#socialmediacontrols").show();
			$('.socialmedia_switcher').attr('href', '#fbeditor')

			$("#fblinkcontrol").show();
			$("#twitlinkcontrol").hide();
			$("#pintlinkcontrol").hide();
			$("#instalinkcontrol").hide();
			$("#youtubelinkcontrol").hide();

			$('#fblink').val($(this).attr("href"));

			if( !$('.socialmedia_switcher').hasClass('active')){
				$('.socialmedia_switcher').click();
				console.log('clicked');
			};
			scroll_controlles();
		});

		$(".twitter").click(function(e){
			e.stopPropagation();
			e.preventDefault();

			close_all_accordions();

			$("#socialmediacontrols").show();
			$('.socialmedia_switcher').attr('href', '#twiteditor')


			$("#fblinkcontrol").hide();
			$("#twitlinkcontrol").show();
			$("#pintlinkcontrol").hide();
			$("#instalinkcontrol").hide();
			$("#youtubelinkcontrol").hide();

			$('#twitlink').val($(this).attr("href"));

			if( !$('.socialmedia_switcher').hasClass('active')){
				$('.socialmedia_switcher').click();
			};
			scroll_controlles();

		});

		$(".pintrest").click(function(e){
			e.stopPropagation();
			e.preventDefault();

			close_all_accordions();

			$("#socialmediacontrols").show();
			$('.socialmedia_switcher').attr('href', '#pinteditor')

			$("#fblinkcontrol").hide();
			$("#twitlinkcontrol").hide();
			$("#pintlinkcontrol").show();
			$("#instalinkcontrol").hide();
			$("#youtubelinkcontrol").hide();

			$('#pintlink').val($(this).attr("href"));
			
			if( !$('.socialmedia_switcher').hasClass('active')){
				$('.socialmedia_switcher').click();
			};
			scroll_controlles();
		});

		$(".instagram").click(function(e){
			e.stopPropagation();
			e.preventDefault();

			close_all_accordions();

			$("#socialmediacontrols").show();
			$('.socialmedia_switcher').attr('href', '#instaeditor')

			$("#fblinkcontrol").hide();
			$("#twitlinkcontrol").hide();
			$("#pintlinkcontrol").hide();
			$("#instalinkcontrol").show();
			$("#youtubelinkcontrol").hide();

			$('#instalink').val($(this).attr("href"));

			if( !$('.socialmedia_switcher').hasClass('active')){
				$('.socialmedia_switcher').click();
			};
			scroll_controlles();
		});

		$(".youtube").click(function(e){
			e.stopPropagation();
			e.preventDefault();

			close_all_accordions();

			$("#socialmediacontrols").show();
			$('.socialmedia_switcher').attr('href', '#youtubeeditor')

			$("#fblinkcontrol").hide();
			$("#twitlinkcontrol").hide();
			$("#pintlinkcontrol").hide();
			$("#instalinkcontrol").hide();
			$("#youtubelinkcontrol").show();

			$('#youtubelink').val($(this).attr("href"));

			if( !$('.socialmedia_switcher').hasClass('active')){
				$('.socialmedia_switcher').click();
			};
			scroll_controlles();
		});
		/* Social Media End */



		function scroll_controlles( height=500, sroll= 300 ){

			console.log(sroll);
			$(".tempelate-bottom-left").animate({ scrollTop: height}, sroll);
		}

		function close_all_accordions(){
			$(".card-header").each(function(){

				if($(this).hasClass("active")){
					$(this).click(0);
				}
			});
		}


		$("#btntext").on('input', function(){
			
			var temp_link = $(this).val();
			var check = change_link.find("p");
			if( check && check.length > 0){
				change_link.find("p").text(temp_link);
			}else{
				change_link.text(temp_link);
			}
		});

		$("#btnlink").on('input', function(){
			
			var temp_link = $(this).val();
			change_link.attr("href", temp_link);
		});










		
		//Add ToolTip function
		iframe.find(".edit_content").hover(function(){
			$(this).attr("data-toggle","tooltip");
			$(this).attr("title","Click Here To Edit");
			$(this).css("cursor","pointer");
		},function(){
			$(this).removeAttr("data-toggle");
			$(this).removeAttr("title");
			$(this).css("cursor","initial");
		}); 
		
		iframe.find(".Timer").hover(function(){
			$(this).attr("data-toggle","tooltip");
			$(this).attr("title","Click Here To Edit Timer");
			$(this).css("cursor","pointer");
		},function(){
			$(this).removeAttr("data-toggle");
			$(this).removeAttr("title");
			$(this).css("cursor","initial");
		});

		iframe.find("img").hover(function(){
			// alert($(this).attr("src"));
			$(this).attr("data-toggle","tooltip");
			$(this).attr("title","Click Here To Change Icon");
			$(this).css("cursor","pointer");
		},function(){
			$(this).removeAttr("data-toggle");
			$(this).removeAttr("title");
			$(this).css("cursor","initial");
		});
		
		iframe.find(".editLink").hover(function(){
			$(this).attr("data-toggle","tooltip");
			$(this).attr("title","Click Here To Change Link");
			$(this).css("cursor","pointer");
        },function(){
			$(this).removeAttr("data-toggle");
			$(this).removeAttr("title");
			$(this).css("cursor","initial");
		});
		

		
		
		iframe.find(".edit_content").click(function(e){
           e.stopPropagation();
			// A CTA is an <a class="editLink"> wrapping <span class="edit_content">,
			// so a click lands on the SPAN: this handler ran, stopped the bubble and
			// opened the content modal, and the anchor's .editLink handler (which
			// calls preventDefault + opens the link control) never fired — which is
			// also why the anchor then navigated the sandboxed preview to a blank
			// page. When we're inside a link, hand off to that link control instead.
			var $inLink = $(this).closest('.editLink');
			if ($inLink.length) {
				e.preventDefault();          // the anchor must NOT navigate the frame
				$inLink.trigger('click');    // open the link editor in the side control
				return;
			}
			$('#contentModal').modal('show');
			$unique_one2 =Math.floor(Math.random() * 1000) + 1;
			$curr_apply=$(this).attr("id",$unique_one2);

			//remove regenerate-button if exist
			// $(".regenerate-button").remove();
			var currentText = $(this).html();

			//alert(currentText);
			var ed = tinymce.get('tinymce2');
			ed.setContent(currentText);
			// Dark-skin the edit AREA (an iframe with its own document) so the text
			// is readable on the IC-dark modal. Scoped to THIS editor only — the
			// global TinyMCE init has no content_style, so its default dark text was
			// invisible on the dark surface. Inject once into the editor's iframe.
			try {
				var edoc = ed.getDoc();
				if (edoc && !edoc.getElementById('ic-tmce-darkskin')) {
					var st = edoc.createElement('style');
					st.id = 'ic-tmce-darkskin';
					st.textContent =
						'body{background:#141a2b !important;color:#eef1fb !important;' +
						'font-family:Inter,system-ui,sans-serif;}' +
						'a{color:#ff8b73 !important;}' +
						'::selection{background:#ff6a4d55;}';
					edoc.head.appendChild(st);
				}
			} catch (e) { /* editor not fully ready — harmless */ }
        });

		// ── Scrolling bars (ticker / strip marquees) — dedicated editor ──────────
		// These bars loop TWO duplicate copies of the text for a seamless scroll,
		// with items separated by decorative nodes (<span class="ticker-dot"> / <i>).
		// The free-text modal only edited ONE copy and mangled the separators, so the
		// loop desynced. Route a click to #popupMarquee and rebuild BOTH copies from a
		// clean one-item-per-line list.
		ic_frameDocs().forEach(function(doc){
			try {
				if (doc && !doc.getElementById('aa-mq-affordance')) {
					var st = doc.createElement('style'); st.id = 'aa-mq-affordance';
					// Injected into the frame's real <head> — OUTSIDE #funnelrh/#funnelrb —
					// so this editor-only affordance is never serialised into the page.
					st.textContent = '.ticker,.strip{cursor:pointer}' +
						'.ticker:hover,.strip:hover{outline:2px dashed rgba(255,106,77,.7);outline-offset:-3px}';
					(doc.head || doc.documentElement).appendChild(st);
				}
			} catch (e) { /* frame not ready — harmless */ }
		});
		var aaMqTrack = null, aaMqSep = '';
		function aaMqSeparator($track){
			if ($track.hasClass('ticker-track')) return '<span class="ticker-dot"></span>';
			if ($track.hasClass('strip-track'))  return '<i></i>';
			var first = $track.children('span').first()[0];
			if (first){ for (var k = 0; k < first.childNodes.length; k++){ if (first.childNodes[k].nodeType === 1) return first.childNodes[k].outerHTML; } }
			return '';
		}
		function aaMqPhrases($track){
			var out = [], first = $track.children('span').first()[0];
			if (!first) return out;
			for (var k = 0; k < first.childNodes.length; k++){
				var n = first.childNodes[k];
				if (n.nodeType === 3){ var t = (n.nodeValue || '').replace(/\s+/g, ' ').trim(); if (t) out.push(t); }
			}
			return out;
		}
		function aaMqEscape(s){ return String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;'); }
		iframe.find('.ticker, .strip').attr('title', 'Click to edit this scrolling bar')
			.off('click.aamq').on('click.aamq', function(e){
				e.preventDefault(); e.stopPropagation();
				var $track = $(this).find('.ticker-track, .strip-track').first();
				if (!$track.length) return;
				aaMqTrack = $track; aaMqSep = aaMqSeparator($track);
				$('#aaMarqueeText').val(aaMqPhrases($track).join('\n'));
				$('#popupMarquee').modal('show');
				setTimeout(function(){ try { $('#aaMarqueeText').focus(); } catch (e2) {} }, 250);
			});
		$('#aaMarqueeSave').off('click.aamq').on('click.aamq', function(){
			if (!aaMqTrack || !aaMqTrack.length){ $('#popupMarquee').modal('hide'); return; }
			var lines = ($('#aaMarqueeText').val() || '').split('\n').map(function(s){ return $.trim(s); }).filter(function(s){ return s.length; });
			if (!lines.length){ $('#popupMarquee').modal('hide'); return; }
			var copy = lines.map(function(p){ return aaMqEscape(p) + aaMqSep; }).join('');
			aaMqTrack.html('<span>' + copy + '</span><span>' + copy + '</span>');
			$('#popupMarquee').modal('hide');
			if (typeof window.flushAutosave === 'function') window.flushAutosave('marquee edit');
			else if (typeof window.scheduleAutosave === 'function') window.scheduleAutosave('marquee edit');
		});

		// ── Content lists (<ul>) — dedicated add / edit / delete / reorder editor ──
		// Eligible lists were tagged .aa-ed-list above. Clicking one opens #popupList;
		// Save rebuilds the <li>s from the row list, cloning each item's own structure
		// so its bullet/icon (and numbering) is preserved, and stripping the alf-* slot
		// class (no CSS uses it) so added items don't collide with content-gen keys.
		ic_frameDocs().forEach(function(doc){
			try {
				if (doc && !doc.getElementById('aa-list-affordance')) {
					var st = doc.createElement('style'); st.id = 'aa-list-affordance';
					st.textContent = '.aa-ed-list{cursor:pointer}' +
						'.aa-ed-list:hover{outline:2px dashed rgba(255,106,77,.7);outline-offset:3px}';
					(doc.head || doc.documentElement).appendChild(st);
				}
			} catch (e) { /* frame not ready — harmless */ }
		});
		var aaListUl = null, aaListTpl = null;
		function aaListTextHolder(li){
			var alf = li.querySelector('[class*="alf-"]');
			if (alf) return alf;
			var direct = [];
			for (var i = 0; i < li.children.length; i++){ if (li.children[i].tagName === 'SPAN') direct.push(li.children[i]); }
			if (direct.length) return direct[direct.length - 1];   // icons come first; text span is last
			var spans = li.getElementsByTagName('span');
			if (spans.length) return spans[spans.length - 1];
			return li;
		}
		function aaListItemText(li){ var h = aaListTextHolder(li); return (h.textContent || '').replace(/\s+/g, ' ').trim(); }
		function aaListRenumber(li, index){
			var holder = aaListTextHolder(li);
			for (var i = 0; i < li.children.length; i++){
				var k = li.children[i];
				if (k === holder) continue;
				var t = (k.textContent || '').trim();
				if (/^\d+$/.test(t)){ var s = String(index + 1); var w = t.length >= 2 ? 2 : 1; while (s.length < w) s = '0' + s; k.textContent = s; return; }
			}
		}
		function aaListBuildLi(text, index){
			var li = aaListTpl.cloneNode(true);
			var holder = aaListTextHolder(li);
			if (holder.classList){ Array.prototype.slice.call(holder.classList).forEach(function(c){ if (c.indexOf('alf-') === 0) holder.classList.remove(c); }); }
			holder.textContent = text;
			aaListRenumber(li, index);
			return li;
		}
		function aaListAutoGrow(ta){ if (!ta) return; ta.style.height = 'auto'; ta.style.height = Math.max(26, ta.scrollHeight) + 'px'; }
		function aaListReindex(){
			var $rows = $('#aaListRows .aa-li-row');
			$rows.each(function(i){ $(this).find('.aa-li-idx').text(i + 1); });
			var n = $rows.length;
			$('#aaListCount').text(n + (n === 1 ? ' item' : ' items'));
		}
		function aaListAddRow(text){
			var $row = $('<div class="aa-li-row">'
				+ '<span class="aa-li-idx"></span>'
				+ '<textarea class="aa-li-input" rows="1" spellcheck="false"></textarea>'
				+ '<span class="aa-li-btns">'
				+   '<button type="button" class="aa-li-up" title="Move up" aria-label="Move up"><svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 15l-6-6-6 6"/></svg></button>'
				+   '<button type="button" class="aa-li-down" title="Move down" aria-label="Move down"><svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9l6 6 6-6"/></svg></button>'
				+   '<button type="button" class="aa-li-del" title="Delete item" aria-label="Delete item"><svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6L6 18M6 6l12 12"/></svg></button>'
				+ '</span></div>');
			var ta = $row.find('.aa-li-input')[0];
			ta.value = text || '';
			$('#aaListRows').append($row);
			aaListAutoGrow(ta);
		}
		function aaOpenListEditor(ul){
			var firstLi = ul.querySelector(':scope > li');
			if (!firstLi) return;
			aaListUl = ul; aaListTpl = firstLi.cloneNode(true);
			$('#aaListRows').empty();
			var lis = ul.querySelectorAll(':scope > li');
			for (var i = 0; i < lis.length; i++) aaListAddRow(aaListItemText(lis[i]));
			aaListReindex();
			$('#popupList').modal('show');
			// a textarea can only measure its content height once it's visible
			setTimeout(function(){ $('#aaListRows .aa-li-input').each(function(){ aaListAutoGrow(this); }); }, 60);
		}
		iframe.find('.aa-ed-list').attr('title', 'Click to edit this list')
			.off('click.aalist').on('click.aalist', function(e){
				e.preventDefault(); e.stopPropagation(); aaOpenListEditor(this);
			});
		$('#aaListRows').off('.aalist')
			.on('input.aalist', '.aa-li-input', function(){ aaListAutoGrow(this); })
			.on('click.aalist', '.aa-li-del', function(){ $(this).closest('.aa-li-row').remove(); aaListReindex(); })
			.on('click.aalist', '.aa-li-up', function(){ var $r = $(this).closest('.aa-li-row'); var $p = $r.prev('.aa-li-row'); if ($p.length){ $p.before($r); aaListReindex(); } })
			.on('click.aalist', '.aa-li-down', function(){ var $r = $(this).closest('.aa-li-row'); var $n = $r.next('.aa-li-row'); if ($n.length){ $n.after($r); aaListReindex(); } });
		$('#aaListAdd').off('click.aalist').on('click.aalist', function(){ aaListAddRow(''); aaListReindex(); var el = $('#aaListRows .aa-li-input').last()[0]; aaListAutoGrow(el); try { el.focus(); } catch (e2) {} });
		$('#aaListSave').off('click.aalist').on('click.aalist', function(){
			if (!aaListUl || !aaListTpl){ $('#popupList').modal('hide'); return; }
			var texts = [];
			$('#aaListRows .aa-li-input').each(function(){ var v = $.trim((this.value || '').replace(/\s+/g, ' ')); if (v) texts.push(v); });
			if (!texts.length){ $('#popupList').modal('hide'); return; }   // never leave an empty list
			var frag = aaListUl.ownerDocument.createDocumentFragment();
			texts.forEach(function(t, i){ frag.appendChild(aaListBuildLi(t, i)); });
			aaListUl.innerHTML = '';
			aaListUl.appendChild(frag);
			$('#popupList').modal('hide');
			if (typeof window.flushAutosave === 'function') window.flushAutosave('list edit');
			else if (typeof window.scheduleAutosave === 'function') window.scheduleAutosave('list edit');
		});

		// ── Price ledgers — dedicated two-field (name + price) editor ──────────────
		// Rows + total were tagged .aa-ed-price above. A click opens #popupPrice; Save
		// rebuilds the rows in place (cloning the ledger's own row so dividers/styling
		// stay), and writes the (editable) total. alf-* slot classes are kept on clones
		// so re-open still detects the fields; content is saved literally so a duplicate
		// key never reaches a re-bake.
		ic_frameDocs().forEach(function(doc){
			try {
				if (doc && !doc.getElementById('aa-price-affordance')) {
					var st = doc.createElement('style'); st.id = 'aa-price-affordance';
					st.textContent = '.aa-ed-price{cursor:pointer}.aa-ed-price:hover{outline:2px dashed rgba(255,106,77,.7);outline-offset:2px}';
					(doc.head || doc.documentElement).appendChild(st);
				}
			} catch (e) { /* frame not ready — harmless */ }
		});
		var aaPriceGroup = null;
		function aaPriceReindex(){
			var $rows = $('#aaPriceRows .aa-price-row');
			$rows.each(function(i){ $(this).find('.aa-price-idx').text(i + 1); });
			var n = $rows.length; $('#aaPriceCount').text(n + (n === 1 ? ' item' : ' items'));
		}
		function aaPriceAutoGrow(ta){ if (!ta) return; ta.style.height = 'auto'; ta.style.height = Math.max(26, ta.scrollHeight) + 'px'; }
		function aaPriceAddRow(name, price){
			var $row = $('<div class="aa-price-row">'
				+ '<span class="aa-price-idx"></span>'
				+ '<textarea class="aa-price-name" rows="1" spellcheck="false" placeholder="Item name"></textarea>'
				+ '<input type="text" class="aa-price-val" spellcheck="false" placeholder="$0">'
				+ '<span class="aa-li-btns">'
				+   '<button type="button" class="aa-li-up" title="Move up" aria-label="Move up"><svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 15l-6-6-6 6"/></svg></button>'
				+   '<button type="button" class="aa-li-down" title="Move down" aria-label="Move down"><svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9l6 6 6-6"/></svg></button>'
				+   '<button type="button" class="aa-li-del" title="Delete item" aria-label="Delete item"><svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6L6 18M6 6l12 12"/></svg></button>'
				+ '</span></div>');
			$row.find('.aa-price-name')[0].value = name || '';
			$row.find('.aa-price-val').val(price || '');
			$('#aaPriceRows').append($row);
			aaPriceAutoGrow($row.find('.aa-price-name')[0]);
		}
		function aaOpenPriceEditor(g){
			if (!g || !g.rows || !g.rows.length) return;
			aaPriceGroup = g;
			$('#aaPriceRows').empty();
			g.rows.forEach(function(r){ aaPriceAddRow(aaPriceText(aaPriceRowTitle(r)), aaPriceText(aaPriceRowValue(r))); });
			if (g.total && g.total.valueEl){ $('#aaPriceTotalLabel').val(aaPriceText(g.total.labelEl)); $('#aaPriceTotalValue').val(aaPriceText(g.total.valueEl)); $('#aaPriceTotalWrap').css('display', 'flex'); }
			else $('#aaPriceTotalWrap').css('display', 'none');
			aaPriceReindex();
			$('#popupPrice').modal('show');
			setTimeout(function(){ $('#aaPriceRows .aa-price-name').each(function(){ aaPriceAutoGrow(this); }); }, 60);
		}
		function aaPriceBuildRow(name, price){
			var row = aaPriceGroup.rows[0].cloneNode(true);
			var t = aaPriceRowTitle(row), v = aaPriceRowValue(row);
			if (t) t.textContent = name;
			if (v) v.textContent = price;
			return row;
		}
		iframe.find('.aa-ed-price').attr('title', 'Click to edit this price list');
		// DELEGATED (not a direct bind): Save replaces the row elements with fresh
		// clones, so a direct handler would be lost on the new rows — re-opening then
		// did nothing. Delegating on the frame document keeps every current AND future
		// .aa-ed-price row clickable.
		iframe.off('click.aaprice').on('click.aaprice', '.aa-ed-price', function(e){
			e.preventDefault(); e.stopPropagation();
			var g = aaPriceGroups[+$(this).attr('data-aa-price')]; if (g) aaOpenPriceEditor(g);
		});
		$('#aaPriceRows').off('.aaprice')
			.on('input.aaprice', '.aa-price-name', function(){ aaPriceAutoGrow(this); })
			.on('click.aaprice', '.aa-li-del', function(){ $(this).closest('.aa-price-row').remove(); aaPriceReindex(); })
			.on('click.aaprice', '.aa-li-up', function(){ var $r = $(this).closest('.aa-price-row'); var $p = $r.prev('.aa-price-row'); if ($p.length){ $p.before($r); aaPriceReindex(); } })
			.on('click.aaprice', '.aa-li-down', function(){ var $r = $(this).closest('.aa-price-row'); var $n = $r.next('.aa-price-row'); if ($n.length){ $n.after($r); aaPriceReindex(); } });
		$('#aaPriceAdd').off('click.aaprice').on('click.aaprice', function(){ aaPriceAddRow('', ''); aaPriceReindex(); var el = $('#aaPriceRows .aa-price-name').last()[0]; aaPriceAutoGrow(el); try { el.focus(); } catch (e2) {} });
		$('#aaPriceSave').off('click.aaprice').on('click.aaprice', function(){
			if (!aaPriceGroup || !aaPriceGroup.rows.length){ $('#popupPrice').modal('hide'); return; }
			var items = [];
			$('#aaPriceRows .aa-price-row').each(function(){
				var name = $.trim(($(this).find('.aa-price-name')[0].value || '').replace(/\s+/g, ' '));
				var price = $.trim($(this).find('.aa-price-val').val() || '');
				if (name || price) items.push({ name: name, price: price });
			});
			if (!items.length){ $('#popupPrice').modal('hide'); return; }
			var first = aaPriceGroup.rows[0], oldRows = aaPriceGroup.rows.slice();
			var doc = first.ownerDocument, frag = doc.createDocumentFragment(), newRows = [];
			items.forEach(function(it){ var r = aaPriceBuildRow(it.name, it.price); newRows.push(r); frag.appendChild(r); });
			first.parentNode.insertBefore(frag, first);
			oldRows.forEach(function(r){ if (r.parentNode) r.parentNode.removeChild(r); });
			aaPriceGroup.rows = newRows;   // refresh so a re-open reads the current rows
			if (aaPriceGroup.total){
				if (aaPriceGroup.total.valueEl) aaPriceGroup.total.valueEl.textContent = $.trim($('#aaPriceTotalValue').val() || '');
				if (aaPriceGroup.total.labelEl) aaPriceGroup.total.labelEl.textContent = $.trim($('#aaPriceTotalLabel').val() || '');
			}
			$('#popupPrice').modal('hide');
			if (typeof window.flushAutosave === 'function') window.flushAutosave('price edit');
			else if (typeof window.scheduleAutosave === 'function') window.scheduleAutosave('price edit');
		});

		var change_link = '';
		var aaImgHandoffBusy = false;
		iframe.find('.editLink').click( function(e){

			// Logo / image-only links: a logo is <a class="brand editLink" href="#">
			// wrapping an <img>. A click on that image (or anywhere in an image-only
			// link) must open the IMAGE control, not the link editor — and must never
			// navigate the sandboxed preview. Hand off to the img handler (guarded
			// against re-entry). Text CTAs (<a> wrapping .edit_content/text) are
			// unaffected and still open the link editor below.
			var $lnkImg = $(e.target).is('img') ? $(e.target) : $(this).find('img').first();
			var noText = $(this).children('.edit_content').length === 0
					   && $.trim($(this).clone().children().remove().end().text()) === '';
			if (!aaImgHandoffBusy && $lnkImg.length && ($(e.target).is('img') || noText)) {
				e.preventDefault();
				e.stopPropagation();
				aaImgHandoffBusy = true;
				$lnkImg.trigger('click');   // opens the image modal via the img handler
				aaImgHandoffBusy = false;
				return;
			}

			e.preventDefault();
			e.stopPropagation();

			console.log('clicked');
			
			$("#inputcontrols").show();
			$("#thanyoubtncontrols").show();
			$("#formbtncontrols").hide();

			close_all_accordions();
			if( !$('#inputcontrols .card-header').hasClass('active') ) {
				$('#inputcontrols .card-header').click();
				scroll_controlles(1000);
			}

			change_link = $(this);
			var btn_text = '';
			var check_btn = change_link.find("p");
			if( check_btn && check_btn.length > 0){
				btn_text = check_btn.text();
			}else{
				btn_text = change_link.text();
			}
			$('#btntext').val( btn_text );

			var btn_link = change_link.attr("href");
			$('#btnlink').val( btn_link );

			$('.link_change').show();

			// console.log(btn_text);
			// console.log(btn_link);

		});

		var change_link = '';
		iframe.find('button').click( function(e){

			e.preventDefault();
			e.stopPropagation();

			if($(this).hasClass('edit_content') || $(this).hasClass('editLink') ){
				return;
			}

			$("#inputcontrols").show();
			$("#thanyoubtncontrols").show();
			$("#formbtncontrols").hide();

			close_all_accordions();
			if( !$('#inputcontrols .card-header').hasClass('active') ) {
				$('#inputcontrols .card-header').click();
				scroll_controlles(1000);
			}

			change_link = $(this);
			var btn_text = '';
			var check_btn = change_link.find("p");
			if( check_btn && check_btn.length > 0){
				btn_text = check_btn.text();
			}else{
				btn_text = change_link.text();
			}
			$('#btntext').val( btn_text );

			$('.link_change').hide();

		});

		iframe.find('form').submit(function(e){

			e.preventDefault();
			e.stopPropagation();

			console.log('form submitted');

		});
				// iframe.find(".edit_content").click(function(){
       
      	$(".edit_content").hover(
			function() {
				// $(this).append('<button class="regenerate-button" style="background-color: yellow;border-radius: 5px; color: black; border: none; padding: 5px 10px; font-size: 14px; float: right; font-weight: bold;">Rewrite</button>');
				$(this).css("border", "3px dotted white");
			},
			function() {

				// $(".regenerate-button").remove();
				$(this).css("border", "none");
			}
		);


		iframe.find(".regenerate-button").click(function(event){
       		event.stopPropagation();
			event.preventDefault();
			console.log('clicked');
       		// $(".edit_content").click(function() {

       		// 	var keyword = $(this).text();
       		// 	var wordCount = keyword.trim().split(/\s+/).length;
       		// 	var currentEditContent = $(this);


       		// 	$.ajax({
            //     url : "<?= base_url('rewriteAIdata') ?>",
            //     data : {keyword:keyword,words:wordCount} ,
            //     type : 'POST',
            //     // dataType : 'JSON',
            //     success(data)
            //     {
            //     	var responseData = JSON.parse(data);
            //     	var desc = responseData.desc;
            //       currentEditContent.text(desc);
            //     }
            //  }); 
       		// });
        }); 


		
		iframe.find(".autoResponser, .name, .email, .phone, #name, #email, #phone, button.cta, form button[type='submit']").click(function(e){

			e.preventDefault();
			e.stopPropagation();

			$("#inputcontrols").show();
			$("#formbtncontrols").show();
			$('#thanyoubtncontrols').hide();

			var formbtntext = icFormBtnLabel(iframe).text().trim();
			$("#formbtntext").val(formbtntext);

			// Pre-fill the field-label controls from what's actually VISIBLE: the
			// floating <label> text on v2 templates, falling back to the placeholder
			// attribute on legacy templates. Mirrors setFormFieldText()'s write path.
			$("#nameplaceholder").val(getFormFieldText('name'));
			$("#emailplaceholder").val(getFormFieldText('email'));
			$("#phoneplaceholder").val(getFormFieldText('phone'));

			close_all_accordions();
			if( !$('#inputcontrols .card-header').hasClass('active') ) {
				$('#inputcontrols .card-header').click();
				scroll_controlles(1000);
			}

		});

		
		// iframe.find("#paste_html_code1").click(function(event){
		// 	event.stopPropagation();
		// 	if($("#ars").hasClass('show')){
				
		// 	}else{
		// 		$("#ars").addClass('show');
		// 		$('#accordion .collapse').not("#ars").removeClass('show');
		// 	}
		// });
		
		// if(iframe.find(".fuseddname").length){
		// 	iframe.find(".fuseddname").attr("name","name");
		// 	iframe.find(".fuseddname").attr("id","name");
		// 	$("#inputcontrols").show();
		// 	$("#formbtncontrols").show();
		// 	$("#nameplaceholdermain").show();
		// 	$("#nameplaceholder").val(iframe.find(".fuseddname").attr("placeholder"));
		// }
		// if(iframe.find(".fuseddemail").length){
		// 	iframe.find(".fuseddemail").attr("name","email");
		// 	iframe.find(".fuseddemail").attr("id","email");
		// 	$("#inputcontrols").show();
		// 	$("#formbtncontrols").show();
		// 	$("#emailplaceholder").val(iframe.find(".fuseddemail").attr("placeholder"));
		// }
		
		if(iframe.find(".autoResponser").length){
			// if(iframe.find("#WPf_submit").length == 0){
			// 	iframe.find(".autoResponser").wrap("<span id='WPf_submit'></span>");
			// }
			$("#inputcontrols").show();
			$("#formbtncontrols").show();
			$('#thanyoubtncontrols').hide();
			// $("#formbtntext").val(iframe.find(".autoResponser").val());
			var formbtntext = iframe.find(".autoResponser").text();
			formbtntext = formbtntext.trim();
			var nameplaceholder = iframe.find(".name").attr("placeholder");
			var emailplaceholder = iframe.find(".email").attr("placeholder");
			var phoneplaceholder = iframe.find(".phone").attr("placeholder");

			// console.log(formbtntext);

			$("#formbtntext").val(formbtntext);
			$("#nameplaceholder").val(nameplaceholder);
			$("#emailplaceholder").val(emailplaceholder);
			$("#phoneplaceholder").val(phoneplaceholder);

		}

		/*
		if(iframe.find("#video").length){
			$("#videocontrols").show();
		}
		*/

		//match if autoresponder_data is object
		if( typeof autoresponder_data == 'object' ){

			console.log(typeof autoresponder_data);

			var mailerType = autoresponder_data.mailer;
			var param1 = autoresponder_data.parameter1;
			var param2 = autoresponder_data.parameter2;
			var param3 = autoresponder_data.parameter3;
			var param4 = autoresponder_data.parameter4;
			var param5 = autoresponder_data.parameter5;

			if(mailerType !=''){
				hideMailerParameter();
				if(mailerType == 1){
					getResponselist(param1,param3);
				}else if(mailerType == 2){
					getActCampaignlist(param2,param1,param3);
				}else if(mailerType == 3){
					getMailchimplist(param1,param3);
				}else if(mailerType == 4){
					getAweberlist(param1,param2,param3);
				}else if(mailerType == 5){
					geticontactlist(param1,param2,param3,param5);
				}else if(mailerType == 6){
					getwebinarlists(param1,param2,param4,param3);
				}else if(mailerType == 7){
					getConvertKitlist(param1,param3);
				}
				$("#mailerType").val(mailerType);
			}

		}

		/*
		if(iframe.find("#hiddenAutoResponser").length > 0){
			var mailerType= iframe.find("#hiddenAutoResponser #mailer").val();
			var param1=iframe.find("#hiddenAutoResponser #parameter1").val();
			var param2=iframe.find("#hiddenAutoResponser #parameter2").val();
			var param3=iframe.find("#hiddenAutoResponser #parameter3").val();
			var param4=iframe.find("#hiddenAutoResponser #parameter4").val();
			var param5=iframe.find("#hiddenAutoResponser #parameter5").val();
			if(mailerType !=''){
				hideMailerParameter();
				if(mailerType == 1){
					getResponselist(param1,param3);
				}else if(mailerType == 2){
					getActCampaignlist(param2,param1,param3);
				}else if(mailerType == 3){
					getMailchimplist(param1,param3);
				}else if(mailerType == 4){
					getAweberlist(param1,param2,param3);
				}else if(mailerType == 5){
					geticontactlist(param1,param2,param3,param5);
				}else if(mailerType == 6){
					getwebinarlists(param1,param2,param4,param3);
				}else if(mailerType == 7){
					getConvertKitlist(param1);
				}
				$("#mailerType").val(mailerType);
			
			}
			
		}
		*/


		if(iframe.find("#copyhiddenAutoResponser").length > 0 ){
			$("#autoresponderType").val('copypaste');
			$('#apiForm').hide();
			$('#copypasteForm').show();
			var copypastecode=iframe.find("#paste_html_code").html().replaceAll('<funnelr','<script');
			$('#copypaste_code').val(copypastecode.replaceAll('funnelr>','script>'));
		}

		// iframe.find(".rmv_bg").hover(function(){
        //          $('#bg_cng').removeClass('bg-background');
        //          $('#bg_cng').removeAttr("data-toggle");
        //          $('#bg_cng').removeAttr("title");
        //          $('#bg_cng').css("cursor","initial");
        //          $("#stylingoption").hide();
        //      },function(){
        //         $('#bg_cng').addClass("bg-background");
        //         $('#bg_cng').attr("data-toggle","tooltip");
        //          $('#bg_cng').attr("title","Click to Change Background Image");
        //          $('#bg_cng').css("cursor","pointer");
        // });

			
		iframe.find(".bg-background").hover(function(){
                 $(this).attr("data-toggle","tooltip");
                 $(this).attr("title","Click to Change Background Image");
                 $(this).css("cursor","pointer");
             },function(){
                $(this).removeAttr("data-toggle");
                $(this).removeAttr("title");
                $(this).css("cursor","initial");
        });
		
		iframe.find(".bg-background").on("click",function(){
			iframe.find(".bg-background").removeClass("bg-selected");
			
			
			if($("#stylingopt").hasClass('show')){
				
			}else{
				console.log("Hello")
				$("#stylingopt").addClass('show');
				// $('#accordion .collapse').not("#stylingopt").removeClass('show');
			}
			$("#stylingoption").show();
			$("#stylingoption").click();
			$(this).addClass("bg-selected");
			
			//alert(iframe.find(".bg-background").css("background-image"));
			if(iframe.find(".bg-background").css("background-image") != undefined){
				
				if($("#stylingopt").hasClass('show')){
					
				}else{
					$("#stylingopt").addClass('show');
				}
				$(".backgroundImgDiv").fadeIn();
			}
			var filterStatus = $(this).attr("data-bgFilter");
            if(filterStatus != undefined)
			{
				if(filterStatus=="true"){
					$("#headerImgFilter").prop("checked",true);
					$(".headeImgFilterdenHide").show();
				}else{
					$("#headerImgFilter").prop("checked",false);
					$(".headeImgFilterdenHide").show();
				}
			}
			var fittoblock = $(this).attr("data-bgImgFit");
			if(fittoblock != undefined)
			{
				if(fittoblock=="true"){
					$("#HeaderImgfitImage").prop("checked",true);
				}
				else
				{
					$("#HeaderImgfitImage").prop("checked",false);
				}
			}
			var filterDenStatus = $(this).attr("data-bgFilterDensity");
			if(filterDenStatus!=undefined)
			{
				$("#headerImgfilterDensity").val(filterDenStatus);
			}
			var bgTest = $(this).attr("data-background");
			// alert(bgTest);
			if(bgTest !=undefined && bgTest !="")
			{
				   // alert(bgTest);
				$(".fittoblockHide").show();
				$(".headerImgFilterhide").show();
				var filterBgStatus = $(this).attr("data-bgFilter");
				if(filterBgStatus!=undefined && filterBgStatus=="true")
				{
				  $(".headeImgFilterdenHide").show();
				}else{
				  $(".headeImgFilterdenHide").hide(); 
				}
			}
			else
			{
				$(".fittoblockHide").hide(); 
				$(".headerImgFilterhide").hide(); 
				$(".headeImgFilterdenHide").hide(); 
			}
               
            var DivbgimgHeader = $(this).css("background-image");
            // fbmimg = Divbgimg.substr(5,Divbgimg.length - 7);
            var fbmimgLen1 = DivbgimgHeader.search("url");
            var fbLen = DivbgimgHeader.length;
            var fbmimg1 = DivbgimgHeader.substr(fbmimgLen1+5);
            // var fbmimg = iframe.find(".fbcoverimg").attr("src");
            var actualImgLink = fbmimg1.substr(0,fbmimg1.length - 2);
            // alert(.width);
            var img = new Image();
				img.onload = function() {
				  document.getElementById("recomandDimHeaderImg").innerHTML="Recommended dimensions:"+this.width + 'x' + this.height+"px";
				  var html='Recommended Dimensions: <b><span id="recommendedWidthHeader">'+this.width+'</span>*<span id="recommendedHeightHeader">'+this.height+'</span>&nbsp;px (WxH) </b>';
				  document.getElementById("recommendation").innerHTML=html;
				  document.getElementById("normrecommendation").style.display='none';
				}
                img.src=actualImgLink;
				 document.getElementById("selectedbgimage").value=actualImgLink;
				document.getElementById("normrecommendation").style.display='block';
				document.getElementById("recommendation").style.display='none';

			$("#headerImg").click();
		});
		
		$("#headerImgfilterDensity").change(function(){
            var bgImgFilter = $(this).val();
            var bgImg =  iframe.find(".bg-selected").attr("data-background");
            var bgImgFilterStatus =  iframe.find(".bg-selected").attr("data-bgFilter");
            var fitStat =  iframe.find(".bg-selected").attr("data-bgImgFit");
            if(fitStat == "true")
			{
				bgsize="cover";
			}else{
				bgsize="auto";
			}
			// console.log(bgsize);
			// console.log(bgImg);
			// console.log(bgImgFilterStatus);
			if(bgImg != undefined && bgImgFilterStatus =="true")
			{
			   iframe.find(".bg-selected").css("background", "linear-gradient(45deg, rgba(24,23,92,"+bgImgFilter+"), rgba(24,23,92,"+bgImgFilter+")), url("+bgImg+") no-repeat center top / "+bgsize+"");  
			   iframe.find(".bg-selected").attr("data-bgFilterDensity",bgImgFilter);
			  
			}
			
		});
		$("#headerImgFilter").change(function(){
			var bgImg =  iframe.find(".bg-selected").attr("data-background");
			if(bgImg!=undefined)
			{
			  if($(this).is(":checked")){
				iframe.find(".bg-selected").css("background", "url("+bgImg+") no-repeat center top");  
				iframe.find(".bg-selected").attr("data-bgFilter","true");
				$(".headeImgFilterdenHide").show();
			  }else{
				iframe.find(".bg-selected").css("background", "url("+bgImg+") no-repeat center top");  
				iframe.find(".bg-selected").attr("data-bgFilter","false");
				$(".headeImgFilterdenHide").hide();
			  }
			  
			}

			if($("#HeaderImgfitImage").is(":checked")){
				iframe.find(".bg-selected").css("background-size", "cover");
				iframe.find(".bg-selected").attr("data-bgImgFit","true");
			}else{
				iframe.find(".bg-selected").css("background-size", "auto");
				iframe.find(".bg-selected").attr("data-bgImgFit","false");
			}
			
		});
		
		$("#HeaderImgfitImage").change(function(){
		  var bgImg =  iframe.find(".bg-selected").attr("data-background");
			if(bgImg !=undefined)
			{
			  if($(this).prop("checked")==true){
				iframe.find(".bg-selected").css("background-size", "cover");
				iframe.find(".bg-selected").attr("data-bgImgFit","true");
			  }else{
				iframe.find(".bg-selected").css("background-size", "auto");
				iframe.find(".bg-selected").attr("data-bgImgFit","false");
			  }
			  
			}
		});
		
		$("#headerImg").click(function(){
			//baseUrl
			var bg_selcted_img  = $("#selectedbgimage").val();
			if( bg_selcted_img.includes(baseUrl) || bg_selcted_img.includes("http") ){
				var currentSrc =$("#selectedbgimage").val();
			}
			
			$(".slct_img_sec").find(".currentimage").attr("src",currentSrc);
			
			$('.cu_imageSizediv').hide();
			$('#popup3').modal('show');
			loadMyImages(false);
			img_status = 1;
			var sizee=getFileSize(currentSrc);
			$("#recommendedSize").text('('+sizee+'KB)');
			
			var image_name = icCleanImageName(currentSrc);
			$(".ptitle").val(image_name);

			image_to_dimention('.recommended_size', currentSrc, 1);
		});
		
		//save content in editor
		$("#applychanges2").click(function(){
			var insertText = tinymce.get('tinymce2').getContent();

			// TinyMCE wraps content in <p> blocks. The old code stripped only the
			// FIRST <p>/</p> (String.replace with a string replaces one occurrence),
			// so multi-paragraph or restructured output left stray/unbalanced tags —
			// which, when written back via .html(), corrupted the target element's
			// structure (e.g. destroying the .n wrapper on a stat and breaking the
			// section nesting). Strip ALL paragraph wrappers; if TinyMCE produced
			// multiple <p> blocks, join them with spaces so no text is lost.
			var stripped = insertText
				.replace(/<p[^>]*>/gi, '')     // ALL opening <p …>
				.replace(/<\/p\s*>/gi, ' ')    // ALL closing </p> → space (avoid word-join)
				.replace(/&nbsp;/gi, ' ')
				.replace(/\s+/g, ' ')
				.trim();

			var $target = iframe.find("#" + $unique_one2);
			// Decode HTML entities before the plain-text write below. TinyMCE's
			// getContent() emits NAMED entities (© → "&copy;", & → "&amp;"), and the
			// text paths further down (.text() and text-node nodeValue) store their
			// argument verbatim — so writing the literal "&copy;" re-escapes the & to
			// "&amp;copy;" and the page then shows the literal text "&copy;". Parse the
			// stripped HTML and read textContent so the REAL character (©, &, é…) is
			// stored. (The .html(stripped) branch keeps entities — correct for innerHTML.)
			var $decode = document.createElement('div');
			$decode.innerHTML = stripped;
			var plainText = ($decode.textContent || $decode.innerText || '').replace(/\s+/g, ' ').trim();

			// SPACE GUARD. Headlines are split across two inline spans — a plain
			// "…title1" and a coloured "…title2" — and the template separates them
			// with a trailing space INSIDE the first span ("Three levers that </span>").
			// The .trim() above strips it, so after an edit the two halves render
			// glued together ("Three levers thatmove the needle."). If this element
			// is followed by an inline sibling on the same line, put the separating
			// space back. Uses   so a later collapse/trim can't eat it again.
			(function preserveInlineGap() {
				try {
					var node = $target.get(0);
					if (!node || plainText === '') return;
					var next = node.nextSibling;
					// Skip a whitespace-only text node to find the real next sibling.
					while (next && next.nodeType === 3 && !/\S/.test(next.nodeValue)) next = next.nextSibling;
					if (!next) return;
					// Only for INLINE siblings (a block sibling already breaks the line).
					var inline = /^(SPAN|A|B|STRONG|EM|I|U|SMALL|SUP|SUB)$/;
					if (next.nodeType === 1 && inline.test(next.tagName)) {
						plainText += ' ';
					} else if (next.nodeType === 3 && /^\S/.test(next.nodeValue)) {
						plainText += ' ';
					}
				} catch (e) { /* never block the edit */ }
			})();
			// LEADING space guard (mirror of preserveInlineGap for the PREVIOUS sibling).
			// preserveInlineGap only restores a TRAILING space (next sibling), so editing
			// the FIRST span fixes the seam but editing the SECOND span does not: its gap
			// lives on its leading side, and the .trim() above drops the space typed there
			// ("services with" + "Cinematic" -> "withCinematic"). If the previous sibling
			// is inline and does not already end in whitespace, put the seam space back as
			// a leading nbsp (collapse/trim proof, same as the trailing guard).
			(function preserveLeadingGap() {
				try {
					var node = $target.get(0);
					if (!node || plainText === '') return;
					var inline = /^(SPAN|A|B|STRONG|EM|I|U|SMALL|SUP|SUB)$/;
					var prev = node.previousSibling;
					while (prev && prev.nodeType === 3 && !/\S/.test(prev.nodeValue)) prev = prev.previousSibling;
					if (prev && prev.nodeType === 1 && inline.test(prev.tagName)) {
						if (!/\s$/.test(prev.textContent || '')) plainText = ' ' + plainText;
					} else if (prev && prev.nodeType === 3 && /\S$/.test(prev.nodeValue)) {
						plainText = ' ' + plainText;
					}
				} catch (e) { /* never block the edit */ }
			})();
			var hasInlineMarkup = /<(a|b|strong|em|i|u|span|br|small|sup|sub)\b/i.test(stripped);

			// STRUCTURE GUARD (fixes the broken 3-up stat bar): if the target wraps
			// ELEMENT children (e.g. a .schip wrapping .n + .l), we must NEVER blow
			// away those children with .html()/.text() — that flattens the wrapper
			// and orphans siblings, breaking the layout. Instead, update only the
			// target's OWN text nodes and leave child elements intact. This also
			// covers the case where a wrong element (a wrapper) received the edit.
			var hasElementChildren = $target.children().length > 0;
			if (hasElementChildren) {
				// Replace the element's direct text nodes with the edited plain text,
				// keeping every child element exactly where it is.
				var node = $target.get(0);
				if (node) {
					var replaced = false;
					for (var k = 0; k < node.childNodes.length; k++) {
						var cn = node.childNodes[k];
						if (cn.nodeType === 3) { // text node
							if (!replaced && plainText !== '') { cn.nodeValue = plainText; replaced = true; }
							else { cn.nodeValue = ''; }
						}
					}
					// No text node existed (all children are elements) — prepend one so
					// the edit isn't silently lost, without disturbing the children.
					if (!replaced && plainText !== '') {
						node.insertBefore(node.ownerDocument.createTextNode(plainText), node.firstChild);
					}
				}
			} else if (hasInlineMarkup) {
				$target.html(stripped);
			} else {
				$target.text(plainText);
			}
			$("#contentModal").modal('hide');
			// Autosave RIGHT NOW — don't wait for the 3s debounce timer. When the tab
			// isn't focused (or DevTools is closed), background setTimeout throttling
			// can delay/pause that timer for a long time, which is why edits "only
			// saved when the console was open". An explicit flush on Apply Changes
			// bypasses the timer entirely.
			if (typeof window.flushAutosave === 'function') { window.flushAutosave('content-modal apply'); }
			else if (typeof window.scheduleAutosave === 'function') { window.scheduleAutosave('content-modal apply'); }
        });

		$(".insertbtn").click(function(){
			var imgsrc=$("#MyImages").find(".currentimage").attr("src");
			var alttext=$("#MyImages").find(".alttext").val();
			var currentImageId = $('#imageIdTemp').val();
			if(iframe.find(".bg-selected").length){
				applyBgImage(iframe.find(".bg-selected"), imgsrc);
				iframe.find(".bg-background").removeClass("bg-selected");
				$(".backgroundImgDiv").fadeOut();
				$("#stylingoption").hide();
			}else{
				iframe.find("#"+currentImageId).attr('src',imgsrc);
				iframe.find("#"+currentImageId).attr('alt',alttext);
			}
			$("#popup3").modal('hide');

		});
		
		$(".useimage").click(function(){
			// Re-host the REAL Pixabay URL (data-pixurl), not the proxy src that's on
			// the <img> for display — downloadImages only accepts pixabay.com hosts.
			var $cur = $("#PremiumImages .slct_img_sec").find(".currentimage");
			var imgsrc = $cur.attr("data-pixurl") || $cur.attr("src");
			var alttext=$("#PremiumImages .slct_img_sec").find(".alttext").val();
			var currentImageId = $('#imageIdTemp').val();
			if(!imgsrc){ alertMsg("Please select an image first.","danger"); return; }
			// Send the previewURL (cdn.pixabay.com) as a fallback source — the server
			// re-hosts the full webformat first, but falls back to this if Pixabay
			// throttles the webformat host, so the import still succeeds.
			var fallbackSrc = $cur.attr("src") || '';
			$(".loaderautomater").show();
			$.ajax({
				url : "<?php echo site_url('sites/downloadImages');?>",
				data :{url:imgsrc, fallback: fallbackSrc},
				type : "POST",
				success(data)
				{
					var img_url = (data||'').trim();
					// The endpoint returns the S3 URL on success, or an error token
					// (IMAGE_FETCH_FAILED / UPLOAD_FAILED) with a 4xx. Guard on a real URL.
					if(img_url !== '' && /^https?:\/\//i.test(img_url)){
						if(iframe.find(".bg-selected").length){
							applyBgImage(iframe.find(".bg-selected"), img_url);
							iframe.find(".bg-background").removeClass("bg-selected");
							$(".backgroundImgDiv").fadeOut();
							$("#stylingoption").hide();
						}else{
							iframe.find("#"+currentImageId).attr('src',img_url);
							iframe.find("#"+currentImageId).attr('alt',alttext);
						}
						$("#popup3").modal('hide');
					} else {
						alertMsg("Couldn't import that image (Pixabay may be busy). Please try again.","danger");
					}
				},
				error: function(){
					// Without this the loader hung forever on a 4xx (jQuery skips
					// success on non-2xx), which is what made "Use This Image" get stuck.
					alertMsg("Couldn't import that image (Pixabay may be busy). Please try again.","danger");
				},
				complete: function(){
					$(".loaderautomater").hide();   // ALWAYS hide, success or error
				}
			});
		});

		// Write the chosen URL into the element's inline style as longhand
		// background-image (not the shorthand `background:`) so it can't
		// reset other background-* properties on re-edit, and survives any
		// stylesheet rule on the published page that targets the same
		// selector with normal specificity. Done by mutating the existing
		// style attribute string so we can append `!important` (jQuery's
		// .css() strips !important on most browsers).
		function applyBgImage($el, url){
			$el.attr("data-background", url);
			$el.attr("data-bgImgFit", "true");
			var existing = ($el.attr("style") || "")
				.replace(/(^|;)\s*background(-image|-size|-repeat|-position)?\s*:[^;]*/gi, "")
				.replace(/^\s*;\s*/, "")
				.replace(/;\s*;/g, ";");
			if (existing && !/;\s*$/.test(existing)) existing += ";";
			var bgDecls =
				"background-image:url('" + url.replace(/'/g, "%27") + "') !important;" +
				"background-size:cover !important;" +
				"background-repeat:no-repeat !important;" +
				"background-position:center top !important;";
			$el.attr("style", (existing + bgDecls).trim());
		}
		
		//image hover function
		iframe.find("img").hover(function() {
            //alert(this);  
            $(this).css("background-color","rgba(239, 62, 57, 0.53)");   
            $(this).css("border","1px solid #EF3E39");
          //  $("#editImageAcc a").css("background-color","rgba(4, 96, 159, 0.32)");
        },function () {
            $(this).css("background-color","");
            $(this).css("border","");
        });   
		
		//select Img
		iframe.find("img").click(function(event){
			//baseUrl

			if($(this).hasClass('no-edit')){
				return true;
			}

			// Skip social media icons — let the parent <a> handler manage these
			if($(this).closest('.facebook').length || $(this).closest('.instagram').length || $(this).closest('.youtube').length || $(this).closest('.twitter').length || $(this).closest('.pintrest').length){
				return true;
			}

			var unique__image_one = Math.floor(Math.random() * 1000) + 1;
			$(this).attr("id",unique__image_one);
			$('#imageIdTemp').val(unique__image_one);

			event.preventDefault();   // never let a click on an <img> inside an <a> navigate the preview
			event.stopPropagation();
			if($(this).attr('src').includes(baseUrl+"template")){
				$(".slct_img_sec").find(".currentimageid").hide();
			}
			var currentSrc =$(this).attr('src');
			if(iframe.find(".bg-selected").length){
				iframe.find(".bg-background").removeClass("bg-selected");
			}
			$(".slct_img_sec").find(".currentimage").attr("src",currentSrc);

			

			var alttext=$(this).attr("alt");
			var image_name = icCleanImageName(currentSrc);
			$(".palttext").val(alttext);
			$(".ptitle").val(image_name);

			$(".pimagename").text(image_name);

			$('.cu_imageSizediv').hide();
			$('#popup3').modal('show');
			loadMyImages(false);
			img_status = 0;
			var alttext=$(this).attr("alt");
			$(".alttext").val(alttext);
			var sizee=getFileSize(currentSrc);
			
			
			image_to_dimention('.recommended_size', currentSrc, 1);


			$("#recommendedSize").text('('+sizee+'KB)');
			
			console.log(unique__image_one);
          
        }); 
		
		//select block
		iframe.find(".spe_block").hover(function() {
			//alert(this);  
			$(this).css("border","1px solid red");
			$(this).prepend("<div style='background: rgba(0,0,0,0.60);margin-top: -9px;max-width: 50px;z-index: 1;border-radius: 5px;position:absolute;' data-toggle='tooltip' title='Remove' class='block-hover-menu'><span id='delete_block' style='font-size: 26px !important;color: white;cursor: pointer;padding: 4px;max-height: 34px;color:red;' class='fa fa-close block-hover-item'></span></div>");
			curr_id=$(this).attr("id");
		},function () {
			$(this).css("border","0px");
			iframe.find(".block-hover-menu").remove();
		});
		
		/*Meta tags*/
        // Head Code is one field now. Pre-fill it from the analyticsCode slot plus any
        // legacy retarget_analyticsCode content (both were injected before </head>), so
        // existing Google/FB head snippets are preserved and shown together.
        var _headAnalytics = $(ic_funnelDoc()).find(".analyticsCode").html();
        var _headRetarget  = $(ic_funnelDoc()).find(".retarget_analyticsCode").html();
        var _headCode = '';
        if(_headAnalytics && _headAnalytics != ''){ _headCode += atob(_headAnalytics); }
        if(_headRetarget && _headRetarget != ''){ _headCode += (_headCode ? "\n" : "") + atob(_headRetarget); }
        if(_headCode != ''){ $("#head_code").val(_headCode); }
		
		// NOTE: the Thank You Page control card was removed from the editor sidebar,
		// so these inputs (#redirectUrl, #redirectuserto, #submission_type,
		// #messageaftersuccess, #messageafterfailed) may not exist. Guard every
		// access — a missing element here must NOT throw, or it aborts window.onload
		// and the auto-populate animation never runs.
		var elRedirectUrl = document.getElementById('redirectUrl');
		var elRedirectUserto = document.getElementById('redirectuserto');
		var elSubmissionType = document.getElementById('submission_type');
		var elMsgSuccess = document.getElementById('messageaftersuccess');
		var elMsgFailed = document.getElementById('messageafterfailed');

		if(elRedirectUrl && iframe.find("#redirect_url").val()!='' ){
			elRedirectUrl.value=iframe.find("#redirect_url").val();
		}

		if(elRedirectUserto && iframe.find("#redirect_userto").val()!='' ){
			elRedirectUserto.value=iframe.find("#redirect_userto").val();
		}


		if(elSubmissionType && iframe.find("#submissiontype").val()!='' ){
			var s_type = iframe.find("#submissiontype").val();
			elSubmissionType.value = s_type;

			if(s_type == 'redirect_user'){
				$(".redirectedit").show();
				$(".displayedit").hide();
			}else{
				$(".displayedit").show();
				$(".redirectedit").hide();
			}

		}

		if(elRedirectUrl && $("#redirectUrl").val().trim() != '' && iframe.find("#redirect_url").val() == '')
		{
			iframe.find("#redirect_url").val($("#redirectUrl").val())
		}

		if(elRedirectUrl && $("#redirectUrl").val().trim()=='' ){
			//alert(4);
			$("#redirectUrl").attr("placeholder"," Please Create ThankYou Page First");
		}

		if(elMsgSuccess && iframe.find("#messageaftersuccess").val()!=''){
			elMsgSuccess.value=iframe.find("#messageaftersuccess").val();
		}

		if(elMsgFailed && iframe.find("#messageafterfailed").val()!=''){
			elMsgFailed.value=iframe.find("#messageafterfailed").val();
		}
		
		// if(iframe.find("#affiurl").val()!=''){
		// 	document.getElementById('redirectUrl').value=iframe.find("#affiurl").val();
		// }
		// Head Code write-back: store into analyticsCode and clear the legacy retarget
		// slot so the renderer (which concatenates both before </head>) can't double-inject.
		$("#head_code").keyup(function(){
			$(ic_funnelDoc()).find(".analyticsCode").html(btoa($(this).val()));
			$(ic_funnelDoc()).find(".retarget_analyticsCode").html('');
		});

		/* Body Code (end of <body>) — stored in the chatbotEmbedCode slot, which the
		   renderers echo just before </body>. */
        var body_code=$(ic_funnelDoc()).find(".chatbotEmbedCode").html();

        if(body_code && body_code !=''){
			$("#body_code").val(atob(body_code));
        }else{
			var mBodyCode = '<?= $chatbotEmbedCode ?>';
			if( mBodyCode != '' ){
				$(ic_funnelDoc()).find(".chatbotEmbedCode").html(mBodyCode);
				$("#body_code").val(atob(mBodyCode));
			}
		}

		$("#body_code").keyup(function(){
			$(ic_funnelDoc()).find(".chatbotEmbedCode").html(btoa($(this).val()));
		});
		
		/* seo tags */
		var title=iframe.find("title").text();
		console.log(title);
		if(title!='')
		{
		  $("#page_title").val(title);
		} 
		var metaDesc=iframe.find(".description").attr('content');
		if(metaDesc!='')
		{
		  $("#meta_desc").val(metaDesc);
		}
		var metaKey=iframe.find(".keywords").attr('content');
		if(metaKey!='')
		{
		  $("#meta_keywords").val(metaKey);
		}
		
		$("#meta_desc").keyup(function(){
			var content=$(this).val();
			iframe.find(".description").attr("content",content);
		});

		$("#meta_keywords").keyup(function(){
			var content=$(this).val();
			iframe.find(".keywords").attr("content",content);
		});



		<?php
		// Auto-populate runs ONCE per Bridge: only on a fresh Draft (status 0) whose
		// content has NOT yet been stamped by the post-populate draft-save. The hidden
		// `ic-autopopulated` marker (added by sites/save_draft) is that stamp, so after
		// a populate + silent draft-save a refresh shows the saved draft instead of
		// re-running the fill.
		$ic_already_populated = (isset($templateContent) && strpos((string) $templateContent, 'ic-autopopulated') !== false);
		if ($status == 0 && !$ic_already_populated) {
		?>
			start_animate();
		<?php
		} else {
		?>
			no_animate();
		<?php
		}
		?>
	}


});



	// Start of image upload gallery functions

		function getFileSize(imgsrc){
			var request = new XMLHttpRequest();
						request.open("HEAD",imgsrc, false);
						request.send(null);
					var headerText = request.getAllResponseHeaders();
					var re = /Content\-Length\s*:\s*(\d+)/i;
						re.exec(headerText);
					return parseInt(RegExp.$1)/1000;
		}

	// end of image upload gallery functions

	//start of autoresponder functions 

		function getActCampaignlist(appUrl,appKey,selected = null){
			$("#loaderlist").show();
			$('#mailerParameter3List')
			.find('option')
			.remove()
			.end();
			var ActData="appurl="+appUrl+"&appkey="+appKey;
			//alert(ActData);
			$.ajax({
				url:"<?php echo base_url();?>inc/functions.php",
				
				data:ActData,
				
				type : 'post',
				
				dataType: 'json',
				
				//cache:false,
				
				success: function(r){
					$("#loaderlist").hide();
					var hasLists = r && typeof r === 'object' && !Array.isArray(r) ? Object.keys(r).length > 0 : (Array.isArray(r) && r.length > 0);
					if (!hasLists) {
						$('#divParameter4').hide();
						$('#mailerParameter3List').hide();
						if (typeof Swal !== 'undefined') {
							Swal.fire({
								icon: 'info',
								title: 'No ActiveCampaign lists found',
								html: 'We connected to ActiveCampaign but no lists were returned.<br><br>' +
									  '<b>What to do next:</b><br>' +
									  '1. Log into your ActiveCampaign account.<br>' +
									  '2. Go to <b>Lists</b> (in the left sidebar under Contacts).<br>' +
									  '3. Click <b>Add a list</b>, name it (e.g. "Funnel Leads"), and save.<br>' +
									  '4. Come back here and reload the lists.<br><br>' +
									  'Also double-check your API URL (e.g. <code>https://youraccount.api-us1.com</code>) and API key in <b>Settings &rarr; Developer</b>.'
							});
						} else {
							icAlert('Please log into ActiveCampaign, go to Lists (under Contacts), click "Add a list", and create one (e.g. "Funnel Leads"). Then come back and reload.\n\nAlso verify your API URL and key under Settings > Developer.', { title: 'No ActiveCampaign lists found' });
						}
						return;
					}
					$('#divParameter4').show();
					$('#mailerParameter3List').show();
					$.each(r, function(key, value) {
						if(key == selected){
							$('#mailerParameter3List').append($("<option selected='selected'></option>").attr("value",key).text(value));
						}else{
							$('#mailerParameter3List').append($("<option></option>").attr("value",key).text(value));
						}
					});
				},
				error: function(){
					$("#loaderlist").hide();
					if (typeof Swal !== 'undefined') {
						Swal.fire({
							icon: 'error',
							title: 'Could not fetch ActiveCampaign lists',
							text: 'Please check your API URL and API key, then try again.'
						});
					} else {
						icAlert('Could not fetch ActiveCampaign lists. Please check your API URL and API key.', { danger: true });
					}
				},
				complete: function(){

				}
			});
		}

		function geticontactlist(appId,appUsername,appPassword,selected=null){
			$('#mailerParameter5List')
			.find('option')
			.remove()
			.end();
			var gdata="appid="+appId+"&appusername="+appUsername+"&appass="+appPassword;
			//alert(gdata);
			$.ajax({ 
				url:"<?php echo base_url();?>inc/functions.php",
							
				data:gdata,
							
				type : 'post',
							
				dataType: 'json',
							
				success: function(r){
					if (!r == ''){	
						$('#divParameter5').show();
						$('#mailerParameter5List').show();
						$.each(r, function(key, value) {  
							//alert(value);
							if(selected == key){
								$('#mailerParameter5List').append($("<option selected='selected'></option>").attr("value",key).text(value)); 
							}else{
								$('#mailerParameter5List').append($("<option></option>").attr("value",key).text(value)); 
							}
							
						});
					}				
				},  
				error: function(){  
									//alert(2);
				},
				complete: function(){
									
				}
							
			});
		}

		function getwebinarlists(organizerKey , Atoken , Rtoken , selected = null) {
			$("#loaderlist").show();
			$('#mailerParameter3List')
			.find('option')
			.remove()
			.end();
			
			var webData="organizerKey="+organizerKey+"&accessToken="+Atoken+"&refreshToken="+Rtoken+"&selected="+selected;
			//alert(gdata);
			$.ajax({ 
				url:"<?= base_url('setting/getWebinarlists') ?>",
							
				data:webData,
							
				type : 'post',
							
				dataType: 'json',
							
				success: function(r){
					//console.log(r);
					if (!r.html == ''){	
						$("#loaderlist").hide();
						$('#divParameter4').show();
						$('#mailerParameter3List').show();
						$('#mailerParameter3List').append(r.html); 
					}else{
						$("#loaderlist").hide();
						$("#errormsg div").html(r.error);
						$("#errormsg").show();
					}
						
				},  
				error: function(){  
									//alert(2);
				},
				complete: function(){
									
				}
							
			});
		}

		function getConvertKitlist(apikey,selected=null){
			$("#loaderlist").show();
			$('#mailerParameter3List')
			.find('option')
			.remove()
			.end();
			var gdata="convertkit_get_lists=yes&Capikey="+apikey+"&selected="+selected;
			//alert(gdata);
			$.ajax({
				url:"<?= base_url('inc/functions.php') ?>",

				data:gdata,

				type : 'post',

				dataType: 'json',

				success: function(r){
					//console.log(r);
					if (!r.html == ''){
						$("#loaderlist").hide();
						$('#divParameter4').show();
						$('#mailerParameter3List').show();
						$('#mailerParameter3List').append(r.html);
					}else{
						$("#loaderlist").hide();
						$("#errormsg div").html(r.error);
						$("#errormsg").show();
					}

				},
				error: function(){
									//alert(2);
				},
				complete: function(){

				}

			});
		}

		function getMailchimplist(apikey,selected=null){
			$("#loaderlist").show();
			$('#mailerParameter3List')
			.find('option')
			.remove()
			.end();
			var gdata="mailchimp_get_lists=yes&mapikey="+apikey+"&selected="+selected;
			//alert(gdata);
			$.ajax({ 
				url:"<?php echo base_url();?>inc/functions.php",
							
				data:gdata,
							
				type : 'post',
							
				dataType: 'json',
							
				success: function(r){
					//console.log(r);
					if (!r.html == ''){	
						$("#loaderlist").hide();
						$('#divParameter4').show();
						$('#mailerParameter3List').show();
						$('#mailerParameter3List').append(r.html); 
					}else{
						$("#loaderlist").hide();
						$("#errormsg div").html(r.error);
						$("#errormsg").show();
					}
						
				},  
				error: function(){  
									//alert(2);
				},
				complete: function(){
									
				}
							
			});
		}

		function getResponselist(apikey,selected=null){
			$("#loaderlist").show();
			$('#mailerParameter3List')
			.find('option')
			.remove()
			.end();
			var gdata="getresponse_get_lists=yes&gapikey="+apikey+"&selected="+selected;
			//alert(gdata);
			$.ajax({ 
				url:"<?php echo base_url();?>inc/functions.php",
							
				data:gdata,
							
				type : 'post',
							
				dataType: 'json',
							
				success: function(r){
					//console.log(r);
					if (!r.html == ''){	
						$("#loaderlist").hide();
						$('#divParameter4').show();
						$('#mailerParameter3List').show();
						$('#mailerParameter3List').append(r.html); 
					}else{
						$("#loaderlist").hide();
						$("#errormsg div").html(r.error);
						$("#errormsg").show();
					}
						
				},  
				error: function(){  
									//alert(2);
				},
				complete: function(){
									
				}
							
			});
		}

		function getAweberlist(Rtoken,Atoken,selected=null){
			$("#loaderlist").show();
			$('#mailerParameter3List')
			.find('option')
			.remove()
			.end();
			var gdata="refreshtoken="+Rtoken+"&accesstoken="+Atoken+"&selected="+selected;
			//alert(gdata);
			$.ajax({ 
				url:"<?php echo base_url('setting/getAweberLists');?>",
							
				data:gdata,
							
				type : 'post',
							
				dataType: 'json',
							
				success: function(r){
					//console.log(r);
					if (!r.html == ''){	
						$("#loaderlist").hide();
						$('#divParameter4').show();
						$('#mailerParameter3List').show();
						$('#mailerParameter3List').append(r.html); 
					}else{
						$("#loaderlist").hide();
						$("#errormsg div").html(r.error);
						$("#errormsg").show();
					}
						
				},  
				error: function(){  
									//alert(2);
				},
				complete: function(){
									
				}
							
			});
		}

		function hideMailerParameter(){
			$('#divParameter5').hide();
			$('#divParameter4').hide();
			$('#mailerParameter3List').hide();
			$('#mailerParameter5List').hide();
			$('#mailerParameter1').val('');
			$('#mailerParameter2').val('');
			$('#mailerParameter3').val('');
			$("#errormsg").html('');
			$("#errormsg").hide();
			$('#mailerParameter3List')
				.find('option')
				.remove()
				.end();
		}

	// end of autoresponder functions

	// start of editor control section functions 

		/* Social Media Start */
			function putfblink(){
				var iframe = $(ic_frameDocs());
				var btnlink = document.getElementById('fblink').value;
				iframe.find(".facebook").attr("href",btnlink);
				if(iframe.find("#facebook").length){
					iframe.find("#facebook").attr("href",btnlink);
				}
			}

			function puttwitlink(){
				var iframe = $(ic_frameDocs());
				var btnlink = document.getElementById('twitlink').value;
				iframe.find(".twitter").attr("href",btnlink);
				if(iframe.find("#twitter").length){
					iframe.find("#twitter").attr("href",btnlink);
				}
			}

			function putpintlink(){
				var iframe = $(ic_frameDocs());
				var btnlink = document.getElementById('pintlink').value;
				iframe.find(".pintrest").attr("href",btnlink);
				if(iframe.find("#pintrest").length){
					iframe.find("#pintrest").attr("href",btnlink);
				}
			}

			function putinstalink(){
				var iframe = $(ic_frameDocs());
				var btnlink = document.getElementById('instalink').value;
				iframe.find(".instagram").attr("href",btnlink);
			}

			function putyoutubelink(){
				var iframe = $(ic_frameDocs());
				var btnlink = document.getElementById('youtubelink').value;
				iframe.find(".youtube").attr("href",btnlink);
			}
		/* Social Media End */

		function handler_link (event){
				
			var text = $('.publish_link').val();
			//alert(text);
			event.clipboardData.setData('text/plain', text);

			event.preventDefault();

			document.removeEventListener('copy', handler, true);

		}

		// These are the Thank You Page card's input handlers. That card was removed
		// from the sidebar, so the inputs may not exist — guard each read so a stray
		// call can't throw.
		function putsubmissiontype(){
			var el = document.getElementById('submission_type'); if(!el) return;
			var submissiontype = el.value;
			$(ic_frameDocs()).find("#submissiontype").val(submissiontype);
			if(submissiontype == 'redirect_user'){
				$(".redirectedit").show();
				$(".displayedit").hide();

			}else{
				$(".displayedit").show();
				$(".redirectedit").hide();


			}
		}
		function putmessageaftersuccess(){
			var el = document.getElementById('messageaftersuccess'); if(!el) return;
			$(ic_frameDocs()).find("#messageaftersuccess").val(el.value);
		}

		function putmessageafterfailed(){
			var el = document.getElementById('messageafterfailed'); if(!el) return;
			$(ic_frameDocs()).find("#messageafterfailed").val(el.value);
		}

		function putredirectuserto(){
			var el = document.getElementById('redirectuserto'); if(!el) return;
			$(ic_frameDocs()).find("#redirect_userto").val(el.value);
		}

		function putredirectUrl(){
			var el = document.getElementById('redirectUrl'); if(!el) return;
			$(ic_frameDocs()).find("#redirect_url").val(el.value);
		}

		function resetthankyou(){
			var r_url = $('.resetthankyou').attr('data-url');
			$(ic_frameDocs()).find("#redirect_url").val(r_url);
			$("#redirectUrl").val(r_url);	
		}

		function putPageTitle(){
			var pageTitle = document.getElementById('page_title').value;
			$(ic_frameDocs()).find("title").text(pageTitle);
		}

		// function putbtntext(){
		// 	var iframe = $(ic_frameDocs());
		// 	var btntext = document.getElementById('btntext').value;
		// 	iframe.find(".actionbtn").text(btntext);
		// 	if(iframe.find("#orderbtn1").length){
		// 		iframe.find("#orderbtn1").text(btntext);
		// 	}
		// }

		// function putbtnlink(){
		// 	var iframe = $(ic_frameDocs());
		// 	var btnlink = document.getElementById('btnlink').value;
		// 	iframe.find(".actionbtn").attr("href",btnlink);
		// 	if(iframe.find("#orderbtn1").length){
		// 		iframe.find("#orderbtn1").attr("href",btnlink);
		// 	}
		// }
		// Resolve the submit-button LABEL element across template generations:
		//  • legacy: <… class="autoResponser">
		//  • v2 Bridge: <button class="cta"><span class="lbl alf-cta1">TEXT</span>…>
		//    (writing the button's own .text() would clobber the spinner/arrow spans,
		//     so target the .lbl span; fall back to the button itself if absent).
		function icFormBtnLabel(iframe){
			var $ar = iframe.find(".autoResponser");
			if ($ar.length) { return $ar; }
			var $btn = iframe.find("button.cta, form button[type='submit'], .alf-cta1").first();
			if (!$btn.length) { return $(); }
			var $lbl = $btn.hasClass('alf-cta1') ? $btn : $btn.find('.lbl, .alf-cta1').first();
			return $lbl.length ? $lbl : $btn;
		}
		function putformbtntext(){
			var iframe = $(ic_frameDocs());
			var btntext = document.getElementById('formbtntext').value;
			icFormBtnLabel(iframe).text(btntext);
		}
		// The v2 Bridge templates use a FLOATING-LABEL form: the field carries a blank
		// placeholder (` `) with `.inp::placeholder{color:transparent}`, and the visible
		// text the user reads is the sibling <label for="..."> — NOT the placeholder.
		// So the control writes the LABEL text (what's actually visible) and also sets
		// the placeholder attribute as a fallback for older templates whose fields show
		// a real placeholder. `setFormFieldText` handles both in one place.
		function setFormFieldText(field, value){
			var iframe = $(ic_frameDocs());
			// Visible floating label (v2 templates): match by class then id.
			var byClass = iframe.find("." + field);
			var input = byClass.length ? byClass : iframe.find("#" + field);
			input.each(function(){
				var $i = $(this);
				var id = $i.attr('id');
				var $lbl = id ? $i.closest('.fld,.form-group,div').find('label[for="' + id + '"]') : $();
				if (!$lbl.length) { $lbl = $i.siblings('label'); }
				if ($lbl.length) { $lbl.text(value); }   // floating-label templates
				$i.attr('placeholder', value);            // legacy templates (harmless on v2)
			});
		}
		// Read the visible field text back out (floating <label> first, else the
		// placeholder attribute) so the sidebar control shows the current value.
		function getFormFieldText(field){
			var iframe = $(ic_frameDocs());
			var byClass = iframe.find("." + field);
			var $i = (byClass.length ? byClass : iframe.find("#" + field)).first();
			if (!$i.length) { return ''; }
			var id = $i.attr('id');
			var $lbl = id ? $i.closest('.fld,.form-group,div').find('label[for="' + id + '"]') : $();
			if (!$lbl.length) { $lbl = $i.siblings('label'); }
			if ($lbl.length) { return $lbl.first().text().trim(); }
			var ph = ($i.attr('placeholder') || '').trim();
			return ph;
		}
		function putemailplaceholder(){ setFormFieldText('email', document.getElementById('emailplaceholder').value); }
		function putphoneplaceholder(){ setFormFieldText('phone', document.getElementById('phoneplaceholder').value); }
		function putnameplaceholder(){  setFormFieldText('name',  document.getElementById('nameplaceholder').value);  }
		function showVideoTr()
		{
			var videoType = document.getElementById('video_selector').value;
			// alert(videoType);
			if(videoType=='youtube')
			{
				document.getElementById("youtube_id_tr").style.display = "block";
				document.getElementById("vimeo_id_tr").style.display = "none";
			}	
			if(videoType=='vimeo')
			{
				document.getElementById("vimeo_id_tr").style.display = "block";
				document.getElementById("youtube_id_tr").style.display = "none";
			}			

		}		
				
				
		function changeVideolinkYt()
		{
			
			var videoLink = document.getElementById('video_link1').value;

			var embedcode = videoLink.replace('https://www.youtube.com/watch?v=','');
			
			//alert(embedcode);

			var video_link='http://www.youtube.com/embed/'+embedcode;
			//alert(video_link);
			var iframe = $(ic_frameDocs());
			//iframevideo2.find('#video iframe').attr('src',video_link);
			
			var iframehtml='<iframe width="100%" height="343" id="videoIframe" src="https://www.youtube.com/embed/'+embedcode+'"></iframe>';		
			iframe.find('#video').html(iframehtml);
			
		}
		function changeVideolinkVimeo()
		{
			
			//alert();
			var videoLink = document.getElementById('video_link2').value;

			var embedcode = videoLink.replace('https://vimeo.com/','');
			
			var video_link='https://player.vimeo.com/video/'+embedcode;
			//alert(video_link);
			var iframe = $(ic_frameDocs());	
			//iframevideo2.find('#video iframe').attr('src',video_link);	
			var iframehtml='<iframe width="100%" height="343" id="videoIframe" src="https://player.vimeo.com/video/'+embedcode+'"></iframe>';		
			iframe.find('#video').html(iframehtml);			
			
		}

	// (Removed: the dead "Link Editor" card + its aFormChange() handler — it was
	// never wired to a canvas click; link editing is done by Form/Button Controls.)
	// end of editor control section functions

	// Start of animate functions to create page using AI



		

		// Guard against saving a half-written page. While the AI auto-populate
		// typewriter is still filling the Bridge, the manual Save button must be
		// inert — a mid-fill click would persist an EMPTY or PARTIAL page. The flag
		// starts true for a fresh draft ($status==0, which triggers the animation)
		// and is cleared only once the fill finishes (close_progress / no_animate).
		window.icAutoPopulating = <?= ($status == 0 && empty($ic_already_populated)) ? 'true' : 'false' ?>;
		window.icSetPopulating = function(on){
			window.icAutoPopulating = !!on;
			try {
				var $b = $('.save_action, .updateThis');
				if (on) { $b.prop('disabled', true).css({ opacity: .5, cursor: 'not-allowed' }); }
				else    { $b.prop('disabled', false).css({ opacity: '', cursor: '' }); }
			} catch (_){}
		};
		// Apply the initial locked state once the buttons exist in the DOM.
		$(function(){ if (window.icAutoPopulating) { window.icSetPopulating(true); } });

		function no_animate(){

			window.icSetPopulating(false);
			$('.publishModal').attr('disabled', false);
			$('.progress_div').hide();
			console.log('no_animate');
		}

		// After the auto-populate fill completes, silently persist the filled page as a
		// DRAFT (status stays 0 — NOT published) via sites/save_draft, which also stamps a
		// hidden ic-autopopulated marker into the content. On the next load that marker makes
		// the editor skip the animation and show the saved draft. Fire-and-forget: if it
		// fails the page simply re-populates next time (no worse than before). The CSRF token
		// is added by the app's global ajax prefilter, same as the Save/publish POST.
		function icSaveDraftAfterPopulate(){
			try {
				var header = $(ic_frameDocs()).find('#funnelrh').html();
				var body = $(ic_frameDocs()).find('#funnelrb').html();
				var thankyou_header = $(ic_frameDocs()).find('#funnelrh1').html();
				var thankyou_body = $(ic_frameDocs()).find('#funnelrb1').html();
				var funnel_id = $('#funnel_id').val();
				if (!funnel_id || (!body && !header)) return;
				$.ajax({
					type: 'POST',
					url: '<?= site_url('save_draft') ?>',
					data: {
						funnel_id: funnel_id,
						header1: header,
						body1: body,
						thankyou_header: thankyou_header,
						thankyou_body: thankyou_body
					},
					success: function(){
						// Baseline the autosave engine to the just-saved state (no-op if absent).
						if (typeof window.resetAutosaveBaseline === 'function') window.resetAutosaveBaseline();
					}
				});
			} catch (e) { console.warn('draft save after populate failed', e); }
		}

		async function start_animate(){

			// console.log('start_animate');
			var tid = '<?= $count  ?>';
			// console.log('template id - '+tid);

			// Animate for the legacy templates (101–130) AND the Bridge templates
			// (201–207). Anything else has no jsondata fill, so skip the animation.
			var tidNum = parseInt(tid);
			var isLegacyTpl = (tidNum >= 101 && tidNum <= 130);
			var isBridgeTpl = (tidNum >= 201 && tidNum <= 210);
			if( !isLegacyTpl && !isBridgeTpl ){

				console.log('no_animate');
				no_animate();
				return false;
			}

			
			window.icSetPopulating(true);
			$('.publishModal').attr('disabled', true);
			$('.progress_div').show();
			$('.taskText').html("Setting up your page&hellip;");
			// toggle_popup(1);
			// await show_confetti();
			// return false;

			generate2();
			
		}

		function generate2() {

			var t_id = '<?= $count ?>';
			var funnel_id = "<?= $funnel_id ?>";

			$.ajax({
					type: "POST",
					url: "<?= base_url('app/page-content-data');?>",
					data: {
						template_id: t_id,
						funnel_id: funnel_id
					},
					success: function(data){
						
						// console.log(data);
						obj = JSON.parse(data);
						// console.log(obj);

						if( obj.status == 200 || obj.status == 201 ){
							
							$('.taskText').html("Writing your page content&hellip;");
							// Overlay stays CLEAR — the point of this phase is to watch the
							// content fill in live behind the little status card.

							(async () => {
								try {
									await parseData2(obj.data);
									await parseData(obj.data);
									console.log('generate2');
									// Fill complete — silently persist as a Draft (status stays 0,
									// not published) so a refresh won't re-run the auto-populate.
									icSaveDraftAfterPopulate();
								} catch (e) {
									console.warn('generate2 animation error', e);
								} finally {
									// ALWAYS clear the full-screen progress overlay so a
									// hidden-element/animation hiccup can never strand the
									// editor with its controls covered.
									close_progress();
								}
							})();
						}else{
							$('.taskText').html("Something went wrong. Please try again.");
							(async () => {
								await delay(2000);
								close_progress();
							})();
						}
					},
					error: async function(){
						$('.taskText').html("Something went wrong. Please try again.");
						await delay(2000);
						close_progress();
					}
			});

		}

		async function close_progress(){

			$('.taskText').html('Your page is ready.');
			$('.iconRotate').html('<i class="fa-solid fa-bolt"></i>');
			$('.iconRotate').removeClass('iconRotate');
			// $('.save_action').click();
			try { await show_confetti(); } catch (e) { /* confetti is cosmetic */ }
			$('.progress_div').hide();
			// toggle_popup(0);
			// $('.goup').show();
			console.log('start_animate2');
			$('.publishModal').attr('disabled', false);
			// Page is fully written — the manual Save button is safe to use again.
			window.icSetPopulating(false);
		}

		// Function to parse JSON data and update HTML elements
		function parseData(obj) {
			return new Promise(async (resolve, reject) => {
				try {
					if (Array.isArray(obj)) {
						for (const item of obj) {
							// console.log(item);
							await parseData(item); // Recursively parse array items
						}
					} else if (typeof obj === "object" && obj !== null) {
						// console.log(obj);

						if(obj['text'] && obj['class']){
							if(obj['type'] === 'link'){
								$('.'+obj['class'], ic_funnelDoc()[0]).attr('href', obj['text']);
							} else {
								await write2(obj['class'], obj['text']);
							}
						}else{
							for (const key of Object.keys(obj)) {
								if (typeof obj[key] === "object") {
									const temp_data = obj[key];
									if (temp_data['text'] && temp_data['class']) {
										if(temp_data['type'] === 'link'){
											$('.'+temp_data['class'], ic_funnelDoc()[0]).attr('href', temp_data['text']);
										} else {
											await write2(temp_data['class'], temp_data['text']);
										}
									} else {
										await parseData(temp_data);
									}
								}
							}
						}
					} else {
						console.log('Something went wrong');
						console.log(obj);
					}
					resolve(1);
				} catch (error) {
					resolve(0);
				}
			});
		}

		// This function is similar to parseData but it clears the content instead of typing it out
		function parseData2(obj) {
			return new Promise(async (resolve, reject) => {
				try {
					if (Array.isArray(obj)) {
						for (const item of obj) {
							// console.log(item);
							await parseData2(item); // Recursively parse array items
						}
					} else if (typeof obj === "object" && obj !== null) {
						// console.log(obj);

						if(obj['text'] && obj['class']){
							// Skip link types — don't clear their innerHTML (icons).
							// Clear INSIDE the funnel iframe (not the parent doc).
							if(obj['type'] !== 'link'){
								$('.'+obj['class'], ic_funnelDoc()[0]).html('');
							}
						}else{
							for (const key of Object.keys(obj)) {
								if (typeof obj[key] === "object") {
									const temp_data = obj[key];
									if (temp_data['text'] && temp_data['class']) {
										if(temp_data['type'] !== 'link'){
											$('.'+temp_data['class'], ic_funnelDoc()[0]).html('');
										}
									} else {
										await parseData2(temp_data);
									}
								}
							}
						}
					} else {
						console.log('Something went wrong');
						console.log(obj);
					}
					resolve(1);
				} catch (error) {
					resolve(0);
				}
			});
		}
		

		var scroll_length = 0;
		async function write2(ref, msg ) {

			return new Promise( async (resolve, reject) => {

				pointer = '.'+ref;
				// The template lives inside the isolated <iframe srcdoc> (#templateFrame1),
				// NOT the parent document — query the funnel frame's document so the
				// auto-populate animation actually finds the alf-* slots. A bare
				// $('.'+ref) here matches nothing (the editor-isolation refactor moved
				// the preview into an iframe), which is why generated copy never filled.
				var temp_this = $(pointer, ic_funnelDoc()[0]);

				// Only animate elements that are actually laid out (visible). A class
				// that lives in the hidden Thank-You pane (#templateEditor2) has no
				// offset — calling .offset().top on it throws and would strand the
				// progress overlay. write2() is still called for it (so the text IS
				// set by typed()), we just skip the scroll math.
				var hasLayout = temp_this && temp_this.length > 0 && temp_this.is(':visible') && temp_this.offset();

				if( temp_this && temp_this.length > 0 ){

					// Scroll the preview to the element ONLY when it's laid out
					// (visible). A class in the hidden Thank-You pane has no offset, so
					// we skip the scroll math but STILL type the text below.
					if( hasLayout ){
						// Two coordinate systems meet here. temp_this lives INSIDE the
						// <iframe srcdoc> preview, so .offset().top is relative to the
						// IFRAME's own document (the first slot is ≈0) — not to this page.
						// The scroller is #templateEditor in the PARENT document, and the
						// iframe is auto-sized to its full content (ic_resizeFrames), so it
						// never scrolls internally. Mixing the two produced a garbage target:
						// the preview jumped somewhere arbitrary and then every slot typed
						// off-screen. Translate properly = the element's offset inside the
						// iframe + where the iframe sits within the scroller's content.
						// A THIRD wrinkle: #templateEditor carries `zoom` (see its CSS), which
						// scales its contents. Offsets read INSIDE the iframe are in the frame's
						// own unzoomed CSS pixels, but scrollTop on the zoomed scroller is in
						// zoomed ones — so an element 1000px down the frame sits at 800 in the
						// scroller. Scale the in-frame offset by the zoom to convert. frameTop
						// is already measured in the parent (zoomed) space, so it is NOT scaled.
						var $frame   = $('#templateFrame1');
						var icZoom   = parseFloat($('#templateEditor').css('zoom')) || 1;
						var frameTop = $frame.length
							? ($frame.offset().top - $('#templateEditor').offset().top + $('#templateEditor').scrollTop())
							: 0;
						var scroll_temp = frameTop + (temp_this.offset().top * icZoom) - 150;
						// Monotonic-DOWN autopopulate: the preview must only ever move
						// DOWNWARD as copy fills in. The jsondata is ordered to match the
						// template's DOM top-to-bottom, so each slot is at/after the last one.
						// If a class is ever slightly out of order we still refuse to scroll
						// back up (a backward jump reads as jarring), so we keep the SIGNED
						// forward delta — a negative (upward) delta can't exceed the 80px gate.
						var diff = (scroll_temp - scroll_length);


						var scroll = true;
						var temp_length = temp_this.parents('.bakery-test-testimonial').length;
						if( temp_length > 0 ){
							var disp_prop = temp_this.parents('.bakery-test-testimonial').css('display');
							if( disp_prop == 'none'){
								scroll = false;
							}
						}

						if( diff > 80 && scroll ){
							// console.log(scroll_temp, scroll_length);
							scroll_length = scroll_temp;
							$('#templateEditor').animate({
								scrollTop:  scroll_temp
							}, 100);

						}
					}

					// console.log('scrolling to '+pointer);
					await delay(200)
					await typed(pointer, msg);
					resolve();
				}else{
					resolve();
				}
			});
		}


		async function typed(key,msg){

			// console.log(key+' | '+msg);
			// Resolve the slot INSIDE the funnel iframe document — a bare string
			// selector would resolve against the PARENT document and find nothing
			// (the preview is an isolated <iframe srcdoc>).
			var $el = $(key, ic_funnelDoc()[0]);
			if (!$el.length) { return; }

			if (msg == '') {
				$el.show();
				return;
			}

			msg = msg.replace('&','and');
			$el.html('');
			$el.show();

			// Custom word-at-a-time typewriter (replaces Typed.js). Two reasons we
			// own the loop instead of using a library:
			//   1) Tab-change resilience. Background tabs are throttled by the browser
			//      — Typed.js paces keystrokes with setTimeout, so switching tabs
			//      mid-fill froze the cascade until you came back. Here, when the tab
			//      is hidden we write the whole slot in ONE shot (no per-word delay),
			//      so a backgrounded fill still completes.
			//   2) Speed. We append a whole word per tick, not a character, so copy
			//      fills noticeably faster while keeping the typewriter feel.
			var el    = $el[0];
			var words = msg.split(' ');
			for (var i = 0; i < words.length; i++) {
				// If the tab is hidden, stop animating and dump the remainder at once,
				// then bail — nobody is watching and the browser would throttle us.
				if (document.hidden) {
					el.textContent = msg;
					break;
				}
				el.textContent += (i === 0 ? '' : ' ') + words[i];
				await delay(40);
			}
			// Guarantee the final text is exact (covers the hidden-mid-word case).
			el.textContent = msg;
		}

		async function delay(ms) {
			return new Promise(resolve => setTimeout(resolve, ms));
		}



		function show_confetti() {
			return new Promise(async (resolve) => {
				await triggerConfetti(20);
				await delay(500);
				await triggerConfetti(60);
				await delay(500);
				await triggerConfetti(100);
				await delay(500);
				await triggerConfetti(140);
				await delay(500);
				await triggerConfetti(180);
				await delay(500);
				await triggerConfetti(240);
				await delay(500);
				await triggerConfetti(280);
				await delay(500);
				await triggerConfetti(320);
				resolve(); // Resolve the promise when all confetti triggers are done
			});
		}


		function triggerConfetti(xx = 0) {

			var offset = $('.progress_div').offset();
			Top = offset.top;
			Left = offset.left;

			// console.log(Left+'-|-'+Top)

			var clickEvent = new MouseEvent("click", {
				bubbles: true,
				cancelable: true,
				clientX: Left+xx,
				clientY: Top,
				view: window
			});

			sparks.dispatchEvent(clickEvent);
		}

		<?php  if ($status == 0) { ?>
		
			var sparks = document.querySelector(".spark");
			party.settings.debug = false;

			sparks.addEventListener("click", function (e) {
				// console.log(e);
				e.preventDefault();
				party.confetti(e);
			});
			sparks.addEventListener("contextmenu", function (e) {
				e.preventDefault();
				party.sparkles(e);
			});
		<?php  } ?>
		
	// End of animate functions to create page using AI


	// ─────────────────────────────────────────────────────────────────
	// CUSTOMIZATION SIDEBAR — Brand Colors / Typography / Image Size /
	// Section Visibility / Social Icon Toggles.
	//
	// All edits write into the page DOM directly. Persistence rides on the
	// existing funnels.content blob save (templateEditor1 / templateEditor2)
	// — the override style block lives inside <funnelrh>, the inline image
	// width / social display:none lives on the elements themselves.
	// ─────────────────────────────────────────────────────────────────
	$(function(){
		// Single source of truth for typography customization. Persisted as
		// data-* attributes on the override <style> block so a reload can
		// hydrate the picker.
		window.aaUserOverrides = {
			fontHeading: '',
			fontBody:    '',
			// Color scheme (v2 templates). Empty = use the template's own palette.
			colBrand:  '',
			colAccent: '',
			colInk:    '',
			colPage:   ''
		};

		// Selector lists — pattern-based attribute selectors so the override
		// works for every one of the ~28 landing-page templates without us
		// having to enumerate every "<niche>-form-submit" class.
		// Heading font targets — bare h1-h6 + every *-title / *-heading /
		// *-headline / *-head-text class pattern used across the 28
		// landing-page templates. Plus common descendant cases (h1-h3 nested
		// in *-content / *-hero / *-section / *-card / *-form-container).
		// All entries get scoped to #funnelrb / #funnelrb1 by aaScopeSel
		// before they hit the page, so they only match inside the funnel
		// preview and the thank-you preview — never the editor chrome.
		// Runs AFTER body font so shared classes like [class*="-title"]
		// resolve to heading font.
		var HEADING_SEL = [
			'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
			'.heading', '.display-1', '.display-2', '.display-3', '.display-4',
			'[class*="-title"]', '[class*="-heading"]', '[class*="-headline"]',
			'[class*="-head-text"]',
			'[class*="-hero"] h1', '[class*="-hero"] h2', '[class*="-hero"] h3',
			'[class*="-content"] h1', '[class*="-content"] h2', '[class*="-content"] h3',
			'[class*="-section"] h1', '[class*="-section"] h2', '[class*="-section"] h3',
			'[class*="-card"] h1', '[class*="-card"] h2', '[class*="-card"] h3',
			'[class*="-form-container"] h1', '[class*="-form-container"] h2',
			'[class*="-form-container"] h3', '[class*="-form-container"] h4'
		].join(', ');
		// Body font targets — bare elements + every body-text class pattern
		// used across the 28 templates: *-description, *-paragraph, *-desc,
		// *-text, *-content, *-info, *-caption, *-detail, *-copy, *-subtitle,
		// plus inputs/buttons inside *-form-container shells. All entries
		// get scoped to #funnelrb / #funnelrb1 by aaScopeSel — only the
		// funnel preview / thank-you preview, never the editor chrome.
		var BODY_FONT_SEL = [
			'body', 'p', 'span', 'label', 'input', 'button', 'select', 'textarea', 'li', 'a',
			'[class*="-description"]', '[class*="-paragraph"]', '[class*="-desc"]',
			'[class*="-subtitle"]',
			'[class*="-text"]', '[class*="-content"]', '[class*="-info"]',
			'[class*="-caption"]', '[class*="-detail"]', '[class*="-copy"]',
			'[class*="-feature-content"]',
			'[class*="-form-container"] input', '[class*="-form-container"] button',
			'[class*="-form-container"] select', '[class*="-form-container"] textarea',
			'[class*="-form-container"] label', '[class*="-form-container"] p'
		].join(', ');

		// Prefix every comma-separated selector in `sel` with `scope` so the
		// generated rule only matches descendants of the funnel preview, never
		// the editor chrome. Empty fragments are dropped — guards against
		// trailing commas in the source selector list.
		function aaScopeSel(sel, scope) {
			return sel.split(',').map(function(s){
				var t = s.trim();
				return t ? (scope + ' ' + t) : t;
			}).filter(Boolean).join(', ');
		}

		function aaBuildOverrideCss() {
			var s = window.aaUserOverrides;
			var r = [];
			// Funnel page + thank-you page wrappers. Every rule below is
			// duplicated under all scopes so the customization applies in BOTH
			// contexts:
			//  - In the EDITOR, the content is wrapped in #funnelrb / #funnelrb1.
			//  - On the PUBLIC page, viewpage.php strips <funnelrb id="funnelrb">
			//    and turns it into <body> — so #funnelrb no longer exists there.
			// Adding `body` makes the override match the published page too. Each
			// preview is an isolated <iframe>, so a `body`-scoped rule can't leak
			// into the editor chrome.
			var SCOPES = ['#funnelrb', '#funnelrb1', 'body'];
			function pushScoped(sel, body) {
				SCOPES.forEach(function(scope){
					r.push(aaScopeSel(sel, scope) + ' { ' + body + ' }');
				});
			}

			// Google-Fonts @import for the chosen face(s), FIRST in the sheet
			// (CSS requires @import before any other rule). This is what makes the
			// font load on the PUBLIC page: the override <style> is saved inside the
			// content, but the editor's <link> in the iframe head is NOT saved — so
			// the font-face must travel with the override CSS itself.
			var imports = [];
			[s.fontHeading, s.fontBody].forEach(function(fam){
				if (fam && imports.indexOf(fam) === -1) imports.push(fam);
			});
			imports.forEach(function(fam){
				var q = encodeURIComponent(fam).replace(/%20/g, '+');
				r.push("@import url('https://fonts.googleapis.com/css2?family=" + q + ":wght@400;500;600;700&display=swap');");
			});

			// Fonts — body first, heading after so headings win for h1-h6
			// and *-title/heading classes that appear in both selector lists.
			if (s.fontBody)    pushScoped(BODY_FONT_SEL, 'font-family:"' + s.fontBody + '", sans-serif !important;');
			if (s.fontHeading) pushScoped(HEADING_SEL,   'font-family:"' + s.fontHeading + '", sans-serif !important;');

			// ── Color scheme — override the template's :root palette ──────────
			// Each custom property is marked !important so it beats the template's
			// OWN :root{} block (which sits later in document order, inside
			// #funnelrb) regardless of source order — !important is valid and
			// load-bearing on custom-property declarations. --brand-strong and
			// --surface-soft auto-derive via color-mix so the user only picks a
			// few roles. Accent is "shotgun"-set across every per-template accent
			// var name (--coral/--teal/--mint/…) so one control recolors the
			// accent on ALL v2 templates without editing the template files.
			var rootDecls = [];
			if (s.colBrand) {
				rootDecls.push('--brand:' + s.colBrand + ' !important');
				rootDecls.push('--brand-strong:color-mix(in srgb, ' + s.colBrand + ' 82%, #000) !important');
			}
			if (s.colInk) rootDecls.push('--ink:' + s.colInk + ' !important');
			if (s.colPage) {
				rootDecls.push('--page:' + s.colPage + ' !important');
				var mixBrand = s.colBrand || 'var(--brand)';
				rootDecls.push('--surface-soft:color-mix(in srgb, ' + mixBrand + ' 8%, ' + s.colPage + ') !important');
			}
			if (s.colAccent) {
				['--accent','--coral','--teal','--mint','--sand','--yellow','--lime','--cyan','--violet'].forEach(function(v){
					rootDecls.push(v + ':' + s.colAccent + ' !important');
				});
			}
			if (rootDecls.length) r.push(':root { ' + rootDecls.join('; ') + '; }');

			return r.join('\n');
		}

		function aaEnsureStyleBlock(scopeId) {
			// scopeId is '#funnelrh' / '#funnelrh1' — these live INSIDE the preview
			// iframes now, so search the frame documents, not the parent. Returns a
			// jQuery set over the matching <style> in whichever frame holds it.
			var $head = $(ic_frameDocs()).find(scopeId);
			if (!$head.length) return null;
			var $style = $head.find('#aa-user-overrides');
			if (!$style.length) {
				// Build the <style> in the frame's own document so it belongs to it.
				var doc = $head[0].ownerDocument || document;
				$style = $(doc.createElement('style')).attr('id', 'aa-user-overrides');
				$head.append($style);
			}
			return $style;
		}

		function aaApplyOverrides() {
			var css = aaBuildOverrideCss();
			['#funnelrh', '#funnelrh1'].forEach(function(scope) {
				var $style = aaEnsureStyleBlock(scope);
				if (!$style) return;
				$style.html(css);
				var s = window.aaUserOverrides;
				$style.attr('data-aa-font-heading', s.fontHeading)
					  .attr('data-aa-font-body', s.fontBody)
					  .attr('data-aa-col-brand',  s.colBrand  || '')
					  .attr('data-aa-col-accent', s.colAccent || '')
					  .attr('data-aa-col-ink',    s.colInk    || '')
					  .attr('data-aa-col-page',   s.colPage   || '');
			});
			// Belt-and-braces autosave hook. The MutationObserver elsewhere
			// also catches childList edits to the style block, but its
			// attributeFilter excludes data-aa-* attrs — so a "no css
			// difference, only data-attr difference" change wouldn't trigger
			// it. Calling scheduleAutosave directly here makes typography /
			// brand-colour picks reliably persist via autosave. No-ops when
			// autosave is off or the page hasn't been published yet.
			if (typeof window.scheduleAutosave === 'function') {
				window.scheduleAutosave('aa-customization');
			}
		}

		

		function aaHydrateFromOverrides() {
			// Read the saved override block (if any) for this funnel. #funnelrh lives
			// inside the funnel preview iframe now, so look in the frame documents.
			var $style = $(ic_frameDocs()).find('#funnelrh #aa-user-overrides');
			if (!$style.length) return;
			var s = window.aaUserOverrides;
			s.fontHeading = $style.attr('data-aa-font-heading') || '';
			s.fontBody    = $style.attr('data-aa-font-body')    || '';
			s.colBrand    = $style.attr('data-aa-col-brand')    || '';
			s.colAccent   = $style.attr('data-aa-col-accent')   || '';
			s.colInk      = $style.attr('data-aa-col-ink')      || '';
			s.colPage     = $style.attr('data-aa-col-page')     || '';
			if (typeof aaSchemeSyncUI === 'function') aaSchemeSyncUI();
			if (s.fontHeading || s.fontBody) {
				// Saved override references a font from the catalog. The
				// catalog is normally lazy-loaded on first Typography open,
				// but if the user already has a font picked we need the
				// @font-face declarations available IMMEDIATELY so the
				// preview renders in the chosen face on first paint instead
				// of falling back to system sans until the panel is opened.
				if (typeof aaLoadFontCatalog === 'function') aaLoadFontCatalog();
				if (typeof aaRenderFontGrid === 'function') aaRenderFontGrid();
			}

			// Re-emit the override block using current code paths. Saved blocks
			// from earlier sessions may have been written before we scoped every
			// selector to #funnelrb / #funnelrb1, which means a stale `button`
			// rule could leak into the editor sidebar (turning the font cards,
			// chips, and tabs unreadable). Rewriting from the in-memory state
			// guarantees only the current — scoped — selector list is in play.
			aaApplyOverrides();
		}
		// Defer ~200ms so the template HTML is in the DOM and the heavy
		// landing-pages/css/style.css has fully cascaded — the 50ms we used
		// before was sometimes too short, returning rgb(0,0,0) for elements
		// whose colours hadn't been computed yet.
		setTimeout(aaHydrateFromOverrides, 200);

		

		// ── Typography — rich font picker (40+ Google Fonts, lazy loaded) ──
		// Each font's category drives the chip filters; weights load on demand
		// when the Typography accordion is first opened.
		var AA_FONTS = [
			// Modern sans-serif
			{ name: 'Inter', cat: 'sans' },
			{ name: 'DM Sans', cat: 'sans' },
			{ name: 'Outfit', cat: 'sans' },
			{ name: 'Poppins', cat: 'sans' },
			{ name: 'Manrope', cat: 'sans' },
			{ name: 'Plus Jakarta Sans', cat: 'sans' },
			{ name: 'Space Grotesk', cat: 'sans' },
			{ name: 'Mulish', cat: 'sans' },
			{ name: 'Nunito', cat: 'sans' },
			{ name: 'Sora', cat: 'sans' },
			{ name: 'Public Sans', cat: 'sans' },
			{ name: 'Montserrat', cat: 'sans' },
			{ name: 'Raleway', cat: 'sans' },
			{ name: 'Work Sans', cat: 'sans' },
			{ name: 'Source Sans 3', cat: 'sans' },
			// Display / decorative
			{ name: 'Playfair Display', cat: 'display' },
			{ name: 'Fraunces', cat: 'display' },
			{ name: 'Bodoni Moda', cat: 'display' },
			{ name: 'Cinzel', cat: 'display' },
			{ name: 'Marcellus', cat: 'display' },
			{ name: 'Italiana', cat: 'display' },
			{ name: 'Abril Fatface', cat: 'display' },
			{ name: 'DM Serif Display', cat: 'display' },
			// Body serif
			{ name: 'Cormorant Garamond', cat: 'serif' },
			{ name: 'Abhaya Libre', cat: 'serif' },
			{ name: 'Crimson Pro', cat: 'serif' },
			{ name: 'EB Garamond', cat: 'serif' },
			{ name: 'Lora', cat: 'serif' },
			{ name: 'Merriweather', cat: 'serif' },
			{ name: 'PT Serif', cat: 'serif' },
			{ name: 'Source Serif 4', cat: 'serif' },
			{ name: 'Newsreader', cat: 'serif' },
			{ name: 'Libre Baskerville', cat: 'serif' },
			{ name: 'Spectral', cat: 'serif' },
			// Mono
			{ name: 'JetBrains Mono', cat: 'mono' },
			{ name: 'IBM Plex Mono', cat: 'mono' },
			{ name: 'Fira Code', cat: 'mono' },
			{ name: 'Space Mono', cat: 'mono' },
			{ name: 'Source Code Pro', cat: 'mono' },
			// Script / handwriting
			{ name: 'Caveat', cat: 'hand' },
			{ name: 'Pacifico', cat: 'hand' },
			{ name: 'Dancing Script', cat: 'hand' },
			{ name: 'Permanent Marker', cat: 'hand' }
		];

		var aaTypoState = { target: 'heading', cat: 'all', search: '', loaded: false };

		// One-shot Google Fonts CSS injection — fires when Typography opens.
		function aaLoadFontCatalog() {
			if (aaTypoState.loaded) return;
			aaTypoState.loaded = true;
			var families = AA_FONTS.map(function(f){
				return 'family=' + encodeURIComponent(f.name).replace(/%20/g, '+') + ':wght@400;500;700';
			}).join('&');
			var href = 'https://fonts.googleapis.com/css2?' + families + '&display=swap';
			// Load the @font-face catalog into:
			//  1. the PARENT head — so the Typography panel's own "Aa" card previews
			//     (which live in the editor chrome) render in the real typeface, and
			//  2. EACH preview iframe's head — so a font applied to #funnelrb/#funnelrb1
			//     inside the frame actually has its font files available there.
			function addLink(doc){
				if (!doc || !doc.head) return;
				if (doc.querySelector('link[data-aa-fonts="1"]')) return;
				var link = doc.createElement('link');
				link.rel = 'stylesheet'; link.href = href; link.setAttribute('data-aa-fonts', '1');
				doc.head.appendChild(link);
			}
			addLink(document);
			ic_frameDocs().forEach(addLink);
		}

		function aaCurrentFont() {
			return aaTypoState.target === 'heading'
				? window.aaUserOverrides.fontHeading
				: window.aaUserOverrides.fontBody;
		}

		function aaRenderFontGrid() {
			var $grid = $('#aaTypoGrid');
			if (!$grid.length) return;
			var current = aaCurrentFont();
			var search = aaTypoState.search.toLowerCase().trim();
			var cat = aaTypoState.cat;
			var html = '';
			AA_FONTS.forEach(function(f){
				if (cat !== 'all' && f.cat !== cat) return;
				if (search && f.name.toLowerCase().indexOf(search) === -1) return;
				var sel = current === f.name ? ' selected' : '';
				var safeName = f.name.replace(/'/g, "\\'");
				html += '<button type="button" class="aa-typo-card' + sel + '" data-font="' + f.name + '">' +
						'<span class="aa-typo-card-preview" style="font-family:\'' + safeName + '\',sans-serif">Aa</span>' +
						'<span class="aa-typo-card-name">' + f.name + '</span>' +
						'</button>';
			});
			if (!html) html = '<div class="aa-typo-empty">No fonts match</div>';
			$grid.html(html);
			$('#aaTypoCurrentName').text(current || 'Template Default');
		}

		// Lazy-load catalog the first time Typography card is touched.
		$(document).on('click', '#typographycontrols .card-header, #typographyopt', function(){
			aaLoadFontCatalog();
			if (!aaTypoState._rendered) { aaTypoState._rendered = true; aaRenderFontGrid(); }
		});

		// Heading vs Body switch
		$(document).on('click', '#typographyopt .aa-typo-tab', function(){
			$(this).siblings().removeClass('active');
			$(this).addClass('active');
			aaTypoState.target = $(this).data('typo-target');
			aaRenderFontGrid();
		});

		// Category chip filter
		$(document).on('click', '#typographyopt .aa-typo-cat', function(){
			$(this).siblings().removeClass('active');
			$(this).addClass('active');
			aaTypoState.cat = $(this).data('cat');
			aaRenderFontGrid();
		});

		// Search filter
		$(document).on('input', '#aaTypoSearch', function(){
			aaTypoState.search = $(this).val();
			aaRenderFontGrid();
		});

		// Font-card pick
		$(document).on('click', '#aaTypoGrid .aa-typo-card', function(){
			var fontName = $(this).data('font');
			if (aaTypoState.target === 'heading') {
				window.aaUserOverrides.fontHeading = fontName;
			} else {
				window.aaUserOverrides.fontBody = fontName;
			}
			aaApplyOverrides();
			aaRenderFontGrid();
		});

		$('#aaResetFonts').on('click', function() {
			if (aaTypoState.target === 'heading') {
				window.aaUserOverrides.fontHeading = '';
			} else {
				window.aaUserOverrides.fontBody = '';
			}
			aaApplyOverrides();
			aaRenderFontGrid();
		});

		// Explicit Save — direct POST to submitAction. Used when the user
		// wants a definite "this is saved now" confirmation without waiting
		// for autosave debounce. Bypasses every guard (publish gate, diff
		// check, in-flight check) via window.forceSaveNow.
		$('#aaSaveFonts').on('click', function() {
			var $btn = $(this);
			var $status = $('#aaTypoSaveStatus');
			if ($btn.hasClass('is-saving')) return;
			$btn.addClass('is-saving');
			$status.removeClass('is-success is-error').text('Saving…');

			// Make absolutely sure the latest state is in the DOM before save.
			aaApplyOverrides();

			// Build payload inline so we don't depend on the autosave's
			// internal buildPayload (which is closure-scoped and not exposed).
			var payload = {
				body1:           $(ic_frameDocs()).find('#funnelrb').html(),
				header1:         $(ic_frameDocs()).find('#funnelrh').html(),
				thankyou_header: $(ic_frameDocs()).find('#funnelrh1').html(),
				thankyou_body:   $(ic_frameDocs()).find('#funnelrb1').html(),
				thankyou_template_id: '<?= isset($thankyou_template_id) ? $thankyou_template_id : "" ?>',
				analyticsCode:         $(ic_funnelDoc()).find('.analyticsCode').text(),
				retarget_analyticsCode:$(ic_funnelDoc()).find('.retarget_analyticsCode').text(),
				chatbotEmbedCode:      $(ic_funnelDoc()).find('.chatbotEmbedCode').text(),
				funnel_id:   $('#funnel_id').val(),
				template_id: $('#template_id').val(),
				slug:        $('.slug-input').val() || ''
			};
			$.ajax({
				type: 'POST',
				url: '<?= site_url('submitAction') ?>',
				data: payload,
				success: function(data) {
					var ok = false;
					try { var r = JSON.parse(data); ok = (r && (r.status == 200 || r.status === '200')); } catch(e) {}
					if (ok) {
						$status.addClass('is-success').text('Saved');
						if (typeof window.resetAutosaveBaseline === 'function') window.resetAutosaveBaseline();
					} else {
						$status.addClass('is-error').text('Save failed');
					}
					setTimeout(function(){ $status.removeClass('is-success is-error').text(''); }, 3000);
				},
				error: function() {
					$status.addClass('is-error').text('Save failed — check connection');
					setTimeout(function(){ $status.removeClass('is-error').text(''); }, 3000);
				},
				complete: function() {
					$btn.removeClass('is-saving');
				}
			});
		});

		// ── Color scheme — recolor the :root palette (v2 templates) ──────
		// Extends the SAME aaUserOverrides / aaApplyOverrides pipeline as
		// Typography. Presets or the custom pickers write colBrand/colAccent/
		// colInk/colPage into aaUserOverrides; aaBuildOverrideCss emits a
		// !important :root{} block that beats the template's own palette (which
		// sits later in the document). Persists via the existing aa-customization
		// autosave. Reset clears the roles — lossless, since the template's own
		// :root is never modified, only layered over.
		var AA_SCHEME_PRESETS = [
			{ name:'Emerald', brand:'#047857', accent:'#34d399', ink:'#14251b', page:'#f6fbf8' },
			{ name:'Royal',   brand:'#6d28d9', accent:'#a78bfa', ink:'#1e1b2e', page:'#f8f6ff' },
			{ name:'Ocean',   brand:'#0369a1', accent:'#38bdf8', ink:'#0c1e2b', page:'#f2f9fd' },
			{ name:'Coral',   brand:'#e11d48', accent:'#fb7185', ink:'#2a1418', page:'#fff5f6' },
			{ name:'Amber',   brand:'#b45309', accent:'#f59e0b', ink:'#2a1e0e', page:'#fffaf0' },
			{ name:'Slate',   brand:'#334155', accent:'#64748b', ink:'#0f172a', page:'#f8fafc' },
			{ name:'Teal',    brand:'#0f766e', accent:'#2dd4bf', ink:'#0e2a27', page:'#f0fbf9' },
			{ name:'Plum',    brand:'#a21caf', accent:'#e879f9', ink:'#2a132b', page:'#fdf4ff' }
		];

		// Normalise any CSS colour (rgb()/#rgb/#rrggbb) into #rrggbb for the
		// native colour input. Returns '' on a value it can't parse.
		function aaSchemeHex(v){
			if (!v) return '';
			v = ('' + v).trim();
			if (v.charAt(0) === '#') {
				if (v.length === 4) return '#' + v[1]+v[1]+v[2]+v[2]+v[3]+v[3];
				if (v.length === 7) return v.toLowerCase();
				return '';
			}
			var m = v.match(/rgba?\(\s*(\d+)\s*,\s*(\d+)\s*,\s*(\d+)/i);
			if (!m) return '';
			function h(n){ var s = (+n).toString(16); return s.length === 1 ? '0'+s : s; }
			return '#' + h(m[1]) + h(m[2]) + h(m[3]);
		}

		// Read a custom property off the FUNNEL preview's :root, trying a list of
		// names (the accent var name varies per template). '' if none resolve.
		function aaSchemeReadVar(names){
			try {
				var f = document.getElementById('templateFrame1');
				if (!f || !f.contentDocument || !f.contentWindow) return '';
				var cs = f.contentWindow.getComputedStyle(f.contentDocument.documentElement);
				for (var i = 0; i < names.length; i++) {
					var val = (cs.getPropertyValue(names[i]) || '').trim();
					if (val) return val;
				}
			} catch (e) {}
			return '';
		}

		// Sync picker inputs + the "Currently" label + selected preset from state.
		// Exposed so aaHydrateFromOverrides() (defined earlier) can call it on load.
		window.aaSchemeSyncUI = function(){
			var s = window.aaUserOverrides;
			var map = {
				brand:  s.colBrand  || aaSchemeHex(aaSchemeReadVar(['--brand'])),
				accent: s.colAccent || aaSchemeHex(aaSchemeReadVar(['--accent','--coral','--teal','--mint','--sand','--yellow','--lime','--cyan','--violet'])),
				ink:    s.colInk    || aaSchemeHex(aaSchemeReadVar(['--ink'])),
				page:   s.colPage   || aaSchemeHex(aaSchemeReadVar(['--page']))
			};
			$('#colorschemeopt .aa-scheme-input').each(function(){
				var v = map[$(this).data('role')];
				if (v) this.value = v;
			});
			// Reflect the resolved colour of each role as a hex label in its tile.
			$('#colorschemeopt .aa-scheme-role-hex').each(function(){
				var v = map[$(this).data('hex')];
				$(this).text(v ? v.toUpperCase() : '—');
			});
			var any = s.colBrand || s.colAccent || s.colInk || s.colPage;
			var label = 'Template Default', matchIdx = -1;
			if (any) {
				label = 'Custom';
				for (var i = 0; i < AA_SCHEME_PRESETS.length; i++) {
					var p = AA_SCHEME_PRESETS[i];
					if ((s.colBrand||'').toLowerCase() === p.brand && (s.colAccent||'').toLowerCase() === p.accent &&
						(s.colInk||'').toLowerCase() === p.ink && (s.colPage||'').toLowerCase() === p.page) { label = p.name; matchIdx = i; break; }
				}
			}
			$('#aaSchemeCurrentName').text(label);
			$('#aaSchemePresets .aa-scheme-swatch').each(function(idx){ $(this).toggleClass('selected', idx === matchIdx); });
		};

		// Build the preset swatch grid once (each shows brand/accent/page bars).
		(function aaRenderSchemePresets(){
			var $wrap = $('#aaSchemePresets');
			if (!$wrap.length) return;
			var html = '';
			AA_SCHEME_PRESETS.forEach(function(p, i){
				html += '<button type="button" class="aa-scheme-swatch" data-preset="' + i + '" title="' + p.name + '">' +
						'<span class="aa-scheme-swatch-bars">' +
							'<i style="background:' + p.brand + '"></i>' +
							'<i style="background:' + p.accent + '"></i>' +
							'<i style="background:' + p.page + '"></i>' +
						'</span><span class="aa-scheme-swatch-name">' + p.name + '</span></button>';
			});
			$wrap.html(html);
		})();

		// Preset click → set all four roles, apply, sync.
		$(document).on('click', '#aaSchemePresets .aa-scheme-swatch', function(){
			var p = AA_SCHEME_PRESETS[+$(this).data('preset')];
			if (!p) return;
			var s = window.aaUserOverrides;
			s.colBrand = p.brand; s.colAccent = p.accent; s.colInk = p.ink; s.colPage = p.page;
			aaApplyOverrides();
			window.aaSchemeSyncUI();
		});

		// Custom picker change → set that one role, apply, sync.
		$(document).on('input change', '#colorschemeopt .aa-scheme-input', function(){
			var role = $(this).data('role'), v = this.value, s = window.aaUserOverrides;
			if (role === 'brand')  s.colBrand  = v;
			if (role === 'accent') s.colAccent = v;
			if (role === 'ink')    s.colInk    = v;
			if (role === 'page')   s.colPage   = v;
			aaApplyOverrides();
			window.aaSchemeSyncUI();
		});

		// Reset → clear the four roles; the template's own palette shows through.
		$('#aaResetScheme').on('click', function(){
			var s = window.aaUserOverrides;
			s.colBrand = ''; s.colAccent = ''; s.colInk = ''; s.colPage = '';
			aaApplyOverrides();
			window.aaSchemeSyncUI();
		});

		// "Match my source website" → read the palette from the URL this Bridge was
		// built from (server: OpenAI primary, Gemini fallback) and autofill the roles.
		// Same aaUserOverrides pipeline as the presets/pickers, so it applies live +
		// autosaves; Reset still reverts to the template default.
		$('#aaSchemeFetch').on('click', function(){
			var $btn = $(this), $hint = $('#aaSchemeFetchHint');
			if ($btn.prop('disabled')) return;
			var origHtml = $btn.html();
			$btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin mr-2"></i>Reading your site…');
			$hint.removeClass('is-success is-error').text('Analysing your source page with AI — this can take a few seconds…');
			var data = { funnel_id: $('#funnel_id').val() };
			data['<?= $this->security->get_csrf_token_name() ?>'] = '<?= $this->security->get_csrf_hash() ?>';
			$.ajax({
				type: 'POST', url: '<?= site_url('app/extract-colors') ?>', data: data, timeout: 90000,
				success: function(r){
					// Robust parse: the endpoint may arrive as a JSON string (content-type
					// edge cases) rather than a pre-parsed object.
					if (typeof r === 'string') { try { r = JSON.parse(r); } catch (e) { r = null; } }
					if (window.console) console.log('[aa palette] response', r);
					if (r && r.ok && r.colors) {
						var c = r.colors, s = window.aaUserOverrides, n = 0;
						if (c.brand)  { s.colBrand  = c.brand;  n++; }
						if (c.accent) { s.colAccent = c.accent; n++; }
						if (c.ink)    { s.colInk    = c.ink;    n++; }
						if (c.page)   { s.colPage   = c.page;   n++; }
						// Recolor the preview (own try/catch so a hiccup here can't
						// block the swatch fill below).
						try { aaApplyOverrides(); }
						catch (e) { if (window.console) console.error('[aa palette] apply', e); }
						// Fill the four pickers DIRECTLY from the fetched values —
						// independent of aaSchemeSyncUI (which also refreshes the label /
						// preset match and was leaving the swatches blank when it errored
						// mid-run). This is why colours hit the preview but not the control.
						var roleKey = { brand:'colBrand', accent:'colAccent', ink:'colInk', page:'colPage' };
						$('#colorschemeopt .aa-scheme-input').each(function(){
							var role = $(this).data('role'), v = s[roleKey[role]];
							if (v) {
								this.value = v;
								$('#colorschemeopt .aa-scheme-role-hex[data-hex="' + role + '"]').text(v.toUpperCase());
							}
						});
						// Best-effort label / preset-match refresh.
						try { if (typeof window.aaSchemeSyncUI === 'function') window.aaSchemeSyncUI(); }
						catch (e) { if (window.console) console.error('[aa palette] syncUI', e); }
						$hint.addClass('is-success').text('Applied ' + n + ' colour' + (n === 1 ? '' : 's') + ' from your site'
							+ (r.source ? (' (via ' + r.source + ')') : '') + '. Tweak any swatch, or Reset to undo.');
					} else {
						$hint.addClass('is-error').text((r && r.msg) ? r.msg : 'Could not read a palette from your site.');
					}
				},
				error: function(xhr, st){
					$hint.addClass('is-error').text(st === 'timeout'
						? 'Reading your site timed out. Try again, or set the colours manually.'
						: 'Something went wrong reading your site. Try again.');
				},
				complete: function(){ $btn.prop('disabled', false).html(origHtml); }
			});
		});

		// First open → pre-fill pickers from the template's own palette so custom
		// tweaks start from the current colours.
		$(document).on('click', '#colorschemecontrols .card-header', function(){
			setTimeout(function(){ window.aaSchemeSyncUI(); }, 60);
		});

		// Explicit Save — same inline POST as Typography's Save for a definite
		// confirmation (autosave also persists this via aa-customization).
		$('#aaSaveScheme').on('click', function(){
			var $btn = $(this), $status = $('#aaSchemeSaveStatus');
			if ($btn.hasClass('is-saving')) return;
			$btn.addClass('is-saving');
			$status.removeClass('is-success is-error').text('Saving...');
			aaApplyOverrides();
			var payload = {
				body1:           $(ic_frameDocs()).find('#funnelrb').html(),
				header1:         $(ic_frameDocs()).find('#funnelrh').html(),
				thankyou_header: $(ic_frameDocs()).find('#funnelrh1').html(),
				thankyou_body:   $(ic_frameDocs()).find('#funnelrb1').html(),
				thankyou_template_id: '<?= isset($thankyou_template_id) ? $thankyou_template_id : "" ?>',
				analyticsCode:          $(ic_funnelDoc()).find('.analyticsCode').text(),
				retarget_analyticsCode: $(ic_funnelDoc()).find('.retarget_analyticsCode').text(),
				chatbotEmbedCode:       $(ic_funnelDoc()).find('.chatbotEmbedCode').text(),
				funnel_id:   $('#funnel_id').val(),
				template_id: $('#template_id').val(),
				slug:        $('.slug-input').val() || ''
			};
			$.ajax({
				type: 'POST', url: '<?= site_url('submitAction') ?>', data: payload,
				success: function(data){
					var ok = false;
					try { var r = JSON.parse(data); ok = (r && (r.status == 200 || r.status === '200')); } catch(e){}
					$status.addClass(ok ? 'is-success' : 'is-error').text(ok ? 'Saved' : 'Save failed');
					if (ok && typeof window.resetAutosaveBaseline === 'function') window.resetAutosaveBaseline();
					setTimeout(function(){ $status.removeClass('is-success is-error').text(''); }, 3000);
				},
				error: function(){
					$status.addClass('is-error').text('Save failed - check connection');
					setTimeout(function(){ $status.removeClass('is-error').text(''); }, 3000);
				},
				complete: function(){ $btn.removeClass('is-saving'); }
			});
		});



		// ── Social icon visibility toggles ──────────────────────────────
		// Most templates only ship 3 of the 5 platforms (Facebook, Instagram,
		// YouTube — Twitter and Pinterest aren't present in any current
		// template). We probe the funnel body for each platform's class and
		// hide rows that don't apply, so the user only sees toggles for icons
		// that actually exist on their page. Re-runs after hydrate in case
		// the saved content is what introduced/removed a platform.
		function aaSyncSocialVisibilityRows() {
			var $rb = $(ic_frameDocs()).find('#funnelrb');
			if (!$rb.length) return;
			var anyShown = false;
			$('.aa-visibility-row .form-check-input').each(function(){
				var $cb = $(this);
				var platform = $cb.data('platform');
				if (!platform) return;
				var $row = $cb.closest('.aa-visibility-row');
				var $matches = $rb.find('.' + platform);
				if (!$matches.length) {
					$row.hide();
					return;
				}
				$row.show();
				anyShown = true;
				// Mirror DOM display state into the checkbox so a saved
				// "display:none" comes back as an unchecked toggle.
				var $first = $matches.first();
				$cb.prop('checked', $first[0].style.display !== 'none');
			});
			// Hide the whole "Visibility" subsection if the template ships
			// with no social icons (e.g. boiler-plate).
			$('.aa-social-vis-section').toggle(anyShown);
		}

		$('.aa-visibility-row .form-check-input').on('change', function(){
			var $cb = $(this);
			var platform = $cb.data('platform');
			var visible  = $cb.is(':checked');
			$(ic_frameDocs()).find('.' + platform).each(function(){
				this.style.display = visible ? '' : 'none';
			});
			if (typeof window.scheduleAutosave === 'function') {
				window.scheduleAutosave('aa-customization-social-' + platform);
			}
		});

		// Initial sync after template HTML is in the DOM.
		setTimeout(aaSyncSocialVisibilityRows, 250);

		// ── Per-icon hide/show for niche template's hero contact icons ─────
		// Each .niche-hero-contact in the canvas gets a tiny "×" overlay on hover.
		// Click toggles .aa-icon-disabled on the icon's <img>. CSS handles
		// rendering: published page hides; editor shows it 35% grey. Replace
		// still works since the icon itself keeps its click-to-swap handler.
		// Hover-managed (not permanent DOM) so the saved funnel content never
		// carries the overlay button — only the class on the <img> persists.
		// Gated to user IDs 1 (super-admin) and 217 (early-access) for now —
		// everyone else continues to see the icons exactly as before.
		<?php if (in_array((int) $user_id, [1, 217], true)): ?>
		$('#templateEditor').on('mouseenter', '.niche-hero-contact', function () {
			var $row = $(this);
			if ($row.find('.aa-icon-vis-toggle').length) return;
			var $img = $row.children('img').first();
			if (!$img.length) return;
			var hidden = $img.hasClass('aa-icon-disabled');
			var $btn = $('<button type="button" class="aa-icon-vis-toggle" title="Hide / show this icon">' + (hidden ? '+' : '×') + '</button>');
			if (hidden) $btn.addClass('is-hidden');
			$row.append($btn);
		}).on('mouseleave', '.niche-hero-contact', function () {
			$(this).find('.aa-icon-vis-toggle').remove();
		}).on('click', '.aa-icon-vis-toggle', function (e) {
			e.preventDefault();
			e.stopPropagation();
			var $row = $(this).closest('.niche-hero-contact');
			var $img = $row.children('img').first();
			if (!$img.length) return;
			var nowHidden = !$img.hasClass('aa-icon-disabled');
			$img.toggleClass('aa-icon-disabled', nowHidden);
			$(this).text(nowHidden ? '+' : '×').toggleClass('is-hidden', nowHidden);
			if (typeof window.scheduleAutosave === 'function') {
				window.scheduleAutosave('aa-customization-niche-contact-' + (nowHidden ? 'hide' : 'show'));
			}
		});
		<?php endif; ?>

		// ── Niche template "Upload your image here" placeholder ─────────
		// The Default Niche template (#124) ships with a .niche-service-upload-container
		// block whose only content is a grey "Upload your images here..." caption
		// and an upload-icon SVG. Tapping the section surfaces an inline mini
		// toolbar over the placeholder with two choices: upload an image (which
		// fires the existing image picker by clicking the inner <img>), or
		// remove the whole block. Outside-click closes the toolbar without
		// committing anything. Behaviour is scoped to the niche placeholder
		// class so other templates' image clicks still flow through the
		// regular picker flow untouched.
		var $aaNichePopover = null;
		function aaCloseNichePopover() {
			if ($aaNichePopover) { $aaNichePopover.remove(); $aaNichePopover = null; }
		}
		function aaOpenNichePopover($container) {
			aaCloseNichePopover();
			$aaNichePopover = $(
				'<div class="aa-niche-pop" role="dialog" aria-label="Image placeholder actions">' +
					'<div class="aa-niche-pop-eyebrow">Image Placeholder</div>' +
					'<div class="aa-niche-pop-title">Replace or remove this section</div>' +
					'<div class="aa-niche-pop-actions">' +
						'<button type="button" class="aa-niche-pop-upload"><i class="fa fa-image" aria-hidden="true"></i><span>Upload Image</span></button>' +
						'<button type="button" class="aa-niche-pop-remove"><i class="fa fa-trash" aria-hidden="true"></i><span>Remove Section</span></button>' +
					'</div>' +
					'<button type="button" class="aa-niche-pop-close" aria-label="Close">&times;</button>' +
				'</div>'
			);
			// Anchor on the placeholder block itself.
			$container.css('position', 'relative').append($aaNichePopover);

			$aaNichePopover.find('.aa-niche-pop-upload').on('click', function(e){
				e.stopPropagation();
				aaCloseNichePopover();
				// Trigger the existing image picker by clicking the inner upload-icon img,
				// which is what the funnel editor's image-click handler already wires up.
				var $img = $container.find('img').first();
				if ($img.length) $img.trigger('click');
			});
			$aaNichePopover.find('.aa-niche-pop-remove').on('click', function(e){
				e.stopPropagation();
				$container.css('display', 'none');
				aaCloseNichePopover();
				if (typeof window.scheduleAutosave === 'function') {
					window.scheduleAutosave('aa-customization-niche-placeholder-remove');
				}
			});
			$aaNichePopover.find('.aa-niche-pop-close').on('click', function(e){
				e.stopPropagation();
				aaCloseNichePopover();
			});
			$aaNichePopover.on('click', function(e){ e.stopPropagation(); });
		}

		// Click-on-placeholder is the only entry point. We capture early so
		// the generic image-picker click handler at line ~5195 doesn't also
		// fire — the placeholder click should NOT open the gallery modal,
		// it should ask the user what they want first.
		// Exception: the .niche-service-upload-text caption stays editable —
		// clicks on it fall through to the inline text-edit handler.
		$(document).on('click', '#templateEditor .niche-service-upload-container, #templateEditor .niche-service-upload-container *', function(e){
			if ($(e.target).closest('.niche-service-upload-text').length) return;
			e.stopPropagation();
			e.preventDefault();
			var $container = $(this).closest('.niche-service-upload-container');
			if ($container.length) aaOpenNichePopover($container);
		});

		// Outside-click closes the popover.
		$(document).on('click', function(){ aaCloseNichePopover(); });

		// ── Runtime DOM tagger for .aa-bg-editable on legacy funnels ─────
		// Existing funnels (rows in `funnels.content`) were saved before we
		// added the `aa-bg-editable` marker class to the on-disk templates,
		// so their rendered DOM has the niche-prefixed wrappers but NOT the
		// marker. Rather than mutate the DB, we re-tag at editor load time
		// by selector: every element matching one of the known niche
		// classes gets `aa-bg-editable` added so the picker below can find
		// it. The list below is the union of every niche-prefixed class
		// that carried `aa-bg-editable` in landing-pages/html/*.html.
		// Scope is strictly `#templateEditor` — public funnel pages never
		// load this script so they never receive the marker. The
		// MutationObserver covers the case where the editor lazy-renders
		// or swaps content (e.g. niche switcher) after first paint; it
		// only re-tags on subtree additions, never on attribute changes,
		// so user keystrokes inside contenteditable nodes don't trigger
		// it. A short debounce additionally collapses bursts of mutations.
		(function aaBgRuntimeTaggerInit(){
			var editorRoot = document.getElementById('templateEditor');
			if (!editorRoot) return; // gated: editor canvas only
			if (window.__aaBgRuntimeTaggerLoaded) return;
			window.__aaBgRuntimeTaggerLoaded = true;

			// Union list of niche-prefixed classes that wear the
			// `aa-bg-editable` marker on disk. 153 entries, one per
			// recolour-eligible element across the 27 templates.
			const AA_BG_SELECTORS = [
				'bakery-footer-section','bakery-form-container','bakery-hero-accent','bakery-hero-main','bakery-hero-nav','bakery-test-testimonial-wrapper',
				'beauty-footer-section','beauty-form-container',
				'body-plumbing',
				'carpenter-footer-section','carpenter-form-container','carpenter-testi-card',
				'carwash-footer-section','carwash-services-accent','carwash-services-accent-bottom','carwash-services-contact','carwash-testimonial-card',
				'chiro-footer-section','chiro-form-container','chiro-hero-container','chiro-hero-header','chiro-logo-ctr','chiro-service-left-column','chiro-testi-card','chiro-testi-container',
				'clean-h-footer-section','clean-h-sv-container','clean-h-sv-image-section','clean-hm-bh-container','cleanhome-service-bottom-ctr',
				'd-care-service-container','d-care-service2-container','d-care-testi-card','dcare-footer-section','dcare-form-container',
				'dental-footer-section','dental-hero-content-box','dental-hero-header','dental-ser-common-card','dental-ser-first-card','dental-service-card','dental-service-container','dental-testi-container','dental-testi-slide',
				'electrical-footer-section','electrical-form-container','electrical-service-features','electrical-service-header','electrical-service-icon','electrical-testi-card','electrical-testi-card-gray','electrical-testi-card-red',
				'event-footer-section','event-testi-card','event-testi-card-dark','event-testi-card-light',
				'flower-contact-bar','flower-footer-section','flower-hero-header','flower-service-hero',
				'gym-footer-section','gym-form-container','gym-service-left','gym-service-section','gym-testi-card','gym-testi-section',
				'home-clean-testi-card','home-clean-testi-card-orange','home-clean-testi-card-white',
				'hvac-body','hvac-contact-bar','hvac-footer-section','hvac-info-testimonial','hvac-service-card','hvac-service-label','hvac-service-label-1','hvac-service-label-2',
				'insurance-card','insurance-contact-bar','insurance-footer-section','insurance-images','insurance-newsletter-button','insurance-service-card','insurance-services',
				'kitchen-body','kitchen-footer-section','kitchen-form-section','kitchen-services-card','kitchen-testimonial-card','kitchen-testimonial-section',
				'lands-footer-section','lands-testi-card','lands-testi-card-green','lands-testi-card-white',
				'locksmith-footer-section','locksmith-service-content-section','locksmith-testi-card',
				'mechanic-footer-section','mechanic-hero-container','mechanic-testi-card','mechanic-testi-container',
				'niche-footer-section','niche-hero-footer','niche-hero-highlight','niche-hero-logo','niche-service-container','niche-service-upload-container','niche-testi-card','niche-testi-card-dark','niche-testi-card-light',
				'pest-contact-container','pest-contact-info','pest-contact-right-bg','pest-footer-section','pest-hero-main-container','pest-hero-nav-container','pest-result-background','pest-result-card',
				'pet-contact-bar','pet-footer-section','pet-hero-header','pet-steps-process-container',
				'photo-contact-bar','photo-footer-section','photo-hero-form-container','photo-service-main-section','photo-testi-card',
				'plumbing-footer-section','plumbing-service-section','plumbing-service-tagline','plumbing-testimonial-card','plumbing-testimonial-section',
				'real-e-service-card','real-es-footer-section','real-es-form-container','real-es-testi-card','real-es-testi-container',
				'rest-testi-card','restau-footer-section','restau-form-container',
				'roof-blue','roof-footer-section','roof-form-container','roof-light','roof-nav','roof-service-circle','roof-service-container','roof-service-content','roof-testimonial-card',
				'spa-footer-section','spa-form-container','spa-service','spa-service-ctr','spa-testimonial'
			];

			// Pre-build a single selector string — querySelectorAll is
			// fast enough that one combined query beats 153 separate
			// passes through the DOM.
			var combinedSelector = '#templateEditor .' + AA_BG_SELECTORS.join(', #templateEditor .');

			function tagAll(){
				var nodes = document.querySelectorAll(combinedSelector);
				for (var i = 0; i < nodes.length; i++) {
					// classList.add is idempotent — re-adding the
					// marker on an already-tagged element is a no-op.
					nodes[i].classList.add('aa-bg-editable');
				}
			}

			// Initial pass — runs before aaBgPickerInit below so the
			// picker's hover binding sees the markers on first paint.
			tagAll();

			// MutationObserver: re-tag whenever new nodes are added to
			// the editor subtree. We deliberately do NOT watch attribute
			// changes (would loop on our own classList.add and fire on
			// every text-edit), and we debounce so a burst of DOM ops
			// (e.g. template swap) collapses into one tag pass.
			var rescanTimer = null;
			function scheduleRescan(){
				if (rescanTimer) return; // already pending
				rescanTimer = setTimeout(function(){
					rescanTimer = null;
					tagAll();
				}, 50);
			}
			var observer = new MutationObserver(function(mutations){
				// Only rescan if at least one mutation added element
				// nodes — text-node insertions from typing don't need
				// a re-tag.
				for (var i = 0; i < mutations.length; i++) {
					var added = mutations[i].addedNodes;
					for (var j = 0; j < added.length; j++) {
						if (added[j].nodeType === 1) { // ELEMENT_NODE
							scheduleRescan();
							return;
						}
					}
				}
			});
			observer.observe(editorRoot, { childList: true, subtree: true });
		})();

		// ── Inline background-colour picker for .aa-bg-editable ──────────
		// Scope: ONLY inside #templateEditor (never on the public funnel page).
		// On hover over an `.aa-bg-editable` element inside the editor we
		// surface a small floating "🎨" button anchored to the element's
		// top-right corner. Clicking the button opens a native HTML5 colour
		// input pre-loaded with the element's current rendered background
		// colour. On change we write `style.backgroundColor` inline (so it
		// overrides the stylesheet and survives save/reload) and then call
		// the existing scheduleAutosave() so the change persists exactly
		// like text edits or image swaps.
		(function aaBgPickerInit(){
			var editorRoot = document.getElementById('templateEditor');
			if (!editorRoot) return; // gated: editor canvas only
			if (window.__aaBgPickerLoaded) return;
			window.__aaBgPickerLoaded = true;

			// ── Lazy-load Phosphor icons once (duotone weight for a more
			// "designed" feel — palette + accent dot read as two tones). ──
			if (!document.getElementById('aa-phosphor-icons')) {
				var ph = document.createElement('link');
				ph.id = 'aa-phosphor-icons';
				ph.rel = 'stylesheet';
				ph.href = 'https://unpkg.com/@phosphor-icons/web@2.1.1/src/duotone/style.css';
				document.head.appendChild(ph);
			}

			// ── Inject styles (scoped via class names so they don't leak) ──
			if (!document.getElementById('aa-bg-picker-styles')) {
				var st = document.createElement('style');
				st.id = 'aa-bg-picker-styles';
				st.textContent = [
					/* Subtle dashed outline on hover so the user can see what
					   is editable. Pointer-events stays on the element so
					   text/image edit handlers underneath still receive
					   clicks. */
					'#templateEditor .aa-bg-editable.aa-bg-hover {',
					'  outline: 1px dashed rgba(232,72,72,.55);',
					'  outline-offset: 2px;',
					'}',
					/* Floating picker button — absolutely positioned, follows
					   the hovered element via JS. pointer-events:auto so it
					   is clickable; the rest of the affordance never blocks
					   the underlying element. */
					'.aa-bg-pick-btn {',
					'  position: absolute; z-index: 9999;',
					'  width: 30px; height: 30px; padding: 0;',
					'  display: inline-flex; align-items: center; justify-content: center;',
					'  line-height: 1;',
					'  background: #FFFDF7;',                  /* dossier paper */
					'  color: #1a1410;',                        /* primary ink (palette outline) */
					'  border: 1px solid rgba(26,20,16,.18);',
					'  border-radius: 50%; cursor: pointer;',
					'  box-shadow: 0 2px 6px rgba(58,35,14,.16), 0 0 0 2px rgba(255,255,255,.65) inset;',
					'  pointer-events: auto;',
					'  transition: transform .18s cubic-bezier(.2,.8,.2,1), box-shadow .18s ease, color .18s ease;',
					'}',
					/* Phosphor duotone: --ph-duotone-secondary-color controls
					   the lighter overlay (the paint dot accents on the
					   palette). Coral matches the editor dossier accent. */
					'.aa-bg-pick-btn .ph-duotone {',
					'  font-size: 18px; line-height: 1; display: inline-block;',
					'  --ph-duotone-secondary-color: #C72E2E;',
					'  --ph-duotone-secondary-opacity: 1;',
					'}',
					'.aa-bg-pick-btn:hover {',
					'  transform: scale(1.1);',
					'  box-shadow: 0 4px 12px rgba(58,35,14,.22), 0 0 0 2px rgba(255,255,255,.75) inset;',
					'}',
					'.aa-bg-pick-btn:hover .ph-duotone {',
					'  --ph-duotone-secondary-color: #FF9E7A;',  /* warmer coral on hover */
					'}',
					/* The colour input we reuse for every element. Positioned
					   to overlap the floating button so the native picker opens
					   reliably (Firefox/Safari refuse to open a colour picker
					   via .click() on an off-screen input). The button sits on
					   top visually; the input is the actual click target. */
					'.aa-bg-pick-input {',
					'  position: absolute; z-index: 10000;',
					'  width: 30px; height: 30px; padding: 0; margin: 0;',
					'  border: 0; background: transparent;',
					'  opacity: 0; cursor: pointer;',
					'}'
				].join('\n');
				document.head.appendChild(st);
			}

			// ── Shared colour input (one element reused) ─────────────────
			// Stacked over the floating 🎨 button via positionButton() so
			// the user's click on the button actually lands on this input,
			// which opens the native picker reliably (no .click() needed).
			var colorInput = document.createElement('input');
			colorInput.type = 'color';
			colorInput.className = 'aa-bg-pick-input';
			colorInput.style.display = 'none';
			document.body.appendChild(colorInput);

			// ── Floating colour-palette button (one element reused) ─────
			// Phosphor duotone palette: outline in editor ink, paint-dot
			// accents in the dossier coral. Reads instantly as "colour
			// palette" while still feeling designed, not emoji-y.
			var pickBtn = document.createElement('button');
			pickBtn.type = 'button';
			pickBtn.className = 'aa-bg-pick-btn';
			pickBtn.title = 'Change background colour';
			pickBtn.setAttribute('aria-label', 'Change background colour');
			pickBtn.innerHTML = '<i class="ph-duotone ph-palette" aria-hidden="true"></i>';
			pickBtn.style.display = 'none';
			document.body.appendChild(pickBtn);

			var currentEl = null;       // element the button is anchored to
			var targetEl = null;        // element being edited (locked during picker)
			var hideTimer = null;

			// Convert "rgb(r,g,b)" / "rgba(r,g,b,a)" → "#rrggbb" for the
			// native picker, which only accepts hex. Falls back to #ffffff.
			function rgbToHex(rgb){
				if (!rgb) return '#ffffff';
				if (rgb.charAt(0) === '#') return rgb;
				var m = rgb.match(/rgba?\(\s*(\d+)\s*,\s*(\d+)\s*,\s*(\d+)/i);
				if (!m) return '#ffffff';
				function h(n){ var s = parseInt(n,10).toString(16); return s.length === 1 ? '0' + s : s; }
				return '#' + h(m[1]) + h(m[2]) + h(m[3]);
			}

			function positionButton(el){
				if (!el) { pickBtn.style.display = 'none'; colorInput.style.display = 'none'; return; }
				var rect = el.getBoundingClientRect();
				// 28px button, anchor to top-right of the element. Use
				// page coords (scrollX/Y) because the button is absolutely
				// positioned against <body>.
				var top = rect.top + window.scrollY + 6;
				var left = rect.left + window.scrollX + rect.width - 28 - 6;
				pickBtn.style.top = top + 'px';
				pickBtn.style.left = left + 'px';
				pickBtn.style.display = 'inline-flex';
				// Stack the colour input over the button so the user's click
				// lands directly on the input — the only reliable way to open
				// the native picker across Chrome / Firefox / Safari.
				colorInput.style.top = top + 'px';
				colorInput.style.left = left + 'px';
				colorInput.style.display = 'block';
			}

			function showFor(el){
				if (hideTimer) { clearTimeout(hideTimer); hideTimer = null; }
				if (currentEl && currentEl !== el) currentEl.classList.remove('aa-bg-hover');
				currentEl = el;
				currentEl.classList.add('aa-bg-hover');
				positionButton(currentEl);
			}

			function scheduleHide(){
				if (hideTimer) clearTimeout(hideTimer);
				hideTimer = setTimeout(function(){
					if (currentEl) currentEl.classList.remove('aa-bg-hover');
					currentEl = null;
					pickBtn.style.display = 'none';
					colorInput.style.display = 'none';
				}, 160);
			}

			// Use mouseover/mouseout on the editor (event delegation) so we
			// don't have to attach listeners to every .aa-bg-editable.
			editorRoot.addEventListener('mouseover', function(e){
				var el = e.target.closest && e.target.closest('.aa-bg-editable');
				if (!el) return;
				if (!editorRoot.contains(el)) return; // belt + braces
				showFor(el);
			});
			editorRoot.addEventListener('mouseout', function(e){
				var el = e.target.closest && e.target.closest('.aa-bg-editable');
				if (!el) return;
				// If we're leaving for the picker button itself, don't hide.
				var to = e.relatedTarget;
				if (to && (to === pickBtn || (to.closest && to.closest('.aa-bg-pick-btn')))) return;
				scheduleHide();
			});
			// Keep the button visible when the cursor enters it OR the
			// colour input layered on top of it.
			pickBtn.addEventListener('mouseenter', function(){
				if (hideTimer) { clearTimeout(hideTimer); hideTimer = null; }
			});
			pickBtn.addEventListener('mouseleave', scheduleHide);
			colorInput.addEventListener('mouseenter', function(){
				if (hideTimer) { clearTimeout(hideTimer); hideTimer = null; }
			});
			colorInput.addEventListener('mouseleave', scheduleHide);

			// Reposition on scroll/resize while a target is hovered (the
			// editor canvas is scrollable).
			function reposition(){ if (currentEl && pickBtn.style.display !== 'none') positionButton(currentEl); }
			editorRoot.addEventListener('scroll', reposition, true);
			window.addEventListener('scroll', reposition, true);
			window.addEventListener('resize', reposition);

			// The colour input is stacked ON TOP of the floating 🎨 button,
			// so the user's click on the visible button actually lands on
			// the input — which opens the native picker without us having
			// to call .click() (which is unreliable across browsers).
			// Seed the input value on mousedown so the picker opens
			// pre-filled with the element's current rendered colour.
			colorInput.addEventListener('mousedown', function(){
				if (!currentEl) return;
				targetEl = currentEl;
				var current = window.getComputedStyle(targetEl).backgroundColor;
				try { colorInput.value = rgbToHex(current); } catch(_) { colorInput.value = '#ffffff'; }
			});

			// Apply on every "input" event so the user sees a live preview
			// while dragging in the picker; final "change" persists.
			colorInput.addEventListener('input', function(){
				if (!targetEl) return;
				targetEl.style.backgroundColor = colorInput.value;
			});
			colorInput.addEventListener('change', function(){
				if (!targetEl) return;
				targetEl.style.backgroundColor = colorInput.value;
				if (typeof window.scheduleAutosave === 'function') {
					window.scheduleAutosave('aa-bg-editable colour change');
				}
				targetEl = null;
			});
		})();
	});


</script>


</body></html>