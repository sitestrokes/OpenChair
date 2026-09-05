<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$caller_name   = isset($caller_name)   ? $caller_name   : '';
$business_name = isset($business_name) ? $business_name : '';
$first_name    = trim(strtok((string) $caller_name, ' '));
?>
Hi <?= $first_name ?: 'there' ?>, your appointment with <?= $business_name ?> is in 1 hour. See you soon!
