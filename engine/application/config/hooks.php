<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
| -------------------------------------------------------------------------
| Hooks
| -------------------------------------------------------------------------
| This file lets you define "hooks" to extend CI without hacking the core
| files.  Please see the user guide for info:
|
|	https://codeigniter.com/user_guide/general/hooks.html
|
*/

// Security headers on every response (docs/70-radar-build.md §6).
$hook['pre_system'][] = [
    'class'    => 'Oc_security_headers',
    'function' => 'send',
    'filename' => 'Oc_security_headers.php',
    'filepath' => 'hooks',
];
