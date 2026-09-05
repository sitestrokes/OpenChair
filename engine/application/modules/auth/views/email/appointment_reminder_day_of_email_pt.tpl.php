<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$caller_name      = isset($caller_name)      ? $caller_name      : '';
$business_name    = isset($business_name)    ? $business_name    : '';
$appointment_date = isset($appointment_date) ? $appointment_date : '';
$appointment_time = isset($appointment_time) ? $appointment_time : '';
$service          = isset($service)          ? $service          : '';
$first_name       = trim(strtok((string) $caller_name, ' '));
$subject          = "Lembrete: seu compromisso hoje — " . $business_name;
?>
<p>Olá <?= htmlspecialchars($first_name ?: '') ?>,</p>
<p>Um lembrete amigável sobre seu compromisso de hoje.</p>
<p>
  <strong>Data:</strong> <?= htmlspecialchars($appointment_date) ?><br>
  <strong>Horário:</strong> <?= htmlspecialchars($appointment_time) ?>
  <?php if (!empty($service)): ?><br><strong>Serviço:</strong> <?= htmlspecialchars($service) ?><?php endif; ?>
</p>
<p>Chegue alguns minutos antes, por favor. Se algo mudar, basta responder a este e-mail.</p>
<p>Até logo,<br><?= htmlspecialchars($business_name) ?></p>
