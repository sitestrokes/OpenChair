/**
 * @file src/config.js
 * Configuration for openchair-worker. Crawl VM only.
 *
 * House rule: import `config`, never read process.env elsewhere.
 */

import { fileURLToPath } from 'node:url';

try {
  process.loadEnvFile(fileURLToPath(new URL('../.env', import.meta.url)));
} catch { /* no .env */ }

const str = (k, d) => (process.env[k] === undefined || process.env[k] === '' ? d : process.env[k]);
const int = (k, d) => {
  const v = process.env[k];
  if (v === undefined || v === '') return d;
  const n = Number.parseInt(v, 10);
  return Number.isNaN(n) ? d : n;
};

export const config = {
  /** Identifies this worker in oc_scan_queue.locked_by. */
  workerId: str('OC_WORKER_ID', `worker-${process.pid}`),

  /** The service this worker claims work from. App box. */
  service: {
    url: str('OC_SERVICE_URL', 'http://127.0.0.1:4100'),
    token: str('OC_WORKER_CALLBACK_TOKEN', ''),
  },

  /**
   * The broker. The ONLY network destination for a read. There is no direct
   * fetch of a booking platform anywhere in this process.
   */
  egress: {
    url: str('OC_EGRESS_URL', 'http://127.0.0.1:4110'),
    token: str('OC_EGRESS_TOKEN', ''),
  },

  /** Which platforms this worker instance handles; empty = all. */
  shard: str('OC_WORKER_SHARD', ''),

  concurrency: int('OC_SCAN_CONCURRENCY', 4),
  maxBrowserContexts: int('OC_MAX_BROWSER_CONTEXTS', 4),
  batchSize: int('OC_BATCH_SIZE', 10),
  idleSleepMs: int('OC_IDLE_SLEEP_MS', 15000),

  /** Circuit breaker on cost. $0.02 = 20000 micros. */
  costCeilingMicros: int('OC_COST_CEILING_MICROS', 20000),
};

export default config;
