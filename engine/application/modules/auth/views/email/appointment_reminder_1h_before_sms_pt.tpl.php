<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$caller_name   = isset($caller_name)   ? $caller_name   : '';
$business_name = isset($business_name) ? $business_name : '';
$first_name    = trim(strtok((string) $caller_name, ' '));
?>
Olá <?= $first_name ?: '' ?>, seu compromisso com <?= $business_name ?> é daqui a 1 hora. Até já!
