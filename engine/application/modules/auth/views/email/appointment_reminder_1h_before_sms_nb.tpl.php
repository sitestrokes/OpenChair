<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$caller_name   = isset($caller_name)   ? $caller_name   : '';
$business_name = isset($business_name) ? $business_name : '';
$first_name    = trim(strtok((string) $caller_name, ' '));
?>
Hei <?= $first_name ?: 'der' ?>, avtalen din hos <?= $business_name ?> er om 1 time. Vi sees snart!