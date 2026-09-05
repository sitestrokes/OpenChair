/**
 * @file src/normalise/chairMinutes.js
 * Adapter output → the canonical unit.
 *
 * ─────────────────────────────────────────────────────────────────────────────
 * WHY CHAIR-MINUTES AND NOT "SLOTS"
 * ─────────────────────────────────────────────────────────────────────────────
 * The brief's formula is open slots ÷ bookable slots. It does not survive
 * contact with real platforms (35-scoring-model.md §1.1): a shop offering 15-min
 * beard trims and one offering 90-min colour treatments produce wildly different
 * slot counts for identical emptiness, and comparing them ranks the market by
 * service menu rather than by vacancy.
 *
 * Minutes are the unit that means the same thing everywhere. A chair-minute is
 * one bookable resource, unavailable for one minute — comparable across
 * platforms, service menus and business types.
 *
 * ─────────────────────────────────────────────────────────────────────────────
 * THE DENOMINATOR IS THE PUBLISHED CAPACITY ENVELOPE, NOT CLOCK HOURS
 * ─────────────────────────────────────────────────────────────────────────────
 * Dividing by 24h, or by "9-5", would make every business look mostly empty and
 * make the number worthless. The envelope is the span the business itself
 * publishes as bookable — so a barber who works Tuesday to Saturday is not
 * scored as 28% vacant for being closed on Mondays.
 */

import { CAPABILITY } from '../registry/contract.js';

/**
 * @typedef {Object} NormalisedGrid
 * @property {string} capability
 * @property {number} openMinutes      total open chair-minutes over the horizon
 * @property {number} envelopeMinutes  total published bookable chair-minutes
 * @property {number} vacancyBp        openMinutes / envelopeMinutes, basis points
 * @property {number} staffCount       distinct bookable resources seen
 * @property {number} dayCount         distinct days observed
 * @property {number|null} daysToNext
 * @property {Record<string, {open: number, envelope: number}>} byDay
 */

/**
 * Basis points, not a float.
 *
 * The Ledger's headline claim is "fill rate went 41% → 68%", shown to a paying
 * client every month. Integers mean that number cannot drift by a hundredth
 * between two runs over the same data and make us look careless about the one
 * figure we are being paid for.
 *
 * @param {number} open
 * @param {number} envelope
 * @returns {number} 0..10000
 */
export function toBasisPoints(open, envelope) {
  if (!(envelope > 0)) return 0;
  return Math.max(0, Math.min(10000, Math.round((open / envelope) * 10000)));
}

/**
 * Collapse validated adapter units into one grid.
 *
 * @param {import('../registry/contract.js').AdapterResult} result
 * @returns {NormalisedGrid}
 */
export function normalise(result) {
  const capability = result.capability;

  // C5 has no grid. It reports a lead time, and the scoring model converts that
  // to a vacancy PROXY with a hard confidence ceiling — a next-available date is
  // real evidence of scarcity but tells us nothing about how empty the week is.
  if (capability === CAPABILITY.NEXT_AVAILABLE) {
    return {
      capability,
      openMinutes: 0,
      envelopeMinutes: 0,
      vacancyBp: 0,
      staffCount: 0,
      dayCount: 0,
      daysToNext: Number.isInteger(result.daysToNext) ? result.daysToNext : null,
      byDay: {},
      proxyOnly: true,
    };
  }

  /** @type {Record<string, {open: number, envelope: number}>} */
  const byDay = {};
  const staff = new Set();
  let openMinutes = 0;
  let envelopeMinutes = 0;

  for (const u of result.units ?? []) {
    const open = Number(u.openMinutes);
    const env = Number(u.envelopeMinutes);

    staff.add(u.staffHash);
    openMinutes += open;
    envelopeMinutes += env;

    if (!byDay[u.day]) byDay[u.day] = { open: 0, envelope: 0 };
    byDay[u.day].open += open;
    byDay[u.day].envelope += env;
  }

  return {
    capability,
    openMinutes,
    envelopeMinutes,
    vacancyBp: toBasisPoints(openMinutes, envelopeMinutes),
    staffCount: staff.size,
    dayCount: Object.keys(byDay).length,
    daysToNext: deriveDaysToNext(byDay, result),
    byDay,
    proxyOnly: false,
  };
}

/**
 * Days until the first day with any open time.
 *
 * This is the Slammed bucket's whole argument — "next available: 19 days" — so
 * it is derived from the observed grid rather than trusted from a page label
 * where a grid exists. A platform's own "next available" banner is frequently
 * stale or promotional.
 *
 * @param {Record<string, {open: number, envelope: number}>} byDay
 * @param {import('../registry/contract.js').AdapterResult} result
 * @returns {number|null}
 */
function deriveDaysToNext(byDay, result) {
  const days = Object.keys(byDay).sort();
  if (!days.length) return Number.isInteger(result.daysToNext) ? result.daysToNext : null;

  const first = new Date(`${days[0]}T00:00:00Z`).getTime();
  for (const d of days) {
    if (byDay[d].open > 0) {
      return Math.round((new Date(`${d}T00:00:00Z`).getTime() - first) / 86400000);
    }
  }

  // Nothing open anywhere in the horizon. That is NOT "0 days to next" and it is
  // not "never" — it is "further out than we can see", and the scoring model
  // must treat it as a lower bound rather than a measurement.
  return null;
}

/**
 * Convert a count of slots to minutes, for adapters that genuinely only expose
 * a count. Kept explicit so the assumption is visible at the call site rather
 * than buried: a slot count times a nominal length is an ESTIMATE, and it should
 * feed a lower confidence than a real grid.
 *
 * @param {number} slots
 * @param {number} slotMinutes
 * @returns {number}
 */
export function slotsToMinutes(slots, slotMinutes) {
  return Math.max(0, Math.round(Number(slots) * Number(slotMinutes)));
}

export default { normalise, toBasisPoints, slotsToMinutes };
