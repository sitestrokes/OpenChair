/**
 * @file src/routes/scan.js
 * The worker fleet's surface onto the scan queue.
 *
 *   POST /v1/scan/claim     worker takes a batch of due work
 *   POST /v1/scan/complete  worker reports the outcome of one pass
 *   GET  /v1/scan/depth     queue depth by status (ops)
 *   POST /v1/scan/reap      return claims whose worker died
 *
 * Note the asymmetry: the CONTROL plane here is POST, because a worker is
 * changing our own state. Nothing about that reaches a booking platform. The
 * only thing that ever leaves for a third party is a GET, issued by the egress
 * broker on a different machine.
 */

import { claim, complete, depth, reapStaleLocks } from '../scan/queue.js';
import { requireWorkerToken } from '../security/internalAuth.js';

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
 * @param {import('node:http').IncomingMessage} req
 * @returns {Promise<any>}
 */
function readJson(req) {
  return new Promise((resolve, reject) => {
    let size = 0;
    const chunks = [];
    req.on('data', (c) => {
      size += c.length;
      if (size > 256 * 1024) { reject(new Error('payload too large')); req.destroy(); return; }
      chunks.push(c);
    });
    req.on('end', () => {
      try { resolve(chunks.length ? JSON.parse(Buffer.concat(chunks).toString('utf8')) : {}); }
      catch (e) { reject(e); }
    });
    req.on('error', reject);
  });
}

/**
 * @param {import('node:http').IncomingMessage} req
 * @param {import('node:http').ServerResponse} res
 * @param {URL} url
 */
export default async function scanRoute(req, res, url) {
  const path = url.pathname;

  if (path === '/v1/scan/depth' && req.method === 'GET') {
    return json(res, 200, { ok: true, depth: await depth() });
  }

  if (!requireWorkerToken(req)) {
    return json(res, 401, { ok: false, error: 'unauthorised worker' });
  }

  if (path === '/v1/scan/claim' && req.method === 'POST') {
    const body = await readJson(req);
    const workerId = String(body.workerId ?? '').slice(0, 64);
    if (!workerId) return json(res, 400, { ok: false, error: 'workerId is required' });

    const batch = await claim(workerId, Math.min(50, Number(body.limit ?? 10)));
    return json(res, 200, { ok: true, claimed: batch.length, work: batch });
  }

  if (path === '/v1/scan/complete' && req.method === 'POST') {
    const body = await readJson(req);
    for (const f of ['queueId', 'businessId', 'platformId', 'observedOn', 'status']) {
      if (body[f] === undefined) return json(res, 400, { ok: false, error: `${f} is required` });
    }
    await complete(body);
    return json(res, 200, { ok: true });
  }

  if (path === '/v1/scan/reap' && req.method === 'POST') {
    return json(res, 200, { ok: true, swept: await reapStaleLocks() });
  }

  return json(res, 404, { ok: false, error: 'not found' });
}
