/**
 * @file test/isolation.test.js
 * The isolation checks 20-architecture.md §6.6 asks for as a CI grep.
 *
 * Two structural guarantees, both of which are true today and are one careless
 * import away from being false tomorrow:
 *
 *   1. openchair-service NEVER launches a browser and never crawls. It runs on
 *      the app box beside bridge-service, which holds live WebRTC and Twilio
 *      media sessions under a <300ms capture SLO (NFR-7). A headless Chrome pool
 *      at 150-400MB RSS each, on that box, is how you drop someone's sales call.
 *
 *   2. The worker has no direct network primitive. Its only route out is the
 *      egress broker, on another machine, which refuses anything but GET.
 *
 * These are cheap to assert and expensive to discover the hard way.
 */

import { test, describe } from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync, readdirSync, statSync, existsSync } from 'node:fs';
import { join, dirname } from 'node:path';
import { fileURLToPath } from 'node:url';

const HERE = dirname(fileURLToPath(import.meta.url));
const SERVICE = join(HERE, '..');
const WORKER = join(SERVICE, '..', 'openchair-worker');

/**
 * @param {string} dir
 * @param {string[]} [acc]
 * @returns {string[]}
 */
function jsFiles(dir, acc = []) {
  if (!existsSync(dir)) return acc;
  for (const name of readdirSync(dir)) {
    if (name === 'node_modules' || name === 'audit') continue;
    const p = join(dir, name);
    if (statSync(p).isDirectory()) jsFiles(p, acc);
    else if (name.endsWith('.js')) acc.push(p);
  }
  return acc;
}

describe('openchair-service never crawls', () => {
  test('declares no browser dependency', () => {
    const pkg = JSON.parse(readFileSync(join(SERVICE, 'package.json'), 'utf8'));
    const deps = Object.keys({ ...pkg.dependencies, ...pkg.devDependencies });
    const browser = deps.filter((d) => /playwright|puppeteer|selenium|chrome|cypress/i.test(d));
    assert.deepEqual(browser, [], 'a browser dependency here ends the isolation');
  });

  test('imports no browser driver', () => {
    const bad = [];
    for (const f of jsFiles(join(SERVICE, 'src'))) {
      const src = readFileSync(f, 'utf8');
      if (/from\s+['"](playwright|puppeteer|selenium-webdriver)/.test(src)) bad.push(f);
    }
    assert.deepEqual(bad, []);
  });

  test('makes no outbound request to a booking platform', () => {
    // The service SERVES http and talks to its database. It does not REQUEST the
    // web — anything here that did would be crawling from the voice box.
    //
    // Note what is deliberately allowed: `import { createServer } from
    // 'node:http'`. Importing the module is not the concern; calling out with it
    // is. A check that banned the import would be flagging the server for being
    // a server.
    const bad = [];
    for (const f of jsFiles(join(SERVICE, 'src'))) {
      const src = readFileSync(f, 'utf8');
      const outbound = [
        /(^|[^.\w])fetch\s*\(/,                       // global fetch
        /\b(?:https?|axios|got|undici)\s*\.\s*(?:request|get)\s*\(/, // http.get / https.request
        /from\s+['"](?:axios|got|node-fetch|undici|superagent)['"]/, // an http client dependency
      ];
      if (outbound.some((re) => re.test(src))) bad.push(f);
    }
    assert.deepEqual(bad, [], 'openchair-service must not make outbound web requests');
  });
});

describe('the worker has no direct network access', () => {
  test('adapters never import a network module', () => {
    const bad = [];
    for (const f of jsFiles(join(WORKER, 'src', 'adapters'))) {
      const src = readFileSync(f, 'utf8');
      if (/from\s+['"]node:https?['"]|require\(['"]https?['"]\)/.test(src)) bad.push(f);
      if (/(^|[^.\w])fetch\s*\(/.test(src)) bad.push(f);
    }
    assert.deepEqual(bad, [], 'an adapter must reach the web only through ctx.read()');
  });

  test('exactly one file in the worker calls fetch, and it is the egress client', () => {
    const callers = jsFiles(join(WORKER, 'src'))
      .filter((f) => /(^|[^.\w])fetch\s*\(/.test(readFileSync(f, 'utf8')))
      .map((f) => f.replace(/\\/g, '/'));

    assert.equal(callers.length, 1, `expected 1 fetch caller, got: ${callers.join(', ')}`);
    assert.match(callers[0], /lib\/egressClient\.js$/);
  });
});

describe('the tier-3 ceiling has no implementation to enable', () => {
  test('no worker or service code branches on tier 3', () => {
    const bad = [];
    for (const f of [...jsFiles(join(SERVICE, 'src')), ...jsFiles(join(WORKER, 'src'))]) {
      const src = readFileSync(f, 'utf8');
      // A residential/stealth code path would show up as one of these.
      if (/residential|stealth[-_ ]?browser|puppeteer-extra|fingerprint-injector/i.test(src)) bad.push(f);
    }
    assert.deepEqual(bad, [], 'tier 3 is out of scope by decision — there is nothing to switch on');
  });
});
