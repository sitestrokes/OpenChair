/**
 * @file src/scan/complete.js
 * A worker's completion, from the wire to a published number.
 *
 * docs/70-radar-build.md §2–§3. One call, in this order:
 *
 *   1. verify      the queue row is LOCKED, and it is this business on this
 *                  platform — a worker cannot complete a row it did not claim
 *   2. record      oc_scan_run + queue/business bookkeeping (queue.complete)
 *   3. store       oc_staff (labels only) · oc_business_service · oc_scan_snapshot
 *   4. score       the Meter: score() → persist() (oc_vacancy_daily + latest_*)
 *   5. roll up     oc_vacancy_weekly · oc_deadzone_cell
 *
 * Steps 3 run in one transaction: a snapshot that exists without its staff rows
 * would render chairs the business screen cannot label. Steps 4–5 run after
 * commit and read what step 3 wrote.
 *
 * A failed scan (blocked, timeout…) stops after step 2. It is still a fact worth
 * keeping — the canary and the backoff both read it — but it produces no
 * number, and must never overwrite the last good one.
 */

import { createHash } from 'node:crypto';
import { gzipSync } from 'node:zlib';
import { query, transaction } from '../db.js';
import config from '../config.js';
import { complete } from './queue.js';
import { validateEnvelope, validateData, PayloadError } from './payload.js';
import { score, persist } from '../meter/score.js';
import { rollupBusiness, accumulateDeadZones } from '../ledger/rollup.js';

const sha1 = (s) => createHash('sha1').update(String(s)).digest('hex');

/**
 * The schema's staff hash: sha1(business_id|staff_key) — oc_staff.staff_hash.
 * The adapter's own hash is platform-scoped; this one is business-scoped, so the
 * same resource id on two businesses can never collide in a snapshot.
 *
 * @param {number} businessId
 * @param {string} staffKey
 * @returns {string}
 */
export function schemaStaffHash(businessId, staffKey) {
  return sha1(`${businessId}|${staffKey}`);
}

/**
 * sha1(lower(trim(name))) — oc_business_service.service_key.
 *
 * @param {string} name
 * @returns {string}
 */
export function serviceKey(name) {
  return sha1(String(name).trim().toLowerCase());
}

/**
 * Dead-zone cells from a snapshot: day-of-week × 2-hour block, summed over
 * chairs. Shape matches ledger/rollup.js accumulateDeadZones().
 *
 * A slot counts as open minutes only where it falls inside the block AND the
 * chair's envelope; envelope minutes are the overlap of envelope and block. So a
 * block the shop is shut for contributes nothing — closed is not vacant.
 *
 * @param {{slotMinutes:number, days:Array<{date:string, units:Array<{envelope:[number,number], open:number[]}>}>}} snapshot
 * @param {string} nicheKey
 * @returns {Array<{dow:number, blockStartHour:number, openMinutes:number, envelopeMinutes:number, nicheKey:string}>}
 */
export function deadZoneCells(snapshot, nicheKey = '') {
  const slot = snapshot.slotMinutes > 0 ? snapshot.slotMinutes : 30;
  /** @type {Map<string, {dow:number, blockStartHour:number, openMinutes:number, envelopeMinutes:number, nicheKey:string}>} */
  const cells = new Map();

  for (const day of snapshot.days ?? []) {
    const dow = (new Date(`${day.date}T00:00:00Z`).getUTCDay() + 6) % 7;   // Mon = 0, matches oc_deadzone_cell
    for (const u of day.units ?? []) {
      const [es, ee] = u.envelope;
      for (let h = 6; h < 22; h += 2) {
        const bs = h * 60, be = (h + 2) * 60;
        const env = Math.max(0, Math.min(ee, be) - Math.max(es, bs));
        if (env <= 0) continue;
        let open = 0;
        for (const m of u.open ?? []) {
          const s = Math.max(m, bs, es);
          const e = Math.min(m + slot, be, ee);
          if (e > s) open += e - s;
        }
        const key = `${dow}:${h}`;
        const c = cells.get(key) ?? { dow, blockStartHour: h, openMinutes: 0, envelopeMinutes: 0, nicheKey };
        c.openMinutes += Math.min(open, env);
        c.envelopeMinutes += env;
        cells.set(key, c);
      }
    }
  }
  return [...cells.values()];
}

