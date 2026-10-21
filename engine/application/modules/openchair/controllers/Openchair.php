<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * OpenChair — the authenticated product screens.
 *
 * Auth and tenancy are inherited from App_Controller: the parent constructor
 * redirects to /login when not authenticated and sets $this->user_id,
 * $this->user and $this->data['ic_brand'].
 *
 * Phase 2 scope: every screen renders from the seeded demo market so the product
 * is walkable before Radar exists. The models already read the real oc_ tables,
 * so Phase 3 replaces the DATA, not this code.
 *
 * What is deliberately absent, and must stay absent: any method that contacts a
 * business. 05-prd.md §8 rule 7 — the product drafts, the consultant sends. There
 * is no send action here and no path from Pitch into a queue.
 */
class Openchair extends App_Controller
{
    /** Default market until saved markets land (Phase 5). */
    const DEMO_CITY  = 'us-fl-tampa';
    const DEMO_NICHE = 'barbershop';

    /**
     * The tenant every per-agency row is keyed by (oc_012_team_tenancy.sql): the
     * agency owner for a team member, the user themself otherwise. $this->user_id
     * stays the ACTOR — who made a link, who generated a pitch.
     *
     * @var int
     */
    protected $tenant_id;

    public function __construct()
    {
        parent::__construct();
        $parent = (int) ($this->user->parent_user_id ?? 0);
        $this->tenant_id = ($parent > 0 && $parent !== (int) $this->user_id) ? $parent : (int) $this->user_id;
        $this->data['oc_tenant_id'] = $this->tenant_id;
        $this->load->library('oc_render_lib');
        // R7: may this account use OpenChair at all? Refused by URL, not only hidden.
        $this->_gate('core');
        $this->load->model('openchair/oc_business_model');
        $this->load->model('openchair/oc_prospect_model');
        $this->load->model('openchair/oc_market_model');
        $this->load->model('openchair/oc_pitch_model');
        $this->load->model('openchair/oc_alert_model');
        $this->load->model('openchair/oc_ledger_model');
        $this->load->model('openchair/oc_share_model');
        $this->load->library('openchair/oc_pitch_lib');
        $this->load->library('openchair/oc_visuals_lib');
        $this->load->helper('openchair/oc_format');
        $this->load->library('oc_render_lib');
    }

    // ── Screens ──────────────────────────────────────────────────────────────

    /** S1 — Today. The habit screen: what changed, split by bucket. */
    public function today()
    {
        $city  = $this->_city();
        $niche = $this->_niche();

        $this->data['totals']  = $this->oc_business_model->market_totals($city, $niche);
        $this->data['counts']  = $this->oc_business_model->bucket_counts($city, $niche);
        $this->data['hungry']  = $this->oc_business_model->market($city, $niche, ['bucket' => 'hungry',  'limit' => 8]);
        $this->data['slammed'] = $this->oc_business_model->market($city, $niche, ['bucket' => 'slammed', 'sort' => 'next', 'limit' => 8]);

        // The 8am list, as sent. Null until a digest has been built.
        $digest = $this->oc_alert_model->latest($this->tenant_id);
        $this->data['digest'] = $digest['run'];
        $this->data['digest_items'] = $digest['items'];

        $this->_render('pages/today', 'today', 'Home');
    }

    /**
     * S4 — Radar / search results. The topbar search lands here.
     *
     * "barbershop, Tampa, FL" sets the active market (kept in the session, so
     * Today, Business and the rail follow it) and lists it as cards: the shops
     * OpenChair tracks, with their Chair Score, then the ones Google lists there
     * that are not tracked yet. A query that is not "niche, city" is treated as
     * a shop name.
     */
    public function radar()
    {
        $q = trim((string) $this->input->get('q', true));
        $search = null;
        if ($q !== '') {
            $search = $this->_resolve_query($q);
            if ($search['city_key'] && $search['niche_key']) {
                $this->session->set_userdata('oc_market', [
                    'city_key' => $search['city_key'], 'city_label' => $search['city_label'],
                    'niche_key' => $search['niche_key'],
                ]);
            }
        }
        $name_mode = $search && !($search['city_key'] && $search['niche_key']);

        $city  = $this->_city();
        $niche = $this->_niche();

        $bucket = $this->input->get('bucket', true);
        $sort   = $this->input->get('sort', true);
        $opts   = ['sort' => $sort ?: 'vacancy'];
        if ($bucket === 'slammed') { $this->_gate('slammed'); }
        if ($bucket && in_array($bucket, ['hungry', 'neutral', 'slammed'], true)) {
            $opts['bucket'] = $bucket;
        }

        // Google: look the market up (or reuse last week's lookup) and hand the
        // new candidates to the service's discovery job. Only on an explicit
        // search or refresh — paging the filters must not bill the key again.
        $this->load->library('openchair/oc_places_lib');
        $places = ['status' => 'skipped', 'added' => 0, 'error' => ''];
        $refresh = $this->input->get('refresh') === '1';
        if (!$name_mode && ($q !== '' || $refresh)) {
            $places = $this->oc_places_lib->discover(
                $this->tenant_id, $city, $this->_market_city_label($city),
                $niche, $this->_niche_label($niche), $refresh
            );
            if ($places['status'] === 'fetched') { $this->_nudge_service('/v1/discovery/run'); }
        }
        $has_key = $this->oc_places_lib->key_for($this->tenant_id) !== '';

        if ($name_mode) {
            $businesses = $this->oc_business_model->search_by_name($search['text'], $search['city_key']);
            $google     = $this->oc_business_model->google_candidates($search['city_key'], null, $search['text']);
            $counts     = ['hungry' => 0, 'neutral' => 0, 'slammed' => 0];
            foreach ($businesses as $b) {
                $band = oc_chair_band_for($b);
                $k = $band === 'steady' ? 'neutral' : $band;
                if (isset($counts[$k])) { $counts[$k]++; }
            }
        } else {
            $businesses = $this->oc_business_model->market($city, $niche, $opts);
            $google     = $this->oc_business_model->google_candidates($city, $niche);
            $counts     = $this->oc_business_model->bucket_counts($city, $niche);
        }

        $this->data['q']          = $q;
        $this->data['search']     = $search;
        $this->data['name_mode']  = $name_mode;
        $this->data['businesses'] = $businesses;
        $this->data['google']     = $google;
        $this->data['places']     = $places;
        $this->data['has_key']    = $has_key;
        $this->data['counts']     = $counts;
        $this->data['totals']     = $this->oc_business_model->market_totals($city, $niche);
        $this->data['market_label'] = $this->_niche_label($niche) . ' · ' . $this->_market_city_label($city);
        $this->data['f_bucket']   = $bucket;
        $this->data['f_sort']     = $opts['sort'];
        // One query for the whole list rather than one per row.
        $this->data['watched_map'] = $this->oc_prospect_model->watched_map(
            $this->tenant_id,
            array_map(function ($b) { return (int) $b->id; }, $businesses)
        );

        $this->_render('pages/radar', 'radar', 'Find businesses');
    }

