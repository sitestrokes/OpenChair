/**
 * @file test/contract.test.js
 * The adapter boundary: the validator and the normaliser.
 *
 * The validator tests are the ones that earn their keep. PRD R5 —
 * "plausible-but-wrong adapter output" — has the largest blast radius in the
 * product, because a crash announces itself and a silent parse failure does not.
 * These assertions are what stand between a broken selector and a consultant
 * telling a struggling barbershop it is too busy to take customers.
 */

import { test, describe } from 'node:test';
import assert from 'node:assert/strict';
import { validateResult, validateAdapterModule, CAPABILITY, CAPABILITY_CEILING } from '../src/registry/contract.js';
import { normalise, toBasisPoints } from '../src/normalise/chairMinutes.js';

/** @returns {import('../src/registry/contract.js').AdapterResult} */
const good = (over = {}) => ({
  status: 'ok',
  capability: CAPABILITY.SLOT_GRID,
  adapterVersion: '1.0.0',
  slotMinutes: 30,
  units: [
    { staffHash: 'a1b2c3', day: '2026-09-05', openMinutes: 120, envelopeMinutes: 480 },
    { staffHash: 'd4e5f6', day: '2026-09-05', openMinutes: 60, envelopeMinutes: 480 },
    { staffHash: 'a1b2c3', day: '2026-09-06', openMinutes: 240, envelopeMinutes: 480 },
  ],
  ...over,
});

describe('validator — the silent parse failure', () => {
  test('accepts a well-formed grid', () => {
    assert.equal(validateResult(good()).ok, true);
  });

  test('REFUSES status ok with zero units', () => {
    // The signature failure: fetch succeeded, selector matched nothing, and
    // every downstream number becomes "100% booked".
    const r = validateResult(good({ units: [] }));
    assert.equal(r.ok, false);
    assert.equal(r.code, 'NO_UNITS');
  });

  test('REFUSES more open minutes than the shop is bookable for', () => {
    const r = validateResult(good({
      units: [{ staffHash: 'x', day: '2026-09-05', openMinutes: 900, envelopeMinutes: 480 }],
    }));
    assert.equal(r.ok, false);
    assert.equal(r.code, 'OPEN_EXCEEDS_ENVELOPE');
  });

  test('REFUSES an envelope longer than a day', () => {
    const r = validateResult(good({
      units: [{ staffHash: 'x', day: '2026-09-05', openMinutes: 10, envelopeMinutes: 2000 }],
    }));
    assert.equal(r.ok, false);
    assert.equal(r.code, 'ENVELOPE_TOO_LONG');
  });

  test('REFUSES a staff name where a hash belongs', () => {
    // A name here would put personal data into every stored snapshot.
    const r = validateResult(good({
      units: [{ staffHash: 'Maria Gonzalez', day: '2026-09-05', openMinutes: 60, envelopeMinutes: 480 }],
    }));
    assert.equal(r.ok, false);
    assert.equal(r.code, 'STAFF_NAME_LEAK');
  });

  test('REFUSES capability drift', () => {
    // A platform that changes shape underneath an adapter invalidates the
    // scoring model's assumptions about what its numbers mean.
    const r = validateResult(good({ capability: CAPABILITY.CLASS_CAPACITY }), { expectCapability: CAPABILITY.SLOT_GRID });
    assert.equal(r.ok, false);
    assert.equal(r.code, 'CAPABILITY_DRIFT');
  });

  test('REFUSES an unattributable result', () => {
    assert.equal(validateResult(good({ adapterVersion: '' })).code, 'NO_VERSION');
  });

  test('REFUSES a non-ISO day', () => {
    const r = validateResult(good({
      units: [{ staffHash: 'x', day: '05/09/2026', openMinutes: 60, envelopeMinutes: 480 }],
    }));
    assert.equal(r.code, 'BAD_DAY');
  });

  test('REFUSES an implausible price', () => {
    assert.equal(validateResult(good({ services: [{ key: 'cut', priceCents: 99999999 }] })).code, 'BAD_PRICE');
  });

  test('a blocked read needs no units', () => {
    const r = validateResult({ status: 'blocked', capability: CAPABILITY.SLOT_GRID, adapterVersion: '1.0.0' });
    assert.equal(r.ok, true);
  });

  test('fails closed on junk', () => {
    for (const junk of [null, undefined, 'x', 42, {}, []]) {
      assert.equal(validateResult(junk).ok, false, String(junk));
    }
  });
});

