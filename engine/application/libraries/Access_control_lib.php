<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Access Control Library (Agentic Agency)
 *
 * Single source of truth for who can do what, based on the JVZoo access codes
 * stored CSV in `users.access`. Mirrors BlankVan's AccessControl pattern,
 * adapted to CodeIgniter 3.
 *
 * Access codes (matches application/config/app_config.php):
 *   1   FE                                 (Core)
 *   2   PRO Unlimited                      (OTO1)
 *   3   Content & Automation Mastery       (OTO2)
 *   4   AI Voice Command Center            (OTO3)
 *   7   Agency Empire                      (OTO4)
 *   9   Bundle (FE + all OTOs)
 *   91  Fastpass (Bundle + perks)
 *   5   Admin
 *   51  JV Admin
 *   92  Beta Access
 *   93  JV Access
 *
 * Bundle (9) and Fastpass (91) grant access to ALL OTO features.
 * Superadmin (user_id=1) bypasses every check.
 *
 * Usage:
 *   $this->load->library('access_control_lib');
 *   $this->access_control_lib->can_access_voice_command();   // OTO3+
 *
 * Or via the require_access() helper on App_Controller:
 *   $this->require_access('voice_command');
 */
class Access_control_lib
{
    public $CI;

    // ── Access code constants ─────────────────────────────────────
    const FE                = '1';
    const PRO_UNLIMITED     = '2';   // OTO1
    const CONTENT_MASTERY   = '3';   // OTO2
    const VOICE_COMMAND     = '4';   // OTO3
    const AGENCY_EMPIRE     = '7';   // OTO4
    const DFY_VAULT         = '10';  // OTO5 (also sold as the $120 bump → same code)
    const BUNDLE            = '9';
    const FASTPASS          = '91';
    const BUNDLE_LIFETIME   = '94';   // Bundle Lifetime - same entitlements as BUNDLE(9)
    const ADMIN             = '5';
    const JV_ADMIN          = '51';
    const BETA              = '92';
    const JV                = '93';

    // ── Permission groups (which codes grant which feature) ──────
    // Anything that grants core/FE access (lets the user log in at all)
    const GRANTS_CORE = ['1', '2', '3', '4', '7', '10', '71', '9', '94', '91', '92', '93'];

    // Codes that grant PRO Unlimited (OTO1) features
    // JV (93) is treated as Bundle (9) — full access for review purposes.
    const GRANTS_PRO_UNLIMITED = ['2', '9', '94', '91', '93'];

    // Codes that grant Content & Automation Mastery (OTO2) features
    const GRANTS_CONTENT_MASTERY = ['3', '9', '94', '91', '93'];

    // Codes that grant AI Voice Command Center (OTO3) features
    const GRANTS_VOICE_COMMAND = ['4', '9', '94', '91', '93'];

    // ── v5.1 per-OTO feature gates (menu visibility + in-page controls). These
    // list only the PURCHASE codes; is_full_access() separately covers the
    // admin / JV / Beta review-and-test roles that see everything. ──
    const GRANTS_OFFER_FINDER  = ['3', '9', '94', '91'];   // OTO2 + Bundle + Bundle-Lifetime + Fastpass
    const GRANTS_MULTILINGUAL  = ['3', '9', '94', '91'];   // OTO2 + Bundle + Bundle-Lifetime + Fastpass
    const GRANTS_TELEGRAM      = ['2', '9', '94', '91'];   // OTO1 + Bundle + Bundle-Lifetime + Fastpass
    const GRANTS_CUSTOM_DOMAIN = ['2', '9', '94', '91'];   // OTO1 + Bundle + Bundle-Lifetime + Fastpass
    const GRANTS_CSV_EXPORT    = ['2', '9', '94', '91'];   // OTO1 + Bundle + Bundle-Lifetime + Fastpass
    const GRANTS_DFY_VAULT     = ['10'];             // OTO5 only (Bundle excludes DFY; the bump grants code 10)

