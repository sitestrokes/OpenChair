<?php
/**
 * Sent right after the AI books an appointment, BEFORE admin approves.
 * Norwegian Bokmål.
 */
defined('BASEPATH') OR exit('No direct script access allowed');
$caller_name      = isset($caller_name)      ? $caller_name      : '';
$business_name    = isset($business_name)    ? $business_name    : 'teamet vårt';
$appointment_date = isset($appointment_date) ? $appointment_date : '';
$appointment_time = isset($appointment_time) ? $appointment_time : '';
$service          = isset($service)          ? $service          : '';
$first_name       = trim(strtok((string) $caller_name, ' '));
$when_phrase      = ($appointment_date && $appointment_time) ? " den {$appointment_date} kl. {$appointment_time}"
                  : ($appointment_date ? " den {$appointment_date}" : '');
$service_phrase   = $service ? " for {$service}" : '';
$subject          = "Vi har mottatt avtaleforespørselen din";
?>
<p>Hei <?= htmlspecialchars($first_name ?: 'der') ?>,</p>
<p>Takk for avtaleforespørselen<?= htmlspecialchars($service_phrase . $when_phrase) ?>.</p>
<p>Teamet vårt går gjennom forespørselen og sender deg en bekreftelse i løpet av kort tid. Har du spørsmål i mellomtiden, er det bare å svare på denne e-posten.</p>
<p>Vi snakkes snart,<br><?= htmlspecialchars($business_name) ?></p>