describe('validator — C5 next-available only', () => {
  const c5 = (over = {}) => ({ status: 'ok', capability: CAPABILITY.NEXT_AVAILABLE, adapterVersion: '1.0.0', daysToNext: 19, ...over });

  test('accepts a plausible lead time', () => {
    assert.equal(validateResult(c5()).ok, true);
  });
  test('refuses an absurd lead time', () => {
    assert.equal(validateResult(c5({ daysToNext: 5000 })).code, 'BAD_DAYS_TO_NEXT');
    assert.equal(validateResult(c5({ daysToNext: -1 })).code, 'BAD_DAYS_TO_NEXT');
  });
  test('C5 carries a hard confidence ceiling', () => {
    // A next-available date can never justify the certainty a full grid does.
    assert.ok(CAPABILITY_CEILING.C5 < CAPABILITY_CEILING.C1);
    assert.equal(CAPABILITY_CEILING.C5, 45);
  });
});

describe('adapter module shape — no write verbs', () => {
  const base = { read: () => {}, meta: { platformKey: 'booksy', capability: 'C1', version: '1.0.0' } };

  test('accepts a read-only adapter', () => {
    assert.equal(validateAdapterModule(base).ok, true);
  });

  test('requires read()', () => {
    assert.equal(validateAdapterModule({ meta: base.meta }).ok, false);
  });

  for (const verb of ['book', 'hold', 'reserve', 'cancel', 'submit', 'login', 'click']) {
    test(`REFUSES an adapter exporting ${verb}()`, () => {
      const r = validateAdapterModule({ ...base, [verb]: () => {} });
      assert.equal(r.ok, false);
      assert.match(r.reason, /forbidden verb/);
    });
  }
});

describe('normalise — chair-minutes', () => {
  test('sums minutes and computes basis points', () => {
    const g = normalise(good());
    assert.equal(g.openMinutes, 420);
    assert.equal(g.envelopeMinutes, 1440);
    assert.equal(g.vacancyBp, 2917);          // 420/1440 = 29.17%
    assert.equal(g.staffCount, 2);
    assert.equal(g.dayCount, 2);
  });

  test('basis points are integers — the Ledger claim cannot float-drift', () => {
    assert.equal(toBasisPoints(1, 3), 3333);
    assert.equal(Number.isInteger(toBasisPoints(1, 3)), true);
    assert.equal(toBasisPoints(0, 0), 0);      // no envelope is 0%, not NaN
    assert.equal(toBasisPoints(5, 1), 10000);  // clamped, never above 100%
  });

  test('daysToNext is derived from the grid, not trusted from a label', () => {
    const g = normalise(good({
      units: [
        { staffHash: 'a', day: '2026-09-05', openMinutes: 0, envelopeMinutes: 480 },
        { staffHash: 'a', day: '2026-09-06', openMinutes: 0, envelopeMinutes: 480 },
        { staffHash: 'a', day: '2026-09-07', openMinutes: 30, envelopeMinutes: 480 },
      ],
      daysToNext: 99,   // a stale banner on the page
    }));
    assert.equal(g.daysToNext, 2, 'the observed grid wins over the page label');
  });

  test('a fully-booked horizon reports null, not zero', () => {
    // "Nothing open in 14 days" is a LOWER BOUND, not a measurement. Reporting 0
    // would say "available today", which is the opposite of the truth.
    const g = normalise(good({
      units: [
        { staffHash: 'a', day: '2026-09-05', openMinutes: 0, envelopeMinutes: 480 },
        { staffHash: 'a', day: '2026-09-06', openMinutes: 0, envelopeMinutes: 480 },
      ],
    }));
    assert.equal(g.daysToNext, null);
    assert.equal(g.vacancyBp, 0);
    assert.ok(g.envelopeMinutes > 0, 'a full shop still has an envelope — that is what distinguishes it from a parse failure');
  });

  test('C5 normalises to a proxy, not a grid', () => {
    const g = normalise({ status: 'ok', capability: CAPABILITY.NEXT_AVAILABLE, adapterVersion: '1.0.0', daysToNext: 19 });
    assert.equal(g.proxyOnly, true);
    assert.equal(g.daysToNext, 19);
    assert.equal(g.envelopeMinutes, 0);
  });
});
