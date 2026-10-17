<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * OpenChair display formatting.
 *
 * This file is where 05-prd.md §8 stops being a document and becomes code. Every
 * number a consultant might read aloud to a business owner passes through here,
 * so the integrity rules are enforced in one place instead of being re-remembered
 * on thirteen screens:
 *
 *   rule 2  never "empty shop" — the unit is "online-bookable open slots"
 *   rule 3  never show a number below the confidence floor; show the gap instead
 *   rule 4  always a range, never a point estimate, and always name the source
 *   rule 6  always show data age; stale data is banner-labelled
 *
 * The module's tests assert against these functions, which is only meaningful
 * because no view formats a figure on its own.
 */

if (!function_exists('oc_money')) {
    /**
     * Whole dollars from cents. No cents shown — "$1,860", never "$1,860.00".
     * A figure quoted in a shop is a round number or it sounds invented.
     *
     * @param int $cents
     * @return string
     */
    function oc_money($cents)
    {
        return '$' . number_format((int) round($cents / 100));
    }
}

if (!function_exists('oc_revenue_range')) {
    /**
     * Unsold Value, as a RANGE (rule 4).
     *
     * BOTH ends come from the Meter (oc_008 added the high column). They are not
     * derived here, and must not be: the model's band is
     * openMinutes × (1 ± rho) crossed with the price band's p25..p75, where
     * rho = 0.05 + 0.45 × (1 − confidence). The range therefore WIDENS as
     * confidence falls, which is the entire point of showing a range at all. No
     * fixed ratio applied to the low end can reproduce that — it would draw a
     * shaky business and a well-understood one with identically tight bands.
     *
     * This is the HEADLINE figure: minutes on their page × prices on their page.
     * It is not a forecast, and it is not Recoverable Revenue — see
     * oc_recoverable_range(), which sizes the offer and is never the headline.
     *
     * @param int      $low_cents   empty_chair_cents
     * @param int|null $high_cents  empty_chair_hi_cents
     * @return string e.g. "$1,860 – $4,340"
     */
    function oc_revenue_range($low_cents, $high_cents = null)
    {
        $low_cents = (int) $low_cents;

        if ($high_cents === null || (int) $high_cents <= $low_cents) {
            // Pre-oc_008 rows, or a business scored before the high end was
            // stored. Fall back to the old assumed ratio rather than printing a
            // zero-width range that would read as a point estimate — the one
            // thing rule 4 forbids.
            $CI = get_instance();
            $lo = (float) $CI->config->item('oc_sellthrough_low');
            $hi = (float) $CI->config->item('oc_sellthrough_high');
            if ($lo <= 0) { $lo = 0.15; }
            if ($hi <= $lo) { $hi = $lo * 2.33; }
            $high_cents = (int) round($low_cents * ($hi / $lo));
        }

        return oc_money($low_cents) . ' – ' . oc_money($high_cents);
    }
}

if (!function_exists('oc_recoverable_range')) {
    /**
     * Recoverable Revenue — what could realistically be sold of that unsold time.
     *
     * Derived from the LOW end of Unsold Value only. 35-scoring-model.md §3.4 is
     * explicit about why: multiplying the top of a price band by the top of a
     * sell-through band is how a forecast becomes fiction.
     *
     * Sizes the offer. Never the headline — a single "vacancy × price" figure
     * silently asserts 100% sell-through, which nobody achieves and any owner
     * will call out in the first thirty seconds.
     *
     * @param int $low_cents empty_chair_cents (the Unsold Value low end)
     * @return string
     */
    function oc_recoverable_range($low_cents)
    {
        $CI = get_instance();
        $lo = (float) $CI->config->item('oc_sellthrough_low');
        $hi = (float) $CI->config->item('oc_sellthrough_high');
        if ($lo <= 0) { $lo = 0.15; }
        if ($hi <= $lo) { $hi = 0.35; }

        // Round down at both ends, matching the model: we never overstate.
        $r50 = function ($cents) { return (int) (floor($cents / 5000) * 5000); };
        $a = $r50($low_cents * $lo);
        $b = max($r50($low_cents * $hi), $a + 5000);

        return oc_money($a) . ' – ' . oc_money($b);
    }
}

