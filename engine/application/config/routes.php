<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * OpenChair routes.
 *
 * HMVC needs module/controller/method, so every route is explicit. House rule
 * carried from the host engine: SPECIFIC routes must precede the (:any)
 * catch-all, or a hyphenated URL gets swallowed as a method name.
 */

// The rail's Today screen is the app.
$route['default_controller'] = 'openchair/openchair/today';
$route['404_override']       = '';
$route['translate_uri_dashes'] = FALSE;

// ── Auth (Ion Auth) ──────────────────────────────────────────────────────────
$route['login']                      = 'auth/login';
$route['logout']                     = 'auth/logout';
$route['forgot_password']            = 'auth/forgot_password';
$route['reset_password']             = 'auth/reset_password';
$route['auth/reset_password/(:any)'] = 'auth/reset_password/$1';

// ── Users ────────────────────────────────────────────────────────────────────
$route['users']               = 'users';
$route['users/(:num)']        = 'users/index/$1';
$route['useroperation']       = 'users/createUser';
$route['useroperation/(:num)']= 'users/createUser/$1';
$route['createNewUser']       = 'users/create';
$route['updateUser']          = 'users/update';
$route['deleteUser']          = 'users/deleteUser';
$route['myprofile']           = 'users/profile';

// ── Settings ─────────────────────────────────────────────────────────────────
$route['settings']        = 'setting/settings';
$route['setting']         = 'setting/index';
$route['insert_setting']  = 'setting/insertSetting';
$route['update_setting']  = 'setting/updateSetting';

// ── White-label / API config ─────────────────────────────────────────────────
$route['whitelabel']  = 'whitelabel';
$route['api-config']  = 'config/index';
$route['api-config/(:any)'] = 'config/$1';

// ── CLI jobs (the controller refuses web requests) ───────────────────────────
$route['cron']         = 'openchair/cron/index';
$route['cron/(:any)']  = 'openchair/cron/$1';

// ── OpenChair — the product. Specific first, catch-all last. ─────────────────
$route['openchair']            = 'openchair/openchair/today';
$route['openchair/scan']       = 'openchair/openchair/scan';
$route['openchair/scan/submit']        = 'openchair/openchair/scan_submit';
$route['openchair/scan/(:num)']        = 'openchair/openchair/scan_status/$1';
$route['openchair/radar']      = 'openchair/openchair/radar';
$route['openchair/prospects']  = 'openchair/openchair/prospects';
$route['openchair/ledger']     = 'openchair/openchair/ledger';
$route['openchair/ledger/(:num)'] = 'openchair/openchair/ledger/$1';
$route['openchair/coverage']   = 'openchair/openchair/coverage';
$route['openchair/adapters']   = 'openchair/openchair/adapters';
// Business Detail and Pitch take an id; the bare form falls back to the first
// demo business so the rail's Meter/Pitch items are never dead links.
$route['openchair/business']         = 'openchair/openchair/business';
$route['openchair/business/(:num)']  = 'openchair/openchair/business/$1';
$route['openchair/pitch']            = 'openchair/openchair/pitch';
$route['openchair/pitch/(:num)']     = 'openchair/openchair/pitch/$1';
$route['openchair/onepager/(:num)']  = 'openchair/openchair/onepager/$1';
// Signature visuals (docs/60-signature-visuals.md): a card on its own page.
$route['openchair/card/(:num)/(:any)'] = 'openchair/openchair/card/$1/$2';
$route['openchair/card/(:num)']        = 'openchair/openchair/card/$1/week';
$route['openchair/how-it-works']       = 'openchair/openchair/how';
// Watchlist + pipeline actions (POST). Before the catch-all.
$route['openchair/watch']      = 'openchair/openchair/watch';
$route['openchair/unwatch']    = 'openchair/openchair/unwatch';
$route['openchair/stage']      = 'openchair/openchair/stage';
$route['openchair/notes']      = 'openchair/openchair/notes';
$route['openchair/make-pitch'] = 'openchair/openchair/make_pitch';
$route['openchair/share']      = 'openchair/openchair/share';
$route['openchair/assign']     = 'openchair/openchair/assign';
$route['openchair/build-digest'] = 'openchair/openchair/build_digest';
$route['openchair/(:any)']           = 'openchair/openchair/$1';

// Public URL alias: /app/* mirrors /openchair/*, the same rebrand trick the host
// engine uses so the internal module name never leaks into a bookmark. Runs AFTER
// the routes above so the aliases inherit specific-before-catch-all ordering.
foreach (array_keys($route) as $__oc_from) {
    if (strpos($__oc_from, 'openchair/') === 0) {
        $route['app/' . substr($__oc_from, strlen('openchair/'))] = $route[$__oc_from];
    }
}
if (isset($route['openchair'])) { $route['app'] = $route['openchair']; }

// Public share links (docs/60-signature-visuals.md §4.5). No login: the token is
// the key. Declared after the /app alias loop on purpose — this is not an
// openchair/* route and must not be mirrored or swallowed by the catch-all.
$route['c/([a-f0-9]{32})'] = 'openchair/oc_share/view/$1';
$route['c/(:any)']         = 'openchair/oc_share/view/$1';

