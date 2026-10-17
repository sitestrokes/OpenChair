/**
 * @file src/routes/discovery.js
 *
 *   POST /v1/discovery/run    process a batch of new candidates now
 *   POST /v1/discovery/detect { url } → which platform, no side effects
 *
 * Internal token required (server.js). CI3 calls /run after a consultant pastes
 * URLs on New scan, so the businesses appear without waiting for the next tick.
 */
import { runDiscovery } from '../discovery/run.js';
import { platformFromUrl } from '../discovery/detect.js';
import { json, readJson } from '../lib/http.js';

/**
 * @param {import('node:http').IncomingMessage} req
 * @param {import('node:http').ServerResponse} res
 * @param {URL} url
 */
export default async function discoveryRoute(req, res, url) {
  if (req.method !== 'POST') return json(res, 405, { ok: false, error: 'POST only' });

  let body;
  try { body = await readJson(req, 8 * 1024); }
  catch (err) { return json(res, err.code === 'PAYLOAD_TOO_LARGE' ? 413 : 400, { ok: false, code: err.code ?? 'BAD_JSON', error: err.message }); }

  if (url.pathname === '/v1/discovery/run') {
    const limit = Number.isInteger(body.limit) ? body.limit : undefined;
    return json(res, 200, { ok: true, summary: await runDiscovery({ limit }) });
  }
  if (url.pathname === '/v1/discovery/detect') {
    const d = platformFromUrl(body.url);
    return json(res, 200, { ok: true, detection: d });
  }
  return json(res, 404, { ok: false, error: 'not found' });
}
