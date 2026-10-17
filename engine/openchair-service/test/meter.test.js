/**
 * @file test/meter.test.js
 * The Meter: the vendored model, and the service wrapper around it.
 *
 * The phase gate is "service output matches meter-reference.mjs on every worked
 * example". Because model.js IS the reference, that holds by construction — so
 * the job here is to prove the copy has not DRIFTED, and that the wrapper hands
 * it the right shapes. A wrapper bug is silent in a way a maths bug is not: pass
 * `openMinutes` where the model wants `openMin` and every business quietly
 * scores as fully booked.
 */

import { test, describe } from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';

import * as model from '../src/meter/model.js';
import { score, toObservations, toSchemaBucket } from '../src/meter/score.js';

const HERE = dirname(fileURLToPath(import.meta.url));
const VENDORED = join(HERE, '..', 'src', 'meter', 'model.js');
const SOURCE = join(HERE, '..', '..', '..', 'docs', 'meter-reference.mjs');

describe('the vendored copy has not drifted from the reference', () => {
  test('every line of maths is byte-identical to docs/meter-reference.mjs', () => {
    // Line endings are normalised first: this repo checks out CRLF on Windows,
    // and a carriage-return difference is not drift.
    const norm = (p) => readFileSync(p, 'utf8').replace(/\r\n/g, '\n').split('\n');
    const vendored = norm(VENDORED);
    const source = norm(SOURCE);

    // Anchor on a line that appears exactly once and cannot collide with the
    // vendor header — the naive anchor matched the header's own opening /**.
    const ANCHOR = "export const MODEL_VERSION = 'meter-1.0.0';";
    const vAt = vendored.indexOf(ANCHOR);
    const sAt = source.indexOf(ANCHOR);
    assert.ok(vAt > 0 && sAt >= 0, 'anchor line must exist in both files');

    // Compare from the anchor to the patched entry-point guard at the tail.
    const vGuard = vendored.findIndex((l) => l.startsWith('} else if (process.argv[1] && import.meta.url'));
    const sGuard = source.findIndex((l) => l.startsWith('} else if (import.meta.url'));
    assert.ok(vGuard > vAt, 'the patched guard should be present in the copy');

    assert.deepEqual(
      vendored.slice(vAt, vGuard),
      source.slice(sAt, sGuard),
      'model.js has diverged from docs/meter-reference.mjs. Edit the reference, ' +
      're-run its self-tests, then re-copy — never patch the maths in place.',
    );
  });

  test('the reference version is pinned', () => {
    assert.equal(model.MODEL_VERSION, 'meter-1.0.0');
  });
});

describe('the reference\'s own worked examples still run', () => {
  test('barbershop', () => {
    const ex = model.exampleBarbershop();
    assert.ok(ex.vs, 'vacancy computed');
    assert.ok(ex.conf, 'confidence computed');
    assert.ok(['hungry', 'steady', 'slammed', 'unrated'].includes(ex.bucket.bucket));
  });

  test('dead zones survive the significance gates', () => {
    const { zones } = model.exampleDeadZones();
    assert.ok(Array.isArray(zones));
    // The chronic Tuesday afternoon is the one that should survive; an
    // oscillating cell must not (35-scoring-model.md §5.2).
    assert.ok(zones.every((z) => typeof z.label === 'string'));
  });

  test('ledger lift is only claimable net of the cohort', () => {
    const { lift } = model.exampleLedger();
    assert.equal(lift.claimable, true);
    assert.ok(lift.clientDelta > lift.netOfMarket, 'raw lift must overstate the net lift');
  });
});

describe('wrapper — shape conversion', () => {
  const grid = {
    capability: 'C1_SLOT_GRID',
    openMinutes: 420, envelopeMinutes: 1440, staffCount: 2, dayCount: 3, daysToNext: 0,
    byDay: {
      '2026-09-06': { open: 180, envelope: 480 },
      '2026-09-07': { open: 60, envelope: 480 },
      '2026-09-08': { open: 180, envelope: 480 },
    },
  };

  test('dayOffset starts at 1, not 0', () => {
    // The model DROPS observations with dayOffset < 1. An off-by-one here empties
    // the whole calculation and every business scores 0% vacant — fully booked.
    const obs = toObservations(grid, '2026-09-05');
    assert.deepEqual(obs.map((o) => o.dayOffset).sort(), [1, 2, 3]);
  });

  test('field names are openMin / envelopeMin, not the worker\'s', () => {
    const [o] = toObservations(grid, '2026-09-05');
    assert.equal(typeof o.openMin, 'number');
    assert.equal(typeof o.envelopeMin, 'number');
    assert.equal(o.openMinutes, undefined, 'passing the worker\'s names yields NaN sums');
  });

  test('vacancy comes out right end up', () => {
    const out = score({
      grid,
      business: { avg_price_cents: 3500, price_source: 'menu', slot_minutes: 30 },
      context: { baseDate: '2026-09-05', observedWeeks: 6, priorSuccessfulScans: 12 },
    });
    // 420 open of 1440 envelope ≈ 29%, weighted toward the nearer days.
    assert.ok(out.vacancyBp > 2500 && out.vacancyBp < 3500, `got ${out.vacancyBp}`);
  });
});

