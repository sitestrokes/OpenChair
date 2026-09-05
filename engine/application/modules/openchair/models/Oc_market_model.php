<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Oc_market_model — a tenant's saved market (city × niche).
 *
 * Tenant layer: every query is user-scoped. A market is a SAVED SEARCH plus the
 * alert settings for it — it does not own the businesses it matches, it just
 * describes them. Two consultants working Tampa barbershops have two market
 * rows over one set of scanned businesses.
 *
 * The FE entitlement is one city; OTO1 unlocks more. Enforcing the cap here
 * rather than in the controller means a direct POST cannot get past it.
 */
class Oc_market_model extends CI_Model
{
    /**
     * @param int $user_id
     * @return array
     */
    public function all($user_id)
    {
        return $this->db->from('oc_market')
                        ->where('user_id', (int) $user_id)
                        ->where('status', 'active')
                        ->order_by('created_at', 'ASC')
                        ->get()->result();
    }

    /**
     * The market a screen should show when the user has not chosen one.
     *
     * @param int $user_id
     * @return object|null
     */
    public function current($user_id)
    {
        $rows = $this->all($user_id);
        return $rows ? $rows[0] : null;
    }

    /**
     * @param int $user_id
     * @param int $id
     * @return object|null
     */
    public function find($user_id, $id)
    {
        return $this->db->from('oc_market')
                        ->where('user_id', (int) $user_id)
                        ->where('id', (int) $id)
                        ->limit(1)->get()->row() ?: null;
    }

    /**
     * Save a market, or return the existing one for the same city+niche.
     *
     * @param int   $user_id
     * @param array $data city_key, niche_key, label, city…
     * @param int   $city_cap how many DISTINCT cities this tier allows
     * @return array{ok: bool, id?: int, error?: string}
     */
    public function save($user_id, array $data, $city_cap = 1)
    {
        $city  = trim((string) ($data['city_key'] ?? ''));
        $niche = trim((string) ($data['niche_key'] ?? ''));
        if ($city === '' || $niche === '') {
            return ['ok' => false, 'error' => 'A market needs both a city and a niche.'];
        }

        $existing = $this->db->from('oc_market')
                             ->where('user_id', (int) $user_id)
                             ->where('city_key', $city)
                             ->where('niche_key', $niche)
                             ->limit(1)->get()->row();
        if ($existing) { return ['ok' => true, 'id' => (int) $existing->id]; }

        // The cap is on distinct CITIES, not markets: the brief's FE is "1 city,
        // 3 niches", so three niches in one metro must not count as three cities.
        $cities = $this->db->select('city_key')->distinct()
                           ->from('oc_market')->where('user_id', (int) $user_id)
                           ->get()->result();
        $keys = array_map(function ($r) { return $r->city_key; }, $cities);

        if (!in_array($city, $keys, true) && count($keys) >= $city_cap) {
            return [
                'ok' => false,
                'error' => 'Your plan covers ' . $city_cap . ' ' . ($city_cap === 1 ? 'city' : 'cities') . '. '
                         . 'Remove a market or upgrade to add another.',
            ];
        }

        $this->db->insert('oc_market', [
            'user_id'        => (int) $user_id,
            'label'          => (string) ($data['label'] ?? ucfirst(str_replace('_', ' ', $niche))),
            'city_key'       => $city,
            'city'           => (string) ($data['city'] ?? ''),
            'region_code'    => (string) ($data['region_code'] ?? ''),
            'country_code'   => (string) ($data['country_code'] ?? 'US'),
            'niche_key'      => $niche,
            'alert_enabled'  => 1,
            'alert_hour'     => 8,                       // the 8am list (FR-6.1)
            'alert_timezone' => (string) ($data['timezone'] ?? 'UTC'),
            // FE ships the Hungry bucket; OTO1 adds Slammed. Stored per market so
            // an upgrade widens existing markets rather than needing them rebuilt.
            'buckets'        => (string) ($data['buckets'] ?? 'hungry'),
            'status'         => 'active',
        ]);

        return ['ok' => true, 'id' => (int) $this->db->insert_id()];
    }

    /**
     * Refresh the cached business_count shown in the market switcher.
     *
     * @param int $market_id
     * @return void
     */
    public function refresh_count($market_id)
    {
        $m = $this->db->from('oc_market')->where('id', (int) $market_id)->limit(1)->get()->row();
        if (!$m) { return; }

        $n = (int) $this->db->from('oc_business')
                            ->where('city_key', $m->city_key)
                            ->where('niche_key', $m->niche_key)
                            ->where('status', 'active')
                            ->where('suppressed_at IS NULL')
                            ->count_all_results();

        $this->db->where('id', (int) $market_id)
                 ->update('oc_market', ['business_count' => $n, 'last_refreshed_at' => date('Y-m-d H:i:s')]);
    }

    /**
     * @param int $user_id
     * @param int $id
     * @return void
     */
    public function remove($user_id, $id)
    {
        $this->db->where('user_id', (int) $user_id)->where('id', (int) $id)
                 ->update('oc_market', ['status' => 'paused']);
    }
}
