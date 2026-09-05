<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$caller_name   = isset($caller_name)   ? $caller_name   : '';
$business_name = isset($business_name) ? $business_name : '';
$first_name    = trim(strtok((string) $caller_name, ' '));
$subject       = "Merci — " . $business_name;
?>
<p>Bonjour <?= htmlspecialchars($first_name ?: '') ?>,</p>
<p>merci pour votre rendez-vous d'hier. Nous espérons que vous avez passé un excellent moment.</p>
<p>Si vous avez des questions ou des commentaires, répondez simplement à ce courriel — nous serions ravis de vous lire.</p>
<p>À très bientôt,<br><?= htmlspecialchars($business_name) ?></p>
