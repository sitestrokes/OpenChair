/**
 * @file src/registry/contract.js
 * The adapter interface, and the validator that guards the boundary.
 *
 * ─────────────────────────────────────────────────────────────────────────────
 * THE INTERFACE HAS NO WRITE VERB, AND NO INTERACTION VERB
 * ─────────────────────────────────────────────────────────────────────────────
 * An adapter exports exactly one function: `read(ctx)`. It cannot book, hold,
 * cancel, sign in, submit a form or click anything, because there is no method
 * for any of those and no network primitive in its scope. It receives a fetcher
 * that only reads. This is layer 1 of the five-layer read-only guarantee; the
 * egress broker is layer 2 and does not trust this one.
 *
 * ─────────────────────────────────────────────────────────────────────────────
 * WHY THE VALIDATOR MATTERS MORE THAN THE ADAPTER
 * ─────────────────────────────────────────────────────────────────────────────
 * PRD R5: "adapter silently returns plausible-but-wrong data" is rated Critical,
 * and it is the failure mode with the largest blast radius — a crash is obvious,
 * but a selector that silently matches nothing produces a business that looks
 * 100% booked. That business gets bucketed Slammed, a consultant walks in and
 * tells a struggling shop owner they are too busy to take customers, and the
 * product is finished in that town.
 *
 * So every adapter's output is validated at this boundary before it is allowed
 * anywhere near the Meter, and the validator is deliberately suspicious of
 * output that is merely *shaped* correctly.
 */

/**
 * Capability classes, locked to 35-scoring-model.md §1.3. The class decides how
 * output converts to chair-minutes AND what confidence ceiling applies — a
 * next-available date can never support the same certainty as a full slot grid,
 * and pretending otherwise is how a product becomes confidently wrong.
 *
 * @readonly
 */
export const CAPABILITY = {
  /** Per-staff slot grid — the richest signal. Booksy, Vagaro, Fresha. */
  SLOT_GRID: 'C1_SLOT_GRID',
  /** Class capacity: "4 of 12 spots left". Momence, Mindbody. */
  CLASS_CAPACITY: 'C2_CLASS_CAPACITY',
  /** Arrival windows. Housecall Pro, Jobber. Weak; see PRD Q9. */
  ARRIVAL_WINDOW: 'C3_ARRIVAL_WINDOW',
  /** Reservation slots at fixed seatings. OpenTable, Resy — NOT shipped in v1. */
  RESERVATION: 'C4_RESERVATION',
  /** Next-available date only. The floor of what is usable. */
  NEXT_AVAILABLE: 'C5_NEXT_AVAILABLE',
};

/**
 * Confidence ceiling per class, as PERCENTAGES of the model's fractions.
 *
 * These are a mirror of CAPABILITY_CEILING in the Meter's model, kept here so
 * an adapter author can see the ceiling their class implies without importing
 * the scoring core across a process boundary. The Meter's copy is authoritative;
 * test/contract.test.js asserts the two agree, because a worker that believes a
 * class is worth more confidence than the Meter grants it would be reporting one
 * story while the product tells another.
 *
 * Note C1 is 95, not 100: even a perfect slot grid cannot see owner-placed
 * blocks, walk-ins or phone bookings. Only an owner-verified envelope reaches
 * 100, which is the post-sale consented path, not the prospecting one.
 */
export const CAPABILITY_CEILING = {
  C1_SLOT_GRID: 95,
  C2_CLASS_CAPACITY: 90,
  C3_ARRIVAL_WINDOW: 75,
  C4_RESERVATION: 65,
  C5_NEXT_AVAILABLE: 40,
};

/** Outcomes an adapter may report. Anything else is a bug, not a status. */
export const SCAN_STATUS = new Set([
  'ok', 'partial', 'blocked', 'captcha', 'not_found', 'timeout', 'error',
]);

/**
 * @typedef {Object} AdapterResult
 * @property {string}  status        one of SCAN_STATUS
 * @property {string}  capability    one of CAPABILITY
 * @property {string}  adapterVersion semver of the adapter that produced this
 * @property {number}  [horizonDays] how far ahead the page exposed availability
 * @property {number}  [slotMinutes] slot granularity actually observed
 * @property {Array<{staffHash: string, day: string, openMinutes: number, envelopeMinutes: number}>} [units]
 * @property {number}  [daysToNext]  days until the next available slot
 * @property {Array<{key: string, priceCents: number}>} [services]
 * @property {string}  [errorCode]
 * @property {string}  [errorNote]
 */

/** @typedef {{ok: true, result: AdapterResult} | {ok: false, code: string, reason: string}} ValidationResult */

/**
 * Validate one adapter's output before anything downstream sees it.
 *
 * Fails closed. An unrecognised shape is refused rather than passed through with
 * a shrug, because the entire point of this function is to catch the adapter
 * that is confidently wrong.
 *
 * @param {unknown} raw
 * @param {{expectCapability?: string}} [opts]
 * @returns {ValidationResult}
 */
