/**
 * @file test/discovery.test.js
 * URL detection (pure) and the discovery pass (real DB).
 *
 * The detection table is the security surface: every string here could have
 * been pasted into New scan by anyone with an account. None of them is fetched,
 * but a bad one must also never become a booking_url the worker later reads.
 */

import { test, describe, before, after } from 'node:test';
import assert from 'node:assert/strict';
import { platformFromUrl, safeUrl, nameFromSlug } from '../src/discovery/detect.js';
import { runDiscovery, parseCityKey } from '../src/discovery/run.js';
import { query, ping, closePool } from '../src/db.js';

describe('platformFromUrl — accepted', () => {
  const good = [
    ['https://booksy.com/en-us/123456_fade-lab_barber-shop_134775_tampa', 'booksy', '123456', 'Fade Lab'],
    ['https://booksy.com/en-us/98765_bayshore-cuts_barber-shop_1_tampa?utm=x#reviews', 'booksy', '98765', 'Bayshore Cuts'],
    ['https://us.booksy.com/en-us/4444_x_y', 'booksy', '4444', 'X'],
    ['https://book.squareup.com/appointments/abc123/location/LXYZ9/services', 'square_appointments', 'abc123:LXYZ9', ''],
    ['https://squareup.com/appointments/book/tok_123abc/LOC/start', 'square_appointments', 'tok_123abc', ''],
    ['https://www.vagaro.com/thebarberlounge', 'vagaro', 'thebarberlounge', 'Thebarberlounge'],
    ['https://shopname.glossgenius.com/services', 'glossgenius', 'shopname', 'Shopname'],
    ['https://studio-one.as.me/', 'acuity', 'studio-one', 'Studio One'],
  ];
  for (const [url, key, id, name] of good) {
    test(`${key}: ${url.slice(8, 60)}`, () => {
      const d = platformFromUrl(url);
      assert.equal(d.ok, true, JSON.stringify(d));
      assert.equal(d.platformKey, key);
      assert.equal(d.platformBusinessKey, id);
      assert.equal(d.slugName, name);
      assert.ok(d.canonicalUrl.startsWith('https://'));
      assert.doesNotMatch(d.canonicalUrl, /[?#]/, 'query and fragment are stripped');
    });
  }
});

describe('platformFromUrl — refused', () => {
  const bad = [
    ['http://booksy.com/en-us/123456_fade-lab', 'NOT_HTTPS'],
    ['javascript:alert(1)', 'NOT_HTTPS'],
    ['data:text/html,<script>', 'NOT_HTTPS'],
    ['ftp://booksy.com/123_x', 'NOT_HTTPS'],
    ['https://user:pass@booksy.com/en-us/123456_x', 'CREDENTIALS'],
    ['https://127.0.0.1/en-us/123456_x', 'IP_HOST'],
    ['https://[::1]/x', 'IP_HOST'],
    ['https://localhost/en-us/123456_x', 'LOCAL_HOST'],
    ['https://booksy.com:8443/en-us/123456_x', 'PORT'],
    ['https://evilbooksy.com/en-us/123456_x', 'UNKNOWN_PLATFORM'],
    ['https://booksy.com.evil.example/en-us/123456_x', 'UNKNOWN_PLATFORM'],
    ['https://booksy.com/en-us/', 'BOOKSY_NO_ID'],
    ['https://book.squareup.com/appointments/', 'SQUARE_NO_ID'],
    ['https://example.com/book', 'UNKNOWN_PLATFORM'],
    [`https://booksy.com/${'a'.repeat(600)}`, 'TOO_LONG'],
    ['https://booksy.com/en-us/1234_x\nHost: evil', 'BAD_CHARS'],
    ['', 'EMPTY'],
    [null, 'EMPTY'],
    ['not a url', 'BAD_CHARS'],
  ];
  for (const [url, reason] of bad) {
    test(`${reason}: ${String(url).slice(0, 50)}`, () => {
      const d = platformFromUrl(url);
      assert.equal(d.ok, false);
      assert.equal(d.reason, reason);
    });
  }

  test('safeUrl strips query and fragment', () => {
    const r = safeUrl('https://booksy.com/a?b=1#c');
    assert.equal(r.ok, true);
    assert.equal(r.url.toString(), 'https://booksy.com/a');
  });

  test('nameFromSlug drops anything that is not a plain word', () => {
    assert.equal(nameFromSlug('fade-lab_barber'), 'Fade Lab');
    assert.equal(nameFromSlug('<script>-x'), 'X');
  });

  test('parseCityKey', () => {
    assert.deepEqual(parseCityKey('us-fl-tampa'), { country: 'US', region: 'FL', city: 'Tampa', timezone: 'America/New_York' });
    assert.equal(parseCityKey('us-tx-san_antonio').city, 'San Antonio');
    assert.equal(parseCityKey('us-ca-los-angeles').timezone, 'America/Los_Angeles');
    assert.equal(parseCityKey('zz').timezone, 'America/New_York', 'unknown falls back to the launch market, never UTC');
  });
});

describe('the discovery pass', () => {
  let live = false;
  const CITY = '__dtest-city';
  const REFS = ['__dtest-1', '__dtest-2', '__dtest-3', '__dtest-4', '__dtest-5', '__dtest-6'];

  async function clean() {
    const biz = await query(
      `SELECT b.id FROM oc_business b JOIN oc_platform p ON p.id = b.platform_id
        WHERE b.city_key = ?`, [CITY],
    );
    if (biz.length) {
      const ids = biz.map((b) => b.id).join(',');
      await query(`DELETE FROM oc_scan_queue WHERE business_id IN (${ids})`);
      await query(`DELETE FROM oc_business WHERE id IN (${ids})`);
    }
    await query(`DELETE FROM oc_discovery_candidate WHERE city_key = ?`, [CITY]);
    await query(`DELETE FROM oc_do_not_scan WHERE platform_business_key = '777001'`);
  }

  before(async () => {
    live = await ping();
    if (!live) return;
    await clean();
    const rows = [
      [REFS[0], 'https://booksy.com/en-us/777000_disco-test-one_barber_tampa', 'Disco Test One'],
      [REFS[1], 'https://booksy.com/en-us/777000_disco-test-one_barber_tampa?again=1', ''],   // same shop twice
      [REFS[2], 'https://booksy.com/en-us/777001_owner-said-no', ''],                           // do-not-scan
      [REFS[3], 'https://www.vagaro.com/sometestshop', ''],                                     // no adapter yet
      [REFS[4], 'https://example.com/booking', ''],                                             // unknown
      [REFS[5], 'http://booksy.com/en-us/777002_x', ''],                                        // unsafe
    ];
    for (const [ref, url, name] of rows) {
      await query(
        `INSERT INTO oc_discovery_candidate (source, source_ref, city_key, niche_key, name, website, lat, lng, status)
         VALUES ('manual', ?, ?, 'barbershop', ?, ?, 27.95, -82.46, 'new')`,
        [ref, CITY, name || null, url],
      );
    }
    await query(
      `INSERT INTO oc_do_not_scan (platform_key, platform_business_key, reason, note)
       VALUES ('booksy', '777001', 'owner_request', 'discovery test')`,
    );
  });

  after(async () => {
    if (live) await clean();
    await closePool();
  });

  test('places, dedupes, refuses and parks', async (t) => {
    if (!live) return t.skip('no database');
    const s = await runDiscovery({ limit: 1000 });
    assert.ok(s.processed >= 6);

    const cand = Object.fromEntries((await query(
      'SELECT source_ref, status, detected_platform_key, business_id, recheck_after FROM oc_discovery_candidate WHERE city_key = ?', [CITY],
    )).map((c) => [c.source_ref, c]));

    assert.equal(cand[REFS[0]].status, 'verified');
    assert.equal(cand[REFS[1]].status, 'verified');
    assert.equal(cand[REFS[0]].business_id, cand[REFS[1]].business_id, 'the same shop pasted twice is ONE business');
    assert.equal(cand[REFS[2]].status, 'rejected', 'the do-not-scan register is honoured before a business exists');
    assert.equal(cand[REFS[2]].business_id, null);
    assert.equal(cand[REFS[3]].status, 'verified');
    assert.equal(cand[REFS[3]].detected_platform_key, 'vagaro');
    assert.equal(cand[REFS[3]].business_id, null, 'no adapter yet ⇒ recognised, not scheduled');
    assert.equal(cand[REFS[4]].status, 'not_bookable');
    assert.ok(cand[REFS[4]].recheck_after, 'unknown sites are re-checked later');
    assert.equal(cand[REFS[5]].status, 'rejected', 'an http:// URL is refused, not parked');

    const biz = await query('SELECT * FROM oc_business WHERE city_key = ?', [CITY]);
    assert.equal(biz.length, 1);
    assert.equal(biz[0].name, 'Disco Test One');
    assert.equal(biz[0].booking_url, 'https://booksy.com/en-us/777000_disco-test-one_barber_tampa');
    assert.equal(biz[0].timezone, 'America/New_York');
    const q = await query('SELECT status FROM oc_scan_queue WHERE business_id = ?', [biz[0].id]);
    assert.equal(q.length, 1, 'placed businesses are queued');
  });

  test('is idempotent', async (t) => {
    if (!live) return t.skip('no database');
    const s = await runDiscovery({ limit: 1000 });
    const mine = await query(`SELECT COUNT(*) AS n FROM oc_discovery_candidate WHERE city_key = ? AND status = 'new'`, [CITY]);
    assert.equal(Number(mine[0].n), 0);
    const biz = await query('SELECT id FROM oc_business WHERE city_key = ?', [CITY]);
    assert.equal(biz.length, 1, 'a second pass creates nothing new');
    assert.equal(typeof s.processed, 'number');
  });
});
