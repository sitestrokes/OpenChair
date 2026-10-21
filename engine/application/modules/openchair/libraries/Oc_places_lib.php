<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Oc_places_lib — the search bar's Google Places lookup.
 *
 * "barbershop, Tampa, FL" → a Places API (New) Text Search → one GLOBAL
 * oc_discovery_candidate row per place (source 'places', source_ref = the place
 * id), exactly the shape the service's discovery job already reads: a website
 * on a known booking platform becomes a tracked business and joins the nightly
 * read; anything else is parked as not_bookable. Nothing here touches a booking
 * platform — Places is Google's licensed API, not a scrape (20-architecture §4.2).
 *
 * Cost discipline (§4.5): a market looked up in the last CACHE_DAYS is answered
 * from the candidates already stored. The key is the tenant's own (Settings →
 * Google Places), so a repeat search must not bill them twice.
 */
class Oc_places_lib
{
    const ENDPOINT   = 'https://places.googleapis.com/v1/places:searchText';
    const CACHE_DAYS = 7;
    const PAGE_SIZE  = 20;

    /** Only the fields the cards print — the field mask is what Google bills on. */
    const FIELDS = 'places.id,places.displayName,places.formattedAddress,places.location,places.rating,'
                 . 'places.userRatingCount,places.websiteUri,places.nationalPhoneNumber,places.googleMapsUri,'
                 . 'places.primaryType,places.businessStatus';

    /** @var CI_Controller */
    protected $CI;

    public function __construct()
    {
        $this->CI =& get_instance();
    }

    /**
     * The Places key saved under Settings for this user (encrypted at rest),
     * or '' when none is saved.
     *
     * @param int $user_id
     * @return string
     */
    public function key_for($user_id)
    {
        $row = $this->CI->db->where('user_id', (int) $user_id)->where('meta_type', 'places')
                            ->get('settings')->row();
        if (!$row || (string) $row->meta_data === '') { return ''; }
        $this->CI->load->library('encryption');
        $dec  = @$this->CI->encryption->decrypt($row->meta_data);
        $data = ($dec !== false) ? @unserialize($dec) : false;
        if (!is_array($data)) { $data = @unserialize($row->meta_data); } // legacy plaintext row
        return (is_array($data) && isset($data['key'])) ? trim((string) $data['key']) : '';
    }

