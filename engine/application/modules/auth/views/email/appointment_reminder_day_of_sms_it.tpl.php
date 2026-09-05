<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$caller_name      = isset($caller_name)      ? $caller_name      : '';
$business_name    = isset($business_name)    ? $business_name    : '';
$appointment_time = isset($appointment_time) ? $appointment_time : '';
$first_name       = trim(strtok((string) $caller_name, ' '));
?>
Ciao <?= $first_name ?: '' ?>, promemoria — il tuo appuntamento presso <?= $business_name ?> è oggi alle <?= $appointment_time ?>. Rispondi se cambia qualcosa.
