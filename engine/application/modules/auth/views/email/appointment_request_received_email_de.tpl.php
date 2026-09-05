<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$caller_name      = isset($caller_name)      ? $caller_name      : '';
$business_name    = isset($business_name)    ? $business_name    : 'unser Team';
$appointment_date = isset($appointment_date) ? $appointment_date : '';
$appointment_time = isset($appointment_time) ? $appointment_time : '';
$service          = isset($service)          ? $service          : '';
$first_name       = trim(strtok((string) $caller_name, ' '));
$when_phrase      = ($appointment_date && $appointment_time) ? " für den {$appointment_date} um {$appointment_time}"
                  : ($appointment_date ? " für den {$appointment_date}" : '');
$service_phrase   = $service ? " für {$service}" : '';
$subject          = "Wir haben Ihre Terminanfrage erhalten";
?>
<p>Hallo <?= htmlspecialchars($first_name ?: '') ?>,</p>
<p>vielen Dank für Ihre Terminanfrage<?= htmlspecialchars($service_phrase . $when_phrase) ?>.</p>
<p>Unser Team prüft die Anfrage und sendet Ihnen in Kürze eine Bestätigung. Falls Sie Fragen haben, antworten Sie einfach auf diese E-Mail.</p>
<p>Bis bald,<br><?= htmlspecialchars($business_name) ?></p>
