<?php
$user = $this->ion_auth->user()->row();
$uAccess = explode(",", $user->access);

if (!isset($responderData) || !is_array($responderData)) { $responderData = []; }
if (!isset($type)) { $type = ''; }

$rtype = null;
foreach ($responderData as $responder) {
    $rtype = $responder['Data']->respondertype;
}

$labels = [
    'getresponse'    => ['name' => 'GetResponse',    'rtype' => 1, 'num' => 'N°01', 'tag' => 'EMAIL · BROADCASTS · LISTS'],
    'activecampaign' => ['name' => 'ActiveCampaign', 'rtype' => 2, 'num' => 'N°02', 'tag' => 'CRM · AUTOMATIONS · LISTS'],
    'mailchimp'      => ['name' => 'MailChimp',      'rtype' => 3, 'num' => 'N°03', 'tag' => 'EMAIL · AUDIENCES · CAMPAIGNS'],
    'aweber'         => ['name' => 'AWeber',         'rtype' => 4, 'num' => 'N°04', 'tag' => 'OAUTH · LISTS · BROADCASTS'],
    'icontact'       => ['name' => 'iContact',       'rtype' => 5, 'num' => 'N°05', 'tag' => 'EMAIL · CONTACTS · LISTS'],
    'gotowebinar'    => ['name' => 'GoToWebinar',    'rtype' => 6, 'num' => 'N°06', 'tag' => 'OAUTH · WEBINARS · REGISTRANTS'],
    'convertkit'     => ['name' => 'ConvertKit',     'rtype' => 7, 'num' => 'N°07', 'tag' => 'EMAIL · TAGS · SUBSCRIBERS'],
];
$meta = isset($labels[$type]) ? $labels[$type] : ['name' => ucfirst($type), 'rtype' => 0, 'num' => 'N°00', 'tag' => 'INTEGRATION'];
$isConnected = (isset($rtype) && (int)$rtype === (int)$meta['rtype']);
?>

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">

<style>
.ar-page{
    --aa-cream:#FBF6EE;
    --aa-cream-2:#F4EBD8;
    --aa-paper:#FFFDF7;
    --aa-paper-2:#FBF4E2;
    --aa-ink:#1A1410;
    --aa-ink-2:#3B2F26;
    --aa-mute:#8B7B6E;
    --aa-mute-2:#B5A89E;
    --aa-c1:#FF9E7A;
    --aa-c2:#E84848;
    --aa-c2-deep:#C72E2E;
    --aa-line:rgba(26,20,16,.14);
    --aa-line-soft:rgba(26,20,16,.07);
    --aa-ok:#5BAE7E;
    --aa-warn:#D4A24C;

    position:relative;
    min-height:100vh;
    width:100%;
    padding:64px 72px 120px;
    background:var(--aa-cream);
    overflow:hidden;
    isolation:isolate;
    font-family:'Poppins',sans-serif;
    color:var(--aa-ink);
}
.ar-page::before{
    content:'';
    position:absolute;inset:0;
    background-image:url("data:image/svg+xml,%3Csvg viewBox='0 0 300 300' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.78' numOctaves='3' stitchTiles='stitch'/%3E%3CfeColorMatrix values='0 0 0 0 0.18 0 0 0 0 0.12 0 0 0 0 0.08 0 0 0 0.42 0'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)'/%3E%3C/svg%3E");
    opacity:.22;
    mix-blend-mode:multiply;
    pointer-events:none;
    z-index:0;
}
.ar-page::after{
    content:'';
    position:absolute;
    top:-220px;right:-180px;
    width:680px;height:680px;
    background:radial-gradient(circle at center,rgba(255,158,122,.08) 0%,rgba(232,72,72,.03) 30%,transparent 65%);
    filter:blur(28px);
    pointer-events:none;
    z-index:0;
}

/* ─── Back link ─── */
.ar-back{
    position:relative;
    z-index:2;
    display:inline-flex;
    align-items:center;
    gap:10px;
    font-family:'JetBrains Mono',monospace;
    font-size:11px;
    font-weight:500;
    letter-spacing:.18em;
    text-transform:uppercase;
    color:var(--aa-ink-2);
    text-decoration:none;
    margin-bottom:36px;
    padding:8px 14px 8px 0;
    transition:color .25s;
}
.ar-back:hover{ color:var(--aa-c2); text-decoration:none; }
.ar-back::before{
    content:'←';
    font-family:'Poppins',sans-serif;
    font-size:16px;
    transition:transform .25s;
}
.ar-back:hover::before{ transform:translateX(-3px); }

