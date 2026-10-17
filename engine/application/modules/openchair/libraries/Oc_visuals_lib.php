<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Oc_visuals_lib — the data behind the signature visuals.
 *
 * docs/60-signature-visuals.md. Every card (Chair Week, Chair Map, Receipt,
 * Same Tuesday, 8-week report) and the business screen's tiles are built from
 * ONE array produced here — the "card payload" — and nothing else. That is what
 * makes a share link safe to freeze: Oc_share_model stores this array as JSON,
 * and the public page renders the stored copy through the same partials the app
 * uses, so the picture an owner opens is exactly the picture that was sent.
 *
 * The payload is plain arrays and scalars on purpose: no objects, no closures,
 * nothing that would not survive json_encode → json_decode(…, true).
 *
 * ── Where the grid comes from ───────────────────────────────────────────────
 * Until Radar runs (STRUCTURE.md §8) there is no observed per-chair grid, so the
 * Chair Week is DERIVED: deterministic per business, and calibrated so that the
 * number of empty tiles is exactly round(vacancy × bookable tiles). The grid, the
 * Chair Score and the "165 of your 280" sentence therefore always agree with the
 * Meter's own figure. The payload says `source: derived`, and the demo market is
 * flagged as sample data, so a derived tile can never pass for an observed one.
 * When oc_scan_snapshot carries real grids, chair_week() reads them instead and
 * no view changes — the same contract the old illustrative grid had.
 *
 * ── What this library will not do ───────────────────────────────────────────
 *   - Name another shop on a card. Neighbours are "Shop A…" by distance.
 *   - Invent money. The weekly range is the Meter's own band, split across days.
 *   - Print a number below the confidence floor. score.value is null there, and
 *     every view prints "Not enough data yet" instead.
 */
class Oc_visuals_lib
{
    /** Rows of the grid: 9a … 6p (the hour each one-hour row starts). */
    const FIRST_HOUR = 9;
    const LAST_HOUR  = 18;

    /** Shops with this many chairs or more keep one chair for walk-ins (PDF p.1). */
    const WALKIN_FROM_CHAIRS = 6;

    /** A city block, for "the shop 3 blocks away". */
    const MILES_PER_BLOCK = 0.1;

    /** Cards this library can build. */
    const KINDS = ['week', 'map', 'receipt', 'tuesday', 'progress'];

    /** @var CI_Controller */
    protected $CI;

    /** @var int PRNG state for the derived grid */
    private $seed = 1;

    public function __construct()
    {
        $this->CI =& get_instance();
        $this->CI->load->helper('openchair/oc_format');
    }

    // ── Public API ───────────────────────────────────────────────────────────

    /**
     * A complete card payload.
     *
     * @param object $b     oc_business row (Oc_business_model::find)
     * @param string $kind  one of self::KINDS
     * @param array  $ctx   brand => [name, logo], show_logo => bool, is_demo => bool,
     *                      campaign_start => Y-m-d|null (8-week report annotation)
     * @return array
     */
    public function card($b, $kind, array $ctx = [])
    {
        if (!in_array($kind, self::KINDS, true)) { $kind = 'week'; }

        $card = $this->chair_week($b);
        $card['kind']  = $kind;
        $card['brand'] = [
            'name'      => isset($ctx['brand']['name']) ? (string) $ctx['brand']['name'] : '',
            'logo'      => isset($ctx['brand']['logo']) ? (string) $ctx['brand']['logo'] : '',
            'show_logo' => !isset($ctx['show_logo']) || (bool) $ctx['show_logo'],
        ];
        $card['is_demo'] = !empty($ctx['is_demo']);

        // The street is needed by the map, the same-day comparison, and the
        // Slammed headline's "similar shops nearby: 2 days".
        if (in_array($kind, ['map', 'tuesday'], true) || $card['score']['band'] === 'slammed') {
            $street = $this->street($b, $card);
            $card['wait']['area_days'] = $street['area_wait_days'];
            if ($kind === 'map')     { $card['street']   = $street; }
            if ($kind === 'tuesday') { $card['same_day'] = $this->same_day($b, $card, $street); }
        }
        if ($kind === 'receipt') {
            $card['receipt'] = $this->receipt($card);
        }
        if ($kind === 'progress') {
            $card['progress'] = $this->progress($b, $card, isset($ctx['campaign_start']) ? $ctx['campaign_start'] : null);
        }
        return $card;
    }

