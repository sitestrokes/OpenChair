<?php
/**
 * Appointment confirmation — Norwegian Bokmål.
 * Expected $vars:
 *   $caller_name, $business_name, $appointment_date, $appointment_time, $service
 */
defined('BASEPATH') OR exit('No direct script access allowed');
$caller_name      = isset($caller_name)      ? $caller_name      : '';
$business_name    = isset($business_name)    ? $business_name    : '';
$appointment_date = isset($appointment_date) ? $appointment_date : '';
$appointment_time = isset($appointment_time) ? $appointment_time : '';
$service          = isset($service)          ? $service          : '';
$first_name       = trim(strtok((string) $caller_name, ' '));
$subject          = "Avtalen din er bekreftet — " . $appointment_date . " kl. " . $appointment_time;
?>
<p>Hei <?= htmlspecialchars($first_name ?: $caller_name) ?>,</p>
<p>Avtalen din hos <strong><?= htmlspecialchars($business_name) ?></strong> er nå bekreftet.</p>
<p>
  <strong>Dato:</strong> <?= htmlspecialchars($appointment_date) ?><br>
  <strong>Tidspunkt:</strong> <?= htmlspecialchars($appointment_time) ?>
  <?php if (!empty($service)): ?><br><strong>Tjeneste:</strong> <?= htmlspecialchars($service) ?><?php endif; ?>
</p>
<p>Trenger du å endre tidspunktet eller avbestille, er det bare å svare på denne e-posten.</p>
<p>Vi sees snart,<br><?= htmlspecialchars($business_name) ?></p>
