<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$caller_name      = isset($caller_name)      ? $caller_name      : '';
$business_name    = isset($business_name)    ? $business_name    : '';
$first_name       = trim(strtok((string) $caller_name, ' '));
$subject          = "Bezüglich Ihrer Terminanfrage — " . $business_name;
?>
<p>Hallo <?= htmlspecialchars($first_name ?: $caller_name) ?>,</p>
<p>vielen Dank, dass Sie <strong><?= htmlspecialchars($business_name) ?></strong> kontaktiert haben.</p>
<p>Leider können wir den von Ihnen angefragten Termin nicht bestätigen. Bitte entschuldigen Sie die Unannehmlichkeiten.</p>
<p>Wenn Sie andere Termine erkunden möchten oder Fragen haben, antworten Sie bitte auf diese E-Mail oder kontaktieren Sie uns direkt.</p>
<p>Mit freundlichen Grüßen,<br>Das Team von <?= htmlspecialchars($business_name) ?></p>