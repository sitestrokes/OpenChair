<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$caller_name      = isset($caller_name)      ? $caller_name      : '';
$business_name    = isset($business_name)    ? $business_name    : '';
$appointment_time = isset($appointment_time) ? $appointment_time : '';
$first_name       = trim(strtok((string) $caller_name, ' '));
?>
Hola <?= $first_name ?: '' ?>, recordatorio — tu cita con <?= $business_name ?> es hoy a las <?= $appointment_time ?>. Responde si algo cambia.
