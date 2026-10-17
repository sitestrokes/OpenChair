/**
 * @file src/discovery/run.js
 * Turn pasted or imported listings into businesses the Radar can scan.
 *
 * docs/70-radar-build.md §2. For each oc_discovery_candidate in status 'new':
 *
 *   detect      platformFromUrl() — URL signature only, no fetch
 *   refuse      a listing on the do-not-scan register (an owner asked us to
 *               stop) is 'rejected', before any business row exists — the only
 *               point at which honouring it PREVENTS the work rather than hiding it
 *   place       a platform we have a scheduled adapter for (rollout live/shadow)
 *               → create or attach oc_business, enqueue it, mark 'verified'
 *   recognise   a known platform with no adapter yet → 'verified', no business,
 *               re-checked later (counts toward honest coverage)
 *   park        anything else → 'not_bookable', re-checked in 90 days
 *
 * Idempotent: a candidate leaves 'new' exactly once, and a business is keyed on
 * (platform_id, platform_business_key), so two consultants pasting the same shop
 * produce ONE business and one queue row — the shared-scan dedupe (FR-3.1).
 *
 * Which column carries the URL: CI3 writes it to `website` (the field is the
 * listing's public page); `source_ref` is accepted as a fallback when it holds a
 * URL rather than a provider id.
 */

import { query } from '../db.js';
import config from '../config.js';
import { platformFromUrl } from './detect.js';
import { enqueue } from '../scan/queue.js';
import { isDoNotScan } from '../observability/removal.js';

/**
 * Business-local time zone by US state, from the city_key ("us-fl-tampa"). The
 * whole Meter is local-time maths — a Tampa shop scored on UTC days would have
 * its Tuesday evening counted as Wednesday — so an unknown key falls back to
 * Eastern (the launch market) and is logged, never left as UTC.
 */
const US_STATE_TZ = {
  ct: 'America/New_York', de: 'America/New_York', dc: 'America/New_York', fl: 'America/New_York',
  ga: 'America/New_York', in: 'America/Indiana/Indianapolis', ky: 'America/New_York', me: 'America/New_York',
  md: 'America/New_York', ma: 'America/New_York', mi: 'America/Detroit', nh: 'America/New_York',
  nj: 'America/New_York', ny: 'America/New_York', nc: 'America/New_York', oh: 'America/New_York',
  pa: 'America/New_York', ri: 'America/New_York', sc: 'America/New_York', vt: 'America/New_York',
  va: 'America/New_York', wv: 'America/New_York',
  al: 'America/Chicago', ar: 'America/Chicago', il: 'America/Chicago', ia: 'America/Chicago',
  ks: 'America/Chicago', la: 'America/Chicago', mn: 'America/Chicago', ms: 'America/Chicago',
  mo: 'America/Chicago', ne: 'America/Chicago', nd: 'America/Chicago', ok: 'America/Chicago',
  sd: 'America/Chicago', tn: 'America/Chicago', tx: 'America/Chicago', wi: 'America/Chicago',
  az: 'America/Phoenix', co: 'America/Denver', id: 'America/Boise', mt: 'America/Denver',
  nm: 'America/Denver', ut: 'America/Denver', wy: 'America/Denver',
  ca: 'America/Los_Angeles', nv: 'America/Los_Angeles', or: 'America/Los_Angeles', wa: 'America/Los_Angeles',
  ak: 'America/Anchorage', hi: 'Pacific/Honolulu',
};

/**
 * "us-fl-tampa" → { country: 'US', region: 'FL', city: 'Tampa', timezone }.
 *
 * @param {string} cityKey
 * @returns {{country: string|null, region: string|null, city: string|null, timezone: string}}
 */
export function parseCityKey(cityKey) {
  const parts = String(cityKey ?? '').toLowerCase().split('-');
  const country = parts[0] && parts[0].length === 2 ? parts[0].toUpperCase() : null;
  const region = parts[1] && parts[1].length <= 3 ? parts[1].toUpperCase() : null;
  const city = parts.length > 2
    ? parts.slice(2).join(' ').replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase()).slice(0, 120)
    : null;
  const timezone = (country === 'US' && region && US_STATE_TZ[region.toLowerCase()]) || 'America/New_York';
  return { country, region, city, timezone };
}

/**
 * Process one batch of new candidates.
 *
 * @param {{limit?: number}} [opts]
 * @returns {Promise<{processed: number, placed: number, attached: number, recognised: number, notBookable: number, rejected: number, invalid: number}>}
 */
