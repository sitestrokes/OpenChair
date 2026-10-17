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
   * Empty-Chair Revenue is always a RANGE (rule 4). These are the sell-through
   * bounds applied to open chair-minutes. Must match the CI3 side's
   * oc_sellthrough_low/high — the two halves quoting different money for the
   * same business is the single worst bug this product could ship.
   */
  sellThroughLow: Number(str('OC_SELLTHROUGH_LOW', '0.15')),
  sellThroughHigh: Number(str('OC_SELLTHROUGH_HIGH', '0.35')),


  /**
   * Budget, not a measurement — the circuit trips on observed cost_micros.
   * $0.02/scan is a defensible steady state; an un-engineered day-one build runs
   * 4-12x that (20-architecture §7.4), which is why this is monitored rather
   * than assumed.
   */
  costBudgetMicros: int('OC_COST_BUDGET_MICROS', 20000),

  /** Retention, in days (30-data-model.md §7). */
  retention: {
    /** oc_scan_snapshot.purge_after = captured_on + this. docs/70 §3.3. */
    snapshotDays: int('OC_SNAPSHOT_RETENTION_DAYS', 14),
    /** oc_staff.display_name is personal data; it is NULLed after this. */
    staffNameDays: int('OC_STAFF_NAME_RETENTION_DAYS', 30),
    /** Rows deleted per purge pass, so a backlog never holds a long lock. */
    purgeBatch: int('OC_PURGE_BATCH', 5000),
  },

  discovery: {
    /** Candidates processed per discovery pass. */
    batch: int('OC_DISCOVERY_BATCH', 200),
    /** A listing we could not place is re-probed after this many days. */
    recheckDays: int('OC_DISCOVERY_RECHECK_DAYS', 90),
  },

  jobs: {
    /** In-process scheduler: off unless OC_JOBS=1 (cron + run.js is the default). */
    enabled: str('OC_JOBS', '0') === '1',
    /** How often the light jobs (discover, enqueue-due, reap) run in-process. */
    tickMinutes: int('OC_JOBS_TICK_MIN', 10),
  },

  /** production ⇒ tokens are mandatory (see assertProductionSecrets below). */
  env: str('NODE_ENV', 'development'),
  requireTokens: str('OC_REQUIRE_TOKENS', '0') === '1',
};

/**
 * Fail closed in production.
 *
 * In development an empty token disables the gate, matching the host engine's
 * BRIDGE_INTERNAL_TOKEN behaviour — convenient on a laptop, and fatal on a
 * server: anyone who can reach :4100 could then report a fabricated scan for any
 * business, and the Meter would publish it. So in production (or when
 * OC_REQUIRE_TOKENS=1) a missing token is a refusal to start, not a warning
 * somebody has to notice in a log. docs/70-radar-build.md §4, §6.
 *
 * Runs at import, so `node src/server.js` and every job exit non-zero before
 * opening a socket or a database connection.
 */
export function assertProductionSecrets(cfg = config) {
  const strict = cfg.env === 'production' || cfg.requireTokens;
  if (!strict) return;
  const missing = [];
  if (!cfg.security.internalToken) missing.push('OC_INTERNAL_TOKEN');
  if (!cfg.security.workerToken) missing.push('OC_WORKER_CALLBACK_TOKEN');
  if (missing.length) {
    throw new Error(`openchair-service refuses to start: ${missing.join(' and ')} must be set when NODE_ENV=production.`);
  }
  for (const [k, v] of [['OC_INTERNAL_TOKEN', cfg.security.internalToken], ['OC_WORKER_CALLBACK_TOKEN', cfg.security.workerToken]]) {
    if (String(v).length < 24) {
      throw new Error(`openchair-service refuses to start: ${k} is shorter than 24 characters.`);
    }
  }
}

assertProductionSecrets(config);

export default config;
