<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Oc_pitch_lib — generates the pitch copy.
 *
 * One place produces every user-facing sentence about a business, which is what
 * makes the wording lock (rule 2) and the compliance guardrails testable rather
 * than aspirational. A view that wrote its own sentence would be outside both.
 *
 * ────────────────────────────────────────────────────────────────────────────
 * WHAT THIS COPY MAY NOT DO
 * ────────────────────────────────────────────────────────────────────────────
 * Every line here goes on paper a consultant hands to a stranger, so:
 *
 *   - No earnings claim. "I can fill those" is a service offer; "you will make
 *     $2,340" is an income claim, and an FTC problem for the vendor as well as
 *     the user (50-risk §A7.3).
 *   - No "empty shop". The unit is online-bookable open slots — always, and in
 *     the artifact as much as the UI, because the artifact is what leaves the
 *     building.
 *   - Never a bare figure. Ranges only, with the source named, and the caveat
 *     block travels WITH the number rather than being a footer someone can
 *     delete before printing.
 *   - Nothing that reads as sent BY the product. The consultant is the sender;
 *     every draft is written in their first person.
 */
class Oc_pitch_lib
{
    /** @var CI_Controller */
    protected $CI;

    public function __construct()
    {
        $this->CI =& get_instance();
        $this->CI->load->helper('openchair/oc_format');
    }

    /**
     * Build every copy block for one business and variant.
     *
     * @param object $b        the oc_business row
     * @param string $variant  hungry|slammed
     * @return array
     */
    public function build($b, $variant)
    {
        $hungry = ($variant === 'hungry');
        $low    = oc_below_floor($b->latest_confidence);

        $open  = (int) $b->latest_open_slots;
        $total = (int) $b->latest_bookable_slots;
        $lead  = oc_next_available($b->latest_days_to_next);
        $money = $low ? null : oc_revenue_range($b->latest_empty_chair_cents, $b->latest_empty_chair_hi_cents);
        $offer = $low ? null : oc_recoverable_range($b->latest_empty_chair_cents);

        $headline = $hungry
            ? 'You have ' . number_format($open) . ' open slots this week. I can fill them.'
            : 'You are booked out ' . $lead . '. That is costing you.';

        $opening = $hungry
            ? 'Your booking page shows ' . oc_slots_phrase($open) . ' out of ' . number_format($total)
              . ' across ' . (int) $b->staff_count . ' chairs over the next seven days.'
            : 'Your next online opening is ' . $lead . ' away. Everyone who checks your page this week '
              . 'and cannot get in goes somewhere else — and you never see them, so it never feels '
              . 'like a loss.';

        return [
            'variant'  => $variant,
            'headline' => $headline,
            'opening'  => $opening,
            'money'    => $money,
            'offer'    => $offer,
            'actions'  => $this->_actions($hungry),
            'caveat'   => $this->caveat($b),
            'email'    => $this->email($b, $variant, $headline, $opening, $money),
            'sms'      => $this->sms($b, $variant, $open, $lead),
            'body'     => $opening,
        ];
    }

    /**
     * The matched fix. Four concrete things, because "I can help with your
     * marketing" is the opener this product exists to replace.
     *
     * @param bool $hungry
     * @return array
     */
    private function _actions($hungry)
    {
        if ($hungry) {
            return [
                ['Midweek offer', 'Aimed squarely at the afternoons that sit empty, not a blanket discount.'],
                ['Google offer post', 'So the offer shows up where people already search for you.'],
                ['Review velocity', 'Ask every filled chair, so next week starts from a higher base.'],
                ['Last-minute list', 'A cancellation gets refilled instead of lost.'],
            ];
        }
        return [
            ['Waitlist automation', 'The people you turn away get captured instead of walking.'],
            ['Price review', 'A wait this long is the market telling you something.'],
            ['Deposit gate', 'Kills the no-shows that cost you an already-booked chair.'],
            ['Second chair or a hire', 'And the numbers to justify the decision.'],
        ];
    }

    /**
     * The caveat that travels WITH the number.
     *
     * Not a footer. 40-ux-spec.md P3: if a consultant repeats a figure that
     * turns out to be a staff holiday, they lose the room and blame us. This
     * sentence is what keeps the number defensible when the owner pushes back,
     * which they will, in the first thirty seconds.
     *
     * @param object $b
     * @return string
     */
    public function caveat($b)
    {
        return 'Based on ' . oc_unit() . ' published on '
             . ($b->platform_name ?: 'your booking platform') . ', observed '
             . oc_data_age($b->last_good_scan_at) . '. It does not include walk-ins, phone bookings, '
             . 'or time you blocked out yourself, and it is not a statement about how busy your '
             . 'business is overall. Figures are a range, not a forecast.';
    }

    /**
     * The email draft. Written in the CONSULTANT's first person — the product is
     * not a party to this message and must not read like one.
     *
     * @return array{subject: string, body: string}
     */
    public function email($b, $variant, $headline, $opening, $money)
    {
        $subject = ($variant === 'hungry')
            ? number_format((int) $b->latest_open_slots) . ' open slots on your booking page this week'
            : 'Your next opening is ' . oc_next_available($b->latest_days_to_next) . ' out';

        $body  = "Hi,\n\n";
        $body .= $opening . "\n\n";
        if ($money !== null) {
            $body .= ($variant === 'hungry')
                ? "At your own published prices that is roughly {$money} of unsold time — before any "
                . "assumption about how much of it could actually be sold.\n\n"
                : "At your own published prices, the demand you are turning away is worth roughly "
                . "{$money} a week.\n\n";
        }
        $body .= ($variant === 'hungry')
            ? "I fill midweek gaps for shops like yours. If it is useful I will show you exactly which "
            . "hours are chronically open and what I would run against them.\n\n"
            : "I help shops in your position capture the people they are turning away, rather than "
            . "adding more hours. Worth fifteen minutes?\n\n";
        $body .= "— \n\n" . $this->caveat($b) . "\n";

        return ['subject' => $subject, 'body' => $body];
    }

    /**
     * The SMS draft.
     *
     * Kept under 320 characters (two segments) and opening with who is texting,
     * because an unidentified cold text is both a bad opener and a compliance
     * problem. The consultant sends it from their own number, on their own
     * footing, having checked their own obligations — this is a draft, not a
     * dispatch.
     *
     * @return string
     */
    public function sms($b, $variant, $open, $lead)
    {
        $msg = ($variant === 'hungry')
            ? "Hi — I noticed " . number_format($open) . " open slots on your booking page this week, "
            . "mostly midweek afternoons. I fill those for local shops. Worth a quick chat?"
            : "Hi — your booking page shows the next opening is " . $lead . " out. I help shops capture "
            . "the customers that turns away. Worth a quick chat?";

        return mb_substr($msg, 0, 320);
    }

    /**
     * Phrases that must never reach an artifact.
     *
     * Used by the module's integrity suite, so the list is data rather than a
     * comment someone can drift away from.
     *
     * @return string[]
     */
    public static function banned_phrases()
    {
        return [
            'empty shop',       // rule 2 — the wording lock
            'guaranteed',       // earnings/outcome claim
            'you will make',
            'risk-free',
            'we contacted',     // the product never contacts anyone
            'we emailed',
            'we called',
        ];
    }
}
