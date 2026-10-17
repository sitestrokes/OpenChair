/**
 * @file src/jobs/purge.js
 * Delete what has aged out. 30-data-model.md §7, docs/70 §3.3.
 *
 *   snapshots     raw per-chair grids are kept 14 days. The daily rollup and the
 *                 dead-zone accumulator keep what the product needs; the grid
 *                 itself is evidence for a dispute window, not an archive.
 *   staff names   oc_staff.display_name is the only column that may hold a
 *                 person's name. It is opt-in and NULLed after the retention
 *                 window, whatever else happens to the row.
 *
 * Batched with LIMIT so a backlog is worked down over several nights rather
 * than holding a long lock on a table the worker writes to.
 */
import { query } from '../db.js';
import config from '../config.js';

/** @returns {Promise<{snapshots: number, staffNames: number}>} */
export default async function purge() {
  const batch = Math.max(100, Number(config.retention.purgeBatch) | 0);
  const snaps = await query(
    `DELETE FROM oc_scan_snapshot WHERE purge_after < CURDATE() LIMIT ${batch}`,
  );
  const names = await query(
    `UPDATE oc_staff
        SET display_name = NULL, name_captured_at = NULL
      WHERE display_name IS NOT NULL
        AND name_captured_at < DATE_SUB(NOW(), INTERVAL ? DAY)`,
    [config.retention.staffNameDays],
  );
  return { snapshots: Number(snaps?.affectedRows ?? 0), staffNames: Number(names?.affectedRows ?? 0) };
}
