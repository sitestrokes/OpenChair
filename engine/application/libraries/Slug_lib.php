<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Slug_lib
 *
 * Builds and validates SEO-friendly slugs for the funnels.slug column.
 * Centralized so insert sites, lazy-backfill, the publish-modal AJAX check,
 * and submitAction persistence all share the same normalization rules.
 */
class Slug_lib {

    protected $CI;

    public function __construct()
    {
        $this->CI =& get_instance();
        $this->CI->load->helper('url');
        $this->CI->load->database();
    }

    /**
     * Build a globally-unique slug from a free-form name.
     */
    public function make_funnel_slug($name, $exclude_id = 0)
    {
        $base = url_title($name, '-', TRUE);
        if ($base === '') {
            $base = 'funnel';
        }
        $base = substr($base, 0, 160);

        $slug = $base;
        $n = 1;
        while ($this->_slug_taken($slug, $exclude_id)) {
            $n++;
            $slug = $base . '-' . $n;
        }
        return $slug;
    }

    /**
     * Normalize a user-typed slug to the same shape we generate.
     * Does NOT auto-suffix on collision — caller decides.
     */
    public function normalize($input)
    {
        $input = trim((string) $input);

        // If a full URL or path was pasted (e.g. the published link
        // "https://…/webpages/resona-health-wellness" or ".../thankyou"),
        // keep ONLY the final path segment. Otherwise url_title() below would
        // strip the "://", "." and "/" and fuse the whole URL into one giant
        // slug — which, fed back through a later save, doubles and overflows
        // site_uid, breaking the capture lookup. Take the basename first.
        if ($input !== '' && preg_match('#[\\\\/]#', $input)) {
            $parts = array_values(array_filter(
                preg_split('#[\\\\/]+#', $input),
                function ($p) { return trim($p) !== ''; }
            ));
            if (!empty($parts)) {
                $last = end($parts);
                // ".../<slug>/thankyou" → use the slug, not the "thankyou" tail.
                if (strtolower($last) === 'thankyou' && count($parts) >= 2) {
                    $last = $parts[count($parts) - 2];
                }
                $input = $last;
            }
        }

        $clean = url_title($input, '-', TRUE);
        return substr($clean, 0, 190);
    }

    /**
     * Free, or already owned by $exclude_id (so re-saving the same slug is fine).
     */
    public function is_available($slug, $exclude_id = 0)
    {
        if ($slug === '' || $slug === NULL) {
            return FALSE;
        }
        return ! $this->_slug_taken($slug, $exclude_id);
    }

    private function _slug_taken($slug, $exclude_id)
    {
        $this->CI->db->where('slug', $slug);
        if ($exclude_id) {
            $this->CI->db->where('id !=', (int) $exclude_id);
        }
        return (int) $this->CI->db->count_all_results('funnels') > 0;
    }
}
