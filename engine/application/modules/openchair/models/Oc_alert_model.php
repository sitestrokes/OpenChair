<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Oc_alert_model — the 8am list (FR-6.1).
 *
 * ────────────────────────────────────────────────────────────────────────────
 * ALERTS GO TO THE CONSULTANT. NEVER TO THE BUSINESS.
 * ────────────────────────────────────────────────────────────────────────────
 * FR-6.3, and it is not a preference. Every recipient here is a `users` row —
 * the tenant. oc_business has a `phone` and an `email` column (they are public
 * listing data) and nothing in this file reads them. A digest that could reach
 * a scanned business would be the product contacting a stranger about its own
 * observations of them, which is rule 7 with worse optics.
 *
 * ────────────────────────────────────────────────────────────────────────────
 * THE NUMBERS ARE STORED AS SENT
 * ────────────────────────────────────────────────────────────────────────────
 * oc_alert_item copies vacancy_bp, open_slots and empty_chair_cents at build
 * time rather than joining to the live business row. A digest is a claim made
 * at a moment: "this is what your list looked like at 8am". If the item
 * re-read live data, a consultant clicking a link three hours later would see
 * different figures from the ones they read over breakfast, with no way to tell
 * which was real. Freezing them also makes a complaint answerable.
 */
class Oc_alert_model extends CI_Model
{
    /**
     * Build one tenant's digest for a date.
     *
     * Idempotent by UNIQUE(user_id, market_id, run_date, channel) — re-running
     * the 8am job replaces the run rather than sending twice.
     *
     * @param int    $user_id
     * @param object $market
     * @param string $run_date  Y-m-d in the CONSULTANT's timezone, not the shop's
     * @param string $channel
     * @return array{run_id: int, hungry: int, slammed: int, items: array}
     */
    public function build($user_id, $market, $run_date, $channel = 'in_app')
    {
        $floor = (int) $this->config->item('oc_confidence_floor');

        // Which buckets this market is entitled to. FE ships Hungry; OTO1 adds
        // Slammed. Read from the market row so an upgrade widens existing
        // markets rather than requiring them to be rebuilt.
        $buckets = $market->buckets ? explode(',', $market->buckets) : ['hungry'];

        // Only businesses this tenant WATCHES, and only ones we would publish a
        // number for. A digest that included businesses whose score the product
        // refuses to display would be leading with figures we will not stand
        // behind one click later.
        $rows = $this->db->select('b.id, b.name, b.latest_bucket, b.latest_vacancy_bp,
                                   b.latest_open_slots, b.latest_empty_chair_cents,
                                   b.latest_empty_chair_hi_cents, b.latest_days_to_next,
                                   b.latest_confidence, p.id AS prospect_id, p.last_alerted_on')
                         ->from('oc_prospect p')
                         ->join('oc_business b', 'b.id = p.business_id')
                         ->where('p.user_id', (int) $user_id)
                         ->where('p.alert_muted', 0)
                         ->where_in('p.stage', ['new', 'watching', 'contacted', 'meeting'])
                         ->where('b.suppressed_at IS NULL')
                         ->where('b.latest_confidence >=', $floor)
                         ->where_in('b.latest_bucket', $buckets)
                         ->order_by('b.latest_empty_chair_cents', 'DESC')
                         ->limit((int) ($market->max_results ?: 25))
                         ->get()->result();

        // Replace any existing run for this slot rather than appending.
        $existing = $this->db->from('oc_alert_run')
                             ->where('user_id', (int) $user_id)
                             ->where('market_id', (int) $market->id)
                             ->where('run_date', $run_date)
                             ->where('channel', $channel)
                             ->limit(1)->get()->row();
        if ($existing) {
            $this->db->where('alert_run_id', $existing->id)->delete('oc_alert_item');
            $run_id = (int) $existing->id;
        } else {
            $this->db->insert('oc_alert_run', [
                'user_id'   => (int) $user_id,
                'market_id' => (int) $market->id,
                'run_date'  => $run_date,
                'channel'   => $channel,
                'status'    => 'pending',
            ]);
            $run_id = (int) $this->db->insert_id();
        }

        $hungry = 0; $slammed = 0; $pos = 0; $items = [];
        foreach ($rows as $r) {
            $pos++;
            if ($r->latest_bucket === 'hungry') { $hungry++; }
            if ($r->latest_bucket === 'slammed') { $slammed++; }

            // "New" means new TO THIS DIGEST, not newly discovered — it is the
            // flag that tells a consultant what to look at first.
            $is_new = empty($r->last_alerted_on) || $r->last_alerted_on < $run_date;

            $this->db->insert('oc_alert_item', [
                'alert_run_id'      => $run_id,
                'business_id'       => (int) $r->id,
                'prospect_id'       => (int) $r->prospect_id,
                'bucket'            => $r->latest_bucket,
                'rank_pos'          => $pos,
                'vacancy_bp'        => (int) $r->latest_vacancy_bp,
                'open_slots'        => (int) $r->latest_open_slots,
                'empty_chair_cents' => (int) $r->latest_empty_chair_cents,
                'days_to_next'      => $r->latest_days_to_next,
                'is_new'            => $is_new ? 1 : 0,
            ]);
            $items[] = $r;

            $this->db->where('id', (int) $r->prospect_id)
                     ->update('oc_prospect', ['last_alerted_on' => $run_date]);
        }

        $this->db->where('id', $run_id)->update('oc_alert_run', [
            'hungry_count'  => $hungry,
            'slammed_count' => $slammed,
            'total_count'   => $pos,
            // in_app is delivered by being read, so it is 'sent' on build. An
            // email or Telegram run stays 'pending' until its sender confirms.
            'status'        => $channel === 'in_app' ? 'sent' : 'pending',
            'sent_at'       => $channel === 'in_app' ? date('Y-m-d H:i:s') : null,
        ]);

        return ['run_id' => $run_id, 'hungry' => $hungry, 'slammed' => $slammed, 'items' => $items];
    }

    /**
     * The most recent digest for a tenant, with its items as sent.
     *
     * @param int $user_id
     * @return array{run: object|null, items: array}
     */
    public function latest($user_id)
    {
        $run = $this->db->from('oc_alert_run')
                        ->where('user_id', (int) $user_id)
                        ->order_by('run_date', 'DESC')->order_by('id', 'DESC')
                        ->limit(1)->get()->row();
        if (!$run) { return ['run' => null, 'items' => []]; }

        // Joined to oc_business for the NAME only. Every figure comes from the
        // item row — the numbers as sent.
        $items = $this->db->select('i.*, b.name, pl.name AS platform_name')
                          ->from('oc_alert_item i')
                          ->join('oc_business b', 'b.id = i.business_id')
                          ->join('oc_platform pl', 'pl.id = b.platform_id', 'left')
                          ->where('i.alert_run_id', $run->id)
                          ->order_by('i.rank_pos', 'ASC')
                          ->get()->result();

        return ['run' => $run, 'items' => $items];
    }
}
