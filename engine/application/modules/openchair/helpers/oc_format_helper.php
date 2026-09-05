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
