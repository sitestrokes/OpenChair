/**
 * @file test/queue.test.js
 * The scan queue, against a real MariaDB.
 *
 * House convention from bridge-service: these self-seed, clean up after
 * themselves, and SKIP gracefully when the database is down rather than failing
 * a build on a machine that simply has no MySQL running.
 *
 * The dedupe assertions are the ones that matter. "One pass per business per
 * day, however many tenants watch it" is not a nice-to-have — it is what makes
 * $0.02/scan a fixed cost the whole customer base amortises instead of a cost
 * multiplied by every watcher.
 */

import { test, describe, before, after } from 'node:test';
import assert from 'node:assert/strict';
import { query, ping, closePool } from '../src/db.js';
import { enqueue, claim, complete, reapStaleLocks, depth } from '../src/scan/queue.js';

let live = false;
/** @type {number} */ let platformId;
/** @type {number[]} */ let bizIds = [];

const TEST_KEY = '__qtest';

before(async () => {
  live = await ping();
  if (!live) return;

  // A dedicated platform in 'shadow' — scannable, publishes nothing.
  await query(
    `INSERT INTO oc_platform (platform_key, name, vertical, enabled, rollout)
     VALUES (?, 'Queue Test', 'other', 1, 'shadow')
     ON DUPLICATE KEY UPDATE enabled = 1, rollout = 'shadow'`,
    [TEST_KEY],
  );
  const [p] = await query('SELECT id FROM oc_platform WHERE platform_key = ?', [TEST_KEY]);
  platformId = p.id;

  bizIds = [];
  for (const n of [1, 2]) {
    await query(
      `INSERT INTO oc_business (platform_id, platform_business_key, name, city_key, niche_key, status, booking_url)
       VALUES (?, ?, ?, '__qtest-city', 'barbershop', 'active', 'https://example.invalid/x')
       ON DUPLICATE KEY UPDATE name = VALUES(name), status = 'active'`,
      [platformId, `${TEST_KEY}-${n}`, `Queue Test ${n}`],
    );
    const [b] = await query(
      'SELECT id FROM oc_business WHERE platform_id = ? AND platform_business_key = ?',
      [platformId, `${TEST_KEY}-${n}`],
    );
    bizIds.push(b.id);
  }
  await query(`DELETE FROM oc_scan_queue WHERE business_id IN (${bizIds.join(',')})`);
  await query(`DELETE FROM oc_scan_run   WHERE business_id IN (${bizIds.join(',')})`);
});

after(async () => {
  if (live && bizIds.length) {
    await query(`DELETE FROM oc_scan_queue WHERE business_id IN (${bizIds.join(',')})`);
    await query(`DELETE FROM oc_scan_run   WHERE business_id IN (${bizIds.join(',')})`);
    await query(`DELETE FROM oc_business   WHERE platform_id = ?`, [platformId]);
    await query('DELETE FROM oc_platform WHERE platform_key = ?', [TEST_KEY]);
  }
  await closePool();
});

describe('shared-scan dedupe', () => {
  test('a second watcher does not create a second queue row', async (t) => {
    if (!live) return t.skip('no database');

    await enqueue(bizIds[0], platformId, { watchers: 1 });
    await enqueue(bizIds[0], platformId, { watchers: 2 });
    await enqueue(bizIds[0], platformId, { watchers: 7 });

    const rows = await query('SELECT * FROM oc_scan_queue WHERE business_id = ?', [bizIds[0]]);
    assert.equal(rows.length, 1, 'three watchers must produce ONE queue row');
    assert.equal(Number(rows[0].watcher_count), 7, 'watcher_count tracks the latest count');
  });

  test('more watchers raise priority rather than adding work', async (t) => {
    if (!live) return t.skip('no database');

    const [row] = await query('SELECT priority FROM oc_scan_queue WHERE business_id = ?', [bizIds[0]]);
    // 7 watchers → 5 - floor(7/3) = 3. Lower runs sooner.
    assert.ok(Number(row.priority) < 5, `priority should have risen, got ${row.priority}`);
  });
});

