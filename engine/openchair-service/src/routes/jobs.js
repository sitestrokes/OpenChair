/**
 * @file src/routes/jobs.js
 *
 *   POST /v1/jobs/<job>   run one job now → { ok, result: {job, ok, ms, summary|error} }
 *   POST /v1/jobs/all     the nightly sequence → { ok, results: [...] }
 *
 * Internal token required (server.js). For operators and CI3's admin screens;
 * cron uses src/jobs/run.js and never goes through HTTP.
 */
import { JOBS, ALL, runJob } from '../jobs/index.js';
import { json } from '../lib/http.js';

/**
 * @param {import('node:http').IncomingMessage} req
 * @param {import('node:http').ServerResponse} res
 * @param {URL} url
 */
export default async function jobsRoute(req, res, url) {
  if (req.method !== 'POST') return json(res, 405, { ok: false, error: 'POST only' });
  req.resume();   // no body expected; drain anything sent

  const name = decodeURIComponent(url.pathname.slice('/v1/jobs/'.length));
  if (name === 'all') {
    const results = [];
    for (const j of ALL) results.push(await runJob(j));
    return json(res, 200, { ok: results.every((r) => r.ok), results });
  }
  if (!Object.prototype.hasOwnProperty.call(JOBS, name)) {
    return json(res, 404, { ok: false, error: 'unknown job', jobs: Object.keys(JOBS) });
  }
  const result = await runJob(name);
  return json(res, result.ok ? 200 : 500, { ok: result.ok, result });
}
