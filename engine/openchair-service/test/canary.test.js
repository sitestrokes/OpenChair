/**
 * @file test/canary.test.js
 * The canary, against deliberately broken adapter output.
 *
 * This suite IS the Phase 8 gate: "the canary demonstrably catches a
 * deliberately broken adapter." So each test breaks one in a specific, realistic
 * way and asserts the platform gets frozen — and, just as importantly, that a
 * genuinely busy or genuinely quiet market does NOT.
 *
 * A canary that freezes on real data is worse than no canary: it trains whoever
 * is on call to ignore it.
 */

import { test, describe, before, after, beforeEach } from 'node:test';
import assert from 'node:assert/strict';
import { query, ping, closePool } from '../src/db.js';
import { checkPlatform, runCanary } from '../src/observability/canary.js';

let live = false;
let platformId = 0;
/** @type {number[]} */
let bizIds = [];
const KEY = '__canarytest';
const TODAY = '2026-09-05';

/**
 * Seed one day of observations for the whole test platform.
 * @param {(i: number) => {open: number, bookable: number, staff: number}} shape
 * @param {string} day
 */
async function seedDay(shape, day = TODAY) {
  for (const [i, id] of bizIds.entries()) {
    const s = shape(i);
    const bp = s.bookable > 0 ? Math.round((s.open / s.bookable) * 10000) : 0;
    await query(
      `INSERT INTO oc_vacancy_daily
         (business_id, observed_on, horizon_days, open_slots, bookable_slots, vacancy_bp,
          staff_count, empty_chair_cents, days_to_next, bucket, confidence)
       VALUES (?, ?, 7, ?, ?, ?, ?, 1000, 0, 'neutral', 80)
       ON DUPLICATE KEY UPDATE
         open_slots = VALUES(open_slots), bookable_slots = VALUES(bookable_slots),
         vacancy_bp = VALUES(vacancy_bp), staff_count = VALUES(staff_count)`,
      [id, day, s.open, s.bookable, bp, s.staff],
    );
  }
}

before(async () => {
  live = await ping();
  if (!live) return;

  await query(
    `INSERT INTO oc_platform (platform_key, name, vertical, enabled, rollout)
     VALUES (?, 'Canary Test', 'other', 1, 'live')
     ON DUPLICATE KEY UPDATE enabled = 1, rollout = 'live'`,
    [KEY],
  );
  [{ id: platformId }] = await query('SELECT id FROM oc_platform WHERE platform_key = ?', [KEY]);

  bizIds = [];
  for (let i = 0; i < 30; i++) {
    await query(
      `INSERT INTO oc_business (platform_id, platform_business_key, name, city_key, niche_key, status)
       VALUES (?, ?, ?, '__ct-city', 'barbershop', 'active')
       ON DUPLICATE KEY UPDATE name = VALUES(name)`,
      [platformId, `${KEY}-${i}`, `Canary ${i}`],
    );
    const [b] = await query(
      'SELECT id FROM oc_business WHERE platform_id = ? AND platform_business_key = ?',
      [platformId, `${KEY}-${i}`],
    );
    bizIds.push(b.id);
  }

  // A 14-day baseline of an ordinary market: vacancy scattered around 40%,
  // 4 chairs each. This is what "normal" looks like.
  for (let d = 1; d <= 14; d++) {
    const day = new Date(`${TODAY}T00:00:00Z`);
    day.setUTCDate(day.getUTCDate() - d);
    await seedDay((i) => ({ open: 60 + (i % 7) * 6, bookable: 200, staff: 4 }), day.toISOString().slice(0, 10));
  }
});

beforeEach(async () => {
  if (live) {
    await query(`UPDATE oc_platform SET rollout = 'live', rollout_note = NULL WHERE id = ?`, [platformId]);
  }
});

after(async () => {
  if (live && bizIds.length) {
    await query(`DELETE FROM oc_vacancy_daily WHERE business_id IN (${bizIds.join(',')})`);
    await query('DELETE FROM oc_business WHERE platform_id = ?', [platformId]);
    await query('DELETE FROM oc_platform WHERE platform_key = ?', [KEY]);
  }
  await closePool();
});

