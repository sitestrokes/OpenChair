<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Agentic Affiliates Bridge template-set switch.
 * ---------------------------------------------------------------------------
 * The 7 Bridge templates (opt-in 201-207 + their thank-you 231-237) exist in
 * TWO parallel sets on disk, with IDENTICAL filenames and jsondata:
 *
 *   engine/landing-pages/   <- the current production set (default)
 *   engine/landing-pages2/  <- the refreshed "v2" set (staging)
 *
 * A single global setting (settings.meta_type = 'bridge_template_set') picks
 * which set both the BUILD pipeline (Bridge_build_lib) and the visual EDITOR
 * (Sites::edit_template) read from, so the whole Bridge look can be swapped
 * from one dropdown without moving files. Default + any invalid value resolve
 * to 'landing-pages', so this is a no-op until a superadmin flips it.
 *
 * The two sets use DIFFERENT artwork conventions, so callers branch on the
 * LOADED path (via ic_template_is_v2), never on the toggle alone -- a legacy
 * niche page that only exists under landing-pages/ still renders correctly:
 *   landing-pages   -> "../images/x.png"        (rewritten to base_url('landing-pages/images/'))
 *   landing-pages2  -> "assets/{img,logos}/x.png" (rewritten by ic_template_rewrite_assets)
 */

if ( ! function_exists('ic_template_set'))
{
    /**
     * The active Bridge template set: 'landing-pages' (default) | 'landing-pages2'.
     * Read once per request (static-cached) and validated against the whitelist.
     *
     * @return string
     */
    function ic_template_set()
    {
        static $set = null;
        if ($set !== null) return $set;

        $allowed = array('landing-pages', 'landing-pages2');
        $set = 'landing-pages'; // safe default -- current production set

        $CI =& get_instance();
        if (isset($CI->db))
        {
            $row = $CI->db
                ->select('meta_data')
                ->where('meta_type', 'bridge_template_set')
                ->order_by('id', 'ASC')
                ->limit(1)
                ->get('settings')
                ->row();
            if ($row && in_array(trim($row->meta_data), $allowed, true))
            {
                $set = trim($row->meta_data);
            }
        }
        return $set;
    }
}

if ( ! function_exists('ic_template_path'))
{
    /**
     * Swap a repo-relative template path ('landing-pages/...') to the active set.
     * Only paths under the landing-pages/ prefix are touched, and the swap is
     * applied ONLY when the alternate file actually exists on disk -- so a
     * template that lives only in the production set (e.g. the legacy niche
     * pages 101-127) transparently falls back instead of 404-ing.
     *
     * @param  string $rel_path e.g. 'landing-pages/html/affiliate-v2.html'
     * @return string           the same path, or the landing-pages2 equivalent
     */
    function ic_template_path($rel_path)
    {
        $set = ic_template_set();
        if ($set === 'landing-pages') return $rel_path;             // default: untouched
        $prefix = 'landing-pages/';
        if (strpos($rel_path, $prefix) !== 0) return $rel_path;     // not ours -- leave it
        $swapped = $set . '/' . substr($rel_path, strlen($prefix));
        return is_file(FCPATH . $swapped) ? $swapped : $rel_path;   // fall back if absent
    }
}

if ( ! function_exists('ic_template_is_v2'))
{
    /**
     * True when a LOADED template path points at the landing-pages2 set. Callers
     * pass the already-swapped path so the asset-rewrite branch matches the file
     * that was actually read (not just the toggle state).
     *
     * @param  string $path
     * @return bool
     */
    function ic_template_is_v2($path)
    {
        return strpos((string) $path, 'landing-pages2/') !== false;
    }
}

if ( ! function_exists('ic_template_rewrite_assets'))
{
    /**
     * Absolutise a landing-pages2 template's artwork. Those templates reference
     * every image as "assets/img/x.png" / "assets/logos/x.png" (relative to
     * html/), which only resolves when the file is opened directly -- the baked
     * page in funnels.content is served from a different URL (or a custom
     * domain), where the relative path 404s. Rewrite to an absolute app URL so
     * the art survives the bake. Hotlinked https assets and inline SVG are
     * untouched (the leading quote anchors the match to a src=/href= value).
     *
     * @param  string $html
     * @return string
     */
    function ic_template_rewrite_assets($html)
    {
        return str_replace('"assets/', '"' . base_url('landing-pages2/html/assets/'), $html);
    }
}

/* End of file ic_template_helper.php */
/* Location: ./application/helpers/ic_template_helper.php */
