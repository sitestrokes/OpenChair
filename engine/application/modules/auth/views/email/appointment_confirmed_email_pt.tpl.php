<?php
/**
 * Appointment confirmation — Portuguese (pt). Defaults to pt-BR style; clear
 * enough for European Portuguese readers too.
 */
defined('BASEPATH') OR exit('No direct script access allowed');
$caller_name      = isset($caller_name)      ? $caller_name      : '';
$business_name    = isset($business_name)    ? $business_name    : '';
$appointment_date = isset($appointment_date) ? $appointment_date : '';
$appointment_time = isset($appointment_time) ? $appointment_time : '';
$service          = isset($service)          ? $service          : '';
$first_name       = trim(strtok((string) $caller_name, ' '));
$subject          = "Compromisso confirmado — " . $appointment_date . " às " . $appointment_time;
?>
<p>Olá <?= htmlspecialchars($first_name ?: $caller_name) ?>,</p>
<p>Seu compromisso com <strong><?= htmlspecialchars($business_name) ?></strong> foi confirmado.</p>
<p>
  <strong>Data:</strong> <?= htmlspecialchars($appointment_date) ?><br>
  <strong>Horário:</strong> <?= htmlspecialchars($appointment_time) ?>
  <?php if (!empty($service)): ?><br><strong>Serviço:</strong> <?= htmlspecialchars($service) ?><?php endif; ?>
</p>
<p>Se precisar remarcar ou cancelar, basta responder a este e-mail.</p>
<p>Até logo,<br><?= htmlspecialchars($business_name) ?></p>