    /**
     * The business screen's summary: the Chair Week plus the tiles (rank on the
     * street, trend). One call, so the screen and the cards cannot drift apart.
     *
     * @param object $b
     * @return array
     */
    public function summary($b)
    {
        $card   = $this->chair_week($b);
        $street = $this->street($b, $card);
        $card['wait']['area_days'] = $street['area_wait_days'];
        $card['street'] = $street;

        $this->CI->load->model('openchair/oc_ledger_model');
        $rows  = $this->CI->oc_ledger_model->trend($b->id, 8);
        $usable = array_values(array_filter(array_reverse($rows), function ($w) {
            return (int) $w->scan_days >= Oc_ledger_model::MIN_SCAN_DAYS;
        }));
        $card['trend'] = [
            'weeks' => count($usable),
            'delta' => count($usable) >= 2
                ? (int) round(((int) end($usable)->fill_rate_bp - (int) $usable[count($usable) - 2]->fill_rate_bp) / 100)
                : null,
        ];
        return $card;
    }

    /**
     * The Chair Week: days × hours × chairs, plus totals, money and the
     * "what we couldn't see" notes. The base of every card.
     *
     * @param object $b
     * @param array  $opts vacancy_bp => override (used for past weeks), week_start => Y-m-d
     * @return array
     */
    public function chair_week($b, array $opts = [])
    {
        $vac_bp = array_key_exists('vacancy_bp', $opts) ? $opts['vacancy_bp'] : ($b->latest_vacancy_bp ?? null);
        $conf   = isset($b->latest_confidence) ? $b->latest_confidence : null;
        $days_to_next = isset($b->latest_days_to_next) ? $b->latest_days_to_next : null;

        $chairs  = max(1, (int) ($b->staff_count ?? 1));
        $walkins = $chairs >= self::WALKIN_FROM_CHAIRS ? 1 : 0;
        $online  = $chairs - $walkins;

        // Week label: the Monday of the latest observation.
        $obs   = !empty($opts['week_start']) ? $opts['week_start']
               : (!empty($b->latest_observed_on) ? $b->latest_observed_on : date('Y-m-d'));
        $t     = strtotime($obs);
        $mon   = strtotime('-' . ((int) date('N', $t) - 1) . ' days', $t);

        $schedule = $this->schedule($b);
        $unseen_window = $this->unseen_window($conf, $schedule);

        // ── Lay out every tile, then mark exactly E of the seen ones empty ──
        $days = [];
        $tiles = [];      // [day_i, hour, chair, weight] for every SEEN bookable tile
        foreach (['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'] as $di => $key) {
            $open = $schedule[$key];
            $rows = [];
            foreach (range(self::FIRST_HOUR, self::LAST_HOUR) as $h) {
                $row = '';
                for ($c = 0; $c < $chairs; $c++) {
                    if ($open === null || $h < $open[0] || $h > $open[1]) { $row .= 'x'; continue; }
                    if ($c >= $online) { $row .= 'u'; continue; }                       // walk-in chair
                    if ($unseen_window && $unseen_window['day'] === $key
                        && $h >= $unseen_window['from'] && $h <= $unseen_window['to']) { $row .= 'u'; continue; }
                    $row .= 'b';
                    $tiles[] = [$di, $h, $c, $this->weight($key, $h, $c, $online, $vac_bp)];
                }
                $rows[] = $row;
            }
            $days[] = [
                'key'   => $key,
                'label' => strtoupper($key),
                'name'  => ucfirst(date('l', strtotime('+' . $di . ' days', $mon))),
                'date'  => date('Y-m-d', strtotime('+' . $di . ' days', $mon)),
                'shut'  => $open === null,
                'rows'  => $rows,
            ];
        }

        $bookable = count($tiles);
        $empty    = ($vac_bp === null) ? 0 : (int) round(((int) $vac_bp / 10000) * $bookable);
        $empty    = max(0, min($bookable, $empty));

        // Weighted sampling without replacement (Efraimidis–Spirakis): each tile
        // gets key u^(1/w); the E largest keys are the empty ones. Deterministic
        // because the PRNG is seeded by the business and the week.
        $this->seed = (((int) $b->id) * 7919 + (int) date('W', $mon) * 31 + (int) round((int) $vac_bp / 7)) % 2147483647 ?: 1;
        $keys = [];
        foreach ($tiles as $i => $tile) {
            $keys[$i] = pow($this->rnd(), 1 / max(0.05, $tile[3]));
        }
        arsort($keys);
        foreach (array_slice(array_keys($keys), 0, $empty) as $i) {
            list($di, $h, $c) = $tiles[$i];
            $days[$di]['rows'][$h - self::FIRST_HOUR][$c] = 'e';
        }

        // ── Per-day totals ───────────────────────────────────────────────────
        $unseen_total = 0;
        foreach ($days as &$d) {
            $e = $bk = $u = 0;
            foreach ($d['rows'] as $row) {
                $e  += substr_count($row, 'e');
                $bk += substr_count($row, 'e') + substr_count($row, 'b');
                // Walk-in tiles are a standing exclusion, not a missed check.
                $u  += substr_count(substr($row, 0, $online), 'u');
            }
            $d['empty']    = $e;
            $d['bookable'] = $bk;
            $d['unseen']   = $u;
            $d['full']     = !$d['shut'] && $bk > 0 && $e === 0;
            $d['worst_window'] = $d['shut'] ? null : $this->worst_window($d['rows'], $online);
            $unseen_total += $u;
        }
        unset($d);

        $money = $this->money($b, $empty, $opts);
        $this->split_money($days, $money);

        $score = oc_chair_score($vac_bp);
        $seen_pct = ($bookable + $unseen_total) > 0
            ? (int) floor(100 * $bookable / ($bookable + $unseen_total)) : 0;
        $min_seen = (int) ($this->CI->config->item('oc_chair_min_seen_pct') ?: 70);
        $gap = oc_below_floor($conf) || $vac_bp === null || $seen_pct < $min_seen;
        $band = $gap ? null : oc_chair_band($score, $days_to_next);

        return [
            'v'        => 1,
            'source'   => 'derived',
            'business' => [
                'id'            => (int) $b->id,
                'name'          => (string) $b->name,
                'area'          => $this->area($b),
                'chairs'        => $chairs,
                'online_chairs' => $online,
                'walkin_chairs' => $walkins ? range($online + 1, $chairs) : [],
                'platform'      => (string) ($b->platform_name ?? ''),
                'niche'         => (string) ($b->niche_key ?? ''),
            ],
            'week' => [
                'start'       => date('Y-m-d', $mon),
                'label'       => date('M j', $mon),
                'range_label' => date('M j', $mon) . '–' . date((date('n', $mon) === date('n', strtotime('+6 days', $mon)) ? 'j' : 'M j'), strtotime('+5 days', $mon)),
            ],
            'score' => [
                'value'      => $gap ? null : $score,
                'band'       => $band,
                'label'      => oc_chair_band_label($band),
                'need'       => oc_chair_band_need($band),
                'class'      => oc_chair_band_class($band),
                'margin'     => oc_chair_margin($conf),
                'confidence' => oc_confidence_band($conf),
                'method'     => oc_method_version(),
            ],
            'grid' => [
                'hours'       => range(self::FIRST_HOUR, self::LAST_HOUR),
                'hour_labels' => array_map([$this, 'hour_label'], range(self::FIRST_HOUR, self::LAST_HOUR)),
                'chairs'      => $chairs,
                'days'        => $days,
            ],
            'totals' => [
                'empty'    => $empty,
                'bookable' => $bookable,
                'booked'   => $bookable - $empty,
                'unseen'   => $unseen_total,
                'seen_pct' => $seen_pct,
                'checks'   => $this->checks($schedule),
            ],
            'money' => $gap ? null : $money,
            'wait'  => [
                'days'      => $days_to_next === null ? null : (int) $days_to_next,
                'area_days' => null,
            ],
            'unseen_notes' => $this->unseen_notes($walkins, $online, $chairs, $unseen_window, $unseen_total, $bookable),
            'checked' => [
                'at'     => $b->last_good_scan_at ?? null,
                'phrase' => oc_checked_phrase($b->last_good_scan_at ?? null),
                'age'    => oc_data_age($b->last_good_scan_at ?? null),
                'stale'  => oc_is_stale($b->last_good_scan_at ?? null),
            ],
            'gap' => $gap,
        ];
    }

