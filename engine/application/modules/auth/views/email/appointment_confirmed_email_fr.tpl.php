<?php
/**
 * Appointment confirmation — French (fr).
 * Same variables as the _en template.
 */
defined('BASEPATH') OR exit('No direct script access allowed');
$caller_name      = isset($caller_name)      ? $caller_name      : '';
$business_name    = isset($business_name)    ? $business_name    : '';
$appointment_date = isset($appointment_date) ? $appointment_date : '';
$appointment_time = isset($appointment_time) ? $appointment_time : '';
$service          = isset($service)          ? $service          : '';
$first_name       = trim(strtok((string) $caller_name, ' '));
$subject          = "Rendez-vous confirmé — " . $appointment_date . " à " . $appointment_time;
?>
<p>Bonjour <?= htmlspecialchars($first_name ?: $caller_name) ?>,</p>
<p>Votre rendez-vous avec <strong><?= htmlspecialchars($business_name) ?></strong> est confirmé.</p>
<p>
  <strong>Date :</strong> <?= htmlspecialchars($appointment_date) ?><br>
  <strong>Heure :</strong> <?= htmlspecialchars($appointment_time) ?>
  <?php if (!empty($service)): ?><br><strong>Service :</strong> <?= htmlspecialchars($service) ?><?php endif; ?>
</p>
<p>Si vous devez reporter ou annuler, il vous suffit de répondre à ce courriel.</p>
<p>À bientôt,<br><?= htmlspecialchars($business_name) ?></p>