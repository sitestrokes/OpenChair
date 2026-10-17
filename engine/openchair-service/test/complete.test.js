/**
 * @file test/complete.test.js
 * A worker's completion, end to end, against a real MariaDB.
 *
 * docs/70-radar-build.md §3. The assertions that matter most:
 *   - a snapshot holds SCHEMA hashes and no name, ever;
 *   - a worker cannot complete a queue row it does not hold;
 *   - a completed scan becomes a scored row the screens read (latest_*), a
 *     weekly rollup row, and dead-zone cells — the whole chain, not just the run.
 *
 * Self-seeding, self-cleaning, skips without a database (queue.test.js house
 * style). Its own platform and business keys never collide with the demo seed.
 */

import { test, describe, before, after } from 'node:test';
import assert from 'node:assert/strict';
import { gunzipSync } from 'node:zlib';
import { createHash } from 'node:crypto';
import { query, ping, closePool } from '../src/db.js';
import { completeScan, deadZoneCells, schemaStaffHash } from '../src/scan/complete.js';
import { PayloadError, findNameKey } from '../src/scan/payload.js';

const KEY = '__ctest';
let live = false;
/** @type {number} */ let platformId;
/** @type {number} */ let businessId;
/** @type {number} */ let queueId;

const sha1 = (s) => createHash('sha1').update(s).digest('hex');
const iso = (d) => d.toISOString().slice(0, 10);
const TODAY = iso(new Date());

/**
 * A §3.2 payload: 3 chairs, 7 days from tomorrow, 9a–7p envelopes, 30-min slots,
 * chair 3 emptiest. Built the way a worker would send it.
 */
function payload(over = {}) {
  const staff = ['r-101', 'r-102', 'r-103'].map((k) => ({ staffHash: sha1(`booksy:${k}`), staffKey: k }));
  const days = [];
  const byDay = {};
  let open = 0, env = 0, firstOpen = null;
  for (let i = 1; i <= 7; i++) {
    const d = new Date(); d.setUTCDate(d.getUTCDate() + i);
    const date = iso(d);
    const units = staff.map((s, si) => {
      const opens = [];
      for (let m = 540; m < 1140; m += 30) {
        // Deterministic emptiness: chair 3 half empty, chair 1 mostly booked.
        if ((m / 30 + i + si * 3) % (si === 2 ? 2 : 5) === 0) opens.push(m);
      }
      return { staffHash: s.staffHash, envelope: [540, 1140], open: opens };
    });
    const o = units.reduce((a, u) => a + u.open.length * 30, 0);
    const e = units.length * 600;
    byDay[date] = { open: o, envelope: e };
    open += o; env += e;
    if (firstOpen === null && o > 0) firstOpen = i - 1;
    days.push({ date, units });
  }
  return {
    queueId, businessId, platformId, observedOn: TODAY,
    status: 'ok', adapterVersion: '0.1.0', tier: 1, bytes: 18000, costMicros: 0, durationMs: 120,
    capability: 'C1_SLOT_GRID', horizonDays: 7, slotMinutes: 30,
    grid: {
      openMinutes: open, envelopeMinutes: env, vacancyBp: Math.round((open / env) * 10000),
      staffCount: 3, dayCount: 7, daysToNext: firstOpen, byDay,
    },
    snapshot: { v: 1, slotMinutes: 30, days },
    staff,
    services: [
      { key: 's1', name: 'Haircut', durationMin: 30, priceCents: 3500 },
      { key: 's2', name: 'Skin fade', durationMin: 45, priceCents: 4500 },
      { key: 's3', name: 'Beard trim', durationMin: 30, priceCents: 2500 },
      { key: 's4', name: 'Kids cut', durationMin: 30, priceCents: 3000 },
    ],
    ...over,
  };
}

async function lockQueue() {
  await query(
    `UPDATE oc_scan_queue SET status = 'locked', locked_by = 'ctest', locked_at = NOW() WHERE id = ?`,
    [queueId],
  );
}