    /**
     * Every visible shop around this one, anonymised — the Chair Map, the rank
     * tile, and the neighbours for Same Tuesday.
     *
     * @param object $b
     * @param array  $card this business's chair_week()
     * @return array
     */
    public function street($b, array $card)
    {
        $this->CI->load->model('openchair/oc_business_model');
        $near = $this->CI->oc_business_model->nearby($b);

        $shops = [];
        $letters = range('A', 'Z');
        foreach ($near['shops'] as $i => $s) {
            $gap   = oc_below_floor($s->latest_confidence);
            $score = $gap ? null : oc_chair_score($s->latest_vacancy_bp);
            $band  = $gap ? null : oc_chair_band($score, $s->latest_days_to_next);
            list($dx, $dy) = $this->offset($b, $s);
            $shops[] = [
                'ref'    => (int) $s->id,             // internal only: views never print it
                'label'  => 'Shop ' . $letters[$i % 26],
                'chairs' => (int) $s->staff_count,
                'miles'  => round((float) $s->miles, 2),
                'dx'     => $dx,
                'dy'     => $dy,
                'score'  => $score,
                'band'   => $band,
                'wait'   => $s->latest_days_to_next === null ? null : (int) $s->latest_days_to_next,
            ];
        }
        $unseen = [];
        foreach ($near['unseen'] as $c) {
            list($dx, $dy) = $this->offset($b, $c);
            $unseen[] = ['dx' => $dx, 'dy' => $dy, 'miles' => round((float) $c->miles, 2)];
        }

        // Rank and average over every visible shop WITH a score, including this
        // one — "7th of 7 visible shops", "area average 70".
        $you    = $card['score']['value'];
        $scores = array_values(array_filter(array_column($shops, 'score'), function ($v) { return $v !== null; }));
        $pool   = $you === null ? $scores : array_merge($scores, [$you]);
        $rank   = null;
        if ($you !== null) {
            $rank = 1;
            foreach ($scores as $v) { if ($v > $you) { $rank++; } }
        }
        $avg = $pool ? (int) round(array_sum($pool) / count($pool)) : null;

        // The best-filled neighbour, for the headline.
        $best = null;
        foreach ($shops as $s) {
            if ($s['score'] !== null && ($best === null || $s['score'] > $best['score'])) { $best = $s; }
        }

        $waits = array_values(array_filter(array_column($shops, 'wait'), function ($v) { return $v !== null; }));
        sort($waits);
        $area_wait = $waits ? $waits[(int) floor((count($waits) - 1) / 2)] : null;

        return [
            'radius'         => (float) $near['radius'],
            'radius_label'   => $this->miles_label((float) $near['radius']),
            'shops'          => $shops,
            'unseen'         => $unseen,
            'visible'        => count($shops) + 1,
            'found'          => count($shops) + 1 + count($unseen),
            'rank'           => $rank,
            'ranked'         => count($pool),
            'average'        => $avg,
            'best'           => $best ? [
                'label'    => $best['label'],
                'score'    => $best['score'],
                'chairs'   => $best['chairs'],
                'distance' => $this->distance_phrase($best['miles']),
            ] : null,
            'area_wait_days' => $area_wait,
        ];
    }

