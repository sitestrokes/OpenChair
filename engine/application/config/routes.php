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

// ── OpenChair — the product. Specific first, catch-all last. ─────────────────
$route['openchair']            = 'openchair/openchair/today';
$route['openchair/scan']       = 'openchair/openchair/scan';
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
// Watchlist + pipeline actions (POST). Before the catch-all.
$route['openchair/watch']      = 'openchair/openchair/watch';
$route['openchair/unwatch']    = 'openchair/openchair/unwatch';
$route['openchair/stage']      = 'openchair/openchair/stage';
$route['openchair/notes']      = 'openchair/openchair/notes';
$route['openchair/make-pitch'] = 'openchair/openchair/make_pitch';
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
