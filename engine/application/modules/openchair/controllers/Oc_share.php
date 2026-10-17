<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Oc_share — the PUBLIC side of a share link: /c/{token}.
 *
 * No login. Extends MX_Controller, not App_Controller, because App_Controller
 * redirects anyone without a session — and the person opening this is a
 * business owner who has never heard of OpenChair.
 *
 * Read-only by construction: one GET, one counter increment, the frozen card.
 *   - The card renders from the payload stored when the link was made, never
 *     from live data (the picture sent is the picture seen).
 *   - noindex in a header and a meta tag; tokens are 128-bit and expire
 *     (rule 10: vacancy rankings are never published or indexed).
 *   - Opens are counted, not tracked: no IP, no user agent, no cookie.
 *   - There is no link from here back into the app, and nothing here can reach
 *     the business (rule 7).
 */
class Oc_share extends MX_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->helper(['url', 'openchair/oc_format']);
        $this->load->model('openchair/oc_share_model');
    }

    /**
     * @param string $token
     * @return void
     */
    public function view($token = '')
    {
        $this->output->set_header('X-Robots-Tag: noindex, nofollow, noarchive');
        $this->output->set_header('Referrer-Policy: no-referrer');
        $this->output->set_header('Cache-Control: private, no-store');

        $row = preg_match('/^[a-f0-9]{32}$/', (string) $token) ? $this->oc_share_model->find_public($token) : null;
        if (!$row) {
            $this->_gone(404, 'This link doesn\'t exist.', 'Check the link you were sent, or ask the person who sent it for a fresh one.');
            return;
        }

        $card = json_decode($row->payload, true);
        if (!is_array($card) || empty($card['kind'])) {
            $this->_gone(404, 'This link doesn\'t exist.', 'Ask the person who sent it for a fresh one.');
            return;
        }
        $agency = !empty($card['brand']['name']) ? $card['brand']['name'] : 'the person who sent it';

        if ($row->expired) {
            $this->_gone(410, 'This link has expired.', 'Ask ' . $agency . ' for a fresh one.');
            return;
        }

        $this->oc_share_model->record_open($row->id);

        $names = ['week' => 'Chair Week', 'map' => 'Chair Map', 'receipt' => 'Unsold chair time',
                  'tuesday' => 'Same day, same street', 'progress' => '8-week report'];
        $name  = $card['business']['name'];

        $this->load->view('visuals/card_page', [
            'card'       => $card,
            'card_mode'  => 'share',
            'size'       => $this->input->get('size') === 'phone' ? 'phone' : 'print',
            'share_meta' => [
                'og_title'       => $name . ' — ' . (isset($names[$card['kind']]) ? $names[$card['kind']] : 'Chair Week'),
                'og_description' => $this->_describe($card),
                'note'           => 'Shared by ' . $agency . ' · link expires ' . date('M j, Y', strtotime($row->expires_at)),
            ],
        ]);
    }

    /**
     * The link-preview sentence. About THIS business only — never another shop
     * (rule 10), and money only as a range (rule 4).
     *
     * @param array $card
     * @return string
     */
    private function _describe(array $card)
    {
        $s = $card['score'];
        if ($s['value'] === null) { return 'Your week, chair by chair.'; }
        $out = 'Chair Score ' . $s['value'] . '/100 · ' . $s['label'] . ', ' . $s['need'] . '. ';
        if ($s['band'] === 'slammed' && $card['wait']['days'] !== null) {
            $out .= 'A new client\'s first opening online is ' . (int) $card['wait']['days'] . ' days away.';
        } elseif (!empty($card['money'])) {
            $out .= $card['totals']['empty'] . ' of ' . $card['totals']['bookable']
                  . ' online-bookable chair-hours sat empty: ≈ ' . oc_range_tight($card['money']['lo'], $card['money']['hi'])
                  . ' unsold.';
        }
        return trim($out);
    }

    /**
     * A plain, unbranded "not here" page — no app chrome, no links into it.
     *
     * @param int    $status
     * @param string $title
     * @param string $line
     * @return void
     */
    private function _gone($status, $title, $line)
    {
        $this->output->set_status_header($status);
        $this->output->set_content_type('text/html');
        $this->output->set_output('<!doctype html><html lang="en"><head><meta charset="utf-8">'
            . '<meta name="viewport" content="width=device-width,initial-scale=1">'
            . '<meta name="robots" content="noindex,nofollow"><title>' . html_escape($title) . '</title>'
            . '<style>body{margin:0;min-height:100vh;display:grid;place-items:center;background:#FAF5F1;color:#1E1915;'
            . 'font:16px/1.5 system-ui,-apple-system,"Segoe UI",sans-serif;padding:24px}main{max-width:420px;text-align:center}'
            . 'h1{font-size:26px;letter-spacing:-.02em;margin:0 0 8px}p{color:#4A403A;margin:0}</style></head><body><main>'
            . '<h1>' . html_escape($title) . '</h1><p>' . html_escape($line) . '</p></main></body></html>');
    }
}
