/**
 * @file test/ledger.test.js
 * The Ledger rollup, against a real MariaDB.
 *
 * These assertions matter more than most in the product. The Ledger's numbers
 * are eventually shown to the business owner who is PAYING for the work they
 * appear to credit — an audience that knows their own busy season and will
 * check. A lift claim that does not survive that conversation loses the account
 * and takes the retention tier with it.
 */

import { test, describe, before, after } from 'node:test';
import assert from 'node:assert/strict';
import { query, ping, closePool } from '../src/db.js';
import { weekStart, rollupBusiness, cohortTrend, accumulateDeadZones } from '../src/ledger/rollup.js';

let live = false;
let platformId = 0;
let bizId = 0;
const KEY = '__ledgertest';

before(async () => {
  live = await ping();
  if (!live) return;

  await query(
    `INSERT INTO oc_platform (platform_key, name, vertical, enabled, rollout)
     VALUES (?, 'Ledger Test', 'other', 1, 'shadow')
     ON DUPLICATE KEY UPDATE enabled = 1`,
    [KEY],
  );
  [{ id: platformId }] = await query('SELECT id FROM oc_platform WHERE platform_key = ?', [KEY]);

  await query(
    `INSERT INTO oc_business (platform_id, platform_business_key, name, city_key, niche_key, status)
     VALUES (?, ?, 'Ledger Test Shop', '__lt-city', 'barbershop', 'active')
     ON DUPLICATE KEY UPDATE name = VALUES(name)`,
    [platformId, `${KEY}-1`],
  );
  [{ id: bizId }] = await query(
    'SELECT id FROM oc_business WHERE platform_id = ? AND platform_business_key = ?',
    [platformId, `${KEY}-1`],
  );

  await query('DELETE FROM oc_vacancy_daily WHERE business_id = ?', [bizId]);
  await query('DELETE FROM oc_vacancy_weekly WHERE business_id = ?', [bizId]);
  await query('DELETE FROM oc_deadzone_cell WHERE business_id = ?', [bizId]);
});

after(async () => {
  if (live && bizId) {
    await query('DELETE FROM oc_vacancy_daily WHERE business_id = ?', [bizId]);
    await query('DELETE FROM oc_vacancy_weekly WHERE business_id = ?', [bizId]);
    await query('DELETE FROM oc_deadzone_cell WHERE business_id = ?', [bizId]);
    await query('DELETE FROM oc_business WHERE platform_id = ?', [platformId]);
    await query('DELETE FROM oc_platform WHERE platform_key = ?', [KEY]);
  }
  await closePool();
});

describe('week anchoring', () => {
  test('weeks start on Monday', () => {
    // A Sunday-anchored week splits every weekend across two columns, and
    // weekends are where a barbershop's utilisation actually lives.
    assert.equal(weekStart('2026-09-05'), '2026-08-31'); // Sat -> that Monday
    assert.equal(weekStart('2026-08-31'), '2026-08-31'); // Mon -> itself
    assert.equal(weekStart('2026-09-06'), '2026-08-31'); // Sun -> same week
    assert.equal(weekStart('2026-09-07'), '2026-09-07'); // next Mon -> new week
  });
});