    // ── Plan caps (funnel-doc v5.1) ────────────────────────────────
    // FE: 25 active Bridges. OTO1 (Unlimited) / Bundle / Fastpass: "unlimited,
    // fair use" → a 100-Bridge finite cap. Superadmin: unlimited (max_*).
    const FE_FUNNEL_LIMIT  = 25;
    const PRO_FUNNEL_LIMIT = 100;

    // ── v5.2 per-ACCOUNT monthly caps (funnel-doc) ─────────────────
    // TWO tiers only. "Upgraded" = OTO1 (Unlimited) / Bundle / Fastpass, or a
    // full-access role (admin/JV/Beta). user 1 (superadmin) is unlimited everywhere.
    //   New leads (subscribers, account-wide) : FE 100 · upgraded 750
    //   AI voice MINUTES (browser + phone)     : FE 200 · upgraded 750
    //   SMS (see the account-wide block below) : FE 200 · upgraded 750
    // Leads are counted across ALL the owner's Bridges (per account, not per funnel).
    // Voice minutes are summed from api_usage_log.duration_seconds where
    // sub_type='realtime_voice' (covers browser + phone) for the owner this month.
    const FE_LEADS_PER_MONTH               = 100;
    const UPGRADED_LEADS_PER_MONTH         = 750;
    const FE_VOICE_MINUTES_PER_MONTH       = 200;
    const UPGRADED_VOICE_MINUTES_PER_MONTH = 750;

    public function __construct()
    {
        $this->CI =& get_instance();
    }

    // ── Internal helpers ──────────────────────────────────────────

    private $cached_user = null; // per-request cache so we hit ion_auth at most once

    private function _resolve_user($user)
    {
        if ($user !== null) return $user;
        if ($this->cached_user !== null) return $this->cached_user ?: null;

        // HMVC's get_instance() returns a CI superobject that does NOT preserve
        // dynamic properties set on App_Controller (e.g. $this->user). Always
        // resolve via the session user_id and load the row by id — and cache it
        // so repeated checks in the same request don't keep hitting ion_auth.
        $CI = function_exists('get_instance') ? get_instance() : $this->CI;
        if (!is_object($CI)) { $this->cached_user = false; return null; }

        // Prefer ion_auth's session user_id (avoids the stateful ->user()->row() pattern)
        $uid = null;
        if (isset($CI->session) && method_exists($CI->session, 'userdata')) {
            $uid = $CI->session->userdata('user_id');
        }
        if (empty($uid) && isset($CI->ion_auth) && method_exists($CI->ion_auth, 'get_user_id')) {
            $uid = $CI->ion_auth->get_user_id();
        }
        if (empty($uid)) { $this->cached_user = false; return null; }

        // Load the row directly — single fresh query, no shared mutable state.
        if (isset($CI->db)) {
            $row = $CI->db->where('id', (int) $uid)->limit(1)->get('users')->row();
            if (is_object($row) && isset($row->id)) {
                $this->cached_user = $row;
                return $row;
            }
        }
        $this->cached_user = false;
        return null;
    }

    private function _user_codes($user)
    {
        $user = $this->_resolve_user($user);
        if (!$user) return [];
        $access = is_object($user)
            ? (isset($user->access) ? $user->access : '')
            : (string) $user;
        if ($access === '' || $access === null) return [];
        $codes = array_map('trim', explode(',', (string) $access));
        return array_values(array_filter($codes, function ($c) { return $c !== ''; }));
    }

    // ── Public API ───────────────────────────────────────────────

    public function is_super_admin($user = null)
    {
        $user = $this->_resolve_user($user);
        return is_object($user) && isset($user->id) && (int) $user->id === 1;
    }

    /**
     * Does the user hold any of the required access codes?
     * Superadmin always passes.
     */
    public function has_access($user = null, $required = [])
    {
        if ($this->is_super_admin($user)) return TRUE;
        $codes = $this->_user_codes($user);
        if (empty($codes)) return FALSE;

        $required = is_array($required) ? $required : [$required];
        foreach ($required as $r) {
            if (in_array((string) $r, $codes, TRUE)) return TRUE;
        }
        return FALSE;
    }

