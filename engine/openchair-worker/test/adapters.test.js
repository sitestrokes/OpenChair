/**
 * @file test/adapters.test.js
 * Booksy and Square Appointments against every fixture case.
 *
 * The fixtures are SYNTHETIC (fixtures/README.md); these tests pin the parsing
 * logic and, more importantly, the refusals — the cases where a page looks
 * almost right and the adapter must still say "I could not read this" instead
 * of handing the Meter a confident number.
 */

import { test, describe } from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync, readdirSync } from 'node:fs';
import { join, dirname } from 'node:path';
import { fileURLToPath } from 'node:url';

import { createFixtureReader } from '../src/lib/fixtureReader.js';
import { loadAdapters } from '../src/registry/index.js';
import { validateResult, CAPABILITY } from '../src/registry/contract.js';
import { normalise } from '../src/normalise/chairMinutes.js';
import { businessId } from '../src/adapters/booksy.js';
import { bookingIds } from '../src/adapters/square_appointments.js';

const HERE = dirname(fileURLToPath(import.meta.url));
const FIX = join(HERE, '..', 'fixtures');
const ADAPTERS = join(HERE, '..', 'src', 'adapters');
const catalog = JSON.parse(readFileSync(join(FIX, 'catalog.json'), 'utf8'));
const reader = createFixtureReader({ dir: FIX });
const adapters = await loadAdapters();

async function run(platform, kase) {
  const tz = catalog.timezone;
  const out = await adapters.get(platform).read({
    bookingUrl: catalog.urls[platform][kase],
    timezone: tz,
    horizonDays: catalog.horizonDays,
    read: (u, o) => reader.read(u, { ...o, timezone: tz }),
  });
  return out;
}

const EXPECT = {
  hungry: 'ok', slammed: 'ok', steady: 'ok', partial: 'partial',
  blocked: 'blocked', notFound: 'not_found', captcha: 'captcha',
  stale: 'error', leak: 'error',
};

for (const platform of Object.keys(catalog.urls)) {
  describe(`${platform} — every fixture lands on the right status`, () => {
    for (const kase of Object.keys(catalog.urls[platform])) {
      test(`${kase} → ${EXPECT[kase]}`, async () => {
        const out = await run(platform, kase);
        assert.equal(out.status, EXPECT[kase], `${kase}: ${out.errorCode ?? ''} ${out.errorNote ?? ''}`);
        const v = validateResult(out, { expectCapability: CAPABILITY.SLOT_GRID });
        assert.equal(v.ok, true, `validator refused ${kase}: ${v.code} ${v.reason}`);
      });
    }

    test('stale selectors are a parse failure, never an empty "full" shop', async () => {
      const out = await run(platform, 'stale');
      assert.equal(out.errorCode, 'NO_GRID_PARSED');
      assert.equal(out.units, undefined);
    });

    test('an id that is a person refuses the read', async () => {
      const out = await run(platform, 'leak');
      assert.equal(out.errorCode, 'UNSAFE_STAFF_ID');
      assert.doesNotMatch(JSON.stringify(out), /Marcus Bell|Jasmine Ortiz/);
    });

    test('NO staff name anywhere in any output', async () => {
      for (const kase of Object.keys(catalog.urls[platform])) {
        const text = JSON.stringify(await run(platform, kase));
        for (const name of catalog.staffNames) {
          assert.ok(!text.includes(name), `${kase} leaked '${name}'`);
        }
      }
    });

    test('the three shop shapes score as Hungry > Steady > Slammed vacancy', async () => {
      const v = {};
      for (const k of ['hungry', 'steady', 'slammed']) v[k] = normalise(await run(platform, k)).vacancyBp;
      assert.ok(v.hungry > 5000, `hungry vacancy ${v.hungry}`);
      assert.ok(v.steady > 1500 && v.steady < 5000, `steady vacancy ${v.steady}`);
      assert.ok(v.slammed < 1000, `slammed vacancy ${v.slammed}`);
    });

    test('the Slammed shop reports a long lead time', async () => {
      assert.ok(normalise(await run(platform, 'slammed')).daysToNext >= 9);
    });

    test('units carry an hour-level grid inside their envelope, and hashes not ids', async () => {
      const out = await run(platform, 'hungry');
      for (const u of out.units) {
        assert.match(u.staffHash, /^[0-9a-f]{40}$/);
        assert.ok(u.openStarts.every((t) => t >= u.envelopeStart && t < u.envelopeEnd));
        assert.ok(u.openMinutes <= u.envelopeMinutes);
      }
      assert.ok(out.services.length > 0 && out.services.every((s) => s.name && Number.isInteger(s.priceCents)));
    });
  });
}

describe('booking URL parsing', () => {
  test('Booksy: business id from the last path segment, Booksy hosts only', () => {
    assert.equal(businessId('https://booksy.com/en-us/100001_fade-lab_barber-shop_134763_tampa'), '100001');
    assert.equal(businessId('https://evil.example/en-us/100001_x'), null);
    assert.equal(businessId('not a url'), null);
  });
  test('Square: merchant and location, book.squareup.com only', () => {
    assert.deepEqual(bookingIds('https://book.squareup.com/appointments/abc/location/L1/services'), { merchant: 'abc', location: 'L1' });
    assert.equal(bookingIds('https://squareup.com.evil.example/appointments/abc/location/L1'), null);
  });
});

describe('adapters have no network of their own', () => {
  // Layer 1 of the read-only guarantee: the only route out is ctx.read.
  const FORBIDDEN = /from\s+['"]node:(https?|net|tls|dgram|http2)['"]|require\(\s*['"](node:)?(https?|net|tls|dgram|http2)['"]|\bfetch\s*\(|XMLHttpRequest|WebSocket/;
  for (const f of readdirSync(ADAPTERS).filter((x) => x.endsWith('.js'))) {
    test(`${f} imports no network module and calls no fetch`, () => {
      assert.doesNotMatch(readFileSync(join(ADAPTERS, f), 'utf8'), FORBIDDEN);
    });
  }
});
