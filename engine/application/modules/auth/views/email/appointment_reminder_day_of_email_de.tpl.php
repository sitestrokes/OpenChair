<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$caller_name      = isset($caller_name)      ? $caller_name      : '';
$business_name    = isset($business_name)    ? $business_name    : '';
$appointment_date = isset($appointment_date) ? $appointment_date : '';
$appointment_time = isset($appointment_time) ? $appointment_time : '';
$service          = isset($service)          ? $service          : '';
$first_name       = trim(strtok((string) $caller_name, ' '));
$subject          = "Erinnerung: Ihr Termin heute — " . $business_name;
?>
<p>Hallo <?= htmlspecialchars($first_name ?: '') ?>,</p>
<p>eine freundliche Erinnerung an Ihren heutigen Termin.</p>
<p>
  <strong>Datum:</strong> <?= htmlspecialchars($appointment_date) ?><br>
  <strong>Uhrzeit:</strong> <?= htmlspecialchars($appointment_time) ?>
  <?php if (!empty($service)): ?><br><strong>Leistung:</strong> <?= htmlspecialchars($service) ?><?php endif; ?>
</p>
<p>Bitte erscheinen Sie ein paar Minuten früher. Falls sich etwas ändert, antworten Sie einfach auf diese E-Mail.</p>
<p>Bis gleich,<br><?= htmlspecialchars($business_name) ?></p>