    /**
     * One Text Search call.
     *
     * @param string $key
     * @param string $text      "barbershop in Tampa, FL"
     * @param int    $page_size 1..20
     * @return array{ok:bool, places:array, error:string}
     */
    public function text_search($key, $text, $page_size = self::PAGE_SIZE)
    {
        if ($key === '') { return ['ok' => false, 'places' => [], 'error' => 'no Google Places key saved']; }
        if (!function_exists('curl_init')) { return ['ok' => false, 'places' => [], 'error' => 'PHP curl is not available']; }

        $ch = curl_init(self::ENDPOINT);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode([
                'textQuery'    => (string) $text,
                'pageSize'     => max(1, min(self::PAGE_SIZE, (int) $page_size)),
                'languageCode' => 'en',
                'regionCode'   => 'US',
            ]),
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'X-Goog-Api-Key: ' . $key,
                'X-Goog-FieldMask: ' . self::FIELDS,
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_PROTOCOLS      => CURLPROTO_HTTPS,
            CURLOPT_FOLLOWLOCATION => false,
        ]);
        $body = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $cerr = curl_error($ch);
        curl_close($ch);

        if ($body === false) { return ['ok' => false, 'places' => [], 'error' => 'could not reach Google (' . $cerr . ')']; }
        $json = json_decode((string) $body, true);
        if ($code !== 200) {
            $msg = isset($json['error']['message']) ? (string) $json['error']['message'] : 'HTTP ' . $code;
            // Many keys only have the older Places API enabled. Same data, older
            // endpoint — use it rather than fail the search.
            if ($code === 403 && stripos($msg, 'Places API (New)') !== false) {
                $legacy = $this->legacy_text_search($key, $text, $page_size);
                if ($legacy['ok']) { return $legacy; }
                $msg = 'neither Places API (New) nor the older Places API is enabled for this key (' . $legacy['error'] . ')';
            }
            return ['ok' => false, 'places' => [], 'error' => mb_substr($msg, 0, 240)];
        }

        $out = [];
        foreach ((isset($json['places']) && is_array($json['places'])) ? $json['places'] : [] as $p) {
            if (empty($p['id'])) { continue; }
            if (isset($p['businessStatus']) && $p['businessStatus'] === 'CLOSED_PERMANENTLY') { continue; }
            $out[] = [
                'place_id'     => (string) $p['id'],
                'name'         => isset($p['displayName']['text']) ? (string) $p['displayName']['text'] : '',
                'address'      => isset($p['formattedAddress']) ? (string) $p['formattedAddress'] : null,
                'lat'          => isset($p['location']['latitude']) ? (float) $p['location']['latitude'] : null,
                'lng'          => isset($p['location']['longitude']) ? (float) $p['location']['longitude'] : null,
                'rating'       => isset($p['rating']) ? (float) $p['rating'] : null,
                'review_count' => isset($p['userRatingCount']) ? (int) $p['userRatingCount'] : null,
                'website'      => isset($p['websiteUri']) ? (string) $p['websiteUri'] : null,
                'phone'        => isset($p['nationalPhoneNumber']) ? (string) $p['nationalPhoneNumber'] : null,
                'maps_url'     => isset($p['googleMapsUri']) ? (string) $p['googleMapsUri'] : null,
                'primary_type' => isset($p['primaryType']) ? (string) $p['primaryType'] : null,
            ];
        }
        return ['ok' => true, 'places' => $out, 'error' => ''];
    }

    /**
     * The older Places API: Text Search, then Place Details for the website and
     * phone (Text Search does not return them, and the website is what tells
     * discovery whether a shop books online).
     *
     * @param string $key
     * @param string $text
     * @param int    $page_size
     * @return array{ok:bool, places:array, error:string}
     */
    protected function legacy_text_search($key, $text, $page_size)
    {
        $search = $this->get_json('https://maps.googleapis.com/maps/api/place/textsearch/json?'
            . http_build_query(['query' => $text, 'region' => 'us', 'language' => 'en', 'key' => $key]));
        if (!$search['ok']) { return ['ok' => false, 'places' => [], 'error' => $search['error']]; }

        $out = [];
        foreach (array_slice($search['json']['results'] ?? [], 0, max(1, (int) $page_size)) as $r) {
            if (empty($r['place_id'])) { continue; }
            if (($r['business_status'] ?? '') === 'CLOSED_PERMANENTLY') { continue; }
            $d = $this->get_json('https://maps.googleapis.com/maps/api/place/details/json?' . http_build_query([
                'place_id' => $r['place_id'], 'fields' => 'website,formatted_phone_number,url',
                'language' => 'en', 'key' => $key,
            ]));
            $det = $d['ok'] ? ($d['json']['result'] ?? []) : [];
            $out[] = [
                'place_id'     => (string) $r['place_id'],
                'name'         => (string) ($r['name'] ?? ''),
                'address'      => isset($r['formatted_address']) ? (string) $r['formatted_address'] : null,
                'lat'          => isset($r['geometry']['location']['lat']) ? (float) $r['geometry']['location']['lat'] : null,
                'lng'          => isset($r['geometry']['location']['lng']) ? (float) $r['geometry']['location']['lng'] : null,
                'rating'       => isset($r['rating']) ? (float) $r['rating'] : null,
                'review_count' => isset($r['user_ratings_total']) ? (int) $r['user_ratings_total'] : null,
                'website'      => isset($det['website']) ? (string) $det['website'] : null,
                'phone'        => isset($det['formatted_phone_number']) ? (string) $det['formatted_phone_number'] : null,
                'maps_url'     => isset($det['url']) ? (string) $det['url'] : null,
                'primary_type' => isset($r['types'][0]) ? (string) $r['types'][0] : null,
            ];
        }
        return ['ok' => true, 'places' => $out, 'error' => ''];
    }

    /**
     * GET a Google JSON endpoint; the older API reports errors in `status`.
     *
     * @param string $url
     * @return array{ok:bool, json:array, error:string}
     */
    protected function get_json($url)
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true, CURLOPT_CONNECTTIMEOUT => 5, CURLOPT_TIMEOUT => 12,
            CURLOPT_PROTOCOLS => CURLPROTO_HTTPS, CURLOPT_FOLLOWLOCATION => false,
        ]);
        $body = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        $json = is_string($body) ? json_decode($body, true) : null;
        if ($code !== 200 || !is_array($json)) { return ['ok' => false, 'json' => [], 'error' => 'HTTP ' . $code]; }
        $status = (string) ($json['status'] ?? '');
        if ($status !== 'OK' && $status !== 'ZERO_RESULTS') {
            $msg = $status . (isset($json['error_message']) ? ': ' . $json['error_message'] : '');
            return ['ok' => false, 'json' => [], 'error' => mb_substr($msg, 0, 200)];
        }
        return ['ok' => true, 'json' => $json, 'error' => ''];
    }

    /**
     * Look a market up on Google — or reuse the last lookup — and store what came
     * back as discovery candidates.
     *
     * @param int    $user_id     whose Settings key pays for the call
     * @param string $city_key    'us-fl-tampa'
     * @param string $city_label  'Tampa, FL'
     * @param string $niche_key   'barbershop'
     * @param string $niche_label 'Barbershop'
     * @param bool   $force       ignore the cache (the "Refresh from Google" button)
     * @return array{status:string, added:int, error:string}
     *         status: 'fetched' | 'cached' | 'no_key' | 'error'
     */
    public function discover($user_id, $city_key, $city_label, $niche_key, $niche_label, $force = false)
    {
        $db = $this->CI->db;
        if (!$force) {
            $recent = (int) $db->where('source', 'places')->where('city_key', $city_key)->where('niche_key', $niche_key)
                               ->where('updated_at >', date('Y-m-d H:i:s', time() - self::CACHE_DAYS * 86400))
                               ->count_all_results('oc_discovery_candidate');
            if ($recent > 0) { return ['status' => 'cached', 'added' => 0, 'error' => '']; }
        }

        $key = $this->key_for($user_id);
        if ($key === '') { return ['status' => 'no_key', 'added' => 0, 'error' => '']; }

        $res = $this->text_search($key, $niche_label . ' in ' . $city_label);
        if (!$res['ok']) { return ['status' => 'error', 'added' => 0, 'error' => $res['error']]; }

        $added = 0;
        foreach ($res['places'] as $p) {
            if ($p['name'] === '') { continue; }
            // A website that is not https cannot pass discovery's URL rules; keep
            // the place (it is still a real shop) but without the link.
            $site = ($p['website'] && stripos($p['website'], 'https://') === 0 && strlen($p['website']) <= 512) ? $p['website'] : null;
            $db->query(
                "INSERT INTO oc_discovery_candidate
                   (source, source_ref, city_key, niche_key, name, website, phone, address_line, lat, lng,
                    rating, review_count, maps_url, primary_type, status)
                 VALUES ('places', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'new')
                 ON DUPLICATE KEY UPDATE
                   name = VALUES(name), phone = VALUES(phone), address_line = VALUES(address_line),
                   lat = VALUES(lat), lng = VALUES(lng), rating = VALUES(rating),
                   review_count = VALUES(review_count), maps_url = VALUES(maps_url),
                   primary_type = VALUES(primary_type), updated_at = NOW()",
                [$p['place_id'], $city_key, $niche_key, mb_substr($p['name'], 0, 191), $site,
                 $p['phone'] ? mb_substr($p['phone'], 0, 32) : null,
                 $p['address'] ? mb_substr($p['address'], 0, 255) : null,
                 $p['lat'], $p['lng'], $p['rating'], $p['review_count'],
                 $p['maps_url'] ? mb_substr($p['maps_url'], 0, 512) : null,
                 $p['primary_type'] ? mb_substr($p['primary_type'], 0, 64) : null]
            );
            if ($db->affected_rows() === 1) { $added++; }   // 1 = inserted, 2 = updated
        }
        return ['status' => 'fetched', 'added' => $added, 'error' => ''];
    }
}
