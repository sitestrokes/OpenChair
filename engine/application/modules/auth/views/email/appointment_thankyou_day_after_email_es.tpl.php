<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$caller_name   = isset($caller_name)   ? $caller_name   : '';
$business_name = isset($business_name) ? $business_name : '';
$first_name    = trim(strtok((string) $caller_name, ' '));
$subject       = "Gracias — " . $business_name;
?>
<p>Hola <?= htmlspecialchars($first_name ?: '') ?>,</p>
<p>Gracias por tu cita de ayer. Esperamos que la experiencia haya sido excelente.</p>
<p>Si tienes alguna pregunta o comentario, simplemente responde a este correo — nos encantaría saber de ti.</p>
<p>Hasta pronto,<br><?= htmlspecialchars($business_name) ?></p>
