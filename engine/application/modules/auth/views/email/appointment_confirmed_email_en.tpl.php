<?php
/**
 * Appointment confirmation — English (covers en, en-gb, en-sc, en-ie, en-wl).
 * Expected $vars:
 *   $caller_name, $business_name, $appointment_date, $appointment_time, $service
 * The library captures $subject and the echoed body.
 */
defined('BASEPATH') OR exit('No direct script access allowed');
$caller_name      = isset($caller_name)      ? $caller_name      : '';
$business_name    = isset($business_name)    ? $business_name    : '';
$appointment_date = isset($appointment_date) ? $appointment_date : '';
$appointment_time = isset($appointment_time) ? $appointment_time : '';
$service          = isset($service)          ? $service          : '';
$first_name       = trim(strtok((string) $caller_name, ' '));
$subject          = "Your appointment is confirmed — " . $appointment_date . " at " . $appointment_time;
?>
<p>Hi <?= htmlspecialchars($first_name ?: $caller_name) ?>,</p>
<p>Your appointment with <strong><?= htmlspecialchars($business_name) ?></strong> has been confirmed.</p>
<p>
  <strong>Date:</strong> <?= htmlspecialchars($appointment_date) ?><br>
  <strong>Time:</strong> <?= htmlspecialchars($appointment_time) ?>
  <?php if (!empty($service)): ?><br><strong>Service:</strong> <?= htmlspecialchars($service) ?><?php endif; ?>
</p>
<p>If you need to reschedule or cancel, simply reply to this email.</p>
<p>See you soon,<br><?= htmlspecialchars($business_name) ?></p>