<?php
/**
 * Appointment confirmation — Italian (it).
 * Same variables as the _en template.
 */
defined('BASEPATH') OR exit('No direct script access allowed');
$caller_name      = isset($caller_name)      ? $caller_name      : '';
$business_name    = isset($business_name)    ? $business_name    : '';
$appointment_date = isset($appointment_date) ? $appointment_date : '';
$appointment_time = isset($appointment_time) ? $appointment_time : '';
$service          = isset($service)          ? $service          : '';
$first_name       = trim(strtok((string) $caller_name, ' '));
$subject          = "Appuntamento confermato — " . $appointment_date . " alle " . $appointment_time;
?>
<p>Ciao <?= htmlspecialchars($first_name ?: $caller_name) ?>,</p>
<p>Il tuo appuntamento con <strong><?= htmlspecialchars($business_name) ?></strong> è stato confermato.</p>
<p>
  <strong>Data:</strong> <?= htmlspecialchars($appointment_date) ?><br>
  <strong>Ora:</strong> <?= htmlspecialchars($appointment_time) ?>
  <?php if (!empty($service)): ?><br><strong>Servizio:</strong> <?= htmlspecialchars($service) ?><?php endif; ?>
</p>
<p>Se hai bisogno di riprogrammare o annullare, basta rispondere a questa email.</p>
<p>A presto,<br><?= htmlspecialchars($business_name) ?></p>