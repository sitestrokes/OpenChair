/**
 * @file test/removal.test.js
 * The business opt-out path.
 *
 * This is a compliance obligation with a published SLA, so the tests are about
 * the three ways the promise gets broken quietly rather than loudly: the opt-out
 * expiring on the next discovery sweep, the observations lingering after the
 * parent is suppressed, and the SLA existing only on a policy page.
 */

import { test, describe, before, after } from 'node:test';
import assert from 'node:assert/strict';
import { query, ping, closePool } from '../src/db.js';
import { intake, action, isDoNotScan, queue, SLA_DAYS } from '../src/observability/removal.js';

let live = false;
let platformId = 0;
let bizId = 0;
const KEY = '__removaltest';

before(async () => {
  live = await ping();
  if (!live) return;

  await query(
    `INSERT INTO oc_platform (platform_key, name, vertical, enabled, rollout)
     VALUES (?, 'Removal Test', 'other', 1, 'live')
     ON DUPLICATE KEY UPDATE enabled = 1`, [KEY],
  );
  [{ id: platformId }] = await query('SELECT id FROM oc_platform WHERE platform_key = ?', [KEY]);

  await query(
    `INSERT INTO oc_business
       (platform_id, platform_business_key, name, booking_url, city_key, niche_key, status,
        latest_vacancy_bp, latest_open_slots, latest_bucket, latest_confidence)
     VALUES (?, ?, 'Removal Test Shop', 'https://example.invalid/removal-1',
             '__rt-city', 'barbershop', 'active', 5000, 100, 'hungry', 80)
     ON DUPLICATE KEY UPDATE status = 'active', suppressed_at = NULL,
       latest_vacancy_bp = 5000, latest_bucket = 'hungry'`,
    [platformId, `${KEY}-1`],
  );
  [{ id: bizId }] = await query(
    'SELECT id FROM oc_business WHERE platform_id = ? AND platform_business_key = ?',
    [platformId, `${KEY}-1`],
  );

  // Give it observations to purge, and a watcher.
  await query(
    `INSERT INTO oc_vacancy_daily
       (business_id, observed_on, horizon_days, open_slots, bookable_slots, vacancy_bp,
        empty_chair_cents, days_to_next, bucket, confidence)
     VALUES (?, '2026-09-01', 7, 100, 200, 5000, 5000, 0, 'hungry', 80)
     ON DUPLICATE KEY UPDATE open_slots = 100`, [bizId],
  );
  await query(
    `INSERT INTO oc_vacancy_weekly (business_id, week_start, scan_days, avg_vacancy_bp, fill_rate_bp)
     VALUES (?, '2026-08-31', 7, 5000, 5000)
     ON DUPLICATE KEY UPDATE scan_days = 7`, [bizId],
  );
  await query(
    `INSERT INTO oc_deadzone_cell
       (business_id, dow, block_start_hour, block_hours, open_slots_sum, total_slots_sum,
        obs_count, open_ratio_bp, ewma_bp, city_key, niche_key, window_start, window_end)
     VALUES (?, 1, 13, 2, 100, 120, 3, 8333, 8333, '__rt-city', 'barbershop', CURDATE(), CURDATE())
     ON DUPLICATE KEY UPDATE obs_count = 3`, [bizId],
  );
  await query(
    `INSERT INTO oc_scan_queue (business_id, platform_id, priority, watcher_count, due_at, status)
     VALUES (?, ?, 5, 1, NOW(), 'due')
     ON DUPLICATE KEY UPDATE watcher_count = 1`, [bizId, platformId],
  );
});

after(async () => {
  if (live && bizId) {
    await query('DELETE FROM oc_removal_request WHERE business_id = ? OR booking_url LIKE ?', [bizId, '%removal-%']);
    await query('DELETE FROM oc_do_not_scan WHERE platform_key = ?', [KEY]);
    await query('DELETE FROM oc_vacancy_daily WHERE business_id = ?', [bizId]);
    await query('DELETE FROM oc_vacancy_weekly WHERE business_id = ?', [bizId]);
    await query('DELETE FROM oc_deadzone_cell WHERE business_id = ?', [bizId]);
    await query('DELETE FROM oc_scan_queue WHERE business_id = ?', [bizId]);
    await query('DELETE FROM oc_business WHERE platform_id = ?', [platformId]);
    await query('DELETE FROM oc_platform WHERE platform_key = ?', [KEY]);
  }
  await closePool();
});