    /** True if the user has at least one valid access code (can log in at all). */
    public function has_any_access($user = null)
    {
        return $this->has_access($user, self::GRANTS_CORE);
    }

    public function can_access_core($user = null)
    {
        return $this->has_access($user, self::GRANTS_CORE);
    }

    public function can_access_pro_unlimited($user = null)
    {
        return $this->has_access($user, self::GRANTS_PRO_UNLIMITED);
    }

    public function can_access_content_mastery($user = null)
    {
        return $this->has_access($user, self::GRANTS_CONTENT_MASTERY);
    }

    public function can_access_voice_command($user = null)
    {
        return $this->has_access($user, self::GRANTS_VOICE_COMMAND);
    }

    /**
     * ElevenLabs voice engine / voice cloning: FRONT-END feature in v5.1 — every
     * tier can use it (the FE includes cloning). Kept as a method (rather than
     * deleting the two call sites) so it stays a single toggle if it's ever
     * re-gated. $user is accepted for signature-compatibility but unused.
     */
    public function can_use_elevenlabs($user = null)
    {
        return TRUE;
    }

    /**
     * Premium templates / niches: OTO2 (Content Mastery) + Bundle + Fastpass + JV.
     * FE and OTO1/OTO3/OTO4 see only the 20 base templates and corresponding niche
     * dropdown options. OTO2 unlocks the 7 premium templates and 6 premium niches.
     */
    public function can_access_premium_templates($user = null)
    {
        return $this->can_access_content_mastery($user);
    }

    /**
     * "Full access" roles that see EVERY feature/menu regardless of purchase:
     * the superadmin (user 1), Ion-Auth admins, and the Admin(5) / JV Admin(51) /
     * Beta(92) / JV(93) codes. Used to bypass the per-OTO gates below for the
     * review / test / admin accounts.
     */
    public function is_full_access($user = null)
    {
        if ($this->is_super_admin($user)) return TRUE;
        // NOTE: the Ion-Auth 'admin' group is intentionally NOT honored here.
        // Most accounts were mis-provisioned into that group, so trusting it
        // granted full access (and admin surfaces) to JV/buyer users. Full access
        // is by access code only: ADMIN(5) / JV_ADMIN(51) / BETA(92) / JV(93).
        return $this->has_access($user, [self::ADMIN, self::JV_ADMIN, self::BETA, self::JV]);
    }

    /** Offer Finder — OTO2 + Bundle + Fastpass (or a full-access role). */
    public function can_use_offer_finder($user = null)
    {
        return $this->is_full_access($user) || $this->has_access($user, self::GRANTS_OFFER_FINDER);
    }

    /** Telegram build + alerts — OTO1 + Bundle + Fastpass (or a full-access role). */
    public function can_use_telegram($user = null)
    {
        return $this->is_full_access($user) || $this->has_access($user, self::GRANTS_TELEGRAM);
    }

    /** Custom domain — OTO1 + Bundle + Fastpass (or a full-access role). */
    public function can_use_custom_domain($user = null)
    {
        return $this->is_full_access($user) || $this->has_access($user, self::GRANTS_CUSTOM_DOMAIN);
    }

    /** CSV / data export — OTO1 + Bundle + Fastpass (or a full-access role). */
    public function can_export_csv($user = null)
    {
        return $this->is_full_access($user) || $this->has_access($user, self::GRANTS_CSV_EXPORT);
    }

    /** Multilingual voices & voice tuning — OTO2 + Bundle + Fastpass (or a full-access role). */
    public function can_use_multilingual($user = null)
    {
        return $this->is_full_access($user) || $this->has_access($user, self::GRANTS_MULTILINGUAL);
    }

    /**
     * DFY Reviewed Page Vault (OTO5) — copy admin-built reviewed Bridges. Access
     * code 10 only (bought standalone, or via the $120 bump which grants the same
     * code), plus full-access roles. Bundle deliberately EXCLUDES DFY.
     */
    public function can_use_dfy($user = null)
    {
        return $this->is_full_access($user) || $this->has_access($user, self::GRANTS_DFY_VAULT);
    }

