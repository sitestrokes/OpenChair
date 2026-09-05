<style>
	.textarea-container {
    display: flex;
    flex-direction: column;
    align-items: flex-start;
}
.tinymce { width: 100%; }
.generate-button { margin-bottom: 10px; }
.generate-button:hover { background-color: #0056b3; }
.publishClose { padding-top: 0px !important; }
.SmsModal .modal-footer { display: flex !important; }

/* ═══════════════════════════════════════════════════════════════
   PUBLISHING MODAL (#popup2) — dossier treatment
   ═══════════════════════════════════════════════════════════════ */

/* Cap modal height so the close button + footer stay reachable
   even when the active tab (e.g. HTML Form) has a tall snippet. */
#popup2 .modal-dialog{
    max-height:calc(100vh - 32px) !important;
    margin:16px auto !important;
    max-width: 780px !important;
    width: 95% !important;
}
#popup2 .modal-content{
    max-height:calc(100vh - 32px) !important;
    display:flex !important;
    flex-direction:column !important;
    overflow:hidden !important;
}
#popup2 .modal-header{
    flex:0 0 auto !important;
    position:sticky !important;
    top:0 !important;
    z-index:5 !important;
    background:#FFFDF7 !important;
    border-bottom:1px solid rgba(26,20,16,.1) !important;
    padding:22px 28px 16px !important;
}
#popup2 .nav-tabs{
    flex:0 0 auto !important;
    background:#FFFDF7 !important;
    position:sticky !important;
    top:0 !important;
    z-index:4 !important;
}
#popup2 .tab-content{
    flex:1 1 auto !important;
    overflow-y:auto !important;
    overflow-x:hidden !important;
    -webkit-overflow-scrolling:touch !important;
}
#popup2 .modal-footer{
    flex:0 0 auto !important;
    background:#FFFDF7 !important;
    border-top:1px solid rgba(26,20,16,.08) !important;
    position:sticky !important;
    bottom:0 !important;
    z-index:5 !important;
}
#popup2 .modal-header h5{
    font-family:'Poppins',sans-serif !important;
    font-size:18px !important;
    font-weight:600 !important;
    color:#1A1410 !important;
    letter-spacing:-.02em !important;
}

/* Tab pills — dossier stamps instead of orange rounded buttons */
#popup2 .nav-tabs{
    border-bottom:none !important;
    padding:0 28px !important;
    gap:8px !important;
    display:flex !important;
    margin-top:8px;
}
#popup2 .nav-tabs li{list-style:none;}
#popup2 .nav-tabs li a,
#popup2 .nav-tabs .nav-link{
    font-family:'JetBrains Mono',monospace !important;
    font-size:9px !important;
    font-weight:700 !important;
    letter-spacing:.12em !important;
    text-transform:uppercase !important;
    color:#8B7B6E !important;
    background:transparent !important;
    border:1.5px solid rgba(26,20,16,.18) !important;
    border-radius:1px !important;
    padding:8px 16px !important;
    transition:all .2s !important;
    text-decoration:none !important;
}
#popup2 .nav-tabs li a:hover,
#popup2 .nav-tabs .nav-link:hover{
    color:#1A1410 !important;
    border-color:#1A1410 !important;
}
#popup2 .nav-tabs li a.active,
#popup2 .nav-tabs .nav-link.active{
    background:#1A1410 !important;
    color:#FBF6EE !important;
    border-color:#1A1410 !important;
    box-shadow:2px 2px 0 #C72E2E !important;
}

/* Tab content */
#popup2 .tab-content{
    padding:32px 28px !important;
}

/* Published link tab */
#popup2 .publish-note{
    font-family:'Poppins',sans-serif !important;
    font-size:16px !important;
    font-weight:500 !important;
    color:#1A1410 !important;
    line-height:1.5 !important;
}
#popup2 .publish-note img{
    width:40px !important;
    height:40px !important;
    filter:hue-rotate(320deg) saturate(1.5);
    margin-bottom:12px;
}
#popup2 .publish-note a{
    color:#C72E2E !important;
    font-weight:600 !important;
    text-decoration:underline !important;
}

/* URL input */
#popup2 .publish_link{
    font-family:'JetBrains Mono',monospace !important;
    font-size:11px !important;
    color:#3B2F26 !important;
    letter-spacing:.02em !important;
}

/* Copy icon */
#popup2 #copylink{
    right:12px !important;
    top:50% !important;
    transform:translateY(-50%) !important;
}
#popup2 #copylink img{
    filter:brightness(0) saturate(100%) !important;
    opacity:.5;
    transition:opacity .2s;
}
#popup2 #copylink:hover img{ opacity:1; }

/* WordPress tab */
#popup2 .btn_4{
    display:inline-flex !important;
    align-items:center;gap:8px;
    font-family:'JetBrains Mono',monospace !important;
    font-size:10px !important;
    font-weight:700 !important;
    letter-spacing:.14em !important;
    text-transform:uppercase !important;
    color:#FBF6EE !important;
    background:#1A1410 !important;
    border:1px solid #1A1410 !important;
    border-radius:1px !important;
    padding:12px 24px !important;
    box-shadow:3px 3px 0 #C72E2E !important;
    text-decoration:none !important;
    transition:all .2s !important;
}
#popup2 .btn_4:hover{
    background:#C72E2E !important;
    border-color:#C72E2E !important;
    transform:translate(-2px,-2px) !important;
    box-shadow:5px 5px 0 #1A1410 !important;
}

/* Footer — Done button + Cancel */
#popup2 .modal-footer{
    padding:16px 28px !important;
    gap:12px;
}
#popup2 .modal-footer .btn_1,
#popup2 .modal-footer .publishDone{
    background:#1A1410 !important;
    color:#FBF6EE !important;
    border:1px solid #1A1410 !important;
    border-radius:1px !important;
    font-family:'JetBrains Mono',monospace !important;
    font-size:10px !important;
    font-weight:700 !important;
    text-transform:uppercase !important;
    letter-spacing:.14em !important;
    padding:12px 28px !important;
    box-shadow:3px 3px 0 #C72E2E !important;
    transition:all .2s !important;
}
#popup2 .modal-footer .btn_1:hover,
#popup2 .modal-footer .publishDone:hover{
    background:#C72E2E !important;
    transform:translate(-2px,-2px) !important;
    box-shadow:5px 5px 0 #1A1410 !important;
}
#popup2 .modal-footer .publishDone img{
    filter:brightness(0) invert(1) !important;
    width:14px;height:14px;
}
#popup2 .modal-footer .btn-modal-cancel,
#popup2 .modal-footer .publishClose{
    font-family:'JetBrains Mono',monospace !important;
    font-size:9px !important;
    font-weight:600 !important;
    text-transform:uppercase !important;
    letter-spacing:.14em !important;
    color:#8B7B6E !important;
    background:none !important;
    border:none !important;
    box-shadow:none !important;
}
#popup2 .modal-footer .btn-modal-cancel:hover{color:#C72E2E !important;}

