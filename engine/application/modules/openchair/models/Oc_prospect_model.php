<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Oc_prospect_model — the TENANT layer.
 *
 * ────────────────────────────────────────────────────────────────────────────
 * EVERY QUERY HERE IS SCOPED BY user_id. THAT IS THE POINT.
 * ────────────────────────────────────────────────────────────────────────────
 * This is the mirror image of Oc_business_model, which must never see a
 * user_id. A prospect row is one tenant's *relationship* to a business in the
 * shared global layer: their stage, their notes, whether they want alerts. The
 * business itself is not theirs and is not scanned for them alone.
 *
 * Watching therefore does NOT create work. It increments watcher_count on the
 * single shared oc_scan_queue row, which raises that business's priority. Fifty
 * consultants watching one barbershop is still one crawl a night — which is the
 * whole reason $0.02/scan is a viable COGS line rather than a per-customer cost.
 */
class Oc_prospect_model extends CI_Model
{
    /**
     * One tenant's watchlist, joined to the shared business row.
     *
     * @param int   $user_id
     * @param array $opts stage, bucket, limit
     * @return array
     */
    public function watchlist($user_id, array $opts = [])
    {
        $this->db->select('p.*, b.name, b.city, b.address_line, b.niche_key,
                           b.latest_vacancy_bp, b.latest_open_slots, b.latest_bookable_slots,
                           b.latest_empty_chair_cents, b.latest_empty_chair_hi_cents,
                           b.latest_days_to_next, b.latest_bucket, b.latest_confidence,
                           b.last_good_scan_at, pl.name AS platform_name')
                 ->from('oc_prospect p')
                 ->join('oc_business b', 'b.id = p.business_id')
                 ->join('oc_platform pl', 'pl.id = b.platform_id', 'left')
                 ->where('p.user_id', (int) $user_id)
                 ->where('b.suppressed_at IS NULL');   // an opt-out disappears from every watchlist

        if (!empty($opts['stage'])) { $this->db->where('p.stage', $opts['stage']); }
        if (!empty($opts['bucket'])) { $this->db->where('b.latest_bucket', $opts['bucket']); }

        $this->db->order_by('p.pinned', 'DESC')
                 ->order_by('b.latest_empty_chair_cents', 'DESC');
        if (!empty($opts['limit'])) { $this->db->limit((int) $opts['limit']); }

        return $this->db->get()->result();
    }

    /**
     * Is this business on this tenant's list?
     *
     * @param int $user_id
     * @param int $business_id
     * @return object|null
     */
    public function find($user_id, $business_id)
    {
        return $this->db->from('oc_prospect')
                        ->where('user_id', (int) $user_id)
                        ->where('business_id', (int) $business_id)
                        ->limit(1)->get()->row() ?: null;
    }

    /**
     * Start watching. Idempotent — the UNIQUE(user_id, business_id) in oc_005
     * makes a double-click a no-op rather than a duplicate row.
     *
     * Also bumps the SHARED queue row's watcher_count, which is where "watching
     * raises priority, never duplicates work" actually happens.
     *
     * @param int $user_id
     * @param int $business_id
     * @param int|null $market_id
     * @return bool true if newly added
     */
    public function watch($user_id, $business_id, $market_id = null)
    {
        $existing = $this->find($user_id, $business_id);
        if ($existing) { return false; }

        $biz = $this->db->select('latest_bucket, platform_id')
                        ->from('oc_business')->where('id', (int) $business_id)
                        ->limit(1)->get()->row();
        if (!$biz) { return false; }

        $this->db->insert('oc_prospect', [
            'user_id'       => (int) $user_id,
            'business_id'   => (int) $business_id,
            'market_id'     => $market_id ? (int) $market_id : null,
            'stage'         => 'watching',
            // Frozen at add time on purpose: six weeks later "you added this when
            // it was Hungry and it is Slammed now" is the conversation, and it is
            // impossible to have if the field tracks the present.
            'bucket_at_add' => $biz->latest_bucket,
            'first_seen_at' => date('Y-m-d H:i:s'),
        ]);

        $this->_sync_watchers($business_id, $biz->platform_id);
        return true;
    }

    /**
     * Stop watching.
     *
     * @param int $user_id
     * @param int $business_id
     * @return void
     */
    public function unwatch($user_id, $business_id)
    {
        $this->db->where('user_id', (int) $user_id)
                 ->where('business_id', (int) $business_id)
                 ->delete('oc_prospect');

        $biz = $this->db->select('platform_id')->from('oc_business')
                        ->where('id', (int) $business_id)->limit(1)->get()->row();
        if ($biz) { $this->_sync_watchers($business_id, $biz->platform_id); }
    }

    /**
     * Move a prospect along the pipeline.
     *
     * @param int    $user_id
     * @param int    $business_id
     * @param string $stage
     * @return bool
     */
    public function set_stage($user_id, $business_id, $stage)
    {
        $allowed = ['new','watching','contacted','meeting','pitched','won','lost','archived'];
        if (!in_array($stage, $allowed, true)) { return false; }

        $this->db->where('user_id', (int) $user_id)
                 ->where('business_id', (int) $business_id)
                 ->update('oc_prospect', ['stage' => $stage]);
        return $this->db->affected_rows() > 0;
    }

    /**
     * @param int    $user_id
     * @param int    $business_id
     * @param string $notes
     * @return void
     */
    public function set_notes($user_id, $business_id, $notes)
    {
        $this->db->where('user_id', (int) $user_id)
                 ->where('business_id', (int) $business_id)
                 ->update('oc_prospect', ['notes' => (string) $notes]);
    }

    /**
     * Pipeline counts for the stage filter.
     *
     * @param int $user_id
     * @return array
     */
    public function stage_counts($user_id)
    {
        $rows = $this->db->select('stage, COUNT(*) AS n')
                         ->from('oc_prospect')
                         ->where('user_id', (int) $user_id)
                         ->group_by('stage')->get()->result();
        $out = [];
        foreach ($rows as $r) { $out[$r->stage] = (int) $r->n; }
        return $out;
    }

    /**
     * Which of these business ids is this tenant already watching? Lets the
     * Radar list render its watch state in one query instead of N.
     *
     * @param int   $user_id
     * @param int[] $business_ids
     * @return array business_id => true
     */
    public function watched_map($user_id, array $business_ids)
    {
        if (empty($business_ids)) { return []; }
        $rows = $this->db->select('business_id')
                         ->from('oc_prospect')
                         ->where('user_id', (int) $user_id)
                         ->where_in('business_id', array_map('intval', $business_ids))
                         ->get()->result();
        $out = [];
        foreach ($rows as $r) { $out[(int) $r->business_id] = true; }
        return $out;
    }

    /**
     * Recompute watcher_count on the SHARED queue row and make sure the business
     * is queued at all.
     *
     * This is the join between the tenant layer and the global one, and it is
     * the only place the two touch. Note what it does NOT do: it never inserts a
     * second queue row, because oc_scan_queue has UNIQUE(business_id) and the
     * upsert below leans on it.
     *
     * @param int $business_id
     * @param int $platform_id
     * @return void
     */
    private function _sync_watchers($business_id, $platform_id)
    {
        $n = (int) $this->db->from('oc_prospect')
                            ->where('business_id', (int) $business_id)
                            ->count_all_results();

        // Lower priority number = sooner. More watchers pulls it forward, and it
        // is floored at 1 so a popular business cannot starve everything else.
        $priority = max(1, 5 - (int) floor($n / 3));

        $sql = 'INSERT INTO oc_scan_queue (business_id, platform_id, priority, watcher_count, due_at, status)
                VALUES (?, ?, ?, ?, NOW(), ' . $this->db->escape('due') . ')
                ON DUPLICATE KEY UPDATE
                  watcher_count = VALUES(watcher_count),
                  priority      = LEAST(priority, VALUES(priority))';
        $this->db->query($sql, [(int) $business_id, (int) $platform_id, $priority, $n]);
    }
}