export function validateResult(raw, opts = {}) {
  if (!raw || typeof raw !== 'object') {
    return { ok: false, code: 'NOT_AN_OBJECT', reason: 'Adapter returned no result object.' };
  }
  const r = /** @type {AdapterResult} */ (raw);

  if (!SCAN_STATUS.has(r.status)) {
    return { ok: false, code: 'BAD_STATUS', reason: `Unknown status '${r.status}'.` };
  }
  if (!Object.values(CAPABILITY).includes(r.capability)) {
    return { ok: false, code: 'BAD_CAPABILITY', reason: `Unknown capability '${r.capability}'.` };
  }
  if (opts.expectCapability && r.capability !== opts.expectCapability) {
    // An adapter that changes class between runs means the page changed shape
    // underneath it — which is exactly when its numbers stop meaning what the
    // scoring model thinks they mean.
    return {
      ok: false,
      code: 'CAPABILITY_DRIFT',
      reason: `Expected ${opts.expectCapability}, got ${r.capability}. The page shape changed.`,
    };
  }
  if (typeof r.adapterVersion !== 'string' || !r.adapterVersion) {
    return { ok: false, code: 'NO_VERSION', reason: 'Result is not attributable to an adapter version.' };
  }

  // A non-ok status carries no data. Nothing more to check.
  if (r.status !== 'ok' && r.status !== 'partial') {
    return { ok: true, result: r };
  }

  // ── C5 reports a date, not a grid. ────────────────────────────────────────
  if (r.capability === CAPABILITY.NEXT_AVAILABLE) {
    if (!Number.isInteger(r.daysToNext) || r.daysToNext < 0 || r.daysToNext > 400) {
      return { ok: false, code: 'BAD_DAYS_TO_NEXT', reason: `daysToNext ${r.daysToNext} is not a plausible lead time.` };
    }
    return { ok: true, result: r };
  }

  // ── Everything else must produce units. ───────────────────────────────────
  if (!Array.isArray(r.units) || r.units.length === 0) {
    // THE most important check here. An empty unit list from a successful read
    // is the silent-parse-failure signature: the fetch worked, the selector
    // matched nothing, and every downstream number becomes "fully booked".
    return {
      ok: false,
      code: 'NO_UNITS',
      reason: 'Status ok but zero units. A successful read with no grid is a parse failure, not a full shop.',
    };
  }

  for (const [i, u] of r.units.entries()) {
    if (typeof u?.staffHash !== 'string' || !u.staffHash) {
      return { ok: false, code: 'BAD_UNIT', reason: `unit[${i}] has no staffHash.` };
    }
    if (/^[A-Za-z ,.'-]{3,}$/.test(u.staffHash) && u.staffHash.includes(' ')) {
      // staffHash must be a hash, not a person. A name here would put personal
      // data into every snapshot (50-risk §A4.2).
      return { ok: false, code: 'STAFF_NAME_LEAK', reason: `unit[${i}].staffHash looks like a person's name.` };
    }
    if (!/^\d{4}-\d{2}-\d{2}$/.test(String(u.day))) {
      return { ok: false, code: 'BAD_DAY', reason: `unit[${i}].day '${u.day}' is not an ISO date.` };
    }
    const open = Number(u.openMinutes);
    const env = Number(u.envelopeMinutes);
    if (!Number.isFinite(open) || open < 0) {
      return { ok: false, code: 'BAD_OPEN', reason: `unit[${i}].openMinutes ${u.openMinutes} is invalid.` };
    }
    if (!Number.isFinite(env) || env <= 0) {
      return { ok: false, code: 'BAD_ENVELOPE', reason: `unit[${i}].envelopeMinutes ${u.envelopeMinutes} is invalid.` };
    }
    if (open > env) {
      return {
        ok: false,
        code: 'OPEN_EXCEEDS_ENVELOPE',
        reason: `unit[${i}] reports ${open} open minutes in a ${env}-minute day. More open time than the shop is bookable for means the parse is wrong.`,
      };
    }
    if (env > 24 * 60) {
      return { ok: false, code: 'ENVELOPE_TOO_LONG', reason: `unit[${i}] envelope ${env}min exceeds a day.` };
    }
  }

  if (r.slotMinutes !== undefined && (!Number.isInteger(r.slotMinutes) || r.slotMinutes < 5 || r.slotMinutes > 240)) {
    return { ok: false, code: 'BAD_SLOT_MINUTES', reason: `slotMinutes ${r.slotMinutes} is implausible.` };
  }

  for (const [i, s] of (r.services ?? []).entries()) {
    if (!Number.isInteger(s?.priceCents) || s.priceCents < 0 || s.priceCents > 10_000_00) {
      return { ok: false, code: 'BAD_PRICE', reason: `service[${i}] price ${s?.priceCents} is implausible.` };
    }
  }

  return { ok: true, result: r };
}

/**
 * Check that a module actually implements the adapter contract — and, just as
 * importantly, that it does NOT implement anything it should not.
 *
 * @param {Record<string, unknown>} mod
 * @returns {{ok: boolean, reason?: string}}
 */
export function validateAdapterModule(mod) {
  if (typeof mod?.read !== 'function') {
    return { ok: false, reason: 'Adapter must export read(ctx).' };
  }
  if (typeof mod?.meta?.platformKey !== 'string' || typeof mod?.meta?.capability !== 'string') {
    return { ok: false, reason: 'Adapter must export meta { platformKey, capability, version }.' };
  }

  // The interface has no write verb. An adapter that grew one is a review
  // failure that must not reach production, whatever the author intended it for.
  const forbidden = ['book', 'hold', 'reserve', 'cancel', 'submit', 'login', 'signIn', 'post', 'click'];
  const found = forbidden.filter((f) => typeof mod[f] === 'function');
  if (found.length) {
    return { ok: false, reason: `Adapter exports forbidden verb(s): ${found.join(', ')}. The interface reads and nothing else.` };
  }

  return { ok: true };
}

export default { CAPABILITY, CAPABILITY_CEILING, SCAN_STATUS, validateResult, validateAdapterModule };
