/**
 * @file src/ledger/rollup.js
 * Daily observations → the weekly Ledger, and the Dead-Zone Map.
 *
 * ─────────────────────────────────────────────────────────────────────────────
 * THE LEDGER IS THE RETENTION PRODUCT
 * ─────────────────────────────────────────────────────────────────────────────
 * The prospecting artifact and the retainer report are the same screen: the
 * chart that won the client is the chart that keeps them. Which means every
 * number here eventually gets shown to the business owner who is PAYING for the
 * work it appears to credit — a much harsher audience than a prospect.
 *
 * So the honesty rules here are stricter than anywhere else in the product:
 *
 *   - A week with fewer than 7 scan days shows a GAP, never an average of what
 *     we happened to catch. `scan_days` is stored so the UI can draw the hole.
 *   - Lift is reported NET OF THE COHORT. A shop whose fill rate rose 20 points
 *     in a metro that rose 18 has been carried by the market, and claiming that
 *     as the consultant's work is the fastest way to lose the account when they
 *     notice.
 *   - A holiday week is excluded, not smoothed. Smoothing hides it; exclusion
 *     says so.
 */

import { query } from '../db.js';

/**
 * The Monday of the ISO week containing a date.
 *
 * Weeks are anchored to Monday because the Ledger's x-axis is a working week —
 * a Sunday-anchored week splits every weekend across two columns, and weekends
 * are where a barbershop's utilisation actually lives.
 *
 * @param {string} isoDate
 * @returns {string}
 */
export function weekStart(isoDate) {
  const d = new Date(`${isoDate}T00:00:00Z`);
  const dow = (d.getUTCDay() + 6) % 7;          // Mon = 0
  d.setUTCDate(d.getUTCDate() - dow);
  return d.toISOString().slice(0, 10);
}

/**
 * Roll one business's daily rows into weekly ones.
 *
 * @param {number} businessId
 * @param {number} [weeks] how far back to rebuild
 * @returns {Promise<number>} weeks written
 */
export async function rollupBusiness(businessId, weeks = 8) {
  const rows = await query(
    `SELECT observed_on, vacancy_bp, open_slots, bookable_slots, confidence, bucket,
            empty_chair_cents, days_to_next
       FROM oc_vacancy_daily
      WHERE business_id = ?
        AND observed_on >= DATE_SUB(CURDATE(), INTERVAL ? WEEK)
      ORDER BY observed_on ASC`,
    [businessId, weeks + 1],
  );
  if (!rows.length) return 0;

  const byWeek = new Map();

  for (const r of rows) {
    const wk = weekStart(String(r.observed_on).slice(0, 10));
    if (!byWeek.has(wk)) {
      byWeek.set(wk, { vac: [], open: 0, bookable: 0, ecr: 0, dtn: [], buckets: [], days: 0 });
    }
    const w = byWeek.get(wk);
    w.vac.push(Number(r.vacancy_bp));
    w.open += Number(r.open_slots);
    w.bookable += Number(r.bookable_slots);
    w.ecr += Number(r.empty_chair_cents);
    if (r.days_to_next !== null) w.dtn.push(Number(r.days_to_next));
    if (r.bucket) w.buckets.push(r.bucket);
    w.days += 1;
  }

  let written = 0;
  for (const [week, w] of byWeek) {
    // Ratio of sums, not mean of daily ratios. One thin Sunday with a 30-minute
    // denominator must not swing the week — the same reasoning the vacancy score
    // itself uses (35-scoring-model.md §1.8).
    const avgVacancyBp = w.bookable > 0
      ? Math.round((w.open / w.bookable) * 10000)
      : Math.round(w.vac.reduce((a, b) => a + b, 0) / w.vac.length);

    // Fill rate is the headline: "41% → 68%". It is 1 − vacancy, in basis points
    // so it cannot float-drift between two runs over the same data.
    const fillRateBp = 10000 - avgVacancyBp;

    await query(
      `INSERT INTO oc_vacancy_weekly
         (business_id, week_start, scan_days, avg_vacancy_bp, min_vacancy_bp, max_vacancy_bp,
          fill_rate_bp, avg_open_slots, avg_bookable_slots, avg_empty_chair_cents,
          avg_days_to_next, dominant_bucket)
       VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
       ON DUPLICATE KEY UPDATE
         scan_days             = VALUES(scan_days),
         avg_vacancy_bp        = VALUES(avg_vacancy_bp),
         min_vacancy_bp        = VALUES(min_vacancy_bp),
         max_vacancy_bp        = VALUES(max_vacancy_bp),
         fill_rate_bp          = VALUES(fill_rate_bp),
         avg_open_slots        = VALUES(avg_open_slots),
         avg_bookable_slots    = VALUES(avg_bookable_slots),
         avg_empty_chair_cents = VALUES(avg_empty_chair_cents),
         avg_days_to_next      = VALUES(avg_days_to_next),
         dominant_bucket       = VALUES(dominant_bucket)`,
      [
        businessId, week, w.days, avgVacancyBp,
        Math.min(...w.vac), Math.max(...w.vac),
        fillRateBp,
        Math.round(w.open / w.days), Math.round(w.bookable / w.days),
        Math.round(w.ecr / w.days),
        w.dtn.length ? Math.round(w.dtn.reduce((a, b) => a + b, 0) / w.dtn.length) : null,
        dominantBucket(w.buckets),
      ],
    );
    written++;
  }
  return written;
}

