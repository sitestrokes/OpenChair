/**
 * @file src/scan/queue.js
 * The scan queue, and the shared-scan dedupe.
 *
 * ─────────────────────────────────────────────────────────────────────────────
 * THE ONE IDEA IN THIS FILE
 * ─────────────────────────────────────────────────────────────────────────────
 * A business is scanned ONCE PER DAY, no matter how many tenants watch it
 * (FR-3.1). Watching raises its priority; it never adds work.
 *
 * This is not an optimisation, it is the business model. At $0.02 a scan, a
 * shared crawl of 100k businesses is a fixed cost the whole customer base
 * amortises. Per-tenant crawling multiplies that by every watcher and the unit
 * economics invert — a $47 one-time front-end would be paying for the same shop
 * to be fetched fifty times a night.
 *
 * Two schema facts enforce it, which is why this code can be simple:
 *   oc_scan_queue  UNIQUE(business_id)              — one durable slot each
 *   oc_scan_run    UNIQUE(business_id, observed_on) — one pass per day
 *
 * A retry inside the same day UPSERTs the run row (attempts++). It does not
 * create a second pass. "Rate-limited, cached, one pass per business per day"
 * is enforced by the database rather than by hope.
 */

import { query, transaction } from '../db.js';
import config from '../config.js';

/**
 * Enqueue a business, or raise its priority if it is already queued.
 *
 * The ON DUPLICATE KEY branch is the dedupe. The second tenant to watch a shop
 * does not get a second queue row; they increment watcher_count and pull due_at
 * earlier. That is the whole mechanism.
 *
 * @param {number} businessId
 * @param {number} platformId
 * @param {{watchers?: number, dueAt?: string|null}} [opts]
 * @returns {Promise<void>}
 */
export async function enqueue(businessId, platformId, opts = {}) {
  const watchers = Number(opts.watchers ?? 0);
  // Priority 1 is soonest. More watchers = sooner, floored at 1, defaulting to 5.
  const priority = Math.max(1, 5 - Math.min(4, Math.floor(watchers / 3)));

  await query(
    `INSERT INTO oc_scan_queue (business_id, platform_id, priority, watcher_count, due_at, status)
     VALUES (?, ?, ?, ?, COALESCE(?, NOW()), 'due')
     ON DUPLICATE KEY UPDATE
       watcher_count = VALUES(watcher_count),
       priority      = LEAST(priority, VALUES(priority)),
       due_at        = LEAST(due_at, VALUES(due_at)),
       status        = IF(status = 'failed', 'due', status)`,
    [businessId, platformId, priority, watchers, opts.dueAt ?? null],
  );
}

/**
 * Claim a batch of due work for one worker.
 *
 * ─────────────────────────────────────────────────────────────────────────────
 * WHY THIS IS NOT `SELECT ... FOR UPDATE SKIP LOCKED`
 * ─────────────────────────────────────────────────────────────────────────────
 * 20-architecture.md §8.1 specifies SKIP LOCKED. **MariaDB did not get SKIP
 * LOCKED until 10.6**, and the target — both the host engine's box and this one
 * — is 10.4. The statement is a syntax error there, so the documented pattern
 * cannot be used as written.
 *
 * The substitute is the standard pre-SKIP-LOCKED claim idiom, and it is race-safe
 * for a different reason. Two workers may compute overlapping candidate sets, but
 * the UPDATE re-asserts `status = 'due'` under a row lock, so only one of them
 * actually flips any given row. The loser silently updates fewer rows rather than
 * stealing work. We then read back only what THIS claim stamped, identified by a
 * per-claim token rather than by worker id — a worker claiming twice in
 * succession must not pick up its own earlier batch a second time.
 *
 * The alternative (plain FOR UPDATE, no SKIP) would block workers behind each
 * other instead of letting them pass, which serialises the whole fleet.
 *
 * Only platforms whose rollout is 'live' or 'shadow' are scheduled. 'research'
 * has no trustworthy adapter yet, and 'frozen' means the canary tripped: keep
 * scanning for diagnosis, publish nothing (FR-9.2).
 *
 * @param {string} workerId
 * @param {number} [limit]
 * @returns {Promise<Array<{id:number,business_id:number,platform_id:number,platform_key:string,booking_url:string,rollout:string}>>}
 */
export async function claim(workerId, limit = 10) {
  const n = Math.max(1, Math.min(50, Number(limit) | 0));
  // Unique per claim, and short enough for locked_by's varchar(64).
  const token = `${String(workerId).slice(0, 24)}:${Date.now().toString(36)}${Math.random().toString(36).slice(2, 8)}`;

  return transaction(async (conn) => {
    // The derived table is required: MariaDB refuses a subquery that selects
    // from the same table an UPDATE targets, unless it is wrapped one level.
    const [upd] = await conn.query(
      `UPDATE oc_scan_queue
          SET status = 'locked', locked_by = ?, locked_at = NOW()
        WHERE status = 'due'
          AND id IN (
            SELECT id FROM (
              SELECT q.id
                FROM oc_scan_queue q
                JOIN oc_business  b ON b.id = q.business_id
                JOIN oc_platform  p ON p.id = q.platform_id
               WHERE q.status = 'due'
                 AND q.due_at <= NOW()
                 AND p.enabled = 1
                 AND p.rollout IN ('live', 'shadow')
                 AND b.status = 'active'
                 AND b.suppressed_at IS NULL
               ORDER BY q.priority ASC, q.due_at ASC
               LIMIT ${n}
            ) AS candidates
          )`,
      [token],
    );

    if (!upd?.affectedRows) return [];

    const [rows] = await conn.query(
      `SELECT q.id, q.business_id, q.platform_id, b.booking_url, b.timezone,
              p.platform_key, p.rollout, p.requires_headless
         FROM oc_scan_queue q
         JOIN oc_business  b ON b.id = q.business_id
         JOIN oc_platform  p ON p.id = q.platform_id
        WHERE q.locked_by = ?`,
      [token],
    );

    return rows;
  });
}

