<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$caller_name      = isset($caller_name)      ? $caller_name      : '';
$business_name    = isset($business_name)    ? $business_name    : 'nuestro equipo';
$appointment_date = isset($appointment_date) ? $appointment_date : '';
$appointment_time = isset($appointment_time) ? $appointment_time : '';
$service          = isset($service)          ? $service          : '';
$first_name       = trim(strtok((string) $caller_name, ' '));
$when_phrase      = ($appointment_date && $appointment_time) ? " para el {$appointment_date} a las {$appointment_time}"
                  : ($appointment_date ? " para el {$appointment_date}" : '');
$service_phrase   = $service ? " sobre {$service}" : '';
?>
Hola <?= $first_name ?: '' ?>, gracias por tu solicitud<?= $service_phrase . $when_phrase ?>. <?= $business_name ?> confirmará en breve. ¡Hablamos pronto!
