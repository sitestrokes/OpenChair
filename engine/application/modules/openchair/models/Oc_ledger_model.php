<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Oc_ledger_model — the 8-week trend.
 *
 * One component, two jobs (40-ux-spec.md §8): it is the prospecting artifact
 * AND the retainer report. Which means every figure here is eventually read by
 * the business owner who is paying for the work it appears to credit — an
 * audience who knows their own seasonality and will check.
 *
 * That is why lift is always reported net of the cohort, and why a partial week
 * shows a gap rather than an average of whatever we happened to catch.
 */
class Oc_ledger_model extends CI_Model
{
    /** Weeks below this many scan days are shown as gaps, not points. */
    const MIN_SCAN_DAYS = 5;

    /** Below this many comparable businesses there is no cohort to net against. */
    const COHORT_MIN_N = 20;

    /**
     * One business's weekly trend.
     *
     * @param int $business_id
     * @param int $weeks
     * @return array
     */
    public function trend($business_id, $weeks = 8)
    {
        return $this->db->from('oc_vacancy_weekly')
                        ->where('business_id', (int) $business_id)
                        ->order_by('week_start', 'DESC')
                        ->limit((int) $weeks)
                        ->get()->result();
    }

    /**
     * The cohort's movement over the same weeks.
     *
     * @param string $city_key
     * @param string $niche_key
     * @param int    $weeks
     * @return array week_start => ['fill_rate_bp' => int, 'n' => int]
     */
    public function cohort($city_key, $niche_key, $weeks = 8)
    {
        $rows = $this->db->select('w.week_start, ROUND(AVG(w.fill_rate_bp)) AS fill_rate_bp, COUNT(*) AS n', false)
                         ->from('oc_vacancy_weekly w')
                         ->join('oc_business b', 'b.id = w.business_id')
                         ->where('b.city_key', $city_key)
                         ->where('b.niche_key', $niche_key)
                         ->where('b.suppressed_at IS NULL')
                         ->where('w.scan_days >=', self::MIN_SCAN_DAYS)
                         ->group_by('w.week_start')
                         ->order_by('w.week_start', 'DESC')
                         ->limit((int) $weeks)
                         ->get()->result();

        $out = [];
        foreach ($rows as $r) {
            $out[$r->week_start] = ['fill_rate_bp' => (int) $r->fill_rate_bp, 'n' => (int) $r->n];
        }
        return $out;
    }

    /**
     * Lift, net of the market.
     *
     * ────────────────────────────────────────────────────────────────────────
     * THIS IS THE NUMBER THE RETENTION TIER IS SOLD ON, SO IT IS THE ONE MOST
     * WORTH GETTING RIGHT.
     * ────────────────────────────────────────────────────────────────────────
     * A difference-in-differences: how much the client's fill rate moved, minus
     * how much comparable businesses in the same metro moved over the same
     * weeks. A shop that gained 20 points in a market that gained 18 was
     * carried by the season, and billing for that is how an account is lost the
     * month the owner works it out for themselves.
     *
     * Returns claimable=false rather than a smaller number when there is no
     * usable cohort. "We cannot separate your result from the market" is a
     * survivable sentence; a confident attribution that turns out to be
     * seasonality is not.
     *
     * @param array  $weeks    trend rows, newest first
     * @param array  $cohort   week_start => [...]
     * @param string $campaign_start  when the work began (Y-m-d)
     * @return array
     */
    public function lift(array $weeks, array $cohort, $campaign_start)
    {
        $usable = array_filter($weeks, function ($w) {
            return (int) $w->scan_days >= self::MIN_SCAN_DAYS;
        });
        $usable = array_reverse(array_values($usable));   // oldest first

        $baseline = [];
        $post = [];
        foreach ($usable as $w) {
            if ($w->week_start < $campaign_start) { $baseline[] = $w; }
            else { $post[] = $w; }
        }

        if (count($baseline) < 2 || count($post) < 2) {
            return [
                'claimable' => false,
                'why' => 'Not enough usable weeks either side of the start date. '
                       . 'At least two before and two after, each with '
                       . self::MIN_SCAN_DAYS . '+ scan days.',
            ];
        }

        $avg = function ($rows) {
            $n = count($rows);
            return $n ? array_sum(array_map(function ($r) { return (int) $r->fill_rate_bp; }, $rows)) / $n : 0;
        };

        $client_before = $avg($baseline);
        $client_after  = $avg($post);
        $client_delta  = $client_after - $client_before;

        // Cohort over the same weeks, and only where the cohort is big enough to
        // mean anything.
        $c_before = []; $c_after = []; $min_n = PHP_INT_MAX;
        foreach ($usable as $w) {
            if (!isset($cohort[$w->week_start])) { continue; }
            $c = $cohort[$w->week_start];
            $min_n = min($min_n, $c['n']);
            if ($w->week_start < $campaign_start) { $c_before[] = $c['fill_rate_bp']; }
            else { $c_after[] = $c['fill_rate_bp']; }
        }

        $has_cohort = count($c_before) >= 2 && count($c_after) >= 2 && $min_n >= self::COHORT_MIN_N;

        if (!$has_cohort) {
            return [
                'claimable'    => false,
                'client_delta' => round($client_delta / 100, 1),
                'why' => 'Fewer than ' . self::COHORT_MIN_N . ' comparable businesses were scanned over '
                       . 'these weeks, so the market movement cannot be measured. The change below is '
                       . 'raw, and part of it may simply be the season.',
            ];
        }

        $cohort_delta = (array_sum($c_after) / count($c_after)) - (array_sum($c_before) / count($c_before));

        return [
            'claimable'    => true,
            'client_delta' => round($client_delta / 100, 1),
            'cohort_delta' => round($cohort_delta / 100, 1),
            'net'          => round(($client_delta - $cohort_delta) / 100, 1),
            'cohort_n'     => $min_n,
        ];
    }

    /**
     * The attribution caveat. Ships verbatim on every client-facing report
     * (FR-8.3, 35-scoring-model.md §6.4) — it is not editable, and it is not
     * a footer that a white-label customer can switch off.
     *
     * @return string
     */
    public function caveat()
    {
        return 'This measures online-bookable availability on the booking platform, not total '
             . 'business. It cannot separate the effect of this work from seasonality, weather, '
             . 'pricing, staffing or anything you did yourself. Where at least ' . self::COHORT_MIN_N
             . ' comparable businesses in this metro were scanned over the same weeks, their movement '
             . 'is shown alongside yours so you can judge how much of the change was the market. '
             . 'The baseline window and start date were recorded before the work began and have not '
             . 'been changed.';
    }

    /**
     * Dead-zone cells for a business — which day/hour blocks are chronically
     * open. "Tuesday 1-4pm is where the offer goes."
     *
     * @param int $business_id
     * @return array
     */
    public function dead_zones($business_id)
    {
        return $this->db->from('oc_deadzone_cell')
                        ->where('business_id', (int) $business_id)
                        ->order_by('dow', 'ASC')->order_by('block_start_hour', 'ASC')
                        ->get()->result();
    }
}
