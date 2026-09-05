<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$caller_name      = isset($caller_name)      ? $caller_name      : '';
$business_name    = isset($business_name)    ? $business_name    : '';
$first_name       = trim(strtok((string) $caller_name, ' '));
$subject          = "Au sujet de votre demande de rendez-vous — " . $business_name;
?>
<p>Bonjour <?= htmlspecialchars($first_name ?: $caller_name) ?>,</p>
<p>Merci d'avoir contacté <strong><?= htmlspecialchars($business_name) ?></strong>.</p>
<p>Malheureusement, nous ne pouvons pas vous accueillir au créneau demandé. Nous nous excusons pour la gêne occasionnée.</p>
<p>Si vous souhaitez explorer d'autres options ou avez des questions, n'hésitez pas à répondre à ce courriel ou à nous contacter directement.</p>
<p>Cordialement,<br>L'équipe <?= htmlspecialchars($business_name) ?></p>
