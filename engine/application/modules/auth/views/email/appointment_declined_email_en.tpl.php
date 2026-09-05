<?php
/**
 * Appointment decline email — English. Sent when admin clicks "Decline" on a
 * pending appointment request. Same vars as the confirmed templates, plus
 * everything below is optional and degrades gracefully if missing.
 */
defined('BASEPATH') OR exit('No direct script access allowed');
$caller_name      = isset($caller_name)      ? $caller_name      : '';
$business_name    = isset($business_name)    ? $business_name    : '';
$first_name       = trim(strtok((string) $caller_name, ' '));
$subject          = "Regarding your appointment request — " . $business_name;
?>
<p>Hi <?= htmlspecialchars($first_name ?: $caller_name) ?>,</p>
<p>Thank you for reaching out to <strong><?= htmlspecialchars($business_name) ?></strong>.</p>
<p>Unfortunately, we're unable to accommodate the appointment time you requested. We apologize for any inconvenience.</p>
<p>If you'd like to explore other options or have any questions, please feel free to reply to this email or contact us directly.</p>
<p>Best regards,<br><?= htmlspecialchars($business_name) ?> team</p>