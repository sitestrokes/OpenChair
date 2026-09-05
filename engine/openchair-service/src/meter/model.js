/**
 * @file src/meter/model.js
 * The Meter's scoring core.
 *
 * ---------------------------------------------------------------------------
 * VENDORED FROM docs/meter-reference.mjs. DO NOT EDIT THE MATHS HERE.
 * ---------------------------------------------------------------------------
 * The reference implementation IS the specification: 35-scoring-model.md is
 * written against it and it carries its own worked examples and self-tests.
 * Re-implementing the maths for the service would create two versions of the
 * one thing in this product that must never disagree with itself, and they
 * would drift on the first calibration change.
 *
 * So it is copied verbatim and wrapped by score.js, which does the parts a
 * reference implementation should not: talk to a database, and decide what is
 * publishable.
 *
 * The phase gate -- "service output matches meter-reference.mjs on every worked
 * example" -- is therefore met by construction rather than by testing, and
 * test/meter.test.js asserts this copy has not drifted from its source.
 *
 * To change the model: edit docs/meter-reference.mjs, re-run its self-tests,
 * re-copy. The drift test fails until you do.
 *
 * ONE PATCHED LINE, marked VENDOR PATCH at the bottom of the file. See there.
 */

/**
 * OpenChair — Meter reference implementation
 * ------------------------------------------------------------------
 * Pure-function reference for the three Meter numbers:
 *   1. Vacancy Score        (0-100, cross-platform comparable)
 *   2. Confidence           (0-1, with a hard REFUSE gate)
 *   3. Empty-Chair Revenue  (a RANGE, never a point estimate)
 * plus bucketing, dead-zone detection and the Ledger trend.
 *
 * Plain ESM. Node 22. No TypeScript. No external dependencies.
 * Every function here is pure: same inputs -> same outputs, no I/O,
 * no clock reads (the caller injects `nowMs` / `scanAgeHours`).
 *
 * Run the demo:   node meter-reference.mjs
 * Run the tests:  node --test meter-reference.mjs
 *
 * Companion document: 35-scoring-model.md (the numbers printed by the
 * demo block at the bottom are pasted verbatim into that doc).
 */

/* ==================================================================
 * 0. CONSTANTS AND MODEL VERSION
 * ==================================================================
 * Every stored score MUST carry MODEL_VERSION. A report rendered under
 * v1 is never re-rendered under v2 without relabelling — otherwise the
 * Ledger's 8-week trend silently mixes two definitions of "fill rate",
 * which is the single easiest way to get caught lying to a client.
 */
export const MODEL_VERSION = 'meter-1.0.0';

/** Adapter capability classes — what the platform actually exposes. */
export const CAPABILITY = {
  C1_SLOT_GRID: 'C1_SLOT_GRID',        // Booksy, Vagaro, Fresha, GlossGenius, Square, Acuity
  C2_CLASS_CAPACITY: 'C2_CLASS_CAPACITY', // Mindbody, Momence ("4 of 12 spots left")
  C3_ARRIVAL_WINDOW: 'C3_ARRIVAL_WINDOW', // Housecall Pro, Jobber, ServiceTitan
  C4_RESERVATION: 'C4_RESERVATION',    // OpenTable, Resy (party-size dependent)
  C5_NEXT_AVAILABLE: 'C5_NEXT_AVAILABLE'  // Zocdoc-style "next available: 19 days"
};

/**
 * Confidence ceiling per capability class.
 * NOTE C1 tops out at 0.95, not 1.00: even a perfect slot grid cannot
 * show us owner-placed blocks, walk-ins, or phone bookings. Only an
 * owner-verified envelope + price lifts a business to 1.00.
 */
export const CAPABILITY_CEILING = {
  [CAPABILITY.C1_SLOT_GRID]: 0.95,
  [CAPABILITY.C2_CLASS_CAPACITY]: 0.90,
  [CAPABILITY.C3_ARRIVAL_WINDOW]: 0.75,
  [CAPABILITY.C4_RESERVATION]: 0.65,
  [CAPABILITY.C5_NEXT_AVAILABLE]: 0.40
};

/** Anomaly flags and their multiplicative confidence penalties. */
export const ANOMALY_PENALTY = {
  suspect_total_blackout: 0.50,  // V >= 0.97 every day -> closed / parse break / holiday
  suspect_walk_in: 0.60,         // grid pinned near-empty but lead time ~0 and stable
  release_limited: 0.85,         // grid only publishes k < H days
  captcha_degraded: 0.70,        // bot-mitigation served a partial page
  dst_unresolved: 0.85,          // scan spans a DST transition without tz metadata
  multi_location: 0.70,          // one page, staff at >1 address
  slot_pitch_changed: 0.90,      // modal slot duration moved week over week
  ghost_staff_present: 0.90      // a unit at 100% vacancy for 14+ days is in the set
};

/** Time-of-day bins for the Dead-Zone Map (business-local wall clock). */
export const TIME_BINS = [
  { i: 0, from: 7, to: 9, label: '7-9a' },
  { i: 1, from: 9, to: 11, label: '9-11a' },
  { i: 2, from: 11, to: 13, label: '11a-1p' },
  { i: 3, from: 13, to: 15, label: '1-3p' },
  { i: 4, from: 15, to: 17, label: '3-5p' },
  { i: 5, from: 17, to: 19, label: '5-7p' },
  { i: 6, from: 19, to: 21, label: '7-9p' }
];
export const DOW = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];

/* ==================================================================
 * 1. CANONICAL UNIT — CHAIR-MINUTES
 * ==================================================================
 * Every adapter, whatever it exposes, is normalised to a list of
 * Observations:
 *
 *   { unitId, dayOffset, envelopeMin, openMin }
 *
 *   unitId      a servable unit: one staff chair, one class seat-block,
 *               one crew, one table-turn. Stable across scans.
 *   dayOffset   1..H days ahead of the scan (0 = today is excluded;
 *               today is half-consumed and not comparable).
 *   envelopeMin PUBLISHED bookable minutes for that unit on that day
 *               (the denominator — see §2, this is NOT clock hours).
 *   openMin     bookable minutes still open.
 *
 * envelopeMin - openMin = booked/blocked minutes. We never observe
 * "booked" directly; it is always a residual. Say so on the report.
 */

/**
 * C1 — per-staff slot grid.
 * @param {object} p
 * @param {Array}  p.staffDays  [{ unitId, dayOffset, openSlots, slotMinutes, envelopeMin }]
 *        envelopeMin comes from the trailing capacity envelope (§2), NOT
 *        from today's snapshot — a fully booked day shows zero slots.
 */
export function fromSlotGrid({ staffDays }) {
  return staffDays.map((sd) => ({
    unitId: sd.unitId,
    dayOffset: sd.dayOffset,
    envelopeMin: sd.envelopeMin,
    openMin: sd.openSlots * sd.slotMinutes
  }));
}

/**
 * C2 — class schedule with capacity.
 * A class of duration D and capacity K is D*K chair-minutes of capacity;
 * S spots left is D*S open. Capacity, not headcount: a 20-person class
 * with 11 empty mats is 55% vacant regardless of how many mats fit.
 * @param {object} p
 * @param {Array}  p.classes [{ unitId, dayOffset, durationMin, capacity, spotsLeft }]
 */
export function fromClassSchedule({ classes }) {
  // Collapse all classes on the same day into one pseudo-unit per room/teacher.
  const out = [];
  for (const c of classes) {
    out.push({
      unitId: c.unitId,
      dayOffset: c.dayOffset,
      envelopeMin: c.durationMin * c.capacity,
      openMin: c.durationMin * Math.min(c.spotsLeft, c.capacity)
    });
  }
  return out;
}

/**
 * C3 — arrival windows with crew capacity (home services).
 * A 2-hour window served by 3 crews is 360 crew-minutes; each window is
 * either taken or not per crew.
 * @param {Array} p.windows [{ unitId, dayOffset, windowMin, crews, crewsFree }]
 */
export function fromArrivalWindows({ windows }) {
  return windows.map((w) => ({
    unitId: w.unitId,
    dayOffset: w.dayOffset,
    envelopeMin: w.windowMin * w.crews,
    openMin: w.windowMin * Math.min(w.crewsFree, w.crews)
  }));
}

/**
 * C4 — restaurant reservations. We see, for one party size, which times
 * have availability. We do NOT see table count, so a "unit" is one
 * seating time-point and capacity is 1 turn. The score is therefore a
 * SEATING-AVAILABILITY ratio, not a true occupancy ratio — hence the
 * 0.65 confidence ceiling. Always disclose the party size scanned.
 * @param {Array} p.seatings [{ unitId, dayOffset, turnMin, offered, available }]
 */
export function fromReservations({ seatings }) {
  return seatings.map((s) => ({
    unitId: s.unitId,
    dayOffset: s.dayOffset,
    envelopeMin: s.turnMin * s.offered,
    openMin: s.turnMin * Math.min(s.available, s.offered)
  }));
}

