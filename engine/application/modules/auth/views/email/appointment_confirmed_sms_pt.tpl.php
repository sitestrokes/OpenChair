<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$caller_name      = isset($caller_name)      ? $caller_name      : '';
$business_name    = isset($business_name)    ? $business_name    : '';
$appointment_date = isset($appointment_date) ? $appointment_date : '';
$appointment_time = isset($appointment_time) ? $appointment_time : '';
$first_name       = trim(strtok((string) $caller_name, ' '));
?>
Olá <?= $first_name ?: $caller_name ?>! Seu compromisso na <?= $business_name ?> está confirmado para <?= $appointment_date ?> às <?= $appointment_time ?>. Até lá!