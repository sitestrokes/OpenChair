<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$caller_name   = isset($caller_name)   ? $caller_name   : '';
$business_name = isset($business_name) ? $business_name : '';
$first_name    = trim(strtok((string) $caller_name, ' '));
$subject       = "Thank you — " . $business_name;
?>
<p>Hi <?= htmlspecialchars($first_name ?: 'there') ?>,</p>
<p>Thank you for your appointment yesterday. We hope you had a great experience.</p>
<p>If you have any questions or feedback, just reply to this email — we'd love to hear from you.</p>
<p>Looking forward to staying in touch,<br><?= htmlspecialchars($business_name) ?></p>