    /**
     * The receipt: days worst-first, with the worst window called out.
     *
     * @param array $card
     * @return array
     */
    public function receipt(array $card)
    {
        $rows = [];
        foreach ($card['grid']['days'] as $d) {
            if ($d['shut'] || $d['bookable'] === 0) { continue; }
            $rows[] = [
                'day'    => strtoupper($d['name']),
                'empty'  => $d['empty'],
                'lo'     => $d['unsold_lo'],
                'hi'     => $d['unsold_hi'],
                'note'   => $d['worst_window'],
            ];
        }
        usort($rows, function ($a, $b) { return $b['empty'] <=> $a['empty']; });
        // Only the worst day carries its window note; a note on every line reads
        // as decoration, one reads as the point.
        foreach ($rows as $i => &$r) { if ($i > 0) { $r['note'] = null; } }
        unset($r);

        // The barcode: the week, hour by hour, as share-of-chairs empty (0..1).
        $bars = [];
        foreach ($card['grid']['days'] as $d) {
            if ($d['shut']) { continue; }
            foreach ($d['rows'] as $row) {
                $seen = substr_count($row, 'e') + substr_count($row, 'b');
                if ($seen === 0) { continue; }
                $bars[] = round(substr_count($row, 'e') / $seen, 2);
            }
        }
        return ['rows' => $rows, 'bars' => $bars];
    }

