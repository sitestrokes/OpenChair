<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Oc_business_model — the GLOBAL scan layer.
 *
 * ────────────────────────────────────────────────────────────────────────────
 * THIS MODEL MUST NEVER TAKE A user_id.
 * ────────────────────────────────────────────────────────────────────────────
 * A business is scanned ONCE and shared by every tenant watching it
 * (30-data-model.md §3, FR-3.1). If a user_id ever appears in a query here, the
 * shared-scan dedupe is broken and each tenant starts paying for their own crawl
 * of the same shop — which breaks the COGS model the whole offer rests on.
 *
 * Per-tenant state — watchlists, markets, pipeline stage — belongs to
 * Oc_prospect_model and Oc_market_model, which point AT this layer and never own
 * it. The split is the one thing this schema has to get right.
 */
class Oc_business_model extends CI_Model
{
    /**
     * Businesses in a market, ordered for the map and the list.
     *
     * Hits idx_biz_market_vacancy / _next / _bucket depending on the sort — the
     * indexes in oc_002 were designed against exactly these queries, so keep the
     * leading columns (city_key, niche_key, status) in place.
     *
     * @param string $city_key   'us-fl-tampa'
     * @param string $niche_key  'barbershop'
     * @param array  $opts       bucket, sort (vacancy|revenue|next|name), limit
     * @return array
     */
    public function market($city_key, $niche_key, array $opts = [])
    {
        $this->db->select('b.*, p.platform_key, p.name AS platform_name')
                 ->from('oc_business b')
                 ->join('oc_platform p', 'p.id = b.platform_id', 'left')
                 ->where('b.city_key', $city_key)
                 ->where('b.status', 'active')
                 ->where('b.suppressed_at IS NULL');   // an owner opt-out is honoured everywhere

        if ($niche_key !== '' && $niche_key !== null) {
            $this->db->where('b.niche_key', $niche_key);
        }
        if (!empty($opts['bucket'])) {
            // Chair Score v1.0 bands, not the stored latest_bucket, so the chip a
            // user clicks and the pill on every card agree (docs/60 §4.1).
            $want = array_map(function ($x) { return $x === 'steady' ? 'neutral' : $x; }, (array) $opts['bucket']);
            $want = array_values(array_intersect($want, ['hungry', 'neutral', 'slammed']));
            if ($want) {
                $this->db->where('(' . $this->band_sql('b') . ") IN ('" . implode("','", $want) . "')", null, false);
            }
        }

        switch (isset($opts['sort']) ? $opts['sort'] : 'vacancy') {
            case 'revenue': $this->db->order_by('b.latest_empty_chair_cents', 'DESC'); break;
            case 'next':    $this->db->order_by('b.latest_days_to_next', 'DESC');      break;
            case 'name':    $this->db->order_by('b.name', 'ASC');                      break;
            default:        $this->db->order_by('b.latest_vacancy_bp', 'DESC');
        }

        if (!empty($opts['limit'])) { $this->db->limit((int) $opts['limit']); }

        return $this->db->get()->result();
    }

    /**
     * Search by shop name (and optionally within one market) — the search bar's
     * answer when what was typed is a name, not "niche, city".
     *
     * @param string      $text
     * @param string|null $city_key
     * @param int         $limit
     * @return array
     */
    public function search_by_name($text, $city_key = null, $limit = 60)
    {
        $this->db->select('b.*, p.platform_key, p.name AS platform_name')
                 ->from('oc_business b')
                 ->join('oc_platform p', 'p.id = b.platform_id', 'left')
                 ->where('b.status', 'active')
                 ->where('b.suppressed_at IS NULL')
                 ->group_start()
                     ->like('b.name', (string) $text)
                     ->or_like('b.address_line', (string) $text)
                 ->group_end();
        if ($city_key) { $this->db->where('b.city_key', $city_key); }
        return $this->db->order_by('b.latest_vacancy_bp', 'DESC')->limit((int) $limit)->get()->result();
    }

    /**
     * Every city key we hold anything for — so "barbershop, Tampa" (no state)
     * can be resolved to us-fl-tampa when that is the only Tampa we know.
     *
     * @return string[]
     */
    public function known_city_keys()
    {
        $rows = $this->db->query(
            "SELECT city_key FROM oc_business WHERE city_key <> ''
             UNION SELECT city_key FROM oc_discovery_candidate WHERE city_key <> ''"
        )->result();
        return array_map(function ($r) { return (string) $r->city_key; }, $rows);
    }