    /**
     * S5 — Business Detail (PDF p.8): the screen that makes the signature cards.
     * Bare /business falls back to the first business so the nav is never dead.
     *
     * Everything figure-shaped comes from Oc_visuals_lib::summary() — the same
     * payload the cards and share links are built from — so the tiles here and
     * the picture a consultant sends cannot disagree.
     */
    public function business($id = null)
    {
        $b = $id ? $this->oc_business_model->find($id)
                 : $this->oc_business_model->first_in_market($this->_city(), $this->_niche());

        if (!$b) { show_404(); return; }

        $prospect = $this->oc_prospect_model->find($this->tenant_id, $b->id);
        $watched  = $prospect && !in_array($prospect->stage, ['archived'], true);

        $this->data['b']        = $b;
        $this->data['prospect'] = $prospect;
        $this->data['watched']  = $watched;
        $this->data['summary']  = $this->oc_visuals_lib->summary($b);
        $this->data['shares']   = $this->oc_share_model->for_business($this->tenant_id, $b->id, 5);
        $this->data['is_demo']  = $this->oc_business_model->is_demo_market($b->city_key);
        $this->data['team']     = $this->_team();
        $this->data['assignee'] = $this->_user_label(
            ($prospect && $prospect->assigned_user_id) ? (int) $prospect->assigned_user_id : (int) $this->user_id
        );

        // A link just made without JavaScript comes back as ?shared=<token>.
        $shared = $this->input->get('shared', true);
        $this->data['shared_link'] = $shared ? $this->oc_share_model->find_own($this->tenant_id, $shared) : null;
        $this->data['share_error'] = $this->input->get('share_error', true);

        $this->_render('pages/business', $watched ? 'prospects' : 'radar', $b->name);
    }

    /**
     * Make a share link for one card (PDF p.8 "Copy share link").
     *
     * Freezes the card payload — the picture is fixed at the moment the link is
     * made. Refused below the confidence floor (rule 3): a card with no number
     * on it is not something to send. This is a SAVE, not a send: the consultant
     * pastes the link; nothing here contacts the business (rule 7).
     */
    public function share()
    {
        $id   = (int) $this->input->post('business_id', true);
        $kind = (string) $this->input->post('kind', true);
        $logo = (string) $this->input->post('show_logo', true) === '1';
        $back = 'openchair/business/' . $id;

        $b = $this->oc_business_model->find($id);
        if (!$b || !in_array($kind, Oc_visuals_lib::KINDS, true)) { show_error('Nothing to share.', 400); return; }

        $card = $this->oc_visuals_lib->card($b, $kind, $this->_card_ctx($b, $logo));
        if (!empty($card['gap'])) {
            $msg = 'Not enough data yet to put a number on a card for this business.';
            if ($this->input->is_ajax_request()) {
                $this->output->set_content_type('application/json')->set_output(json_encode(['ok' => false, 'error' => $msg]));
                return;
            }
            redirect($back . '?share_error=gap#share');
            return;
        }

        $token = $this->oc_share_model->create($this->tenant_id, $this->user_id, $b, $kind, $logo, $card);
        if (!$token) { show_error('Could not make the link.', 500); return; }

        if ($this->input->is_ajax_request()) {
            $this->output->set_content_type('application/json')
                         ->set_output(json_encode(['ok' => true, 'token' => $token, 'url' => site_url('c/' . $token)]));
            return;
        }
        redirect($back . '?shared=' . $token . '#share');
    }

    /**
     * Owner and due date for a prospect ("Assigned to Maya R." / "due Mon, Oct
     * 20"). Assignees are limited to this account's own team.
     */
    public function assign()
    {
        $id  = (int) $this->input->post('business_id', true);
        $uid = (int) $this->input->post('assigned_user_id', true);
        $due = (string) $this->input->post('next_due_on', true);

        $team = array_map('intval', array_keys($this->_team()));
        $set  = [
            'assigned_user_id' => in_array($uid, $team, true) ? $uid : null,
            'next_due_on'      => preg_match('/^\d{4}-\d{2}-\d{2}$/', $due) && strtotime($due) ? $due : null,
        ];
        $this->db->where('user_id', (int) $this->tenant_id)
                 ->where('business_id', $id)
                 ->update('oc_prospect', $set);

        $this->_after_action(['ok' => true] + $set);
    }

    /**
     * This agency's team: the owner (the tenant) and every sub-user under them.
     * The only people a prospect may be assigned to — anyone else could not see it.
     *
     * @return array id => label
     */
    private function _team()
    {
        $owner = (int) $this->tenant_id;
        $this->db->select('id, first_name, last_name, email')->from('users')
                 ->group_start()->where('id', $owner)->or_where('parent_user_id', $owner);
        $rows = $this->db->group_end()->where('active', 1)->order_by('first_name', 'ASC')->get()->result();

        $out = [];
        foreach ($rows as $r) { $out[(int) $r->id] = $this->_name_of($r); }
        return $out;
    }

