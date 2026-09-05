<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$caller_name      = isset($caller_name)      ? $caller_name      : '';
$business_name    = isset($business_name)    ? $business_name    : '';
$appointment_date = isset($appointment_date) ? $appointment_date : '';
$appointment_time = isset($appointment_time) ? $appointment_time : '';
$first_name       = trim(strtok((string) $caller_name, ' '));
?>
Hei <?= $first_name ?: $caller_name ?>! Avtalen din hos <?= $business_name ?> er bekreftet <?= $appointment_date ?> kl. <?= $appointment_time ?>. Vi sees!