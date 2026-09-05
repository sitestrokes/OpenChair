/**
 * @file src/db.js
 * The MariaDB pool, shared by every model.
 *
 * Note what this connects to: the SAME schema the CI3 app uses. The oc_ tables
 * are an island in it — they link out to `users` and `clients` by loose INT and
 * never inward, and there are no cross-schema foreign keys (30-data-model.md
 * §7). So this service can be migrated, restarted or replaced without the PHP
 * app noticing, which is the whole reason it is a separate process.
 */

import mysql from 'mysql2/promise';
import config from './config.js';

/** @type {import('mysql2/promise').Pool|null} */
let pool = null;

/** @returns {import('mysql2/promise').Pool} */
export function getPool() {
  if (!pool) {
    pool = mysql.createPool({
      host: config.db.host,
      port: config.db.port,
      user: config.db.user,
      password: config.db.password,
      database: config.db.database,
      waitForConnections: true,
      connectionLimit: config.db.connectionLimit,
      // The Meter deals in basis points and cents — integers, deliberately, so
      // the 41%→68% proof can never float-drift. Keeping DECIMAL as a string
      // stops mysql2 turning a lat/lng into a lossy double on the way past.
      decimalNumbers: false,
      dateStrings: true,
    });
  }
  return pool;
}

/**
 * Run a query.
 * @param {string} sql
 * @param {unknown[]} [params]
 * @returns {Promise<any>}
 */
export async function query(sql, params = []) {
  const [rows] = await getPool().execute(sql, params);
  return rows;
}

/**
 * Run a callback inside a transaction, rolling back on any throw.
 * @template T
 * @param {(conn: import('mysql2/promise').PoolConnection) => Promise<T>} fn
 * @returns {Promise<T>}
 */
export async function transaction(fn) {
  const conn = await getPool().getConnection();
  try {
    await conn.beginTransaction();
    const out = await fn(conn);
    await conn.commit();
    return out;
  } catch (err) {
    try { await conn.rollback(); } catch { /* connection already gone */ }
    throw err;
  } finally {
    conn.release();
  }
}

/**
 * Liveness probe for /health. Returns false rather than throwing: a database
 * that is down should make the service report unhealthy, not crash it.
 * @returns {Promise<boolean>}
 */
export async function ping() {
  try {
    await query('SELECT 1');
    return true;
  } catch {
    return false;
  }
}

export async function closePool() {
  if (pool) { await pool.end(); pool = null; }
}

export default { getPool, query, transaction, ping, closePool };