    /**
     * The "upgraded" capacity tier for the v5.2 per-account caps: OTO1 (Unlimited)
     * / Bundle / Fastpass, or a full-access role (admin/JV/Beta). Everyone else is
     * on the FE tier. Superadmin (user 1) is unlimited and handled separately by
     * each cap method (returns -1 before this is consulted).
     */
    public function has_upgraded_limits($user = null)
    {
        return $this->is_full_access($user) || $this->can_access_pro_unlimited($user);
    }

    /**
     * Maximum number of funnels this user is allowed to own.
     *   FE / non-upgraded → 25
     *   Upgraded (OTO1 / Bundle / Fastpass / full-access) → 100
     *   Superadmin        → -1 (unlimited)
     */
    public function max_funnels($user = null)
    {
        if ($this->is_super_admin($user)) return -1;
        if ($this->has_upgraded_limits($user)) return self::PRO_FUNNEL_LIMIT;
        return self::FE_FUNNEL_LIMIT;
    }

    /** Has the user hit their funnel cap? */
    public function has_reached_funnel_cap($user = null, $current_count = 0)
    {
        $max = $this->max_funnels($user);
        if ($max === -1) return FALSE;
        return $current_count >= $max;
    }

    /**
     * Max NEW leads (subscribers) this ACCOUNT can collect this calendar month,
     * counted across ALL the owner's Bridges (per account, not per funnel).
     *   FE / non-upgraded → 100
     *   Upgraded          → 750
     *   Superadmin        → -1 (unlimited)
     */
    public function max_leads_per_month($user = null)
    {
        if ($this->is_super_admin($user)) return -1;
        if ($this->has_upgraded_limits($user)) return self::UPGRADED_LEADS_PER_MONTH;
        return self::FE_LEADS_PER_MONTH;
    }

    /** Has this account hit its monthly lead cap, given the account-wide count this month? */
    public function has_reached_lead_cap($user = null, $current_month_count = 0)
    {
        $max = $this->max_leads_per_month($user);
        if ($max === -1) return FALSE;
        return $current_month_count >= $max;
    }

    /**
     * Max AI VOICE MINUTES this ACCOUNT can consume this calendar month — browser
     * AND phone. FE 200 · upgraded 750 · superadmin unlimited (-1).
     */
    public function max_voice_minutes_per_month($user = null)
    {
        if ($this->is_super_admin($user)) return -1;
        if ($this->has_upgraded_limits($user)) return self::UPGRADED_VOICE_MINUTES_PER_MONTH;
        return self::FE_VOICE_MINUTES_PER_MONTH;
    }

    /**
     * Seconds of realtime voice this ACCOUNT has used this calendar month — browser
     * AND phone, summed from api_usage_log.duration_seconds (sub_type='realtime_voice')
     * for the owner. Month boundary computed in MySQL (not PHP date()) so it lines up
     * with the MySQL-written created_at regardless of PHP/MySQL timezone skew.
     */
    public function voice_seconds_this_month($owner_id)
    {
        $row = $this->CI->db->select('COALESCE(SUM(duration_seconds),0) AS s', FALSE)
            ->where('owner_user_id', (int) $owner_id)
            ->where("sub_type = 'realtime_voice'", NULL, FALSE)
            ->where('created_at >= DATE_FORMAT(NOW(), "%Y-%m-01 00:00:00")', NULL, FALSE)
            ->get('api_usage_log')->row();
        return $row ? (int) $row->s : 0;
    }

