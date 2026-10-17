<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Card footer: the method/visibility note and data age on the left, "Prepared
 * by <agency>" on the right. Every card ends with this, so rules 5 and 6 (say
 * what we couldn't see; always show data age) cannot be dropped from one card.
 *
 * Vars: $card, $foot_note (string — the card-specific sentence(s), plain text;
 *       data age and sample-data flag are appended here, not by the caller)
 */
$brand = isset($card['brand']) ? $card['brand'] : ['name' => '', 'logo' => '', 'show_logo' => true];
$agency = trim((string) $brand['name']) !== '' ? $brand['name'] : 'Your agency';
?>
<footer class="occ-foot">
  <p><?= html_escape(trim($foot_note)) ?>
     <?= html_escape($card['checked']['phrase']) ?><?= $card['business']['platform'] !== '' && empty($foot_skip_platform) ? ' via ' . html_escape($card['business']['platform']) : '' ?>.
     <?php if (!empty($card['is_demo']) || $card['source'] === 'derived'): ?><span class="occ-sample"><?= !empty($card['is_demo']) ? 'Sample data' : 'Illustrative grid' ?></span><?php endif; ?>
  </p>
  <div class="occ-prepared">
    <?= !empty($foot_prepared_for) ? 'Prepared for ' . html_escape($card['business']['name']) . ' by' : 'Prepared by' ?>
    <?php if (!empty($brand['show_logo']) && !empty($brand['logo'])): ?>
      <img src="<?= html_escape($brand['logo']) ?>" alt="<?= html_escape($agency) ?>">
    <?php else: ?>
      <b><?= html_escape($agency) ?></b>
    <?php endif; ?>
  </div>
</footer>