/* Slug editor inside the publish modal */
#popup2 .slug-editor{
    margin-top:18px;
}
#popup2 .slug-editor-label{
    display:block;
    font-family:'JetBrains Mono', ui-monospace, monospace !important;
    font-size:10px !important;
    font-weight:600 !important;
    text-transform:uppercase;
    letter-spacing:.14em;
    color:#7A6E61 !important;
    margin-bottom:6px;
}
#popup2 .slug-editor-row{
    display:flex;
    align-items:stretch;
    gap:0;
    border:1px solid rgba(26,20,16,.18);
    border-radius:6px;
    background:#FFFDF7;
    overflow:hidden;
    transition:border-color .15s ease;
}
#popup2 .slug-editor-row:focus-within{
    border-color:#1A1410;
}
#popup2 .slug-editor-prefix{
    display:inline-flex;
    align-items:center;
    padding:0 10px;
    font-family:'JetBrains Mono', ui-monospace, monospace;
    font-size:12px;
    color:#7A6E61;
    background:rgba(26,20,16,.04);
    border-right:1px solid rgba(26,20,16,.1);
    white-space:nowrap;
}
#popup2 .slug-input{
    flex:1 1 auto;
    border:none !important;
    background:transparent !important;
    padding:8px 10px !important;
    font-family:'JetBrains Mono', ui-monospace, monospace !important;
    font-size:13px !important;
    color:#1A1410 !important;
    box-shadow:none !important;
    outline:none !important;
    height:auto !important;
}
#popup2 .slug-editor-row #copylink,
#popup2.SmsModal .slug-editor-row #copylink{
    position:static !important;
    top:auto !important;
    right:auto !important;
    left:auto !important;
    bottom:auto !important;
    transform:none !important;
    margin:0 !important;
    display:inline-flex !important;
    align-items:center;
    justify-content:center;
    padding:0 12px !important;
    background:rgba(26,20,16,.04) !important;
    border-left:1px solid rgba(26,20,16,.1);
    flex-shrink:0;
}
#popup2 .slug-editor-row #copylink img{
    width:16px !important;
    height:16px !important;
    display:block;
}
#popup2 .slug-status{
    margin-top:6px;
    min-height:16px;
    font-family:'JetBrains Mono', ui-monospace, monospace;
    font-size:11px;
    letter-spacing:.04em;
    color:#7A6E61;
}
#popup2 .slug-status.ok{ color:#2F8F5C; }
#popup2 .slug-status.bad{ color:#C72E2E; }
#popup2 .slug-preview-row{
    margin-top:10px;
    display:flex;
    align-items:baseline;
    gap:8px;
    flex-wrap:wrap;
}
#popup2 .slug-preview-label{
    font-family:'JetBrains Mono', ui-monospace, monospace;
    font-size:10px;
    font-weight:600;
    text-transform:uppercase;
    letter-spacing:.14em;
    color:#7A6E61;
    flex-shrink:0;
}
#popup2 .slug-preview-url{
    font-family:'JetBrains Mono', ui-monospace, monospace;
    font-size:11px;
    color:#1A1410;
    background:rgba(26,20,16,.04);
    padding:3px 8px;
    border-radius:4px;
    word-break:break-all;
    flex:1 1 auto;
    min-width:0;
}