    /**
     * Shops Google lists in a market that OpenChair does not track yet — no
     * business row, so no Chair Score. Shown as their own cards, honestly
     * labelled, rather than dropped (FR-2.3: say what we cannot see).
     *
     * @param string|null $city_key
     * @param string|null $niche_key
     * @param string|null $text       optional name filter
     * @param int         $limit
     * @return array
     */
    public function google_candidates($city_key, $niche_key, $text = null, $limit = 60)
    {
        $this->db->select('c.*')
                 ->from('oc_discovery_candidate c')
                 ->where('c.source', 'places')
                 ->where('c.business_id IS NULL')
                 // Hide owner opt-outs only. A shop "rejected" because Google lists
                 // no website is still a real shop — it just cannot be checked.
                 ->where("NOT (c.status = 'rejected' AND COALESCE(c.detect_evidence, '') = 'do-not-scan register')", null, false);
        if ($city_key)  { $this->db->where('c.city_key', $city_key); }
        if ($niche_key) { $this->db->where('c.niche_key', $niche_key); }
        if ($text !== null && $text !== '') {
            $this->db->group_start()->like('c.name', $text)->or_like('c.address_line', $text)->group_end();
        }
        return $this->db->order_by('c.review_count', 'DESC')->order_by('c.name', 'ASC')
                        ->limit((int) $limit)->get()->result();
    }

    /**
     * One business, with its platform.
     *
     * @param int $id
     * @return object|null
     */
    public function find($id)
    {
        $row = $this->db->select('b.*, p.platform_key, p.name AS platform_name, p.rollout, p.grid_horizon_days')
                        ->from('oc_business b')
                        ->join('oc_platform p', 'p.id = b.platform_id', 'left')
                        ->where('b.id', (int) $id)
                        ->limit(1)->get()->row();
        return $row ?: null;
    }

    /**
     * The first business in a market — so the rail's Meter and Pitch entries are
     * never dead links before the user has picked anything.
     *
     * @param string $city_key
     * @param string $niche_key
     * @return object|null
     */
    public function first_in_market($city_key, $niche_key)
    {
        $rows = $this->market($city_key, $niche_key, ['limit' => 1]);
        return $rows ? $rows[0] : null;
    }

    /**
     * Bucket counts for the filter chips.
     *
     * @param string $city_key
     * @param string $niche_key
     * @return array bucket => count
     */
    public function bucket_counts($city_key, $niche_key)
    {
        $rows = $this->db->select('(' . $this->band_sql('oc_business') . ') AS latest_bucket, COUNT(*) AS n', false)
                         ->from('oc_business')
                         ->where('city_key', $city_key)
                         ->where('niche_key', $niche_key)
                         ->where('status', 'active')
                         ->where('suppressed_at IS NULL')
                         ->group_by('1', false)
                         ->get()->result();

        $out = ['hungry' => 0, 'neutral' => 0, 'slammed' => 0];
        foreach ($rows as $r) {
            if (isset($out[$r->latest_bucket])) { $out[$r->latest_bucket] = (int) $r->n; }
        }
        return $out;
    }

    /**
     * Market totals for the Today screen.
     *
     * Sums empty_chair_cents ONLY over businesses at or above the confidence
     * floor. A total that quietly includes figures the product refuses to show
     * individually would be a number we would not stand behind if asked to break
     * it down (rule 3).
     *
     * @param string $city_key
     * @param string $niche_key
     * @return array
     */
    public function market_totals($city_key, $niche_key)
    {
        $floor = (int) $this->config->item('oc_confidence_floor');

        $row = $this->db->select('COUNT(*) AS businesses', false)
                        ->select('SUM(CASE WHEN latest_confidence >= ' . $floor . ' THEN latest_empty_chair_cents ELSE 0 END) AS ecr_cents', false)
                        ->select('SUM(CASE WHEN latest_confidence >= ' . $floor . ' THEN latest_empty_chair_hi_cents ELSE 0 END) AS ecr_hi_cents', false)
                        ->select('SUM(CASE WHEN latest_confidence >= ' . $floor . ' THEN latest_open_slots ELSE 0 END) AS open_slots', false)
                        ->select('SUM(CASE WHEN latest_confidence <  ' . $floor . ' THEN 1 ELSE 0 END) AS below_floor', false)
                        ->select('MAX(last_good_scan_at) AS last_scan', false)
                        ->from('oc_business')
                        ->where('city_key', $city_key)
                        ->where('niche_key', $niche_key)
                        ->where('status', 'active')
                        ->where('suppressed_at IS NULL')
                        ->get()->row();

        return [
            'businesses'  => (int) ($row->businesses ?? 0),
            'ecr_cents'    => (int) ($row->ecr_cents ?? 0),
            'ecr_hi_cents' => (int) ($row->ecr_hi_cents ?? 0),
            'open_slots'  => (int) ($row->open_slots ?? 0),
            'below_floor' => (int) ($row->below_floor ?? 0),
            'last_scan'   => $row->last_scan ?? null,
        ];
    }