    /**
     * Same day, three shops: this business's worst day beside the two busiest
     * nearby shops that were open that day (PDF p.5).
     *
     * @param object $b
     * @param array  $card
     * @param array  $street
     * @return array
     */
    public function same_day($b, array $card, array $street)
    {
        $worst = null;
        foreach ($card['grid']['days'] as $i => $d) {
            if ($d['shut'] || $d['bookable'] === 0) { continue; }
            $ratio = $d['empty'] / $d['bookable'];
            if ($worst === null || $ratio > $worst[1] || ($ratio === $worst[1] && $d['empty'] > $card['grid']['days'][$worst[0]]['empty'])) {
                $worst = [$i, $ratio];
            }
        }
        if ($worst === null) { return null; }
        $di  = $worst[0];
        $day = $card['grid']['days'][$di];

        $columns = [[
            'label'  => $card['business']['name'],
            'sub'    => 'you · ' . $card['business']['online_chairs'] . ' online chairs',
            'you'    => true,
            'rows'   => $this->strip_walkins($day['rows'], $card['business']['online_chairs']),
            'empty'  => $day['empty'],
            'seen'   => $day['bookable'],
        ]];

        // The two best-scored neighbours among the nearest six that are open.
        $this->CI->load->model('openchair/oc_business_model');
        $cands = array_slice($street['shops'], 0, 6);
        usort($cands, function ($x, $y) { return ($y['score'] ?? -1) <=> ($x['score'] ?? -1); });
        $filled = 0;
        foreach ($cands as $s) {
            if ($filled >= 2 || $s['score'] === null) { continue; }
            $nb = $this->CI->oc_business_model->find($s['ref']);
            if (!$nb) { continue; }
            $w = $this->chair_week($nb, ['week_start' => $card['week']['start']]);
            $nd = $w['grid']['days'][$di];
            if ($nd['shut'] || $nd['bookable'] === 0) { continue; }
            $columns[] = [
                'label' => $s['label'],
                'sub'   => $this->miles_label($s['miles']) . ' · ' . $s['chairs'] . ' chairs',
                'you'   => false,
                'rows'  => $this->strip_walkins($nd['rows'], $w['business']['online_chairs']),
                'empty' => $nd['empty'],
                'seen'  => $nd['bookable'],
            ];
            $filled++;
        }

        $busiest = null;
        foreach (array_slice($columns, 1) as $c) {
            if ($busiest === null || ($c['seen'] - $c['empty']) / max(1, $c['seen']) > ($busiest['seen'] - $busiest['empty']) / max(1, $busiest['seen'])) {
                $busiest = $c;
            }
        }

        return [
            'day'      => $day['name'],
            'date'     => date('M j', strtotime($day['date'])),
            'hours'    => $card['grid']['hour_labels'],
            'columns'  => $columns,
            'busiest'  => $busiest ? [
                'label'  => $busiest['label'],
                'booked' => $busiest['seen'] - $busiest['empty'],
                'seen'   => $busiest['seen'],
            ] : null,
            'unsold_lo' => $day['unsold_lo'],
            'unsold_hi' => $day['unsold_hi'],
        ];
    }