/**
 * C5 — "next available date" only. There is NO grid, so there are no
 * chair-minutes and we must not invent them. We map lead time to a
 * vacancy PROXY on a monotone, rank-preserving curve calibrated against
 * businesses that expose both a grid and a next-available date.
 *
 * The proxy is for ORDERING and BUCKETING only. §3 forbids turning it
 * into dollars.
 */
export const LEAD_TIME_ANCHORS = [
  { leadDays: 0, v: 0.75 }, { leadDays: 1, v: 0.55 }, { leadDays: 2, v: 0.45 },
  { leadDays: 3, v: 0.38 }, { leadDays: 5, v: 0.28 }, { leadDays: 7, v: 0.22 },
  { leadDays: 10, v: 0.15 }, { leadDays: 14, v: 0.10 }, { leadDays: 21, v: 0.06 },
  { leadDays: 30, v: 0.04 }
];

export function nextAvailableProxy(leadDays) {
  const a = LEAD_TIME_ANCHORS;
  if (leadDays <= a[0].leadDays) return a[0].v;
  if (leadDays >= a[a.length - 1].leadDays) return a[a.length - 1].v;
  for (let i = 0; i < a.length - 1; i++) {
    if (leadDays >= a[i].leadDays && leadDays <= a[i + 1].leadDays) {
      // Log-linear in lead days: the difference between 1 and 2 days
      // matters far more than the difference between 20 and 21.
      const lo = Math.log(a[i].leadDays + 1);
      const hi = Math.log(a[i + 1].leadDays + 1);
      const x = Math.log(leadDays + 1);
      const f = hi === lo ? 0 : (x - lo) / (hi - lo);
      return a[i].v + f * (a[i + 1].v - a[i].v);
    }
  }
  return a[a.length - 1].v;
}

/* ==================================================================
 * 2. THE DENOMINATOR — CAPACITY ENVELOPE
 * ==================================================================
 * Never use clock hours or "business hours from Google". Use the
 * PUBLISHED BOOKABLE ENVELOPE: for each (unit, day-of-week), the span of
 * time the platform has actually offered over the trailing R weeks.
 *
 * Two problems this solves:
 *   - A shop that publishes 4 online hours a day is not 50% vacant.
 *   - A fully booked day shows zero slots today; without history you
 *     would compute 0/0 and drop a Slammed business off the map.
 *
 * Robust statistic: p90 of observed span minutes across trailing weeks
 * (max is too sensitive to one freak late booking; median under-counts
 * because most days have some blocked time).
 */

/** Percentile with linear interpolation (type-7, same as R/numpy default). */
export function percentile(sortedAsc, p) {
  if (sortedAsc.length === 0) return null;
  if (sortedAsc.length === 1) return sortedAsc[0];
  const idx = (p / 100) * (sortedAsc.length - 1);
  const lo = Math.floor(idx), hi = Math.ceil(idx);
  if (lo === hi) return sortedAsc[lo];
  return sortedAsc[lo] + (idx - lo) * (sortedAsc[hi] - sortedAsc[lo]);
}

/**
 * Build the envelope for one (unit, dow) from trailing observations.
 * @param {number[]} spanMinutesByWeek observed offered-span minutes, one per trailing week
 * @param {number}   nicheDefaultMin   fallback when there is no history
 * @returns {{ envelopeMin:number, source:'observed'|'default', weeks:number }}
 */
export function capacityEnvelope(spanMinutesByWeek, nicheDefaultMin) {
  const obs = spanMinutesByWeek.filter((m) => m > 0).sort((a, b) => a - b);
  if (obs.length === 0) {
    return { envelopeMin: nicheDefaultMin, source: 'default', weeks: 0 };
  }
  return { envelopeMin: Math.round(percentile(obs, 90)), source: 'observed', weeks: obs.length };
}

/**
 * Unit eligibility. Phantom capacity is the #1 way to overstate vacancy.
 * A staff member counts on a given day-of-week only if they have been
 * seen offering at least one slot on that DOW in the trailing window,
 * and are not a "ghost" (100% vacant for 14+ consecutive days = departed
 * or fully blocked, not an empty chair).
 */
export function isUnitEligible({ seenOnDowInTrailing, distinctDaysSeen14d, consecutiveDaysFullyVacant }) {
  if (!seenOnDowInTrailing) return { eligible: false, reason: 'not_offered_on_this_dow' };
  if (distinctDaysSeen14d < 2) return { eligible: false, reason: 'insufficient_history' };
  if (consecutiveDaysFullyVacant >= 14) return { eligible: false, reason: 'ghost_staff' };
  return { eligible: true, reason: null };
}

/* ==================================================================
 * 3. TIME-DECAY WEIGHTS
 * ==================================================================
 * Empty tomorrow is worse than empty next Sunday, because tomorrow's
 * slot has ~1 day of remaining sell-through and day 7 has seven.
 *
 * Prior: exponential decay, half-life 3 days, over t = 1..7.
 *   w(t) = 2 ^ (-(t-1)/3)
 * Empirical override (preferred once available): w(t) = 1 - fillIn(t),
 * where fillIn(t) is the observed fraction of slots standing open at
 * horizon t that are booked by the time that day arrives. That makes
 * the weight the RESIDUAL VACANCY PROBABILITY — an auditable quantity,
 * not a taste parameter.
 */
export function horizonWeights(H = 7, halfLifeDays = 3, empiricalFillIn = null) {
  const w = new Array(H + 1).fill(0);
  for (let t = 1; t <= H; t++) {
    w[t] = empiricalFillIn && empiricalFillIn[t] != null
      ? Math.max(0.05, 1 - empiricalFillIn[t])
      : Math.pow(2, -(t - 1) / halfLifeDays);
  }
  return w;
}

/* ==================================================================
 * 4. VACANCY SCORE
 * ==================================================================
 *      sum_t  w(t) * sum_u OpenMin(u,t)
 *  V = ---------------------------------      S = round(100 * V)
 *      sum_t  w(t) * sum_u EnvMin(u,t)
 *
 * Ratio-of-weighted-sums, NOT mean-of-daily-ratios: one thin Sunday with
 * a 30-minute denominator must not swing the headline.
 * Days with zero envelope (closed / outside the booking window) drop out
 * of BOTH sums; they are a coverage fact, not a vacancy fact.
 */
export function vacancyScore(observations, opts = {}) {
  const H = opts.H ?? 7;
  const w = opts.weights ?? horizonWeights(H, opts.halfLifeDays ?? 3);
  // Days the business is KNOWN to be closed (from the trailing envelope).
  // These are excluded from the coverage denominator: a shop shut on Sunday
  // is a business fact, not a data gap, and must not cost it confidence.
  const closed = new Set(opts.closedDayOffsets ?? []);

  const byDay = new Map();       // dayOffset -> { env, open }
  const byUnit = new Map();      // unitId    -> { env, open }
  for (const o of observations) {
    if (o.dayOffset < 1 || o.dayOffset > H) continue;
    const d = byDay.get(o.dayOffset) ?? { env: 0, open: 0 };
    d.env += o.envelopeMin; d.open += o.openMin;
    byDay.set(o.dayOffset, d);
    const u = byUnit.get(o.unitId) ?? { env: 0, open: 0 };
    u.env += o.envelopeMin; u.open += o.openMin;
    byUnit.set(o.unitId, u);
  }

  let wOpen = 0, wEnv = 0, rawOpen = 0, rawEnv = 0;
  const daily = [];
  let coveredWeight = 0, totalWeight = 0;
  for (let t = 1; t <= H; t++) {
    const d = byDay.get(t);
    if (!closed.has(t)) totalWeight += w[t];
    if (!d || d.env <= 0) {
      daily.push({ t, env: 0, open: 0, v: null, covered: false, closed: closed.has(t) });
      continue;
    }
    coveredWeight += w[t];
    wOpen += w[t] * d.open; wEnv += w[t] * d.env;
    rawOpen += d.open; rawEnv += d.env;
    daily.push({ t, env: d.env, open: d.open, v: d.open / d.env, covered: true, closed: false });
  }

  const V = wEnv > 0 ? wOpen / wEnv : null;
  const Vraw = rawEnv > 0 ? rawOpen / rawEnv : null;

  // Per-unit dispersion: 5 barbers where 1 is slammed is a DIFFERENT
  // sale from 5 barbers who are all half empty.
  const units = [...byUnit.entries()].map(([unitId, u]) => ({
    unitId, env: u.env, open: u.open,
    vacancy: u.env > 0 ? u.open / u.env : null,
    utilisation: u.env > 0 ? 1 - u.open / u.env : null
  })).sort((a, b) => b.utilisation - a.utilisation);

  const shopUtil = V == null ? null : 1 - V;
  const topUtil = units.length ? units[0].utilisation : null;
  const unevenChairs = !!(units.length >= 2 && topUtil >= 0.75 && shopUtil <= 0.60);

  return {
    modelVersion: MODEL_VERSION,
    V, score: V == null ? null : Math.max(0, Math.min(100, Math.round(100 * V))),
    Vraw, scoreRaw: Vraw == null ? null : Math.round(100 * Vraw),
    openMinutes: rawOpen, envelopeMinutes: rawEnv,
    openHours: +(rawOpen / 60).toFixed(1), envelopeHours: +(rawEnv / 60).toFixed(1),
    daily, units, unevenChairs,
    horizonCoverage: totalWeight > 0 ? coveredWeight / totalWeight : 0,
    daysCovered: daily.filter((d) => d.covered).length
  };
}

