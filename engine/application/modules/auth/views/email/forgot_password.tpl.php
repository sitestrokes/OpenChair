<?php
/**
 * Reset-password email — Agentic Affiliates brand, email-safe (table layout, inline
 * styles only; no external CSS/fonts/SVG so it renders across mail clients).
 * Vars: $identity (email/username), $forgotten_password_code.
 * Keeps the same reset URL Ion Auth built: auth/reset_password/<code>.
 */
$reset_url = site_url('auth/reset_password/' . $forgotten_password_code);
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
            <td style="padding-left:11px;font-size:21px;font-weight:700;letter-spacing:-.5px;color:#1F1712;">Agentic Affiliates</td>
          </tr></table>
        </td></tr>

        <!-- Body -->
        <tr><td style="padding:34px 36px 30px;">
          <div style="font-family:Georgia,'Times New Roman',serif;font-size:11px;letter-spacing:.16em;text-transform:uppercase;color:#E75C43;font-weight:700;margin-bottom:12px;">Password reset</div>
          <h1 style="margin:0 0 16px;font-size:25px;line-height:1.25;font-weight:700;letter-spacing:-.5px;color:#1F1712;">Reset your password</h1>
          <p style="margin:0 0 14px;font-size:15px;line-height:1.65;color:#5F5148;">
            Hi <b style="color:#1F1712;"><?= html_escape($identity) ?></b>, we received a request to reset the password for your Agentic Affiliates account. Click the button below to choose a new one.
          </p>

          <!-- CTA button -->
          <table role="presentation" cellpadding="0" cellspacing="0" style="margin:26px 0 24px;"><tr>
            <td style="border-radius:999px;background:#F0755E;background-image:linear-gradient(135deg,#F0755E,#E75C43);">
              <a href="<?= html_escape($reset_url) ?>" style="display:inline-block;padding:15px 34px;font-size:15px;font-weight:700;color:#FFFFFF;text-decoration:none;border-radius:999px;">Reset your password &rarr;</a>
            </td>
          </tr></table>

          <p style="margin:0 0 6px;font-size:13px;line-height:1.6;color:#8E7E74;">
            If the button doesn't work, copy and paste this link into your browser:
          </p>
          <p style="margin:0 0 22px;font-size:12.5px;line-height:1.5;word-break:break-all;">
            <a href="<?= html_escape($reset_url) ?>" style="color:#E75C43;text-decoration:underline;"><?= html_escape($reset_url) ?></a>
          </p>

          <div style="border-top:1px solid #ECDDD4;padding-top:18px;">
            <p style="margin:0;font-size:12.5px;line-height:1.6;color:#8E7E74;">
              Didn't ask for this? You can safely ignore this email &mdash; your password won't change until you open the link above and set a new one. For your security, this link expires after a short while.
            </p>
          </div>
        </td></tr>

        <!-- Footer -->
        <tr><td style="padding:20px 36px;background:#F8F0EB;border-top:1px solid #ECDDD4;text-align:center;">
          <p style="margin:0 0 4px;font-size:12px;color:#8E7E74;">
            Need a hand? <a href="https://engageleads.zendesk.com/" style="color:#E75C43;text-decoration:none;">Contact support</a>
          </p>
          <p style="margin:0;font-size:11px;color:#B9A79C;">&copy; <?= date('Y') ?> Agentic Affiliates &middot; All rights reserved.</p>
        </td></tr>

      </table>
    </td></tr>
  </table>
</body>
</html>
