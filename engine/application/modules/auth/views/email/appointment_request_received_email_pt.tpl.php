<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$caller_name      = isset($caller_name)      ? $caller_name      : '';
$business_name    = isset($business_name)    ? $business_name    : 'nossa equipe';
$appointment_date = isset($appointment_date) ? $appointment_date : '';
$appointment_time = isset($appointment_time) ? $appointment_time : '';
$service          = isset($service)          ? $service          : '';
$first_name       = trim(strtok((string) $caller_name, ' '));
$when_phrase      = ($appointment_date && $appointment_time) ? " para {$appointment_date} às {$appointment_time}"
                  : ($appointment_date ? " para {$appointment_date}" : '');
$service_phrase   = $service ? " sobre {$service}" : '';
$subject          = "Recebemos seu pedido de compromisso";
?>
<p>Olá <?= htmlspecialchars($first_name ?: '') ?>,</p>
<p>Obrigado pelo seu pedido de compromisso<?= htmlspecialchars($service_phrase . $when_phrase) ?>.</p>
<p>Nossa equipe vai analisar e enviar a confirmação em breve. Se tiver alguma dúvida nesse meio tempo, basta responder a este e-mail.</p>
<p>Até logo,<br><?= htmlspecialchars($business_name) ?></p>