/* ==================================================================
 * 5. CONFIDENCE / DATA QUALITY
 * ==================================================================
 * C = ceiling(capability) x freshness x envelopeEvidence x coverage
 *     x rosterStability x PRODUCT(anomaly penalties),  clamped [0,1].
 *
 * Multiplicative because the failure modes are independent AND any one
 * of them alone is enough to make the number wrong.
 */
const FRESHNESS_ANCHORS = [[0, 1.0], [24, 1.0], [48, 0.90], [72, 0.75], [168, 0.40], [336, 0.0]];

export function freshnessFactor(scanAgeHours) {
  const a = FRESHNESS_ANCHORS;
  if (scanAgeHours <= a[0][0]) return a[0][1];
  for (let i = 0; i < a.length - 1; i++) {
    if (scanAgeHours >= a[i][0] && scanAgeHours <= a[i + 1][0]) {
      const f = (scanAgeHours - a[i][0]) / (a[i + 1][0] - a[i][0]);
      return a[i][1] + f * (a[i + 1][1] - a[i][1]);
    }
  }
  return 0;
}

export function confidenceScore({
  capability,
  scanAgeHours = 0,
  observedWeeks = 0,          // trailing weeks with >=1 successful scan
  envelopeSource = 'observed',// 'observed' | 'default'
  horizonCoverage = 1,        // weighted fraction of the horizon parsed
  rosterChurn = 0,            // |units_today - median(units,14d)| / median
  priorSuccessfulScans = 0,
  anomalies = []
}) {
  const reasons = [];
  const ceiling = CAPABILITY_CEILING[capability] ?? 0.4;
  const fresh = freshnessFactor(scanAgeHours);
  const evidence = envelopeSource === 'default'
    ? 0.55
    : Math.min(1, observedWeeks / 4);
  const stability = 1 - Math.min(0.4, 2 * rosterChurn);
  let C = ceiling * fresh * evidence * horizonCoverage * stability;
  for (const a of anomalies) {
    const p = ANOMALY_PENALTY[a];
    if (p != null) { C *= p; reasons.push(a); }
  }
  C = Math.max(0, Math.min(1, C));

  // ---- HARD REFUSALS (independent of the numeric score) ----
  const refusals = [];
  if (priorSuccessfulScans < 2) refusals.push('need_two_successful_scans');
  if (horizonCoverage < 0.5) refusals.push('horizon_coverage_below_50pct');
  if (scanAgeHours > 336) refusals.push('scan_older_than_14_days');
  if (envelopeSource === 'default' && observedWeeks === 0) refusals.push('no_observed_envelope');
  if (anomalies.includes('captcha_degraded')) refusals.push('bot_mitigation_partial_parse');
  // A shop that reads 100% open on every day of the horizon is far more
  // likely closed, on holiday, or freshly broken than genuinely desperate.
  // It would otherwise sit at the TOP of the 8am Hungry list. Suppress it.
  if (anomalies.includes('suspect_total_blackout')) refusals.push('total_blackout_needs_human_check');
  if (C < 0.35) refusals.push('confidence_below_floor');

  const band = refusals.length ? 'refuse'
    : C >= 0.75 ? 'high'
      : C >= 0.50 ? 'medium'
        : 'low';

  // Some flags cap what may be RENDERED even when confidence is passable.
  // A suspected walk-in shop is still a real prospect (its online booking
  // is not carrying weight — that is the pitch), but we must not put a
  // dollar figure on time the owner was never trying to sell online.
  const capped = anomalies.includes('suspect_walk_in') || anomalies.includes('multi_location');

  let display = refusals.length ? 'nothing_rescan'
    : C >= 0.75 ? 'score+range+dollars'
      : C >= 0.50 ? 'score+wide_range+dollars'
        : 'bucket+slot_count_only';
  if (capped && display !== 'nothing_rescan') display = 'bucket+slot_count_only';

  return {
    C: +C.toFixed(3), band, refuse: refusals.length > 0, refusals, reasons,
    dollarsAllowed: !refusals.length && !capped && C >= 0.50,
    parts: {
      ceiling, freshness: +fresh.toFixed(3), evidence: +evidence.toFixed(3),
      coverage: +horizonCoverage.toFixed(3), stability: +stability.toFixed(3)
    },
    // What the UI is permitted to render at this confidence.
    display
  };
}

/* ==================================================================
 * 6. PRICE — 3-TIER SOURCE MODEL
 * ==================================================================
 * T1 owner-verified   conf 1.00  (arrives AFTER the meeting; that's fine)
 * T2 published menu   conf 0.85  (default at prospecting time)
 * T3 cohort median    conf 0.50  (needs n>=12 niche+metro, else n>=40 national)
 *
 * The rate is revenue PER BOOKABLE MINUTE, taken from services whose
 * duration matches the modal open slot (+-25%). Taking the mean over the
 * whole menu lets a $450 bridal package price a 30-minute beard trim.
 */
export function priceRate({ tier, services = [], modalSlotMin = 30, override = null, cohort = null }) {
  if (tier === 'T1' && override) {
    const r = override.pricePerMinute ?? (override.price / override.durationMin);
    return { tier: 'T1', tag: 'owner-verified', lo: r * 0.95, mid: r, hi: r * 1.05, n: 1, conf: 1.0 };
  }
  if (tier === 'T2') {
    const ppmAll = services
      .filter((s) => s.price > 0 && s.durationMin > 0)
      .map((s) => ({ ...s, ppm: s.price / s.durationMin }));
    if (ppmAll.length === 0) return { tier: null, tag: 'no-price', lo: null, mid: null, hi: null, n: 0, conf: 0 };
    // Drop absurd outliers (> 3x median ppm): packages, memberships, typos.
    const medAll = percentile(ppmAll.map((s) => s.ppm).sort((a, b) => a - b), 50);
    const sane = ppmAll.filter((s) => s.ppm <= 3 * medAll);
    // Prefer slot-pitch-matched services; need >=3 or the band is noise.
    const matched = sane.filter((s) => Math.abs(s.durationMin - modalSlotMin) <= 0.25 * modalSlotMin);
    const pool = matched.length >= 3 ? matched : sane;
    const arr = pool.map((s) => s.ppm).sort((a, b) => a - b);
    return {
      tier: 'T2',
      tag: matched.length >= 3 ? 'published-menu (slot-matched)' : 'published-menu (full menu)',
      lo: percentile(arr, 25), mid: percentile(arr, 50), hi: percentile(arr, 75),
      n: arr.length, conf: 0.85
    };
  }
  if (tier === 'T3' && cohort) {
    const ok = (cohort.scope === 'niche+metro' && cohort.n >= 12) ||
      (cohort.scope === 'niche+national' && cohort.n >= 40);
    if (!ok) return { tier: null, tag: 'cohort-too-thin', lo: null, mid: null, hi: null, n: cohort.n, conf: 0 };
    return {
      tier: 'T3', tag: `cohort median (${cohort.scope}, n=${cohort.n})`,
      lo: cohort.p25, mid: cohort.p50, hi: cohort.p75, n: cohort.n, conf: 0.50
    };
  }
  return { tier: null, tag: 'no-price', lo: null, mid: null, hi: null, n: 0, conf: 0 };
}

/* ==================================================================
 * 7. EMPTY-CHAIR REVENUE
 * ==================================================================
 * TWO numbers, deliberately:
 *   Unsold Value       = open minutes x price band. A pure arithmetic
 *                        restatement of observable facts. Headline.
 *   Recoverable Revenue= Unsold Value x realistic sell-through (15-35%).
 *                        What the offer is sized against. Never headline.
 *
 * Open minutes use the RAW (unweighted) total. The time decay is a
 * prioritisation device; money is money whatever day it sits on.
 *
 * Range = (open-minute band from confidence) x (price band). If the
 * resulting ratio hi/lo exceeds 2.5, the dollar figure is decoration,
 * not information — we show the slot count instead.
 */
