<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<title><?= htmlspecialchars($site_title) ?> &mdash; Account Access</title>
</head>
<body style="margin:0;padding:0;background:#FBF6EE;font-family:'Poppins','Segoe UI',Arial,sans-serif;-webkit-font-smoothing:antialiased;-moz-osx-font-smoothing:grayscale;color:#1A1410;">

<!-- Preheader (hidden) -->
<div style="display:none;font-size:1px;color:#FBF6EE;line-height:1px;max-height:0;max-width:0;opacity:0;overflow:hidden;">
  Your <?= htmlspecialchars($site_title) ?> account is ready. Here are your login details inside.
</div>

<?php $display_name = !empty($first_name) ? $first_name : (isset($identity) ? $identity : $email); ?>

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#FBF6EE;">
<tr><td align="center" style="padding:40px 20px;">

<table role="presentation" width="580" cellpadding="0" cellspacing="0" border="0" style="max-width:580px;width:100%;">

  <!-- ═══ Paper Card ═══════════════════════════════════════════ -->
  <tr><td>
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#FFFDF7;border:1px solid rgba(26,20,16,.12);border-radius:14px;overflow:hidden;box-shadow:0 4px 12px rgba(58,35,14,.06),0 20px 40px -12px rgba(58,35,14,.12);">

      <!-- Dark ink header -->
      <tr>
        <td style="background:#1A1410;background:linear-gradient(135deg,#1A1410 0%,#3B2F26 100%);padding:36px 40px;text-align:center;">
          <?php
            // Email clients strip CSS filters, so a dark logo on this dark header
            // disappears. For the default Agentic brand, use the on-dark asset
            // (coral mark + white wordmark). A whitelabel logo keeps the
            // (best-effort) filter as before.
            $aa_default = base_url('assets/images/logos/aa-logo.png');
            $aa_ondark  = base_url('assets/images/logos/aa-logo-ondark.png');
            $use_ondark = (empty($logo) || rtrim($logo, '/') === rtrim($aa_default, '/'));
          ?>
          <?php if ($use_ondark): ?>
            <img src="<?= $aa_ondark ?>" alt="<?= htmlspecialchars($site_title) ?>" style="max-height:40px;max-width:220px;display:inline-block;">
          <?php elseif (!empty($logo)): ?>
            <img src="<?= $logo ?>" alt="<?= htmlspecialchars($site_title) ?>" style="max-height:40px;max-width:220px;display:inline-block;filter:brightness(0) invert(1);">
          <?php else: ?>
            <div style="font-family:'Poppins',Arial,sans-serif;font-size:22px;font-weight:600;color:#FFFDF7;letter-spacing:-.01em;"><?= htmlspecialchars($site_title) ?></div>
          <?php endif; ?>
          <div style="margin-top:10px;font-family:'JetBrains Mono',Consolas,monospace;font-size:10px;font-weight:500;letter-spacing:.24em;text-transform:uppercase;color:rgba(255,253,247,.5);">
            Account Access Bulletin
          </div>
        </td>
      </tr>

      <!-- Coral hairline -->
      <tr><td style="height:3px;background:#FF9E7A;background:linear-gradient(90deg,#FF9E7A 0%,#E84848 100%);"></td></tr>

      <!-- Body -->
      <tr>
        <td style="padding:44px 44px 32px;">

          <!-- Greeting -->
          <h1 style="margin:0 0 14px;font-family:'Poppins',Arial,sans-serif;font-size:26px;font-weight:500;line-height:1.22;letter-spacing:-.02em;color:#1A1410;">
            Welcome to <?= htmlspecialchars($site_title) ?>, <?= htmlspecialchars($display_name) ?>.
          </h1>
          <p style="margin:0 0 28px;font-family:'Poppins',Arial,sans-serif;font-size:14px;font-weight:300;line-height:1.65;color:#3B2F26;">
            Your account is ready and your login details are below. Keep them safe &mdash; this message won't be sent again.
          </p>

          <!-- Credentials Card -->
          <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#FBF6EE;border:1px solid rgba(26,20,16,.10);border-radius:10px;margin-bottom:28px;">
            <tr><td style="padding:22px 26px;">

              <!-- Login URL -->
              <div style="font-family:'JetBrains Mono',Consolas,monospace;font-size:9.5px;font-weight:600;letter-spacing:.22em;text-transform:uppercase;color:#8B7B6E;margin-bottom:6px;">
                Access Portal
              </div>
              <div style="font-family:'Poppins',Arial,sans-serif;font-size:14px;margin-bottom:18px;word-break:break-all;">
                <a href="<?= base_url('login') ?>" style="color:#E84848;text-decoration:none;font-weight:500;border-bottom:1px solid rgba(232,72,72,.3);"><?= base_url('login') ?></a>
              </div>

              <!-- divider -->
              <div style="height:1px;background:rgba(26,20,16,.08);margin:0 0 18px;"></div>

              <!-- Username -->
              <div style="font-family:'JetBrains Mono',Consolas,monospace;font-size:9.5px;font-weight:600;letter-spacing:.22em;text-transform:uppercase;color:#8B7B6E;margin-bottom:6px;">
                Username
              </div>
              <div style="font-family:'JetBrains Mono',Consolas,monospace;font-size:14px;font-weight:500;color:#1A1410;margin-bottom:18px;word-break:break-all;">
                <?= htmlspecialchars($email) ?>
              </div>

              <!-- divider -->
              <div style="height:1px;background:rgba(26,20,16,.08);margin:0 0 18px;"></div>

              <!-- Password -->
              <div style="font-family:'JetBrains Mono',Consolas,monospace;font-size:9.5px;font-weight:600;letter-spacing:.22em;text-transform:uppercase;color:#8B7B6E;margin-bottom:6px;">
                Password
              </div>
              <div style="font-family:'JetBrains Mono',Consolas,monospace;font-size:14px;font-weight:500;color:#1A1410;letter-spacing:.04em;word-break:break-all;">
                <?= htmlspecialchars($password) ?>
              </div>

            </td></tr>
          </table>

          <!-- CTA -->
          <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-bottom:32px;">
            <tr><td align="center">
              <a href="<?= base_url('login') ?>" style="display:inline-block;background:#1A1410;color:#FFFDF7;font-family:'Poppins',Arial,sans-serif;font-size:13px;font-weight:600;text-decoration:none;padding:15px 38px;border-radius:10px;letter-spacing:.04em;">
                Sign In to Dashboard &rarr;
              </a>
            </td></tr>
          </table>

          <!-- Security note -->
          <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:rgba(255,158,122,.08);border-left:3px solid #FF9E7A;border-radius:0 6px 6px 0;">
            <tr><td style="padding:14px 18px;">
              <div style="font-family:'Poppins',Arial,sans-serif;font-size:12px;font-weight:500;color:#3B2F26;line-height:1.55;">
                <strong style="color:#1A1410;">Security note.</strong> Please change your password after your first login for safekeeping.
              </div>
            </td></tr>
          </table>

        </td>
      </tr>

    </table>
  </td></tr>

  <!-- ═══ Help / Connect Card ═══════════════════════════════════════ -->
  <tr><td style="padding-top:22px;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#FFFDF7;border:1px solid rgba(26,20,16,.12);border-radius:14px;overflow:hidden;box-shadow:0 4px 12px rgba(58,35,14,.06),0 20px 40px -12px rgba(58,35,14,.12);">
      <tr><td style="padding:34px 36px 30px;text-align:center;">

        <div style="font-family:'Poppins',Arial,sans-serif;font-size:18px;font-weight:600;color:#1A1410;letter-spacing:-.01em;line-height:1.3;margin-bottom:8px;">
          Need Help or Want to Connect?
        </div>
        <div style="font-family:'Poppins',Arial,sans-serif;font-size:13px;font-weight:300;color:#8B7B6E;line-height:1.55;margin-bottom:22px;">
          We're here for you every step of the way
        </div>

        <!-- Buttons row -->
        <table role="presentation" cellpadding="0" cellspacing="0" border="0" align="center">
          <tr>
            <td style="padding:0 6px;">
              <a href="https://www.facebook.com/groups/1449420278587071" target="_blank" style="display:inline-block;background:#E84848;color:#FFFDF7;font-family:'Poppins',Arial,sans-serif;font-size:13px;font-weight:600;text-decoration:none;padding:12px 26px;border-radius:999px;letter-spacing:.02em;">
                Join Our Community
              </a>
            </td>
            <td style="padding:0 6px;">
              <a href="https://engageleads.zendesk.com/" target="_blank" style="display:inline-block;background:#1A1410;color:#FFFDF7;font-family:'Poppins',Arial,sans-serif;font-size:13px;font-weight:600;text-decoration:none;padding:12px 26px;border-radius:999px;letter-spacing:.02em;">
                Get Support
              </a>
            </td>
          </tr>
        </table>

        <div style="margin-top:22px;font-family:'Poppins',Arial,sans-serif;font-size:11px;font-weight:300;color:#B5A89E;line-height:1.55;">
          This is an automated bulletin from <?= htmlspecialchars($site_title) ?>.<br>
          You are receiving this because an account was created for you.
        </div>

      </td></tr>
    </table>
  </td></tr>

  <!-- Ruled footer mark -->
  <tr><td style="padding:20px 0 0;text-align:center;">
    <div style="font-family:'JetBrains Mono',Consolas,monospace;font-size:9px;font-weight:500;letter-spacing:.30em;text-transform:uppercase;color:#B5A89E;">
      &mdash; End of transmission &mdash;
    </div>
  </td></tr>

</table>

</td></tr>
</table>

</body>
</html>