    /**
     * Has this account hit its monthly voice-minutes cap? Pass the owner user (for
     * the tier) and optionally the owner id (for the count; derived from $user when
     * omitted). Fails OPEN (false) on any error — a cap lookup blip must never
     * silence a lead's voice.
     */
    public function voice_minutes_cap_reached($user = null, $owner_id = null)
    {
        try {
            $max_min = $this->max_voice_minutes_per_month($user);
            if ($max_min === -1) return FALSE;
            if (empty($owner_id)) {
                $u = $this->_resolve_user($user);
                $owner_id = (is_object($u) && isset($u->id)) ? (int) $u->id : 0;
            }
            if (empty($owner_id)) return FALSE;
            return $this->voice_seconds_this_month((int) $owner_id) >= ($max_min * 60);
        } catch (\Throwable $e) {
            return FALSE;
        }
    }

    // ── v5.1 monthly usage caps (account-wide) ─────────────────────
    //   Traffic Pack generations: FE 10 · OTO3/Bundle/Fastpass unlimited (unchanged).
    //   Triggered SMS contacts:   FE 200 · upgraded (OTO1/Bundle/Fastpass) 750.
    // Superadmin (user 1) unlimited. The old per-account PHONE-contact cap was
    // removed — the account VOICE-MINUTES cap above covers browser + phone instead.
    const FE_TRAFFIC_GENS_PER_MONTH       = 10;
    const FE_SMS_CONTACTS_PER_MONTH       = 200;
    const UPGRADED_SMS_CONTACTS_PER_MONTH = 750;

    /** Max Traffic Pack generations this month (-1 = unlimited). OTO3 unlocks unlimited. */
    public function max_traffic_gens_per_month($user = null)
    {
        if ($this->is_full_access($user)) return -1;
        if ($this->can_access_voice_command($user)) return -1; // OTO3 + Bundle + Fastpass
        return self::FE_TRAFFIC_GENS_PER_MONTH;
    }

    /** Max triggered SMS contacts this month. FE 200 · upgraded 750 · superadmin unlimited. */
    public function max_sms_contacts_per_month($user = null)
    {
        if ($this->is_super_admin($user)) return -1;
        if ($this->has_upgraded_limits($user)) return self::UPGRADED_SMS_CONTACTS_PER_MONTH;
        return self::FE_SMS_CONTACTS_PER_MONTH;
    }

    /**
     * Count of this account's Traffic Pack *generations* this calendar month.
     * One generation writes a batch of asset rows sharing a single (second-
     * resolution) created_at, so distinct timestamps ≈ distinct generations —
     * not raw row count, which would count individual assets.
     */
    public function traffic_gens_this_month($user_id)
    {
        // Month boundary computed in MySQL (NOT PHP date()) so it lines up with the
        // MySQL-written created_at column regardless of PHP/MySQL timezone skew.
        $row = $this->CI->db->select('COUNT(DISTINCT `created_at`) AS n', FALSE)
            ->where('user_id', (int) $user_id)
            ->where('created_at >= DATE_FORMAT(NOW(), "%Y-%m-01 00:00:00")', NULL, FALSE)
            ->get('traffic_assets')->row();
        return $row ? (int) $row->n : 0;
    }

    /** Has this account hit its monthly Traffic Pack cap? Pass a pre-counted total or let it count. */
    public function has_reached_traffic_cap($user = null, $current_month_count = null)
    {
        $max = $this->max_traffic_gens_per_month($user);
        if ($max === -1) return FALSE;
        if ($current_month_count === null) {
            $u = $this->_resolve_user($user);
            $current_month_count = (is_object($u) && isset($u->id)) ? $this->traffic_gens_this_month((int) $u->id) : 0;
        }
        return $current_month_count >= $max;
    }

    // ── v5.1 connection caps ───────────────────────────────────────
    //   Connected autoresponders: FE 1 · OTO3/Bundle/Fastpass unlimited.
    //   Active webhook/Slack rules: FE 1 · OTO3/Bundle/Fastpass 10.
    // Both live in the Automated Funnel Engine (OTO3). Full-access roles
    // (admin/JV/Beta) + superadmin are unlimited on both.
    const FE_AUTORESPONDERS    = 1;
    const FE_AUTOMATION_RULES  = 1;
    const PRO_AUTOMATION_RULES = 10;