describe('claiming', () => {
  test('a claim locks the row so a second worker cannot take it', async (t) => {
    if (!live) return t.skip('no database');

    const first = await claim('worker-a', 10);
    assert.ok(first.length >= 1, 'expected due work');

    const second = await claim('worker-b', 10);
    const overlap = second.filter((s) => first.some((f) => f.id === s.id));
    assert.equal(overlap.length, 0, 'two workers must never hold the same row');
  });

  test('research-rollout platforms are never scheduled', async (t) => {
    if (!live) return t.skip('no database');

    await query('UPDATE oc_scan_queue SET status = "due", locked_by = NULL WHERE business_id = ?', [bizIds[1]]);
    await enqueue(bizIds[1], platformId, { watchers: 0 });
    await query('UPDATE oc_platform SET rollout = "research" WHERE id = ?', [platformId]);

    const work = await claim('worker-c', 10);
    assert.equal(
      work.filter((w) => w.platform_id === platformId).length, 0,
      'an unproven adapter must not be scheduled — that is what shadow is for',
    );

    await query('UPDATE oc_platform SET rollout = "shadow" WHERE id = ?', [platformId]);
  });

  test('a suppressed business is never scanned again', async (t) => {
    if (!live) return t.skip('no database');

    await query('UPDATE oc_business SET suppressed_at = NOW() WHERE id = ?', [bizIds[1]]);
    await query('UPDATE oc_scan_queue SET status = "due", due_at = NOW() WHERE business_id = ?', [bizIds[1]]);

    const work = await claim('worker-d', 10);
    assert.equal(
      work.filter((w) => w.business_id === bizIds[1]).length, 0,
      'an owner opt-out must be honoured by the scheduler, not just the UI',
    );

    await query('UPDATE oc_business SET suppressed_at = NULL WHERE id = ?', [bizIds[1]]);
  });
});

describe('one pass per business per day', () => {
  test('a retry within the day updates the run, it does not add one', async (t) => {
    if (!live) return t.skip('no database');

    const day = '2026-09-05';
    const base = {
      queueId: (await query('SELECT id FROM oc_scan_queue WHERE business_id = ?', [bizIds[0]]))[0].id,
      businessId: bizIds[0],
      platformId,
      observedOn: day,
      tier: 2,
      costMicros: 700,
    };

    await complete({ ...base, status: 'blocked', error: 'first attempt blocked' });
    await complete({ ...base, status: 'blocked', error: 'second attempt blocked' });
    await complete({ ...base, status: 'ok' });

    const runs = await query(
      'SELECT * FROM oc_scan_run WHERE business_id = ? AND observed_on = ?',
      [bizIds[0], day],
    );
    assert.equal(runs.length, 1, 'three attempts, ONE run row for the day');
    assert.equal(Number(runs[0].attempts), 3, 'attempts accumulate on the same row');
    assert.equal(runs[0].status, 'ok', 'final status wins');
    assert.equal(Number(runs[0].cost_micros), 2100, 'cost accumulates across attempts — all three were paid for');
  });

  test('the tier ceiling is enforced by the database, not by this code', async (t) => {
    if (!live) return t.skip('no database');

    const [q] = await query('SELECT id FROM oc_scan_queue WHERE business_id = ?', [bizIds[0]]);

    await assert.rejects(
      () => complete({
        queueId: q.id,
        businessId: bizIds[0], platformId, observedOn: '2026-09-06', status: 'ok', tier: 3,
      }),
      /chk_scan_tier_ceiling|CONSTRAINT/i,
      'a tier-3 scan must be unwritable',
    );
  });
});

describe('stale locks', () => {
  test('a dead worker\'s claim is returned to the pool', async (t) => {
    if (!live) return t.skip('no database');

    await query(
      `UPDATE oc_scan_queue
          SET status = 'locked', locked_by = 'ghost', locked_at = DATE_SUB(NOW(), INTERVAL 3 HOUR)
        WHERE business_id = ?`,
      [bizIds[0]],
    );
    const swept = await reapStaleLocks();
    assert.ok(swept >= 1, 'the sweep must return abandoned claims');

    const [row] = await query('SELECT status FROM oc_scan_queue WHERE business_id = ?', [bizIds[0]]);
    assert.equal(row.status, 'due', 'without this a crashed worker silently drops a business from rotation');
  });

  test('depth() reports by status', async (t) => {
    if (!live) return t.skip('no database');
    const d = await depth();
    assert.equal(typeof d, 'object');
  });
});