describe('rollup', () => {
  test('a full week rolls up as a ratio of sums, not a mean of ratios', async (t) => {
    if (!live) return t.skip('no database');

    // Six ordinary days plus one thin Sunday. The Sunday is 100% vacant but has
    // a tiny denominator — a mean of daily ratios would let it drag the week
    // down by ~10 points on the strength of two open slots.
    const days = [
      ['2026-08-31', 40, 200], ['2026-09-01', 40, 200], ['2026-09-02', 40, 200],
      ['2026-09-03', 40, 200], ['2026-09-04', 40, 200], ['2026-09-05', 40, 200],
      ['2026-09-06', 2, 2],
    ];
    for (const [d, open, bookable] of days) {
      await query(
        `INSERT INTO oc_vacancy_daily
           (business_id, observed_on, horizon_days, open_slots, bookable_slots, vacancy_bp,
            empty_chair_cents, days_to_next, bucket, confidence)
         VALUES (?, ?, 7, ?, ?, ?, 1000, 0, 'neutral', 80)
         ON DUPLICATE KEY UPDATE open_slots = VALUES(open_slots)`,
        [bizId, d, open, bookable, Math.round((open / bookable) * 10000)],
      );
    }

    const written = await rollupBusiness(bizId, 8);
    assert.ok(written >= 1);

    const [w] = await query(
      'SELECT * FROM oc_vacancy_weekly WHERE business_id = ? AND week_start = ?',
      [bizId, '2026-08-31'],
    );
    assert.ok(w, 'the week should exist');
    assert.equal(Number(w.scan_days), 7);

    // 242 open of 1202 bookable = 20.1%. A mean of daily ratios would give
    // (6 x 20% + 100%) / 7 = 31.4% — eleven points of pure artefact.
    assert.ok(Math.abs(Number(w.avg_vacancy_bp) - 2013) < 30, `got ${w.avg_vacancy_bp}`);
    assert.equal(Number(w.fill_rate_bp), 10000 - Number(w.avg_vacancy_bp),
      'fill rate is exactly 1 - vacancy, in integers, so the 41%->68% claim cannot drift');
  });

  test('a partial week records HOW partial, so the UI can draw the gap', async (t) => {
    if (!live) return t.skip('no database');

    // Three days only — a blocked adapter, or a business added midweek.
    for (const d of ['2026-09-07', '2026-09-08', '2026-09-09']) {
      await query(
        `INSERT INTO oc_vacancy_daily
           (business_id, observed_on, horizon_days, open_slots, bookable_slots, vacancy_bp,
            empty_chair_cents, days_to_next, bucket, confidence)
         VALUES (?, ?, 7, 50, 200, 2500, 1000, 0, 'neutral', 80)
         ON DUPLICATE KEY UPDATE open_slots = VALUES(open_slots)`,
        [bizId, d],
      );
    }
    await rollupBusiness(bizId, 8);

    const [w] = await query(
      'SELECT * FROM oc_vacancy_weekly WHERE business_id = ? AND week_start = ?',
      [bizId, '2026-09-07'],
    );
    assert.equal(Number(w.scan_days), 3,
      'scan_days is what lets the chart show a hole instead of averaging what we happened to catch');
  });

  test('the dominant bucket is the mode, not the last reading', async (t) => {
    if (!live) return t.skip('no database');

    // Six hungry days and one slammed outlier last. Taking the last value would
    // relabel a hungry week as slammed and flip which pitch the product suggests.
    await query('DELETE FROM oc_vacancy_daily WHERE business_id = ? AND observed_on >= ?', [bizId, '2026-09-14']);
    const week = ['2026-09-14','2026-09-15','2026-09-16','2026-09-17','2026-09-18','2026-09-19','2026-09-20'];
    for (const [i, d] of week.entries()) {
      await query(
        `INSERT INTO oc_vacancy_daily
           (business_id, observed_on, horizon_days, open_slots, bookable_slots, vacancy_bp,
            empty_chair_cents, days_to_next, bucket, confidence)
         VALUES (?, ?, 7, 150, 200, 7500, 1000, 0, ?, 80)`,
        [bizId, d, i === 6 ? 'slammed' : 'hungry'],
      );
    }
    await rollupBusiness(bizId, 8);

    const [w] = await query(
      'SELECT dominant_bucket FROM oc_vacancy_weekly WHERE business_id = ? AND week_start = ?',
      [bizId, '2026-09-14'],
    );
    assert.equal(w.dominant_bucket, 'hungry');
  });

  test('re-running the rollup replaces, it does not duplicate', async (t) => {
    if (!live) return t.skip('no database');

    const before = await query('SELECT COUNT(*) AS n FROM oc_vacancy_weekly WHERE business_id = ?', [bizId]);
    await rollupBusiness(bizId, 8);
    await rollupBusiness(bizId, 8);
    const after = await query('SELECT COUNT(*) AS n FROM oc_vacancy_weekly WHERE business_id = ?', [bizId]);
    assert.equal(Number(after[0].n), Number(before[0].n));
  });
});

describe('the cohort — what makes a lift claim survivable', () => {
  test('cohort trend only counts weeks with real coverage', async (t) => {
    if (!live) return t.skip('no database');

    const rows = await cohortTrend('__lt-city', 'barbershop', 8);
    assert.ok(Array.isArray(rows));
    // The 3-day week must be excluded: a cohort baseline built from partial
    // weeks would move for reasons that have nothing to do with the market.
    const partial = rows.find((r) => String(r.week_start).slice(0, 10) === '2026-09-07');
    assert.equal(partial, undefined, 'weeks under 5 scan days are not cohort evidence');
  });
});

describe('dead-zone accumulator', () => {
  test('accumulates rather than storing a time series', async (t) => {
    if (!live) return t.skip('no database');

    const cells = [
      { dow: 1, blockStartHour: 13, openMinutes: 100, envelopeMinutes: 120, nicheKey: 'barbershop' },
      { dow: 1, blockStartHour: 15, openMinutes: 90, envelopeMinutes: 120, nicheKey: 'barbershop' },
      { dow: 5, blockStartHour: 13, openMinutes: 10, envelopeMinutes: 120, nicheKey: 'barbershop' },
    ];
    await accumulateDeadZones(bizId, cells, '__lt-city');
    await accumulateDeadZones(bizId, cells, '__lt-city');
    await accumulateDeadZones(bizId, cells, '__lt-city');

    const rows = await query('SELECT * FROM oc_deadzone_cell WHERE business_id = ? ORDER BY dow, block_start_hour', [bizId]);
    assert.equal(rows.length, 3, 'three observations of three cells stay three rows — fixed size, not a series');

    const tue = rows.find((r) => Number(r.dow) === 1 && Number(r.block_start_hour) === 13);
    assert.equal(Number(tue.obs_count), 3);
    assert.equal(Number(tue.open_slots_sum), 300, 'sums accumulate');
    assert.ok(Number(tue.open_ratio_bp) > 8000, 'a chronically open block reads high');

    const fri = rows.find((r) => Number(r.dow) === 5);
    assert.ok(Number(fri.open_ratio_bp) < 1500, 'a busy block reads low');
  });

  test('a closed block is not vacancy', async (t) => {
    if (!live) return t.skip('no database');

    // A shop shut on Sunday is a business fact, not empty time. Counting it
    // would make every closed day look like desperate availability.
    const before = await query('SELECT COUNT(*) AS n FROM oc_deadzone_cell WHERE business_id = ?', [bizId]);
    await accumulateDeadZones(bizId, [{ dow: 6, blockStartHour: 9, openMinutes: 0, envelopeMinutes: 0 }], '__lt-city');
    const after = await query('SELECT COUNT(*) AS n FROM oc_deadzone_cell WHERE business_id = ?', [bizId]);
    assert.equal(Number(after[0].n), Number(before[0].n));
  });
});
