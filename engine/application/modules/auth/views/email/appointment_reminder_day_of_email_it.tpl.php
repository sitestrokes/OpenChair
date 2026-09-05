<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$caller_name      = isset($caller_name)      ? $caller_name      : '';
$business_name    = isset($business_name)    ? $business_name    : '';
$appointment_date = isset($appointment_date) ? $appointment_date : '';
$appointment_time = isset($appointment_time) ? $appointment_time : '';
$service          = isset($service)          ? $service          : '';
$first_name       = trim(strtok((string) $caller_name, ' '));
$subject          = "Promemoria: il tuo appuntamento oggi — " . $business_name;
?>
<p>Ciao <?= htmlspecialchars($first_name ?: '') ?>,</p>
<p>un promemoria amichevole per il tuo appuntamento di oggi.</p>
<p>
  <strong>Data:</strong> <?= htmlspecialchars($appointment_date) ?><br>
  <strong>Ora:</strong> <?= htmlspecialchars($appointment_time) ?>
  <?php if (!empty($service)): ?><br><strong>Servizio:</strong> <?= htmlspecialchars($service) ?><?php endif; ?>
</p>
<p>Ti chiediamo di arrivare qualche minuto prima. Se qualcosa cambia, basta rispondere a questa email.</p>
<p>A presto,<br><?= htmlspecialchars($business_name) ?></p>
