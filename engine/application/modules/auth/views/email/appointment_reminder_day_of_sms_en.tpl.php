<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$caller_name      = isset($caller_name)      ? $caller_name      : '';
$business_name    = isset($business_name)    ? $business_name    : '';
$appointment_time = isset($appointment_time) ? $appointment_time : '';
$first_name       = trim(strtok((string) $caller_name, ' '));
?>
Hi <?= $first_name ?: 'there' ?>, reminder — your appointment with <?= $business_name ?> is today at <?= $appointment_time ?>. Reply if anything changes.
