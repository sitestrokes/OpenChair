<?php
/**
 * Sent right after the AI books an appointment, BEFORE admin approves.
 * Vars: $caller_name, $business_name, $appointment_date, $appointment_time, $service
 */
defined('BASEPATH') OR exit('No direct script access allowed');
$caller_name      = isset($caller_name)      ? $caller_name      : '';
$business_name    = isset($business_name)    ? $business_name    : 'our team';
$appointment_date = isset($appointment_date) ? $appointment_date : '';
$appointment_time = isset($appointment_time) ? $appointment_time : '';
$service          = isset($service)          ? $service          : '';
$first_name       = trim(strtok((string) $caller_name, ' '));
$when_phrase      = ($appointment_date && $appointment_time) ? " on {$appointment_date} at {$appointment_time}"
                  : ($appointment_date ? " on {$appointment_date}" : '');
$service_phrase   = $service ? " for {$service}" : '';
$subject          = "We received your appointment request";
?>
<p>Hi <?= htmlspecialchars($first_name ?: 'there') ?>,</p>
<p>Thanks for your appointment request<?= htmlspecialchars($service_phrase . $when_phrase) ?>.</p>
<p>Our team will review and send you a confirmation shortly. If you have any questions in the meantime, just reply to this email.</p>
<p>Talk soon,<br><?= htmlspecialchars($business_name) ?></p>