/**
 * Verify the worker holds this queue row.
 *
 * @param {{queueId:number, businessId:number, platformId:number}} p
 * @returns {Promise<void>}
 * @throws {PayloadError} QUEUE_MISMATCH
 */
async function verifyClaim(p) {
  const [q] = await query(
    'SELECT id, business_id, platform_id, status FROM oc_scan_queue WHERE id = ? LIMIT 1',
    [p.queueId],
  );
  if (!q || Number(q.business_id) !== p.businessId || Number(q.platform_id) !== p.platformId || q.status !== 'locked') {
    // One code for every way this fails: telling a caller WHICH field was wrong
    // helps an attacker enumerate queue rows and helps nobody else.
    throw new PayloadError('QUEUE_MISMATCH', 'That queue row is not locked for this business and platform.');
  }
}

/**
 * Handle one completion end to end.
 *
 * @param {any} body  the §3.2 payload, untrusted
 * @returns {Promise<{scored: {vacancyBp:number, confidence:number, bucket:string|null, publishable:boolean}|null}>}
 * @throws {PayloadError}
 */
export async function completeScan(body) {
  validateEnvelope(body);
  const ok = body.status === 'ok' || body.status === 'partial';
  // Validate the data BEFORE touching the database, so a refused payload leaves
  // no half-written run behind.
  const data = ok ? validateData(body) : null;

  await verifyClaim(body);

  await complete({
    queueId: body.queueId,
    businessId: body.businessId,
    platformId: body.platformId,
    observedOn: body.observedOn,
    status: body.status,
    adapterVersion: body.adapterVersion ?? null,
    tier: body.tier ?? 1,
    bytes: body.bytes ?? null,
    costMicros: body.costMicros ?? 0,
    durationMs: body.durationMs ?? null,
    errorCode: typeof body.errorCode === 'string' ? body.errorCode.slice(0, 40) : null,
    error: typeof body.error === 'string' ? body.error.slice(0, 255) : null,
  });

  if (!ok) return { scored: null };
  return { scored: await ingest(body.businessId, body.observedOn, data) };
}

/**
 * Steps 3–5 for a successful scan.
 *
 * @param {number} businessId
 * @param {string} observedOn
 * @param {ReturnType<typeof validateData>} data
 * @returns {Promise<{vacancyBp:number, confidence:number, bucket:string|null, publishable:boolean}>}
 */