if (!function_exists('oc_below_floor')) {
    /**
     * Is this observation below the confidence floor? (rule 3)
     *
     * @param int|null $confidence 0..100
     * @return bool
     */
    function oc_below_floor($confidence)
    {
        if ($confidence === null) { return true; }
        return (int) $confidence < (int) get_instance()->config->item('oc_confidence_floor');
    }
}

if (!function_exists('oc_score_or_gap')) {
    /**
     * The Vacancy Score as a percentage — or the honest gap when confidence is
     * too low to publish a number (rule 3).
     *
     * Returning the literal string rather than a number is deliberate: a caller
     * cannot accidentally do arithmetic on "—" and print something.
     *
     * @param int|null $vacancy_bp  0..10000 basis points
     * @param int|null $confidence  0..100
     * @return string
     */
    function oc_score_or_gap($vacancy_bp, $confidence)
    {
        if (oc_below_floor($confidence) || $vacancy_bp === null) { return '—'; }
        return (string) (int) round($vacancy_bp / 100);
    }
}

if (!function_exists('oc_bucket_label')) {
    /**
     * Human label for a bucket. "Steady" rather than "neutral": the middle bucket
     * still has something to sell it (efficiency), and calling it neutral tells
     * the consultant to skip it.
     *
     * @param string|null $bucket hungry|neutral|slammed
     * @return string
     */
    function oc_bucket_label($bucket)
    {
        switch ($bucket) {
            case 'hungry':  return 'Hungry';
            case 'slammed': return 'Slammed';
            case 'neutral': return 'Steady';
            default:        return 'No score';
        }
    }
}

if (!function_exists('oc_bucket_class')) {
    /**
     * CSS modifier for a bucket — hot / mid / cool / low, matching the prototype.
     * Colour is never the only encoding: every caller also prints the label
     * (NFR-8, no meaning by colour alone).
     *
     * @param string|null $bucket
     * @param int|null    $confidence
     * @return string
     */
    function oc_bucket_class($bucket, $confidence = null)
    {
        if ($confidence !== null && oc_below_floor($confidence)) { return 'low'; }
        switch ($bucket) {
            case 'hungry':  return 'hot';
            case 'slammed': return 'cool';
            case 'neutral': return 'mid';
            default:        return 'low';
        }
    }
}

if (!function_exists('oc_bucket_glyph')) {
    /**
     * The redundant non-colour encoding for a bucket (FR-5.1, NFR-8).
     *
     * @param string|null $bucket
     * @return string
     */
    function oc_bucket_glyph($bucket)
    {
        switch ($bucket) {
            case 'hungry':  return '▲';
            case 'slammed': return '●';
            case 'neutral': return '■';
            default:        return '○';
        }
    }
}

if (!function_exists('oc_confidence_band')) {
    /**
     * Confidence as a word, not a percentage. "72% confident" invites the reader
     * to treat the number as precise; a band does not. 35-scoring-model.md §2.2.
     *
     * @param int|null $confidence
     * @return string
     */
    function oc_confidence_band($confidence)
    {
        if ($confidence === null)  { return 'unknown'; }
        if (oc_below_floor($confidence)) { return 'insufficient'; }
        if ($confidence >= 80) { return 'high'; }
        if ($confidence >= 60) { return 'good'; }
        return 'fair';
    }
}

if (!function_exists('oc_confidence_bars')) {
    /**
     * 4-bar confidence meter markup, as in the prototype.
     *
     * @param int|null $confidence
     * @return string
     */
    function oc_confidence_bars($confidence)
    {
        $c  = (int) $confidence;
        $on = $c >= 80 ? 4 : ($c >= 60 ? 3 : ($c >= 45 ? 2 : 1));
        $h  = '<span class="confbars" aria-hidden="true">';
        for ($i = 1; $i <= 4; $i++) {
            $h .= '<i class="' . ($i <= $on ? '' : 'off') . '" style="height:' . (3 + $i * 2) . 'px"></i>';
        }
        return $h . '</span>';
    }
}