    /**
     * Coverage by platform — the honest "38 of 91 have online booking" answer
     * (FR-2.3), plus each adapter's rollout state so the user can see which
     * platforms are actually publishing.
     *
     * @param string $city_key
     * @return array
     */
    public function coverage($city_key)
    {
        return $this->db->select('p.platform_key, p.name, p.vertical, p.rollout, p.enabled, p.tier')
                        ->select('COUNT(b.id) AS businesses', false)
                        ->select('SUM(CASE WHEN b.last_good_scan_at IS NOT NULL THEN 1 ELSE 0 END) AS scanned', false)
                        ->from('oc_platform p')
                        ->join('oc_business b', "b.platform_id = p.id AND b.city_key = " . $this->db->escape($city_key), 'left')
                        ->group_by('p.id')
                        ->order_by('businesses', 'DESC')
                        ->get()->result();
    }

    /**
     * Discovery funnel for a market: how many candidates were found versus how
     * many turned out to have public online booking. This is the numerator and
     * denominator of the coverage ratio the product must state honestly rather
     * than quietly dropping the businesses it cannot see (FR-2.3).
     *
     * @param string $city_key
     * @param string $niche_key
     * @return array
     */
    public function discovery_funnel($city_key, $niche_key)
    {
        $row = $this->db->select('COUNT(*) AS found', false)
                        ->select("SUM(status = 'verified') AS verified", false)
                        ->select("SUM(status = 'not_bookable') AS not_bookable", false)
                        ->from('oc_discovery_candidate')
                        ->where('city_key', $city_key)
                        ->where('niche_key', $niche_key)
                        ->get()->row();

        return [
            'found'        => (int) ($row->found ?? 0),
            'verified'     => (int) ($row->verified ?? 0),
            'not_bookable' => (int) ($row->not_bookable ?? 0),
        ];
    }

    /**
     * Is this market demo data? Drives the shell's sample-data flag, so a seeded
     * figure can never be mistaken for an observed one.
     *
     * @param string $city_key
     * @return bool
     */
    public function is_demo_market($city_key)
    {
        $n = $this->db->from('oc_business')
                      ->where('city_key', $city_key)
                      ->where("platform_business_key NOT LIKE 'demo-%'")
                      ->count_all_results();
        return $n === 0;
    }

    /**
     * The Chair Score v1.0 band as a SQL expression: 'hungry' | 'neutral' |
     * 'slammed', or NULL below the confidence floor.
     *
     * The same rule as oc_chair_band() in oc_format_helper.php, so filtering and
     * counting in the database agree with what every view prints. 'neutral' is
     * the stored spelling of Steady, kept so existing callers' keys still work.
     *
     * @param string $t table alias
     * @return string
     */
    public function band_sql($t = 'b')
    {
        $floor = (int) $this->config->item('oc_confidence_floor');
        $low   = (int) ($this->config->item('oc_chair_hungry_below') ?: 50);
        $high  = (int) ($this->config->item('oc_chair_slammed_from') ?: 85);
        $wait  = (int) ($this->config->item('oc_chair_wait_slammed_days') ?: 10);
        $score = "(100 - ROUND({$t}.latest_vacancy_bp / 100))";

        return "CASE WHEN {$t}.latest_vacancy_bp IS NULL OR {$t}.latest_confidence IS NULL"
             . " OR {$t}.latest_confidence < {$floor} THEN NULL"
             . " WHEN {$t}.latest_days_to_next >= {$wait} THEN 'slammed'"
             . " WHEN {$score} >= {$high} THEN 'slammed'"
             . " WHEN {$score} < {$low} THEN 'hungry'"
             . " ELSE 'neutral' END";
    }

