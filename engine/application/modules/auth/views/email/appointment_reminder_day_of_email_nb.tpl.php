<?php
/**
 * Day-of appointment reminder email — Norwegian Bokmål.
 * Scheduled for 9am local on the appointment date.
 */
defined('BASEPATH') OR exit('No direct script access allowed');
$caller_name      = isset($caller_name)      ? $caller_name      : '';
$business_name    = isset($business_name)    ? $business_name    : '';
$appointment_date = isset($appointment_date) ? $appointment_date : '';
$appointment_time = isset($appointment_time) ? $appointment_time : '';
$service          = isset($service)          ? $service          : '';
$first_name       = trim(strtok((string) $caller_name, ' '));
$subject          = "Påminnelse: avtalen din i dag — " . $business_name;
?>
<p>Hei <?= htmlspecialchars($first_name ?: 'der') ?>,</p>
<p>En liten påminnelse om avtalen din i dag.</p>
<p>
  <strong>Dato:</strong> <?= htmlspecialchars($appointment_date) ?><br>
  <strong>Tidspunkt:</strong> <?= htmlspecialchars($appointment_time) ?>
  <?php if (!empty($service)): ?><br><strong>Tjeneste:</strong> <?= htmlspecialchars($service) ?><?php endif; ?>
</p>
<p>Vennligst kom noen minutter før avtalt tid. Endrer noe seg, er det bare å svare på denne e-posten.</p>
<p>Vi sees snart,<br><?= htmlspecialchars($business_name) ?></p>
