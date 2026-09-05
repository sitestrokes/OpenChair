<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$caller_name      = isset($caller_name)      ? $caller_name      : '';
$business_name    = isset($business_name)    ? $business_name    : '';
$appointment_time = isset($appointment_time) ? $appointment_time : '';
$first_name       = trim(strtok((string) $caller_name, ' '));
?>
Bonjour <?= $first_name ?: '' ?>, rappel — votre rendez-vous chez <?= $business_name ?> est aujourd'hui à <?= $appointment_time ?>. Répondez si quelque chose change.
