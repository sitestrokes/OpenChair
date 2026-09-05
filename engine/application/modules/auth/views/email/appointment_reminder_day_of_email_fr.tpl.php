<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$caller_name      = isset($caller_name)      ? $caller_name      : '';
$business_name    = isset($business_name)    ? $business_name    : '';
$appointment_date = isset($appointment_date) ? $appointment_date : '';
$appointment_time = isset($appointment_time) ? $appointment_time : '';
$service          = isset($service)          ? $service          : '';
$first_name       = trim(strtok((string) $caller_name, ' '));
$subject          = "Rappel : votre rendez-vous aujourd'hui — " . $business_name;
?>
<p>Bonjour <?= htmlspecialchars($first_name ?: '') ?>,</p>
<p>Petit rappel pour votre rendez-vous d'aujourd'hui.</p>
<p>
  <strong>Date :</strong> <?= htmlspecialchars($appointment_date) ?><br>
  <strong>Heure :</strong> <?= htmlspecialchars($appointment_time) ?>
  <?php if (!empty($service)): ?><br><strong>Service :</strong> <?= htmlspecialchars($service) ?><?php endif; ?>
</p>
<p>Merci d'arriver quelques minutes en avance. Si quoi que ce soit change, répondez simplement à ce courriel.</p>
<p>À tout de suite,<br><?= htmlspecialchars($business_name) ?></p>
