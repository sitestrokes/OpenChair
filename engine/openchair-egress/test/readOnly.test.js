/**
 * @file test/readOnly.test.js
 * The read-only guard's spec.
 *
 * This suite IS spike criterion S7 ("zero non-GET requests") expressed as code.
 * S7 is pass/fail, not a threshold, so a red run here is a release blocker
 * regardless of what else is green.
 */

import { test, describe } from 'node:test';
import assert from 'node:assert/strict';
import { checkRequest, isPrivateHost } from '../src/guard/readOnly.js';

const GOOD = 'https://booksy.example.com/salon/1234';

describe('method — only reads leave the box', () => {
  for (const m of ['GET', 'HEAD', 'get', 'head']) {
    test(`allows ${m}`, () => {
      assert.equal(checkRequest({ method: m, url: GOOD }).ok, true);
    });
  }

  // Each of these is how a booking, a hold or a cancellation would be made.
  for (const m of ['POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS', 'TRACE', 'CONNECT']) {
    test(`refuses ${m}`, () => {
      const r = checkRequest({ method: m, url: GOOD });
      assert.equal(r.ok, false);
      assert.equal(r.code, 'METHOD_NOT_READ_ONLY');
    });
  }

  test('an absent method is a read, not an error', () => {
    assert.equal(checkRequest({ url: GOOD }).ok, true);
  });

  test('refuses a smuggled verb', () => {
    assert.equal(checkRequest({ method: 'GET\r\nX: 1', url: GOOD }).ok, false);
  });
});

describe('body — a read carries none', () => {
  test('refuses a GET with a body', () => {
    const r = checkRequest({ method: 'GET', url: GOOD, body: 'slot=14:30&confirm=1' });
    assert.equal(r.ok, false);
    assert.equal(r.code, 'BODY_ON_READ');
  });

  test('refuses an object body', () => {
    assert.equal(checkRequest({ url: GOOD, body: { slot: 1 } }).ok, false);
  });

  test('empty string and null are not bodies', () => {
    assert.equal(checkRequest({ url: GOOD, body: '' }).ok, true);
    assert.equal(checkRequest({ url: GOOD, body: null }).ok, true);
  });
});

describe('scheme and host', () => {
  test('allows http and https', () => {
    assert.equal(checkRequest({ url: 'http://example.com/a' }).ok, true);
    assert.equal(checkRequest({ url: 'https://example.com/a' }).ok, true);
  });

  for (const u of ['file:///etc/passwd', 'data:text/html,x', 'ftp://example.com', 'gopher://example.com']) {
    test(`refuses ${u.split(':')[0]}:`, () => {
      const r = checkRequest({ url: u });
      assert.equal(r.ok, false);
      assert.ok(['BAD_SCHEME', 'BAD_URL'].includes(r.code));
    });
  }

  test('refuses inline credentials in the URL', () => {
    const r = checkRequest({ url: 'https://user:pass@example.com/a' });
    assert.equal(r.ok, false);
    assert.equal(r.code, 'URL_CREDENTIALS');
  });

  test('refuses an unparseable URL', () => {
    assert.equal(checkRequest({ url: 'not a url' }).ok, false);
    assert.equal(checkRequest({}).ok, false);
  });
});

describe('private and metadata addresses', () => {
  const priv = [
    'localhost', '127.0.0.1', '0.0.0.0', '::1',
    '10.0.0.5', '172.16.0.1', '172.31.255.254', '192.168.1.1',
    '169.254.169.254',          // cloud metadata — how a proxy credential escapes
    '100.64.0.1',               // CGNAT
    'fd00::1', 'fe80::1',
    'db.local', 'api.localhost',
  ];
  for (const h of priv) {
    test(`refuses ${h}`, () => {
      assert.equal(isPrivateHost(h), true, `${h} should be private`);
      assert.equal(checkRequest({ url: `https://${h.includes(':') ? `[${h}]` : h}/x` }).ok, false);
    });
  }

  const pub = ['example.com', 'booksy.com', '8.8.8.8', '172.32.0.1', '192.169.0.1', '11.0.0.1'];
  for (const h of pub) {
    test(`allows ${h}`, () => {
      assert.equal(isPrivateHost(h), false, `${h} should be public`);
    });
  }

  test('a malformed dotted quad is refused, not accepted', () => {
    // Fail closed: something that looks like an address but is not parseable as
    // one must never fall through to "allowed".
    assert.equal(isPrivateHost('999.1.1.1'), true);
  });
});

describe('the login line — rule 9', () => {
  for (const h of ['Cookie', 'cookie', 'Authorization', 'X-API-Key', 'x-auth-token', 'Proxy-Authorization']) {
    test(`refuses a request carrying '${h}'`, () => {
      const r = checkRequest({ url: GOOD, headers: { [h]: 'v' } });
      assert.equal(r.ok, false);
      assert.equal(r.code, 'AUTH_HEADER');
    });
  }

  test('ordinary headers pass', () => {
    const r = checkRequest({
      url: GOOD,
      headers: { 'User-Agent': 'OpenChairBot/0.1', 'Accept-Language': 'en-US' },
    });
    assert.equal(r.ok, true);
  });
});

describe('tier ceiling', () => {
  test('tier 1 and 2 are allowed', () => {
    assert.equal(checkRequest({ url: GOOD, tier: 1 }).ok, true);
    assert.equal(checkRequest({ url: GOOD, tier: 2 }).ok, true);
  });

  test('tier 3 is refused — the ceiling is a decision, not a setting', () => {
    const r = checkRequest({ url: GOOD, tier: 3 });
    assert.equal(r.ok, false);
    assert.equal(r.code, 'TIER_CEILING');
  });

  test('a caller cannot raise the ceiling past what the guard knows', () => {
    // Even asked for tier 3 explicitly, there is no tier-3 path to take.
    assert.equal(checkRequest({ url: GOOD, tier: 3 }, { maxTier: 2 }).ok, false);
  });

  test('nonsense tiers are refused, not coerced', () => {
    for (const t of [0, -1, 2.5, NaN, 'two']) {
      assert.equal(checkRequest({ url: GOOD, tier: t }).ok, false, `tier ${t}`);
    }
  });
});

describe('fails closed', () => {
  test('an empty request is refused', () => {
    assert.equal(checkRequest({}).ok, false);
  });
  test('null and undefined are refused, not thrown on', () => {
    assert.equal(checkRequest(null).ok, false);
    assert.equal(checkRequest(undefined).ok, false);
  });
});
