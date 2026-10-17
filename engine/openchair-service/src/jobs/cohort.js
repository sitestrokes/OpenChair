/**
 * @file src/jobs/cohort.js
 * Refresh each saved market's business count — the denominator a tenant sees
 * ("47 barbershops in Tampa") and the cohort size the Ledger nets against.
 *
 * Counts are taken from the global layer and written onto the tenant's market
 * row; nothing here reads or writes across tenants beyond that number.
 */
import { query } from '../db.js';

/** @returns {Promise<{markets: number}>} */
export default async function cohort() {
  const res = await query(
    `UPDATE oc_market m
        JOIN (
          SELECT city_key, niche_key, COUNT(*) AS n
            FROM oc_business
           WHERE status = 'active' AND suppressed_at IS NULL
           GROUP BY city_key, niche_key
        ) c ON c.city_key = m.city_key AND c.niche_key = m.niche_key
        SET m.business_count = c.n, m.last_refreshed_at = NOW()`,
  );
  return { markets: Number(res?.affectedRows ?? 0) };
}
