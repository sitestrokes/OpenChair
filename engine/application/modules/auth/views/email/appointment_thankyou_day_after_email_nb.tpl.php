<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$caller_name   = isset($caller_name)   ? $caller_name   : '';
$business_name = isset($business_name) ? $business_name : '';
$first_name    = trim(strtok((string) $caller_name, ' '));
$subject       = "Takk — " . $business_name;
?>
<p>Hei <?= htmlspecialchars($first_name ?: 'der') ?>,</p>
<p>Takk for besøket i går. Vi håper du hadde en god opplevelse.</p>
<p>Har du spørsmål eller tilbakemeldinger, er det bare å svare på denne e-posten — vi hører gjerne fra deg.</p>
<p>Vi gleder oss til å holde kontakten,<br><?= htmlspecialchars($business_name) ?></p>
