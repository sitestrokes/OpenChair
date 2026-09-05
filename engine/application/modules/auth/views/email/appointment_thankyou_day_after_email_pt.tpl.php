<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$caller_name   = isset($caller_name)   ? $caller_name   : '';
$business_name = isset($business_name) ? $business_name : '';
$first_name    = trim(strtok((string) $caller_name, ' '));
$subject       = "Obrigado — " . $business_name;
?>
<p>Olá <?= htmlspecialchars($first_name ?: '') ?>,</p>
<p>Obrigado pelo seu compromisso de ontem. Esperamos que tenha sido uma ótima experiência.</p>
<p>Se tiver alguma dúvida ou feedback, basta responder a este e-mail — adoraríamos ouvir você.</p>
<p>Até logo,<br><?= htmlspecialchars($business_name) ?></p>
