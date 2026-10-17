/**
 * @file src/jobs/enqueueDue.js
 * Make sure every scannable business has exactly one queue row.
 *
 * "Scannable" = active, not suppressed, on an enabled platform whose adapter is
 * scheduled (rollout live or shadow — research is never scheduled, frozen was
 * stopped by the canary). The watcher count comes from oc_prospect across ALL
 * tenants: fifty consultants watching one shop is one row with a higher
 * priority, never fifty reads (FR-3.1).
 *
 * The INSERT … SELECT only adds missing rows and refreshes watcher counts; it
 * never pulls a row's due_at forward, so this job cannot cause a second read of
 * a business inside its 24-hour window.
 */
import { query } from '../db.js';

/** @returns {Promise<{inserted: number, refreshed: number}>} */
export default async function enqueueDue() {
  const res = await query(
    `INSERT INTO oc_scan_queue (business_id, platform_id, priority, watcher_count, due_at, status)
     SELECT b.id, b.platform_id,
            GREATEST(1, 5 - LEAST(4, FLOOR(COALESCE(w.n, 0) / 3))),
            COALESCE(w.n, 0), NOW(), 'due'
       FROM oc_business b
       JOIN oc_platform p ON p.id = b.platform_id
       LEFT JOIN (
         SELECT business_id, COUNT(*) AS n FROM oc_prospect
          WHERE stage NOT IN ('lost','archived') GROUP BY business_id
       ) w ON w.business_id = b.id
      WHERE b.status = 'active' AND b.suppressed_at IS NULL
        AND b.platform_business_key NOT LIKE 'demo-%'   -- seeded demo rows are never read
        AND p.enabled = 1 AND p.rollout IN ('live','shadow')
     ON DUPLICATE KEY UPDATE
       watcher_count = VALUES(watcher_count),
       priority      = LEAST(priority, VALUES(priority))`,
  );
  // MariaDB reports 1 per inserted row and 2 per updated row.
  const affected = Number(res?.affectedRows ?? 0);
  const changed = Number(res?.changedRows ?? 0);
  return { inserted: Math.max(0, affected - 2 * changed), refreshed: changed };
}