export function emptyChairRevenue({ openMinutes, rate, confidence, sellThrough = [0.15, 0.35], maxRatio = 2.5 }) {
  if (!rate || rate.lo == null) {
    return { showDollars: false, why: 'no_price_source', openMinutes };
  }
  if (confidence.refuse) {
    return { showDollars: false, why: 'confidence_refused', openMinutes };
  }
  if (confidence.C < 0.60 && rate.tier === 'T3') {
    return { showDollars: false, why: 'cohort_price_at_low_confidence', openMinutes };
  }
  if (confidence.dollarsAllowed === false) {
    return { showDollars: false, why: 'display_capped_by_flag', openMinutes };
  }
  // Observed open minutes are near-certain; what is uncertain is whether
  // they were truly sellable. Hence a modest band that widens as C falls.
  const rho = 0.05 + 0.45 * (1 - confidence.C);
  const oLo = openMinutes * (1 - rho);
  const oHi = openMinutes * (1 + rho);

  const unsoldLo = oLo * rate.lo;
  const unsoldHi = oHi * rate.hi;
  const ratio = unsoldLo > 0 ? unsoldHi / unsoldLo : Infinity;

  // Round DOWN at both ends to the nearest $50: we never overstate.
  const r50down = (x) => Math.floor(x / 50) * 50;
  let dispLo = r50down(unsoldLo);
  let dispHi = r50down(unsoldHi);
  if (dispHi <= dispLo) dispHi = dispLo + 50;

  // RECOVERABLE is a forward-looking promise, so it is computed off the
  // BOTTOM of the unsold range only. Multiplying the top of a price band
  // by the top of a sell-through band is how forecasts become fiction.
  const recLo = r50down(unsoldLo * sellThrough[0]);
  const recHi = r50down(unsoldLo * sellThrough[1]);

  return {
    showDollars: ratio <= maxRatio,
    why: ratio <= maxRatio ? null : 'range_wider_than_2.5x',
    openMinutes, openHours: +(openMinutes / 60).toFixed(1),
    rho: +rho.toFixed(3), ratio: +ratio.toFixed(2),
    rate: { lo: +rate.lo.toFixed(4), mid: +rate.mid.toFixed(4), hi: +rate.hi.toFixed(4), tier: rate.tier, tag: rate.tag },
    unsold: { lo: dispLo, hi: dispHi },
    recoverable: { lo: recLo, hi: Math.max(recHi, recLo + 50) },
    assumedTicket: {
      minutes: 30,
      lo: +(rate.lo * 30).toFixed(2), mid: +(rate.mid * 30).toFixed(2), hi: +(rate.hi * 30).toFixed(2)
    }
  };
}

/** Meeting-safe sentence. Every claim in it is pointable-at on the owner's screen. */
export function revenueSentence(rev, ctx) {
  if (!rev.showDollars) {
    return `Your ${ctx.platform} page showed ${ctx.openSlots} open slots (${rev.openHours} bookable hours) in the next ${ctx.days} days. `
      + `We are not putting a dollar figure on that — ${rev.why === 'no_price_source' ? 'your prices are not published' : 'our confidence in it is too low'}.`;
  }
  return `${rev.openHours} hours of online-bookable time on your ${ctx.platform} page went unsold in the next ${ctx.days} days. `
    + `At your own published prices that is $${rev.unsold.lo.toLocaleString('en-US')}-$${rev.unsold.hi.toLocaleString('en-US')} of retail value. `
    + `Fill a third of it and that is $${rev.recoverable.lo.toLocaleString('en-US')}-$${rev.recoverable.hi.toLocaleString('en-US')}.`;
}

/* ==================================================================
 * 8. BUCKETING — NICHE-RELATIVE, WITH ABSOLUTE GUARDRAILS
 * ==================================================================
 * Percentile within the niche+metro cohort, floored/ceilinged by
 * absolute values so we never call a healthy niche "Hungry", plus
 * hysteresis so the 8am list does not flip-flop.
 */
export function percentileRank(value, cohortValues) {
  if (!cohortValues || cohortValues.length === 0) return null;
  const n = cohortValues.length;
  const below = cohortValues.filter((v) => v < value).length;
  const equal = cohortValues.filter((v) => v === value).length;
  return (100 * (below + 0.5 * equal)) / n;
}

export const BUCKET_RULES = {
  cohortMinN: 30,
  hungry: { minPct: 70, minAbsV: 0.40 },
  slammed: { maxPct: 15, maxAbsV: 0.20, leadDaysOverride: 10 },
  hysteresisPct: 5
};

export function bucketBusiness({
  V, leadDays = null, cohortVacancies = null, cohortScope = 'niche+metro',
  prevBucket = null, confidence = null, rules = BUCKET_RULES
}) {
  if (confidence && confidence.refuse) {
    return { bucket: 'unrated', reason: 'confidence_refused', pct: null };
  }
  const usable = cohortVacancies && cohortVacancies.length >= rules.cohortMinN;
  const pct = usable ? percentileRank(V, cohortVacancies) : null;

  // Long lead time is the most robust Slammed signal we have: it survives
  // blocked slots, walk-ins and a broken denominator.
  if (leadDays != null && leadDays >= rules.slammed.leadDaysOverride) {
    return { bucket: 'slammed', reason: `next_available_${leadDays}d`, pct, mode: 'lead_time' };
  }

  let bucket, reason, mode;
  if (usable) {
    mode = `percentile(${cohortScope}, n=${cohortVacancies.length})`;
    if (pct >= rules.hungry.minPct && V >= rules.hungry.minAbsV) { bucket = 'hungry'; reason = `p${pct.toFixed(0)} & V=${(100 * V).toFixed(0)}%`; }
    else if (pct <= rules.slammed.maxPct && V <= rules.slammed.maxAbsV) { bucket = 'slammed'; reason = `p${pct.toFixed(0)} & V=${(100 * V).toFixed(0)}%`; }
    else { bucket = 'steady'; reason = `p${pct.toFixed(0)}`; }
  } else {
    mode = 'absolute_fallback';
    if (V >= 0.55) { bucket = 'hungry'; reason = `V=${(100 * V).toFixed(0)}% (no cohort)`; }
    else if (V <= 0.15) { bucket = 'slammed'; reason = `V=${(100 * V).toFixed(0)}% (no cohort)`; }
    else { bucket = 'steady'; reason = `V=${(100 * V).toFixed(0)}% (no cohort)`; }
  }

  // Hysteresis: only move buckets if we are clearly past the line.
  if (prevBucket && prevBucket !== bucket && usable) {
    const nearHungry = Math.abs(pct - rules.hungry.minPct) < rules.hysteresisPct;
    const nearSlammed = Math.abs(pct - rules.slammed.maxPct) < rules.hysteresisPct;
    if (nearHungry || nearSlammed) {
      return { bucket: prevBucket, reason: `${reason} (held by hysteresis)`, pct, mode, held: true };
    }
  }
  return { bucket, reason, pct, mode, held: false };
}

/* ==================================================================
 * 9. DEAD-ZONE MAP
 * ==================================================================
 * 7 days-of-week x 7 two-hour bins = 49 cells, business-local time.
 * Cell statistic: POOLED vacancy = sum(openMin) / sum(envMin) across the
 * trailing N weeks and all staff. Pooled, not mean-of-weekly-ratios.
 *
 * "Chronic" requires three things, and we ship all three:
 *   Gate 1 (effect size)  cell vacancy >= shop vacancy + 0.15 AND >= 0.50
 *   Gate 2 (consistency)  cell beat shop vacancy in >= 75% of weeks
 *   Gate 3 (significance) one-sided sign test, k of n weeks, H0: p=0.5,
 *                         then Benjamini-Hochberg FDR 0.10 across the 49
 *                         cells. Without the FDR correction, 49 tests at
 *                         alpha=0.05 hand you ~2 fake dead zones per shop
 *                         — the exact thing that gets you laughed at.
 *
 * With 8 weeks, k=7 gives p=0.035 and k=8 gives p=0.0039: reachable.
 * With 4 weeks the best possible is k=4, p=0.0625 — NOT significant, so
 * 4-week findings ship labelled PROVISIONAL. Say that on the report.
 */
function binomCoef(n, k) {
  let r = 1;
  for (let i = 1; i <= k; i++) r = (r * (n - k + i)) / i;
  return r;
}

/** One-sided P(X >= k) for X ~ Binomial(n, p). */
export function binomTailGE(k, n, p = 0.5) {
  let s = 0;
  for (let i = k; i <= n; i++) s += binomCoef(n, i) * Math.pow(p, i) * Math.pow(1 - p, n - i);
  return Math.min(1, s);
}

export const CELL_ELIGIBILITY = { minWeeks: 3, minEnvelopeMin: 180, minStaffDays: 2 };

/**
 * @param {Array} cells  [{ dow, bin, weekly:[{envMin, openMin}], staffDays }]
 * @param {number[]} shopWeeklyVacancy  shop-wide vacancy, one per trailing week
 */
