/**
 * @file src/meter/score.js
 * The Meter's service wrapper: normalised grid in, published rows out.
 *
 * model.js holds the maths and is vendored from the reference implementation.
 * This file holds what a reference implementation should not: the database, and
 * the decision about what may be published.
 *
 * That split matters. The model can say "vacancy 0.29 at confidence 0.31"; only
 * this layer knows 0.31 is below the floor and the product must therefore show
 * the gap rather than the number (05-prd.md §8 rule 3). One place enforces the
 * refusal, for every caller.
 */

import {
  vacancyScore, confidenceScore, priceRate, emptyChairRevenue,
  bucketBusiness, CAPABILITY_CEILING, MODEL_VERSION,
} from './model.js';
import { query } from '../db.js';
import config from '../config.js';

/**
 * The model's bucket vocabulary is not the schema's.
 *
 *   model            oc_vacancy_daily.bucket ENUM
 *   ---------------  ----------------------------
 *   hungry           hungry
 *   steady           neutral      ← different word for the same middle bucket
 *   slammed          slammed
 *   unrated          NULL         ← no enum member; absence IS the value
 *
 * 'unrated' is what the model returns when confidence refuses. Coercing it to
 * 'neutral' would silently promote "we don't know" into "we looked, it's
 * average" — which is the confidently-wrong failure the whole confidence model
 * exists to prevent. It must be NULL, and the UI must render it as no score.
 *
 * @param {string|null|undefined} b
 * @returns {string|null}
 */
export function toSchemaBucket(b) {
  switch (b) {
    case 'hungry':  return 'hungry';
    case 'slammed': return 'slammed';
    case 'steady':  return 'neutral';
    default:        return null;     // 'unrated' and anything unrecognised
  }
}

/**
 * A normalised grid → the observation shape the model expects.
 *
 * Two conversions worth naming, because both are easy to get silently wrong:
 *   - the model uses `dayOffset` 1..H (tomorrow is 1); an offset of 0 or >H is
 *     dropped, so an off-by-one here empties the whole calculation;
 *   - the model's field names are `openMin`/`envelopeMin`, not the worker's
 *     `openMinutes`/`envelopeMinutes`. Passing the wrong ones yields NaN sums
 *     that quietly become a vacancy of 0 — a fully-booked shop.
 *
 * @param {Object} grid       normalised grid (byDay keyed by ISO date)
 * @param {string} baseDate   ISO date the horizon starts from
 * @returns {Array<{dayOffset:number, unitId:string, openMin:number, envelopeMin:number}>}
 */
export function toObservations(grid, baseDate) {
  const base = new Date(`${baseDate}T00:00:00Z`).getTime();
  /** @type {any[]} */
  const obs = [];

  for (const [day, cell] of Object.entries(grid.byDay ?? {})) {
    // No +1. `baseDate` is the day the horizon is measured FROM (today), so the
    // difference in days IS the model's offset: tomorrow is 1. Adding one on top
    // pushed everything a day out and silently dropped the last day of the
    // horizon past H — caught by the dayOffset test, which exists for this.
    const offset = Math.round((new Date(`${day}T00:00:00Z`).getTime() - base) / 86400000);
    obs.push({
      dayOffset: offset,
      unitId: `d${offset}`,   // per-day roll-up; per-chair dispersion needs unit-level input
      openMin: cell.open,
      envelopeMin: cell.envelope,
    });
  }
  return obs;
}

/**
 * Open chair-minutes on days 1..H after baseDate — the scoring window.
 *
 * Null when the grid has no per-day breakdown (a C5 next-available read), in
 * which case the caller falls back to the grid total.
 *
 * @param {Object} grid
 * @param {string} baseDate ISO date
 * @param {number} H
 * @returns {number|null}
 */
export function openMinutesInWindow(grid, baseDate, H, field = 'open') {
  const byDay = grid?.byDay;
  if (!byDay || !Object.keys(byDay).length) return null;
  const base = new Date(`${baseDate}T00:00:00Z`).getTime();
  let sum = 0;
  for (const [day, cell] of Object.entries(byDay)) {
    const off = Math.round((new Date(`${day}T00:00:00Z`).getTime() - base) / 86400000);
    if (off >= 1 && off <= H) sum += Number(cell?.[field] ?? 0);
  }
  return sum;
}

/**
 * @typedef {Object} MeterOutput
 * @property {number}  vacancyBp
 * @property {number}  confidence          0..100
 * @property {boolean} publishable         false ⇒ show the gap, never the number
 * @property {boolean} showDollars         the model's own money gate
 * @property {number|null} emptyChairCentsLow
 * @property {number|null} emptyChairCentsHigh
 * @property {string|null} bucket          schema vocabulary; null = unrated
 * @property {number|null} daysToNext
 * @property {string}  modelVersion
 */

