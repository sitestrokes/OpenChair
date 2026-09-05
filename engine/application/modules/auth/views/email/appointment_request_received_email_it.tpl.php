<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$caller_name      = isset($caller_name)      ? $caller_name      : '';
$business_name    = isset($business_name)    ? $business_name    : 'il nostro team';
$appointment_date = isset($appointment_date) ? $appointment_date : '';
$appointment_time = isset($appointment_time) ? $appointment_time : '';
$service          = isset($service)          ? $service          : '';
$first_name       = trim(strtok((string) $caller_name, ' '));
$when_phrase      = ($appointment_date && $appointment_time) ? " per il {$appointment_date} alle {$appointment_time}"
                  : ($appointment_date ? " per il {$appointment_date}" : '');
$service_phrase   = $service ? " per {$service}" : '';
$subject          = "Abbiamo ricevuto la tua richiesta di appuntamento";
?>
<p>Ciao <?= htmlspecialchars($first_name ?: '') ?>,</p>
<p>Grazie per la tua richiesta di appuntamento<?= htmlspecialchars($service_phrase . $when_phrase) ?>.</p>
<p>Il nostro team la esaminerà e ti invierà una conferma a breve. Se nel frattempo hai domande, basta rispondere a questa email.</p>
<p>A presto,<br><?= htmlspecialchars($business_name) ?></p>
