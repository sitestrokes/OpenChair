<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * SHARED ICON MAP — the single source of truth for Agentic Affiliates nav/tool glyphs.
 *
 * This is the engine-wide home (application/views/ic_shell/) so ANY module can
 * render the dark Agentic Affiliates shell. Every screen that draws an icon button
 * (the sidebar nav in ic_shell/layout.php, the per-campaign tool bar in
 * instantclose/pages/campaigns.php, …) reads from HERE, so a label and its glyph
 * can never drift apart.
 *
 * Each value is the INNER markup of a 24×24 stroked <svg> (viewBox 0 0 24 24,
 * fill=none, stroke=currentColor). Keys mirror the destinations they open.
 *
 * Usage:
 *   require_once APPPATH . 'views/ic_shell/_icons.php';   // from layout.php
 *   echo '<svg viewBox="0 0 24 24" ...>' . ic_icon('cockpit') . '</svg>';
 *   $map = ic_icons();                                    // whole array if needed
 */
if (!function_exists('ic_icons')) {
    /**
     * @return array<string,string> key => inner-SVG markup
     */
    function ic_icons()
    {
        static $icons = null;
        if ($icons !== null) { return $icons; }
        $icons = [
            'home'        => '<path d="M3 11l9-7 9 7M5 10v10h14V10"/>',
            'build'       => '<path d="M12 5v14M5 12h14"/>',
            'templates'   => '<rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/>',
            'radar'       => '<circle cx="12" cy="12" r="9"/><circle cx="12" cy="12" r="4"/><path d="M12 3v3M12 18v3"/>',
            'campaigns'   => '<path d="M12 3l9 5-9 5-9-5 9-5zM3 13l9 5 9-5"/>',
            'vault'       => '<rect x="4" y="10" width="16" height="11" rx="2"/><path d="M8 10V7a4 4 0 018 0v3"/>',
            'moderation'  => '<path d="M12 3l7 3v5c0 4.4-3 7.4-7 8.9C8 17.4 5 14.4 5 10V6l7-3z"/><path d="M9.3 11.6l1.8 1.8 3.6-3.9"/>',
            'sequences'   =>'<circle cx="6" cy="6" r="2.5"/><circle cx="6" cy="18" r="2.5"/><circle cx="18" cy="12" r="2.5"/><path d="M8 6h4a4 4 0 014 4M8 18h4a4 4 0 004-4"/>',
            'seqedit'     => '<path d="M4 7h10M4 12h7M4 17h5"/><path d="M14.5 14.5l5-5 2 2-5 5-2.5.5z"/>',
            'connect'     => '<path d="M9 12h6M8 8l-3 3 3 3M16 8l3 3-3 3"/><rect x="3" y="4" width="18" height="16" rx="3"/>',
            'calls'       => '<path d="M5 4h4l2 5-3 2a12 12 0 005 5l2-3 5 2v4a2 2 0 01-2 2A16 16 0 013 6a2 2 0 012-2z"/>',
            'cockpit'     => '<path d="M4 20V10M10 20V4M16 20v-7M22 20H2"/>',
            'automations' => '<path d="M13 2L3 14h7l-1 8 10-12h-7l1-8z"/>',
            'traffic'     => '<path d="M3 12h4l3 8 4-16 3 8h4"/>',
            'mirror'      => '<rect x="6" y="3" width="12" height="18" rx="3"/><path d="M12 7v10M9.5 9.5L12 7l2.5 2.5"/>',
            'compliance'  => '<path d="M12 3l8 4v5c0 4.5-3.2 7.7-8 9-4.8-1.3-8-4.5-8-9V7l8-4z"/><path d="M9 12l2 2 4-4"/>',
            'flagged'     => '<path d="M4 21V4h13l-2 4 2 4H4"/>',
            'observability' => '<path d="M3 12h3l2-6 4 14 3-9 2 4h4"/><circle cx="12" cy="12" r="9"/>',
            'monitoring'  => '<path d="M17 21v-8M12 21V3M7 21v-5"/>',
            'demosessions' => '<path d="M12 3a3 3 0 00-3 3v5a3 3 0 006 0V6a3 3 0 00-3-3z"/><path d="M18 11v1a6 6 0 01-12 0v-1M12 18v3"/>',
            'users'       => '<circle cx="9" cy="7" r="3.2"/><path d="M2.5 21a6.5 6.5 0 0113 0"/><path d="M16.5 3.6a3.2 3.2 0 010 6.8M22 21a6.5 6.5 0 00-5-6.3"/>',
            'profile'     => '<circle cx="12" cy="8" r="4"/><path d="M5 21a7 7 0 0114 0"/>',
            'abuse'       => '<path d="M12 9v4M12 17h.01M10.3 3.9l-8 14A1.5 1.5 0 003.7 20h16.6a1.5 1.5 0 001.4-2.1l-8-14a1.5 1.5 0 00-2.8 0z"/>',
            'apicosts'    => '<circle cx="12" cy="12" r="9"/><path d="M14.5 9.5a2.5 2.5 0 00-2.5-1.5c-1.4 0-2.5.7-2.5 2s1.1 1.8 2.5 2 2.5.8 2.5 2-1.1 2-2.5 2a2.5 2.5 0 01-2.5-1.5M12 6v12"/>',
            'agency'      => '<circle cx="9" cy="8" r="3"/><path d="M3 20a6 6 0 0112 0"/><circle cx="17.5" cy="9.5" r="2.2"/><path d="M15 20a5 5 0 016-4.6"/>',
            'clients'     => '<path d="M3 21V8l6-4 6 4v13"/><path d="M15 21V11l6 4v6"/><path d="M7 11h.01M11 11h.01M7 15h.01M11 15h.01"/>',
            'team'        => '<circle cx="9" cy="7" r="3"/><path d="M3 21a6 6 0 0112 0"/><path d="M16 3.5a3 3 0 010 7M21 21a6 6 0 00-5-5.9"/>',
            'whitelabel'  => '<path d="M12 3l2.2 6.6H21l-5.4 4 2 6.6L12 16.4 6.4 20.2l2-6.6L3 9.6h6.8L12 3z"/>',
            'settings'    => '<circle cx="12" cy="12" r="3"/><path d="M19 12a7 7 0 00-.1-1l2-1.5-2-3.5-2.3 1a7 7 0 00-1.7-1L14.5 2h-5l-.4 2.5a7 7 0 00-1.7 1l-2.3-1-2 3.5L3.1 11a7 7 0 000 2l-2 1.5 2 3.5 2.3-1a7 7 0 001.7 1l.4 2.5h5l.4-2.5a7 7 0 001.7-1l2.3 1 2-3.5-2-1.5a7 7 0 00.1-1z"/>',
            'api'         => '<path d="M16 18l6-6-6-6M8 6l-6 6 6 6"/>',
            'config'      => '<line x1="4" y1="7" x2="20" y2="7"/><line x1="4" y1="12" x2="20" y2="12"/><line x1="4" y1="17" x2="20" y2="17"/><circle cx="9" cy="7" r="2"/><circle cx="15" cy="12" r="2"/><circle cx="8" cy="17" r="2"/>',
            'logo'        => '<path d="M12 2a7 7 0 00-7 7v3.5L3.5 16a1 1 0 00.9 1.4H8a4 4 0 008 0h3.6a1 1 0 00.9-1.4L19 12.5V9a7 7 0 00-7-7z"/>',
            'tutorials'   => '<circle cx="12" cy="12" r="10"/><path d="M9.1 9a3 3 0 015.8 1c0 2-3 3-3 3M12 17h.01"/>',
            'guide'       => '<path d="M4 19.5A2.5 2.5 0 016.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 014 19.5v-15A2.5 2.5 0 016.5 2z"/>',

            // ── Card / tool-bar glyphs that aren't sidebar destinations ──
            'dossier'     => '<path d="M12 3l9 5-9 5-9-5 9-5zM3 13l9 5 9-5"/>',          // same as 'campaigns' — the Bridge overview
            'copyvoice'   => '<path d="M9 18a4 4 0 004-4V5a3 3 0 00-6 0v9a4 4 0 002 4z"/><path d="M5 11a7 7 0 0014 0M12 18v3"/>', // mic = AI voice (greeting/persona/engine)
            'chatbot'     => '<path d="M21 11.5a8.38 8.38 0 01-.9 3.8 8.5 8.5 0 01-7.6 4.7 8.38 8.38 0 01-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 01-.9-3.8 8.5 8.5 0 014.7-7.6 8.38 8.38 0 013.8-.9h.5a8.48 8.48 0 018 8v.5z"/>', // chat bubble = per-Bridge text chatbot (ic_040)
            'pagecontent' => '<path d="M6 3h9l4 4v14a0 0 0 010 0H6a0 0 0 010 0V3z"/><path d="M14 3v5h5M9 13h6M9 17h6"/>', // doc = opt-in page copy
            'extlink'     => '<path d="M14 3h7v7M21 3l-9 9M10 5H5a2 2 0 00-2 2v12a2 2 0 002 2h12a2 2 0 002-2v-5"/>', // open external (live Bridge)

            // ── Emoji replacements — onboarding steps, next-moves, live-feed lanes ──
            'key'         => '<circle cx="8" cy="15" r="4"/><path d="M10.8 12.2L21 2M17 6l2 2M14 9l2 2"/>',                    // was 🔑
            'bridge'      => '<path d="M3 13a9 9 0 0118 0M3 13v3M21 13v3M3 20c2 0 2-1.5 4-1.5s2 1.5 4 1.5 2-1.5 4-1.5 2 1.5 4 1.5"/>', // 🌉 arch bridge over water
            'rocket'      => '<path d="M5 15l-2 4 4-2M9 15l6-6a8 8 0 004-6 8 8 0 00-6 4l-6 6M9 15l-2-2M9 15l2 2"/><circle cx="14.5" cy="9.5" r="1.3"/>', // was 🚀
            'link'        => '<path d="M9 12h6M8.5 8H7a4 4 0 000 8h1.5M15.5 8H17a4 4 0 010 8h-1.5"/>',                          // was 🔗
            'share'       => '<circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><path d="M8.6 13.5l6.8 4M15.4 6.5l-6.8 4"/>', // share & publish (three-node)
            'browser'     => '<rect x="3" y="4" width="18" height="16" rx="2"/><path d="M3 9h18M6.5 6.5h.01M9 6.5h.01"/>',       // was 💻
            'phone'       => '<path d="M5 4h4l2 5-3 2a12 12 0 005 5l2-3 5 2v4a2 2 0 01-2 2A16 16 0 013 6a2 2 0 012-2z"/>',      // was 📞 (same as calls)
            'mobile'      => '<rect x="7" y="3" width="10" height="18" rx="2"/><path d="M11 18h2"/>',                            // was 📱
            'chat'        => '<path d="M4 5h16v11H9l-5 4V5z"/>',                                                                // was 💬
            'megaphone'   => '<path d="M3 11v2a1 1 0 001 1h2l4 4V6L6 10H4a1 1 0 00-1 1z"/><path d="M14 8a5 5 0 010 8M17.5 5.5a9 9 0 010 13"/>', // was 📣
            'magnet'      => '<path d="M6 5v7a6 6 0 0012 0V5h-4v7a2 2 0 01-4 0V5H6z"/><path d="M6 8h4M14 8h4"/>',               // was 🧲 (centred in the 24 box)
            'files'       => '<path d="M4 7a2 2 0 012-2h4l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H6a2 2 0 01-2-2V7z"/>',              // was 🗂️
            'clock'       => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3.5 2"/>',                                       // stat: Today

            // ── App-wide emoji replacements (used via ic_svg('key')) ──
            'email'       => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 7l9 6 9-6"/>',                       // ✉️
            'lock'        => '<rect x="4" y="10" width="16" height="11" rx="2"/><path d="M8 10V7a4 4 0 018 0v3"/>',             // 🔒
            'shield'      => '<path d="M12 3l8 4v5c0 4.5-3.2 7.7-8 9-4.8-1.3-8-4.5-8-9V7l8-4z"/>',                             // 🛡️
            'brain'       => '<path d="M9 4a3 3 0 00-3 3 3 3 0 00-2 5 3 3 0 001 5 3 3 0 004 2V4zM15 4a3 3 0 013 3 3 3 0 012 5 3 3 0 01-1 5 3 3 0 01-4 2V4z"/>', // 🧠
            'money'       => '<circle cx="12" cy="12" r="9"/><path d="M14.5 9.5A2.5 2.5 0 0012 8c-1.4 0-2.5.7-2.5 2s1.1 1.8 2.5 2 2.5.8 2.5 2-1.1 2-2.5 2A2.5 2.5 0 019.5 14.5M12 6v12"/>', // 💰
            'company'     => '<rect x="4" y="3" width="10" height="18" rx="1"/><path d="M14 8h6v13H4M7 7h.01M7 11h.01M7 15h.01M11 7h.01M11 11h.01M11 15h.01M17 12h.01M17 16h.01"/>', // 🏢
            'person'      => '<circle cx="12" cy="8" r="4"/><path d="M5 21a7 7 0 0114 0"/>',                                    // 👤
            'pin'         => '<path d="M12 21s7-6.2 7-11a7 7 0 00-14 0c0 4.8 7 11 7 11z"/><circle cx="12" cy="10" r="2.5"/>',    // 📍
            'tag'         => '<path d="M3 12l9-9h8v8l-9 9-8-8z"/><circle cx="16.5" cy="7.5" r="1.3"/>',                          // 🏷️
            'search'      => '<circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/>',                                      // 🔍 🔎
            'doc'         => '<path d="M6 3h9l4 4v14H6V3z"/><path d="M14 3v5h5M9 13h6M9 17h6"/>',                               // 📄
            'clip'        => '<path d="M21 12.5l-8.5 8.5a5 5 0 01-7-7L14 5.5a3.5 3.5 0 015 5l-8.5 8.5a2 2 0 01-3-3l8-8"/>',      // 📎
            'headphones'  => '<path d="M4 14v-2a8 8 0 0116 0v2"/><rect x="2" y="14" width="5" height="7" rx="1.5"/><rect x="17" y="14" width="5" height="7" rx="1.5"/>', // 🎧
            'chart'       => '<path d="M4 20V10M10 20V4M16 20v-7M22 20H2"/>',                                                   // 📊 📈
            'eye'         => '<path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7z"/><circle cx="12" cy="12" r="3"/>',      // 👁 👀
            'archive'     => '<rect x="3" y="4" width="18" height="5" rx="1"/><path d="M5 9v10h14V9M9 13h6"/>',                 // 🗄️
            'inbox'       => '<path d="M4 13l2-8h12l2 8v6H4v-6z"/><path d="M4 13h5a3 3 0 006 0h5"/>',                           // 📭
            'bolt'        => '<path d="M13 2L3 14h7l-1 8 10-12h-7l1-8z"/>',                                                     // ⚡
            'target'      => '<circle cx="12" cy="12" r="9"/><circle cx="12" cy="12" r="5"/><circle cx="12" cy="12" r="1.4"/>', // 🎯
            'sparkle'     => '<path d="M12 3l1.8 5.2L19 10l-5.2 1.8L12 17l-1.8-5.2L5 10l5.2-1.8L12 3z"/>',                       // ✨
            'bulb'        => '<path d="M9 18h6M10 21h4M8 11a4 4 0 118 0c0 2-2 3-2 5H10c0-2-2-3-2-5z"/>',                        // 💡
            'repeat'      => '<path d="M17 2l4 4-4 4"/><path d="M3 12V10a4 4 0 014-4h14M7 22l-4-4 4-4"/><path d="M21 12v2a4 4 0 01-4 4H3"/>', // 🔁
            'bell'        => '<path d="M18 8a6 6 0 00-12 0c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.7 21a2 2 0 01-3.4 0"/>',       // 🔔
            'puzzle'      => '<path d="M10 3h4v3a2 2 0 104 0V3h3v4h-3a2 2 0 100 4h3v6h-4v-3a2 2 0 10-4 0v3H6v-4H3a2 2 0 100-4h3V3h4z"/>', // 🧩
            'mailbox'     => '<path d="M3 12a4 4 0 018 0v7H3v-7z"/><path d="M11 12h9a2 2 0 012 2v5h-8M8 6h5"/>',                 // 📬
            'globe'       => '<circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3a15 15 0 010 18M12 3a15 15 0 000 18"/>',     // 🌐
            'video'       => '<rect x="3" y="6" width="13" height="12" rx="2"/><path d="M16 10l5-3v10l-5-3z"/>',                // 🎬 🎥
            'film'        => '<rect x="3" y="4" width="18" height="16" rx="2"/><path d="M7 4v16M17 4v16M3 8h4M3 12h4M3 16h4M17 8h4M17 12h4M17 16h4"/>', // 🎞️
            'image'       => '<rect x="3" y="4" width="18" height="16" rx="2"/><circle cx="8.5" cy="9.5" r="1.5"/><path d="M21 16l-5-5-9 9"/>', // 🖼️
            'cart'        => '<circle cx="9" cy="20" r="1.5"/><circle cx="17" cy="20" r="1.5"/><path d="M2 3h3l2.5 12h11l2-8H6"/>', // 🛒
            'basket'      => '<path d="M5 10L9 3M19 10l-4-7M3 10h18l-1.5 9h-13L3 10z"/><path d="M9 13v3M15 13v3"/>',            // 🧺
            'park'        => '<rect x="3" y="3" width="18" height="18" rx="3"/><path d="M9 17V7h4a3 3 0 010 6H9"/>',            // 🅿️
            'construction'=> '<path d="M3 21h18M5 21V8l7-4 7 4v13M9 21v-6h6v6"/>',                                             // 🚧
            'course'      => '<path d="M3 8l9-4 9 4-9 4-9-4z"/><path d="M7 10v5a5 3 0 0010 0v-5M21 8v6"/>',                     // 🎓
            'check'       => '<path d="M20 6L9 17l-5-5"/>',                                                                     // ✓ ✔
            'x'           => '<path d="M18 6L6 18M6 6l12 12"/>',                                                               // ✕ ❌
            'pause'       => '<rect x="6" y="5" width="4" height="14" rx="1"/><rect x="14" y="5" width="4" height="14" rx="1"/>', // ⏸
            'stop'        => '<rect x="5" y="5" width="14" height="14" rx="2"/>',                                              // ⏹
            'skip'        => '<path d="M5 5l9 7-9 7V5zM19 5v14"/>',                                                            // ⏭
            'edit'        => '<path d="M4 20h4l10-10-4-4L4 16v4z"/><path d="M13.5 6.5l4 4"/>',                                 // ✏️
            'download'    => '<path d="M12 3v12M7 10l5 5 5-5M5 21h14"/>',                                                      // ⬇
            'restore'     => '<path d="M3 12a9 9 0 109-9 9 9 0 00-7 3.3M3 3v4h4"/>',                                           // ↩︎
            'alert'       => '<path d="M12 8v5M12 17h.01"/><circle cx="12" cy="12" r="9"/>',                                   // ❗
            'fire'        => '<path d="M12 3c1 3 4 4 4 8a4 4 0 01-8 0c0-2 1-3 1-3 0 2 1 3 2 3 0-3-2-4 1-8z"/>',                // 🔥
            'partly'      => '<circle cx="9" cy="9" r="3.5"/><path d="M9 2v2M9 14v0M2 9h2M4 4l1.4 1.4"/><path d="M13 18a3 3 0 010-6 4 4 0 017.7 1A2.5 2.5 0 0120 18H13z"/>', // 🌤
            'snow'        => '<path d="M12 3v18M4.5 7.5l15 9M19.5 7.5l-15 9M12 6l-2.5 2M12 6l2.5 2M12 18l-2.5-2M12 18l2.5-2"/>', // ❄️
            'zzz'         => '<path d="M5 7h6l-6 6h6M13 11h5l-5 5h5"/>',                                                       // 💤
            'smile'       => '<circle cx="12" cy="12" r="9"/><path d="M8 14a4 4 0 008 0M9 9h.01M15 9h.01"/>',                 // 🙂
            'neutral'     => '<circle cx="12" cy="12" r="9"/><path d="M8 14h8M9 9h.01M15 9h.01"/>',                           // 😐
            'frown'       => '<circle cx="12" cy="12" r="9"/><path d="M8 15a4 4 0 018 0M9 9h.01M15 9h.01"/>',                 // ☹️

            // ── App-wide emoji replacements, batch 2 ──
            'flag'        => '<path d="M5 21V4h13l-2 4 2 4H5"/>',                                                            // 🚩
            'scales'      => '<path d="M12 3v18M7 21h10M5 7h14l-3 6a3 3 0 01-4 0zM5 7l-3 6a3 3 0 004 0zM12 7l7-2M12 7L5 5"/>', // ⚖️
            'ban'         => '<circle cx="12" cy="12" r="9"/><path d="M6 6l12 12"/>',                                         // 🚫
            'nophone'     => '<rect x="7" y="3" width="10" height="18" rx="2"/><path d="M4 4l16 16"/>',                       // 📵
            'lockkey'     => '<rect x="4" y="10" width="16" height="11" rx="2"/><path d="M8 10V7a4 4 0 018 0v3"/><circle cx="12" cy="15" r="1.4"/>', // 🔐
            'compass'     => '<circle cx="12" cy="12" r="9"/><path d="M15.5 8.5l-2 5-5 2 2-5 5-2z"/>',                        // 🧭
            'upload'      => '<path d="M12 21V9M7 14l5-5 5 5M5 3h14"/>',                                                      // 📤
            'book'        => '<path d="M4 5a2 2 0 012-2h13v16H6a2 2 0 00-2 2V5z"/><path d="M4 19a2 2 0 012-2h13"/>',          // 📖 📘
            'plug'        => '<path d="M9 3v5M15 3v5M6 8h12v3a6 6 0 01-12 0V8zM12 17v4"/>',                                   // 🔌
            'gear'        => '<circle cx="12" cy="12" r="3"/><path d="M19 12a7 7 0 00-.1-1l2-1.5-2-3.5-2.3 1a7 7 0 00-1.7-1L14.5 2h-5l-.4 2.5a7 7 0 00-1.7 1l-2.3-1-2 3.5L3.1 11a7 7 0 000 2l-2 1.5 2 3.5 2.3-1a7 7 0 001.7 1l.4 2.5h5l.4-2.5a7 7 0 001.7-1l2.3 1 2-3.5-2-1.5a7 7 0 00.1-1z"/>', // ⚙️
            'mic'         => '<rect x="9" y="3" width="6" height="11" rx="3"/><path d="M6 11a6 6 0 0012 0M12 17v4"/>',        // 🎙️ 🗣️
            'ladder'      => '<path d="M7 3v18M17 3v18M7 7h10M7 12h10M7 17h10"/>',                                            // 🪜
            'mirrorpage'  => '<rect x="5" y="3" width="14" height="18" rx="2"/><path d="M12 6v12M9 9l3-3 3 3"/>',             // 🪞
            'calendar'    => '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M3 9h18M8 3v4M16 3v4"/>',             // 🗓️ 📅
            'checkered'   => '<circle cx="12" cy="12" r="9"/><path d="M12 3v18M3 12h18"/><path d="M12 3a9 9 0 019 9M12 21a9 9 0 01-9-9"/>', // 🏁
            'road'        => '<path d="M6 3L3 21M18 3l3 18M12 4v2M12 10v2M12 16v2"/>',                                        // 🛣️
            'party'       => '<path d="M4 20l6-16 10 10-16 6z"/><path d="M14 6l2-2M18 8l2-1M16 12l3 1"/>',                     // 🎉
            'bookmark'    => '<path d="M6 3h12v18l-6-4-6 4V3z"/>',                                                            // 🔖
            'satellite'   => '<path d="M4 12a8 8 0 018-8M4 16a12 12 0 0112-12"/><rect x="3" y="15" width="6" height="6" rx="1"/>', // 🛰️
            'note'        => '<path d="M6 3h9l4 4v14H6V3z"/><path d="M14 3v5h5M9 12h6M9 16h4"/>',                             // 📝
            'warning'     => '<path d="M12 9v4M12 17h.01M10.3 3.9l-8 14A1.5 1.5 0 003.7 20h16.6a1.5 1.5 0 001.4-2.1l-8-14a1.5 1.5 0 00-2.8 0z"/>', // ⚠️ ⚠
            'play'        => '<path d="M7 5l12 7-12 7V5z"/>',                                                                 // ▶
            'usersgroup'  => '<circle cx="9" cy="8" r="3.2"/><path d="M2.5 20a6.5 6.5 0 0113 0"/><path d="M16.5 4.6a3.2 3.2 0 010 6.8M22 20a6.5 6.5 0 00-5-6.3"/>', // 👥
            'hourglass'   => '<path d="M6 3h12M6 21h12M7 3c0 5 10 5 10 9s-10 4-10 9M17 3c0 5-10 5-10 9s10 4 10 9"/>',         // ⏳
            'scroll'      => '<path d="M6 4h11a2 2 0 012 2v11a3 3 0 01-3 3H6a2 2 0 01-2-2V6a2 2 0 012-2z"/><path d="M9 8h7M9 12h7"/>', // 📜
            'clipboard'   => '<rect x="6" y="4" width="12" height="17" rx="2"/><path d="M9 4a3 3 0 016 0M9 11h6M9 15h4"/>',   // 📋
            'lightning'   => '<path d="M13 2L3 14h7l-1 8 10-12h-7l1-8z"/>',                                                   // ⚡ (alias of bolt)
            'star'        => '<path d="M12 3l2.6 6.3 6.8.5-5.2 4.4 1.6 6.6L12 17.8 6.2 21.3l1.6-6.6L2.6 9.8l6.8-.5L12 3z"/>', // ⭐
        ];
        return $icons;
    }
}