/**
 * Score one observation.
 *
 * @param {Object} input
 * @param {Object} input.grid
 * @param {Object} input.business
 * @param {Object} [input.context]
 * @returns {MeterOutput}
 */
export function score({ grid, business, context = {} }) {
  const H = context.horizonDays ?? 7;
  const capability = grid.capability;

  const obs = toObservations(grid, context.baseDate ?? new Date().toISOString().slice(0, 10));
  const vs = vacancyScore(obs, { H, closedDayOffsets: context.closedDayOffsets ?? [] });

  const conf = confidenceScore({
    capability,
    scanAgeHours: context.scanAgeHours ?? 0,
    observedWeeks: context.observedWeeks ?? 0,
    // 'default' means we are pricing off the niche fallback rather than the
    // business's own published menu — a materially weaker claim, and the model
    // caps evidence at 0.55 for it.
    envelopeSource: context.envelopeSource ?? 'observed',
    horizonCoverage: vs?.coverage ?? context.horizonCoverage ?? 1,
    rosterChurn: context.rosterChurn ?? 0,
    priorSuccessfulScans: context.priorSuccessfulScans ?? 0,
    anomalies: context.anomalies ?? [],
  });

  // Tiers are STRINGS in the model: T1 owner-verified, T2 the business's own
  // published menu, T3 a niche cohort estimate.
  const rate = priceRate({
    tier: context.priceTier ?? (business.price_source === 'menu' ? 'T2' : 'T3'),
    services: context.services ?? [],
    modalSlotMin: business.slot_minutes ?? 30,
    override: context.priceOverride ?? null,
    cohort: context.priceCohort ?? null,
  });

  // NOTE: the whole confidence OBJECT goes in, not the number — the model reads
  // .refuse and .dollarsAllowed off it to decide whether money may be shown at
  // all, independently of the numeric threshold.
  // Money covers the SAME window as the score: days 1..H after baseDate. The
  // grid's openMinutes spans the whole read horizon (14 days on Booksy), and
  // pricing that as "unsold this week" doubled the headline figure — the one
  // number an owner checks against their own week.
  const baseIso = context.baseDate ?? new Date().toISOString().slice(0, 10);
  const windowOpen = openMinutesInWindow(grid, baseIso, H);
  const windowEnvelope = openMinutesInWindow(grid, baseIso, H, 'envelope');

  const rev = emptyChairRevenue({
    openMinutes: windowOpen ?? grid.openMinutes,
    rate,
    confidence: conf,
    sellThrough: [config.sellThroughLow, config.sellThroughHigh],
  });

  const bucket = bucketBusiness({
    V: vs?.V ?? 0,
    leadDays: grid.daysToNext,
    cohortVacancies: context.cohortVacancies ?? null,
    prevBucket: context.prevBucket ?? null,
    confidence: conf,
  });

  const confidence = Math.max(0, Math.min(100, Math.round((conf?.C ?? 0) * 100)));

  return {
    vacancyBp: vs?.V == null ? 0 : Math.max(0, Math.min(10000, Math.round(vs.V * 10000))),
    confidence,
    // Two independent gates, and both must pass. Ours is the published floor;
    // the model's `refuse` fires on conditions a threshold cannot express (a
    // total blackout, an unusable envelope). Either one alone would let a case
    // through that the other catches.
    publishable: confidence >= config.confidenceFloor && !conf?.refuse,
    showDollars: rev?.showDollars === true,
    whyNoDollars: rev?.showDollars === true ? null : (rev?.why ?? null),
    // The scoring window's minutes — what "this week" means on every screen.
    openMinutes: windowOpen ?? grid.openMinutes,
    envelopeMinutes: windowEnvelope ?? grid.envelopeMinutes,
    // TWO figures, and they are not interchangeable (35-scoring-model.md §3.1):
    //
    //   unsold      openMinutes × price band. THE HEADLINE. A pure arithmetic
    //               restatement of observable facts — minutes on their page
    //               times prices on their page. Big, true, unattackable.
    //   recoverable unsold(low) × 15–35% sell-through. Sizes the offer.
    //               NEVER the headline.
    //
    // The split exists because a single "vacancy × price" number silently
    // asserts 100% sell-through, which nobody achieves and any owner will call
    // out inside thirty seconds. Collapsing these back into one figure would
    // reintroduce exactly the claim the model was built to avoid.
    unsoldCentsLow:      rev?.showDollars ? Math.round(rev.unsold.lo * 100) : null,
    unsoldCentsHigh:     rev?.showDollars ? Math.round(rev.unsold.hi * 100) : null,
    recoverableCentsLow: rev?.showDollars ? Math.round(rev.recoverable.lo * 100) : null,
    recoverableCentsHigh:rev?.showDollars ? Math.round(rev.recoverable.hi * 100) : null,
    priceTier: rev?.rate?.tier ?? null,
    priceTag: rev?.rate?.tag ?? null,
    bucket: toSchemaBucket(bucket?.bucket),
    // The model decides not just IF a number may be shown but HOW MUCH: at
    // medium confidence it permits a score and a WIDER range; at low, only the
    // bucket and a slot count with no dollars at all. Surfacing it lets the UI
    // degrade the way the model intends rather than treating publishable as a
    // single on/off.
    display: conf?.display ?? 'nothing_rescan',
    band: conf?.band ?? 'refuse',
    refusals: conf?.refusals ?? [],
    bucketReason: bucket?.reason ?? null,
    daysToNext: grid.daysToNext,
    capabilityCeiling: Math.round((CAPABILITY_CEILING[capability] ?? 0) * 100),
    modelVersion: MODEL_VERSION,
  };
}

