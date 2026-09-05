/**
 * @file src/observability/removal.js
 * Business removal requests (FR-9.3, 50-risk §A6.6).
 *
 * ─────────────────────────────────────────────────────────────────────────────
 * THE PROMISE THIS KEEPS
 * ─────────────────────────────────────────────────────────────────────────────
 * A business owner who discovers we read their booking page must be able to say
 * stop, and have it mean something. That promise is cheap to make and easy to
 * break in three specific ways, all of which this module is shaped to avoid:
 *
 *   1. The opt-out expires silently. Deleting the business row means the next
 *      discovery sweep finds the same shop, adds it back, and starts scanning
 *      again — while the owner believes it worked. So suppression leaves a
 *      TOMBSTONE, and the listing goes on a permanent do-not-scan list keyed by
 *      platform rather than by our internal id.
 *
 *   2. The data lingers. Suppressing the parent while leaving the grids,
 *      rollups and dead-zone cells means the numbers stay queryable and can
 *      still reach a tenant through a cached view. Children are purged.
 *
 *   3. The SLA is a claim rather than a fact. `due_by` is written on intake, so
 *      "we honour removal requests within N days" is a query against reality.
 */

import { query, transaction } from '../db.js';

/** Days to act. Published, so it is a commitment rather than an aspiration. */
export const SLA_DAYS = 5;

/**
 * Record a request. Does not act on it — acting is a separate, logged step, so
 * an operator can see what basis they had.
 *
 * @param {Object} req
 * @returns {Promise<number>} request id
 */
export async function intake(req) {
  const res = await query(
    `INSERT INTO oc_removal_request
       (business_id, platform_key, booking_url, business_name,
        requester_name, requester_email, requester_role, channel, message,
        verify_method, verify_note, status, requested_at, due_by)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'new', NOW(), DATE_ADD(NOW(), INTERVAL ? DAY))`,
    [
      req.businessId ?? null, req.platformKey ?? null, req.bookingUrl ?? null,
      req.businessName ?? null, req.requesterName ?? null, req.requesterEmail ?? null,
      req.requesterRole ?? null, req.channel ?? 'form', req.message ?? null,
      req.verifyMethod ?? 'none', req.verifyNote ?? null,
      SLA_DAYS,
    ],
  );
  return res.insertId;
}

/**
 * Act on a request: suppress the business, purge its observations, and add the
 * listing to the permanent do-not-scan list.
 *
 * One transaction. A half-applied removal — say, the do-not-scan row written
 * but the grids left behind — is the worst outcome available: it looks handled
 * on the operator's screen while the data is still queryable.
 *
 * @param {number} requestId
 * @param {number|null} actionedBy
 * @returns {Promise<{ok: boolean, purged?: object, error?: string}>}
 */
export async function action(requestId, actionedBy = null) {
  return transaction(async (conn) => {
    const [[req]] = await conn.query('SELECT * FROM oc_removal_request WHERE id = ?', [requestId]);
    if (!req) return { ok: false, error: 'no such request' };
    if (req.status === 'actioned') return { ok: true, purged: { alreadyDone: true } };

    // Match the request to a business if intake could not.
    let businessId = req.business_id;
    if (!businessId && req.booking_url) {
      const [[b]] = await conn.query(
        'SELECT id FROM oc_business WHERE booking_url = ? LIMIT 1', [req.booking_url],
      );
      if (b) businessId = b.id;
    }
    if (!businessId) {
      await conn.query(
        `UPDATE oc_removal_request
            SET status = 'rejected', actioned_at = NOW(), actioned_by = ?,
                outcome_note = 'Could not match the request to a scanned business.'
          WHERE id = ?`,
        [actionedBy, requestId],
      );
      return { ok: false, error: 'unmatched' };
    }

    const [[biz]] = await conn.query(
      `SELECT b.id, b.platform_business_key, b.booking_url, p.platform_key
         FROM oc_business b JOIN oc_platform p ON p.id = b.platform_id
        WHERE b.id = ?`,
      [businessId],
    );

    // Tombstone, not delete. The row survives so discovery recognises the shop
    // and skips it rather than re-adding it as new.
    await conn.query(
      `UPDATE oc_business
          SET status = 'suppressed', suppressed_at = NOW(),
              suppression_reason = 'owner removal request',
              latest_vacancy_bp = NULL, latest_open_slots = NULL,
              latest_bookable_slots = NULL, latest_empty_chair_cents = NULL,
              latest_empty_chair_hi_cents = NULL, latest_days_to_next = NULL,
              latest_bucket = NULL, latest_confidence = NULL
        WHERE id = ?`,
      [businessId],
    );

    // Purge the observations. Leaving these would keep the numbers queryable
    // and reachable through a cached view.
    const purged = {};
    for (const [table, label] of [
      ['oc_scan_snapshot', 'snapshots'],
      ['oc_vacancy_daily', 'daily'],
      ['oc_vacancy_weekly', 'weekly'],
      ['oc_deadzone_cell', 'deadzone'],
      ['oc_scan_queue', 'queue'],
    ]) {
      const [r] = await conn.query(`DELETE FROM ${table} WHERE business_id = ?`, [businessId]);
      purged[label] = r.affectedRows;
    }

    // Watchlist entries go too: a consultant must not keep a suppressed
    // business on their list where it would show a frozen last-known figure.
    const [pr] = await conn.query('DELETE FROM oc_prospect WHERE business_id = ?', [businessId]);
    purged.prospects = pr.affectedRows;

    // Keyed by the PLATFORM LISTING, so it still matches when discovery meets
    // the same shop again under a new internal id.
    await conn.query(
      `INSERT INTO oc_do_not_scan
         (platform_key, platform_business_key, booking_url, reason, removal_request_id, note)
       VALUES (?, ?, ?, 'owner_request', ?, 'honoured removal request')
       ON DUPLICATE KEY UPDATE removal_request_id = VALUES(removal_request_id)`,
      [biz.platform_key, biz.platform_business_key, biz.booking_url, requestId],
    );

    await conn.query(
      `UPDATE oc_removal_request
          SET status = 'actioned', business_id = ?, actioned_at = NOW(), actioned_by = ?,
              outcome_note = 'Suppressed, observations purged, listing added to do-not-scan.'
        WHERE id = ?`,
      [businessId, actionedBy, requestId],
    );

    return { ok: true, purged };
  });
}

/**
 * Is this listing permanently off limits? Called by discovery BEFORE a business
 * is created — which is the only point at which checking it prevents the work
 * rather than merely hiding it.
 *
 * @param {string} platformKey
 * @param {string} platformBusinessKey
 * @returns {Promise<boolean>}
 */
export async function isDoNotScan(platformKey, platformBusinessKey) {
  const rows = await query(
    'SELECT id FROM oc_do_not_scan WHERE platform_key = ? AND platform_business_key = ? LIMIT 1',
    [platformKey, platformBusinessKey],
  );
  return rows.length > 0;
}

/**
 * The operator queue: what is open, and what is overdue.
 *
 * @returns {Promise<{open: array, overdue: number}>}
 */
export async function queue() {
  const open = await query(
    `SELECT *, (due_by < NOW()) AS overdue
       FROM oc_removal_request
      WHERE status IN ('new', 'verifying')
      ORDER BY due_by ASC`,
  );
  return { open, overdue: open.filter((r) => Number(r.overdue) === 1).length };
}

export default { SLA_DAYS, intake, action, isDoNotScan, queue };
