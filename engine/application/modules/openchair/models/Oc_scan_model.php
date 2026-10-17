<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Oc_scan_model — "New scan": a tenant's request over the global discovery table.
 *
 * docs/70-radar-build.md R1. The consultant pastes public booking URLs for a
 * niche and a city. Each URL becomes (or re-uses) ONE global
 * oc_discovery_candidate row — the same listing pasted by fifty agencies is
 * still one candidate and, once verified, one oc_business scanned once a night.
 * The tenant's oc_scan_request only points at those candidates, so the progress
 * page can say "your scan" without the global layer ever knowing who asked.
 *
 * Nothing here fetches anything. The service's discovery job reads the
 * candidates, recognises the platform from the URL's shape, and creates the
 * business; this model only validates and records what was typed.
 */
class Oc_scan_model extends CI_Model
{
    /** A paste is capped: a market scan is dozens of shops, not thousands. */
    const MAX_URLS = 50;

    /** Longest URL we store (oc_discovery_candidate.website is 512). */
    const MAX_URL_LEN = 512;

    /**
     * "Tampa, FL" → city_key us-fl-tampa. US only for now, because every
     * adapter and the demo market are US; an unparseable city is refused rather
     * than guessed, since a wrong city_key silently splits a market in two.
     *
     * @param string $input
     * @return array{city_key:string, city:string, region:string}|null
     */
    public function parse_city($input)
    {
        $input = trim(preg_replace('/\s+/', ' ', (string) $input));
        if (!preg_match('/^([A-Za-z][A-Za-z .\'-]{1,60}),\s*([A-Za-z]{2})$/', $input, $m)) { return null; }
        $city = ucwords(strtolower(trim($m[1])));
        $region = strtoupper($m[2]);
        $slug = trim(preg_replace('/[^a-z0-9]+/', '_', strtolower($city)), '_');
        return ['city_key' => 'us-' . strtolower($region) . '-' . $slug, 'city' => $city, 'region' => $region];
    }

    /**
     * Split a paste into URLs we will accept, and the lines we will not (with
     * the reason, so the consultant can fix them).
     *
     * Accepted: https only, no credentials in the URL, a named host (not an IP
     * address, not localhost), at most MAX_URL_LEN. The fragment is dropped; the
     * query string is kept here and canonicalised by the service, which knows
     * each platform's URL shape.
     *
     * @param string $text
     * @return array{ok: string[], bad: array<int, array{line:string, why:string}>}
     */
    public function clean_urls($text)
    {
        $ok = []; $bad = [];
        foreach (preg_split('/[\r\n]+/', (string) $text) as $line) {
            $line = trim($line);
            if ($line === '') { continue; }
            $why = $this->url_problem($line);
            if ($why !== null) { $bad[] = ['line' => mb_substr($line, 0, 120), 'why' => $why]; continue; }
            $url = preg_replace('/#.*$/', '', $line);
            $ok[$url] = true;
        }
        $ok = array_keys($ok);
        if (count($ok) > self::MAX_URLS) {
            foreach (array_slice($ok, self::MAX_URLS) as $extra) {
                $bad[] = ['line' => mb_substr($extra, 0, 120), 'why' => 'over the ' . self::MAX_URLS . '-link limit for one scan'];
            }
            $ok = array_slice($ok, 0, self::MAX_URLS);
        }
        return ['ok' => $ok, 'bad' => $bad];
    }

    /**
     * Why a line is not an acceptable booking URL, or null when it is.
     *
     * @param string $url
     * @return string|null
     */
    public function url_problem($url)
    {
        if (strlen($url) > self::MAX_URL_LEN) { return 'too long'; }
        if (!filter_var($url, FILTER_VALIDATE_URL)) { return 'not a web address'; }
        $p = parse_url($url);
        if (!$p || strtolower($p['scheme'] ?? '') !== 'https') { return 'must start with https://'; }
        if (isset($p['user']) || isset($p['pass'])) { return 'must not contain a username or password'; }
        $host = strtolower($p['host'] ?? '');
        if ($host === '' || $host === 'localhost' || substr($host, -6) === '.local'
            || filter_var(trim($host, '[]'), FILTER_VALIDATE_IP)) {
            return 'must be a public booking site';
        }
        if (strpos($host, '.') === false) { return 'must be a public booking site'; }
        return null;
    }