    /** Max connected autoresponders (-1 = unlimited). OTO3 unlocks unlimited. */
    public function max_autoresponders($user = null)
    {
        if ($this->is_full_access($user)) return -1;
        if ($this->can_access_voice_command($user)) return -1; // OTO3 + Bundle + Fastpass
        return self::FE_AUTORESPONDERS;
    }

    /** Count of this account's currently-connected (status=1) autoresponders. */
    public function autoresponders_connected($user_id)
    {
        return (int) $this->CI->db->where('user_id', (int) $user_id)
            ->where('status', 1)->count_all_results('autoresponder');
    }

    /** Has this account hit its connected-autoresponder cap? */
    public function has_reached_autoresponder_cap($user = null, $current_count = null)
    {
        $max = $this->max_autoresponders($user);
        if ($max === -1) return FALSE;
        if ($current_count === null) {
            $u = $this->_resolve_user($user);
            $current_count = (is_object($u) && isset($u->id)) ? $this->autoresponders_connected((int) $u->id) : 0;
        }
        return $current_count >= $max;
    }

    /** Max automation (webhook/Slack) rules (-1 = unlimited). OTO3 raises FE 1 → 10. */
    public function max_automation_rules($user = null)
    {
        if ($this->is_full_access($user)) return -1;
        if ($this->can_access_voice_command($user)) return self::PRO_AUTOMATION_RULES; // OTO3 + Bundle + Fastpass
        return self::FE_AUTOMATION_RULES;
    }

    /** Count of this account's automation rules. */
    public function automation_rules_count($user_id)
    {
        return (int) $this->CI->db->where('user_id', (int) $user_id)
            ->count_all_results('automations');
    }

    /** Has this account hit its automation-rule cap? */
    public function has_reached_automation_cap($user = null, $current_count = null)
    {
        $max = $this->max_automation_rules($user);
        if ($max === -1) return FALSE;
        if ($current_count === null) {
            $u = $this->_resolve_user($user);
            $current_count = (is_object($u) && isset($u->id)) ? $this->automation_rules_count((int) $u->id) : 0;
        }
        return $current_count >= $max;
    }

    // ── v5.1 OTO3 (Automated Funnel Engine) extras ──────────────────
    // Delivery logs + test controls, and extending the follow-up sequence, are
    // OTO3/Bundle/Fastpass (or a full-access role). Extending the sequence has a
    // fair-use ceiling of MAX_FOLLOWUP_STEPS per Bridge.
    const MAX_FOLLOWUP_STEPS = 10;

    /** Automations delivery log + per-rule Test control — OTO3+. */
    public function can_use_automation_logs($user = null)
    {
        return $this->is_full_access($user) || $this->can_access_voice_command($user);
    }

    /** Can extend the follow-up sequence (add steps beyond the FE default)? OTO3+. */
    public function can_add_followups($user = null)
    {
        return $this->is_full_access($user) || $this->can_access_voice_command($user);
    }

    public function get_access_summary($user = null)
    {
        return [
            'is_super_admin'  => $this->is_super_admin($user),
            'codes'           => $this->_user_codes($user),
            'core'            => $this->can_access_core($user),
            'pro_unlimited'   => $this->can_access_pro_unlimited($user),
            'content_mastery' => $this->can_access_content_mastery($user),
            'voice_command'   => $this->can_access_voice_command($user),
            'full_access'     => $this->is_full_access($user),
            'offer_finder'    => $this->can_use_offer_finder($user),
            'telegram'        => $this->can_use_telegram($user),
            'custom_domain'   => $this->can_use_custom_domain($user),
            'csv_export'      => $this->can_export_csv($user),
            'multilingual'    => $this->can_use_multilingual($user),
            'max_funnels'     => $this->max_funnels($user),
            'max_leads_per_month'         => $this->max_leads_per_month($user),
            'max_voice_minutes_per_month' => $this->max_voice_minutes_per_month($user),
            'max_sms_contacts_per_month'  => $this->max_sms_contacts_per_month($user),
        ];
    }
}