if (!function_exists('ic_icon')) {
    /**
     * Inner-SVG markup for a single key (falls back to a neutral dot).
     * @param string $key
     * @return string
     */
    function ic_icon($key)
    {
        $icons = ic_icons();
        return isset($icons[$key]) ? $icons[$key] : '<circle cx="12" cy="12" r="6"/>';
    }
}

if (!function_exists('ic_svg')) {
    /**
     * Full 24×24 stroked <svg> for a key — the reusable helper every page uses
     * to draw a custom icon in place of an emoji. currentColor + 1.8 stroke, so
     * it inherits the surrounding text colour and sizes via width/height (attrs
     * or CSS). CSP-safe (inline, no CDN/font).
     *
     *   echo ic_svg('rocket');                    // 1em, inherits color
     *   echo ic_svg('rocket', 18);                // 18×18
     *   echo ic_svg('rocket', 18, 'my-cls');      // + a class
     *
     * @param string     $key
     * @param int|string $size  px (int) or any CSS length; '' = 1em
     * @param string     $class optional class attribute
     * @return string
     */
    function ic_svg($key, $size = '', $class = '')
    {
        $dim = ($size === '' || $size === null)
            ? '1em'
            : (is_numeric($size) ? ((int) $size) . 'px' : $size);
        $cls = $class !== '' ? ' class="' . htmlspecialchars($class, ENT_QUOTES) . '"' : '';
        return '<svg' . $cls . ' width="' . $dim . '" height="' . $dim . '" viewBox="0 0 24 24"'
            . ' fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"'
            . ' stroke-linejoin="round" aria-hidden="true">' . ic_icon($key) . '</svg>';
    }
}