    /**
     * "Maya R." for a user id; "you" when the row is gone.
     *
     * @param int $id
     * @return string
     */
    private function _user_label($id)
    {
        $r = $this->db->select('first_name, last_name, email')->from('users')->where('id', (int) $id)->limit(1)->get()->row();
        return $r ? $this->_name_of($r) : 'you';
    }

    /** @return string first name + last initial, else the email's local part */
    private function _name_of($r)
    {
        $first = trim((string) $r->first_name);
        $last  = trim((string) $r->last_name);
        if ($first !== '') { return $first . ($last !== '' ? ' ' . mb_substr($last, 0, 1) . '.' : ''); }
        return strstr((string) $r->email, '@', true) ?: 'you';
    }

    /**
     * S6 — Pitch Composer. Hungry and Slammed variants of the same scan.
     *
     * There is no send action, by design. The composer produces an artifact the
     * consultant carries; the product never contacts the business.
     */
    public function pitch($id = null)
    {
        $b = $id ? $this->oc_business_model->find($id)
                 : $this->oc_business_model->first_in_market($this->_city(), $this->_niche());

        if (!$b) { show_404(); return; }

        // The variant follows the bucket unless the user flips it — the flip is
        // the demo's punch line, so it must be reachable on any business.
        $variant = $this->input->get('variant', true);
        if (!in_array($variant, ['hungry', 'slammed'], true)) {
            $variant = ($b->latest_bucket === 'slammed') ? 'slammed' : 'hungry';
        }
        if ($variant === 'slammed') { $this->_gate('slammed'); }

        $this->data['b']        = $b;
        $this->data['variant']  = $variant;
        $this->data['copy']     = $this->oc_pitch_lib->build($b, $variant);
        $this->data['previous'] = $this->oc_pitch_model->for_business($this->tenant_id, $b->id);
        $this->_render('pages/pitch', 'pitch', 'Pitch — ' . $b->name);
    }

    /** S7 — Prospects / watchlist and the pipeline. */
    public function prospects()
    {
        $stage = $this->input->get('stage', true);
        $opts  = [];
        if ($stage && $stage !== 'all') { $opts['stage'] = $stage; }

        $this->data['watched'] = $this->oc_prospect_model->watchlist($this->tenant_id, $opts);
        $this->data['counts']  = $this->oc_prospect_model->stage_counts($this->tenant_id);
        $this->data['f_stage'] = $stage ?: 'all';

        $this->_render('pages/prospects', 'prospects', 'My pipeline');
    }

    // ── Watchlist actions ────────────────────────────────────────────────────

    /**
     * Add a business to this tenant's watchlist.
     *
     * Watching does not queue another crawl. It increments watcher_count on the
     * ONE shared oc_scan_queue row, raising that business's priority — see
     * Oc_prospect_model::_sync_watchers(). Fifty consultants watching the same
     * barbershop is still one read a night.
     */
    public function watch()
    {
        $id = (int) $this->input->post('business_id', true);
        if (!$id) { show_error('No business.', 400); return; }

        $added = $this->oc_prospect_model->watch($this->tenant_id, $id);
        $this->_after_action(['ok' => true, 'watching' => true, 'added' => $added]);
    }

    /** Remove from the watchlist. */
    public function unwatch()
    {
        $id = (int) $this->input->post('business_id', true);
        if (!$id) { show_error('No business.', 400); return; }

        $this->oc_prospect_model->unwatch($this->tenant_id, $id);
        $this->_after_action(['ok' => true, 'watching' => false]);
    }

    /** Move a prospect along the pipeline. */
    public function stage()
    {
        $id = (int) $this->input->post('business_id', true);
        $to = (string) $this->input->post('stage', true);

        $ok = $this->oc_prospect_model->set_stage($this->tenant_id, $id, $to);
        $this->_after_action(['ok' => $ok, 'stage' => $to]);
    }

    /** Save per-prospect notes. */
    public function notes()
    {
        $id = (int) $this->input->post('business_id', true);
        $this->oc_prospect_model->set_notes($this->tenant_id, $id, (string) $this->input->post('notes'));
        $this->_after_action(['ok' => true]);
    }

    /**
     * Answer an action: JSON to fetch, a redirect back to a normal form post.
     * Keeps every action usable without JavaScript, which matters on the phone
     * view a consultant uses standing outside a shop (FR-5.4).
     *
     * @param array $payload
     * @return void
     */
    private function _after_action(array $payload)
    {
        if ($this->input->is_ajax_request()) {
            $this->output->set_content_type('application/json')
                         ->set_output(json_encode($payload));
            return;
        }
        redirect($this->_safe_back($this->input->post('back', true), 'openchair/radar'));
    }

    /**
     * Generate and store a pitch, freezing its evidence.
     *
     * This is a SAVE, not a send. It writes an artifact the consultant carries;
     * nothing leaves the building. See Oc_pitch_model's class note.
     */
    public function make_pitch()
    {
        $id      = (int) $this->input->post('business_id', true);
        $variant = (string) $this->input->post('variant', true);
        if (!in_array($variant, ['hungry', 'slammed'], true)) { $variant = 'hungry'; }

        $b = $this->oc_business_model->find($id);
        if (!$b) { show_404(); return; }

        $copy = $this->oc_pitch_lib->build($b, $variant);
        $grid = $this->_grid_for($b);

        $pitch_id = $this->oc_pitch_model->create($this->tenant_id, $b, $variant, 'onepager', $copy, $grid, $this->user_id);
        redirect('openchair/onepager/' . $pitch_id);
    }

    /**
     * S6b — the one-pager. The handable artifact.
     *
     * Renders from the FROZEN copy on the pitch row, never from live data: the
     * paper a consultant is holding must not disagree with what they printed.
     */
    public function onepager($id = null)
    {
        $pitch = $this->oc_pitch_model->find($this->tenant_id, (int) $id);
        if (!$pitch) { show_404(); return; }

        $this->data['pitch'] = $pitch;
        // Own chrome: this is print output, not an app screen. It mounts no rail
        // and no topbar, so it prints as a page rather than as a screenshot of
        // a dashboard.
        $this->load->view('pages/onepager', $this->data);
    }

