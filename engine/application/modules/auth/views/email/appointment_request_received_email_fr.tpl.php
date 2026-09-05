<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$caller_name      = isset($caller_name)      ? $caller_name      : '';
$business_name    = isset($business_name)    ? $business_name    : 'notre équipe';
$appointment_date = isset($appointment_date) ? $appointment_date : '';
$appointment_time = isset($appointment_time) ? $appointment_time : '';
$service          = isset($service)          ? $service          : '';
$first_name       = trim(strtok((string) $caller_name, ' '));
$when_phrase      = ($appointment_date && $appointment_time) ? " le {$appointment_date} à {$appointment_time}"
                  : ($appointment_date ? " le {$appointment_date}" : '');
$service_phrase   = $service ? " pour {$service}" : '';
$subject          = "Nous avons reçu votre demande de rendez-vous";
?>
<p>Bonjour <?= htmlspecialchars($first_name ?: '') ?>,</p>
<p>Merci pour votre demande de rendez-vous<?= htmlspecialchars($service_phrase . $when_phrase) ?>.</p>
<p>Notre équipe va l'examiner et vous enverra une confirmation sous peu. Si vous avez des questions entre-temps, répondez simplement à ce courriel.</p>
<p>À bientôt,<br><?= htmlspecialchars($business_name) ?></p>