if (!function_exists('oc_unit')) {
    /**
     * The unit, in words (rule 2). Anything user-facing that names the measure
     * calls this rather than writing its own string — which is what makes the
     * "never say empty shop" rule testable instead of aspirational.
     *
     * @return string
     */
    function oc_unit()
    {
        return (string) get_instance()->config->item('oc_unit_label');
    }
}

if (!function_exists('oc_slots_phrase')) {
    /**
     * "38 online-bookable open slots" — the count and the unit, together, so the
     * qualifier can never be dropped in the copy-paste between screens.
     *
     * @param int $open
     * @return string
     */
    function oc_slots_phrase($open)
    {
        return number_format((int) $open) . ' ' . oc_unit();
    }
}

if (!function_exists('oc_data_age')) {
    /**
     * Human data age (rule 6).
     *
     * @param string|null $ts MySQL datetime
     * @return string
     */
    function oc_data_age($ts)
    {
        if (empty($ts)) { return 'never scanned'; }
        $t = strtotime($ts);
        if (!$t) { return 'unknown'; }
        $d = time() - $t;
        if ($d < 3600)  { return max(1, (int) floor($d / 60)) . 'm ago'; }
        if ($d < 86400) { return (int) floor($d / 3600) . 'h ago'; }
        return (int) floor($d / 86400) . 'd ago';
    }
}

if (!function_exists('oc_is_stale')) {
    /**
     * Is this observation stale enough to need the banner? (rule 6, FR-3.5)
     *
     * @param string|null $ts
     * @return bool
     */
    function oc_is_stale($ts)
    {
        if (empty($ts)) { return true; }
        $t = strtotime($ts);
        if (!$t) { return true; }
        $hours = (int) get_instance()->config->item('oc_stale_after_hours');
        return (time() - $t) > ($hours * 3600);
    }
}

if (!function_exists('oc_next_available')) {
    /**
     * Days-to-next as a phrase. This is the Slammed bucket's headline — "next
     * available: 19 days" is the whole capacity pitch in four words.
     *
     * @param int|null $days
     * @return string
     */
    function oc_next_available($days)
    {
        if ($days === null) { return 'unknown'; }
        $days = (int) $days;
        if ($days <= 0) { return 'today'; }
        if ($days === 1) { return 'tomorrow'; }
        return $days . ' days';
    }
}

if (!function_exists('oc_csrf')) {
    /**
     * The hidden CSRF field every POST form in this module needs.
     *
     * CI3 has csrf_protection ON, and the OpenChair shell is dependency-free —
     * it does not load the host engine's jQuery prefilter that injected this
     * token automatically on legacy pages. So forms here emit it themselves,
     * server-side, which also means every action keeps working with JavaScript
     * off. That matters for FR-5.4: the phone view a consultant uses standing
     * outside a shop, on bad signal, where a JS bundle may simply not arrive.
     *
     * @return string
     */
    function oc_csrf()
    {
        $CI = get_instance();
        return '<input type="hidden" name="' . $CI->security->get_csrf_token_name()
             . '" value="' . $CI->security->get_csrf_hash() . '">';
    }
}

// ── Chair Score v1.0 (docs/60-signature-visuals.md §4.1) ─────────────────────
//
// The signature visuals replace the abstract Vacancy Score with the number an
// owner is proud or ashamed of: out of every 100 chair-hours put online, how many
// got booked. It is the SAME Meter observation, inverted — no new scoring math —
// so every screen and every card that shows it goes through these functions and
// a chip, a pin, a pill and a card can never disagree about one business.

