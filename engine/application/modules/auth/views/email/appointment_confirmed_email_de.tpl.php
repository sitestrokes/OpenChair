<?php
/**
 * Appointment confirmation — German (de).
 * Same variables as the _en template.
 */
defined('BASEPATH') OR exit('No direct script access allowed');
$caller_name      = isset($caller_name)      ? $caller_name      : '';
$business_name    = isset($business_name)    ? $business_name    : '';
$appointment_date = isset($appointment_date) ? $appointment_date : '';
$appointment_time = isset($appointment_time) ? $appointment_time : '';
$service          = isset($service)          ? $service          : '';
$first_name       = trim(strtok((string) $caller_name, ' '));
$subject          = "Ihr Termin ist bestätigt — " . $appointment_date . " um " . $appointment_time;
?>
<p>Hallo <?= htmlspecialchars($first_name ?: $caller_name) ?>,</p>
<p>Ihr Termin bei <strong><?= htmlspecialchars($business_name) ?></strong> wurde bestätigt.</p>
<p>
  <strong>Datum:</strong> <?= htmlspecialchars($appointment_date) ?><br>
  <strong>Uhrzeit:</strong> <?= htmlspecialchars($appointment_time) ?>
  <?php if (!empty($service)): ?><br><strong>Leistung:</strong> <?= htmlspecialchars($service) ?><?php endif; ?>
</p>
<p>Falls Sie Ihren Termin verschieben oder absagen möchten, antworten Sie einfach auf diese E-Mail.</p>
<p>Wir freuen uns auf Sie,<br><?= htmlspecialchars($business_name) ?></p>