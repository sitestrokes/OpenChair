/**
 * @file test/jobs.test.js
 * The scheduled jobs, the CLI, and the production fail-closed boot.
 *
 * Every job is run for real against the database: each must return a summary
 * object and be safe to run twice (index.js). The purge test seeds an expired
 * snapshot of its own and checks only that row went — it must never touch the
 * demo seed or another suite's rows.
 */

import { test, describe, before, after } from 'node:test';
import assert from 'node:assert/strict';
import { spawnSync } from 'node:child_process';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';
import { JOBS, ALL, runJob } from '../src/jobs/index.js';
import { assertProductionSecrets } from '../src/config.js';
import { query, ping, closePool } from '../src/db.js';

const ROOT = join(dirname(fileURLToPath(import.meta.url)), '..');
const KEY = '__jtest';
let live = false;
let platformId, businessId, runId;

before(async () => {
  live = await ping();
  if (!live) return;
  await query(
    `INSERT INTO oc_platform (platform_key, name, vertical, enabled, rollout) VALUES (?, 'Jobs Test', 'beauty', 1, 'shadow')
     ON DUPLICATE KEY UPDATE enabled = 1, rollout = 'shadow'`, [KEY],
  );
  [{ id: platformId }] = await query('SELECT id FROM oc_platform WHERE platform_key = ?', [KEY]);
  await query(
    `INSERT INTO oc_business (platform_id, platform_business_key, name, city_key, niche_key, status, booking_url)
     VALUES (?, ?, 'Jobs Test Shop', '__jtest-city', 'barbershop', 'active', 'https://example.invalid/j')
     ON DUPLICATE KEY UPDATE status = 'active'`, [platformId, `${KEY}-1`],
  );
  [{ id: businessId }] = await query('SELECT id FROM oc_business WHERE platform_id = ?', [platformId]);
  // Also a demo-keyed business on the same platform: it must never be queued.
  await query(
    `INSERT INTO oc_business (platform_id, platform_business_key, name, city_key, niche_key, status, booking_url)
     VALUES (?, 'demo-jtest', 'Jobs Demo Shop', '__jtest-city', 'barbershop', 'active', 'https://example.invalid/jd')
     ON DUPLICATE KEY UPDATE status = 'active'`, [platformId],
  );
  await query('DELETE FROM oc_scan_queue WHERE platform_id = ?', [platformId]);
  const r = await query(
    `INSERT INTO oc_scan_run (business_id, platform_id, observed_on, status) VALUES (?, ?, '2020-01-01', 'ok')
     ON DUPLICATE KEY UPDATE status = 'ok'`, [businessId, platformId],
  );
  [{ id: runId }] = await query('SELECT id FROM oc_scan_run WHERE business_id = ? AND observed_on = ?', [businessId, '2020-01-01']);
  await query(
    `INSERT INTO oc_scan_snapshot (scan_run_id, business_id, captured_on, purge_after)
     VALUES (?, ?, '2020-01-01', '2020-01-15') ON DUPLICATE KEY UPDATE purge_after = '2020-01-15'`,
    [runId, businessId],
  );
});

after(async () => {
  if (live) {
    await query('DELETE FROM oc_scan_queue WHERE platform_id = ?', [platformId]);
    await query('DELETE FROM oc_scan_snapshot WHERE business_id = ?', [businessId]);
    await query('DELETE FROM oc_scan_run WHERE business_id = ?', [businessId]);
    await query('DELETE FROM oc_business WHERE platform_id = ?', [platformId]);
    await query('DELETE FROM oc_platform WHERE platform_key = ?', [KEY]);
  }
  await closePool();
});

describe('every job returns a summary', () => {
  for (const name of ALL) {
    test(name, async (t) => {
      if (!live) return t.skip('no database');
      const r = await runJob(name);
      assert.equal(r.ok, true, r.error);
      assert.equal(typeof r.summary, 'object');
      const again = await runJob(name);
      assert.equal(again.ok, true, 'safe to run twice in a row');
    });
  }

  test('an unknown job is refused, not thrown', async () => {
    const r = await runJob('drop-everything');
    assert.equal(r.ok, false);
    assert.equal(r.error, 'unknown job');
  });

  test('the registry and the nightly order agree', () => {
    assert.deepEqual([...ALL].sort(), Object.keys(JOBS).sort());
  });
});

describe('what the jobs did', () => {
  test('enqueue-due queued the scannable business, not the demo row', async (t) => {
    if (!live) return t.skip('no database');
    const rows = await query(
      `SELECT b.platform_business_key FROM oc_scan_queue q JOIN oc_business b ON b.id = q.business_id
        WHERE q.platform_id = ?`, [platformId],
    );
    assert.deepEqual(rows.map((r) => r.platform_business_key), [`${KEY}-1`]);
  });

  test('purge removed the expired snapshot', async (t) => {
    if (!live) return t.skip('no database');
    const rows = await query('SELECT id FROM oc_scan_snapshot WHERE scan_run_id = ?', [runId]);
    assert.equal(rows.length, 0);
  });
});

describe('the CLI', () => {
  test('usage error exits 2', () => {
    const r = spawnSync(process.execPath, ['src/jobs/run.js', 'nope'], { cwd: ROOT, encoding: 'utf8' });
    assert.equal(r.status, 2);
    assert.match(r.stderr, /usage/);
  });

  test('a real job exits 0', (t) => {
    if (!live) return t.skip('no database');
    const r = spawnSync(process.execPath, ['src/jobs/run.js', 'reap'], {
      cwd: ROOT, encoding: 'utf8', env: { ...process.env },
    });
    assert.equal(r.status, 0, r.stderr);
    assert.match(r.stdout, /\[jobs\] reap ok/);
  });
});

describe('production refuses to start without tokens', () => {
  const boot = (env) => spawnSync(process.execPath, ['-e', "import('./src/server.js')"], {
    cwd: ROOT, encoding: 'utf8', env: { ...process.env, OC_PORT: '0', ...env }, timeout: 15000,
  });

  test('NODE_ENV=production with no tokens exits non-zero', () => {
    const r = boot({ NODE_ENV: 'production', OC_INTERNAL_TOKEN: '', OC_WORKER_CALLBACK_TOKEN: '' });
    assert.notEqual(r.status, 0);
    assert.match(r.stderr, /refuses to start/);
  });

  test('OC_REQUIRE_TOKENS=1 does the same outside production', () => {
    const r = boot({ OC_REQUIRE_TOKENS: '1', OC_INTERNAL_TOKEN: '', OC_WORKER_CALLBACK_TOKEN: '' });
    assert.notEqual(r.status, 0);
  });

  test('a short token is refused too', () => {
    assert.throws(() => assertProductionSecrets({
      env: 'production', requireTokens: false, security: { internalToken: 'short', workerToken: 'x'.repeat(32) },
    }), /shorter than 24/);
  });

  test('long tokens pass', () => {
    assert.doesNotThrow(() => assertProductionSecrets({
      env: 'production', requireTokens: false, security: { internalToken: 'a'.repeat(32), workerToken: 'b'.repeat(32) },
    }));
  });

  test('development keeps the open dev gate', () => {
    assert.doesNotThrow(() => assertProductionSecrets({
      env: 'development', requireTokens: false, security: { internalToken: '', workerToken: '' },
    }));
  });
});