/* ─── Editorial header ─── */
.ar-head{
    position:relative;
    z-index:2;
    margin:0 auto 48px;
    text-align:center;
}
.ar-eyebrow{
    display:inline-flex;
    align-items:center;
    gap:18px;
    margin-bottom:22px;
    font-family:'JetBrains Mono',monospace;
    font-size:11px;
    font-weight:500;
    text-transform:uppercase;
    letter-spacing:.32em;
    color:var(--aa-ink-2);
}
.ar-eyebrow .rule{
    width:56px;height:1px;
    background:var(--aa-ink);
    opacity:.45;
}
.ar-eyebrow .num{
    font-family:'Poppins',sans-serif;
    font-weight:500;
    font-size:18px;
    color:var(--aa-c2);
    letter-spacing:0;
    text-transform:none;
}
.ar-title{
    font-family:'Poppins',sans-serif;
    font-weight:400;
    font-size:clamp(40px,5.4vw,72px);
    line-height:1;
    letter-spacing:-.024em;
    color:var(--aa-ink);
    margin:0 auto 14px;
    max-width:18ch;
    text-wrap:balance;
}
.ar-title em{
    color:var(--aa-c2);
    font-style:normal;
    position:relative;
}
.ar-title em::after{
    content:'';
    position:absolute;
    left:4%;right:4%;
    bottom:.06em;
    height:.06em;
    background:var(--aa-c2);
    opacity:.35;
    transform:skewX(-8deg);
}
.ar-sub{
    font-size:15px;
    font-weight:300;
    line-height:1.65;
    color:var(--aa-mute);
    max-width:60ch;
    margin:0 auto;
}

/* ─── Card ─── */
.ar-wrap{
    position:relative;
    z-index:2;
    max-width:880px;
    margin:0 auto;
}
.ar-card{
    position:relative;
    background:linear-gradient(178deg,var(--aa-paper) 0%,var(--aa-paper-2) 100%);
    border:1px solid var(--aa-line);
    border-radius:3px;
    box-shadow:
        inset 0 1px 0 rgba(255,255,255,.85),
        0 1px 0 rgba(26,20,16,.06),
        0 22px 44px -28px rgba(60,40,20,.32),
        0 4px 10px -6px rgba(60,40,20,.14);
    overflow:hidden;
    isolation:isolate;
}
.ar-card::before{
    content:'';
    position:absolute;inset:0;
    background-image:url("data:image/svg+xml,%3Csvg viewBox='0 0 200 200' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='1.1' numOctaves='2' stitchTiles='stitch'/%3E%3CfeColorMatrix values='0 0 0 0 0.16 0 0 0 0 0.10 0 0 0 0 0.06 0 0 0 0.34 0'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)'/%3E%3C/svg%3E");
    opacity:.18;
    mix-blend-mode:multiply;
    pointer-events:none;
    z-index:0;
    border-radius:inherit;
}
.ar-card-head{
    position:relative;
    z-index:2;
    display:flex;
    align-items:center;
    gap:20px;
    padding:28px 36px 18px 40px;
    border-bottom:1px dashed var(--aa-line);
}
.ar-card-num{
    font-family:'JetBrains Mono',monospace;
    font-size:11px;
    font-weight:500;
    letter-spacing:.18em;
    color:var(--aa-c2);
    text-transform:uppercase;
}
.ar-card-title{
    font-family:'Poppins',sans-serif;
    font-weight:600;
    font-size:24px;
    letter-spacing:-.012em;
    color:var(--aa-ink);
    margin:0;
    flex:1;
}
.ar-card-tag{
    font-family:'JetBrains Mono',monospace;
    font-size:10px;
    font-weight:500;
    letter-spacing:.16em;
    text-transform:uppercase;
    color:var(--aa-mute);
}
.ar-card-body{
    position:relative;
    z-index:2;
    padding:32px 40px 36px;
}

