<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$caller_name      = isset($caller_name)      ? $caller_name      : '';
$business_name    = isset($business_name)    ? $business_name    : '';
$appointment_time = isset($appointment_time) ? $appointment_time : '';
$first_name       = trim(strtok((string) $caller_name, ' '));
?>
Hallo <?= $first_name ?: '' ?>, Erinnerung — Ihr Termin bei <?= $business_name ?> ist heute um <?= $appointment_time ?>. Antworten Sie, wenn sich etwas ändert.
