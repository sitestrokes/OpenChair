<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="margin:0;padding:0;background:#FBF6EE;font-family:'DM Sans','Segoe UI',Arial,sans-serif;-webkit-font-smoothing:antialiased;">

<table width="100%" cellpadding="0" cellspacing="0" style="background:#FBF6EE;padding:40px 20px;">
<tr><td align="center">
<table width="560" cellpadding="0" cellspacing="0" style="max-width:560px;width:100%;background:#ffffff;border-radius:16px;border:1px solid #E8E0D8;overflow:hidden;">

  <!-- Header -->
  <tr>
    <td style="background:#1A1410;padding:28px 40px;text-align:center;">
      <img src="<?= base_url() ?>assets_new/images/logo.png" alt="Agentic Agency" style="max-height:40px;max-width:200px;">
    </td>
  </tr>

  <!-- Orange accent line -->
  <tr><td style="height:3px;background:#FF6200;"></td></tr>

  <!-- Body -->
  <tr>
    <td style="padding:36px 40px 28px;">

      <h1 style="margin:0 0 8px;font-size:22px;font-weight:700;color:#1A1410;">Welcome to Beta Testing!</h1>
      <p style="margin:0 0 24px;font-size:14px;color:#6B5E54;line-height:1.6;">
        Thank you for joining the <strong>Agentic Agency</strong> beta program. Here are your login credentials:
      </p>

      <!-- Credentials Card -->
      <table width="100%" cellpadding="0" cellspacing="0" style="background:#FBF6EE;border:1px solid #E8E0D8;border-radius:12px;margin-bottom:24px;">
        <tr>
          <td style="padding:20px 24px;">
            <table width="100%" cellpadding="0" cellspacing="0">
              <tr>
                <td style="padding:6px 0;font-size:12px;text-transform:uppercase;letter-spacing:0.8px;color:#8A8078;font-weight:600;">Login URL</td>
              </tr>
              <tr>
                <td style="padding:0 0 14px;font-size:14px;">
                  <a href="<?= base_url('login') ?>" style="color:#FF6200;text-decoration:none;font-weight:600;"><?= base_url('login') ?></a>
                </td>
              </tr>
              <tr><td style="border-top:1px solid #E8E0D8;"></td></tr>
              <tr>
                <td style="padding:14px 0 6px;font-size:12px;text-transform:uppercase;letter-spacing:0.8px;color:#8A8078;font-weight:600;">Username</td>
              </tr>
              <tr>
                <td style="padding:0 0 14px;font-size:14px;color:#1A1410;font-weight:500;font-family:'JetBrains Mono',Consolas,monospace;"><?= htmlspecialchars($email) ?></td>
              </tr>
              <tr><td style="border-top:1px solid #E8E0D8;"></td></tr>
              <tr>
                <td style="padding:14px 0 6px;font-size:12px;text-transform:uppercase;letter-spacing:0.8px;color:#8A8078;font-weight:600;">Password</td>
              </tr>
              <tr>
                <td style="padding:0;font-size:14px;color:#1A1410;font-weight:500;font-family:'JetBrains Mono',Consolas,monospace;"><?= htmlspecialchars($password) ?></td>
              </tr>
            </table>
          </td>
        </tr>
      </table>

      <!-- CTA Button -->
      <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:28px;">
        <tr>
          <td align="center">
            <a href="<?= base_url('login') ?>" style="display:inline-block;background:#FF6200;color:#ffffff;font-size:14px;font-weight:700;text-decoration:none;padding:14px 36px;border-radius:10px;letter-spacing:0.3px;">
              Sign In to Your Account
            </a>
          </td>
        </tr>
      </table>

      <!-- Divider -->
      <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:20px;">
        <tr><td style="border-top:1px solid #E8E0D8;"></td></tr>
      </table>

      <p style="margin:0 0 6px;font-size:13px;color:#6B5E54;line-height:1.6;">
        For security, we recommend changing your password after your first login.
      </p>
      <p style="margin:0;font-size:13px;color:#6B5E54;line-height:1.6;">
        If you have any questions, reply to this email or contact our support team.
      </p>

    </td>
  </tr>

  <!-- Footer -->
  <tr>
    <td style="background:#F5F0EB;padding:20px 40px;text-align:center;border-top:1px solid #E8E0D8;">
      <p style="margin:0 0 4px;font-size:11px;color:#8A8078;">
        Agentic Agency &middot; Beta Program
      </p>
      <p style="margin:0;font-size:11px;color:#AEA59C;">
        This is an automated message. Please do not reply directly to this email.
      </p>
    </td>
  </tr>

</table>
</td></tr>
</table>

</body>
</html>