export async function runDiscovery(opts = {}) {
  const limit = Math.max(1, Math.min(1000, Number(opts.limit ?? config.discovery.batch) | 0));
  const summary = { processed: 0, placed: 0, attached: 0, recognised: 0, notBookable: 0, rejected: 0, invalid: 0 };

  const candidates = await query(
    `SELECT id, source, source_ref, city_key, niche_key, name, website, address_line, lat, lng
       FROM oc_discovery_candidate
      WHERE status = 'new'
      ORDER BY id ASC
      LIMIT ${limit}`,
  );
  if (!candidates.length) return summary;

  const platforms = await query('SELECT id, platform_key, rollout, enabled FROM oc_platform');
  const byKey = new Map(platforms.map((p) => [p.platform_key, p]));

  for (const c of candidates) {
    summary.processed++;
    const url = c.website || (/^https?:/i.test(String(c.source_ref ?? '')) ? c.source_ref : null);
    const d = platformFromUrl(url);

    if (!d.ok) {
      // A malformed or unsafe URL is not a shop we failed to read; it is input
      // we refuse. Unknown-but-valid sites are parked for a re-check.
      const unsafe = ['EMPTY', 'TOO_LONG', 'BAD_CHARS', 'NOT_A_URL', 'NOT_HTTPS', 'CREDENTIALS', 'PORT', 'LOCAL_HOST', 'IP_HOST'];
      if (unsafe.includes(d.reason)) {
        summary.invalid++;
        await mark(c.id, 'rejected', { evidence: `refused: ${d.reason}`, recheck: null });
      } else {
        summary.notBookable++;
        await mark(c.id, 'not_bookable', { platformKey: d.platformKey ?? null, evidence: d.reason, recheck: config.discovery.recheckDays });
      }
      continue;
    }

    if (await isDoNotScan(d.platformKey, d.platformBusinessKey)) {
      summary.rejected++;
      await mark(c.id, 'rejected', { platformKey: d.platformKey, evidence: 'do-not-scan register', recheck: null });
      continue;
    }

    const p = byKey.get(d.platformKey);
    const scheduled = p && Number(p.enabled) === 1 && (p.rollout === 'live' || p.rollout === 'shadow');
    if (!scheduled) {
      summary.recognised++;
      await mark(c.id, 'verified', {
        platformKey: d.platformKey,
        evidence: `${d.evidence}; no scheduled adapter yet`,
        recheck: 30,
      });
      continue;
    }

    const where = parseCityKey(c.city_key);
    const [existing] = await query(
      'SELECT id FROM oc_business WHERE platform_id = ? AND platform_business_key = ? LIMIT 1',
      [p.id, d.platformBusinessKey],
    );
    let businessId;
    if (existing) {
      businessId = Number(existing.id);
      summary.attached++;
    } else {
      const name = (String(c.name ?? '').trim() || d.slugName || 'Unnamed business').slice(0, 191);
      const res = await query(
        `INSERT INTO oc_business
           (platform_id, platform_business_key, name, booking_url, address_line, city, region_code,
            country_code, city_key, niche_key, lat, lng, timezone, status, first_seen_at)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active', NOW())
         ON DUPLICATE KEY UPDATE id = LAST_INSERT_ID(id)`,
        [p.id, d.platformBusinessKey, name, d.canonicalUrl, c.address_line ?? null,
         where.city, where.region, where.country, c.city_key, c.niche_key,
         c.lat ?? null, c.lng ?? null, where.timezone],
      );
      businessId = Number(res.insertId);
      summary.placed++;
    }

    const [w] = await query(
      `SELECT COUNT(*) AS n FROM oc_prospect
        WHERE business_id = ? AND stage NOT IN ('lost','archived')`,
      [businessId],
    );
    await enqueue(businessId, p.id, { watchers: Number(w?.n ?? 0) });
    await mark(c.id, 'verified', { platformKey: d.platformKey, evidence: d.evidence, businessId, recheck: null });
  }

  process.stdout.write(`[discovery] ${JSON.stringify(summary)}\n`);
  return summary;
}

/**
 * @param {number} id
 * @param {string} status
 * @param {{platformKey?: string|null, evidence?: string, businessId?: number, recheck?: number|null}} f
 */
async function mark(id, status, f) {
  await query(
    `UPDATE oc_discovery_candidate
        SET status = ?, detected_platform_key = ?, detect_confidence = ?, detect_evidence = ?,
            business_id = COALESCE(?, business_id), probe_count = probe_count + 1, last_probed_at = NOW(),
            recheck_after = IF(? IS NULL, NULL, DATE_ADD(CURDATE(), INTERVAL ? DAY)), last_error = NULL
      WHERE id = ?`,
    [status, f.platformKey ?? null, f.platformKey ? 90 : null, String(f.evidence ?? '').slice(0, 512),
     f.businessId ?? null, f.recheck ?? null, f.recheck ?? 0, id],
  );
}

export default { runDiscovery, parseCityKey };
