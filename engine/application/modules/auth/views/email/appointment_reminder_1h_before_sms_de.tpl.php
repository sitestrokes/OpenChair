<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$caller_name   = isset($caller_name)   ? $caller_name   : '';
$business_name = isset($business_name) ? $business_name : '';
$first_name    = trim(strtok((string) $caller_name, ' '));
?>
Hallo <?= $first_name ?: '' ?>, Ihr Termin bei <?= $business_name ?> ist in 1 Stunde. Bis gleich!
