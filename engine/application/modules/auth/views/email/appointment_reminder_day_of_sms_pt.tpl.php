<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$caller_name      = isset($caller_name)      ? $caller_name      : '';
$business_name    = isset($business_name)    ? $business_name    : '';
$appointment_time = isset($appointment_time) ? $appointment_time : '';
$first_name       = trim(strtok((string) $caller_name, ' '));
?>
Olá <?= $first_name ?: '' ?>, lembrete — seu compromisso com <?= $business_name ?> é hoje às <?= $appointment_time ?>. Responda se algo mudar.
