/**
 * @file src/lib/fixtureReader.js
 * The default reader: recorded (today: synthetic) pages from fixtures/, and no
 * network whatsoever.
 *
 * Same shape as egressClient.read() — `read(url, opts) → {status, body, bytes,
 * ms, finalUrl}` — so an adapter cannot tell which one it was handed, which is
 * the point: the code path that runs in tests and in the end-to-end demo is the
 * code path that will run against the broker, minus the internet.
 *
 * GET-only by construction: there is no method parameter to honour, exactly as
 * in egressClient. fixtures/index.json maps a URL to a file; dates inside both
 * the URL keys and the files are written as {{D0}}…{{D20}} and filled with
 * "today + n" IN THE BUSINESS'S TIMEZONE, so a fixture never goes stale and a
 * week of availability always starts today.
 *
 * An unknown URL is a 404 with code FIXTURE_MISSING — which an adapter maps to
 * not_found. It must never be a 200 with an empty body: that is the
 * silent-parse-failure shape the validator exists to stop.
 */

import { readFile } from 'node:fs/promises';
import { join, resolve, sep } from 'node:path';
import { todayIn, addDays } from './localDate.js';

/** Highest {{Dn}} offset a fixture may use. */
const MAX_OFFSET = 30;

/**
 * Replace {{Dn}} with today+n in the zone.
 *
 * @param {string} text
 * @param {string} today YYYY-MM-DD
 * @returns {string}
 */
export function fillDates(text, today) {
  return String(text).replace(/\{\{D(\d{1,2})\}\}/g, (_, n) => {
    const k = Number(n);
    return k <= MAX_OFFSET ? addDays(today, k) : _;
  });
}

/**
 * Build a reader over a fixtures directory.
 *
 * @param {Object} opts
 * @param {string} opts.dir           fixtures directory containing index.json
 * @param {() => Date} [opts.now]     clock, for tests
 * @returns {{mode: 'fixture', read: (url: string, o?: {timezone?: string}) => Promise<{status:number, body:string, bytes:number, ms:number, finalUrl:string, code?:string}>}}
 */
export function createFixtureReader({ dir, now = () => new Date() }) {
  const root = resolve(dir);
  /** @type {Promise<Record<string, {file?: string, status?: number, contentType?: string}>>|null} */
  let indexP = null;
  const index = () => (indexP ??= readFile(join(root, 'index.json'), 'utf8').then((t) => JSON.parse(t)));

  return {
    mode: 'fixture',
    async read(url, o = {}) {
      const started = Date.now();
      const today = todayIn(o.timezone, now());
      const idx = await index();

      // Keys may carry date placeholders (an availability URL names its range),
      // so match against the FILLED key.
      let entry = null;
      for (const [k, v] of Object.entries(idx)) {
        if (k.startsWith('_')) continue;           // _comment keys
        if (fillDates(k, today) === url) { entry = v; break; }
      }
      if (!entry) {
        return { status: 404, body: '', bytes: 0, ms: Date.now() - started, finalUrl: url, code: 'FIXTURE_MISSING' };
      }

      let body = '';
      if (entry.file) {
        // Keep reads inside the fixtures directory, whatever index.json says.
        const path = resolve(root, entry.file);
        if (!path.startsWith(root + sep)) {
          throw Object.assign(new Error(`fixture path escapes ${root}`), { code: 'FIXTURE_PATH' });
        }
        body = fillDates(await readFile(path, 'utf8'), today);
      }
      return {
        status: entry.status ?? 200,
        body,
        bytes: Buffer.byteLength(body),
        ms: Date.now() - started,
        finalUrl: url,
      };
    },
  };
}

export default { createFixtureReader, fillDates };
