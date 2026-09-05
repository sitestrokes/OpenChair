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
            $this->db->where_in('b.latest_bucket', (array) $opts['bucket']);
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
        $rows = $this->db->select('latest_bucket, COUNT(*) AS n')
                         ->from('oc_business')
                         ->where('city_key', $city_key)
                         ->where('niche_key', $niche_key)
                         ->where('status', 'active')
                         ->where('suppressed_at IS NULL')
                         ->group_by('latest_bucket')
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
            'ecr_cents'   => (int) ($row->ecr_cents ?? 0),
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
}