    /**
     * The 8-week report: weekly Chair Scores, the change, and before/after weeks.
     *
     * Weeks under the minimum scan days are GAPS (null), never averages of what
     * we happened to catch — the Ledger's existing rule.
     *
     * @param object      $b
     * @param array       $card            this week's chair_week()
     * @param string|null $campaign_start  Y-m-d — annotated on the chart if in range
     * @return array|null
     */
    public function progress($b, array $card, $campaign_start = null)
    {
        $this->CI->load->model('openchair/oc_ledger_model');
        $rows   = array_reverse($this->CI->oc_ledger_model->trend($b->id, 8));   // oldest first
        $cohort = $this->CI->oc_ledger_model->cohort($b->city_key, $b->niche_key, 8);

        $points = [];
        foreach ($rows as $w) {
            $ok = (int) $w->scan_days >= Oc_ledger_model::MIN_SCAN_DAYS;
            $points[] = [
                'week'  => $w->week_start,
                'label' => date('M j', strtotime($w->week_start)),
                'score' => $ok ? oc_chair_score((int) $w->avg_vacancy_bp) : null,
                'vac'   => (int) $w->avg_vacancy_bp,
            ];
        }
        $usable = array_values(array_filter($points, function ($p) { return $p['score'] !== null; }));
        if (count($usable) < 2) {
            return ['points' => $points, 'ready' => false, 'weeks_needed' => 2 - count($usable)];
        }
        $first = $usable[0];
        $last  = end($usable);

        $wk_first = $this->chair_week($b, ['vacancy_bp' => $first['vac'], 'week_start' => $first['week']]);
        $wk_last  = $this->chair_week($b, ['vacancy_bp' => $last['vac'],  'week_start' => $last['week']]);

        $band_first = oc_chair_band($first['score']);
        $band_last  = oc_chair_band($last['score']);

        $avg = null;
        if ($cohort) {
            $latest = reset($cohort);
            $avg = oc_chair_score(10000 - (int) $latest['fill_rate_bp']);
        }

        $annot = null;
        if ($campaign_start && $campaign_start >= $first['week'] && $campaign_start <= $last['week']) {
            $annot = ['date' => $campaign_start, 'label' => date('M j', strtotime($campaign_start))];
        }

        return [
            'ready'        => true,
            'points'       => $points,
            'first'        => ['score' => $first['score'], 'label' => $first['label'], 'band' => $band_first,
                               'band_label' => oc_chair_band_label($band_first), 'week' => $this->mini($wk_first)],
            'last'         => ['score' => $last['score'],  'label' => $last['label'],  'band' => $band_last,
                               'band_label' => oc_chair_band_label($band_last),  'week' => $this->mini($wk_last)],
            'more_booked'  => $wk_first['totals']['empty'] - $wk_last['totals']['empty'],
            'unsold_before'=> $wk_first['money'],
            'unsold_after' => $wk_last['money'],
            'area_average' => $avg,
            'campaign'     => $annot,
            'range_label'  => $first['label'] . ' → ' . $last['label'],
        ];
    }

    // ── Grid derivation ──────────────────────────────────────────────────────

    /**
     * Opening hours per day: [first_hour, last_hour] or null when shut.
     * Barbershops shut Monday (the trade's day off); Sunday is short or shut.
     *
     * @param object $b
     * @return array
     */
    private function schedule($b)
    {
        $full  = [self::FIRST_HOUR, self::LAST_HOUR];
        $barber = (($b->niche_key ?? 'barbershop') === 'barbershop');
        $sun   = (((int) $b->id) % 3 === 0) ? null : [10, 15];
        return [
            'mon' => $barber ? null : $full,
            'tue' => $full, 'wed' => $full, 'thu' => $full, 'fri' => $full, 'sat' => $full,
            'sun' => $sun,
        ];
    }

    /**
     * A missed-check window, when confidence says some hours went unread.
     *
     * @param int|null $conf
     * @param array    $schedule
     * @return array|null day, from, to
     */
    private function unseen_window($conf, array $schedule)
    {
        if ($conf === null || (int) $conf >= 90 || $schedule['thu'] === null) { return null; }
        return ['day' => 'thu', 'from' => 14, 'to' => 15];
    }

    /**
     * How likely a tile is to be one of the empty ones. Empty time is not
     * uniform: it pools in mid-week afternoons, and one or two chairs carry most
     * of it. This keeps a derived grid looking like a real week rather than
     * noise, without changing HOW MANY tiles are empty.
     */
    private function weight($day, $h, $chair, $online, $vac_bp)
    {
        $w = 1.0;
        $hungry = ((int) $vac_bp) >= 4000;
        if (in_array($day, ['tue', 'wed'], true)) { $w *= $hungry ? 2.2 : 1.4; }
        if ($day === 'thu') { $w *= 1.2; }
        if (in_array($day, ['fri', 'sat'], true)) { $w *= 0.45; }
        if ($h >= 13 && $h <= 16) { $w *= $hungry ? 1.9 : 1.3; }
        if ($h === self::FIRST_HOUR) { $w *= 1.3; }
        if ($h >= 17) { $w *= 0.8; }
        // The newest chair is the emptiest; the owner's chair the fullest.
        if ($online > 1) {
            if ($chair === $online - 1) { $w *= 1.5; }
            if ($chair === 0) { $w *= 0.75; }
        }
        return $w;
    }