/**
 * The week's representative bucket — the mode, not the last value.
 *
 * A business that read Hungry six days and Slammed once had a Hungry week; the
 * last observation happening to be the outlier must not relabel it.
 *
 * @param {string[]} list
 * @returns {string}
 */
function dominantBucket(list) {
  if (!list.length) return 'neutral';
  const counts = {};
  for (const b of list) counts[b] = (counts[b] ?? 0) + 1;
  return Object.entries(counts).sort((a, b) => b[1] - a[1])[0][0];
}

/**
 * The cohort's movement over the same weeks.
 *
 * This is what turns "your fill rate rose" into a claim worth making. Without
 * it, a consultant takes credit for a busy season — and the owner, who has run
 * the shop for fifteen years, knows exactly when their busy season is.
 *
 * @param {string} cityKey
 * @param {string} nicheKey
 * @param {number} [weeks]
 * @returns {Promise<Array<{week_start: string, fill_rate_bp: number, n: number}>>}
 */
export async function cohortTrend(cityKey, nicheKey, weeks = 8) {
  return query(
    `SELECT w.week_start,
            ROUND(AVG(w.fill_rate_bp)) AS fill_rate_bp,
            COUNT(*) AS n
       FROM oc_vacancy_weekly w
       JOIN oc_business b ON b.id = w.business_id
      WHERE b.city_key = ? AND b.niche_key = ?
        AND b.suppressed_at IS NULL
        AND w.scan_days >= 5
      GROUP BY w.week_start
      ORDER BY w.week_start ASC
      LIMIT ?`,
    [cityKey, nicheKey, weeks],
  );
}

/**
 * Accumulate the Dead-Zone Map: which day-of-week × hour-block is chronically
 * empty. "Tuesday 1–4pm" is where the offer goes.
 *
 * Stored as a materialised ACCUMULATOR, not a time series (30-data-model.md
 * §5). A 7 × 8 matrix per business is fixed-size and answers the only question
 * anyone asks of it — "which blocks are chronically empty" — in one indexed
 * read. A time series would be unbounded and would have to be re-aggregated on
 * every view for no additional answer.
 *
 * EWMA at alpha 0.25: recent weeks matter more, but one quiet Tuesday cannot
 * create a dead zone on its own. The significance gates in the scoring model
 * decide which accumulated cells are real.
 *
 * @param {number} businessId
 * @param {Array<{dow: number, blockStartHour: number, openMinutes: number, envelopeMinutes: number}>} cells
 * @param {string} cityKey
 * @returns {Promise<void>}
 */
export async function accumulateDeadZones(businessId, cells, cityKey) {
  const ALPHA = 0.25;

  for (const c of cells) {
    if (!(c.envelopeMinutes > 0)) continue;   // closed blocks are not vacancy
    const bp = Math.round((c.openMinutes / c.envelopeMinutes) * 10000);

    await query(
      `INSERT INTO oc_deadzone_cell
         (business_id, dow, block_start_hour, block_hours, open_slots_sum, total_slots_sum,
          obs_count, open_ratio_bp, ewma_bp, city_key, niche_key, window_start, window_end)
       VALUES (?, ?, ?, 2, ?, ?, 1, ?, ?, ?, ?, CURDATE(), CURDATE())
       ON DUPLICATE KEY UPDATE
         open_slots_sum  = open_slots_sum + VALUES(open_slots_sum),
         total_slots_sum = total_slots_sum + VALUES(total_slots_sum),
         obs_count       = obs_count + 1,
         open_ratio_bp   = ROUND(10000 * (open_slots_sum + VALUES(open_slots_sum))
                                       / GREATEST(1, total_slots_sum + VALUES(total_slots_sum))),
         ewma_bp         = ROUND(${ALPHA} * VALUES(ewma_bp) + ${1 - ALPHA} * ewma_bp),
         window_end      = CURDATE()`,
      [businessId, c.dow, c.blockStartHour, c.openMinutes, c.envelopeMinutes, bp, bp,
       cityKey, c.nicheKey ?? ''],
    );
  }
}

export default { weekStart, rollupBusiness, cohortTrend, accumulateDeadZones };