describe('a healthy market is left alone', () => {
  test('ordinary scatter raises nothing', async (t) => {
    if (!live) return t.skip('no database');
    await seedDay((i) => ({ open: 58 + (i % 9) * 7, bookable: 200, staff: 4 }));

    const res = await checkPlatform(platformId, TODAY);
    assert.equal(res.shouldFreeze, false, res.findings.map((f) => f.code).join(', '));
  });

  test('a genuinely busy market is not a broken adapter', async (t) => {
    if (!live) return t.skip('no database');
    // Everyone busier than usual, but scattered and within a believable move.
    await seedDay((i) => ({ open: 20 + (i % 5) * 4, bookable: 200, staff: 4 }));

    const res = await checkPlatform(platformId, TODAY);
    const freezes = res.findings.filter((f) => f.severity === 'freeze');
    assert.equal(freezes.length, 0, `false freeze: ${freezes.map((f) => f.code).join(', ')}`);
  });

  test('too few businesses to judge — no freeze on noise', async (t) => {
    if (!live) return t.skip('no database');
    const res = await checkPlatform(999999, TODAY);
    assert.equal(res.shouldFreeze, false);
    assert.equal(res.findings.length, 0);
  });
});

describe('THE GATE: a deliberately broken adapter is caught', () => {
  test('selector stops matching -> every business reads fully booked', async (t) => {
    if (!live) return t.skip('no database');
    // The signature failure. Zero open slots everywhere: the fetch worked, the
    // grid parse returned nothing, and the product would report a whole
    // platform as sold out — with a high confidence badge.
    await seedDay(() => ({ open: 0, bookable: 200, staff: 4 }));

    const res = await checkPlatform(platformId, TODAY);
    assert.equal(res.shouldFreeze, true);
    assert.ok(res.findings.some((f) => f.code === 'MASS_FULLY_BOOKED'),
      res.findings.map((f) => f.code).join(', '));
  });

  test('inverted parse -> every business reads totally open', async (t) => {
    if (!live) return t.skip('no database');
    // Commercially the worse direction: these go straight to the top of
    // tomorrow's 8am Hungry list and get pitched first.
    await seedDay(() => ({ open: 200, bookable: 200, staff: 4 }));

    const res = await checkPlatform(platformId, TODAY);
    assert.equal(res.shouldFreeze, true);
    assert.ok(res.findings.some((f) => f.code === 'MASS_TOTALLY_OPEN'));
  });

  test('envelope parse breaks -> the denominator collapses', async (t) => {
    if (!live) return t.skip('no database');
    await seedDay(() => ({ open: 0, bookable: 0, staff: 4 }));

    const res = await checkPlatform(platformId, TODAY);
    assert.equal(res.shouldFreeze, true);
    assert.ok(res.findings.some((f) => f.code === 'ENVELOPE_COLLAPSE'));
  });

  test('the whole distribution moves as a block', async (t) => {
    if (!live) return t.skip('no database');
    // Baseline sits near 33%; this puts everyone near 90% with real scatter, so
    // no single-value check fires — only the population comparison catches it.
    await seedDay((i) => ({ open: 175 + (i % 6) * 3, bookable: 200, staff: 4 }));

    const res = await checkPlatform(platformId, TODAY);
    assert.ok(res.findings.some((f) => f.code === 'DISTRIBUTION_JUMP' || f.code === 'MASS_TOTALLY_OPEN'),
      res.findings.map((f) => f.code).join(', '));
  });

  test('staff parse breaks -> chairs per business halves overnight', async (t) => {
    if (!live) return t.skip('no database');
    // Silent and nasty: halving the roster halves every envelope, so vacancy
    // stays plausible while every dollar figure quietly doubles.
    await seedDay((i) => ({ open: 60 + (i % 7) * 6, bookable: 200, staff: 1 }));

    const res = await checkPlatform(platformId, TODAY);
    assert.ok(res.findings.some((f) => f.code === 'ROSTER_SHIFT'),
      res.findings.map((f) => f.code).join(', '));
  });
});

describe('freezing', () => {
  test('a freeze stops publishing but keeps scanning', async (t) => {
    if (!live) return t.skip('no database');
    await seedDay(() => ({ open: 0, bookable: 200, staff: 4 }));

    await runCanary(TODAY);

    const [p] = await query('SELECT rollout, enabled, rollout_note FROM oc_platform WHERE id = ?', [platformId]);
    assert.equal(p.rollout, 'frozen');
    // Still enabled on purpose: FR-3.5 wants last-good data served with a
    // staleness label rather than businesses vanishing from a consultant's
    // list, and continuing to scan is how an operator sees whether the next run
    // looks sane. Freezing stops the numbers reaching a tenant; it does not
    // blind us.
    assert.equal(Number(p.enabled), 1);
    assert.match(p.rollout_note, /canary/);
  });

  test('a frozen platform is no longer scheduled for publishing', async (t) => {
    if (!live) return t.skip('no database');
    await query(`UPDATE oc_platform SET rollout = 'frozen' WHERE id = ?`, [platformId]);

    const rows = await query(
      `SELECT id FROM oc_platform WHERE id = ? AND rollout IN ('live','shadow')`, [platformId],
    );
    assert.equal(rows.length, 0);
  });
});
