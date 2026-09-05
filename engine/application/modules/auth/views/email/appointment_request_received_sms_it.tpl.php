<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$caller_name      = isset($caller_name)      ? $caller_name      : '';
$business_name    = isset($business_name)    ? $business_name    : 'il nostro team';
$appointment_date = isset($appointment_date) ? $appointment_date : '';
$appointment_time = isset($appointment_time) ? $appointment_time : '';
$service          = isset($service)          ? $service          : '';
$first_name       = trim(strtok((string) $caller_name, ' '));
$when_phrase      = ($appointment_date && $appointment_time) ? " per il {$appointment_date} alle {$appointment_time}"
                  : ($appointment_date ? " per il {$appointment_date}" : '');
$service_phrase   = $service ? " per {$service}" : '';
?>
Ciao <?= $first_name ?: '' ?>, grazie per la richiesta<?= $service_phrase . $when_phrase ?>. <?= $business_name ?> confermerà a breve. A presto!