if (!function_exists('oc_chair_score')) {
    /**
     * Chair Score 0..100 from the Meter's vacancy, or null when there is none.
     * Ungated — callers that PRINT it use oc_chair_score_or_gap().
     *
     * @param int|null $vacancy_bp 0..10000
     * @return int|null
     */
    function oc_chair_score($vacancy_bp)
    {
        if ($vacancy_bp === null || $vacancy_bp === '') { return null; }
        return max(0, min(100, 100 - (int) round(((int) $vacancy_bp) / 100)));
    }
}

if (!function_exists('oc_chair_score_or_gap')) {
    /**
     * The Chair Score as printable text — or the honest gap below the confidence
     * floor (rule 3). Mirrors oc_score_or_gap(): a string, so nobody can do
     * arithmetic on "—" and print the result.
     *
     * @param int|null $vacancy_bp
     * @param int|null $confidence
     * @return string
     */
    function oc_chair_score_or_gap($vacancy_bp, $confidence)
    {
        if (oc_below_floor($confidence) || $vacancy_bp === null) { return '—'; }
        return (string) oc_chair_score($vacancy_bp);
    }
}

if (!function_exists('oc_chair_band')) {
    /**
     * The band for a Chair Score: hungry | steady | slammed, or null when there
     * is nothing we would stand behind.
     *
     * Hungry < 50, Steady 50–84, Slammed 85+ (PDF p.6). Lead time overrides the
     * score: ten days to a new client's first opening is Slammed whatever the
     * grid says (35-scoring-model.md §4.2).
     *
     * @param int|null $score        0..100 (oc_chair_score)
     * @param int|null $days_to_next first opening for a new client
     * @param int|null $confidence   when given, below the floor ⇒ null
     * @return string|null
     */
    function oc_chair_band($score, $days_to_next = null, $confidence = null)
    {
        if ($score === null) { return null; }
        if (func_num_args() >= 3 && oc_below_floor($confidence)) { return null; }
        $CI   = get_instance();
        $low  = (int) ($CI->config->item('oc_chair_hungry_below') ?: 50);
        $high = (int) ($CI->config->item('oc_chair_slammed_from') ?: 85);
        $wait = (int) ($CI->config->item('oc_chair_wait_slammed_days') ?: 10);

        if ($days_to_next !== null && (int) $days_to_next >= $wait) { return 'slammed'; }
        if ((int) $score >= $high) { return 'slammed'; }
        if ((int) $score <  $low)  { return 'hungry'; }
        return 'steady';
    }
}

if (!function_exists('oc_chair_band_for')) {
    /**
     * The band for a business row — the one call views make.
     *
     * @param object $b an oc_business row (latest_* columns)
     * @return string|null
     */
    function oc_chair_band_for($b)
    {
        return oc_chair_band(
            oc_chair_score($b->latest_vacancy_bp ?? null),
            isset($b->latest_days_to_next) ? $b->latest_days_to_next : null,
            isset($b->latest_confidence) ? $b->latest_confidence : null
        );
    }
}

if (!function_exists('oc_chair_band_label')) {
    /**
     * @param string|null $band
     * @return string
     */
    function oc_chair_band_label($band)
    {
        switch ($band) {
            case 'hungry':  return 'Hungry';
            case 'steady':  return 'Steady';
            case 'slammed': return 'Slammed';
            default:        return 'Not enough data yet';
        }
    }
}

if (!function_exists('oc_chair_band_need')) {
    /**
     * What the band means to the owner, in their words (PDF p.1/p.2/p.6).
     *
     * @param string|null $band
     * @return string
     */
    function oc_chair_band_need($band)
    {
        switch ($band) {
            case 'hungry':  return 'needs customers';
            case 'steady':  return 'uneven week';
            case 'slammed': return 'needs more capacity';
            default:        return 'still checking';
        }
    }
}

if (!function_exists('oc_chair_band_class')) {
    /**
     * CSS modifier for a band — reuses the shell's hot / mid / cool / low so the
     * existing pills, pins and chips colour the same way. Every caller also
     * prints the label (NFR-8, never meaning by colour alone).
     *
     * @param string|null $band
     * @return string
     */
    function oc_chair_band_class($band)
    {
        switch ($band) {
            case 'hungry':  return 'hot';
            case 'steady':  return 'mid';
            case 'slammed': return 'cool';
            default:        return 'low';
        }
    }
}

