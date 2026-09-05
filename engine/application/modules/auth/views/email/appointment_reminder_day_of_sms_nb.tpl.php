<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$caller_name      = isset($caller_name)      ? $caller_name      : '';
$business_name    = isset($business_name)    ? $business_name    : '';
$appointment_time = isset($appointment_time) ? $appointment_time : '';
$first_name       = trim(strtok((string) $caller_name, ' '));
?>
Hei <?= $first_name ?: 'der' ?>, påminnelse — avtalen din hos <?= $business_name ?> er i dag kl. <?= $appointment_time ?>. Svar hvis noe endrer seg.