describe('intake', () => {
  test('the SLA is written as a date, not promised on a page', async (t) => {
    if (!live) return t.skip('no database');

    const id = await intake({
      businessId: bizId, bookingUrl: 'https://example.invalid/removal-1',
      businessName: 'Removal Test Shop', requesterName: 'A Owner',
      requesterEmail: 'owner@example.invalid', channel: 'form',
      message: 'Please stop reading our booking page.',
    });

    const [r] = await query('SELECT * FROM oc_removal_request WHERE id = ?', [id]);
    assert.equal(r.status, 'new');
    assert.ok(r.due_by, 'due_by must be set on intake');

    const days = (new Date(r.due_by) - new Date(r.requested_at)) / 86400000;
    assert.ok(Math.abs(days - SLA_DAYS) < 0.1, `SLA should be ${SLA_DAYS} days, got ${days}`);
  });

  test('an unverified request is still recorded and still counts', async (t) => {
    if (!live) return t.skip('no database');
    // Refusing to act without notarised proof makes the promise hollow; acting
    // blindly lets a competitor delist a rival. So verification is RECORDED and
    // the clock runs regardless.
    const id = await intake({ bookingUrl: 'https://example.invalid/removal-2', channel: 'email' });
    const [r] = await query('SELECT verify_method, status FROM oc_removal_request WHERE id = ?', [id]);
    assert.equal(r.verify_method, 'none');
    assert.equal(r.status, 'new');
  });

  test('the queue shows what is overdue', async (t) => {
    if (!live) return t.skip('no database');
    const q = await queue();
    assert.ok(Array.isArray(q.open));
    assert.equal(typeof q.overdue, 'number');
  });
});

describe('acting on a request', () => {
  test('suppresses, purges the observations, and never scans again', async (t) => {
    if (!live) return t.skip('no database');

    const id = await intake({
      businessId: bizId, bookingUrl: 'https://example.invalid/removal-1', channel: 'form',
    });
    const res = await action(id, 1);
    assert.equal(res.ok, true, res.error);

    // 1. Tombstone, not delete — so discovery recognises the shop and skips it
    //    rather than re-adding it as new.
    const [b] = await query('SELECT status, suppressed_at, latest_vacancy_bp, latest_bucket FROM oc_business WHERE id = ?', [bizId]);
    assert.ok(b, 'the row must SURVIVE as a tombstone');
    assert.equal(b.status, 'suppressed');
    assert.ok(b.suppressed_at);
    assert.equal(b.latest_vacancy_bp, null, 'the projection must be cleared, not just hidden');
    assert.equal(b.latest_bucket, null);

    // 2. The observations are gone, not merely orphaned — otherwise the numbers
    //    stay queryable and can still reach a tenant through a cached view.
    for (const table of ['oc_vacancy_daily', 'oc_vacancy_weekly', 'oc_deadzone_cell', 'oc_scan_queue']) {
      const rows = await query(`SELECT COUNT(*) AS n FROM ${table} WHERE business_id = ?`, [bizId]);
      assert.equal(Number(rows[0].n), 0, `${table} should be purged`);
    }

    // 3. The listing is permanently off limits, keyed by PLATFORM rather than by
    //    our internal id — so it still matches after a re-discovery.
    assert.equal(await isDoNotScan(KEY, `${KEY}-1`), true);

    const [r] = await query('SELECT status, actioned_at FROM oc_removal_request WHERE id = ?', [id]);
    assert.equal(r.status, 'actioned');
    assert.ok(r.actioned_at);
  });

  test('acting twice is a no-op, not a second purge', async (t) => {
    if (!live) return t.skip('no database');
    const id = await intake({ businessId: bizId, channel: 'form' });
    await action(id, 1);
    const again = await action(id, 1);
    assert.equal(again.ok, true);
  });

  test('an unmatchable request is rejected with a reason, not silently dropped', async (t) => {
    if (!live) return t.skip('no database');
    const id = await intake({ bookingUrl: 'https://example.invalid/not-a-business-we-scan' });
    const res = await action(id, 1);
    assert.equal(res.ok, false);

    const [r] = await query('SELECT status, outcome_note FROM oc_removal_request WHERE id = ?', [id]);
    assert.equal(r.status, 'rejected');
    assert.ok(r.outcome_note, 'a rejection must say why — the requester is owed an answer');
  });
});

describe('the opt-out does not expire', () => {
  test('a suppressed business is invisible to the market query', async (t) => {
    if (!live) return t.skip('no database');
    const rows = await query(
      `SELECT id FROM oc_business
        WHERE city_key = '__rt-city' AND status = 'active' AND suppressed_at IS NULL`,
    );
    assert.equal(rows.length, 0);
  });

  test('do-not-scan is keyed by the platform listing, not our internal id', async (t) => {
    if (!live) return t.skip('no database');
    // The failure this prevents: discovery meets the same shop next month, our
    // id has changed, and an opt-out the owner believes is permanent quietly
    // expires.
    const [row] = await query(
      'SELECT platform_key, platform_business_key FROM oc_do_not_scan WHERE platform_key = ?',
      [KEY],
    );
    assert.ok(row);
    assert.equal(row.platform_key, KEY);
    assert.equal(row.platform_business_key, `${KEY}-1`);

    // Stronger than checking the value: the table has no business_id COLUMN, so
    // matching cannot come to depend on an internal id even by accident.
    const cols = await query(
      `SELECT COLUMN_NAME FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'oc_do_not_scan'`,
    );
    const names = cols.map((c) => c.COLUMN_NAME);
    assert.ok(!names.includes('business_id'),
      'do-not-scan must key on the platform listing alone; a business_id column would invite id-based matching');
  });
});