if (!function_exists('oc_chair_margin')) {
    /**
     * The ± on a Chair Score. Widens as confidence falls, for the same reason the
     * money is a range: a shaky read and a solid one must not look equally sure.
     *
     * @param int|null $confidence
     * @return int
     */
    function oc_chair_margin($confidence)
    {
        $c = max(0, min(100, (int) $confidence));
        return max(2, (int) round((100 - $c) / 2.5));
    }
}

if (!function_exists('oc_method_version')) {
    /** @return string e.g. "Chair Score v1.0" — printed on every card */
    function oc_method_version()
    {
        $v = (string) get_instance()->config->item('oc_chair_method');
        return 'Chair Score ' . ($v !== '' ? $v : 'v1.0');
    }
}

if (!function_exists('oc_money_short')) {
    /**
     * Compact money for small tiles: "$2.9k". Used ONLY as one end of a range —
     * see oc_range_short().
     *
     * @param int $cents
     * @return string
     */
    function oc_money_short($cents)
    {
        $d = (int) round($cents / 100);
        if ($d < 1000) { return '$' . $d; }
        $k = $d / 1000;
        return '$' . ($k >= 10 ? (string) (int) round($k) : rtrim(rtrim(number_format($k, 1), '0'), '.')) . 'k';
    }
}

if (!function_exists('oc_range_short')) {
    /**
     * "$2.9k–4.1k" — a compact RANGE for tiles where the full one will not fit
     * (rule 4 still holds: two ends, never one).
     *
     * @param int $lo_cents
     * @param int $hi_cents
     * @return string
     */
    function oc_range_short($lo_cents, $hi_cents = null)
    {
        // A row scored before oc_008 has no stored high end. Take it from the
        // same fallback oc_revenue_range() uses, never from a ratio in a view.
        if ($hi_cents === null || (int) $hi_cents <= (int) $lo_cents) {
            $CI = get_instance();
            $lo = (float) $CI->config->item('oc_sellthrough_low') ?: 0.15;
            $hi = (float) $CI->config->item('oc_sellthrough_high') ?: 0.35;
            $hi_cents = (int) round(((int) $lo_cents) * ($hi / $lo));
        }
        return oc_money_short($lo_cents) . '–' . ltrim(oc_money_short($hi_cents), '$');
    }
}

if (!function_exists('oc_range_tight')) {
    /**
     * "$2,890–4,125" — the receipt/card form: one currency sign, en dash, no
     * spaces. Still two ends (rule 4).
     *
     * @param int $lo_cents
     * @param int $hi_cents
     * @return string
     */
    function oc_range_tight($lo_cents, $hi_cents)
    {
        return oc_money($lo_cents) . '–' . ltrim(oc_money($hi_cents), '$');
    }
}

if (!function_exists('oc_checked_phrase')) {
    /**
     * "Checked Oct 15, 2026 (2 days ago)" — data age in the words a card prints
     * (rule 6).
     *
     * @param string|null $ts
     * @return string
     */
    function oc_checked_phrase($ts)
    {
        if (empty($ts) || !strtotime($ts)) { return 'Not checked yet'; }
        $t    = strtotime($ts);
        $days = (int) floor((time() - $t) / 86400);
        $ago  = $days <= 0 ? 'today' : ($days === 1 ? '1 day ago' : $days . ' days ago');
        return 'Checked ' . date('M j, Y', $t) . ' (' . $ago . ')';
    }
}

if (!function_exists('oc_chair_glyph')) {
    /**
     * The non-colour mark for a band (NFR-8) — same shapes as oc_bucket_glyph().
     *
     * @param string|null $band
     * @return string
     */
    function oc_chair_glyph($band)
    {
        switch ($band) {
            case 'hungry':  return '▲';
            case 'slammed': return '●';
            case 'steady':  return '■';
            default:        return '○';
        }
    }
}