/* ─── Status pill ─── */
.ar-status{
    display:inline-flex;
    align-items:center;
    gap:10px;
    padding:8px 14px;
    border-radius:2px;
    font-family:'JetBrains Mono',monospace;
    font-size:10px;
    font-weight:600;
    letter-spacing:.18em;
    text-transform:uppercase;
    margin-bottom:24px;
}
.ar-status.is-on{
    background:rgba(91,174,126,.12);
    border:1px solid rgba(91,174,126,.5);
    color:#2A6B47;
}
.ar-status.is-off{
    background:rgba(232,72,72,.06);
    border:1px solid rgba(232,72,72,.35);
    color:var(--aa-c2-deep);
}
.ar-status .dot{
    width:8px;height:8px;
    border-radius:50%;
    background:currentColor;
}

/* ─── Form ─── */
.ar-form{ display:flex; flex-direction:column; gap:20px; margin:0; }
.ar-field{ display:flex; flex-direction:column; gap:8px; }
.ar-label{
    font-family:'JetBrains Mono',monospace;
    font-size:10px;
    font-weight:500;
    letter-spacing:.18em;
    text-transform:uppercase;
    color:var(--aa-mute);
}
.ar-label sup{ color:var(--aa-c2); }
.ar-input{
    font-family:'Poppins',sans-serif;
    font-size:14px;
    font-weight:400;
    color:var(--aa-ink);
    padding:14px 16px;
    background:var(--aa-paper);
    border:1px solid var(--aa-line);
    border-radius:2px;
    transition:border-color .2s, background .2s, box-shadow .2s;
    width:100%;
}
.ar-input:focus{
    outline:none;
    border-color:var(--aa-c2);
    background:#fff;
    box-shadow:0 0 0 3px rgba(232,72,72,.08);
}
.ar-input::placeholder{ color:var(--aa-mute-2); font-weight:300; }

.ar-actions{
    display:flex;
    align-items:center;
    gap:16px;
    margin-top:8px;
}
.ar-btn{
    font-family:'JetBrains Mono',monospace;
    font-size:11px;
    font-weight:600;
    letter-spacing:.20em;
    text-transform:uppercase;
    padding:14px 22px;
    border-radius:2px;
    cursor:pointer;
    transition:all .25s;
    display:inline-flex;
    align-items:center;
    gap:10px;
    border:1px solid;
}
.ar-btn--connect{
    color:var(--aa-paper);
    background:var(--aa-ink);
    border-color:var(--aa-ink);
}
.ar-btn--connect:hover{
    background:var(--aa-c2);
    border-color:var(--aa-c2);
    color:#fff;
}
.ar-btn--connect::after{
    content:'→';
    font-family:'Poppins',sans-serif;
    font-size:14px;
    transition:transform .25s;
}
.ar-btn--connect:hover::after{ transform:translateX(3px); }

.ar-btn--disconnect{
    color:var(--aa-c2);
    background:var(--aa-paper);
    border-color:var(--aa-c2);
}
.ar-btn--disconnect:hover{
    background:var(--aa-c2);
    color:#fff;
}

/* ─── Help block ─── */
.ar-help{
    margin-top:28px;
    padding:18px 22px;
    background:rgba(255,158,122,.06);
    border:1px dashed var(--aa-line);
    border-radius:2px;
}
.ar-help-label{
    font-family:'JetBrains Mono',monospace;
    font-size:10px;
    font-weight:500;
    letter-spacing:.22em;
    text-transform:uppercase;
    color:var(--aa-mute);
    margin-bottom:6px;
}
.ar-help-text{
    font-size:13px;
    line-height:1.6;
    color:var(--aa-ink-2);
    margin:0;
    font-weight:300;
}
.ar-help-text a{ color:var(--aa-c2); text-decoration:underline; }
.ar-help-text a:hover{ color:var(--aa-c2-deep); }

/* ─── Alert ─── */
.ar-alert{
    position:relative;
    background:rgba(91,174,126,.10);
    border-left:3px solid var(--aa-ok);
    padding:12px 36px 12px 16px;
    margin-bottom:22px;
    font-family:'JetBrains Mono',monospace;
    font-size:11px;
    font-weight:500;
    letter-spacing:.08em;
    text-transform:uppercase;
    color:#2A6B47;
    border-radius:2px;
}
.ar-alert .close{
    position:absolute;
    right:8px;top:50%;
    transform:translateY(-50%);
    background:none;border:none;
    font-size:20px;color:#2A6B47;
    cursor:pointer;line-height:1;padding:4px 8px;
}