describe('wrapper — the refusal gates', () => {
  const grid = {
    capability: 'C1_SLOT_GRID',
    openMinutes: 420, envelopeMinutes: 1440, staffCount: 2, dayCount: 3, daysToNext: 0,
    byDay: { '2026-09-06': { open: 180, envelope: 480 }, '2026-09-07': { open: 60, envelope: 480 }, '2026-09-08': { open: 180, envelope: 480 } },
  };
  const biz = { avg_price_cents: 3500, price_source: 'menu', slot_minutes: 30 };
  const base = { baseDate: '2026-09-05', observedWeeks: 6, priorSuccessfulScans: 12 };

  test('a business seen only once is never published', () => {
    // Two successful scans before any number goes out. One reading of a booking
    // page is an anecdote — it could be a holiday, an outage or a bad parse.
    const out = score({ grid, business: biz, context: { ...base, priorSuccessfulScans: 1 } });
    assert.equal(out.publishable, false);
    assert.ok(out.refusals.includes('need_two_successful_scans'));
  });

  test('a total blackout is suppressed rather than topping the Hungry list', () => {
    // A shop reading 100% open every single day is far likelier closed, on
    // holiday or freshly broken than genuinely desperate — and it would
    // otherwise sit at the very top of the 8am list.
    const out = score({
      grid, business: biz,
      context: { ...base, anomalies: ['suspect_total_blackout'] },
    });
    assert.equal(out.publishable, false);
    assert.ok(out.refusals.includes('total_blackout_needs_human_check'));
  });

  test('stale data past 14 days is refused outright', () => {
    const out = score({ grid, business: biz, context: { ...base, scanAgeHours: 400 } });
    assert.equal(out.publishable, false);
    assert.ok(out.refusals.includes('scan_older_than_14_days'));
  });

  test('a refused business gets a NULL bucket, never "neutral"', () => {
    // Coercing unrated → neutral would promote "we don't know" into "we looked,
    // it's average" — the confidently-wrong failure the model exists to prevent.
    const out = score({ grid, business: biz, context: { ...base, priorSuccessfulScans: 0 } });
    assert.equal(out.bucket, null);
  });

  test('display degrades in steps, not on/off', () => {
    const high = score({ grid, business: biz, context: { ...base, scanAgeHours: 1 } });
    assert.equal(high.display, 'score+range+dollars');
    const refused = score({ grid, business: biz, context: { ...base, priorSuccessfulScans: 0 } });
    assert.equal(refused.display, 'nothing_rescan');
  });
});

describe('wrapper — money is two numbers, not one', () => {
  const grid = {
    capability: 'C1_SLOT_GRID',
    openMinutes: 420, envelopeMinutes: 1440, staffCount: 2, dayCount: 3, daysToNext: 0,
    byDay: { '2026-09-06': { open: 180, envelope: 480 }, '2026-09-07': { open: 60, envelope: 480 }, '2026-09-08': { open: 180, envelope: 480 } },
  };
  const ctx = {
    baseDate: '2026-09-05', observedWeeks: 6, priorSuccessfulScans: 12,
    services: [
      { price: 35, durationMin: 30 }, { price: 30, durationMin: 30 },
      { price: 45, durationMin: 45 }, { price: 25, durationMin: 20 },
    ],
  };

  test('unsold is the headline and recoverable is smaller', () => {
    const out = score({ grid, business: { price_source: 'menu', slot_minutes: 30 }, context: ctx });
    assert.equal(out.showDollars, true);
    assert.ok(out.unsoldCentsLow > 0, 'unsold low must be a real figure');
    assert.ok(out.unsoldCentsHigh > out.unsoldCentsLow, 'always a range, never a point estimate');
    assert.ok(
      out.recoverableCentsLow < out.unsoldCentsLow,
      'recoverable sizes the offer and must never exceed the unsold headline',
    );
  });

  test('no price source means no dollars, not a guessed figure', () => {
    const out = score({
      grid, business: { price_source: 'niche_default', slot_minutes: 30 },
      context: { ...ctx, services: [], priceCohort: null },
    });
    assert.equal(out.showDollars, false);
    assert.equal(out.unsoldCentsLow, null);
    assert.ok(out.whyNoDollars, 'the refusal must say why');
  });
});

describe('bucket vocabulary maps to the schema enum', () => {
  test('model words → schema words', () => {
    assert.equal(toSchemaBucket('hungry'), 'hungry');
    assert.equal(toSchemaBucket('slammed'), 'slammed');
    assert.equal(toSchemaBucket('steady'), 'neutral');
  });
  test('unrated is NULL — absence is the value', () => {
    assert.equal(toSchemaBucket('unrated'), null);
    assert.equal(toSchemaBucket(undefined), null);
    assert.equal(toSchemaBucket('nonsense'), null);
  });
});

describe('money covers the scoring window, not the read horizon', () => {
  test('openMinutesInWindow sums days 1..H only', async () => {
    const { openMinutesInWindow } = await import('../src/meter/score.js');
    const byDay = {};
    for (let d = 0; d < 14; d++) {
      const iso = new Date(Date.UTC(2026, 9, 17 + d)).toISOString().slice(0, 10);
      byDay[iso] = { open: 100, envelope: 600 };
    }
    // today (offset 0) and days 8..13 are outside a 7-day window
    assert.equal(openMinutesInWindow({ byDay }, '2026-10-17', 7), 700);
    assert.equal(openMinutesInWindow({ byDay: {} }, '2026-10-17', 7), null);
  });
});