</style>
<!-- ── Content Editor modal — Agentic Affiliates dark-token reskin (scoped to
     #contentModal so it never touches other modals). Tighter than the old
     1050px Bootstrap-lg so it reads like a focused inline-edit box. ── -->
<style id="ic-content-modal-skin">
#contentModal .modal-dialog{ max-width:720px !important; margin:1.75rem auto; }
#contentModal .modal-content{
	/* !important: a global `.modal-content{background:#fffdf7!important}` rule
	   otherwise wins despite this rule's higher specificity. */
	background:var(--ic-surface,#0f1320) !important;
	border:1px solid var(--ic-line2,#2c3658) !important;
	border-radius:var(--ic-r3,15px) !important;
	box-shadow:0 24px 64px -16px rgba(0,0,0,.6) !important;
	color:var(--ic-txt,#eef1fb);
	font-family:var(--ic-body,'Inter',sans-serif);
	overflow:hidden;
}
#contentModal .modal-body{ padding:18px 20px 20px; background:var(--ic-surface,#0f1320) !important; }
#contentModal h4{
	margin:0 0 14px; font-family:var(--ic-disp,'Archivo',sans-serif);
	font-size:16px; font-weight:700; letter-spacing:-.01em; color:var(--ic-txt,#eef1fb);
}
#contentModal .close{
	position:absolute; top:14px; right:16px; opacity:1; z-index:2;
	width:28px; height:28px; border-radius:8px; padding:0;
	display:flex; align-items:center; justify-content:center;
	background:var(--ic-surface2,#141a2b); border:1px solid var(--ic-line,#1f2740);
	color:var(--ic-mute,#7e89a9); transition:.13s;
}
#contentModal .close:hover{ background:var(--ic-coraldim,#ff6a4d22); border-color:var(--ic-coral,#ff6a4d); color:var(--ic-coral,#ff6a4d); }
#contentModal .close .sr-only{ position:absolute; width:1px; height:1px; overflow:hidden; }
/* TinyMCE — shrink + dark-skin the chrome so it sits on the IC surface */
#contentModal .tox-tinymce{
	border:1px solid var(--ic-line,#1f2740) !important;
	border-radius:var(--ic-r2,11px) !important; overflow:hidden;
	height:300px !important;   /* compact — was the TinyMCE default ~400px */
}
#contentModal .tox .tox-editor-header,
#contentModal .tox .tox-toolbar,
#contentModal .tox .tox-toolbar__primary,
#contentModal .tox .tox-toolbar-overlord{ background:var(--ic-surface2,#141a2b) !important; }
#contentModal .tox .tox-edit-area__iframe{ background:var(--ic-surface2,#141a2b) !important; }
#contentModal .tox .tox-statusbar{
	background:var(--ic-surface2,#141a2b) !important;
	border-top:1px solid var(--ic-line,#1f2740) !important;
	color:var(--ic-mute,#7e89a9) !important;
}
#contentModal .tox .tox-statusbar a,
#contentModal .tox .tox-statusbar__wordcount{ color:var(--ic-mute,#7e89a9) !important; }
/* Toolbar buttons + select — light icons on the dark bar */
#contentModal .tox .tox-tbtn{ color:var(--ic-txt2,#aab2cd) !important; }
#contentModal .tox .tox-tbtn svg{ fill:var(--ic-txt2,#aab2cd) !important; }
#contentModal .tox .tox-tbtn:hover{ background:var(--ic-surface,#0f1320) !important; }
#contentModal .tox .tox-tbtn--enabled,
#contentModal .tox .tox-tbtn--enabled:hover{ background:var(--ic-coraldim,#ff6a4d22) !important; }
#contentModal .tox .tox-tbtn--enabled svg{ fill:var(--ic-coral,#ff6a4d) !important; }
#contentModal .tox .tox-split-button:hover,
#contentModal .tox .tox-tbtn--bespoke{ background:var(--ic-surface,#0f1320) !important; }
#contentModal .tox .tox-tbtn--bespoke .tox-tbtn__select-label{ color:var(--ic-txt2,#aab2cd) !important; }
#contentModal .tox:not(.tox-tinymce-inline) .tox-editor-header{ box-shadow:none !important; border-bottom:1px solid var(--ic-line,#1f2740) !important; }
#contentModal .block{ margin-top:14px !important; display:flex; justify-content:flex-end; }
#contentModal #applychanges2{
	background:var(--ic-coral,#ff6a4d) !important; border:0;
	color:#fff; font-family:var(--ic-body,'Inter',sans-serif);
	font-weight:600; font-size:13px; letter-spacing:.01em;
	border-radius:var(--ic-r1,8px); padding:9px 18px; transition:.13s;
}
#contentModal #applychanges2:hover{ background:var(--ic-coral2,#ff8b73) !important; }
#contentModal .modal-backdrop{ background:rgba(8,11,18,.66); }
</style>
<div class="modal fade selectPopup" id="contentModal">

	<div class="modal-dialog" >

		<div class="modal-content" >

			<div class="modal-body">
                <button type="button" class="close" data-dismiss="modal">
					<span class="fa fa-close" aria-hidden="true"></span>
					<span class="sr-only"><?php echo $this->lang->line('modal_close')?></span>
				</button>
				<h4><b>Content Editor</b></h4>
				<div class="ScriptFild">
					<div class="textarea-container">
    <textarea name="content" id="tinymce2" style="width: 100%" class="font18 m_b_8 tinymce"></textarea>
</div>

				</div>
				<div class="block">
					<a href="javascript:void(0);" class="btn btn-misc" id="applychanges2">Apply Changes</a>
				</div>
			</div>

		</div>

	</div>

</div>

<!-- ── Scrolling-bar (marquee/ticker) editor. A DEDICATED editor for the looping
     bars so they are no longer edited through the free-text modal — those bars
     duplicate their text TWICE for a seamless loop and separate items with
     decorative nodes, so inline editing touched one copy and mangled separators.
     One item per line; Save rebuilds both copies. Scoped to #popupMarquee. ── -->
<style id="ic-marquee-modal-skin">
#popupMarquee .modal-dialog{ max-width:560px !important; margin:1.75rem auto; }
#popupMarquee .modal-content{
	background:var(--ic-surface,#0f1320) !important;
	border:1px solid var(--ic-line2,#2c3658) !important;
	border-radius:var(--ic-r3,15px) !important;
	box-shadow:0 24px 64px -16px rgba(0,0,0,.6) !important;
	color:var(--ic-txt,#eef1fb); font-family:var(--ic-body,'Inter',sans-serif); overflow:hidden;
}
#popupMarquee .modal-body{ padding:20px 22px 22px; background:var(--ic-surface,#0f1320) !important; position:relative; }
#popupMarquee h4{ margin:0 0 6px; font-family:var(--ic-disp,'Archivo',sans-serif); font-size:16px; font-weight:700; color:var(--ic-txt,#eef1fb); }
#popupMarquee .aa-mq-hint{ margin:0 0 14px; font-size:12px; line-height:1.55; color:var(--ic-mute,#7e89a9); }
#popupMarquee textarea{
	width:100%; min-height:190px; resize:vertical; display:block;
	background:var(--ic-surface2,#141a2b); border:1px solid var(--ic-line,#1f2740);
	border-radius:var(--ic-r2,11px); color:var(--ic-txt,#eef1fb);
	font-family:var(--ic-body,'Inter',sans-serif); font-size:14px; line-height:1.7; padding:12px 14px; outline:none;
}
#popupMarquee textarea:focus{ border-color:var(--ic-coral,#ff6a4d); box-shadow:0 0 0 3px var(--ic-coraldim,#ff6a4d22); }
#popupMarquee .close{
	position:absolute; top:14px; right:16px; opacity:1; z-index:2; width:28px; height:28px; border-radius:8px; padding:0;
	display:flex; align-items:center; justify-content:center;
	background:var(--ic-surface2,#141a2b); border:1px solid var(--ic-line,#1f2740); color:var(--ic-mute,#7e89a9); transition:.13s;
}
#popupMarquee .close:hover{ background:var(--ic-coraldim,#ff6a4d22); border-color:var(--ic-coral,#ff6a4d); color:var(--ic-coral,#ff6a4d); }
#popupMarquee .close .sr-only{ position:absolute; width:1px; height:1px; overflow:hidden; }
#popupMarquee .aa-mq-actions{ margin-top:16px; display:flex; justify-content:flex-end; align-items:center; gap:14px; }
#popupMarquee .aa-mq-cancel{ background:none; border:none; color:var(--ic-mute,#7e89a9); font-size:11px; font-weight:600; letter-spacing:.08em; text-transform:uppercase; cursor:pointer; }
#popupMarquee .aa-mq-cancel:hover{ color:var(--ic-coral,#ff6a4d); }
#popupMarquee .aa-mq-save{ background:var(--ic-coral,#ff6a4d); border:0; color:#fff; font-weight:600; font-size:13px; border-radius:var(--ic-r1,8px); padding:9px 20px; cursor:pointer; transition:.13s; }
#popupMarquee .aa-mq-save:hover{ background:var(--ic-coral2,#ff8b73); }
</style>
<div class="modal fade" id="popupMarquee" tabindex="-1" role="dialog" aria-hidden="true">
	<div class="modal-dialog" role="document">
		<div class="modal-content">
			<div class="modal-body">
				<button type="button" class="close" data-dismiss="modal">
					<span class="fa fa-close" aria-hidden="true"></span>
					<span class="sr-only">Close</span>
				</button>
				<h4><b>Edit scrolling bar</b></h4>
				<p class="aa-mq-hint">One item per line. The bar loops through these, and both copies update together so the scroll stays seamless.</p>
				<textarea id="aaMarqueeText" spellcheck="false" placeholder="Instant delivery&#10;Any purchase level&#10;No upsell required"></textarea>
				<div class="aa-mq-actions">
					<button type="button" class="aa-mq-cancel" data-dismiss="modal">Cancel</button>
					<button type="button" class="aa-mq-save" id="aaMarqueeSave">Save</button>
				</div>
			</div>
		</div>
	</div>
</div>

<!-- ── List editor. A DEDICATED editor for content <ul> lists so items can be
     ADDED and DELETED (and reordered), which the free-text modal couldn't do.
     Each row = one <li>; Save rebuilds the list preserving each item's icon.
     Scoped to #popupList. ── -->
<style id="ic-list-modal-skin">
#popupList .modal-dialog{ max-width:588px !important; margin:1.75rem auto; }
#popupList .modal-content{
	background:linear-gradient(180deg, var(--ic-surface2,#141a2b) 0%, var(--ic-surface,#0f1320) 42%) !important;
	border:1px solid var(--ic-line2,#2c3658) !important;
	border-radius:18px !important;
	box-shadow:0 32px 84px -26px rgba(0,0,0,.72) !important;
	color:var(--ic-txt,#eef1fb); font-family:var(--ic-body,'Inter',sans-serif); overflow:hidden;
}
#popupList .modal-body{ padding:24px 24px 22px; position:relative; }
#popupList .aa-list-head{ display:flex; align-items:center; justify-content:space-between; gap:12px; padding-right:36px; }
#popupList .aa-list-title{ margin:0; font-family:var(--ic-disp,'Archivo',sans-serif); font-size:18px; font-weight:700; letter-spacing:-.015em; color:var(--ic-txt,#eef1fb); }
#popupList .aa-list-count{
	font-family:var(--ic-mono,ui-monospace,'SFMono-Regular',Menlo,monospace); font-size:10px; font-weight:700;
	letter-spacing:.13em; text-transform:uppercase; color:var(--ic-mute,#7e89a9);
	background:var(--ic-surface,#0f1320); border:1px solid var(--ic-line,#1f2740); border-radius:999px; padding:4px 11px; white-space:nowrap;
}
#popupList .aa-list-hint{ margin:7px 0 16px; font-size:12.5px; line-height:1.55; color:var(--ic-mute,#7e89a9); }
#popupList .close{
	position:absolute; top:18px; right:18px; opacity:1; z-index:2; width:30px; height:30px; border-radius:9px; padding:0;
	display:flex; align-items:center; justify-content:center;
	background:var(--ic-surface,#0f1320); border:1px solid var(--ic-line,#1f2740); color:var(--ic-mute,#7e89a9); transition:.13s;
}
#popupList .close:hover{ background:var(--ic-coraldim,#ff6a4d22); border-color:var(--ic-coral,#ff6a4d); color:var(--ic-coral,#ff6a4d); }
#popupList .close .sr-only{ position:absolute; width:1px; height:1px; overflow:hidden; }
#popupList #aaListRows{ max-height:48vh; overflow-y:auto; margin:0 -6px; padding:2px 6px; }
#popupList #aaListRows::-webkit-scrollbar{ width:9px; }
#popupList #aaListRows::-webkit-scrollbar-thumb{ background:var(--ic-line2,#2c3658); border-radius:5px; border:2px solid transparent; background-clip:padding-box; }
#popupList #aaListRows::-webkit-scrollbar-thumb:hover{ background:var(--ic-mute,#7e89a9); background-clip:padding-box; }
#popupList .aa-li-row{
	display:flex; align-items:flex-start; gap:10px; margin-bottom:8px; padding:8px 8px 8px 10px;
	background:var(--ic-surface,#0f1320); border:1px solid var(--ic-line,#1f2740); border-radius:12px;
	transition:border-color .16s ease, box-shadow .16s ease;
}
#popupList .aa-li-row:focus-within{ border-color:var(--ic-coral,#ff6a4d); box-shadow:0 0 0 3px var(--ic-coraldim,#ff6a4d22); }
#popupList .aa-li-idx{
	flex:0 0 auto; width:23px; height:23px; margin-top:4px; border-radius:7px;
	display:flex; align-items:center; justify-content:center;
	font-family:var(--ic-mono,ui-monospace,Menlo,monospace); font-size:11px; font-weight:700;
	color:var(--ic-mute,#7e89a9); background:var(--ic-surface2,#141a2b); border:1px solid var(--ic-line,#1f2740);
	user-select:none; transition:.16s;
}
#popupList .aa-li-row:focus-within .aa-li-idx{ color:var(--ic-coral,#ff6a4d); border-color:var(--ic-coral,#ff6a4d); }
#popupList .aa-li-input{
	flex:1 1 auto; min-width:0; resize:none; overflow:hidden; display:block;
	background:transparent; border:0; outline:0; color:var(--ic-txt,#eef1fb);
	font-family:var(--ic-body,'Inter',sans-serif); font-size:14px; line-height:1.5; padding:5px 2px; min-height:26px;
}
#popupList .aa-li-input::placeholder{ color:rgba(126,137,169,.55); }
#popupList .aa-li-btns{ flex:0 0 auto; display:flex; gap:2px; opacity:.42; transition:opacity .16s ease; }
#popupList .aa-li-row:hover .aa-li-btns, #popupList .aa-li-row:focus-within .aa-li-btns{ opacity:1; }
#popupList .aa-li-btns button{
	width:28px; height:28px; border-radius:8px; border:1px solid transparent; background:transparent;
	color:var(--ic-mute,#7e89a9); cursor:pointer; display:flex; align-items:center; justify-content:center; transition:.13s;
}
#popupList .aa-li-btns button:hover{ background:var(--ic-surface2,#141a2b); border-color:var(--ic-line,#1f2740); color:var(--ic-txt,#eef1fb); }
#popupList .aa-li-del:hover{ background:rgba(229,72,77,.14) !important; border-color:rgba(229,72,77,.5) !important; color:#ff7a7d !important; }
#popupList #aaListAdd{
	margin-top:4px; display:flex; align-items:center; justify-content:center; gap:7px;
	background:none; border:1.5px dashed var(--ic-line2,#2c3658); color:var(--ic-txt2,#aab2cd);
	border-radius:11px; padding:11px 12px; width:100%; cursor:pointer; font-family:var(--ic-body,'Inter',sans-serif);
	font-size:13px; font-weight:600; transition:.15s ease;
}
#popupList #aaListAdd svg{ width:15px; height:15px; }
#popupList #aaListAdd:hover{ border-color:var(--ic-coral,#ff6a4d); color:var(--ic-coral,#ff6a4d); background:var(--ic-coraldim,#ff6a4d14); }
#popupList .aa-list-actions{ margin-top:20px; display:flex; justify-content:flex-end; align-items:center; gap:18px; }
#popupList .aa-list-cancel{ background:none; border:none; color:var(--ic-mute,#7e89a9); font-size:11px; font-weight:600; letter-spacing:.09em; text-transform:uppercase; cursor:pointer; transition:.13s; }
#popupList .aa-list-cancel:hover{ color:var(--ic-coral,#ff6a4d); }
#popupList .aa-list-save{
	background:var(--ic-coral,#ff6a4d); border:0; color:#fff; font-weight:600; font-size:13px;
	border-radius:9px; padding:10px 24px; cursor:pointer; transition:.14s ease; box-shadow:0 10px 22px -12px var(--ic-coral,#ff6a4d);
}
#popupList .aa-list-save:hover{ background:var(--ic-coral2,#ff8b73); transform:translateY(-1px); box-shadow:0 14px 28px -12px var(--ic-coral,#ff6a4d); }
</style>
<div class="modal fade" id="popupList" tabindex="-1" role="dialog" aria-hidden="true">
	<div class="modal-dialog" role="document">
		<div class="modal-content">
			<div class="modal-body">
				<button type="button" class="close" data-dismiss="modal">
					<span class="fa fa-close" aria-hidden="true"></span>
					<span class="sr-only">Close</span>
				</button>
				<div class="aa-list-head">
					<h4 class="aa-list-title">Edit list</h4>
					<span class="aa-list-count" id="aaListCount">0 items</span>
				</div>
				<p class="aa-list-hint">Add, edit, reorder, or delete items. Each one keeps its bullet or icon automatically.</p>
				<div id="aaListRows"></div>
				<button type="button" id="aaListAdd">
					<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>
					Add item
				</button>
				<div class="aa-list-actions">
					<button type="button" class="aa-list-cancel" data-dismiss="modal">Cancel</button>
					<button type="button" class="aa-list-save" id="aaListSave">Save</button>
				</div>
			</div>
		</div>
	</div>
</div>

<!-- ── Price-ledger editor. A DEDICATED two-field editor for the "value stack"
     price lists (item name + price + total). One row per item; Save rebuilds the
     rows and writes the total. Scoped to #popupPrice. ── -->
<style id="ic-price-modal-skin">
#popupPrice .modal-dialog{ max-width:600px !important; margin:1.75rem auto; }
#popupPrice .modal-content{
	background:linear-gradient(180deg, var(--ic-surface2,#141a2b) 0%, var(--ic-surface,#0f1320) 42%) !important;
	border:1px solid var(--ic-line2,#2c3658) !important; border-radius:18px !important;
	box-shadow:0 32px 84px -26px rgba(0,0,0,.72) !important;
	color:var(--ic-txt,#eef1fb); font-family:var(--ic-body,'Inter',sans-serif); overflow:hidden;
}
#popupPrice .modal-body{ padding:24px 24px 22px; position:relative; }
#popupPrice .aa-list-head{ display:flex; align-items:center; justify-content:space-between; gap:12px; padding-right:36px; }
#popupPrice .aa-list-title{ margin:0; font-family:var(--ic-disp,'Archivo',sans-serif); font-size:18px; font-weight:700; letter-spacing:-.015em; color:var(--ic-txt,#eef1fb); }
#popupPrice .aa-list-count{ font-family:var(--ic-mono,ui-monospace,Menlo,monospace); font-size:10px; font-weight:700; letter-spacing:.13em; text-transform:uppercase; color:var(--ic-mute,#7e89a9); background:var(--ic-surface,#0f1320); border:1px solid var(--ic-line,#1f2740); border-radius:999px; padding:4px 11px; white-space:nowrap; }
#popupPrice .aa-list-hint{ margin:7px 0 16px; font-size:12.5px; line-height:1.55; color:var(--ic-mute,#7e89a9); }
#popupPrice .close{ position:absolute; top:18px; right:18px; opacity:1; z-index:2; width:30px; height:30px; border-radius:9px; padding:0; display:flex; align-items:center; justify-content:center; background:var(--ic-surface,#0f1320); border:1px solid var(--ic-line,#1f2740); color:var(--ic-mute,#7e89a9); transition:.13s; }
#popupPrice .close:hover{ background:var(--ic-coraldim,#ff6a4d22); border-color:var(--ic-coral,#ff6a4d); color:var(--ic-coral,#ff6a4d); }
#popupPrice .close .sr-only{ position:absolute; width:1px; height:1px; overflow:hidden; }
#popupPrice #aaPriceRows{ max-height:44vh; overflow-y:auto; margin:0 -6px; padding:2px 6px; }
#popupPrice #aaPriceRows::-webkit-scrollbar{ width:9px; }
#popupPrice #aaPriceRows::-webkit-scrollbar-thumb{ background:var(--ic-line2,#2c3658); border-radius:5px; border:2px solid transparent; background-clip:padding-box; }
#popupPrice .aa-price-row{ display:flex; align-items:flex-start; gap:8px; margin-bottom:8px; padding:8px 8px 8px 10px; background:var(--ic-surface,#0f1320); border:1px solid var(--ic-line,#1f2740); border-radius:12px; transition:border-color .16s ease, box-shadow .16s ease; }
#popupPrice .aa-price-row:focus-within{ border-color:var(--ic-coral,#ff6a4d); box-shadow:0 0 0 3px var(--ic-coraldim,#ff6a4d22); }
#popupPrice .aa-price-idx{ flex:0 0 auto; width:23px; height:23px; margin-top:4px; border-radius:7px; display:flex; align-items:center; justify-content:center; font-family:var(--ic-mono,ui-monospace,Menlo,monospace); font-size:11px; font-weight:700; color:var(--ic-mute,#7e89a9); background:var(--ic-surface2,#141a2b); border:1px solid var(--ic-line,#1f2740); user-select:none; transition:.16s; }
#popupPrice .aa-price-row:focus-within .aa-price-idx{ color:var(--ic-coral,#ff6a4d); border-color:var(--ic-coral,#ff6a4d); }
#popupPrice .aa-price-name{ flex:1 1 auto; min-width:0; resize:none; overflow:hidden; display:block; background:transparent; border:0; outline:0; color:var(--ic-txt,#eef1fb); font-family:var(--ic-body,'Inter',sans-serif); font-size:14px; line-height:1.5; padding:5px 2px; min-height:26px; }
#popupPrice .aa-price-name::placeholder,
#popupPrice .aa-price-val::placeholder{ color:rgba(126,137,169,.55); }
#popupPrice .aa-price-val{ flex:0 0 96px; width:96px; margin-top:2px; background:var(--ic-surface2,#141a2b); border:1px solid var(--ic-line,#1f2740); border-radius:8px; color:var(--ic-txt,#eef1fb); font-family:var(--ic-mono,ui-monospace,Menlo,monospace); font-size:13px; font-weight:600; text-align:right; padding:7px 10px; outline:none; }
#popupPrice .aa-price-val:focus{ border-color:var(--ic-coral,#ff6a4d); box-shadow:0 0 0 2px var(--ic-coraldim,#ff6a4d22); }
#popupPrice .aa-li-btns{ flex:0 0 auto; display:flex; gap:2px; opacity:.42; transition:opacity .16s ease; }
#popupPrice .aa-price-row:hover .aa-li-btns, #popupPrice .aa-price-row:focus-within .aa-li-btns{ opacity:1; }
#popupPrice .aa-li-btns button{ width:28px; height:28px; border-radius:8px; border:1px solid transparent; background:transparent; color:var(--ic-mute,#7e89a9); cursor:pointer; display:flex; align-items:center; justify-content:center; transition:.13s; }
#popupPrice .aa-li-btns button:hover{ background:var(--ic-surface2,#141a2b); border-color:var(--ic-line,#1f2740); color:var(--ic-txt,#eef1fb); }
#popupPrice .aa-li-del:hover{ background:rgba(229,72,77,.14) !important; border-color:rgba(229,72,77,.5) !important; color:#ff7a7d !important; }
#popupPrice #aaPriceAdd{ margin-top:4px; display:flex; align-items:center; justify-content:center; gap:7px; background:none; border:1.5px dashed var(--ic-line2,#2c3658); color:var(--ic-txt2,#aab2cd); border-radius:11px; padding:11px 12px; width:100%; cursor:pointer; font-family:var(--ic-body,'Inter',sans-serif); font-size:13px; font-weight:600; transition:.15s ease; }
#popupPrice #aaPriceAdd svg{ width:15px; height:15px; }
#popupPrice #aaPriceAdd:hover{ border-color:var(--ic-coral,#ff6a4d); color:var(--ic-coral,#ff6a4d); background:var(--ic-coraldim,#ff6a4d14); }
#popupPrice #aaPriceTotalWrap{ display:flex; align-items:center; gap:8px; margin-top:14px; padding:12px 10px 0; border-top:1px solid var(--ic-line,#1f2740); }
#popupPrice .aa-price-total-tag{ flex:0 0 auto; font-family:var(--ic-mono,ui-monospace,Menlo,monospace); font-size:10px; font-weight:700; letter-spacing:.12em; text-transform:uppercase; color:var(--ic-mute,#7e89a9); }
#popupPrice #aaPriceTotalLabel{ flex:1 1 auto; min-width:0; background:var(--ic-surface2,#141a2b); border:1px solid var(--ic-line,#1f2740); border-radius:8px; color:var(--ic-txt,#eef1fb); font-family:var(--ic-body,'Inter',sans-serif); font-size:13px; padding:8px 11px; outline:none; }
#popupPrice #aaPriceTotalValue{ flex:0 0 112px; width:112px; background:var(--ic-surface2,#141a2b); border:1px solid var(--ic-line,#1f2740); border-radius:8px; color:var(--ic-txt,#eef1fb); font-family:var(--ic-mono,ui-monospace,Menlo,monospace); font-size:14px; font-weight:700; text-align:right; padding:8px 11px; outline:none; }
#popupPrice #aaPriceTotalLabel:focus, #popupPrice #aaPriceTotalValue:focus{ border-color:var(--ic-coral,#ff6a4d); box-shadow:0 0 0 2px var(--ic-coraldim,#ff6a4d22); }
#popupPrice .aa-list-actions{ margin-top:20px; display:flex; justify-content:flex-end; align-items:center; gap:18px; }
#popupPrice .aa-list-cancel{ background:none; border:none; color:var(--ic-mute,#7e89a9); font-size:11px; font-weight:600; letter-spacing:.09em; text-transform:uppercase; cursor:pointer; transition:.13s; }
#popupPrice .aa-list-cancel:hover{ color:var(--ic-coral,#ff6a4d); }
#popupPrice .aa-list-save{ background:var(--ic-coral,#ff6a4d); border:0; color:#fff; font-weight:600; font-size:13px; border-radius:9px; padding:10px 24px; cursor:pointer; transition:.14s ease; box-shadow:0 10px 22px -12px var(--ic-coral,#ff6a4d); }
#popupPrice .aa-list-save:hover{ background:var(--ic-coral2,#ff8b73); transform:translateY(-1px); box-shadow:0 14px 28px -12px var(--ic-coral,#ff6a4d); }
</style>
<div class="modal fade" id="popupPrice" tabindex="-1" role="dialog" aria-hidden="true">
	<div class="modal-dialog" role="document">
		<div class="modal-content">
			<div class="modal-body">
				<button type="button" class="close" data-dismiss="modal">
					<span class="fa fa-close" aria-hidden="true"></span>
					<span class="sr-only">Close</span>
				</button>
				<div class="aa-list-head">
					<h4 class="aa-list-title">Edit price list</h4>
					<span class="aa-list-count" id="aaPriceCount">0 items</span>
				</div>
				<p class="aa-list-hint">Each row is an item name and its price. Add, edit, reorder, or delete rows, then set the total.</p>
				<div id="aaPriceRows"></div>
				<button type="button" id="aaPriceAdd">
					<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>
					Add item
				</button>
				<div id="aaPriceTotalWrap">
					<span class="aa-price-total-tag">Total</span>
					<input type="text" id="aaPriceTotalLabel" spellcheck="false" placeholder="Total value">
					<input type="text" id="aaPriceTotalValue" spellcheck="false" placeholder="$0">
				</div>
				<div class="aa-list-actions">
					<button type="button" class="aa-list-cancel" data-dismiss="modal">Cancel</button>
					<button type="button" class="aa-list-save" id="aaPriceSave">Save</button>
				</div>
			</div>
		</div>
	</div>
</div>

<!-- Publish Modal-->
<!-- Modal -->
<!-- Modal -->
<div class="modal SmsModal fade" id="popup2" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
		
        <div class="modal-content">
           
			<div class="modal-header d-flex justify-content-between px-0 pt-0">
				<h5 class="heading_color f35 lh45 sf-pro-display " id="">Publishing Option</h5>
				<button type="button" class="close publishClose" data-dismiss="modal" aria-label="Close">
					<span aria-hidden="true">&times;</span>
				</button>
			</div>

            <?php
                // Distribution (Embed widget · HTML form · Custom domain · Iframe
                // embed) all moved to the dedicated Share & Publish page — the modal
                // now links there instead of duplicating those surfaces.
                // $distribute_url is set by Sites::editFunnelPage.
                $__distribute_url = isset($distribute_url) ? $distribute_url : '';
            ?>
            <ul class="nav nav-tabs">
                <li><a class="active" data-toggle="tab" href="#tab1">Published Link</a></li>
                <?php if ($__distribute_url !== ''): ?>
                    <li><a href="<?= html_escape($__distribute_url) ?>" target="_blank" rel="noopener">Share &amp; Publish ↗</a></li>
                <?php endif; ?>
            </ul>

            <div class="tab-content py-5">

                <div id="tab1" class="tab-pane fade active show lof-tab-pane-publish">
                    <?php
                        $__pm_full_link = '';
                        $__pm_slug      = '';
                        $__pm_funnel_id = 0;
                        if (isset($templatedata) && !empty($templatedata[0]['Data'])) {
                            $__pm_d = $templatedata[0]['Data'];
                            $__pm_funnel_id = isset($__pm_d->id) ? (int) $__pm_d->id : 0;
                            $__pm_slug = isset($__pm_d->slug) ? $__pm_d->slug : '';
                            if (isset($funnel_type) && $funnel_type == 'landing') {
                                $__pm_full_link = $__pm_d->publish_link;
                            } else {
                                $__pm_full_link = $__pm_d->thankyou_page_link;
                            }
                        }
                        // Bridge templates (201..206) get the redesigned "launch console"
                        // layout (styled in pages/_ic_editor_skin.php). Others keep the
                        // legacy centered note. All JS hooks (.slug-input/.slug-status/
                        // #copylink/.publish_link/.publish_link_url) are preserved in both.
                        $__pm_is_bridge = isset($count) && (int) $count >= 201 && (int) $count <= 210;
                    ?>
                    <?php if ($__pm_is_bridge): ?>
                    <!-- Redesigned: dispatch-console layout for Agentic Affiliates Bridges. -->
                    <div class="ic-pub">
                      <div class="ic-pub-status">
                        <span class="ic-pub-live"><span class="ic-pub-dot"></span>Live</span>
                        <span class="ic-pub-statustxt">Your Bridge is published &amp; catching leads</span>
                        <a class="ic-pub-open publish_link_url" href="<?= $__pm_full_link ?>" target="_new">Open&nbsp;↗</a>
                      </div>

                      <div class="ic-pub-urlcard">
                        <div class="ic-pub-urlhead">
                          <span class="ic-pub-urllabel">Public URL</span>
                          <button type="button" class="ic-pub-copy" id="copylink" title="Copy full URL">
                            <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="11" height="11" rx="2"/><path d="M5 15V5a2 2 0 0 1 2-2h10"/></svg>
                            <span class="ic-pub-copytxt">Copy</span>
                          </button>
                        </div>
                        <!-- The URL as an editable terminal readout: fixed /webpages/ prefix
                             + the slug as the only editable segment. -->
                        <div class="ic-pub-urlline">
                          <span class="ic-pub-host"><?= rtrim(str_replace(array('http://','https://'), '', base_url()), '/') ?>/webpages/</span>
                          <input type="text" class="slug-input ic-pub-slug"
                                 value="<?= htmlspecialchars($__pm_slug, ENT_QUOTES) ?>"
                                 data-funnel-id="<?= $__pm_funnel_id ?>"
                                 data-original="<?= htmlspecialchars($__pm_slug, ENT_QUOTES) ?>"
                                 placeholder="my-business-page"
                                 maxlength="190" autocomplete="off" spellcheck="false"
                                 aria-label="Page URL slug" size="18">
                          <span class="ic-pub-edit" aria-hidden="true">
                            <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 20h4L18 10l-4-4L4 16v4z"/><path d="M13.5 6.5l4 4"/></svg>
                          </span>
                        </div>
                        <div class="slug-status ic-pub-status-msg" aria-live="polite"></div>
                      </div>

                      <p class="ic-pub-hint">Tap the slug to rename your link. Changes save when you hit <b>Done</b>.</p>
                      <input type="hidden" class="publish_link" value="<?= $__pm_full_link ?>">
                      <!-- Keep the legacy preview node present (some JS reads it) but hidden. -->
                      <code class="slug-preview-url" style="display:none"><?= htmlspecialchars($__pm_full_link, ENT_QUOTES) ?></code>
                    </div>
                    <?php else: ?>
                    <h5 class="publish-note text-center heading_color f25 lh45 sf-pro-display">
                        <img src="<?= base_url() ?>assets/new-images/access.svg" width="50" height="50" alt="Publish Note Icon">
						<br>
                        Your page is Published.
						<br>
						You can <a class="publish_link_url" href="<?= $__pm_full_link ?>" target="_new"> View here</a>
                    </h5>

                    <div class="mt-4 slug-editor">
                        <label class="slug-editor-label">Page URL Slug</label>
                        <div class="slug-editor-row">
                            <span class="slug-editor-prefix" title="<?= base_url('webpages/') ?>">/webpages/</span>
                            <input type="text" class="form-control slug-input"
                                   value="<?= htmlspecialchars($__pm_slug, ENT_QUOTES) ?>"
                                   data-funnel-id="<?= $__pm_funnel_id ?>"
                                   data-original="<?= htmlspecialchars($__pm_slug, ENT_QUOTES) ?>"
                                   placeholder="my-business-page"
                                   maxlength="190" autocomplete="off"
                                   aria-label="Page URL slug">
                            <a href="#" id="copylink" title="Copy full URL">
                                <img src="<?= base_url() ?>assets/new-images/copy.svg" width="20" height="20" alt="Copy Link Icon">
                            </a>
                        </div>
                        <div class="slug-status" aria-live="polite"></div>
                        <div class="slug-preview-row">
                            <span class="slug-preview-label">Full URL</span>
                            <code class="slug-preview-url"><?= htmlspecialchars($__pm_full_link, ENT_QUOTES) ?></code>
                        </div>
                        <input type="hidden" class="publish_link" value="<?= $__pm_full_link ?>">
                    </div>
                    <?php endif; ?>
                </div>

            </div>

            <div class="modal-footer  py-0 justify-content-center">
                <button type="button" class="btn_1 publishDone">
					Done
					<img src="<?= base_url() ?>assets/new-images/arrow.svg"  alt="Done Icon">
				</button>
                <button type="button" class="text-color btn-modal-cancel publishClose" data-dismiss="modal">Cancel</button>
            </div>

        </div>
    </div>
</div>

<!-- Gallery Modal-->
<style>
	/* ── "Choose an Image" modal — refreshed, on-brand (coral accent) ──────────
	   Scoped to #popup3 so nothing leaks into the rest of the editor. Classes/IDs
	   the editor JS depends on are all preserved; this is styling only. */
	#popup3 .modal-dialog{max-width:640px}
	#popup3 .modal-content{border:0;border-radius:16px;overflow:hidden;box-shadow:0 30px 80px -30px rgba(42,30,24,.5)}
	#popup3 .modal-header{align-items:center;border-bottom:1px solid #EBD8CF;padding:16px 20px;background:#fff}
	#popup3 .modal-title{font-family:'Outfit','Manrope',sans-serif;font-weight:600;font-size:18px;color:#1F1712;letter-spacing:-.3px;margin:0}
	#popup3 .modal-header .close{opacity:.6;transition:opacity .15s;padding:0;margin:0}
	#popup3 .modal-header .close:hover{opacity:1}
	#popup3 .modal-header .close img{width:14px;height:14px}
	#popup3 .modal-body{padding:16px 20px 20px}

	/* Tabs → pill segmented control */
	#popup3 .nav-tabs.menu_m_2{border:0;background:#F7F0EB;border-radius:999px;padding:4px;gap:2px;display:inline-flex;margin-bottom:6px}
	#popup3 .nav-tabs.menu_m_2 .nav-item{margin:0}
	#popup3 .nav-tabs.menu_m_2 .nav-link{border:0;border-radius:999px;padding:7px 18px;font-family:'Outfit','Manrope',sans-serif;
		font-size:13.5px;font-weight:500;color:#5F5148;cursor:pointer;transition:.15s}
	#popup3 .nav-tabs.menu_m_2 .nav-link:hover{color:#1F1712}
	#popup3 .nav-tabs.menu_m_2 .nav-link.active{background:#2A1E18;color:#fff;font-weight:600}

	/* Scroll only vertically — the Bootstrap .row negative margins made the grid
	   wider than the pane and triggered a horizontal scrollbar. Clip X and cancel
	   the row's negative side margins so columns fit the pane exactly. */
	#popup3 .tab-content{max-height:440px;overflow-y:auto;overflow-x:hidden;padding-top:12px}
	#popup3 .tab-content .row{margin-left:0;margin-right:0}
	#popup3 .tab-content .row > [class*="col-"]{padding-left:6px;padding-right:6px}
	#popup3 hr{border-color:#EBD8CF;margin:16px 0}
	#popup3 .imgform label{font-size:11px;font-weight:600;letter-spacing:.04em;text-transform:uppercase;color:#8E7E74;margin-bottom:3px}
	#popup3 .imgform .form-control{border:1px solid #E1C9BD;border-radius:9px;font-size:13.5px}
	#popup3 .imgform .form-control:focus{border-color:#F0755E;box-shadow:0 0 0 3px rgba(240,117,94,.16)}
	#popup3 .descrip_img p{font-size:12px;color:#8E7E74;margin:2px 0}
	#popup3 figure{margin:0}
	#popup3 .img_full{border-radius:10px;border:1px solid #EBD8CF;max-width:100%}

	/* "My Images" uploaded-image grid (populated by sites/fetchMyImages). */
	#MyImages .my_img_grid ul,#MyImages .my_uploaded_images{list-style:none;margin:0;padding:0}
	#MyImages .my_img_grid ul{display:grid;grid-template-columns:repeat(auto-fill,minmax(96px,1fr));gap:10px}
	#MyImages .my_img_grid li{margin:0;border:2px solid transparent;border-radius:8px;overflow:hidden;
		cursor:pointer;background:#f4f4f4;transition:border-color .15s,transform .15s;position:relative}
	#MyImages .my_img_grid li:hover{transform:translateY(-2px);border-color:#F0755E}
	#MyImages .my_img_grid li.is_active{border-color:#E75C43;box-shadow:0 0 0 3px rgba(240,117,94,.25)}
	#MyImages .my_img_grid li a{display:block;line-height:0}
	#MyImages .my_img_grid li img{width:100%;height:88px;object-fit:cover;display:block}
	#MyImages .myimages_gallery{max-height:230px;overflow-y:auto;padding:2px}

	/* Pixabay search box + results */
	#PremiumImages .input-group .form-control{border:1px solid #E1C9BD;border-radius:10px 0 0 10px;font-size:13.5px}
	#PremiumImages .input-group .form-control:focus{border-color:#F0755E;box-shadow:none}
	#PremiumImages .searchpixabayimage{background:#2A1E18;border:0;color:#fff;border-radius:0 10px 10px 0;font-weight:600;padding:0 20px}
	#PremiumImages .searchpixabayimage:hover{background:#1F1712}
	#PremiumImages .img_lg_col img{border-radius:10px;border:2px solid transparent;cursor:pointer;transition:.15s;width:100%;height:110px;object-fit:cover}
	#PremiumImages .addselectedimage:hover img{border-color:#F0755E;transform:translateY(-2px)}
	#PremiumImages .loadmore{background:#F7F0EB;border:1px solid #EBD8CF;color:#5F5148;border-radius:999px;font-weight:600;padding:8px 20px}
	#PremiumImages .loadmore:hover{border-color:#F0755E;color:#1F1712}

	/* Upload dropzone */
	#Upload .uploadImg{padding:44px 24px !important;border:2px dashed #E1C9BD !important;border-radius:14px;
		background:#F7F0EB;text-align:center;transition:.15s}
	#Upload .uploadImg:hover{border-color:#F0755E !important;background:#FBF6F2}
	#Upload .dropzone__btn{background:linear-gradient(135deg,#F0755E,#E75C43);border:0;color:#fff;font-family:'Outfit','Manrope',sans-serif;
		font-weight:600;font-size:14px;border-radius:999px;padding:11px 26px;cursor:pointer;box-shadow:0 12px 26px -14px rgba(240,117,94,.6)}
	#Upload .dropzone__btn:hover{filter:brightness(1.05)}
	#Upload .dropzone__file-size-cap{display:inline-block;margin-top:12px;font-size:12px;color:#8E7E74}

	/* Primary action buttons — one coral system */
	#popup3 .insertbtn,#popup3 .useimage{background:linear-gradient(135deg,#F0755E,#E75C43) !important;border:0 !important;color:#fff !important;
		font-family:'Outfit','Manrope',sans-serif;font-weight:600;border-radius:11px;padding:12px;letter-spacing:.2px;
		box-shadow:0 14px 28px -14px rgba(240,117,94,.55)}
	#popup3 .insertbtn:hover,#popup3 .useimage:hover{filter:brightness(1.05)}

	/* Pixabay search: full width. The markup carries Bootstrap offset-xl-2/lg-2
	   which add a left margin that pushed the search bar past the pane (horizontal
	   scroll). Zero the offset + margins so it fits exactly. */
	#PremiumImages .input-group{max-width:none}
	#PremiumImages .col-md-8{flex:0 0 100%;max-width:100%;margin:0 !important;padding:0}
	#PremiumImages .offset-xl-2,#PremiumImages .offset-lg-2{margin-left:0 !important}
	#PremiumImages .img_lg_col{margin-bottom:2px}
	/* Selected-image preview (Title/Alt + size note) stays hidden until a result is
	   chosen — showing an empty preview + fields on an unsearched tab looked broken.
	   editor.php's .addselectedimage handler removes .is-empty when one is picked. */
	#selectedDiv.is-empty,#popup3 .pix-sel-sep.is-empty{display:none}
	#popup3 .descrip_img .cu_imageSizediv,#popup3 .descrip_img .pimagename:empty{display:none}
	#popup3 .my_uploaded_images .text-muted{color:#8E7E74;font-size:13px}
</style>
<div class="modal fade popup_bx_all" id="popup3" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h3 class="modal-title letter_space text_inherit" >Choose an Image</h3>
				<button type="button" class="close close_image_gallery" data-dismiss="modal" aria-label="Close">
					<img src="<?php echo base_url('assets/app/ui/images/close.png');?>"  alt="img">
				</button>
			</div>
			<div class="modal-body">
				<ul class="nav nav-tabs menu_m_2">
					<li class="nav-item">
						<a class="nav-link active" data-toggle="tab" href="#MyImages" id="galimages">My Images</a>
					</li>
					<li class="nav-item">
						<a class="nav-link" data-toggle="tab" href="#PremiumImages">Pixabay</a>
					</li>
					<li class="nav-item">
						<a class="nav-link" data-toggle="tab" href="#Upload">Upload</a>
					</li>
				</ul>
				<div class="tab-content" style="padding-top: 10px; max-height: 420px; overflow-y: scroll;">
					<div class="tab-pane  active" id="MyImages">
						<!-- Uploaded-image gallery: fetched from sites/fetchMyImages when the
						     modal opens (see editor.php loadMyImages()). Click a thumb to load
						     it into the current-image preview below. -->
						<div class="myimages_gallery">
							<div class="row w-100 my_uploaded_images">
								<div class="col-sm-12 text-center py-3 text-muted">Loading your images…</div>
							</div>
							<div class="clearfix"></div>
						</div>
						<hr>
						<div class="slct_img_sec">
							<div class="row w-100">
								<div class="col-md-6">
									<div class="thmnl_lf">
										<figure>
											<a href="javascript:void(0);" class="close_img currentimageid d-none" data-id="">
												<img src="<?php echo base_url('assets/app/ui/images/close2.png');?>"  alt="img">
											</a>
											<img src="" class="img_full currentimage"  alt="img">
										</figure>
									</div>
								</div>
								<div class="col-md-6">
									<form class="imgform">
										<p><label>Title</label><input type="text" class="form-control txt1 ptitle" placeholder=""></p>
										<p><label>Alt Text</label><input type="text" class="form-control txt1 alttext" placeholder=""></p>
									</form>
								</div> 
								<div class="col-md-12">
									<div class="descrip_img">
										<h6>
											<span class="pimagename"></span><br>
											<p class="cu_imageSizediv" class="p-0 mt-2" style="display:none;">
												Current Image Size: <span class="current_image_size"></span>
											</p>
											<p>Recommended Image Size: <span class="recommended_size"></span></p>
										</h6>
									</div>
								</div>


								<div class="col-md-12 note_rw mt-3 d-none"></div>
							</div>
						</div>
						<input type="hidden" id="currentobj" value="0" />
						<div class="btm_r_img rw mx-2 mb-4"><input name="" type="button" value="Insert" class="btn btn-block btm_btn insertbtn"></div>
					</div>
					<div class="tab-pane  fade" id="PremiumImages">
						<div class="col-md-8 offset-xl-2 offset-lg-2 text-center my-3">
							<div class="input-group">
								<input type="text" class="form-control"  id="pixkeyword" placeholder="Enter Your Keyword">
								<div class="input-group-append">
									<button class="btn btn-secondary searchpixabayimage">Search</button>
								</div>
							</div>
						</div>
						<div class="myimg_gallery">
							<div class="row w-100"></div>
							<div class="clearfix"></div>
						</div>
						<hr class="pix-sel-sep is-empty">
						<div class="slct_img_sec is-empty" id="selectedDiv">
							<div class="row w-100">
								<div class="col-md-6">
									<div class="thmnl_lf">
										<figure>
											<a href="javascript:void(0);" class="close_img currentpiximg d-none" data-id="">
												<img src="<?php echo base_url('assets/app/ui/images/close2.png');?>"  alt="img">
											</a>
											<img src="" class="img_full currentimage"  alt="img">
										</figure>
									</div>
								</div>
								<div class="col-md-6">
									<form class="imgform">
										<p><label>Title</label><input type="text" class="form-control txt1 ptitle" placeholder=""></p>
										<p><label>Alt Text</label><input type="text" class="form-control txt1 palttext" placeholder=""></p>
									</form>
								</div> 

								<div class="col-md-12">
									<div class="descrip_img">
										<h6>
										<span class="pimagename"></span><br>
										<p class="cu_imageSizediv" class="p-0 mt-2" style="display:none;">
											Current Image Size: <span class="current_image_size"></span>
										</p>
										<p>Recommended Image Size: <span class="recommended_size"></span></p>
										</h6>
									</div>
								</div>



								<div class="col-md-12 note_rw mt-3 d-none"></div>
							</div>
						</div>
						<div class="btm_r_img rw"><input type="button" value="Use This Image" class="btn btn-success btn-block btm_btn useimage"></div>
					</div>
					<div class="tab-pane fade" id="Upload">
						<div class="text-center">
							<!--h3 class="title2">Drag Image here</h3>
							<div class="thmnl_upload mt-4 mb-5">
								<img src="<?php echo base_url('assets/app/images/pic.png')?>" class="img_responsive"  alt="img">
							</div> 
							<div class="inputfile mb-4"><input name="" type="file"></div>
							<div class="grytxt1 mb-5">Max 15 MB</div>
							<div class="grytxt1 recommended">Recommended maximum image width: 1200px. 
								<a href="#" class="link1">Read More</a>
							</div>-->
							<div style="overflow:auto!important">
									<div class="dz-message uploadImg" style="cursor: pointer;">
										<button type="button" class="ui-btn ui-btn--outlined dropzone__btn">
										  <i class="fa fa-upload"></i> Upload an image
										</button>
										<br>
										<span class="dropzone__file-size-cap">JPG, PNG, GIF or WEBP &middot; max 15MB</span>

									</div>
									<input type="file"  class="dz-hidden-input hiddenfile" accept="image/jpeg,image/png,image/gif,image/webp" style="visibility: hidden; position: absolute; top: 0px; left: 0px; height: 0px; width: 0px;" name="file">

								<span style="color:#FF0000"></span>
								<div id="recommendation"></div>
								<div id="normrecommendation">
									<p class="image-upload-message">
										
									</p>
								</div>
							</div>
						</div>
					</div> 
				</div>
			</div>
		</div>
  </div>
</div>

<!-- Publish Modal-->
<div class="modal fade popup_bx_all" id="popup4" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
	<div class="modal-dialog" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h3 class="modal-title letter_space text_inherit" >Automation Options</h3>
				<button type="button" class="close" data-dismiss="modal" aria-label="Close">
					<img src="<?php echo base_url('assets/app/ui/images/close.png');?>"  alt="img">
				</button>
			</div>
			<div class="modal-body">
				<div class="col-sm-12">
					<div class="container">
						<p class="mb-2">Enter Your Main Keyword</p>
						<input type="text" name="brand" class="brand form-control mb-1" value="<?php if(isset($templatedata)){echo $templatedata[0]['Data']->brand_name;}?>"/>
						<small id="brandhelp" class="form-text text-danger" style="display:none;">Please Enter Your Main Keyword.</small>
						<p class="mt-4 mb-1">Keywords</p>
						<textarea name="" cols="" rows="4" class="form-control txt1 textarea mb-4"><?php if(isset($templatedata) && $templatedata[0]['Data']->keywords !=''){$kw = @unserialize($templatedata[0]['Data']->keywords); echo $kw !== false ? implode(",", $kw) : $templatedata[0]['Data']->keywords;}?></textarea>
						<small id="keywordshelp" class="form-text text-danger" style="display:none;">Please Enter Your Keywords.</small>
						<input type="button" value="Automate" class="mt-4 btn btn2 btn3 btn-block automate">
					</div>
				</div>
			</div>
		</div>
	</div>
</div>

<script type="text/javascript">
	$(document).ready(function() {
		// Close modal when the button with class 'fa-close' is clicked
		$('.selectPopup .close').on('click', function() {
			$('#contentModal').modal('hide');
		});

		$('.publishClose').on('click', function() {
			$('#popup2').modal('hide');
		});

		// Distribution (widget / HTML form / custom domain) moved to the Share &
		// Publish page. Done closes the publish modal, then reloads the editor so
		// it re-pulls the freshly-saved/published state (restores the old behaviour).
		// The small delay lets the slug-save AJAX (bound in editor.php) finish first.
		$('.publishDone').on('click', function() {
			$('#popup2').modal('hide');
			setTimeout(function(){ window.location.reload(); }, 400);
		});

	});

</script>