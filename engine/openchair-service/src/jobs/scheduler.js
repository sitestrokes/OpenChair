/**
 * @file src/jobs/scheduler.js
 * An optional in-process scheduler, for a single-box install without cron.
 *
 * Off by default (OC_JOBS=1 turns it on). Cron + run.js is the production
 * arrangement: it survives a service restart mid-night and keeps the heavy
 * nightly work out of the request-serving process. This exists for the light,
 * frequent jobs — discovery and queue upkeep — so a pasted URL becomes a queued
 * business within minutes on a dev box or a small install.
 *
 * Ticks never overlap: a slow pass delays the next one rather than stacking.
 */
import config from '../config.js';
import { LIGHT, runJob } from './index.js';

let timer = null;
let running = false;

/** @returns {() => void} stop */
export function startScheduler() {
  if (timer) return stopScheduler;
  const every = Math.max(1, config.jobs.tickMinutes) * 60_000;
  const tick = async () => {
    if (running) return;
    running = true;
    try {
      for (const j of LIGHT) await runJob(j);
    } finally {
      running = false;
    }
  };
  timer = setInterval(tick, every);
  timer.unref?.();
  setTimeout(tick, 5_000).unref?.();
  process.stdout.write(`[jobs] in-process scheduler on: ${LIGHT.join(', ')} every ${config.jobs.tickMinutes}m\n`);
  return stopScheduler;
}

export function stopScheduler() {
  if (timer) clearInterval(timer);
  timer = null;
}

export default { startScheduler, stopScheduler };
