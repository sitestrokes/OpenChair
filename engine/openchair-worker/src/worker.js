/**
 * @file src/worker.js
 * The Radar worker loop: claim → read → validate → normalise → report.
 *
 *   node src/worker.js          run until SIGINT/SIGTERM
 *   node src/worker.js --once   one claim cycle, then exit (tests, the end-to-end run)
 *
 * ─────────────────────────────────────────────────────────────────────────────
 * WHAT THIS PROCESS IS ALLOWED TO TALK TO
 * ─────────────────────────────────────────────────────────────────────────────
 *   - openchair-service, to claim work and report it (serviceClient.js);
 *   - the reader chosen in readers.js — fixtures by default, the egress broker
 *     only with OC_READ_MODE=broker AND OC_ALLOW_LIVE=1 AND a 'live' rollout.
 * Adapters get the reader and nothing else. The worker does not touch the
 * database: the service owns every write, so a compromised crawl box cannot
 * rewrite a single score.
 *
 * ─────────────────────────────────────────────────────────────────────────────
 * ONE BAD ITEM NEVER STOPS THE BATCH
 * ─────────────────────────────────────────────────────────────────────────────
 * Each item is processed inside its own try/catch and ALWAYS reported — a
 * crash is reported as status 'error' so the service backs the item off rather
 * than leaving it locked until the reaper finds it.
 */

import { fileURLToPath } from 'node:url';
import config from './config.js';
import { loadAdapters, adapterFor } from './registry/index.js';
import { validateResult } from './registry/contract.js';
import { normalise } from './normalise/chairMinutes.js';
import { buildSnapshot } from './normalise/snapshot.js';
import { selectReader, readerForItem } from './lib/readers.js';
import { createServiceClient } from './lib/serviceClient.js';
import { todayIn } from './lib/localDate.js';

/** The service refuses bodies over 256 KB; stay clear of the edge. */
export const PAYLOAD_LIMIT = 250 * 1024;

/**
 * Refuse to start in a configuration nobody chose. Throws; the caller exits.
 *
 * @param {typeof config} cfg
 */
export function assertSafeStartup(cfg) {
  if (cfg.env === 'production' && !cfg.service.token) {
    throw Object.assign(new Error('NODE_ENV=production requires OC_WORKER_CALLBACK_TOKEN.'), { code: 'NO_WORKER_TOKEN' });
  }
  // selectReader() throws for broker mode without OC_ALLOW_LIVE=1.
  return selectReader(cfg.read);
}

/**
 * Run one claimed item and build its completion payload (docs/70 §3.2).
 * Never throws.
 *
 * @param {any} item   a claimed oc_scan_queue row (see openchair-service claim())
 * @param {{adapters: Map<string, any>, reader: {mode: string, read: Function}, horizonDays?: number}} deps
 * @returns {Promise<object>}
 */
export async function processItem(item, deps) {
  const started = Date.now();
  let bytes = 0;
  const base = {
    queueId: item.id,
    businessId: item.business_id,
    platformId: item.platform_id,
    observedOn: todayIn(item.timezone),
  };

  /** @type {any} */
  let result;
  let tier = 1;
  try {
    const found = adapterFor(deps.adapters, item.platform_key);
    if ('error' in found) {
      result = found.error;
    } else {
      const adapter = found.adapter;
      tier = Number(adapter.meta.tier ?? 1);
      const read = readerForItem(deps.reader, item, (n) => { bytes += n; });
      if (!read) {
        // Broker mode, platform not 'live'. The adapter never runs, so no
        // request is made — the switch is enforced before the read, not after.
        result = {
          status: 'error', capability: adapter.meta.capability, adapterVersion: adapter.meta.version,
          errorCode: 'ROLLOUT_NOT_LIVE',
          errorNote: `Platform rollout is '${item.rollout}'; live reads only run for 'live' platforms.`,
        };
      } else {
        const raw = await adapter.read({
          bookingUrl: item.booking_url,
          timezone: item.timezone,
          horizonDays: Number(item.grid_horizon_days) || deps.horizonDays || 14,
          read,
        });
        const v = validateResult(raw, { expectCapability: adapter.meta.capability });
        result = v.ok ? v.result : {
          status: 'error',
          capability: adapter.meta.capability,
          adapterVersion: adapter.meta.version,
          errorCode: v.code,
          errorNote: v.reason,
        };
      }
    }
  } catch (err) {
    result = {
      status: 'error', capability: 'C1_SLOT_GRID', adapterVersion: '0.0.0',
      errorCode: 'WORKER_THREW', errorNote: String(err?.message ?? err),
    };
  }

  const payload = {
    ...base,
    status: result.status,
    adapterVersion: result.adapterVersion ?? null,
    tier,
    bytes,
    costMicros: 0,                       // fixture reads cost nothing; the broker meters its own
    durationMs: Date.now() - started,
    errorCode: result.errorCode ?? null,
    error: result.errorNote ? String(result.errorNote).slice(0, 255) : null,
    capability: result.capability ?? null,
    horizonDays: Number.isInteger(result.horizonDays) ? result.horizonDays : null,
    slotMinutes: Number.isInteger(result.slotMinutes) ? result.slotMinutes : null,
  };

  if (result.status === 'ok' || result.status === 'partial') {
    const g = normalise(result);
    const { snapshot, staff } = buildSnapshot(result);
    Object.assign(payload, {
      grid: {
        openMinutes: g.openMinutes,
        envelopeMinutes: g.envelopeMinutes,
        vacancyBp: g.vacancyBp,
        staffCount: g.staffCount,
        dayCount: g.dayCount,
        daysToNext: g.daysToNext,
        byDay: g.byDay,
      },
      snapshot,
      staff,
      services: (result.services ?? []).map((s) => ({
        key: s.key, name: s.name, durationMin: s.durationMin ?? null, priceCents: s.priceCents,
      })),
    });

    if (Buffer.byteLength(JSON.stringify(payload)) > PAYLOAD_LIMIT) {
      // Refuse rather than trim: a silently truncated grid is a wrong grid.
      for (const k of ['grid', 'snapshot', 'staff', 'services']) delete payload[k];
      Object.assign(payload, {
        status: 'error', errorCode: 'PAYLOAD_TOO_LARGE',
        error: `Completion payload exceeds ${PAYLOAD_LIMIT} bytes; the read is reported as failed rather than truncated.`,
      });
    }
  }
  return payload;
}

