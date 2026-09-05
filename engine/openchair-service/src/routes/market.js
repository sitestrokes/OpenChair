/**
 * @file src/routes/market.js
 * Read surface over the global scan layer.
 *
 * GET /v1/market?city=us-fl-tampa&niche=barbershop
 *   → totals + bucket counts for a market
 *
 * GET /v1/market/coverage?city=us-fl-tampa
 *   → the honest coverage ratio (FR-2.3)
 *
 * These queries are duplicated in the CI3 model on purpose, not by accident. The
 * PHP app reads the database directly for page rendering — a page that needs a
 * second process alive to draw a list is a page that breaks twice as often. This
 * surface exists for the worker fleet and for anything that is not a web page.
 */

import { query } from '../db.js';
import config from '../config.js';

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
 * @param {import('node:http').ServerResponse} res
 * @param {URL} url
 */
export default async function marketRoute(req, res, url) {
  if (req.method !== 'GET') return json(res, 405, { ok: false, error: 'read-only surface' });

  const city = url.searchParams.get('city') ?? '';
  const niche = url.searchParams.get('niche') ?? '';
  if (!city) return json(res, 400, { ok: false, error: 'city is required' });

  // ── Coverage ──────────────────────────────────────────────────────────────
  if (url.pathname.endsWith('/coverage')) {
    const platforms = await query(
      `SELECT p.platform_key, p.name, p.rollout, p.enabled,
              COUNT(b.id) AS businesses,
              SUM(b.last_good_scan_at IS NOT NULL) AS scanned
         FROM oc_platform p
         LEFT JOIN oc_business b ON b.platform_id = p.id AND b.city_key = ?
        GROUP BY p.id
        ORDER BY businesses DESC`,
      [city],
    );

    const [funnel] = await query(
      `SELECT COUNT(*) AS found,
              SUM(status = 'verified')     AS verified,
              SUM(status = 'not_bookable') AS not_bookable
         FROM oc_discovery_candidate
        WHERE city_key = ? AND (? = '' OR niche_key = ?)`,
      [city, niche, niche],
    );

    return json(res, 200, { ok: true, city, funnel, platforms });
  }

  // ── Market totals ─────────────────────────────────────────────────────────
  // The confidence floor is applied to the SUM, not just to each row's display.
  // A total that quietly includes figures the product refuses to show
  // individually is a number we could not break down if asked — and being asked
  // to break it down is exactly what happens in a shop.
  const CONF_FLOOR = config.confidenceFloor;

  const [totals] = await query(
    `SELECT COUNT(*) AS businesses,
            SUM(IF(latest_confidence >= ?, latest_empty_chair_cents, 0)) AS ecr_cents,
            SUM(IF(latest_confidence >= ?, latest_open_slots, 0))        AS open_slots,
            SUM(latest_confidence <  ?)                                  AS below_floor,
            MAX(last_good_scan_at)                                       AS last_scan
       FROM oc_business
      WHERE city_key = ? AND (? = '' OR niche_key = ?)
        AND status = 'active' AND suppressed_at IS NULL`,
    [CONF_FLOOR, CONF_FLOOR, CONF_FLOOR, city, niche, niche],
  );

  const buckets = await query(
    `SELECT latest_bucket AS bucket, COUNT(*) AS n
       FROM oc_business
      WHERE city_key = ? AND (? = '' OR niche_key = ?)
        AND status = 'active' AND suppressed_at IS NULL
      GROUP BY latest_bucket`,
    [city, niche, niche],
  );

  return json(res, 200, {
    ok: true,
    city,
    niche,
    totals,
    buckets: Object.fromEntries(buckets.map((b) => [b.bucket ?? 'none', Number(b.n)])),
  });
}
