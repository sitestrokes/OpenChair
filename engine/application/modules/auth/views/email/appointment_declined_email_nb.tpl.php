<?php
/**
 * Appointment decline email — Norwegian Bokmål.
 * Sent when admin clicks "Decline" on a pending appointment request.
 */
defined('BASEPATH') OR exit('No direct script access allowed');
$caller_name      = isset($caller_name)      ? $caller_name      : '';
$business_name    = isset($business_name)    ? $business_name    : '';
$first_name       = trim(strtok((string) $caller_name, ' '));
$subject          = "Angående avtaleforespørselen din — " . $business_name;
?>
<p>Hei <?= htmlspecialchars($first_name ?: $caller_name) ?>,</p>
<p>Takk for at du tok kontakt med <strong><?= htmlspecialchars($business_name) ?></strong>.</p>
<p>Dessverre er vi ikke i stand til å tilby avtaletiden du ønsket. Vi beklager ulempen dette måtte medføre.</p>
<p>Hvis du vil se på andre alternativer eller har spørsmål, er det bare å svare på denne e-posten eller kontakte oss direkte.</p>
<p>Med vennlig hilsen,<br><?= htmlspecialchars($business_name) ?>-teamet</p>
