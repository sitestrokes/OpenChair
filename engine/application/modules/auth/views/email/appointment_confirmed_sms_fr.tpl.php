<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$caller_name      = isset($caller_name)      ? $caller_name      : '';
$business_name    = isset($business_name)    ? $business_name    : '';
$appointment_date = isset($appointment_date) ? $appointment_date : '';
$appointment_time = isset($appointment_time) ? $appointment_time : '';
$first_name       = trim(strtok((string) $caller_name, ' '));
?>
Bonjour <?= $first_name ?: $caller_name ?>. Votre rendez-vous chez <?= $business_name ?> est confirmé pour le <?= $appointment_date ?> à <?= $appointment_time ?>. À bientôt !