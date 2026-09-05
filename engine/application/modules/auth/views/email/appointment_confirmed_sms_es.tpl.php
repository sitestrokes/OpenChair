<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$caller_name      = isset($caller_name)      ? $caller_name      : '';
$business_name    = isset($business_name)    ? $business_name    : '';
$appointment_date = isset($appointment_date) ? $appointment_date : '';
$appointment_time = isset($appointment_time) ? $appointment_time : '';
$first_name       = trim(strtok((string) $caller_name, ' '));
?>
Hola <?= $first_name ?: $caller_name ?>. Tu cita en <?= $business_name ?> está confirmada para el <?= $appointment_date ?> a las <?= $appointment_time ?>. ¡Nos vemos!