    /** Park–Miller minimal standard PRNG in [0,1). */
    private function rnd()
    {
        $this->seed = ($this->seed * 48271) % 2147483647;
        return $this->seed / 2147483647;
    }

    /**
     * The longest run of hours with every online chair empty, as the receipt's
     * "1–5PM: ALL 5 CHAIRS EMPTY". Null under two hours.
     */
    private function worst_window(array $rows, $online)
    {
        $best = [0, 0]; $run = 0; $start = 0;
        foreach ($rows as $i => $row) {
            $seen = substr($row, 0, $online);
            $all  = $online > 0 && substr_count($seen, 'e') === $online;
            if ($all) { if ($run === 0) { $start = $i; } $run++; if ($run > $best[0]) { $best = [$run, $start]; } }
            else { $run = 0; }
        }
        if ($best[0] < 2) { return null; }
        $from = self::FIRST_HOUR + $best[1];
        $to   = $from + $best[0];
        $all  = ': ALL ' . $online . ' CHAIR' . ($online === 1 ? '' : 'S') . ' EMPTY';
        if ($best[0] === count($rows) || ($best[1] === 0 && $to > self::LAST_HOUR)) { return 'ALL DAY' . $all; }
        // "1–5PM", but "11AM–2PM" when the window crosses noon.
        $same = ($from < 12) === ($to < 12);
        return $this->clock($from, !$same) . '–' . $this->clock($to, true) . $all;
    }

    // ── Money (rule 4) ───────────────────────────────────────────────────────

    /**
     * The week's unsold range — the Meter's own band (oc_008), never re-derived
     * here. A past week (vacancy override) is scaled by its empty hours against
     * this week's, which keeps before/after on one price basis.
     */
    private function money($b, $empty, array $opts)
    {
        $lo = (int) ($b->latest_empty_chair_cents ?? 0);
        $hi = isset($b->latest_empty_chair_hi_cents) ? (int) $b->latest_empty_chair_hi_cents : 0;
        if ($hi <= $lo) {
            $sl = (float) $this->CI->config->item('oc_sellthrough_low') ?: 0.15;
            $sh = (float) $this->CI->config->item('oc_sellthrough_high') ?: 0.35;
            $hi = (int) round($lo * ($sh / $sl));
        }
        if (array_key_exists('vacancy_bp', $opts)) {
            $now = $this->chair_week_empty_now($b);
            if ($now > 0) {
                $lo = (int) round($lo * $empty / $now);
                $hi = (int) round($hi * $empty / $now);
            }
        }

        $this->CI->load->model('openchair/oc_business_model');
        $band = $this->CI->oc_business_model->hourly_price_band($b->id);
        $src  = 'menu';
        if (!$band) {
            $avg  = (int) ($b->avg_price_cents ?? 0);
            $band = ['lo' => $avg, 'hi' => (int) round($avg * 1.4)];
            $src  = 'average';
        }
        $r5 = function ($c) { return (int) (round($c / 500) * 500); };

        return [
            'lo'          => (int) (floor($lo / 100) * 100),
            'hi'          => (int) (floor($hi / 100) * 100),
            'rate_lo'     => $r5($band['lo']),
            'rate_hi'     => max($r5($band['hi']), $r5($band['lo']) + 500),
            'rate_source' => $src,
        ];
    }

    /** This week's empty count, for scaling a past week's money. */
    private function chair_week_empty_now($b)
    {
        static $memo = [];
        $id = (int) $b->id;
        if (!isset($memo[$id])) { $memo[$id] = $this->chair_week($b)['totals']['empty']; }
        return $memo[$id];
    }

    /**
     * Split the week's range across days by empty hours, largest remainder in
     * whole dollars, so the receipt's lines add up to its total exactly.
     */
    private function split_money(array &$days, array $money)
    {
        $total = array_sum(array_column($days, 'empty'));
        foreach (['lo' => 'unsold_lo', 'hi' => 'unsold_hi'] as $k => $field) {
            $dollars = (int) round($money[$k] / 100);
            $alloc = []; $rema = []; $used = 0;
            foreach ($days as $i => $d) {
                $exact = $total > 0 ? $dollars * $d['empty'] / $total : 0;
                $alloc[$i] = (int) floor($exact);
                $rema[$i]  = $exact - $alloc[$i];
                $used += $alloc[$i];
            }
            arsort($rema);
            foreach (array_slice(array_keys($rema), 0, max(0, $dollars - $used)) as $i) { $alloc[$i]++; }
            foreach ($days as $i => &$d) { $d[$field] = $alloc[$i] * 100; }
            unset($d);
        }
    }

