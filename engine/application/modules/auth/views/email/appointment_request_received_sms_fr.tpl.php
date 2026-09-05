<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$caller_name      = isset($caller_name)      ? $caller_name      : '';
$business_name    = isset($business_name)    ? $business_name    : 'notre équipe';
$appointment_date = isset($appointment_date) ? $appointment_date : '';
$appointment_time = isset($appointment_time) ? $appointment_time : '';
$service          = isset($service)          ? $service          : '';
$first_name       = trim(strtok((string) $caller_name, ' '));
$when_phrase      = ($appointment_date && $appointment_time) ? " le {$appointment_date} à {$appointment_time}"
                  : ($appointment_date ? " le {$appointment_date}" : '');
$service_phrase   = $service ? " pour {$service}" : '';
?>
Bonjour <?= $first_name ?: '' ?>, merci pour votre demande<?= $service_phrase . $when_phrase ?>. <?= $business_name ?> confirmera sous peu. À bientôt !