async function cleanup() {
  if (!businessId) return;
  for (const t of ['oc_scan_snapshot', 'oc_scan_run', 'oc_staff', 'oc_business_service', 'oc_vacancy_daily',
    'oc_vacancy_weekly', 'oc_deadzone_cell', 'oc_scan_queue']) {
    await query(`DELETE FROM ${t} WHERE business_id = ?`, [businessId]);
  }
}

before(async () => {
  live = await ping();
  if (!live) return;
  await query(
    `INSERT INTO oc_platform (platform_key, name, vertical, enabled, rollout)
     VALUES (?, 'Completion Test', 'beauty', 1, 'shadow')
     ON DUPLICATE KEY UPDATE enabled = 1, rollout = 'shadow'`,
    [KEY],
  );
  [{ id: platformId }] = await query('SELECT id FROM oc_platform WHERE platform_key = ?', [KEY]);
  await query(
    `INSERT INTO oc_business (platform_id, platform_business_key, name, city_key, niche_key, status, booking_url, timezone, price_source)
     VALUES (?, ?, 'Completion Test Shop', '__ctest-city', 'barbershop', 'active', 'https://example.invalid/c', 'America/New_York', 'niche_default')
     ON DUPLICATE KEY UPDATE status = 'active'`,
    [platformId, `${KEY}-1`],
  );
  [{ id: businessId }] = await query(
    'SELECT id FROM oc_business WHERE platform_id = ? AND platform_business_key = ?', [platformId, `${KEY}-1`],
  );
  await cleanup();
  const r = await query(
    `INSERT INTO oc_scan_queue (business_id, platform_id, priority, watcher_count, due_at, status)
     VALUES (?, ?, 5, 0, NOW(), 'due')`,
    [businessId, platformId],
  );
  queueId = Number(r.insertId);
});

after(async () => {
  if (live) {
    await cleanup();
    await query('DELETE FROM oc_business WHERE platform_id = ?', [platformId]);
    await query('DELETE FROM oc_platform WHERE platform_key = ?', [KEY]);
  }
  await closePool();
});

describe('the claim is verified', () => {
  test('a row that is not locked cannot be completed', async (t) => {
    if (!live) return t.skip('no database');
    await query(`UPDATE oc_scan_queue SET status = 'due', locked_by = NULL WHERE id = ?`, [queueId]);
    await assert.rejects(() => completeScan(payload()), (e) => e instanceof PayloadError && e.code === 'QUEUE_MISMATCH');
    const runs = await query('SELECT id FROM oc_scan_run WHERE business_id = ?', [businessId]);
    assert.equal(runs.length, 0, 'a refused completion must leave no run behind');
  });

  test('a locked row cannot be completed for a different business', async (t) => {
    if (!live) return t.skip('no database');
    await lockQueue();
    await assert.rejects(
      () => completeScan(payload({ businessId: businessId + 1_000_000 })),
      (e) => e.code === 'QUEUE_MISMATCH',
    );
  });
});

describe('names never reach a snapshot', () => {
  test('a staff entry with a name key is refused', async (t) => {
    if (!live) return t.skip('no database');
    await lockQueue();
    const p = payload();
    p.staff[0].name = 'Maria Lopez';
    await assert.rejects(() => completeScan(p), (e) => e.code === 'STAFF_NAME_LEAK');
  });

  test('a snapshot unit with displayName is refused', async (t) => {
    if (!live) return t.skip('no database');
    const p = payload();
    p.snapshot.days[0].units[0].displayName = 'Jo';
    await assert.rejects(() => completeScan(p), (e) => e.code === 'STAFF_NAME_LEAK');
  });

  test('a staffKey that looks like a person is refused', async (t) => {
    if (!live) return t.skip('no database');
    const p = payload();
    p.staff[1].staffKey = 'Maria Lopez';
    await assert.rejects(() => completeScan(p), (e) => e.code === 'BAD_STAFF');
  });

  test('findNameKey walks nested arrays', () => {
    assert.equal(findNameKey({ a: [{ b: { staffName: 'x' } }] }, 'p'), 'p.a[0].b.staffName');
    assert.equal(findNameKey({ a: [{ b: 1 }] }, 'p'), null);
  });
});

