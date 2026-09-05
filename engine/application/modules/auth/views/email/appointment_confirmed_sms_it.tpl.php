<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$caller_name      = isset($caller_name)      ? $caller_name      : '';
$business_name    = isset($business_name)    ? $business_name    : '';
$appointment_date = isset($appointment_date) ? $appointment_date : '';
$appointment_time = isset($appointment_time) ? $appointment_time : '';
$first_name       = trim(strtok((string) $caller_name, ' '));
?>
Ciao <?= $first_name ?: $caller_name ?>! Il tuo appuntamento presso <?= $business_name ?> è confermato per il <?= $appointment_date ?> alle <?= $appointment_time ?>. A presto!