    /**
     * A signature-visual card on its own page (docs/60-signature-visuals.md §3).
     *
     * /openchair/card/{id}/{kind} — kind: week | map | receipt | tuesday | progress.
     * ?size=phone renders the 390px phone variant; ?print=1 opens the print
     * dialog (the "Download one-pager (PDF)" action); ?format=json returns the
     * payload, which is what a share link freezes.
     *
     * Read-only. Producing a card contacts nobody (rule 7).
     */
    public function card($id = null, $kind = 'week')
    {
        $b = $id ? $this->oc_business_model->find($id) : null;
        if (!$b) { show_404(); return; }
        if (!in_array($kind, Oc_visuals_lib::KINDS, true)) { show_404(); return; }
        if ($kind === 'progress') { $this->_gate('ledger'); }

        $card = $this->oc_visuals_lib->card($b, $kind, $this->_card_ctx($b, $this->input->get('logo') !== '0'));

        if ($this->input->get('format') === 'json') {
            $this->output->set_content_type('application/json')
                         ->set_output(json_encode($card, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
            return;
        }

        $urls = [];
        foreach (Oc_visuals_lib::KINDS as $k) { $urls[$k] = site_url('openchair/card/' . (int) $b->id . '/' . $k); }

        $this->load->view('visuals/card_page', [
            'card'      => $card,
            'card_mode' => 'app',
            'size'      => $this->input->get('size') === 'phone' ? 'phone' : 'print',
            'card_urls' => $urls,
            'back_url'  => site_url('openchair/business/' . (int) $b->id),
        ]);
    }

    /** S-how — How the Chair Score works (PDF p.6). */
    public function how()
    {
        $this->_render('pages/how_it_works', 'how', 'How it works');
    }

    /**
     * Context every card needs from the tenant layer: the agency brand that goes
     * in "Prepared by", the sample-data flag, and the campaign start date the
     * 8-week report annotates. The business itself stays global.
     *
     * @param object $b
     * @param bool   $show_logo
     * @return array
     */
    private function _card_ctx($b, $show_logo = true)
    {
        $prospect = $this->oc_prospect_model->find($this->tenant_id, $b->id);
        return [
            'brand'          => isset($this->data['ic_brand']) ? $this->data['ic_brand'] : [],
            'show_logo'      => $show_logo,
            'is_demo'        => $this->oc_business_model->is_demo_market($b->city_key),
            'campaign_start' => ($prospect && !empty($prospect->last_pitched_at))
                ? substr($prospect->last_pitched_at, 0, 10) : null,
        ];
    }

    /**
     * Build today's 8am digest for this tenant on demand.
     *
     * In production a scheduled job calls this per tenant at their local 08:00.
     * Exposed here so Today can build one when none exists yet, and so the
     * behaviour is inspectable without waiting for a cron.
     */
    public function build_digest()
    {
        $this->_gate('alerts');
        $market = $this->oc_market_model->current($this->tenant_id);
        if (!$market) {
            $res = $this->oc_market_model->save($this->tenant_id, [
                'city_key' => $this->_city(), 'niche_key' => $this->_niche(),
                'city' => 'Tampa', 'region_code' => 'FL', 'country_code' => 'US',
                'buckets' => 'hungry,slammed',
            ]);
            $market = $res['ok'] ? $this->oc_market_model->find($this->tenant_id, $res['id']) : null;
        }
        if ($market) {
            $this->oc_alert_model->build($this->tenant_id, $market, date('Y-m-d'), 'in_app');
        }
        redirect('openchair');
    }

    /**
     * The availability grid to freeze into a pitch.
     *
     * Derived until Radar runs (Phase 3 produces the real snapshot). Marked
     * `derived` in the payload so a stored artifact can never be mistaken later
     * for an observed one.
     *
     * @param object $b
     * @return array
     */
    private function _grid_for($b)
    {
        return [
            'source'       => 'derived',
            'observed_on'  => $b->latest_observed_on,
            'vacancy_bp'   => (int) $b->latest_vacancy_bp,
            'open_slots'   => (int) $b->latest_open_slots,
            'bookable'     => (int) $b->latest_bookable_slots,
            'staff_count'  => (int) $b->staff_count,
            'slot_minutes' => (int) ($b->slot_minutes ?: 30),
        ];
    }

    /**
     * S8 — Ledger. One component, two jobs: the prospecting artifact and the
     * retainer report are the same screen.
     */
    public function ledger($id = null)
    {
        $this->_gate('ledger');
        // Default to a watched business — the Ledger is about businesses you
        // have a relationship with, not the whole market.
        if (!$id) {
            $watched = $this->oc_prospect_model->watchlist($this->tenant_id, ['limit' => 1]);
            $id = $watched ? (int) $watched[0]->business_id : null;
        }
        $b = $id ? $this->oc_business_model->find($id)
                 : $this->oc_business_model->first_in_market($this->_city(), $this->_niche());
        if (!$b) { show_404(); return; }

        $weeks  = $this->oc_ledger_model->trend($b->id, 8);
        $cohort = $this->oc_ledger_model->cohort($b->city_key, $b->niche_key, 8);

        $this->data['b']       = $b;
        $this->data['weeks']   = $weeks;
        $this->data['cohort']  = $cohort;
        $this->data['zones']   = $this->oc_ledger_model->dead_zones($b->id);
        $this->data['caveat']  = $this->oc_ledger_model->caveat();
        // Campaign start is set when a prospect becomes a client. Until then the
        // report is the prospecting view and there is no lift to claim.
        $prospect = $this->oc_prospect_model->find($this->tenant_id, $b->id);
        $this->data['lift'] = ($prospect && $prospect->stage === 'won' && $prospect->last_pitched_at)
            ? $this->oc_ledger_model->lift($weeks, $cohort, substr($prospect->last_pitched_at, 0, 10))
            : null;

        $this->data['watchlist'] = $this->oc_prospect_model->watchlist($this->tenant_id, ['limit' => 30]);
        $this->_render('pages/ledger', 'ledger', 'Client report — ' . $b->name);
    }

    /** S9 — Coverage. What we can and cannot see, stated honestly. */
    public function coverage()
    {
        $city = $this->_city();
        $this->data['platforms'] = $this->oc_business_model->coverage($city);
        $this->data['funnel']    = $this->oc_business_model->discovery_funnel($city, $this->_niche());
        $this->data['totals']    = $this->oc_business_model->market_totals($city, $this->_niche());

        $this->_render('pages/coverage', 'coverage', 'What we can see');
    }

    /** S10 — Adapters. Per-platform health. Admin only. */
    public function adapters()
    {
        if (empty($this->data['user_row']) || !$this->_is_admin()) {
            show_error('That area is for administrators only.', 403);
            return;
        }
        $this->data['platforms'] = $this->oc_business_model->coverage($this->_city());
        $this->_render('pages/adapters', 'adapters', 'Adapters');
    }

    /**
     * S2 — New scan: pick a niche and a city, paste public booking links.
     *
     * Radar reads only what is pasted here (and, later, what a Places import
     * finds). Nothing is fetched on submit: the service's discovery job
     * recognises each platform from the URL's shape and queues the business
     * for the nightly read. docs/70-radar-build.md R1.
     */
    public function scan()
    {
        $this->load->model('openchair/oc_scan_model');
        $this->data['q']       = (string) $this->input->get('q', true);
        $this->data['niches']  = $this->oc_scan_model->niches();
        $this->data['recent']  = $this->oc_scan_model->recent($this->tenant_id);
        $this->data['errors']  = $this->session->flashdata('oc_scan_errors') ?: [];
        $this->data['old']     = $this->session->flashdata('oc_scan_old') ?: [];
        $this->_render('pages/scan', 'radar', 'New scan');
    }

    /** POST — record the request and nudge discovery. */
    public function scan_submit()
    {
        $this->load->model('openchair/oc_scan_model');
        $city  = $this->oc_scan_model->parse_city($this->input->post('city', true));
        $niche = (string) $this->input->post('niche', true);
        $paste = $this->oc_scan_model->clean_urls((string) $this->input->post('urls', true));

        $errors = [];
        if (!$city) { $errors[] = 'Write the city as "City, ST" — for example "Tampa, FL".'; }
        if (!array_key_exists($niche, $this->oc_scan_model->niches())) { $errors[] = 'Pick a niche.'; }
        if (!$paste['ok']) { $errors[] = 'Paste at least one public booking link (https://…).'; }
        foreach ($paste['bad'] as $b) { $errors[] = $b['line'] . ' — ' . $b['why']; }

        // Abuse ceiling: a tenant gets 20 scan requests an hour. A real market is a
        // handful; a loop is not a consultant.
        $recent = (int) $this->db->where('user_id', (int) $this->tenant_id)
                                 ->where('created_at >', date('Y-m-d H:i:s', time() - 3600))
                                 ->count_all_results('oc_scan_request');
        if ($recent >= 20) { $errors = ['You have started 20 scans in the last hour. Try again shortly.']; }

        if ($errors && (!$paste['ok'] || !$city || $recent >= 20 || !array_key_exists($niche, $this->oc_scan_model->niches()))) {
            $this->session->set_flashdata('oc_scan_errors', array_slice($errors, 0, 12));
            $this->session->set_flashdata('oc_scan_old', [
                'city' => (string) $this->input->post('city', true), 'niche' => $niche,
                'urls' => mb_substr((string) $this->input->post('urls', true), 0, 30000),
            ]);
            redirect('openchair/scan');
            return;
        }

        $rid = $this->oc_scan_model->create($this->tenant_id, $this->user_id, $city, $niche, $paste['ok']);
        if (!$rid) { show_error('Could not save the scan. Please try again.', 500); return; }
        if ($paste['bad']) { $this->session->set_flashdata('oc_scan_skipped', $paste['bad']); }

        $this->_nudge_service('/v1/discovery/run');
        redirect('openchair/scan/' . $rid);
    }

    /** S3 — a scan's progress: each link, what it became, and its score. */
    public function scan_status($id = null)
    {
        $this->load->model('openchair/oc_scan_model');
        $req = $this->oc_scan_model->find($this->tenant_id, (int) $id);
        if (!$req) { show_404(); return; }

        $items = $this->oc_scan_model->items($req->id);
        if ($this->input->get('format') === 'json') {
            $out = [];
            foreach ($items as $it) {
                $out[] = [
                    'candidate'   => (int) $it->candidate_id,
                    'status'      => $it->cand_status,
                    'platform'    => $it->detected_platform_key,
                    'business_id' => $it->business_id ? (int) $it->business_id : null,
                    'queue'       => $it->queue_status,
                    'score'       => $it->business_id ? oc_chair_score_or_gap($it->latest_vacancy_bp, $it->latest_confidence) : null,
                ];
            }
            $this->output->set_content_type('application/json')->set_output(json_encode(['ok' => true, 'items' => $out]));
            return;
        }

        $ids = array_values(array_filter(array_map(function ($i) { return (int) $i->business_id; }, $items)));
        $this->data['req']         = $req;
        $this->data['items']       = $items;
        $this->data['skipped']     = $this->session->flashdata('oc_scan_skipped') ?: [];
        $this->data['watched_map'] = $this->oc_prospect_model->watched_map($this->tenant_id, $ids);
        $this->_render('pages/scan_status', 'radar', 'Scan — ' . $req->city_label);
    }

    /**
     * GET openchair/cities?q=tam — city suggestions for the search box and the
     * New scan city field, as {places:[{short:"Tampa, FL", label, city, state, known}]}.
     *
     * Markets we already hold come first (instant, no outside call). The rest is
     * OpenStreetMap's Nominatim, as the reference app does: US settlements only,
     * since every city_key here is us-<st>-<city>. Nominatim's policy is one call
     * a second with an identifying User-Agent, so answers are cached for a week
     * and each session is held to one outside call a second. Suggestions are
     * optional — any failure returns an empty list and typing still works.
     */
    public function cities()
    {
        $this->output->set_content_type('application/json');
        $q = trim(preg_replace('/\s+/', ' ', (string) $this->input->get('q', true)));
        $q = mb_substr($q, 0, 80);
        if (mb_strlen($q) < 2 || !preg_match("/^[\\p{L} .,'-]+$/u", $q)) {
            $this->output->set_output(json_encode(['places' => []]));
            return;
        }

        // "Tampa, F" → match "Tampa" against city names; a 2-letter tail narrows the state.
        $parts  = array_map('trim', explode(',', $q));
        $city_q = strtolower($parts[0]);
        $st_q   = isset($parts[1]) ? strtolower(substr($parts[1], 0, 2)) : '';

        $out = []; $seen = [];
        foreach (array_unique($this->oc_business_model->known_city_keys()) as $key) {
            if (!preg_match('/^us-([a-z]{2})-([a-z0-9_]+)$/', $key, $m)) { continue; }
            $name = ucwords(str_replace('_', ' ', $m[2]));
            if (strpos(strtolower($name), $city_q) !== 0) { continue; }
            if ($st_q !== '' && strpos($m[1], $st_q) !== 0) { continue; }
            $short = $name . ', ' . strtoupper($m[1]);
            $seen[strtolower($short)] = true;
            $out[] = ['short' => $short, 'label' => $short . ' · market you have data for', 'city' => $name,
                      'state' => strtoupper($m[1]), 'known' => true];
        }

        // Big US cities, matched as you type (Nominatim only matches whole words).
        $this->config->load('oc_us_cities', true);
        foreach ((array) $this->config->item('oc_us_cities', 'oc_us_cities') as $row) {
            list($name, $st) = explode('|', $row);
            if (strpos(strtolower($name), $city_q) !== 0) { continue; }
            if ($st_q !== '' && strpos(strtolower($st), $st_q) !== 0) { continue; }
            $short = $name . ', ' . $st;
            if (isset($seen[strtolower($short)])) { continue; }
            $seen[strtolower($short)] = true;
            $out[] = ['short' => $short, 'label' => $short . ', United States', 'city' => $name, 'state' => $st, 'known' => false];
            if (count($out) >= 8) { break; }
        }

        // Only go outside when the offline answers did not fill the list.
        $osm = count($out) >= 8 ? [] : $this->_osm_cities($q);
        foreach ((array) $osm as $p) {
            if ($st_q !== '' && strpos(strtolower($p['state']), $st_q) !== 0) { continue; }
            if (isset($seen[strtolower($p['short'])])) { continue; }
            $seen[strtolower($p['short'])] = true;
            $out[] = $p;
        }

        // null = held back by the one-a-second rule; the browser asks again shortly.
        $this->output->set_output(json_encode(['places' => array_slice($out, 0, 8), 'throttled' => $osm === null]));
    }

    /**
     * Nominatim lookup for US settlements, cached and rate-held.
     *
     * @param string $q
     * @return array|null  null when held back by the rate rule
     */
    private function _osm_cities($q)
    {
        $this->load->driver('cache', ['adapter' => 'file', 'backup' => 'dummy']);
        $key = 'oc_osm_' . md5(strtolower($q));
        $hit = $this->cache->get($key);
        if (is_array($hit)) { return $hit; }

        $now = microtime(true);
        if ($now - (float) $this->session->userdata('oc_osm_at') < 1.0) { return null; }
        $this->session->set_userdata('oc_osm_at', $now);

        if (!function_exists('curl_init')) { return []; }
        $url = 'https://nominatim.openstreetmap.org/search?' . http_build_query([
            'q' => $q, 'format' => 'jsonv2', 'addressdetails' => 1, 'limit' => 6,
            'featuretype' => 'settlement', 'countrycodes' => 'us',
        ]);
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true, CURLOPT_CONNECTTIMEOUT => 4, CURLOPT_TIMEOUT => 6,
            CURLOPT_PROTOCOLS => CURLPROTO_HTTPS, CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_HTTPHEADER => [
                'User-Agent: OpenChair/1.0 (+' . rtrim(base_url(), '/') . ')',
                'Accept: application/json',
            ],
        ]);
        $body = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($body === false || $code !== 200) { return []; }

        // Nominatim is a geocoder, not a prefix search: "tam" also returns peaks,
        // airports and big cities that merely mention it. Keep real settlements
        // whose name starts with what was typed.
        $typed = strtolower(trim(explode(',', $q)[0]));
        $out = [];
        foreach ((array) json_decode((string) $body, true) as $row) {
            if (!in_array((string) ($row['addresstype'] ?? ''), ['city', 'town', 'village', 'hamlet', 'municipality'], true)) { continue; }
            $a    = isset($row['address']) && is_array($row['address']) ? $row['address'] : [];
            $city = (string) ($row['name'] ?? $a['city'] ?? $a['town'] ?? $a['village'] ?? '');
            if ($city === '' || strpos(strtolower($city), $typed) !== 0) { continue; }
            if (!preg_match('/^US-([A-Z]{2})$/', (string) ($a['ISO3166-2-lvl4'] ?? ''), $m)) { continue; }
            // parse_city() accepts letters, spaces, . ' - only; skip names it would refuse.
            if (!preg_match("/^[A-Za-z][A-Za-z .'-]{1,60}$/", $city)) { continue; }
            $out[] = ['short' => $city . ', ' . $m[1], 'label' => (string) ($row['display_name'] ?? $city),
                      'city' => $city, 'state' => $m[1], 'known' => false];
        }
        $this->cache->save($key, $out, 7 * 86400);
        return $out;
    }

