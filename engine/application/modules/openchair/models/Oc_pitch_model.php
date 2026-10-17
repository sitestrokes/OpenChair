<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Oc_pitch_model — generated pitch artifacts, and their frozen evidence.
 *
 * ────────────────────────────────────────────────────────────────────────────
 * THIS MODEL HAS NO SEND METHOD, AND MUST NEVER GET ONE.
 * ────────────────────────────────────────────────────────────────────────────
 * 05-prd.md §8 rule 7: the product drafts, the consultant sends. `sent_at` and
 * `sent_channel` exist on oc_pitch, and they are a MANUAL LOG — the consultant
 * ticking "I emailed this on Tuesday" so their own pipeline stays honest. They
 * are not a dispatch record, and nothing in this codebase writes them from a
 * queue. There is no path from here into sequence_queue or telephony; the
 * module's integrity suite asserts the absence.
 *
 * ────────────────────────────────────────────────────────────────────────────
 * WHY THE EVIDENCE IS FROZEN INTO THE PITCH ROW
 * ────────────────────────────────────────────────────────────────────────────
 * Raw snapshots are purged at 14 days (oc_003). A pitch outlives that: a
 * consultant may walk into a shop three weeks after generating the one-pager,
 * holding a printout of a grid the database no longer has.
 *
 * If the artifact re-rendered from live data it would silently change between
 * the print and the meeting — the owner would be looking at one grid and the
 * paper would show another. So the numbers AS SHOWN are copied onto the pitch
 * row, and the grid is gzipped into evidence_gz. What was handed over is what
 * is stored, permanently, which is also what makes a disputed figure
 * answerable.
 */
class Oc_pitch_model extends CI_Model
{
    /**
     * Create a pitch, freezing the evidence at this moment.
     *
     * @param int    $user_id
     * @param object $business  the oc_business row as displayed
     * @param string $kind      hungry|slammed
     * @param string $format    onepager|email|sms
     * @param array  $artifact  the generated copy blocks
     * @param array|null $grid  the availability grid to freeze
     * @return int pitch id
     */
    public function create($user_id, $business, $kind, $format, array $artifact, $grid = null, $actor_id = null)
    {
        $prospect = $this->db->select('id')->from('oc_prospect')
                             ->where('user_id', (int) $user_id)
                             ->where('business_id', (int) $business->id)
                             ->limit(1)->get()->row();

        $evidence = null;
        if ($grid !== null) {
            // gzip, not raw JSON: a 14-day x 6-chair grid is a few KB compressed
            // and roughly ten times that as text. These rows are kept forever.
            $evidence = gzencode(json_encode($grid), 6);
        }

        $this->db->insert('oc_pitch', [
            'user_id'     => (int) $user_id,
            'created_by'  => $actor_id === null ? (int) $user_id : (int) $actor_id,
            'business_id' => (int) $business->id,
            'prospect_id' => $prospect ? (int) $prospect->id : null,
            'kind'        => $kind,
            'format'      => $format,
            'headline'    => mb_substr((string) ($artifact['headline'] ?? ''), 0, 255),
            'body'        => (string) ($artifact['body'] ?? ''),
            'artifact'    => json_encode($artifact),

            'evidence_codec'       => $evidence ? 'gzip_json_v1' : null,
            'evidence_gz'          => $evidence,
            'evidence_captured_on' => $business->latest_observed_on ?: date('Y-m-d'),

            // The numbers AS SHOWN. Never recomputed on read — see the class note.
            'vacancy_bp'        => (int) $business->latest_vacancy_bp,
            'open_slots'        => (int) $business->latest_open_slots,
            'empty_chair_cents' => (int) $business->latest_empty_chair_cents,
            'days_to_next'      => $business->latest_days_to_next,
        ]);

        $id = (int) $this->db->insert_id();

        if ($prospect) {
            $this->db->where('id', $prospect->id)
                     ->update('oc_prospect', ['last_pitched_at' => date('Y-m-d H:i:s')]);
        }
        return $id;
    }

    /**
     * @param int $user_id
     * @param int $id
     * @return object|null
     */
    public function find($user_id, $id)
    {
        $row = $this->db->select('p.*, b.name, b.address_line, b.city, b.avg_price_cents,
                                  b.staff_count, b.latest_empty_chair_hi_cents,
                                  pl.name AS platform_name')
                        ->from('oc_pitch p')
                        ->join('oc_business b', 'b.id = p.business_id')
                        ->join('oc_platform pl', 'pl.id = b.platform_id', 'left')
                        ->where('p.user_id', (int) $user_id)
                        ->where('p.id', (int) $id)
                        ->limit(1)->get()->row();
        if (!$row) { return null; }

        $row->artifact_data = $row->artifact ? json_decode($row->artifact, true) : [];
        $row->evidence = null;
        if ($row->evidence_gz && $row->evidence_codec === 'gzip_json_v1') {
            $json = @gzdecode($row->evidence_gz);
            if ($json !== false) { $row->evidence = json_decode($json, true); }
        }
        return $row;
    }

    /**
     * Pitches this tenant has generated for a business.
     *
     * @param int $user_id
     * @param int $business_id
     * @return array
     */
    public function for_business($user_id, $business_id)
    {
        return $this->db->from('oc_pitch')
                        ->where('user_id', (int) $user_id)
                        ->where('business_id', (int) $business_id)
                        ->order_by('created_at', 'DESC')
                        ->limit(20)->get()->result();
    }

    /**
     * Record that the CONSULTANT sent this themselves.
     *
     * Deliberately named `mark_sent`, not `send`. It writes a log entry about
     * something a human did outside this system. It dispatches nothing, and the
     * day it does, rule 7 is broken.
     *
     * @param int    $user_id
     * @param int    $id
     * @param string $channel  free text: 'walked in', 'email', 'phone'
     * @return void
     */
    public function mark_sent($user_id, $id, $channel)
    {
        $this->db->where('user_id', (int) $user_id)->where('id', (int) $id)
                 ->update('oc_pitch', [
                     'sent_at'      => date('Y-m-d H:i:s'),
                     'sent_channel' => mb_substr((string) $channel, 0, 24),
                 ]);
    }
}