export function detectDeadZones(cells, { shopWeeklyVacancy, shopVacancy, fdr = 0.10, elig = CELL_ELIGIBILITY }) {
  const scored = [];
  for (const c of cells) {
    const weeksWithData = c.weekly.filter((w) => w.envMin > 0);
    const totalEnv = weeksWithData.reduce((a, w) => a + w.envMin, 0);
    const totalOpen = weeksWithData.reduce((a, w) => a + w.openMin, 0);
    const eligible = weeksWithData.length >= elig.minWeeks
      && totalEnv >= elig.minEnvelopeMin
      && (c.staffDays ?? elig.minStaffDays) >= elig.minStaffDays;
    const v = totalEnv > 0 ? totalOpen / totalEnv : null;

    // Consistency: how many weeks was this cell emptier than the shop?
    let k = 0, n = 0;
    c.weekly.forEach((w, i) => {
      if (w.envMin <= 0) return;
      const sv = shopWeeklyVacancy[i];
      if (sv == null) return;
      n += 1;
      if (w.openMin / w.envMin > sv) k += 1;
    });
    const p = n > 0 ? binomTailGE(k, n, 0.5) : 1;

    scored.push({
      dow: c.dow, bin: c.bin, label: `${DOW[c.dow]} ${TIME_BINS[c.bin].label}`,
      eligible, vacancy: v, openMin: totalOpen, envMin: totalEnv,
      weeks: weeksWithData.length, k, n, p,
      gate1: eligible && v != null && v >= shopVacancy + 0.15 && v >= 0.50,
      gate2: n > 0 && k / n >= 0.75
    });
  }

  // Benjamini-Hochberg across ELIGIBLE cells that already passed gates 1+2.
  const candidates = scored.filter((s) => s.eligible && s.gate1 && s.gate2).sort((a, b) => a.p - b.p);
  const m = scored.filter((s) => s.eligible).length;
  let cutoff = -1;
  candidates.forEach((s, i) => { if (s.p <= ((i + 1) / Math.max(m, 1)) * fdr) cutoff = i; });
  candidates.forEach((s, i) => {
    s.significant = i <= cutoff;
    s.status = !s.significant ? 'noise'
      : s.n >= 8 ? 'confirmed'
        : 'provisional';
  });
  for (const s of scored) if (s.significant === undefined) { s.significant = false; s.status = s.eligible ? 'noise' : 'insufficient_data'; }

  return { cells: scored, tested: m, deadCells: scored.filter((s) => s.significant) };
}

/** Merge contiguous significant bins on the same day into one sellable zone. */
export function mergeZones(deadCells) {
  const byDow = new Map();
  for (const c of deadCells) {
    if (!byDow.has(c.dow)) byDow.set(c.dow, []);
    byDow.get(c.dow).push(c);
  }
  const zones = [];
  for (const [dow, list] of byDow) {
    list.sort((a, b) => a.bin - b.bin);
    let run = [list[0]];
    for (let i = 1; i <= list.length; i++) {
      if (i < list.length && list[i].bin === run[run.length - 1].bin + 1) { run.push(list[i]); continue; }
      const env = run.reduce((a, c) => a + c.envMin, 0);
      const open = run.reduce((a, c) => a + c.openMin, 0);
      zones.push({
        dow, dowLabel: DOW[dow],
        fromHour: TIME_BINS[run[0].bin].from,
        toHour: TIME_BINS[run[run.length - 1].bin].to,
        bins: run.map((c) => c.bin),
        vacancy: env > 0 ? open / env : null,
        openMinPerWeek: Math.round(open / Math.max(...run.map((c) => c.weeks))),
        status: run.every((c) => c.status === 'confirmed') ? 'confirmed' : 'provisional',
        label: `${DOW[dow]} ${TIME_BINS[run[0].bin].from}:00-${TIME_BINS[run[run.length - 1].bin].to}:00`
      });
      if (i < list.length) run = [list[i]];
    }
  }
  return zones.sort((a, b) => b.openMinPerWeek - a.openMinPerWeek);
}

/** Zone -> offer. The mapping is deliberately boring and auditable. */
export function recommendOffer(zone, ctx = {}) {
  const midday = zone.fromHour >= 10 && zone.fromHour <= 15 && zone.toHour <= 18;
  const weekday = zone.dow >= 1 && zone.dow <= 4;
  const early = zone.toHour <= 10;
  const evening = zone.fromHour >= 17;
  const wholeDay = zone.toHour - zone.fromHour >= 8;

  if (ctx.unevenChairs) {
    return { offer: 'chair-ramp', headline: `Your busiest chair is full while others sit open in ${zone.label}.`, play: 'Rebooking prompts + new-staff intro offer, not a discount blast.' };
  }
  if (wholeDay) {
    return { offer: 'verify-first', headline: `${zone.dowLabel} looks empty all day.`, play: 'Verify the shop is actually open before pitching — full-day zones are usually a closed day still published.' };
  }
  if (weekday && midday) {
    return { offer: 'midweek-fill', headline: `${zone.label} is your dead zone.`, play: 'Midweek-only offer + 48h SMS to lapsed clients + Google offer post timed to that window.' };
  }
  if (early) {
    return { offer: 'early-bird', headline: `${zone.label} never fills.`, play: 'Before-work pricing and a nearby-employer partnership.' };
  }
  if (evening) {
    return { offer: 'demand-problem', headline: `${zone.label} is empty in your peak window.`, play: 'This is not a scheduling problem. Reviews, pricing or listing quality — lead with the LossReel.' };
  }
  return { offer: 'generic-fill', headline: `${zone.label} runs empty.`, play: 'Targeted window offer + waitlist capture.' };
}

/* ==================================================================
 * 10. LEDGER — 8-WEEK FILL-RATE TREND
 * ==================================================================
 * Fill rate F = 1 - V, on the SAME envelope basis, stamped with
 * MODEL_VERSION. Weeks with low confidence, low coverage, or a public
 * holiday are marked and EXCLUDED FROM FITTING but never deleted —
 * deleting inconvenient weeks is what gets a consultant caught.
 */
export function centeredMA(series, k = 3) {
  const half = Math.floor(k / 2);
  return series.map((_, i) => {
    const lo = i - half, hi = i + half;
    if (lo < 0 || hi >= series.length) return null; // incomplete window -> draw dashed
    let s = 0, n = 0;
    for (let j = lo; j <= hi; j++) { if (series[j] != null) { s += series[j]; n++; } }
    return n === k ? s / n : null;
  });
}

/**
 * Difference-in-differences against a cohort of comparable businesses.
 * This is the ONLY form in which a lift may be claimed as ours.
 */
export function ledgerLift({ weeks, cohortMinN = 20 }) {
  const usable = weeks.filter((w) => w.usable);
  const base = usable.filter((w) => w.phase === 'baseline');
  const post = usable.filter((w) => w.phase === 'campaign');
  const mean = (a, f) => (a.length ? a.reduce((s, x) => s + f(x), 0) / a.length : null);

  const clientBase = mean(base, (w) => w.fill);
  const clientPost = mean(post, (w) => w.fill);
  const clientDelta = clientBase != null && clientPost != null ? clientPost - clientBase : null;

  const cohortOk = usable.every((w) => (w.cohortN ?? 0) >= cohortMinN);
  const cohortBase = cohortOk ? mean(base, (w) => w.cohortFill) : null;
  const cohortPost = cohortOk ? mean(post, (w) => w.cohortFill) : null;
  const cohortDelta = cohortBase != null && cohortPost != null ? cohortPost - cohortBase : null;

  const net = clientDelta != null && cohortDelta != null ? clientDelta - cohortDelta : null;

  return {
    clientBase, clientPost, clientDelta,
    cohortBase, cohortPost, cohortDelta, cohortOk,
    netOfMarket: net,
    claimable: net != null,
    smoothed: centeredMA(weeks.map((w) => (w.usable ? w.fill : null)), 3),
    excluded: weeks.filter((w) => !w.usable).map((w) => ({ week: w.week, why: w.excludeReason })),
    caveat: ATTRIBUTION_CAVEAT
  };
}

export const ATTRIBUTION_CAVEAT =
  'This measures online-bookable availability on the booking platform, not total business. '
  + 'It cannot separate the effect of our work from seasonality, weather, pricing, staffing '
  + 'or anything you did yourself. Where at least 20 comparable businesses in this metro were '
  + 'scanned over the same weeks, their movement is shown alongside yours so you can judge how '
  + 'much of the change was the market. Baseline window and campaign start date were recorded '
  + 'before the campaign began and have not been changed.';

/* ==================================================================
 * 11. DEMO / SELF-TEST
 * ==================================================================
 * `node meter-reference.mjs` prints the worked examples pasted into
 * 35-scoring-model.md. `node --test meter-reference.mjs` runs assertions.
 */