describe('the contract is enforced server-side', () => {
  test('open time beyond the envelope is refused', async (t) => {
    if (!live) return t.skip('no database');
    const p = payload();
    p.grid.openMinutes = p.grid.envelopeMinutes + 1;
    await assert.rejects(() => completeScan(p), (e) => e.code === 'BAD_GRID');
  });

  test('an open slot outside its envelope is refused', async (t) => {
    if (!live) return t.skip('no database');
    const p = payload();
    p.snapshot.days[0].units[0].open.push(1200);
    await assert.rejects(() => completeScan(p), (e) => e.code === 'BAD_SNAPSHOT');
  });

  test('ok without a grid is refused', async (t) => {
    if (!live) return t.skip('no database');
    const p = payload();
    delete p.grid;
    await assert.rejects(() => completeScan(p), (e) => e.code === 'NO_GRID');
  });
});

describe('a good completion becomes a published number', () => {
  /** @type {any} */ let result;

  test('completes and scores', async (t) => {
    if (!live) return t.skip('no database');
    await lockQueue();
    result = await completeScan(payload());
    assert.ok(result.scored, 'an ok scan returns its score');
    assert.ok(result.scored.vacancyBp > 0 && result.scored.vacancyBp < 10000);
    assert.ok(Number.isInteger(result.scored.confidence));
  });

  test('records the run and releases the queue row', async (t) => {
    if (!live) return t.skip('no database');
    const [run] = await query('SELECT * FROM oc_scan_run WHERE business_id = ? AND observed_on = ?', [businessId, TODAY]);
    assert.equal(run.status, 'ok');
    assert.equal(run.adapter_version, '0.1.0');
    const [q] = await query('SELECT status, locked_by FROM oc_scan_queue WHERE id = ?', [queueId]);
    assert.equal(q.status, 'due');
    assert.equal(q.locked_by, null);
  });

  test('stores a snapshot with schema hashes and no names', async (t) => {
    if (!live) return t.skip('no database');
    const [snap] = await query('SELECT * FROM oc_scan_snapshot WHERE business_id = ?', [businessId]);
    assert.ok(snap, 'a snapshot row exists');
    assert.equal(snap.grid_codec, 'gzip_json_v1');
    assert.equal(Number(snap.staff_count), 3);
    const raw = gunzipSync(snap.grid_gz).toString('utf8');
    assert.equal(createHash('sha1').update(raw).digest('hex'), snap.grid_sha1, 'grid_sha1 is the hash of the stored JSON');
    const grid = JSON.parse(raw);
    assert.equal(grid.days.length, 7);
    const hashes = new Set(grid.days.flatMap((d) => d.units.map((u) => u.staffHash)));
    assert.deepEqual([...hashes].sort(), ['r-101', 'r-102', 'r-103'].map((k) => schemaStaffHash(businessId, k)).sort(),
      'every unit carries the business-scoped schema hash, not the adapter hash');
    assert.equal(findNameKey(grid, 'snap'), null);
    assert.doesNotMatch(raw, /Maria|name/i);
    const days = Math.round((Date.parse(snap.purge_after) - Date.parse(snap.captured_on)) / 86400000);
    assert.equal(days, 14, 'snapshots purge 14 days after capture');
  });

  test('labels chairs, never names them', async (t) => {
    if (!live) return t.skip('no database');
    const staff = await query('SELECT staff_key, staff_hash, label, display_name FROM oc_staff WHERE business_id = ? ORDER BY id', [businessId]);
    assert.deepEqual(staff.map((s) => s.label), ['Chair 1', 'Chair 2', 'Chair 3']);
    assert.ok(staff.every((s) => s.display_name === null));
    assert.equal(staff[0].staff_hash, schemaStaffHash(businessId, 'r-101'));
  });

  test('stores the menu and prices the business from it', async (t) => {
    if (!live) return t.skip('no database');
    const svc = await query('SELECT name, price_cents FROM oc_business_service WHERE business_id = ? ORDER BY name', [businessId]);
    assert.equal(svc.length, 4);
    const [b] = await query('SELECT * FROM oc_business WHERE id = ?', [businessId]);
    assert.equal(b.price_source, 'menu');
    assert.equal(Number(b.avg_price_cents), 3375);
    assert.equal(Number(b.staff_count), 3);
    assert.equal(b.latest_observed_on, TODAY, 'the projection the screens read is updated');
    assert.equal(Number(b.latest_vacancy_bp), result.scored.vacancyBp);
    assert.equal(Number(b.latest_confidence), result.scored.confidence);
  });

  test('writes the daily row, the weekly rollup and dead-zone cells', async (t) => {
    if (!live) return t.skip('no database');
    const [d] = await query('SELECT * FROM oc_vacancy_daily WHERE business_id = ? AND observed_on = ?', [businessId, TODAY]);
    assert.ok(d, 'daily row');
    assert.ok(Number(d.scan_run_id) > 0);
    const weekly = await query('SELECT * FROM oc_vacancy_weekly WHERE business_id = ?', [businessId]);
    assert.ok(weekly.length >= 1, 'weekly rollup row');
    const cells = await query('SELECT COUNT(*) AS n, MAX(obs_count) AS mx FROM oc_deadzone_cell WHERE business_id = ?', [businessId]);
    assert.ok(Number(cells[0].n) > 0, 'dead-zone cells accumulated');
    assert.equal(Number(cells[0].mx), 1);
  });

  test('a retried completion of the same grid does not double-count dead zones', async (t) => {
    if (!live) return t.skip('no database');
    await lockQueue();
    await completeScan(payload());
    const [c] = await query('SELECT MAX(obs_count) AS mx FROM oc_deadzone_cell WHERE business_id = ?', [businessId]);
    assert.equal(Number(c.mx), 1, 'same observation, same count');
    const runs = await query('SELECT id FROM oc_scan_run WHERE business_id = ?', [businessId]);
    assert.equal(runs.length, 1, 'still one run for the day');
    const snaps = await query('SELECT id FROM oc_scan_snapshot WHERE business_id = ?', [businessId]);
    assert.equal(snaps.length, 1, 'still one snapshot for the run');
    const staff = await query('SELECT id FROM oc_staff WHERE business_id = ?', [businessId]);
    assert.equal(staff.length, 3, 'chairs are not re-created');
  });

  test('a failed scan records the run but never overwrites the last good number', async (t) => {
    if (!live) return t.skip('no database');
    const [before] = await query('SELECT latest_vacancy_bp FROM oc_business WHERE id = ?', [businessId]);
    await lockQueue();
    const out = await completeScan({
      queueId, businessId, platformId, observedOn: '2026-01-02', status: 'blocked', errorCode: 'HTTP_403', error: 'blocked',
    });
    assert.equal(out.scored, null);
    const [after] = await query('SELECT latest_vacancy_bp FROM oc_business WHERE id = ?', [businessId]);
    assert.equal(after.latest_vacancy_bp, before.latest_vacancy_bp);
  });
});

describe('dead-zone cells', () => {
  test('closed time is not vacant time', () => {
    const cells = deadZoneCells({
      slotMinutes: 30,
      days: [{ date: '2026-10-20', units: [{ staffHash: 'x', envelope: [600, 720], open: [600, 630] }] }],
    });
    // 2026-10-20 is a Tuesday → dow 1. Envelope 10:00–12:00 sits in the 10–12 block only.
    assert.deepEqual(cells, [{ dow: 1, blockStartHour: 10, openMinutes: 60, envelopeMinutes: 120, nicheKey: '' }]);
  });
});