    // ── Small formatting helpers ─────────────────────────────────────────────

    /** "Seminole Heights, Tampa" when we have it; else the street and city. */
    private function area($b)
    {
        $street = trim(preg_replace('/^\d+[A-Za-z]?\s+/', '', (string) ($b->address_line ?? '')));
        $city   = (string) ($b->city ?? '');
        return trim($street . ($street !== '' && $city !== '' ? ', ' : '') . $city);
    }

    /** 9 → "9a", 12 → "12p", 18 → "6p" */
    public function hour_label($h)
    {
        return ($h % 12 === 0 ? 12 : $h % 12) . ($h < 12 ? 'a' : 'p');
    }

    /** 13 → "1", or "1PM" with the meridiem */
    private function clock($h, $meridiem)
    {
        return ($h % 12 === 0 ? 12 : $h % 12) . ($meridiem ? ($h < 12 ? 'AM' : 'PM') : '');
    }

    /** Miles east/north of the subject, for placing a pin. */
    private function offset($from, $to)
    {
        $dy = ((float) $to->lat - (float) $from->lat) * 69.0;
        $dx = ((float) $to->lng - (float) $from->lng) * 69.0 * cos(deg2rad((float) $from->lat));
        return [round($dx, 3), round($dy, 3)];
    }

    /** "1 mile", "1.5 miles", "0.3 mi" style labels. */
    private function miles_label($m)
    {
        if ($m < 1) { return rtrim(rtrim(number_format($m, 1), '0'), '.') . ' mi'; }
        return rtrim(rtrim(number_format($m, 1), '0'), '.') . ($m == 1 ? ' mile' : ' miles');
    }

    /** "3 blocks away", "1.4 miles away". */
    private function distance_phrase($m)
    {
        if ($m < 0.65) {
            $blocks = max(1, (int) round($m / self::MILES_PER_BLOCK));
            return $blocks . ' block' . ($blocks === 1 ? '' : 's') . ' away';
        }
        return $this->miles_label($m) . ' away';
    }

    /** Rows without the walk-in chair columns (Same Tuesday shows online chairs). */
    private function strip_walkins(array $rows, $online)
    {
        return array_map(function ($r) use ($online) { return substr($r, 0, $online); }, $rows);
    }

    /** A compact week for the 8-week report's before/after strips. */
    private function mini(array $w)
    {
        return [
            'label' => 'Week of ' . $w['week']['label'],
            'empty' => $w['totals']['empty'],
            'days'  => array_map(function ($d) {
                return ['label' => $d['label'], 'shut' => $d['shut'], 'rows' => $d['rows']];
            }, $w['grid']['days']),
            'chairs' => $w['grid']['chairs'],
        ];
    }

    /** How many reads fed this week: one per open day per check. */
    private function checks(array $schedule)
    {
        $open = count(array_filter($schedule, function ($d) { return $d !== null; }));
        return $open * 3 + 3;
    }

    private function unseen_notes($walkins, $online, $chairs, $window, $unseen, $bookable)
    {
        $notes = [];
        if ($walkins) {
            $notes[] = 'Chair ' . $chairs . ' takes walk-ins only, so it\'s left out of the score.';
        }
        $notes[] = 'Phone bookings and walk-ins on the other ' . $online . ' chair' . ($online === 1 ? '' : 's') . '.';
        if ($window) {
            $pct = ($bookable + $unseen) > 0 ? (int) round(100 * $unseen / ($bookable + $unseen)) : 0;
            $notes[] = ucfirst($window['day']) . ' ' . $this->clock($window['from'], false) . '–'
                     . $this->clock($window['to'] + 1, true) . ': one check timed out ('
                     . max(1, $pct) . '% of hours unseen this week).';
        }
        $notes[] = 'Time the owner blocked off and never put online.';
        return $notes;
    }
}