const fmtPct = (x) => (x == null ? 'n/a' : `${(100 * x).toFixed(1)}%`);
const line = (s = '') => console.log(s);
const rule = (t) => { line(); line('='.repeat(66)); line(t); line('='.repeat(66)); };

/* ---------- Example A: barbershop, 3 staff, Booksy (C1) ---------- */
export function exampleBarbershop() {
  const SLOT = 30;              // modal slot pitch, minutes
  const ENV = 540;              // 9:00-18:00 published envelope, per chair per day
  // dayOffset 1..5 = Tue..Sat (open), 6..7 = Sun/Mon (closed -> envelope 0)
  const openSlots = {
    A: { 1: 2, 2: 3, 3: 4, 4: 1, 5: 0 },
    B: { 1: 12, 2: 11, 3: 10, 4: 6, 5: 3 },
    C: { 1: 14, 2: 13, 3: 12, 4: 9, 5: 5 }
  };
  const staffDays = [];
  for (const unitId of Object.keys(openSlots)) {
    for (const t of [1, 2, 3, 4, 5]) {
      staffDays.push({ unitId, dayOffset: t, openSlots: openSlots[unitId][t], slotMinutes: SLOT, envelopeMin: ENV });
    }
    for (const t of [6, 7]) {
      staffDays.push({ unitId, dayOffset: t, openSlots: 0, slotMinutes: SLOT, envelopeMin: 0 });
    }
  }
  const obs = fromSlotGrid({ staffDays });
  // Sun/Mon are known closed from 6 weeks of envelope history -> they do
  // not count against horizon coverage.
  const vs = vacancyScore(obs, { closedDayOffsets: [6, 7] });

  const conf = confidenceScore({
    capability: CAPABILITY.C1_SLOT_GRID,
    scanAgeHours: 8, observedWeeks: 6, envelopeSource: 'observed',
    horizonCoverage: vs.horizonCoverage, rosterChurn: 0, priorSuccessfulScans: 41, anomalies: []
  });

  const menu = [
    { name: 'Haircut', price: 35, durationMin: 30 },
    { name: 'Beard trim', price: 20, durationMin: 15 },
    { name: 'Cut + beard', price: 50, durationMin: 45 },
    { name: 'Kids cut', price: 25, durationMin: 30 },
    { name: 'Skin fade', price: 40, durationMin: 30 },
    { name: 'Hot towel shave', price: 40, durationMin: 45 }
  ];
  const rate = priceRate({ tier: 'T2', services: menu, modalSlotMin: SLOT });
  const rev = emptyChairRevenue({ openMinutes: vs.openMinutes, rate, confidence: conf });

  // Cohort of 40 Tampa barbershops (synthetic, deterministic).
  const cohort = Array.from({ length: 40 }, (_, i) => 0.12 + (i * 0.0135));
  const bucket = bucketBusiness({ V: vs.V, leadDays: 1, cohortVacancies: cohort, prevBucket: 'steady', confidence: conf });

  return { vs, conf, rate, rev, bucket, openSlotCount: vs.openMinutes / SLOT, cohort };
}

/* ---------- Example B: yoga studio, class capacity (C2) ---------- */
export function exampleYoga() {
  // 5 classes/day, 7 days. spotsLeft hand-set so days 1-3 are emptier.
  const template = [
    { t: 6, durationMin: 60, capacity: 20 },
    { t: 9.5, durationMin: 60, capacity: 20 },
    { t: 12, durationMin: 45, capacity: 12 },
    { t: 17.5, durationMin: 60, capacity: 20 },
    { t: 19, durationMin: 75, capacity: 20 }
  ];
  const left = [
    [3, 11, 8, 2, 9],   // day 1
    [4, 12, 9, 3, 10],  // day 2
    [5, 13, 7, 4, 11],  // day 3
    [2, 9, 6, 1, 7],    // day 4
    [1, 6, 4, 0, 5],    // day 5
    [6, 14, 9, 5, 12],  // day 6
    [7, 15, 10, 6, 13]  // day 7
  ];
  const classes = [];
  for (let d = 1; d <= 7; d++) {
    template.forEach((c, i) => {
      classes.push({ unitId: `class@${c.t}`, dayOffset: d, durationMin: c.durationMin, capacity: c.capacity, spotsLeft: left[d - 1][i] });
    });
  }
  const obs = fromClassSchedule({ classes });
  const vs = vacancyScore(obs);
  const conf = confidenceScore({
    capability: CAPABILITY.C2_CLASS_CAPACITY,
    scanAgeHours: 11, observedWeeks: 4, envelopeSource: 'observed',
    horizonCoverage: vs.horizonCoverage, rosterChurn: 0.1, priorSuccessfulScans: 19, anomalies: []
  });
  // Studio publishes passes, not per-class prices -> cohort fallback.
  const rate = priceRate({
    tier: 'T3', cohort: { scope: 'niche+metro', n: 23, p25: 22 / 60, p50: 26 / 60, p75: 32 / 60 }
  });
  const rev = emptyChairRevenue({ openMinutes: vs.openMinutes, rate, confidence: conf });
  const cohort = Array.from({ length: 34 }, (_, i) => 0.18 + i * 0.0125);
  const bucket = bucketBusiness({ V: vs.V, leadDays: 0, cohortVacancies: cohort, confidence: conf });
  return { vs, conf, rate, rev, bucket };
}

/* ---------- Example C: dentist, next-available only (C5) ---------- */
export function exampleDentist() {
  const leadDays = 19;
  const V = nextAvailableProxy(leadDays);
  const conf = confidenceScore({
    capability: CAPABILITY.C5_NEXT_AVAILABLE,
    scanAgeHours: 20, observedWeeks: 5, envelopeSource: 'observed',
    horizonCoverage: 1, rosterChurn: 0, priorSuccessfulScans: 30, anomalies: []
  });
  const rate = priceRate({ tier: 'T3', cohort: { scope: 'niche+metro', n: 9, p25: 2, p50: 3, p75: 4 } });
  const rev = emptyChairRevenue({ openMinutes: 0, rate, confidence: conf });
  const cohort = Array.from({ length: 31 }, (_, i) => 0.05 + i * 0.011);
  const bucket = bucketBusiness({ V, leadDays, cohortVacancies: cohort, confidence: conf });
  return { V, leadDays, conf, rate, rev, bucket };
}

/* ---------- Dead-zone demo: 8 weeks for the barbershop ---------- */
export function exampleDeadZones() {
  const shopWeekly = [0.44, 0.41, 0.46, 0.39, 0.43, 0.45, 0.40, 0.42];
  // Base vacancy per (dow, bin); Tue 1-3p and 3-5p are chronically dead.
  const base = {};
  for (let dow = 2; dow <= 6; dow++) for (let bin = 1; bin <= 5; bin++) base[`${dow}:${bin}`] = 0.40;
  base['2:3'] = 0.78; base['2:4'] = 0.72;        // Tue 1-3p, 3-5p
  base['3:3'] = 0.58;                            // Wed 1-3p: elevated but not chronic
  base['6:5'] = 0.12;                            // Sat 5-7p: slammed
  const jitter = [0.03, -0.04, 0.02, -0.02, 0.05, -0.03, 0.01, -0.01];

  const cells = [];
  for (let dow = 2; dow <= 6; dow++) {
    for (let bin = 1; bin <= 5; bin++) {
      const b = base[`${dow}:${bin}`];
      const weekly = jitter.map((j, i) => {
        // Wed 1-3p deliberately oscillates around the shop mean -> noise.
        const v = (dow === 3 && bin === 3) ? b + (i % 2 === 0 ? 0.14 : -0.16) : b + j;
        const envMin = 3 * 120; // 3 chairs x 2 hours
        return { envMin, openMin: Math.round(envMin * Math.max(0, Math.min(1, v))) };
      });
      cells.push({ dow, bin, weekly, staffDays: 3 });
    }
  }
  const res = detectDeadZones(cells, { shopWeeklyVacancy: shopWeekly, shopVacancy: 0.43 });
  const zones = mergeZones(res.deadCells);
  return { res, zones };
}

/* ---------- Ledger demo ---------- */
export function exampleLedger() {
  const weeks = [
    { week: '2026-W10', fill: 0.41, cohortFill: 0.44, cohortN: 26, phase: 'baseline', usable: true },
    { week: '2026-W11', fill: 0.39, cohortFill: 0.43, cohortN: 26, phase: 'baseline', usable: true },
    { week: '2026-W12', fill: 0.43, cohortFill: 0.45, cohortN: 27, phase: 'baseline', usable: true },
    { week: '2026-W13', fill: 0.41, cohortFill: 0.44, cohortN: 27, phase: 'baseline', usable: true },
    { week: '2026-W14', fill: 0.52, cohortFill: 0.47, cohortN: 28, phase: 'campaign', usable: true },
    { week: '2026-W15', fill: 0.98, cohortFill: 0.51, cohortN: 28, phase: 'campaign', usable: false, excludeReason: 'public_holiday_week' },
    { week: '2026-W16', fill: 0.61, cohortFill: 0.50, cohortN: 29, phase: 'campaign', usable: true },
    { week: '2026-W17', fill: 0.68, cohortFill: 0.52, cohortN: 29, phase: 'campaign', usable: true }
  ];
  return { weeks, lift: ledgerLift({ weeks }) };
}

