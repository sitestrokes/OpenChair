<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$caller_name   = isset($caller_name)   ? $caller_name   : '';
$business_name = isset($business_name) ? $business_name : '';
$first_name    = trim(strtok((string) $caller_name, ' '));
$subject       = "Grazie — " . $business_name;
?>
<p>Ciao <?= htmlspecialchars($first_name ?: '') ?>,</p>
<p>grazie per l'appuntamento di ieri. Speriamo che sia stata un'ottima esperienza.</p>
<p>Se hai domande o feedback, basta rispondere a questa email — ci farebbe piacere sentirti.</p>
<p>A presto,<br><?= htmlspecialchars($business_name) ?></p>
