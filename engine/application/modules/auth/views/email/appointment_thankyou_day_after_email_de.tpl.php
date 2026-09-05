<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$caller_name   = isset($caller_name)   ? $caller_name   : '';
$business_name = isset($business_name) ? $business_name : '';
$first_name    = trim(strtok((string) $caller_name, ' '));
$subject       = "Vielen Dank — " . $business_name;
?>
<p>Hallo <?= htmlspecialchars($first_name ?: '') ?>,</p>
<p>vielen Dank für Ihren gestrigen Termin. Wir hoffen, Sie hatten ein tolles Erlebnis.</p>
<p>Wenn Sie Fragen oder Feedback haben, antworten Sie einfach auf diese E-Mail — wir freuen uns, von Ihnen zu hören.</p>
<p>Bleiben wir in Kontakt,<br><?= htmlspecialchars($business_name) ?></p>
