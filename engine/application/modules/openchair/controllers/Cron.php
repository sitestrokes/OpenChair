<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Scheduled jobs that belong to the CI3 side — docs/70-radar-build.md R5.
 *
 * The crawl, scoring and rollup jobs live in openchair-service
 * (`node src/jobs/run.js …`), next to the code that owns that data. These are
 * the tenant-layer jobs: the 8am digest and share-link housekeeping.
 *
 *   php index.php cron digest      build today's digest for every tenant market
 *   php index.php cron housekeep   delete share links 30 days past expiry; old scan requests
 *   php index.php cron all
 *
 * CLI ONLY. This controller extends the bare framework controller (no login
 * gate) precisely because it is never reachable over HTTP: the constructor
 * refuses any non-CLI request with a 404 before a method runs.
 */
class Cron extends MX_Controller
{
    public function __construct()
    {
        parent::__construct();
        if (!is_cli()) { show_404(); exit; }
        $this->load->config('app_config');
        $this->load->helper(['url', 'openchair/oc_format']);
        $this->load->model('openchair/oc_market_model');
        $this->load->model('openchair/oc_alert_model');
    }

    /**
     * Today's digest for every tenant that has a market. In production a
     * scheduler calls this per timezone at 08:00 local; one run per day is
     * idempotent (the alert model claims businesses per day).
     */
    public function digest()
    {
        $today = date('Y-m-d');
        $markets = $this->db->from('oc_market')->order_by('user_id', 'ASC')->order_by('id', 'ASC')->get()->result();
        $done = []; $built = 0;
        foreach ($markets as $m) {
            if (isset($done[(int) $m->user_id])) { continue; }       // one digest per tenant per day
            $done[(int) $m->user_id] = true;
            $this->oc_alert_model->build((int) $m->user_id, $m, $today, 'in_app');
            $built++;
        }
        $this->_line('digest', ['tenants' => $built, 'date' => $today]);
    }

    /** Share links long past expiry, and scan requests older than a year. */
    public function housekeep()
    {
        $this->db->where('expires_at <', date('Y-m-d H:i:s', time() - 30 * 86400))->delete('oc_share_link');
        $links = $this->db->affected_rows();
        $this->db->where('created_at <', date('Y-m-d H:i:s', time() - 365 * 86400))->delete('oc_scan_request');
        $reqs = $this->db->affected_rows();
        $this->_line('housekeep', ['share_links_deleted' => $links, 'scan_requests_deleted' => $reqs]);
    }

    public function all()
    {
        $this->digest();
        $this->housekeep();
    }

    public function index()
    {
        fwrite(STDERR, "usage: php index.php cron digest|housekeep|all\n");
    }

    /** One JSON line per job, for the scheduler's log. */
    private function _line($job, array $summary)
    {
        echo json_encode(['job' => $job, 'at' => date('c')] + $summary), "\n";
    }
}
