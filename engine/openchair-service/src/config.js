/**
 * @file src/config.js
 * Central configuration for openchair-service.
 *
 * House rule inherited from bridge-service: import the named `config` object and
 * never read `process.env` anywhere else. One reader means one place to audit
 * and one place to change.
 */

import { fileURLToPath } from 'node:url';

try {
  process.loadEnvFile(fileURLToPath(new URL('../.env', import.meta.url)));
} catch { /* no .env — rely on the process environment */ }

/** @param {string} k @param {string} d @returns {string} */
const str = (k, d) => (process.env[k] === undefined || process.env[k] === '' ? d : process.env[k]);
/** @param {string} k @param {number} d @returns {number} */
const int = (k, d) => {
  const v = process.env[k];
  if (v === undefined || v === '') return d;
  const n = Number.parseInt(v, 10);
  return Number.isNaN(n) ? d : n;
};

export const config = {
  port: int('OC_PORT', 4100),

  db: {
    host: str('DB_HOST', '127.0.0.1'),
    port: int('DB_PORT', 3306),
    database: str('DB_NAME', 'db_openchair'),
    user: str('DB_USER', 'root'),
    password: str('DB_PASS', ''),
    connectionLimit: int('DB_POOL', 8),
  },

  security: {
    /**
     * Shared secret for the CI3 → service hop, sent as X-Internal-Token. Empty
     * in dev disables the gate, matching the host engine's behaviour so a local
     * checkout runs with no configuration.
     */
    internalToken: str('OC_INTERNAL_TOKEN', ''),
    /** Secret the worker fleet presents when reporting a completed scan. */
    workerToken: str('OC_WORKER_CALLBACK_TOKEN', ''),
  },

  scan: {
    /** One observation per business per day (FR-3.1). */
    intervalHours: int('OC_SCAN_INTERVAL_HOURS', 24),
    /** A claim older than this is assumed dead and swept back to 'due'. */
    lockTtlMinutes: int('OC_SCAN_LOCK_TTL_MIN', 20),
    /** Consecutive failures before a business is parked as unreachable. */
    maxAttempts: int('OC_SCAN_MAX_ATTEMPTS', 3),
  },

  /**
   * Below this confidence the product shows no number (05-prd.md §8 rule 3).
   * Must stay in step with the CI3 side's oc_confidence_floor: two halves of one
   * product disagreeing about when a figure is publishable is worse than either
   * threshold on its own.
   */
  confidenceFloor: int('OC_CONFIDENCE_FLOOR', 45),

  /**
   * Budget, not a measurement — the circuit trips on observed cost_micros.
   * $0.02/scan is a defensible steady state; an un-engineered day-one build runs
   * 4-12x that (20-architecture §7.4), which is why this is monitored rather
   * than assumed.
   */
  costBudgetMicros: int('OC_COST_BUDGET_MICROS', 20000),
};

export default config;