/**
 * Write one day's scores.
 *
 * Two writes, deliberately:
 *   oc_vacancy_daily  the time series the Ledger and Dead-Zone Map read
 *   oc_business       a denormalised projection of the LATEST observation
 *
 * The projection exists so "47 pins, reddest first" is one indexed scan of one
 * table rather than a join against the series (the Q1 index in 30-data-model.md
 * §6). It is a cache this function owns; nothing else writes it, and the series
 * stays the source of truth.
 *
 * UNPUBLISHABLE OBSERVATIONS ARE STILL STORED. A low-confidence reading is real
 * data: it feeds the trailing envelope, the Ledger and the canary. The
 * confidence gate governs DISPLAY, not retention. Discarding it would make the
 * product forget the businesses it finds hardest to read — which are exactly
 * the ones worth watching.
 *
 * @param {Object} args
 * @returns {Promise<void>}
 */
export async function persist({ businessId, observedOn, out, grid, scanRunId = null, avgPriceCents = null, slotMinutes = 30 }) {
  const slot = slotMinutes > 0 ? slotMinutes : 30;
  // Slots are minutes ÷ slot length — a display convenience. Minutes are the
  // unit of record; these columns exist because "38 open slots" is what a
  // consultant says out loud, not "1,140 chair-minutes".
  // The scoring window (out.*), not the whole read horizon: the pitch says
  // "open slots this week", and a 14-day count there contradicted the Chair
  // Week and the money beside it.
  const openSlots = Math.round((out.openMinutes ?? grid.openMinutes ?? 0) / slot);
  const bookableSlots = Math.round((out.envelopeMinutes ?? grid.envelopeMinutes ?? 0) / slot);

  await query(
    `INSERT INTO oc_vacancy_daily
       (business_id, observed_on, scan_run_id, horizon_days, open_slots, bookable_slots,
        vacancy_bp, staff_count, avg_price_cents, empty_chair_cents,
        empty_chair_hi_cents, days_to_next, bucket, confidence)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, COALESCE(?, 'neutral'), ?)
     ON DUPLICATE KEY UPDATE
       scan_run_id       = VALUES(scan_run_id),
       open_slots        = VALUES(open_slots),
       bookable_slots    = VALUES(bookable_slots),
       vacancy_bp        = VALUES(vacancy_bp),
       staff_count       = VALUES(staff_count),
       avg_price_cents   = VALUES(avg_price_cents),
       empty_chair_cents    = VALUES(empty_chair_cents),
       empty_chair_hi_cents = VALUES(empty_chair_hi_cents),
       days_to_next         = VALUES(days_to_next),
       bucket            = VALUES(bucket),
       confidence        = VALUES(confidence)`,
    [
      businessId, observedOn, scanRunId, 7,
      openSlots, bookableSlots, out.vacancyBp,
      grid.staffCount ?? null, avgPriceCents,
      out.unsoldCentsLow ?? 0, out.unsoldCentsHigh ?? 0, out.daysToNext,
      out.bucket, out.confidence,
    ],
  );

  // The projection keeps NULL for an unrated bucket — the map must be able to
  // render "no score" rather than inventing a middle one.
  await query(
    `UPDATE oc_business
        SET latest_observed_on       = ?,
            latest_vacancy_bp        = ?,
            latest_open_slots        = ?,
            latest_bookable_slots    = ?,
            latest_empty_chair_cents    = ?,
            latest_empty_chair_hi_cents = ?,
            latest_days_to_next         = ?,
            latest_bucket            = ?,
            latest_confidence        = ?
      WHERE id = ?`,
    [
      observedOn, out.vacancyBp, openSlots, bookableSlots,
      out.unsoldCentsLow, out.unsoldCentsHigh, out.daysToNext, out.bucket, out.confidence,
      businessId,
    ],
  );
}

export default { score, persist, toObservations, toSchemaBucket, openMinutesInWindow };