/**
 * Record the outcome of a pass and schedule the next one.
 *
 * The scan_run UPSERT is where "one pass per day" actually happens: a retry
 * within the same local day lands on the existing row and increments attempts.
 *
 * @param {{queueId:number, businessId:number, platformId:number, observedOn:string,
 *          status:string, tier?:number, bytes?:number, costMicros?:number,
 *          durationMs?:number, adapterVersion?:string, error?:string|null}} r
 * @returns {Promise<void>}
 */
export async function complete(r) {
  const ok = r.status === 'ok' || r.status === 'partial';

  // cost_micros ACCUMULATES across attempts rather than being replaced: three
  // blocked tries followed by a success cost us four fetches, and a cost model
  // that only records the last one would under-report exactly the businesses
  // that are expensive to read.
  await query(
    `INSERT INTO oc_scan_run
       (business_id, platform_id, observed_on, status, adapter_version,
        tier, bytes, cost_micros, duration_ms, attempts, error_code, error_note,
        started_at, finished_at)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1, ?, ?, NOW(), NOW())
     ON DUPLICATE KEY UPDATE
       status          = VALUES(status),
       adapter_version = VALUES(adapter_version),
       tier            = VALUES(tier),
       bytes           = VALUES(bytes),
       cost_micros     = cost_micros + VALUES(cost_micros),
       duration_ms     = VALUES(duration_ms),
       attempts        = attempts + 1,
       error_code      = VALUES(error_code),
       error_note      = VALUES(error_note),
       finished_at     = NOW()`,
    [
      r.businessId, r.platformId, r.observedOn, r.status, r.adapterVersion ?? null,
      Number(r.tier ?? 1), r.bytes ?? null, Number(r.costMicros ?? 0),
      r.durationMs ?? null,
      r.errorCode ?? null,
      r.error ? String(r.error).slice(0, 255) : null,
    ],
  );

  // Next pass one interval out on success. On failure, back off exponentially
  // rather than hammering a platform that is already refusing us — politeness
  // is also the trespass-to-chattels defence (50-risk §A6.3).
  if (ok) {
    await query(
      `UPDATE oc_scan_queue
          SET status = 'due', locked_by = NULL, locked_at = NULL, attempts = 0,
              last_error = NULL, due_at = DATE_ADD(NOW(), INTERVAL ? HOUR)
        WHERE id = ?`,
      [config.scan.intervalHours, r.queueId],
    );
    await query(
      `UPDATE oc_business
          SET last_scan_at = NOW(), last_good_scan_at = NOW(), consecutive_failures = 0
        WHERE id = ?`,
      [r.businessId],
    );
    return;
  }

  await query(
    `UPDATE oc_scan_queue
        SET attempts   = attempts + 1,
            locked_by  = NULL,
            locked_at  = NULL,
            last_error = ?,
            status     = IF(attempts + 1 >= ?, 'failed', 'due'),
            due_at     = DATE_ADD(NOW(), INTERVAL POW(4, LEAST(attempts + 1, 4)) MINUTE)
      WHERE id = ?`,
    [r.error ?? null, config.scan.maxAttempts, r.queueId],
  );
  await query(
    `UPDATE oc_business
        SET last_scan_at = NOW(),
            consecutive_failures = consecutive_failures + 1,
            status = IF(consecutive_failures + 1 >= 10, 'unreachable', status)
      WHERE id = ?`,
    [r.businessId],
  );
}

/**
 * Return claims whose worker died mid-pass.
 *
 * Without this a crashed worker silently removes businesses from the rotation:
 * the row stays 'locked' forever and nobody notices until a customer asks why
 * their prospect has not updated in a week.
 *
 * @returns {Promise<number>} rows swept
 */
export async function reapStaleLocks() {
  const res = await query(
    `UPDATE oc_scan_queue
        SET status = 'due', locked_by = NULL, locked_at = NULL
      WHERE status = 'locked'
        AND locked_at < DATE_SUB(NOW(), INTERVAL ? MINUTE)`,
    [config.scan.lockTtlMinutes],
  );
  return res?.affectedRows ?? 0;
}

/**
 * Queue depth by status, for the ops console.
 * @returns {Promise<Record<string, number>>}
 */
export async function depth() {
  const rows = await query('SELECT status, COUNT(*) AS n FROM oc_scan_queue GROUP BY status');
  return Object.fromEntries(rows.map((r) => [r.status, Number(r.n)]));
}

export default { enqueue, claim, complete, reapStaleLocks, depth };