    /**
     * Record a scan request and its candidates.
     *
     * @param int    $tenant_id
     * @param int    $actor_id
     * @param array  $city   parse_city() output
     * @param string $niche_key
     * @param string[] $urls clean_urls()['ok']
     * @return int request id, 0 on failure
     */
    public function create($tenant_id, $actor_id, array $city, $niche_key, array $urls)
    {
        $this->db->trans_start();
        $this->db->insert('oc_scan_request', [
            'user_id'    => (int) $tenant_id,
            'created_by' => (int) $actor_id,
            'city_key'   => $city['city_key'],
            'niche_key'  => (string) $niche_key,
            'city_label' => $city['city'] . ', ' . $city['region'],
            'url_count'  => count($urls),
        ]);
        $rid = (int) $this->db->insert_id();

        foreach ($urls as $url) {
            // One global row per listing (uq_cand_source). LAST_INSERT_ID(id) makes
            // insert_id() return the existing row's id on a duplicate. A candidate
            // already parked as not_bookable is NOT reset — the 90-day recheck
            // owns that — but a rejected one stays rejected (owner opt-out).
            $this->db->query(
                'INSERT INTO oc_discovery_candidate (source, source_ref, city_key, niche_key, website, status)
                 VALUES (\'manual\', ?, ?, ?, ?, \'new\')
                 ON DUPLICATE KEY UPDATE id = LAST_INSERT_ID(id)',
                [sha1(strtolower($url)), $city['city_key'], (string) $niche_key, $url]
            );
            $cid = (int) $this->db->insert_id();
            if ($cid > 0) {
                $this->db->query('INSERT IGNORE INTO oc_scan_request_item (request_id, candidate_id) VALUES (?, ?)', [$rid, $cid]);
            }
        }
        $this->db->trans_complete();
        return $this->db->trans_status() ? $rid : 0;
    }

    /**
     * One of this tenant's requests.
     *
     * @param int $tenant_id
     * @param int $id
     * @return object|null
     */
    public function find($tenant_id, $id)
    {
        return $this->db->from('oc_scan_request')
                        ->where('id', (int) $id)
                        ->where('user_id', (int) $tenant_id)
                        ->limit(1)->get()->row() ?: null;
    }

    /**
     * This tenant's recent requests, with progress counts.
     *
     * @param int $tenant_id
     * @param int $limit
     * @return array
     */
    public function recent($tenant_id, $limit = 8)
    {
        return $this->db->select('r.*, COUNT(i.candidate_id) AS items,'
                               . " SUM(c.status = 'verified') AS verified,"
                               . " SUM(c.status IN ('new','probing')) AS pending", false)
                        ->from('oc_scan_request r')
                        ->join('oc_scan_request_item i', 'i.request_id = r.id', 'left')
                        ->join('oc_discovery_candidate c', 'c.id = i.candidate_id', 'left')
                        ->where('r.user_id', (int) $tenant_id)
                        ->group_by('r.id')
                        ->order_by('r.created_at', 'DESC')
                        ->limit((int) $limit)
                        ->get()->result();
    }

    /**
     * The rows of a request's progress page: each URL, what discovery made of
     * it, and — once there is a business — its scan state and Chair Score inputs.
     *
     * @param int $request_id  (already tenant-checked by find())
     * @return array
     */
    public function items($request_id)
    {
        return $this->db->select('c.id AS candidate_id, c.website, c.status AS cand_status, c.detected_platform_key,'
                               . ' c.last_error, c.business_id, b.name, b.latest_vacancy_bp, b.latest_confidence,'
                               . ' b.latest_days_to_next, b.last_good_scan_at, b.status AS biz_status,'
                               . ' q.status AS queue_status, q.due_at, q.last_error AS queue_error, p.name AS platform_name', false)
                        ->from('oc_scan_request_item i')
                        ->join('oc_discovery_candidate c', 'c.id = i.candidate_id')
                        ->join('oc_business b', 'b.id = c.business_id', 'left')
                        ->join('oc_platform p', 'p.id = b.platform_id', 'left')
                        ->join('oc_scan_queue q', 'q.business_id = b.id', 'left')
                        ->where('i.request_id', (int) $request_id)
                        ->order_by('c.id', 'ASC')
                        ->get()->result();
    }

    /**
     * Niches the scan form offers.
     *
     * @return array niche_key => label
     */
    public function niches()
    {
        $out = [];
        foreach ($this->db->select('niche_key, label')->from('oc_niche')->order_by('label', 'ASC')->get()->result() as $r) {
            $out[$r->niche_key] = $r->label;
        }
        return $out;
    }
}
