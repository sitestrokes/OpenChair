<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$caller_name   = isset($caller_name)   ? $caller_name   : '';
$business_name = isset($business_name) ? $business_name : '';
$first_name    = trim(strtok((string) $caller_name, ' '));
?>
Ciao <?= $first_name ?: '' ?>, il tuo appuntamento presso <?= $business_name ?> è tra 1 ora. A presto!