/* ---------- Printer ---------- */
export function runDemo() {
  line(`OpenChair Meter reference — ${MODEL_VERSION}`);
  line(`node ${process.version}`);

  rule('TIME-DECAY WEIGHTS  w(t) = 2^(-(t-1)/3), t = 1..7');
  const w = horizonWeights();
  line('  t      : ' + [1, 2, 3, 4, 5, 6, 7].map((t) => String(t).padStart(6)).join(''));
  line('  w(t)   : ' + [1, 2, 3, 4, 5, 6, 7].map((t) => w[t].toFixed(3).padStart(6)).join(''));
  const sw = w.slice(1).reduce((a, b) => a + b, 0);
  line('  norm   : ' + [1, 2, 3, 4, 5, 6, 7].map((t) => (w[t] / sw).toFixed(3).padStart(6)).join(''));
  line(`  sum w  : ${sw.toFixed(4)}`);

  rule('EXAMPLE A — Barbershop, 3 chairs, Booksy (C1 slot grid)');
  const a = exampleBarbershop();
  line(`  open slots (7d)      : ${a.openSlotCount}  (${a.vs.openHours} h of ${a.vs.envelopeHours} h envelope)`);
  line(`  unweighted vacancy   : ${fmtPct(a.vs.Vraw)}   -> score ${a.vs.scoreRaw}`);
  line(`  TIME-WEIGHTED vacancy: ${fmtPct(a.vs.V)}   -> VACANCY SCORE ${a.vs.score}`);
  line('  daily:');
  for (const d of a.vs.daily) {
    line(`    t=${d.t}  env ${String(d.env).padStart(5)}m  open ${String(d.open).padStart(5)}m  v=${d.v == null ? 'closed' : fmtPct(d.v)}`);
  }
  line('  per chair:');
  for (const u of a.vs.units) line(`    ${u.unitId}: vacancy ${fmtPct(u.vacancy)}  utilisation ${fmtPct(u.utilisation)}`);
  line(`  uneven-chairs flag   : ${a.vs.unevenChairs}`);
  line(`  confidence           : C=${a.conf.C} (${a.conf.band})  parts=${JSON.stringify(a.conf.parts)}`);
  line(`  display permitted    : ${a.conf.display}`);
  line(`  price source         : ${a.rate.tier} ${a.rate.tag}  n=${a.rate.n}  $/min p25=${a.rate.lo.toFixed(3)} p50=${a.rate.mid.toFixed(3)} p75=${a.rate.hi.toFixed(3)}`);
  line(`  assumed 30-min ticket: $${a.rev.assumedTicket.lo} - $${a.rev.assumedTicket.hi} (mid $${a.rev.assumedTicket.mid})`);
  line(`  open-minute band rho : +/-${(100 * a.rev.rho).toFixed(1)}%   range ratio ${a.rev.ratio}x (cap 2.5x)`);
  line(`  UNSOLD VALUE         : $${a.rev.unsold.lo} - $${a.rev.unsold.hi}`);
  line(`  RECOVERABLE (15-35%) : $${a.rev.recoverable.lo} - $${a.rev.recoverable.hi}`);
  line(`  bucket               : ${a.bucket.bucket}  [${a.bucket.mode}] ${a.bucket.reason}`);
  line('  sentence:');
  line(`    "${revenueSentence(a.rev, { platform: 'Booksy', openSlots: a.openSlotCount, days: 7 })}"`);

  rule('EXAMPLE B — Yoga studio, Mindbody (C2 class capacity)');
  const b = exampleYoga();
  line(`  seat-minutes open    : ${b.vs.openMinutes} of ${b.vs.envelopeMinutes}  (${b.vs.openHours} of ${b.vs.envelopeHours} seat-hours)`);
  line(`  unweighted vacancy   : ${fmtPct(b.vs.Vraw)}   -> score ${b.vs.scoreRaw}`);
  line(`  TIME-WEIGHTED vacancy: ${fmtPct(b.vs.V)}   -> VACANCY SCORE ${b.vs.score}`);
  line(`  confidence           : C=${b.conf.C} (${b.conf.band})  parts=${JSON.stringify(b.conf.parts)}`);
  line(`  price source         : ${b.rate.tier} ${b.rate.tag}  $/min p25=${b.rate.lo.toFixed(3)} p50=${b.rate.mid.toFixed(3)} p75=${b.rate.hi.toFixed(3)}`);
  line(`  UNSOLD VALUE         : ${b.rev.showDollars ? `$${b.rev.unsold.lo} - $${b.rev.unsold.hi}` : `SUPPRESSED (${b.rev.why})`}`);
  if (b.rev.showDollars) line(`  RECOVERABLE (15-35%) : $${b.rev.recoverable.lo} - $${b.rev.recoverable.hi}`);
  line(`  bucket               : ${b.bucket.bucket}  [${b.bucket.mode}] ${b.bucket.reason}`);

  rule('EXAMPLE C — Dentist, next-available-date only (C5)');
  const c = exampleDentist();
  line(`  next available       : ${c.leadDays} days`);
  line(`  vacancy PROXY        : ${fmtPct(c.V)}  (rank-preserving only, not a measurement)`);
  line(`  confidence           : C=${c.conf.C} (${c.conf.band})  parts=${JSON.stringify(c.conf.parts)}`);
  line(`  display permitted    : ${c.conf.display}`);
  line(`  price source         : ${c.rate.tier ?? 'none'} (${c.rate.tag})`);
  line(`  UNSOLD VALUE         : ${c.rev.showDollars ? 'shown' : `SUPPRESSED (${c.rev.why})`}`);
  line(`  bucket               : ${c.bucket.bucket}  [${c.bucket.mode}] ${c.bucket.reason}`);
  line('  sentence:');
  line('    "Next available appointment on your booking page is 19 days out. You are turning people away — that is a capacity problem, not a marketing problem."');

  rule('REFUSAL CASES');
  const cases = [
    ['fresh scan but only 1 prior success', { capability: CAPABILITY.C1_SLOT_GRID, scanAgeHours: 2, observedWeeks: 3, horizonCoverage: 1, priorSuccessfulScans: 1 }],
    ['stale 10-day-old scan', { capability: CAPABILITY.C1_SLOT_GRID, scanAgeHours: 240, observedWeeks: 6, horizonCoverage: 1, priorSuccessfulScans: 20 }],
    ['booking window only 3 of 7 days', { capability: CAPABILITY.C1_SLOT_GRID, scanAgeHours: 5, observedWeeks: 6, horizonCoverage: 0.42, priorSuccessfulScans: 20, anomalies: ['release_limited'] }],
    ['captcha-degraded page', { capability: CAPABILITY.C1_SLOT_GRID, scanAgeHours: 3, observedWeeks: 6, horizonCoverage: 0.9, priorSuccessfulScans: 20, anomalies: ['captcha_degraded'] }],
    ['total blackout (holiday?)', { capability: CAPABILITY.C1_SLOT_GRID, scanAgeHours: 3, observedWeeks: 6, horizonCoverage: 1, priorSuccessfulScans: 20, anomalies: ['suspect_total_blackout'] }],
    ['suspected walk-in shop', { capability: CAPABILITY.C1_SLOT_GRID, scanAgeHours: 3, observedWeeks: 6, horizonCoverage: 1, priorSuccessfulScans: 20, anomalies: ['suspect_walk_in'] }],
    ['restaurant, 2 days old', { capability: CAPABILITY.C4_RESERVATION, scanAgeHours: 48, observedWeeks: 4, horizonCoverage: 1, priorSuccessfulScans: 12 }]
  ];
  for (const [name, inp] of cases) {
    const r = confidenceScore(inp);
    line(`  ${name.padEnd(36)} C=${String(r.C).padEnd(6)} ${r.band.padEnd(7)} ${r.refuse ? 'REFUSE ' + r.refusals.join(',') : r.display}`);
  }

  rule('DEAD-ZONE MAP — 8 weeks, 3 chairs, shop vacancy 43%');
  const dz = exampleDeadZones();
  line(`  cells tested (eligible): ${dz.res.tested}`);
  line('  dow bin  label          vacancy  k/n   p        gate1 gate2  status');
  for (const s of dz.res.cells.filter((x) => x.vacancy > 0.5 || x.k >= 6 || x.significant)) {
    line(`  ${String(s.dow).padStart(3)} ${String(s.bin).padStart(3)}  ${s.label.padEnd(14)} ${fmtPct(s.vacancy).padStart(7)}  ${s.k}/${s.n}  ${s.p.toFixed(4)}   ${String(s.gate1).padEnd(5)} ${String(s.gate2).padEnd(5)}  ${s.status}`);
  }
  line('  merged zones:');
  for (const z of dz.zones) {
    const off = recommendOffer(z, { unevenChairs: false });
    line(`    ${z.label.padEnd(18)} vacancy ${fmtPct(z.vacancy)}  ${z.openMinPerWeek} open min/week  [${z.status}]`);
    line(`       -> ${off.offer}: ${off.headline}`);
    line(`          ${off.play}`);
  }

  rule('SIGN-TEST REFERENCE (H0: cell no worse than shop, p=0.5)');
  for (const n of [4, 6, 8]) {
    const row = [];
    for (let k = Math.ceil(n * 0.75); k <= n; k++) row.push(`k=${k}: p=${binomTailGE(k, n).toFixed(4)}`);
    line(`  n=${n} weeks  ${row.join('   ')}`);
  }
  line('  => 4 weeks can never reach p<0.05 -> label PROVISIONAL. 8 weeks needs k>=7.');

  rule('LEDGER — 8-week fill rate with difference-in-differences');
  const led = exampleLedger();
  line('  week      fill   cohort  phase     usable  3wk-MA');
  led.weeks.forEach((wk, i) => {
    const ma = led.lift.smoothed[i];
    line(`  ${wk.week}  ${fmtPct(wk.fill).padStart(6)} ${fmtPct(wk.cohortFill).padStart(7)}  ${wk.phase.padEnd(9)} ${String(wk.usable).padEnd(6)} ${ma == null ? '  -' : fmtPct(ma)}`);
  });
  line(`  excluded             : ${JSON.stringify(led.lift.excluded)}`);
  line(`  client  baseline->post: ${fmtPct(led.lift.clientBase)} -> ${fmtPct(led.lift.clientPost)}  (${(100 * led.lift.clientDelta).toFixed(1)} pts)`);
  line(`  cohort  baseline->post: ${fmtPct(led.lift.cohortBase)} -> ${fmtPct(led.lift.cohortPost)}  (${(100 * led.lift.cohortDelta).toFixed(1)} pts)`);
  line(`  NET OF MARKET         : ${(100 * led.lift.netOfMarket).toFixed(1)} pts   claimable=${led.lift.claimable}`);
  line('  caveat line:');
  line(`    "${ATTRIBUTION_CAVEAT}"`);

  rule('LEAD-TIME PROXY CURVE (C5)');
  line('  days : ' + [0, 1, 2, 3, 5, 7, 10, 14, 19, 21, 30, 45].map((d) => String(d).padStart(6)).join(''));
  line('  V^   : ' + [0, 1, 2, 3, 5, 7, 10, 14, 19, 21, 30, 45].map((d) => nextAvailableProxy(d).toFixed(3).padStart(6)).join(''));
  line();
}

