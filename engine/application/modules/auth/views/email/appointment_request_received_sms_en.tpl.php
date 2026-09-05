<?php
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
?>
Hi <?= $first_name ?: 'there' ?>, thanks for your request<?= $service_phrase . $when_phrase ?>. <?= $business_name ?> will confirm shortly — talk soon!