    /**
     * Shops on the same street, nearest first — the Chair Map and Same Tuesday.
     *
     * The radius widens (1 → 1.5 → 2 → 3 → 5 miles) until at least $min_visible
     * shops with online booking are found, and the radius actually used is
     * returned so the card can print it ("every barbershop within 1 mile").
     * Found-but-not-bookable shops come back separately as the "?" pins: the
     * honest count of what we could not see (rule 5).
     *
     * Global layer — no tenant scoping, as for every method here.
     *
     * @param object $b           the subject business
     * @param int    $min_visible
     * @return array{radius:float, shops:array, unseen:array}
     */
    public function nearby($b, $min_visible = 4)
    {
        $out = ['radius' => 1.0, 'shops' => [], 'unseen' => []];
        if ($b->lat === null || $b->lng === null) { return $out; }

        $lat = (float) $b->lat;
        $lng = (float) $b->lng;
        $dist = function ($tbl) use ($lat, $lng) {
            return "(3958.8 * 2 * ASIN(SQRT(POW(SIN(RADIANS({$tbl}.lat - {$lat}) / 2), 2)"
                 . " + COS(RADIANS({$lat})) * COS(RADIANS({$tbl}.lat)) * POW(SIN(RADIANS({$tbl}.lng - {$lng}) / 2), 2))))";
        };

        $all = $this->db->select('b.id, b.name, b.lat, b.lng, b.staff_count, b.slot_minutes, b.avg_price_cents,'
                               . ' b.latest_vacancy_bp, b.latest_confidence, b.latest_days_to_next,'
                               . ' b.latest_empty_chair_cents, b.latest_empty_chair_hi_cents, b.last_good_scan_at', false)
                        ->select($dist('b') . ' AS miles', false)
                        ->from('oc_business b')
                        ->where('b.city_key', $b->city_key)
                        ->where('b.niche_key', $b->niche_key)
                        ->where('b.status', 'active')
                        ->where('b.suppressed_at IS NULL')
                        ->where('b.id !=', (int) $b->id)
                        ->where('b.lat IS NOT NULL')
                        ->order_by('miles', 'ASC')
                        ->limit(40)
                        ->get()->result();

        $radius = 5.0;
        foreach ([1.0, 1.5, 2.0, 3.0, 5.0] as $r) {
            $n = 0;
            foreach ($all as $s) { if ((float) $s->miles <= $r) { $n++; } }
            if ($n >= $min_visible) { $radius = $r; break; }
        }
        $out['radius'] = $radius;
        $out['shops']  = array_values(array_filter($all, function ($s) use ($radius) {
            return (float) $s->miles <= $radius;
        }));

        $out['unseen'] = $this->db->select('c.id, c.lat, c.lng', false)
                                  ->select($dist('c') . ' AS miles', false)
                                  ->from('oc_discovery_candidate c')
                                  ->where('c.city_key', $b->city_key)
                                  ->where('c.niche_key', $b->niche_key)
                                  ->where('c.status', 'not_bookable')
                                  ->where('c.lat IS NOT NULL')
                                  ->having('miles <=', $radius)
                                  ->order_by('miles', 'ASC')
                                  ->limit(12)
                                  ->get()->result();
        return $out;
    }

    /**
     * The menu's hourly price band, p25..p75 — the "$35–50/hr" a card names as
     * its price source (rule 4). Null when the shop publishes no priced menu.
     *
     * @param int $business_id
     * @return array{lo:int,hi:int}|null cents per hour
     */
    public function hourly_price_band($business_id)
    {
        $rows = $this->db->select('price_cents, duration_min')
                         ->from('oc_business_service')
                         ->where('business_id', (int) $business_id)
                         ->where('price_cents >', 0)
                         ->where('duration_min >', 0)
                         ->get()->result();
        if (!$rows) { return null; }

        $per_hour = array_map(function ($r) {
            return (int) round($r->price_cents * 60 / max(15, (int) $r->duration_min));
        }, $rows);
        sort($per_hour);
        $q = function ($p) use ($per_hour) {
            return $per_hour[(int) floor($p * (count($per_hour) - 1))];
        };
        return ['lo' => $q(0.25), 'hi' => max($q(0.75), $q(0.25))];
    }
}
