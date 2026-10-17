<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * The Chair Week grid — one tile per chair per hour, days side by side.
 *
 * Shared by the Chair Week card, the business screen and the 8-week report's
 * before/after strips, so the same week always draws the same way. Redrawn from
 * our own data: never a capture of a booking platform's page (05-prd §4.2).
 *
 * Vars:
 *   $grid       card['grid'] (hours, hour_labels, chairs, days[])
 *   $grid_opts  optional:
 *     counts      'fraction' (45/50 under each day) | 'head' (TUE 45 in the header) | 'none'
 *     hours       bool  show the hour labels (default true)
 *     size        'lg' | 'md' | 'sm' (tile size; default 'lg')
 *     skip_shut   bool  drop shut days entirely (default false: they show as pale bars)
 *     day_label   'short' (M for a shut Monday, like the PDF) | 'full' (default 'short')
 *
 * Tile states: e empty · b booked · u can't see (walk-in chair / missed check) · x shut.
 * Every state has a non-colour encoding too (pattern or shape) — NFR-8.
 */
$o = isset($grid_opts) && is_array($grid_opts) ? $grid_opts : [];
$counts    = isset($o['counts']) ? $o['counts'] : 'fraction';
$show_h    = !isset($o['hours']) || $o['hours'];
$size      = isset($o['size']) ? $o['size'] : 'lg';
$skip_shut = !empty($o['skip_shut']);

$days = $grid['days'];
if ($skip_shut) { $days = array_values(array_filter($days, function ($d) { return !$d['shut']; })); }
$chairs = (int) $grid['chairs'];
$state_name = ['e' => 'empty', 'b' => 'booked', 'u' => "can't see", 'x' => 'shut'];

// Column template: a shut day is narrow, an open day is one track per chair.
$cols = [];
foreach ($days as $d) { $cols[] = $d['shut'] ? 'minmax(14px,.32fr)' : 'minmax(0,1fr)'; }
?>
<div class="ocg ocg--<?= html_escape($size) ?><?= $show_h ? '' : ' ocg--nohours' ?>"
     style="--ocg-cols:<?= html_escape(implode(' ', $cols)) ?>;--ocg-chairs:<?= $chairs ?>"
     role="img"
     aria-label="<?= html_escape('Chair by chair, hour by hour: '
        . implode('; ', array_map(function ($d) {
            return $d['shut'] ? $d['name'] . ' shut' : $d['name'] . ' ' . $d['empty'] . ' of ' . $d['bookable'] . ' chair-hours empty';
        }, $days))) ?>">

  <div class="ocg-head">
    <?php if ($show_h): ?><span class="ocg-corner"></span><?php endif; ?>
    <?php foreach ($days as $d): ?>
      <span class="ocg-dayname<?= $d['shut'] ? ' is-shut' : '' ?>">
        <?= html_escape($d['shut'] && (!isset($o['day_label']) || $o['day_label'] === 'short') ? substr($d['label'], 0, 1) : $d['label']) ?>
        <?php if ($counts === 'head' && !$d['shut']): ?> <b><?= (int) $d['empty'] ?></b><?php endif; ?>
      </span>
    <?php endforeach; ?>
  </div>

  <div class="ocg-body">
    <?php foreach ($grid['hour_labels'] as $hi => $hl): ?>
      <div class="ocg-row">
        <?php if ($show_h): ?><span class="ocg-hour"><?= html_escape($hl) ?></span><?php endif; ?>
        <?php foreach ($days as $d):
          $row = $d['rows'][$hi];
          $all_x = (strspn($row, 'x') === strlen($row)); ?>
          <span class="ocg-day<?= $d['shut'] ? ' is-shut' : '' ?>">
            <?php if ($all_x): ?>
              <i class="ocg-shut"></i>
            <?php else: for ($c = 0; $c < $chairs; $c++): $s = $row[$c]; ?>
              <i class="ocg-t ocg-<?= $s ?>" title="<?= html_escape($d['label'] . ' ' . $hl . ' · chair ' . ($c + 1) . ' · ' . $state_name[$s]) ?>"></i>
            <?php endfor; endif; ?>
          </span>
        <?php endforeach; ?>
      </div>
    <?php endforeach; ?>
  </div>

  <?php if ($counts === 'fraction'): ?>
  <div class="ocg-foot">
    <?php if ($show_h): ?><span class="ocg-corner">EMPTY</span><?php endif; ?>
    <?php foreach ($days as $d): ?>
      <span class="ocg-count">
        <?php if ($d['shut']): ?><em>shut</em>
        <?php elseif ($d['full']): ?><span class="ocg-full">FULL</span>
        <?php else: ?><b><?= (int) $d['empty'] ?></b><small>/<?= (int) $d['bookable'] ?></small><?php endif; ?>
      </span>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>
