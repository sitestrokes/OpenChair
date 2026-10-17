/**
 * @file test/readers.test.js
 * The safety switches (docs/70 §4) and the fixture reader.
 *
 * "No live crawl until the spike says go" is a founder decision. These tests
 * are what make it a property of the code rather than of an env file.
 */

import { test, describe } from 'node:test';
import assert from 'node:assert/strict';
import { mkdtempSync, writeFileSync } from 'node:fs';
import { tmpdir } from 'node:os';
import { join } from 'node:path';

import { selectReader, readerForItem } from '../src/lib/readers.js';
import { createFixtureReader, fillDates } from '../src/lib/fixtureReader.js';
import { assertSafeStartup } from '../src/worker.js';

describe('read mode switches', () => {
  test('fixture mode is the default and needs no permission', () => {
    assert.equal(selectReader({ mode: 'fixture', allowLive: false, fixturesDir: '.' }).mode, 'fixture');
    assert.equal(selectReader({ fixturesDir: '.' }).mode, 'fixture');
  });

  test('broker mode REFUSES to start without OC_ALLOW_LIVE=1', () => {
    assert.throws(() => selectReader({ mode: 'broker', allowLive: false }), { code: 'LIVE_NOT_ALLOWED' });
  });

  test('broker mode with the switch on is allowed to exist…', () => {
    assert.equal(selectReader({ mode: 'broker', allowLive: true }).mode, 'broker');
  });

  test('…but refuses every item whose platform is not live', () => {
    const broker = { mode: 'broker', read: async () => { throw new Error('must not be called'); } };
    for (const rollout of ['shadow', 'research', 'frozen', undefined]) {
      assert.equal(readerForItem(broker, { rollout }), null, String(rollout));
    }
    assert.equal(typeof readerForItem(broker, { rollout: 'live' }), 'function');
  });

  test('fixture mode reads shadow platforms (that is what fixtures are for)', () => {
    assert.equal(typeof readerForItem({ mode: 'fixture', read: async () => ({}) }, { rollout: 'shadow' }), 'function');
  });

  test('an unknown mode is refused, not defaulted', () => {
    assert.throws(() => selectReader({ mode: 'live' }), { code: 'BAD_READ_MODE' });
  });

  test('production refuses to boot without a callback token', () => {
    assert.throws(
      () => assertSafeStartup({ env: 'production', service: { token: '' }, read: { mode: 'fixture', fixturesDir: '.' } }),
      { code: 'NO_WORKER_TOKEN' },
    );
    assert.equal(
      assertSafeStartup({ env: 'production', service: { token: 't' }, read: { mode: 'fixture', fixturesDir: '.' } }).mode,
      'fixture',
    );
  });
});

describe('fixture reader', () => {
  const dir = mkdtempSync(join(tmpdir(), 'ocfix-'));
  writeFileSync(join(dir, 'index.json'), JSON.stringify({
    _comment: 'x',
    'https://example.test/a?d={{D0}}': { file: 'a.json', status: 200 },
    'https://example.test/escape': { file: '../../etc/hostname', status: 200 },
    'https://example.test/blocked': { status: 403 },
  }));
  writeFileSync(join(dir, 'a.json'), '{"day":"{{D1}}"}');
  const now = () => new Date('2026-10-17T03:00:00Z');   // still Oct 16 in New York
  const r = createFixtureReader({ dir, now });

  test('fills dates in the BUSINESS timezone, in keys and bodies', async () => {
    const out = await r.read('https://example.test/a?d=2026-10-16', { timezone: 'America/New_York' });
    assert.equal(out.status, 200);
    assert.equal(out.body, '{"day":"2026-10-17"}');
    assert.equal(out.bytes, out.body.length);
  });

  test('an unknown URL is a 404 FIXTURE_MISSING, never an empty 200', async () => {
    const out = await r.read('https://example.test/nope', { timezone: 'UTC' });
    assert.equal(out.status, 404);
    assert.equal(out.code, 'FIXTURE_MISSING');
  });

  test('a status-only entry serves that status', async () => {
    assert.equal((await r.read('https://example.test/blocked')).status, 403);
  });

  test('index.json cannot point outside the fixtures directory', async () => {
    await assert.rejects(r.read('https://example.test/escape'), { code: 'FIXTURE_PATH' });
  });

  test('fillDates ignores offsets beyond the cap', () => {
    assert.equal(fillDates('{{D0}} {{D99}}', '2026-01-31'), '2026-01-31 {{D99}}');
  });
});
