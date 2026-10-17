/**
 * @file test/worker.test.js
 * The loop end to end, against a fake openchair-service.
 *
 * node:http appears here and ONLY here — it is the fake service. The worker
 * itself reaches the service with fetch and reaches pages only through its
 * reader. The payload assertions pin docs/70 §3.2, which agent S builds the
 * service side to; if either side drifts, this test is where it shows.
 */

import { test, describe, before, after } from 'node:test';
import assert from 'node:assert/strict';
import { createServer } from 'node:http';
import { spawn } from 'node:child_process';
import { readFileSync } from 'node:fs';
import { join, dirname } from 'node:path';
import { fileURLToPath } from 'node:url';

import { runOnce, processItem, PAYLOAD_LIMIT } from '../src/worker.js';
import { loadAdapters } from '../src/registry/index.js';
import { createFixtureReader } from '../src/lib/fixtureReader.js';
import { createServiceClient } from '../src/lib/serviceClient.js';

const HERE = dirname(fileURLToPath(import.meta.url));
const FIX = join(HERE, '..', 'fixtures');
const catalog = JSON.parse(readFileSync(join(FIX, 'catalog.json'), 'utf8'));
const TZ = catalog.timezone;

const item = (id, platform, kase, over = {}) => ({
  id, business_id: 1000 + id, platform_id: platform === 'booksy' ? 1 : 5,
  booking_url: catalog.urls[platform]?.[kase], timezone: TZ,
  platform_key: platform, rollout: 'shadow', requires_headless: 1, ...over,
});

const WORK = [
  item(1, 'booksy', 'hungry'),
  item(2, 'square_appointments', 'slammed'),
  item(3, 'booksy', 'blocked'),
  item(4, 'booksy', 'stale'),
  item(5, 'opentable', 'hungry', { booking_url: 'https://www.opentable.com/r/x' }),
  item(6, 'booksy', 'partial'),
];

/** A fake service: hands out WORK once, records completions. */
function fakeService(token) {
  const completions = [];
  const seenTokens = [];
  let handedOut = false;
  const server = createServer((req, res) => {
    const chunks = [];
    req.on('data', (c) => chunks.push(c));
    req.on('end', () => {
      seenTokens.push(req.headers['x-worker-token']);
      const body = chunks.length ? JSON.parse(Buffer.concat(chunks).toString('utf8')) : {};
      res.setHeader('content-type', 'application/json');
      if (token && req.headers['x-worker-token'] !== token) { res.statusCode = 401; return res.end('{"ok":false,"error":"unauthorised worker"}'); }
      if (req.url === '/v1/scan/claim') {
        const work = handedOut ? [] : WORK;
        handedOut = true;
        return res.end(JSON.stringify({ ok: true, claimed: work.length, work }));
      }
      if (req.url === '/v1/scan/complete') {
        assert.ok(Buffer.byteLength(JSON.stringify(body)) < 256 * 1024, 'payload under the service cap');
        completions.push(body);
        return res.end('{"ok":true}');
      }
      res.statusCode = 404; res.end('{"ok":false}');
    });
  });
  return { server, completions, seenTokens };
}

const listen = (server) => new Promise((r) => server.listen(0, '127.0.0.1', () => r(server.address().port)));

describe('worker — one claim cycle against a fake service', () => {
  const fake = fakeService('sekrit');
  let result;
  before(async () => {
    const port = await listen(fake.server);
    result = await runOnce({
      client: createServiceClient({ url: `http://127.0.0.1:${port}`, token: 'sekrit' }),
      adapters: await loadAdapters(),
      reader: createFixtureReader({ dir: FIX }),
      workerId: 'test-worker', batchSize: 10, concurrency: 3, horizonDays: 14,
    });
  });
  after(() => fake.server.close());

  test('every claimed item is reported — failures included', () => {
    assert.deepEqual(result, { claimed: WORK.length, reported: WORK.length, failed: 0 });
    assert.deepEqual(fake.completions.map((c) => c.queueId).sort(), WORK.map((w) => w.id).sort());
  });

  test('the worker token rides on every call', () => {
    assert.ok(fake.seenTokens.every((t) => t === 'sekrit'));
  });

  const TOP = ['queueId', 'businessId', 'platformId', 'observedOn', 'status', 'adapterVersion', 'tier',
    'bytes', 'costMicros', 'durationMs', 'errorCode', 'error', 'capability', 'horizonDays', 'slotMinutes'];
  const DATA = ['grid', 'snapshot', 'staff', 'services'];

  test('an ok read matches §3.2 exactly', () => {
    const p = fake.completions.find((c) => c.queueId === 1);
    assert.deepEqual(Object.keys(p).sort(), [...TOP, ...DATA].sort());
    assert.equal(p.status, 'ok');
    assert.equal(p.businessId, 1001);
    assert.match(p.observedOn, /^\d{4}-\d{2}-\d{2}$/);
    assert.equal(p.capability, 'C1_SLOT_GRID');
    assert.deepEqual(Object.keys(p.grid).sort(),
      ['byDay', 'dayCount', 'daysToNext', 'envelopeMinutes', 'openMinutes', 'staffCount', 'vacancyBp']);
    assert.equal(p.snapshot.v, 1);
    assert.ok(p.snapshot.days.length > 0);
    for (const d of p.snapshot.days) {
      for (const u of d.units) {
        assert.deepEqual(Object.keys(u).sort(), ['envelope', 'open', 'staffHash']);
      }
    }
    assert.ok(p.staff.every((s) => /^[0-9a-f]{40}$/.test(s.staffHash) && typeof s.staffKey === 'string'));
    assert.ok(p.services.every((s) => Object.keys(s).sort().join() === 'durationMin,key,name,priceCents'));
    assert.ok(p.bytes > 0, 'bytes read are metered');
    assert.equal(p.errorCode, null);
  });

  test('a failed read carries no data blocks', () => {
    for (const [id, code] of [[3, 'HTTP_403'], [4, 'NO_GRID_PARSED'], [5, 'UNKNOWN_PLATFORM']]) {
      const p = fake.completions.find((c) => c.queueId === id);
      assert.deepEqual(Object.keys(p).sort(), [...TOP].sort(), `item ${id}`);
      assert.equal(p.errorCode, code, `item ${id}`);
    }
  });

  test('the Slammed Square shop arrives with its lead time', () => {
    const p = fake.completions.find((c) => c.queueId === 2);
    assert.ok(p.grid.daysToNext >= 9);
  });

  test('no staff name crosses the wire', () => {
    const text = JSON.stringify(fake.completions);
    for (const n of catalog.staffNames) assert.ok(!text.includes(n), n);
  });
});

