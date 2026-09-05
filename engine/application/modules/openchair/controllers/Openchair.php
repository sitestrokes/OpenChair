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
     * S5 — Business Detail (the rail calls it "Meter").
     * Bare /business falls back to the first business so the nav is never dead.
     */
    public function business($id = null)
    {
        $b = $id ? $this->oc_business_model->find($id)
                 : $this->oc_business_model->first_in_market($this->_city(), $this->_niche());

        if (!$b) { show_404(); return; }

        $this->data['b'] = $b;
        $this->data['prospect'] = $this->oc_prospect_model->find($this->user_id, $b->id);
        $this->_render('pages/business', 'business', $b->name);
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

        $this->data['b']       = $b;
        $this->data['variant'] = $variant;
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

    /** S8 — Ledger. The 8-week trend; real rollups land in Phase 7. */
    public function ledger()
    {
        $this->data['b'] = $this->oc_business_model->first_in_market($this->_city(), $this->_niche());
        $this->_render('pages/ledger', 'ledger', 'Ledger');
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
