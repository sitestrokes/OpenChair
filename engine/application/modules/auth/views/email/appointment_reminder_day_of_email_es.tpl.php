<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$caller_name      = isset($caller_name)      ? $caller_name      : '';
$business_name    = isset($business_name)    ? $business_name    : '';
$appointment_date = isset($appointment_date) ? $appointment_date : '';
$appointment_time = isset($appointment_time) ? $appointment_time : '';
$service          = isset($service)          ? $service          : '';
$first_name       = trim(strtok((string) $caller_name, ' '));
$subject          = "Recordatorio: tu cita hoy — " . $business_name;
?>
<p>Hola <?= htmlspecialchars($first_name ?: '') ?>,</p>
<p>Un recordatorio amistoso sobre tu cita de hoy.</p>
<p>
  <strong>Fecha:</strong> <?= htmlspecialchars($appointment_date) ?><br>
  <strong>Hora:</strong> <?= htmlspecialchars($appointment_time) ?>
  <?php if (!empty($service)): ?><br><strong>Servicio:</strong> <?= htmlspecialchars($service) ?><?php endif; ?>
</p>
<p>Por favor, llega unos minutos antes. Si algo cambia, simplemente responde a este correo.</p>
<p>Nos vemos pronto,<br><?= htmlspecialchars($business_name) ?></p>
