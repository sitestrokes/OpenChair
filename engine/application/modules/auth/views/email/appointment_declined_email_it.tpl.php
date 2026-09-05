<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$caller_name      = isset($caller_name)      ? $caller_name      : '';
$business_name    = isset($business_name)    ? $business_name    : '';
$first_name       = trim(strtok((string) $caller_name, ' '));
$subject          = "In merito alla tua richiesta di appuntamento — " . $business_name;
?>
<p>Ciao <?= htmlspecialchars($first_name ?: $caller_name) ?>,</p>
<p>Grazie per aver contattato <strong><?= htmlspecialchars($business_name) ?></strong>.</p>
<p>Purtroppo non possiamo confermare l'appuntamento nell'orario che hai richiesto. Ci scusiamo per l'inconveniente.</p>
<p>Se vuoi esplorare altre opzioni o hai domande, basta rispondere a questa email o contattarci direttamente.</p>
<p>Cordiali saluti,<br>Il team di <?= htmlspecialchars($business_name) ?></p>
