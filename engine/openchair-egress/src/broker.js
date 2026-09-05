/**
 * @file src/broker.js
 * openchair-egress — the only door to the internet.
 *
 * Workers do not fetch. They ask this process to fetch, and it decides. That
 * inversion is the entire design: a worker cannot accidentally issue a POST,
 * because a worker has no network code at all.
 *
 * Deployment: crawl VM only, bound to loopback. Nothing on the app box talks to
 * it, and it never talks to the app box — 20-architecture §1.6 requires that
 * nothing in the crawl tier can reach bridge-service.
 *
 * Zero dependencies. Node built-ins only.
 */

import { createServer } from 'node:http';
import config from './config.js';
import { checkRequest } from './guard/readOnly.js';
import { auditFetch, buildEntry } from './audit/jsonl.js';
import { selectProxy } from './proxy/select.js';

/**
 * Read a request body with a hard cap, so a malformed caller cannot exhaust
 * memory here.
 * @param {import('node:http').IncomingMessage} req
 * @returns {Promise<string>}
 */
function readBody(req) {
  return new Promise((resolve, reject) => {
    let size = 0;
    /** @type {Buffer[]} */
    const chunks = [];
    req.on('data', (c) => {
      size += c.length;
      if (size > 64 * 1024) { reject(new Error('control body too large')); req.destroy(); return; }
      chunks.push(c);
    });
    req.on('end', () => resolve(Buffer.concat(chunks).toString('utf8')));
    req.on('error', reject);
  });
}

/**
 * @param {import('node:http').ServerResponse} res
 * @param {number} status
 * @param {object} payload
 */
function json(res, status, payload) {
  const body = JSON.stringify(payload);
  res.writeHead(status, { 'content-type': 'application/json', 'content-length': Buffer.byteLength(body) });
  res.end(body);
}

/**
 * Perform an approved read.
 *
 * The guard has already run. Everything here is about being a polite client:
 * bounded time, bounded bytes, bounded redirects — and re-checking the guard on
 * every redirect hop, because a 302 is an attacker-controlled URL and the first
 * check said nothing about where it points.
 *
 * @param {URL} url
 * @param {{tier: number, headers?: Record<string,string>}} opts
 * @returns {Promise<{status: number, bytes: number, body: string, finalUrl: string, proxy: string|null}>}
 */
async function performRead(url, opts) {
  const proxy = selectProxy(opts.tier);
  const started = Date.now();

  let current = url;
  let hops = 0;

  for (;;) {
    const ac = new AbortController();
    const timer = setTimeout(() => ac.abort(), config.limits.timeoutMs);

    let res;
    try {
      res = await fetch(current, {
        method: 'GET',
        redirect: 'manual',           // we follow by hand so the guard sees each hop
        signal: ac.signal,
        headers: {
          'user-agent': config.userAgent,
          'accept': 'text/html,application/xhtml+xml,application/json;q=0.9,*/*;q=0.8',
          'accept-language': 'en-US,en;q=0.9',
          ...(opts.headers ?? {}),
        },
      });
    } finally {
      clearTimeout(timer);
    }

    if ([301, 302, 303, 307, 308].includes(res.status)) {
      const loc = res.headers.get('location');
      if (!loc) throw new Error(`redirect ${res.status} without a location`);
      if (++hops > config.limits.maxRedirects) throw new Error('too many redirects');

      const next = new URL(loc, current);
      // Re-guard. The first check validated the URL we chose; this one validates
      // the URL the SERVER chose, which is a different trust question entirely —
      // a redirect into 169.254.169.254 is the classic way out of a sandbox.
      const verdict = checkRequest({ method: 'GET', url: next.href, tier: opts.tier }, { maxTier: config.maxTier });
      if (!verdict.ok) throw new Error(`redirect refused: ${verdict.code} — ${verdict.reason}`);

      current = next;
      continue;
    }

    // Bounded read: stop at the cap rather than buffering whatever arrives.
    const reader = res.body?.getReader();
    let bytes = 0;
    /** @type {Uint8Array[]} */
    const parts = [];
    if (reader) {
      for (;;) {
        const { done, value } = await reader.read();
        if (done) break;
        bytes += value.length;
        if (bytes > config.limits.maxBytes) { await reader.cancel(); break; }
        parts.push(value);
      }
    }

    return {
      status: res.status,
      bytes,
      body: Buffer.concat(parts.map((p) => Buffer.from(p))).toString('utf8'),
      finalUrl: current.href,
      proxy,
      ms: Date.now() - started,
    };
  }
}

const server = createServer(async (req, res) => {
  if (req.url === '/health') {
    return json(res, 200, { ok: true, service: 'openchair-egress', maxTier: config.maxTier });
  }

  // The control plane itself takes a POST — that is how a worker asks for a
  // read. It is not the read. The thing that goes out is always a GET.
  if (req.method !== 'POST' || req.url !== '/fetch') {
    return json(res, 404, { ok: false, error: 'not found' });
  }

  if (config.token && req.headers['x-egress-token'] !== config.token) {
    return json(res, 401, { ok: false, error: 'unauthorised' });
  }

  /** @type {any} */
  let ask;
  try {
    ask = JSON.parse(await readBody(req));
  } catch {
    return json(res, 400, { ok: false, error: 'bad control payload' });
  }

  const verdict = checkRequest(ask, { maxTier: config.maxTier });
  if (!verdict.ok) {
    // Refusals are logged deliberately: "we only sent GETs" is a claim, a log of
    // every refusal is evidence.
    auditFetch(buildEntry(ask, { ok: false, code: verdict.code }));
    return json(res, 403, { ok: false, code: verdict.code, error: verdict.reason });
  }

  try {
    const out = await performRead(verdict.url, { tier: Number(ask.tier ?? 1), headers: ask.headers });
    auditFetch(buildEntry(ask, { ok: true, status: out.status, bytes: out.bytes, ms: out.ms, proxy: out.proxy }));
    return json(res, 200, {
      ok: true, status: out.status, bytes: out.bytes, ms: out.ms,
      finalUrl: out.finalUrl, body: out.body,
    });
  } catch (err) {
    auditFetch(buildEntry(ask, { ok: false, code: 'FETCH_FAILED' }));
    return json(res, 502, { ok: false, code: 'FETCH_FAILED', error: String(err?.message ?? err) });
  }
});

server.listen(config.port, '127.0.0.1', () => {
  process.stdout.write(
    `openchair-egress on 127.0.0.1:${config.port} — GET/HEAD only, tier ceiling ${config.maxTier}\n`,
  );
});

export default server;
