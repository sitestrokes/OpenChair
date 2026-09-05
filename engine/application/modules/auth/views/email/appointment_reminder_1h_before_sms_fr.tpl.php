<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$caller_name   = isset($caller_name)   ? $caller_name   : '';
$business_name = isset($business_name) ? $business_name : '';
$first_name    = trim(strtok((string) $caller_name, ' '));
?>
Bonjour <?= $first_name ?: '' ?>, votre rendez-vous chez <?= $business_name ?> est dans 1 heure. À tout de suite !
