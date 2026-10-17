/**
 * @file src/jobs/rollup.js
 * Recompute the weekly rollup for every business observed in the last 8 weeks.
 *
 * Completion already rolls up the business it scored; this pass catches a week
 * whose daily rows changed some other way (a backfill, a purge, a fix) and keeps
 * the Ledger honest about partial weeks.
 */
import { query } from '../db.js';
import { rollupBusiness } from '../ledger/rollup.js';

/** @returns {Promise<{businesses: number, weeks: number}>} */
export default async function rollup() {
  const rows = await query(
    `SELECT DISTINCT business_id FROM oc_vacancy_daily
      WHERE observed_on >= DATE_SUB(CURDATE(), INTERVAL 8 WEEK)`,
  );
  let weeks = 0;
  for (const r of rows) weeks += await rollupBusiness(Number(r.business_id));
  return { businesses: rows.length, weeks };
}
