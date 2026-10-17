/**
 * @file src/server.js
 * openchair-service — the API the CI3 module and the worker fleet talk to.
 *
 * Runs on the APP BOX beside bridge-service, but shares nothing with it. It is a
 * separate process for one reason (20-architecture §1.7): adapters break weekly,
 * and bridge-service holds live WebRTC and Twilio media sessions. Restarting a
 * voice call to fix a Booksy selector is not a trade anyone would take.
 *
 * THIS PROCESS NEVER CRAWLS. It launches no browser and makes no outbound
 * request to a booking platform — that is the worker's job, through the egress
 * broker, on a different machine. If `playwright` is ever imported here the
 * isolation is gone, which is why the CI grep exists.
 *
 * Routes load DEFENSIVELY, the same pattern bridge-service uses: each handler is
 * dynamically imported inside a try, so a broken module makes one surface return
 * 503 while the service still boots and /health stays green.
 */

import { createServer } from 'node:http';
import config from './config.js';
import { ping } from './db.js';
import { requireInternalToken } from './security/internalAuth.js';

/** Routes that only the CI3 app or the worker fleet may call. */
const INTERNAL_PREFIXES = ['/v1/scan', '/v1/market', '/v1/admin', '/v1/discovery', '/v1/jobs'];

/** @param {string} pathname @returns {boolean} */
function isInternalRoute(pathname) {
  return INTERNAL_PREFIXES.some((p) => pathname.startsWith(p));
}

/**
 * @param {import('node:http').ServerResponse} res
 * @param {number} status
 * @param {unknown} payload
 */
function json(res, status, payload) {
  const body = JSON.stringify(payload);
  res.writeHead(status, { 'content-type': 'application/json', 'content-length': Buffer.byteLength(body) });
  res.end(body);
}

/**
 * Load a route module lazily. A module that fails to import degrades ONE
 * surface, not the process — the alternative is a typo in a rarely-used route
 * taking the whole service down at 3am.
 *
 * @param {string} spec
 * @returns {Promise<Function|null>}
 */
async function loadHandler(spec) {
  try {
    const mod = await import(spec);
    return mod.default ?? mod.handler ?? null;
  } catch (err) {
    process.stderr.write(`[routes] ${spec} unavailable: ${err?.message}\n`);
    return null;
  }
}

const server = createServer(async (req, res) => {
  const url = new URL(req.url ?? '/', `http://${req.headers.host ?? 'localhost'}`);
  const path = url.pathname;

  try {
    // ── Health. Never gated: a probe that needs a secret is not a probe. ────
    if (path === '/health') {
      const db = await ping();
      return json(res, db ? 200 : 503, {
        ok: db,
        service: 'openchair-service',
        db,
        uptimeSec: Math.round(process.uptime()),
      });
    }

    if (isInternalRoute(path) && !requireInternalToken(req)) {
      return json(res, 401, { ok: false, error: 'unauthorised' });
    }

    if (path.startsWith('/v1/scan')) {
      const h = await loadHandler('./routes/scan.js');
      if (!h) return json(res, 503, { ok: false, error: 'scan surface unavailable' });
      return h(req, res, url);
    }

    if (path.startsWith('/v1/market')) {
      const h = await loadHandler('./routes/market.js');
      if (!h) return json(res, 503, { ok: false, error: 'market surface unavailable' });
      return h(req, res, url);
    }

    if (path.startsWith('/v1/discovery/')) {
      const h = await loadHandler('./routes/discovery.js');
      if (!h) return json(res, 503, { ok: false, error: 'discovery surface unavailable' });
      return h(req, res, url);
    }

    if (path.startsWith('/v1/jobs/')) {
      const h = await loadHandler('./routes/jobs.js');
      if (!h) return json(res, 503, { ok: false, error: 'jobs surface unavailable' });
      return h(req, res, url);
    }

    return json(res, 404, { ok: false, error: 'not found' });
  } catch (err) {
    process.stderr.write(`[server] ${path}: ${err?.stack ?? err}\n`);
    return json(res, 500, { ok: false, error: 'internal error' });
  }
});

// Bind to loopback. CI3 reaches this over a server-side hop; nothing about it is
// browser-facing, so there is no reason for it to have a public socket.
server.listen(config.port, '127.0.0.1', () => {
  if (config.jobs.enabled) {
    import('./jobs/scheduler.js').then((m) => m.startScheduler()).catch((err) => {
      process.stderr.write(`[jobs] scheduler unavailable: ${err?.message}\n`);
    });
  }
  process.stdout.write(
    `openchair-service on 127.0.0.1:${config.port} (db ${config.db.database}) — no browser, no egress\n`,
  );
});

export default server;
