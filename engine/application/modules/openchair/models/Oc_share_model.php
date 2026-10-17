<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Oc_share_model — share links for the signature-visual cards (oc_010).
 *
 * A link is the consultant's to send, by their own hand. Nothing here contacts a
 * business (05-prd.md §8 rule 7): this model writes that a link EXISTS, and
 * counts how often it was opened. It stores no IP, no user agent, no cookie —
 * counted, not tracked.
 *
 * The card is FROZEN into `payload` when the link is made, for the same reason a
 * pitch freezes its evidence: what an owner opens next week is exactly what was
 * chosen today, not whatever the nightly scan says by then.
 *
 * Tenant model: every read is scoped by user_id, except find_public(), which is
 * keyed by the unguessable token and is the public page's only entry point.
 */
class Oc_share_model extends CI_Model
{
    const KINDS = ['week', 'map', 'receipt', 'tuesday', 'progress'];

    /**
     * Freeze a card and mint its link.
     *
     * @param int    $user_id   the TENANT (agency) — see oc_012_team_tenancy.sql
     * @param int    $actor_id  the team member who made it
     * @param object $b         oc_business row
     * @param string $kind
     * @param bool   $show_logo
     * @param array  $payload   Oc_visuals_lib::card() output
     * @return string|null the token
     */
    public function create($user_id, $actor_id, $b, $kind, $show_logo, array $payload)
    {
        if (!in_array($kind, self::KINDS, true)) { return null; }

        $days  = (int) ($this->config->item('oc_share_ttl_days') ?: 30);
        $token = bin2hex(random_bytes(16));
        $method = (string) ($this->config->item('oc_chair_method') ?: 'v1.0');

        $ok = $this->db->insert('oc_share_link', [
            'token'       => $token,
            'user_id'     => (int) $user_id,
            'created_by'  => (int) $actor_id,
            'business_id' => (int) $b->id,
            'kind'        => $kind,
            'show_logo'   => $show_logo ? 1 : 0,
            'method'      => substr($method, 0, 16),
            'payload'     => json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            'expires_at'  => date('Y-m-d H:i:s', time() + $days * 86400),
        ]);
        return $ok ? $token : null;
    }

    /**
     * This consultant's links for one business, newest first — the Activity list.
     *
     * @param int $user_id
     * @param int $business_id
     * @param int $limit
     * @return array
     */
    public function for_business($user_id, $business_id, $limit = 5)
    {
        return $this->db->select('s.id, s.token, s.kind, s.created_at, s.expires_at, s.open_count, s.last_opened_at, s.revoked_at,'
                               . ' u.first_name, u.last_name', false)
                        ->from('oc_share_link s')
                        ->join('users u', 'u.id = COALESCE(s.created_by, s.user_id)', 'left', false)
                        ->where('s.user_id', (int) $user_id)
                        ->where('s.business_id', (int) $business_id)
                        ->order_by('s.created_at', 'DESC')
                        ->limit((int) $limit)
                        ->get()->result();
    }

    /**
     * One of this consultant's links by token (for the "your link" confirmation).
     *
     * @param int    $user_id
     * @param string $token
     * @return object|null
     */
    public function find_own($user_id, $token)
    {
        if (!preg_match('/^[a-f0-9]{32}$/', (string) $token)) { return null; }
        return $this->db->from('oc_share_link')
                        ->where('user_id', (int) $user_id)
                        ->where('token', $token)
                        ->limit(1)->get()->row() ?: null;
    }

    /**
     * The public page's lookup. Returns the row with `expired` set when it has
     * run out or been revoked, so the page can say so rather than 404.
     *
     * @param string $token
     * @return object|null
     */
    public function find_public($token)
    {
        if (!preg_match('/^[a-f0-9]{32}$/', (string) $token)) { return null; }
        $row = $this->db->from('oc_share_link')
                        ->where('token', $token)
                        ->limit(1)->get()->row();
        if (!$row) { return null; }
        $row->expired = $row->revoked_at !== null || strtotime($row->expires_at) < time();
        return $row;
    }

    /**
     * Count an open. Nothing about the opener is stored.
     *
     * @param int $id
     * @return void
     */
    public function record_open($id)
    {
        $this->db->set('open_count', 'open_count + 1', false)
                 ->set('last_opened_at', date('Y-m-d H:i:s'))
                 ->where('id', (int) $id)
                 ->update('oc_share_link');
    }
}
