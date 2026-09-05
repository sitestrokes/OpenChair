<?php
/**
 * SMS confirmation — English. Plain text only, no HTML. ~160 char target.
 * Same vars as the email templates: $caller_name, $business_name,
 * $appointment_date, $appointment_time, $service.
 *
 * SMS templates have no $subject — the library returns it empty and the
 * caller in Leads::preview_appointment uses the rendered body as the SMS body.
 */
defined('BASEPATH') OR exit('No direct script access allowed');
$caller_name      = isset($caller_name)      ? $caller_name      : '';
$business_name    = isset($business_name)    ? $business_name    : '';
$appointment_date = isset($appointment_date) ? $appointment_date : '';
$appointment_time = isset($appointment_time) ? $appointment_time : '';
$first_name       = trim(strtok((string) $caller_name, ' '));
?>
Hi <?= $first_name ?: $caller_name ?>! Your appointment at <?= $business_name ?> is confirmed for <?= $appointment_date ?> at <?= $appointment_time ?>. See you then!