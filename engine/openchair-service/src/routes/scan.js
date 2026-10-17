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

import { claim, depth, reapStaleLocks } from '../scan/queue.js';
import { completeScan } from '../scan/complete.js';
import { PayloadError } from '../scan/payload.js';
import { json, readJson } from '../lib/http.js';
import { requireWorkerToken } from '../security/internalAuth.js';

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
    let body;
    try { body = await readJson(req, 4 * 1024); }
    catch (err) { return json(res, err.code === 'PAYLOAD_TOO_LARGE' ? 413 : 400, { ok: false, code: err.code ?? 'BAD_JSON', error: err.message }); }
    const workerId = String(body.workerId ?? '').slice(0, 64);
    if (!workerId) return json(res, 400, { ok: false, error: 'workerId is required' });

    const batch = await claim(workerId, Math.min(50, Number(body.limit ?? 10)));
    return json(res, 200, { ok: true, claimed: batch.length, work: batch });
  }

  if (path === '/v1/scan/complete' && req.method === 'POST') {
    let body;
    try { body = await readJson(req); }
    catch (err) { return json(res, err.code === 'PAYLOAD_TOO_LARGE' ? 413 : 400, { ok: false, code: err.code ?? 'BAD_JSON', error: err.message }); }

    try {
      const { scored } = await completeScan(body);
      return json(res, 200, { ok: true, scored });
    } catch (err) {
      if (err instanceof PayloadError) {
        // 409 for a claim the worker does not hold; 422 for a payload that is
        // well-formed JSON but not the contract. Neither echoes the payload.
        return json(res, err.code === 'QUEUE_MISMATCH' ? 409 : 422, { ok: false, code: err.code, error: err.message });
      }
      throw err;
    }
  }

  if (path === '/v1/scan/reap' && req.method === 'POST') {
    return json(res, 200, { ok: true, swept: await reapStaleLocks() });
  }

  return json(res, 404, { ok: false, error: 'not found' });
}