describe('worker — refusals', () => {
  test('broker mode never runs the adapter for a non-live platform', async () => {
    let calls = 0;
    const p = await processItem(item(9, 'booksy', 'hungry', { rollout: 'shadow' }), {
      adapters: await loadAdapters(),
      reader: { mode: 'broker', read: async () => { calls++; return { status: 200, body: '' }; } },
    });
    assert.equal(p.errorCode, 'ROLLOUT_NOT_LIVE');
    assert.equal(calls, 0, 'no read was attempted');
  });

  test('an oversized payload is reported as a failure, not truncated', async () => {
    const huge = {
      meta: { platformKey: 'booksy', capability: 'C1_SLOT_GRID', version: '1', tier: 1 },
      read: async () => ({
        status: 'ok', capability: 'C1_SLOT_GRID', adapterVersion: '1', slotMinutes: 5,
        units: Array.from({ length: 2000 }, (_, i) => ({
          staffHash: `h${i}`, day: '2026-10-20', openMinutes: 600, envelopeMinutes: 600,
          envelopeStart: 540, envelopeEnd: 1140, openStarts: Array.from({ length: 120 }, (_, k) => 540 + k * 5),
        })),
      }),
    };
    const p = await processItem(item(10, 'booksy', 'hungry'), {
      adapters: new Map([['booksy', huge]]),
      reader: createFixtureReader({ dir: FIX }),
    });
    assert.equal(p.errorCode, 'PAYLOAD_TOO_LARGE');
    assert.equal(p.grid, undefined);
    assert.ok(Buffer.byteLength(JSON.stringify(p)) < PAYLOAD_LIMIT);
  });

  test('an adapter that throws is reported, not fatal', async () => {
    const boom = { meta: { platformKey: 'booksy', capability: 'C1_SLOT_GRID', version: '1' }, read: async () => { throw new Error('kaput'); } };
    const p = await processItem(item(11, 'booksy', 'hungry'), { adapters: new Map([['booksy', boom]]), reader: createFixtureReader({ dir: FIX }) });
    assert.equal(p.status, 'error');
    assert.equal(p.errorCode, 'WORKER_THREW');
  });

  test('validator refusal becomes an error report with the validator code', async () => {
    const bad = { meta: { platformKey: 'booksy', capability: 'C1_SLOT_GRID', version: '1' },
      read: async () => ({ status: 'ok', capability: 'C1_SLOT_GRID', adapterVersion: '1', units: [] }) };
    const p = await processItem(item(12, 'booksy', 'hungry'), { adapters: new Map([['booksy', bad]]), reader: createFixtureReader({ dir: FIX }) });
    assert.equal(p.errorCode, 'NO_UNITS');
  });
});

describe('worker CLI', () => {
  const run = (env) => new Promise((resolve) => {
    const child = spawn(process.execPath, [join(HERE, '..', 'src', 'worker.js'), '--once'], {
      env: { ...process.env, ...env }, stdio: ['ignore', 'pipe', 'pipe'],
    });
    let out = '';
    child.stdout.on('data', (d) => { out += d; });
    child.stderr.on('data', (d) => { out += d; });
    child.on('close', (code) => resolve({ code, out }));
  });

  test('--once runs one cycle against the service and exits 0', async () => {
    const fake = fakeService('');
    const port = await listen(fake.server);
    const { code, out } = await run({ OC_SERVICE_URL: `http://127.0.0.1:${port}`, OC_READ_MODE: 'fixture', OC_WORKER_CALLBACK_TOKEN: '', NODE_ENV: 'test' });
    fake.server.close();
    assert.equal(code, 0, out);
    assert.equal(fake.completions.length, WORK.length);
    assert.match(out, /read mode fixture/);
  });

  test('refuses to start in broker mode without OC_ALLOW_LIVE', async () => {
    const { code, out } = await run({ OC_READ_MODE: 'broker', OC_ALLOW_LIVE: '0', NODE_ENV: 'test' });
    assert.equal(code, 2);
    assert.match(out, /OC_ALLOW_LIVE=1/);
  });
});
