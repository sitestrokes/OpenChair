<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$caller_name   = isset($caller_name)   ? $caller_name   : '';
$business_name = isset($business_name) ? $business_name : '';
$first_name    = trim(strtok((string) $caller_name, ' '));
?>
Hola <?= $first_name ?: '' ?>, tu cita con <?= $business_name ?> es en 1 hora. ¡Nos vemos pronto!
