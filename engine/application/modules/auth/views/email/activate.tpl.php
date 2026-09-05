<?php
/**
 * Welcome / account-access email — sent when an account is created (users
 * module / manual registration). OpenChair brand, email-safe (table layout,
 * inline styles only). Vars: $identity, $password, $email, $product.
 * Replaces the legacy engine welcome template.
 */
$login_url = rtrim(base_url(), '/') . '/';
$uname     = isset($identity) ? $identity : (isset($email) ? $email : '');
$product   = isset($product) ? (string) $product : '';
?><!doctype html>
<html>
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head>
<body style="margin:0;padding:0;background:#F3EAE4;font-family:Helvetica,Arial,sans-serif;">
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#F3EAE4;padding:32px 12px;">
    <tr><td align="center">
      <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;background:#FFFFFF;border-radius:20px;overflow:hidden;box-shadow:0 24px 60px -30px rgba(90,50,35,.35);">

        <!-- Brand header -->
        <tr><td style="padding:28px 36px 22px;border-bottom:1px solid #ECDDD4;">
          <table role="presentation" cellpadding="0" cellspacing="0"><tr>
            <td style="width:40px;height:40px;background:#F0755E;border-radius:11px;text-align:center;vertical-align:middle;font-size:22px;line-height:40px;color:#1a0e08;font-weight:700;">&#128276;</td>
            <td style="padding-left:11px;font-size:21px;font-weight:700;letter-spacing:-.5px;color:#1F1712;">OpenChair</td>
          </tr></table>
        </td></tr>

        <!-- Hero -->
        <tr><td style="padding:34px 36px 8px;">
          <div style="font-family:Georgia,'Times New Roman',serif;font-size:11px;letter-spacing:.16em;text-transform:uppercase;color:#E75C43;font-weight:700;margin-bottom:12px;">Welcome aboard</div>
          <h1 style="margin:0 0 14px;font-size:26px;line-height:1.22;font-weight:700;letter-spacing:-.6px;color:#1F1712;">Your OpenChair account is ready</h1>
          <p style="margin:0 0 8px;font-size:15px;line-height:1.65;color:#5F5148;">
            Welcome<?= $product ? ' to <b style="color:#1F1712;">' . html_escape($product) . '</b>' : '' ?>! Your account has been created. Use the details below to sign in and start turning every lead into a live conversation.
          </p>
        </td></tr>

        <!-- Credentials card -->
        <tr><td style="padding:18px 36px 6px;">
          <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#F8F0EB;border:1px solid #ECDDD4;border-radius:14px;">
            <tr><td style="padding:20px 22px;">
              <div style="font-family:Georgia,serif;font-size:10px;letter-spacing:.14em;text-transform:uppercase;color:#8E7E74;font-weight:700;margin-bottom:12px;">Your login details</div>
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                <tr>
                  <td style="padding:6px 0;font-size:13px;color:#8E7E74;width:120px;">Login URL</td>
                  <td style="padding:6px 0;font-size:14px;"><a href="<?= html_escape($login_url) ?>" style="color:#E75C43;text-decoration:none;font-weight:700;"><?= html_escape($login_url) ?></a></td>
                </tr>
                <tr>
                  <td style="padding:6px 0;font-size:13px;color:#8E7E74;">Username</td>
                  <td style="padding:6px 0;font-size:14px;color:#1F1712;font-weight:700;font-family:'Courier New',monospace;"><?= html_escape($uname) ?></td>
                </tr>
                <?php if (!empty($password)): ?>
                <tr>
                  <td style="padding:6px 0;font-size:13px;color:#8E7E74;">Password</td>
                  <td style="padding:6px 0;font-size:14px;color:#1F1712;font-weight:700;font-family:'Courier New',monospace;"><?= html_escape($password) ?></td>
                </tr>
                <?php endif; ?>
              </table>
            </td></tr>
          </table>
        </td></tr>

        <!-- CTA -->
        <tr><td style="padding:22px 36px 4px;">
          <table role="presentation" cellpadding="0" cellspacing="0"><tr>
            <td style="border-radius:999px;background:#F0755E;background-image:linear-gradient(135deg,#F0755E,#E75C43);">
              <a href="<?= html_escape($login_url) ?>" style="display:inline-block;padding:15px 34px;font-size:15px;font-weight:700;color:#FFFFFF;text-decoration:none;border-radius:999px;">Log in to OpenChair &rarr;</a>
            </td>
          </tr></table>
          <p style="margin:14px 0 0;font-size:12.5px;line-height:1.6;color:#8E7E74;">
            For your security, we recommend changing your password after your first login.
          </p>
        </td></tr>

        <!-- Getting started -->
        <tr><td style="padding:26px 36px 6px;">
          <div style="border-top:1px solid #ECDDD4;padding-top:20px;">
            <h2 style="margin:0 0 12px;font-size:16px;font-weight:700;color:#1F1712;">Getting started</h2>
            <p style="margin:0 0 9px;font-size:14px;line-height:1.6;color:#5F5148;"><b style="color:#1F1712;">1.</b> Paste an offer URL and let Autopilot build your first Bridge.</p>
            <p style="margin:0 0 9px;font-size:14px;line-height:1.6;color:#5F5148;"><b style="color:#1F1712;">2.</b> Add your AI + Twilio keys in Settings to unlock voice, phone and SMS.</p>
            <p style="margin:0;font-size:14px;line-height:1.6;color:#5F5148;"><b style="color:#1F1712;">3.</b> Share your Bridge link &mdash; the moment someone opts in, it talks back.</p>
          </div>
        </td></tr>

        <!-- Support -->
        <tr><td style="padding:22px 36px 30px;">
          <p style="margin:0;font-size:13px;line-height:1.6;color:#5F5148;">
            Questions? Our team is here to help at
            <a href="https://engageleads.zendesk.com/" style="color:#E75C43;text-decoration:none;">the support desk</a>.
          </p>
        </td></tr>

        <!-- Footer -->
        <tr><td style="padding:20px 36px;background:#F8F0EB;border-top:1px solid #ECDDD4;text-align:center;">
          <p style="margin:0;font-size:11px;color:#B9A79C;">&copy; <?= date('Y') ?> OpenChair &middot; All rights reserved.</p>
        </td></tr>

      </table>
    </td></tr>
  </table>
</body>
</html>
