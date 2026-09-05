<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$caller_name      = isset($caller_name)      ? $caller_name      : '';
$business_name    = isset($business_name)    ? $business_name    : 'teamet vårt';
$appointment_date = isset($appointment_date) ? $appointment_date : '';
$appointment_time = isset($appointment_time) ? $appointment_time : '';
$service          = isset($service)          ? $service          : '';
$first_name       = trim(strtok((string) $caller_name, ' '));
$when_phrase      = ($appointment_date && $appointment_time) ? " den {$appointment_date} kl. {$appointment_time}"
                  : ($appointment_date ? " den {$appointment_date}" : '');
$service_phrase   = $service ? " for {$service}" : '';
?>
Hei <?= $first_name ?: 'der' ?>, takk for forespørselen<?= $service_phrase . $when_phrase ?>. <?= $business_name ?> bekrefter snart — vi snakkes!