    /**
     * Ask openchair-service to run a job now rather than at its next tick.
     *
     * Best effort and short: the scheduled job picks the work up anyway, so a
     * service that is down or slow costs a few minutes, never the request.
     * The internal token is sent when configured (it is required in production).
     *
     * @param string $path
     * @return bool
     */
    private function _nudge_service($path)
    {
        $base = rtrim((string) $this->config->item('oc_service_internal_base'), '/');
        if ($base === '' || !function_exists('curl_init')) { return false; }
        $ch = curl_init($base . $path);
        $headers = ['content-type: application/json'];
        $tok = (string) $this->config->item('oc_internal_token');
        if ($tok !== '') { $headers[] = 'x-internal-token: ' . $tok; }
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => '{}',
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT_MS => 800,
            CURLOPT_TIMEOUT_MS     => 3000,
            CURLOPT_PROTOCOLS      => CURLPROTO_HTTP | CURLPROTO_HTTPS,
            CURLOPT_FOLLOWLOCATION => false,
        ]);
        curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return $code >= 200 && $code < 300;
    }

    // ── Internals ────────────────────────────────────────────────────────────

    /**
     * A "back" target from a form, made safe to redirect to.
     *
     * Only an app-relative path is accepted. CI's redirect() follows an absolute
     * URL anywhere, so passing a posted value straight through was an open
     * redirect: a crafted form could bounce a logged-in consultant to a lookalike
     * login page. Scheme, host, protocol-relative ("//evil") and backslash tricks
     * are all refused in favour of the fallback.
     *
     * @param mixed  $back
     * @param string $fallback
     * @return string
     */
    private function _safe_back($back, $fallback)
    {
        $back = trim((string) $back);
        if ($back === '' || strlen($back) > 300) { return $fallback; }
        $back = ltrim($back, '/');
        if (preg_match('#^[a-z][a-z0-9+.-]*:|^/|\\\\|[\\x00-\\x1f]#i', $back) || strpos($back, '\\') !== false) { return $fallback; }
        if (!preg_match('#^(openchair|app)(/[A-Za-z0-9_\\-/]*)?(\\?[A-Za-z0-9_=&%.\\-]*)?(\\#[A-Za-z0-9_-]*)?$#', $back)) { return $fallback; }
        return $back;
    }

    /**
     * Refuse a request for a tier this account has not bought (R7).
     *
     * Plain 403, no upsell modal in the daily path — the anti-mugging rule
     * (NAV-CONTRACT.md). The rail hides the same items from the same answer.
     *
     * @param string $gate
     * @return void  exits on refusal
     */
    private function _gate($gate)
    {
        if ($this->oc_render_lib->can($gate, $this->user)) { return; }
        $labels = ['core' => 'OpenChair', 'slammed' => 'the Slammed bucket', 'alerts' => 'nightly alerts',
                   'deadzone' => 'the Dead-Zone Map', 'ledger' => 'client reports', 'kit' => 'the campaign kit'];
        show_error('Your plan does not include ' . (isset($labels[$gate]) ? $labels[$gate] : 'this') . '.', 403, 'Not on your plan');
        exit;
    }

    /**
     * Render into the OpenChair shell, with the market context the chrome needs.
     *
     * @param string $view
     * @param string $nav_key   the rail key to light up
     * @param string $title
     * @return void
     */
    private function _render($view, $nav_key, $title)
    {
        $city = $this->_city();

        // The shell's sample-data flag. A seeded number must never be mistakable
        // for an observed one (rule 6 — provenance is always visible).
        $this->data['oc_is_demo'] = $this->oc_business_model->is_demo_market($city);
        $this->data['oc_query']   = strtolower($this->_niche_label($this->_niche())) . ', ' . $this->_market_city_label($city);

        // Rail-foot quota meters. FE entitlements per the brief's ladder; the real
        // two-meter model (pulls vs monitored slots) arrives in Phase 8.
        $this->data['oc_quota'] = [
            ['label' => 'Watched', 'used' => (int) $this->db->where('user_id', (int) $this->tenant_id)
                                                     ->where_not_in('stage', ['lost', 'archived'])
                                                     ->count_all_results('oc_prospect'), 'cap' => 25],
            ['label' => 'Cities',  'used' => 1, 'cap' => 1],
        ];

        $this->oc_render_lib->render($view, $this->data, [
            'page'       => $nav_key,
            'page_title' => $title,
        ]);
    }

    /** @return string the active market's city key — the last search, else the demo market */
    private function _city()
    {
        $m = $this->session->userdata('oc_market');
        return (is_array($m) && !empty($m['city_key'])) ? (string) $m['city_key'] : self::DEMO_CITY;
    }

    /** @return string the active market's niche key */
    private function _niche()
    {
        $m = $this->session->userdata('oc_market');
        return (is_array($m) && !empty($m['niche_key'])) ? (string) $m['niche_key'] : self::DEMO_NICHE;
    }

    /** @return array<string,string> enabled niches, key => label */
    private function _niches()
    {
        static $cache = null;
        if ($cache === null) {
            $cache = [];
            foreach ($this->db->select('niche_key, label')->where('enabled', 1)->order_by('id')->get('oc_niche')->result() as $n) {
                $cache[(string) $n->niche_key] = (string) $n->label;
            }
        }
        return $cache;
    }

    /** 'barbershop' → 'Barbershop' */
    private function _niche_label($niche_key)
    {
        $n = $this->_niches();
        return isset($n[$niche_key]) ? $n[$niche_key] : ucwords(str_replace('_', ' ', (string) $niche_key));
    }

    /** 'us-fl-tampa' → 'Tampa, FL' */
    private function _market_city_label($city_key)
    {
        $m = $this->session->userdata('oc_market');
        if (is_array($m) && isset($m['city_key'], $m['city_label']) && $m['city_key'] === $city_key) {
            return (string) $m['city_label'];
        }
        $parts = explode('-', (string) $city_key);
        if (count($parts) >= 3) {
            return ucwords(str_replace('_', ' ', implode(' ', array_slice($parts, 2)))) . ', ' . strtoupper($parts[1]);
        }
        return $this->_city_label($city_key);
    }

    /**
     * What was typed in the search bar → a market, or a name search.
     *
     * Accepts "barbershop, Tampa, FL", "barbershop, Tampa", "barbers in tampa fl",
     * "Tampa FL dentist". The niche is matched against oc_niche (plus everyday
     * words: "barber", "dentist", "nails"…); the rest is the city. A city with no
     * state resolves only when we already know exactly one city of that name —
     * guessing a state would silently split a market.
     *
     * @param string $q
     * @return array{text:string, niche_key:?string, city_key:?string, city_label:?string, problem:?string}
     */
    private function _resolve_query($q)
    {
        $out = ['text' => $q, 'niche_key' => null, 'city_key' => null, 'city_label' => null, 'problem' => null];
        $norm = strtolower(trim(preg_replace('/\s+/', ' ', $q)));

        $synonyms = [
            'barbershop' => ['barbershop', 'barber shop', 'barbers', 'barber'],
            'hair_salon' => ['hair salon', 'hairdresser', 'hair stylist', 'salon'],
            'nail_salon' => ['nail salon', 'nails', 'nail', 'manicure'],
            'lash_brow'  => ['lash', 'brow', 'eyelash'],
            'med_spa'    => ['med spa', 'medspa', 'medical spa', 'botox'],
            'massage'    => ['massage'],
            'tattoo'     => ['tattoo'],
            'dental'     => ['dental', 'dentist', 'dentists'],
            'chiropractic' => ['chiropractic', 'chiropractor', 'chiropractors'],
            'physio'     => ['physiotherapy', 'physical therapy', 'physio', 'physical therapist'],
            'yoga_studio'    => ['yoga'],
            'pilates_studio' => ['pilates'],
            'gym_pt'     => ['personal trainer', 'personal training', 'gym', 'fitness'],
            'restaurant' => ['restaurant', 'restaurants'],
            'hvac'       => ['hvac', 'air conditioning', 'heating'],
            'plumbing'   => ['plumbing', 'plumber', 'plumbers'],
        ];
        $niches = $this->_niches();
        foreach ($niches as $k => $label) {
            $synonyms[$k] = array_merge([strtolower($label), str_replace('_', ' ', $k)], isset($synonyms[$k]) ? $synonyms[$k] : []);
        }
        // Longest phrase first, so "hair salon" wins over "salon".
        $phrases = [];
        foreach ($synonyms as $k => $list) {
            if (!isset($niches[$k])) { continue; }
            foreach ($list as $w) { $phrases[$w] = $k; }
        }
        uksort($phrases, function ($a, $b) { return strlen($b) - strlen($a); });

        $rest = $norm;
        foreach ($phrases as $w => $k) {
            if (preg_match('/(^|[\s,])' . preg_quote($w, '/') . '(?=$|[\s,])/', $norm)) {
                $out['niche_key'] = $k;
                $rest = preg_replace('/(^|[\s,])' . preg_quote($w, '/') . '(?=$|[\s,])/', ' ', $norm, 1);
                break;
            }
        }

        // What is left is the city: strip filler and punctuation.
        $rest = trim(preg_replace('/\b(in|near|around)\b/', ' ', $rest));
        $rest = trim(preg_replace('/\s*,\s*/', ', ', $rest), " ,");
        $rest = preg_replace('/\s+/', ' ', $rest);
        if ($rest === '') {
            if ($out['niche_key']) { $out['problem'] = 'Add a city, for example "' . $niches[$out['niche_key']] . ', Tampa, FL".'; }
            return $out;
        }

        $city = null; $region = null;
        if (preg_match('/^([a-z][a-z .\'-]{1,60}?)[ ,]+([a-z]{2})$/', $rest, $m)) {
            $city = trim($m[1], " ,"); $region = $m[2];
        } elseif (preg_match('/^[a-z][a-z .\'-]{1,60}$/', $rest)) {
            $city = trim($rest);
        }
        if ($city === null) { return $out; }
        $slug = trim(preg_replace('/[^a-z0-9]+/', '_', $city), '_');

        if ($region === null) {
            $hits = array_values(array_unique(array_filter($this->oc_business_model->known_city_keys(), function ($k) use ($slug) {
                return (bool) preg_match('/^[a-z]{2}-[a-z]{2,3}-' . preg_quote($slug, '/') . '$/', $k);
            })));
            if (count($hits) === 1) {
                $region = explode('-', $hits[0])[1];
            } elseif (!$out['niche_key']) {
                return $out;   // a single word that is not a known city — search it as a name
            } else {
                $out['problem'] = 'Which ' . ucwords($city) . '? Add the state, for example "' . ucwords($city) . ', FL".';
                return $out;
            }
        }

        $out['city_key']   = 'us-' . strtolower($region) . '-' . $slug;
        $out['city_label'] = ucwords($city) . ', ' . strtoupper($region);
        // A city typed with no niche keeps the current niche, so "Orlando, FL"
        // means "this niche, in Orlando".
        if (!$out['niche_key']) { $out['niche_key'] = $this->_niche(); }
        return $out;
    }

    /**
     * 'us-fl-tampa' → 'Tampa'.
     *
     * @param string $city_key
     * @return string
     */
    private function _city_label($city_key)
    {
        $parts = explode('-', $city_key);
        return ucwords(str_replace('_', ' ', end($parts)));
    }

    /**
     * Admin gate — superadmin (user 1) or JV Admin (access code '51'), matching
     * the host engine. The Ion-Auth 'admin' group is deliberately not consulted.
     *
     * @return bool
     */
    private function _is_admin()
    {
        if ((int) $this->user_id === 1) { return true; }
        $access = array_map('trim', explode(',', (string) ($this->user->access ?? '')));
        return in_array('51', $access, true);
    }
}
