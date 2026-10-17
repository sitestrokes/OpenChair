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

    public function __construct()
    {
        parent::__construct();
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
        $digest = $this->oc_alert_model->latest($this->user_id);
        $this->data['digest'] = $digest['run'];
        $this->data['digest_items'] = $digest['items'];

        $this->_render('pages/today', 'today', 'Today');
    }

    /** S4 — Radar. The hero: map + ranked list. */
    public function radar()
    {
        $city  = $this->_city();
        $niche = $this->_niche();

        $bucket = $this->input->get('bucket', true);
        $sort   = $this->input->get('sort', true);
        $opts   = ['sort' => $sort ?: 'vacancy'];
        if ($bucket && in_array($bucket, ['hungry', 'neutral', 'slammed'], true)) {
            $opts['bucket'] = $bucket;
        }

        $this->data['businesses'] = $this->oc_business_model->market($city, $niche, $opts);
        $this->data['counts']     = $this->oc_business_model->bucket_counts($city, $niche);
        $this->data['totals']     = $this->oc_business_model->market_totals($city, $niche);
        $this->data['f_bucket']   = $bucket;
        $this->data['f_sort']     = $opts['sort'];
        // One query for the whole list rather than one per row.
        $this->data['watched_map'] = $this->oc_prospect_model->watched_map(
            $this->user_id,
            array_map(function ($b) { return (int) $b->id; }, $this->data['businesses'])
        );

        $this->_render('pages/radar', 'radar', 'Radar');
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

        $prospect = $this->oc_prospect_model->find($this->user_id, $b->id);
        $watched  = $prospect && !in_array($prospect->stage, ['archived'], true);

        $this->data['b']        = $b;
        $this->data['prospect'] = $prospect;
        $this->data['watched']  = $watched;
        $this->data['summary']  = $this->oc_visuals_lib->summary($b);
        $this->data['shares']   = $this->oc_share_model->for_business($this->user_id, $b->id, 5);
        $this->data['is_demo']  = $this->oc_business_model->is_demo_market($b->city_key);
        $this->data['team']     = $this->_team();
        $this->data['assignee'] = $this->_user_label(
            ($prospect && $prospect->assigned_user_id) ? (int) $prospect->assigned_user_id : (int) $this->user_id
        );

        // A link just made without JavaScript comes back as ?shared=<token>.
        $shared = $this->input->get('shared', true);
        $this->data['shared_link'] = $shared ? $this->oc_share_model->find_own($this->user_id, $shared) : null;
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

        $token = $this->oc_share_model->create($this->user_id, $b, $kind, $logo, $card);
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
        $this->db->where('user_id', (int) $this->user_id)
                 ->where('business_id', $id)
                 ->update('oc_prospect', $set);

        $this->_after_action(['ok' => true] + $set);
    }

    /**
     * This account's team: the user, their sub-users, and their agency parent.
     *
     * @return array id => label
     */
    private function _team()
    {
        $me = (int) $this->user_id;
        $parent = (int) ($this->user->parent_user_id ?? 0);
        $this->db->select('id, first_name, last_name, email')->from('users')
                 ->group_start()->where('id', $me)->or_where('parent_user_id', $me);
        if ($parent > 0) { $this->db->or_where('id', $parent); }
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

        $this->data['b']        = $b;
        $this->data['variant']  = $variant;
        $this->data['copy']     = $this->oc_pitch_lib->build($b, $variant);
        $this->data['previous'] = $this->oc_pitch_model->for_business($this->user_id, $b->id);
        $this->_render('pages/pitch', 'pitch', 'Pitch — ' . $b->name);
    }

    /** S7 — Prospects / watchlist and the pipeline. */
    public function prospects()
    {
        $stage = $this->input->get('stage', true);
        $opts  = [];
        if ($stage && $stage !== 'all') { $opts['stage'] = $stage; }

        $this->data['watched'] = $this->oc_prospect_model->watchlist($this->user_id, $opts);
        $this->data['counts']  = $this->oc_prospect_model->stage_counts($this->user_id);
        $this->data['f_stage'] = $stage ?: 'all';

        $this->_render('pages/prospects', 'prospects', 'Prospects');
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

        $added = $this->oc_prospect_model->watch($this->user_id, $id);
        $this->_after_action(['ok' => true, 'watching' => true, 'added' => $added]);
    }

    /** Remove from the watchlist. */
    public function unwatch()
    {
        $id = (int) $this->input->post('business_id', true);
        if (!$id) { show_error('No business.', 400); return; }

        $this->oc_prospect_model->unwatch($this->user_id, $id);
        $this->_after_action(['ok' => true, 'watching' => false]);
    }

    /** Move a prospect along the pipeline. */
    public function stage()
    {
        $id = (int) $this->input->post('business_id', true);
        $to = (string) $this->input->post('stage', true);

        $ok = $this->oc_prospect_model->set_stage($this->user_id, $id, $to);
        $this->_after_action(['ok' => $ok, 'stage' => $to]);
    }

    /** Save per-prospect notes. */
    public function notes()
    {
        $id = (int) $this->input->post('business_id', true);
        $this->oc_prospect_model->set_notes($this->user_id, $id, (string) $this->input->post('notes'));
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
        redirect($this->input->post('back', true) ?: 'openchair/radar');
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

        $pitch_id = $this->oc_pitch_model->create($this->user_id, $b, $variant, 'onepager', $copy, $grid);
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
        $pitch = $this->oc_pitch_model->find($this->user_id, (int) $id);
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
        $prospect = $this->oc_prospect_model->find($this->user_id, $b->id);
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
        $market = $this->oc_market_model->current($this->user_id);
        if (!$market) {
            $res = $this->oc_market_model->save($this->user_id, [
                'city_key' => $this->_city(), 'niche_key' => $this->_niche(),
                'city' => 'Tampa', 'region_code' => 'FL', 'country_code' => 'US',
                'buckets' => 'hungry,slammed',
            ]);
            $market = $res['ok'] ? $this->oc_market_model->find($this->user_id, $res['id']) : null;
        }
        if ($market) {
            $this->oc_alert_model->build($this->user_id, $market, date('Y-m-d'), 'in_app');
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
        // Default to a watched business — the Ledger is about businesses you
        // have a relationship with, not the whole market.
        if (!$id) {
            $watched = $this->oc_prospect_model->watchlist($this->user_id, ['limit' => 1]);
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
        $prospect = $this->oc_prospect_model->find($this->user_id, $b->id);
        $this->data['lift'] = ($prospect && $prospect->stage === 'won' && $prospect->last_pitched_at)
            ? $this->oc_ledger_model->lift($weeks, $cohort, substr($prospect->last_pitched_at, 0, 10))
            : null;

        $this->data['watchlist'] = $this->oc_prospect_model->watchlist($this->user_id, ['limit' => 30]);
        $this->_render('pages/ledger', 'ledger', 'Ledger — ' . $b->name);
    }

    /** S9 — Coverage. What we can and cannot see, stated honestly. */
    public function coverage()
    {
        $city = $this->_city();
        $this->data['platforms'] = $this->oc_business_model->coverage($city);
        $this->data['funnel']    = $this->oc_business_model->discovery_funnel($city, $this->_niche());
        $this->data['totals']    = $this->oc_business_model->market_totals($city, $this->_niche());

        $this->_render('pages/coverage', 'coverage', 'Coverage');
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
     * S2/S3 — Scan setup and progress.
     *
     * Radar does not exist until Phase 3, so this states that plainly rather than
     * showing a spinner that will never resolve. 40-ux-spec.md P2: a fake progress
     * bar is worse than an honest empty state.
     */
    public function scan()
    {
        $this->data['q'] = (string) $this->input->get('q', true);
        $this->_render('pages/scan', 'radar', 'New scan');
    }

    // ── Internals ────────────────────────────────────────────────────────────

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
        $this->data['oc_query']   = $this->_niche() . ', ' . $this->_city_label($city);

        // Rail-foot quota meters. FE entitlements per the brief's ladder; the real
        // two-meter model (pulls vs monitored slots) arrives in Phase 8.
        $this->data['oc_quota'] = [
            ['label' => 'Watched', 'used' => 0, 'cap' => 25],
            ['label' => 'Cities',  'used' => 1, 'cap' => 1],
        ];

        $this->oc_render_lib->render($view, $this->data, [
            'page'       => $nav_key,
            'page_title' => $title,
        ]);
    }

    /** @return string the active market's city key */
    private function _city()
    {
        return self::DEMO_CITY;
    }

    /** @return string the active market's niche key */
    private function _niche()
    {
        return self::DEMO_NICHE;
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