/**
 * Run `fn` over `items` with at most `n` in flight.
 *
 * @template T, R
 * @param {T[]} items
 * @param {number} n
 * @param {(t: T) => Promise<R>} fn
 * @returns {Promise<R[]>}
 */
async function pool(items, n, fn) {
  const out = new Array(items.length);
  let next = 0;
  const lane = async () => {
    while (next < items.length) {
      const i = next++;
      out[i] = await fn(items[i]);
    }
  };
  await Promise.all(Array.from({ length: Math.max(1, Math.min(n, items.length)) }, lane));
  return out;
}

/**
 * One claim cycle: claim a batch, process it, report every item.
 *
 * @param {{client: ReturnType<typeof createServiceClient>, adapters: Map<string, any>, reader: any,
 *          workerId: string, batchSize: number, concurrency: number, horizonDays?: number,
 *          log?: (s: string) => void}} deps
 * @returns {Promise<{claimed: number, reported: number, failed: number}>}
 */
export async function runOnce(deps) {
  const log = deps.log ?? (() => {});
  const work = await deps.client.claim(deps.workerId, deps.batchSize);
  let reported = 0;
  let failed = 0;

  await pool(work, deps.concurrency, async (item) => {
    const payload = await processItem(item, deps);
    try {
      await deps.client.complete(payload);
      reported++;
      log(`[worker] business ${item.business_id} ${item.platform_key}: ${payload.status}${payload.errorCode ? ` (${payload.errorCode})` : ''}`);
    } catch (err) {
      // The service did not take the report. The lock times out and the
      // reaper re-queues the item; nothing else to do from here.
      failed++;
      log(`[worker] report for business ${item.business_id} failed: ${err?.message}`);
    }
  });

  return { claimed: work.length, reported, failed };
}

/** Entry point. */
async function main() {
  const once = process.argv.includes('--once');
  const log = (s) => process.stdout.write(`${s}\n`);

  let reader;
  try {
    reader = assertSafeStartup(config);
  } catch (err) {
    process.stderr.write(`[worker] refusing to start: ${err.message}\n`);
    process.exit(2);
  }

  const adapters = await loadAdapters();
  const deps = {
    client: createServiceClient(config.service),
    adapters,
    reader,
    workerId: config.workerId,
    batchSize: config.batchSize,
    concurrency: config.concurrency,
    horizonDays: config.horizonDays,
    log,
  };
  log(`[worker] ${config.workerId} up — read mode ${reader.mode}, adapters: ${[...adapters.keys()].join(', ')}`);

  let stopping = false;
  const stop = () => { stopping = true; log('[worker] stopping after this cycle'); };
  process.on('SIGINT', stop);
  process.on('SIGTERM', stop);

  do {
    let res = { claimed: 0 };
    try {
      res = await runOnce(deps);
      if (res.claimed) log(`[worker] cycle: ${res.claimed} claimed, ${res.reported} reported, ${res.failed} unreported`);
    } catch (err) {
      log(`[worker] claim failed: ${err?.message}`);
    }
    if (once || stopping) break;
    if (!res.claimed) await new Promise((r) => setTimeout(r, config.idleSleepMs));
  } while (!stopping);
}

if (process.argv[1] && fileURLToPath(import.meta.url) === process.argv[1]) {
  main().catch((err) => {
    process.stderr.write(`[worker] fatal: ${err?.stack ?? err}\n`);
    process.exit(1);
  });
}

export default { processItem, runOnce, assertSafeStartup, PAYLOAD_LIMIT };