export async function ingest(businessId, observedOn, data) {
  const [run] = await query(
    'SELECT id FROM oc_scan_run WHERE business_id = ? AND observed_on = ? LIMIT 1',
    [businessId, observedOn],
  );
  const scanRunId = run ? Number(run.id) : null;

  // ── 3. store ──────────────────────────────────────────────────────────────
  const stored = await transaction(async (conn) => {
    // Staff: stable "Chair N" in first-seen order. Existing rows keep their
    // label forever — a chair renumbered between two cards would make a
    // before/after look like a different shop.
    const [[{ maxN }]] = await conn.query(
      `SELECT COALESCE(MAX(CAST(SUBSTRING(label, 7) AS UNSIGNED)), 0) AS maxN
         FROM oc_staff WHERE business_id = ? AND label LIKE 'Chair %'`,
      [businessId],
    );
    let next = Number(maxN);
    /** adapter hash → schema hash */
    const hashMap = new Map();
    for (const s of data.staff) {
      const schemaHash = schemaStaffHash(businessId, s.staffKey);
      hashMap.set(s.staffHash, schemaHash);
      const [exists] = await conn.query(
        'SELECT id FROM oc_staff WHERE business_id = ? AND staff_key = ? LIMIT 1',
        [businessId, s.staffKey],
      );
      if (exists.length) {
        await conn.query(
          'UPDATE oc_staff SET staff_hash = ?, active = 1, last_seen_at = NOW() WHERE id = ?',
          [schemaHash, exists[0].id],
        );
      } else {
        next += 1;
        // display_name is deliberately absent: a scan never writes a name.
        await conn.query(
          `INSERT INTO oc_staff (business_id, staff_key, staff_hash, label, active, first_seen_at, last_seen_at)
           VALUES (?, ?, ?, ?, 1, NOW(), NOW())`,
          [businessId, s.staffKey, schemaHash, `Chair ${next}`],
        );
      }
    }

    // Services: the published menu, the T2 price source.
    for (const s of data.services) {
      await conn.query(
        `INSERT INTO oc_business_service (business_id, service_key, name, duration_min, price_cents, seen_on)
         VALUES (?, ?, ?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE name = VALUES(name), duration_min = VALUES(duration_min),
                                 price_cents = VALUES(price_cents), seen_on = VALUES(seen_on)`,
        [businessId, serviceKey(s.name), s.name, s.durationMin, s.priceCents, observedOn],
      );
    }

    // Snapshot: the per-chair grid with SCHEMA hashes only.
    let snapshotNew = false;
    let grid_sha1 = null;
    if (data.snapshot && scanRunId) {
      const snap = {
        v: 1,
        slotMinutes: data.snapshot.slotMinutes,
        days: data.snapshot.days.map((d) => ({
          date: d.date,
          units: d.units.map((u) => ({
            staffHash: hashMap.get(u.staffHash) ?? schemaStaffHash(businessId, u.staffHash),
            envelope: u.envelope,
            open: u.open,
          })),
        })),
      };
      const raw = JSON.stringify(snap);
      grid_sha1 = sha1(raw);
      const gz = gzipSync(raw);
      const staffCount = new Set(snap.days.flatMap((d) => d.units.map((u) => u.staffHash))).size;

      const [prev] = await conn.query(
        'SELECT grid_sha1 FROM oc_scan_snapshot WHERE scan_run_id = ? LIMIT 1',
        [scanRunId],
      );
      snapshotNew = !prev.length || prev[0].grid_sha1 !== grid_sha1;

      await conn.query(
        `INSERT INTO oc_scan_snapshot
           (scan_run_id, business_id, captured_on, horizon_days, slot_minutes, staff_count,
            grid_codec, grid_sha1, grid_bytes, grid_gz, purge_after)
         VALUES (?, ?, ?, ?, ?, ?, 'gzip_json_v1', ?, ?, ?, DATE_ADD(?, INTERVAL ? DAY))
         ON DUPLICATE KEY UPDATE
           captured_on = VALUES(captured_on), horizon_days = VALUES(horizon_days),
           slot_minutes = VALUES(slot_minutes), staff_count = VALUES(staff_count),
           grid_sha1 = VALUES(grid_sha1), grid_bytes = VALUES(grid_bytes),
           grid_gz = VALUES(grid_gz), purge_after = VALUES(purge_after)`,
        [scanRunId, businessId, observedOn, Math.min(127, snap.days.length || data.horizonDays),
         snap.slotMinutes, staffCount, grid_sha1, gz.length, gz, observedOn, config.retention.snapshotDays],
      );
    }

    // The business row's own facts, as last observed.
    const priced = data.services.filter((s) => s.priceCents > 0);
    const avgPrice = priced.length
      ? Math.round(priced.reduce((a, s) => a + s.priceCents, 0) / priced.length)
      : null;
    await conn.query(
      `UPDATE oc_business
          SET staff_count = COALESCE(?, staff_count),
              slot_minutes = ?,
              avg_price_cents = COALESCE(?, avg_price_cents),
              price_source = IF(? IS NULL, price_source, 'menu')
        WHERE id = ?`,
      [data.grid.staffCount || null, data.slotMinutes, avgPrice, avgPrice, businessId],
    );

    // The shop's published name replaces a PLACEHOLDER only — the "Unnamed
    // business" discovery writes for a URL with no slug, or the title-cased slug
    // itself. A name a person typed (or an earlier read set) is never overwritten
    // by a scan.
    const shopName = cleanShopName(data.businessName);
    if (shopName) {
      await conn.query(
        `UPDATE oc_business
            SET name = ?, name_from_slug = 0
          WHERE id = ? AND (name IS NULL OR name = '' OR name_from_slug = 1)`,
        [shopName, businessId],
      );
    }

    return { snapshotNew, avgPrice };
  });

  // ── 4. score ──────────────────────────────────────────────────────────────
  const [business] = await query('SELECT * FROM oc_business WHERE id = ?', [businessId]);

  const [[prior], [weeks], cohort, services] = await Promise.all([
    query(
      `SELECT COUNT(*) AS n FROM oc_scan_run
        WHERE business_id = ? AND status IN ('ok','partial') AND observed_on < ?`,
      [businessId, observedOn],
    ),
    query(
      `SELECT COUNT(DISTINCT YEARWEEK(observed_on, 3)) AS n FROM oc_vacancy_daily WHERE business_id = ?`,
      [businessId],
    ),
    query(
      `SELECT latest_vacancy_bp FROM oc_business
        WHERE city_key = ? AND niche_key = ? AND id <> ? AND status = 'active'
          AND suppressed_at IS NULL AND latest_vacancy_bp IS NOT NULL AND latest_confidence >= ?`,
      [business.city_key, business.niche_key, businessId, config.confidenceFloor],
    ),
    query(
      `SELECT duration_min, price_cents FROM oc_business_service
        WHERE business_id = ? AND price_cents > 0 AND duration_min > 0`,
      [businessId],
    ),
  ]);

  // The model reads prices in dollars per booking; the schema stores cents.
  const menu = services.map((s) => ({ price: Number(s.price_cents) / 100, durationMin: Number(s.duration_min) }));
  const prevBucket = business.latest_bucket === 'neutral' ? 'steady' : business.latest_bucket;

  const out = score({
    grid: data.grid,
    business,
    context: {
      baseDate: observedOn,
      horizonDays: 7,
      scanAgeHours: 0,
      observedWeeks: Number(weeks?.n ?? 0),
      priorSuccessfulScans: Number(prior?.n ?? 0),
      envelopeSource: 'observed',
      services: menu,
      priceTier: menu.length ? 'T2' : 'T3',
      prevBucket: prevBucket ?? null,
      cohortVacancies: cohort.length >= 5 ? cohort.map((c) => Number(c.latest_vacancy_bp) / 10000) : null,
    },
  });

  await persist({
    businessId,
    observedOn,
    out,
    grid: data.grid,
    scanRunId,
    avgPriceCents: stored.avgPrice ?? business.avg_price_cents ?? null,
    slotMinutes: data.slotMinutes,
  });

  // ── 5. roll up ────────────────────────────────────────────────────────────
  await rollupBusiness(businessId);
  // Dead zones accumulate per OBSERVATION. A retried completion for a run whose
  // grid has not changed is the same observation and must not count twice.
  if (data.snapshot && stored.snapshotNew) {
    await accumulateDeadZones(businessId, deadZoneCells(data.snapshot, business.niche_key), business.city_key);
  }

  return {
    vacancyBp: out.vacancyBp,
    confidence: out.confidence,
    bucket: out.bucket,
    publishable: out.publishable,
  };
}


/**
 * A shop's public name from the scan payload, or null. Business names are not
 * personal data; the payload's staff blocks never carry names at all.
 *
 * @param {unknown} v
 * @returns {string|null}
 */
function cleanShopName(v) {
  if (typeof v !== 'string') return null;
  const s = v.replace(/[\u0000-\u001f\u007f]/g, '').replace(/\s+/g, ' ').trim().slice(0, 120);
  return s.length >= 2 ? s : null;
}

export default { completeScan, ingest, deadZoneCells, schemaStaffHash, serviceKey };
