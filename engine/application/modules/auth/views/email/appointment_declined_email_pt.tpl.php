<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$caller_name      = isset($caller_name)      ? $caller_name      : '';
$business_name    = isset($business_name)    ? $business_name    : '';
$first_name       = trim(strtok((string) $caller_name, ' '));
$subject          = "Sobre seu pedido de compromisso — " . $business_name;
?>
<p>Olá <?= htmlspecialchars($first_name ?: $caller_name) ?>,</p>
<p>Obrigado por entrar em contato com a <strong><?= htmlspecialchars($business_name) ?></strong>.</p>
<p>Infelizmente, não podemos atender no horário que você solicitou. Pedimos desculpas pelo inconveniente.</p>
<p>Se quiser explorar outras opções ou tiver dúvidas, é só responder a este e-mail ou nos contatar diretamente.</p>
<p>Atenciosamente,<br>Equipe <?= htmlspecialchars($business_name) ?></p>
