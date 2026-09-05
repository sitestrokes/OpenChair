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
?>
Hallo <?= $first_name ?: '' ?>, danke für Ihre Anfrage<?= $service_phrase . $when_phrase ?>. <?= $business_name ?> bestätigt in Kürze — bis bald!