/* ---------- node --test assertions ---------- */
const isTestRun = process.argv.some((a) => a === '--test') ||
  (process.env.NODE_TEST_CONTEXT != null);

if (isTestRun) {
  const { test } = await import('node:test');
  const assert = (await import('node:assert/strict')).default;

  test('time-decay weights: half-life 3 days', () => {
    const w = horizonWeights();
    assert.equal(+w[1].toFixed(3), 1.000);
    assert.equal(+w[4].toFixed(3), 0.500);   // exactly half at t=4
    assert.equal(+w[7].toFixed(3), 0.250);   // quarter at t=7
  });

  test('closed days drop out of both sums', () => {
    const obs = [
      { unitId: 'A', dayOffset: 1, envelopeMin: 480, openMin: 240 },
      { unitId: 'A', dayOffset: 2, envelopeMin: 0, openMin: 0 }
    ];
    const r = vacancyScore(obs);
    assert.equal(r.Vraw, 0.5);
    assert.equal(r.daysCovered, 1);
    assert.ok(r.horizonCoverage < 1);
  });

  test('near-horizon emptiness raises the weighted score above the raw score', () => {
    const a = exampleBarbershop();
    assert.ok(a.vs.V > a.vs.Vraw, 'weighted should exceed unweighted when near days are emptier');
    assert.equal(a.vs.score, 43);
  });

  test('multi-staff pooling: one slammed chair does not rescue the shop', () => {
    const a = exampleBarbershop();
    assert.ok(a.vs.units[0].utilisation > 0.85);
    assert.equal(a.vs.unevenChairs, true);
  });

  test('confidence: C1 can never exceed 0.95 without owner verification', () => {
    const r = confidenceScore({
      capability: CAPABILITY.C1_SLOT_GRID, scanAgeHours: 0, observedWeeks: 52,
      envelopeSource: 'observed', horizonCoverage: 1, rosterChurn: 0, priorSuccessfulScans: 100
    });
    assert.equal(r.C, 0.95);
  });

  test('confidence: hard refusals fire independently of the numeric score', () => {
    const r = confidenceScore({
      capability: CAPABILITY.C1_SLOT_GRID, scanAgeHours: 1, observedWeeks: 8,
      horizonCoverage: 1, priorSuccessfulScans: 1
    });
    assert.equal(r.refuse, true);
    assert.ok(r.refusals.includes('need_two_successful_scans'));
  });

  test('price: a $450 bridal package cannot price a 30-minute cut', () => {
    const menu = [
      { name: 'Cut', price: 35, durationMin: 30 },
      { name: 'Kids', price: 25, durationMin: 30 },
      { name: 'Trim', price: 30, durationMin: 30 },
      { name: 'Bridal package', price: 450, durationMin: 240 }
    ];
    const r = priceRate({ tier: 'T2', services: menu, modalSlotMin: 30 });
    assert.equal(r.n, 3);
    assert.ok(r.hi < 1.3);
  });

  test('revenue: dollars suppressed for a C5 next-available business', () => {
    const c = exampleDentist();
    assert.equal(c.rev.showDollars, false);
    assert.equal(c.bucket.bucket, 'slammed');
  });

  test('revenue: cohort price at low confidence is refused', () => {
    const conf = { C: 0.5, refuse: false };
    const rate = { tier: 'T3', lo: 1, mid: 2, hi: 3 };
    const r = emptyChairRevenue({ openMinutes: 1000, rate, confidence: conf });
    assert.equal(r.showDollars, false);
    assert.equal(r.why, 'cohort_price_at_low_confidence');
  });

  test('bucket: long lead time overrides everything', () => {
    const r = bucketBusiness({ V: 0.9, leadDays: 21, cohortVacancies: null });
    assert.equal(r.bucket, 'slammed');
  });

  test('bucket: hysteresis holds a business near the line', () => {
    const cohort = Array.from({ length: 40 }, (_, i) => i / 40);
    // V placed just above the 70th percentile -> would flip to hungry
    const r = bucketBusiness({ V: 0.71, cohortVacancies: cohort, prevBucket: 'steady' });
    assert.equal(r.held, true);
    assert.equal(r.bucket, 'steady');
  });

  test('sign test: 4 weeks cannot reach significance, 8 weeks can', () => {
    assert.ok(binomTailGE(4, 4) > 0.05);
    assert.ok(binomTailGE(7, 8) < 0.05);
  });

  test('dead zones: chronic Tuesday found, oscillating Wednesday rejected', () => {
    const { res, zones } = exampleDeadZones();
    const labels = res.deadCells.map((c) => c.label);
    assert.ok(labels.includes('Tue 1-3p'));
    assert.ok(labels.includes('Tue 3-5p'));
    assert.ok(!labels.includes('Wed 1-3p'), 'oscillating cell must not survive the consistency gate');
    assert.equal(zones[0].label, 'Tue 13:00-17:00');
  });

  test('ledger: lift is only claimable net of the cohort', () => {
    const { lift } = exampleLedger();
    assert.equal(lift.claimable, true);
    assert.ok(lift.clientDelta > lift.netOfMarket, 'raw lift must overstate the net lift');
    assert.equal(lift.excluded.length, 1);
  });
} else if (process.argv[1] && import.meta.url === `file://${process.argv[1].replace(/\\/g, '/')}`) {
  // VENDOR PATCH -- the only edit to this file, and not to the model.
  //
  // Upstream dereferenced process.argv[1] unguarded, and also auto-ran when
  // argv[1] merely ENDED WITH 'meter-reference.mjs'. Both break in service:
  //   - `node -e "import(...)"`, worker threads and some test runners leave
  //     argv[1] undefined, so .replace() threw before a single export could be
  //     read -- the module was simply un-importable;
  //   - the filename arm would fire the console demo INSIDE the service if the
  //     process were started from any path containing that name.
  //
  // The guard now asks 'am I the entry point', which is what was meant.
  runDemo();
}
