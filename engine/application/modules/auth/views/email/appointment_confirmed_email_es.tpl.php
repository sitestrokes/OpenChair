<?php
/**
 * Appointment confirmation — Spanish (es).
 * Same variables as the _en template.
 */
defined('BASEPATH') OR exit('No direct script access allowed');
$caller_name      = isset($caller_name)      ? $caller_name      : '';
$business_name    = isset($business_name)    ? $business_name    : '';
$appointment_date = isset($appointment_date) ? $appointment_date : '';
$appointment_time = isset($appointment_time) ? $appointment_time : '';
$service          = isset($service)          ? $service          : '';
$first_name       = trim(strtok((string) $caller_name, ' '));
$subject          = "Cita confirmada — " . $appointment_date . " a las " . $appointment_time;
?>
<p>Hola <?= htmlspecialchars($first_name ?: $caller_name) ?>,</p>
<p>Tu cita con <strong><?= htmlspecialchars($business_name) ?></strong> ha sido confirmada.</p>
<p>
  <strong>Fecha:</strong> <?= htmlspecialchars($appointment_date) ?><br>
  <strong>Hora:</strong> <?= htmlspecialchars($appointment_time) ?>
  <?php if (!empty($service)): ?><br><strong>Servicio:</strong> <?= htmlspecialchars($service) ?><?php endif; ?>
</p>
<p>Si necesitas cambiar o cancelar tu cita, simplemente responde a este correo.</p>
<p>Nos vemos pronto,<br><?= htmlspecialchars($business_name) ?></p>