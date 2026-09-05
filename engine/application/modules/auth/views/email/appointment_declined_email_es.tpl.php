<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$caller_name      = isset($caller_name)      ? $caller_name      : '';
$business_name    = isset($business_name)    ? $business_name    : '';
$first_name       = trim(strtok((string) $caller_name, ' '));
$subject          = "Sobre tu solicitud de cita — " . $business_name;
?>
<p>Hola <?= htmlspecialchars($first_name ?: $caller_name) ?>,</p>
<p>Gracias por contactar con <strong><?= htmlspecialchars($business_name) ?></strong>.</p>
<p>Lamentablemente, no podemos atenderte en el horario que solicitaste. Disculpa las molestias.</p>
<p>Si quieres explorar otras opciones o tienes alguna pregunta, responde a este correo o contáctanos directamente.</p>
<p>Un saludo,<br>Equipo de <?= htmlspecialchars($business_name) ?></p>