/* ─── Responsive ─── */
@media (max-width: 991px){
    .ar-page{ padding:48px 32px 100px; }
    .ar-card-head{ padding:22px 24px 16px; flex-wrap:wrap; }
    .ar-card-body{ padding:26px 24px 30px; }
}
@media (max-width: 600px){
    .ar-page{ padding:36px 18px 80px; }
    .ar-title{ font-size:34px; }
    .ar-card-tag{ display:none; }
}
</style>

<div class="ar-page">

    <a href="<?= site_url('setting'); ?>#autoresponders" class="ar-back">Back to Settings</a>

    <header class="ar-head">
        <div class="ar-eyebrow">
            <span class="rule"></span>
            <span class="num"><?= $meta['num'] ?></span>
            <span>Autoresponder</span>
            <span class="rule"></span>
        </div>
        <h1 class="ar-title">Connect <em><?= $meta['name'] ?></em></h1>
        <p class="ar-sub">
            <?php if ($type === 'aweber' || $type === 'gotowebinar'): ?>
                Click connect below to authorize <?= $meta['name'] ?> with your <?= lang('site_title') ?> account. You'll be redirected to <?= $meta['name'] ?> to grant access, then sent right back here.
            <?php else: ?>
                Provide the credentials below to link your <?= $meta['name'] ?> account to <?= lang('site_title') ?>. Credentials are encrypted before storage.
            <?php endif; ?>
        </p>
    </header>

    <div class="ar-wrap">

        <div class="alert ar-alert" style="display:none;" role="alert">
            <button type="button" class="close" aria-label="Close">
                <span aria-hidden="true">×</span>
            </button>
            <span class="sopan">API Keys saved successfully</span>
        </div>

        <div class="ar-card">

            <div class="ar-card-head">
                <span class="ar-card-num"><?= $meta['num'] ?></span>
                <h2 class="ar-card-title"><?= $meta['name'] ?> Account</h2>
                <span class="ar-card-tag"><?= $meta['tag'] ?></span>
            </div>

            <div class="ar-card-body">

                <?php if ($isConnected): ?>
                    <span class="ar-status is-on"><span class="dot"></span><?= $meta['name'] ?> connected</span>
                <?php else: ?>
                    <span class="ar-status is-off"><span class="dot"></span><?= $meta['name'] ?> not connected</span>
                <?php endif; ?>

                <?php /* ─────────────────────── GETRESPONSE ─────────────────────── */ ?>
                <?php if ($type === 'getresponse'): ?>
                    <form method="post" action="<?= site_url('saveresponder'); ?>" class="ar-form">
                        <input type="hidden" name="type" value="getresponse"/>

                        <?php if (!$isConnected): ?>
                            <div class="ar-field">
                                <label class="ar-label" for="ar-getresponse-key">API Key <sup>*</sup></label>
                                <input type="text" class="ar-input" id="ar-getresponse-key" name="apikey" placeholder="Enter your GetResponse API key" required/>
                            </div>
                            <div class="ar-actions">
                                <input type="hidden" name="connect" value="1"/>
                                <button type="submit" class="ar-btn ar-btn--connect">Connect</button>
                            </div>
                        <?php else: ?>
                            <div class="ar-actions">
                                <input type="hidden" name="connect" value="0"/>
                                <button type="submit" class="ar-btn ar-btn--disconnect">Disconnect</button>
                            </div>
                        <?php endif; ?>

                        <div class="ar-help">
                            <div class="ar-help-label">Where to find this</div>
                            <p class="ar-help-text">
                                In your GetResponse account, go to <strong>Menu → Integrations and API → API</strong>, then generate or copy an API key.
                            </p>
                        </div>
                    </form>

                <?php /* ─────────────────────── ACTIVECAMPAIGN ─────────────────────── */ ?>
                <?php elseif ($type === 'activecampaign'): ?>
                    <form method="post" action="<?= site_url('saveresponder'); ?>" class="ar-form">
                        <input type="hidden" name="type" value="activecampaign"/>

                        <?php if (!$isConnected): ?>
                            <div class="ar-field">
                                <label class="ar-label" for="ar-ac-url">App URL <sup>*</sup></label>
                                <input type="text" class="ar-input" id="ar-ac-url" name="appurl" placeholder="https://youraccount.api-us1.com" required/>
                            </div>
                            <div class="ar-field">
                                <label class="ar-label" for="ar-ac-key">API Key <sup>*</sup></label>
                                <input type="text" class="ar-input" id="ar-ac-key" name="apikey" placeholder="Enter your ActiveCampaign API key" required/>
                            </div>
                            <div class="ar-actions">
                                <input type="hidden" name="connect" value="1"/>
                                <button type="submit" class="ar-btn ar-btn--connect">Connect</button>
                            </div>
                        <?php else: ?>
                            <div class="ar-actions">
                                <input type="hidden" name="connect" value="0"/>
                                <button type="submit" class="ar-btn ar-btn--disconnect">Disconnect</button>
                            </div>
                        <?php endif; ?>

                        <div class="ar-help">
                            <div class="ar-help-label">Where to find this</div>
                            <p class="ar-help-text">
                                In ActiveCampaign, go to <strong>Settings → Developer</strong>. Copy the <strong>API URL</strong> (e.g. <code>https://youraccount.api-us1.com</code>) and the <strong>Key</strong>. Make sure at least one list exists under Contacts → Lists.
                            </p>
                        </div>
                    </form>

                <?php /* ─────────────────────── MAILCHIMP ─────────────────────── */ ?>
                <?php elseif ($type === 'mailchimp'): ?>
                    <form method="post" action="<?= site_url('saveresponder'); ?>" class="ar-form">
                        <input type="hidden" name="type" value="mailchimp"/>

                        <?php if (!$isConnected): ?>
                            <div class="ar-field">
                                <label class="ar-label" for="ar-mc-key">API Key <sup>*</sup></label>
                                <input type="text" class="ar-input" id="ar-mc-key" name="apikey" placeholder="Enter your MailChimp API key" required/>
                            </div>
                            <div class="ar-actions">
                                <input type="hidden" name="connect" value="1"/>
                                <button type="submit" class="ar-btn ar-btn--connect">Connect</button>
                            </div>
                        <?php else: ?>
                            <div class="ar-actions">
                                <input type="hidden" name="connect" value="0"/>
                                <button type="submit" class="ar-btn ar-btn--disconnect">Disconnect</button>
                            </div>
                        <?php endif; ?>

                        <div class="ar-help">
                            <div class="ar-help-label">Where to find this</div>
                            <p class="ar-help-text">
                                In MailChimp, click your profile icon, then <strong>Account &amp; billing → Extras → API keys</strong>. Generate a new key and paste it here.
                            </p>
                        </div>
                    </form>

                <?php /* ─────────────────────── AWEBER ─────────────────────── */ ?>
                <?php elseif ($type === 'aweber'): ?>
                    <form method="post" action="<?= site_url('saveresponder'); ?>" class="ar-form">
                        <input type="hidden" name="type" value="aweber"/>
                        <div class="ar-actions">
                            <?php if ($isConnected): ?>
                                <input type="hidden" name="connect" value="0"/>
                                <button type="submit" class="ar-btn ar-btn--disconnect">Disconnect</button>
                            <?php else: ?>
                                <input type="hidden" name="connect" value="1"/>
                                <button type="submit" class="ar-btn ar-btn--connect">Connect with AWeber</button>
                            <?php endif; ?>
                        </div>
                        <div class="ar-help">
                            <div class="ar-help-label">How this works</div>
                            <p class="ar-help-text">
                                Clicking connect sends you to AWeber's authorization page. After approval you'll be redirected back, and your account will be linked automatically.
                            </p>
                        </div>
                    </form>

                <?php /* ─────────────────────── ICONTACT ─────────────────────── */ ?>
                <?php elseif ($type === 'icontact'): ?>
                    <form method="post" action="<?= site_url('saveresponder'); ?>" class="ar-form">
                        <input type="hidden" name="type" value="icontact"/>

                        <?php if (!$isConnected): ?>
                            <div class="ar-field">
                                <label class="ar-label" for="ar-ic-appid">App ID <sup>*</sup></label>
                                <input type="text" class="ar-input" id="ar-ic-appid" name="appid" placeholder="Enter your iContact App ID" required/>
                            </div>
                            <div class="ar-field">
                                <label class="ar-label" for="ar-ic-user">API Username <sup>*</sup></label>
                                <input type="text" class="ar-input" id="ar-ic-user" name="apiuser" placeholder="Enter your iContact App username" required/>
                            </div>
                            <div class="ar-field">
                                <label class="ar-label" for="ar-ic-pass">API Password <sup>*</sup></label>
                                <input type="text" class="ar-input" id="ar-ic-pass" name="apipass" placeholder="Enter your iContact application password" required/>
                            </div>
                            <div class="ar-actions">
                                <input type="hidden" name="connect" value="1"/>
                                <button type="submit" class="ar-btn ar-btn--connect">Connect</button>
                            </div>
                        <?php else: ?>
                            <div class="ar-actions">
                                <input type="hidden" name="connect" value="0"/>
                                <button type="submit" class="ar-btn ar-btn--disconnect">Disconnect</button>
                            </div>
                        <?php endif; ?>

                        <div class="ar-help">
                            <div class="ar-help-label">Where to find these</div>
                            <p class="ar-help-text">
                                In iContact, register an application under <strong>Settings → iContact API</strong> to get your App ID, then generate an Application password tied to your username.
                            </p>
                        </div>
                    </form>

                <?php /* ─────────────────────── GOTOWEBINAR ─────────────────────── */ ?>
                <?php elseif ($type === 'gotowebinar'): ?>
                    <form method="post" action="<?= site_url('saveresponder'); ?>" class="ar-form">
                        <input type="hidden" name="type" value="gotowebinar"/>
                        <div class="ar-actions">
                            <?php if ($isConnected): ?>
                                <input type="hidden" name="connect" value="0"/>
                                <button type="submit" class="ar-btn ar-btn--disconnect">Disconnect</button>
                            <?php else: ?>
                                <input type="hidden" name="connect" value="1"/>
                                <button type="submit" class="ar-btn ar-btn--connect">Connect with GoToWebinar</button>
                            <?php endif; ?>
                        </div>
                        <div class="ar-help">
                            <div class="ar-help-label">How this works</div>
                            <p class="ar-help-text">
                                Clicking connect sends you to GoToWebinar's authorization page. After approval you'll be redirected back, and your account will be linked automatically.
                            </p>
                        </div>
                    </form>

                <?php /* ─────────────────────── CONVERTKIT ─────────────────────── */ ?>
                <?php elseif ($type === 'convertkit'): ?>
                    <form method="post" action="<?= site_url('saveresponder'); ?>" class="ar-form">
                        <input type="hidden" name="type" value="convertkit"/>

                        <?php if (!$isConnected): ?>
                            <div class="ar-field">
                                <label class="ar-label" for="ar-ck-key">API Key <sup>*</sup></label>
                                <input type="text" class="ar-input" id="ar-ck-key" name="apikey" placeholder="Enter your ConvertKit API key" required/>
                            </div>
                            <div class="ar-actions">
                                <input type="hidden" name="connect" value="1"/>
                                <button type="submit" class="ar-btn ar-btn--connect">Connect</button>
                            </div>
                        <?php else: ?>
                            <div class="ar-actions">
                                <input type="hidden" name="connect" value="0"/>
                                <button type="submit" class="ar-btn ar-btn--disconnect">Disconnect</button>
                            </div>
                        <?php endif; ?>

                        <div class="ar-help">
                            <div class="ar-help-label">Where to find this</div>
                            <p class="ar-help-text">
                                In ConvertKit (Kit), go to <strong>Settings → Advanced</strong> and copy your <strong>API Key</strong>. Make sure you have at least one tag created for subscribers.
                            </p>
                        </div>
                    </form>

                <?php endif; ?>

            </div>
        </div>
    </div>

</div>

<?php $this->load->view('shared/footer'); ?>
<script type="text/javascript">
$(document).ready(function(){
    $('.ar-alert .close').on('click', function(){
        $(this).closest('.ar-alert').fadeOut(200);
    });
});
</script>
