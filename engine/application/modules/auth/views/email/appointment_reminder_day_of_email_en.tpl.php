<?php
/**
 * Day-of appointment reminder email — English.
 * Scheduled for 9am local on the appointment date.
 */
defined('BASEPATH') OR exit('No direct script access allowed');
$caller_name      = isset($caller_name)      ? $caller_name      : '';
$business_name    = isset($business_name)    ? $business_name    : '';
$appointment_date = isset($appointment_date) ? $appointment_date : '';
$appointment_time = isset($appointment_time) ? $appointment_time : '';
$service          = isset($service)          ? $service          : '';
$first_name       = trim(strtok((string) $caller_name, ' '));
$subject          = "Reminder: your appointment today — " . $business_name;
?>
<p>Hi <?= htmlspecialchars($first_name ?: 'there') ?>,</p>
<p>Friendly reminder about your appointment today.</p>
<p>
  <strong>Date:</strong> <?= htmlspecialchars($appointment_date) ?><br>
  <strong>Time:</strong> <?= htmlspecialchars($appointment_time) ?>
  <?php if (!empty($service)): ?><br><strong>Service:</strong> <?= htmlspecialchars($service) ?><?php endif; ?>
</p>
<p>Please arrive a few minutes early. If anything changes, just reply to this email.</p>
<p>See you soon,<br><?= htmlspecialchars($business_name) ?></p>
