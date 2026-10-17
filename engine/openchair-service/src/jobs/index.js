/**
 * @file src/jobs/index.js
 * Every scheduled job, by name. docs/70-radar-build.md §1 R5.
 *
 * Each job is `async () => summary`: it does one bounded pass, returns a plain
 * object describing what it did, and is safe to run twice in a row. That last
 * property is what lets the same job be fired by cron, by the in-process
 * scheduler and by an operator over /v1/jobs without anyone coordinating.
 *
 * Order in ALL is the nightly order: find work, schedule it, clear dead claims,
 * check the night's reads, roll the weeks up, then delete what has aged out.
 */

import discover from './discover.js';
import enqueueDue from './enqueueDue.js';
import reap from './reap.js';
import canary from './canary.js';
import rollup from './rollup.js';
import cohort from './cohort.js';
import purge from './purge.js';

/** @type {Record<string, () => Promise<object>>} */
export const JOBS = {
  discover,
  'enqueue-due': enqueueDue,
  reap,
  canary,
  rollup,
  cohort,
  purge,
};

/** The nightly sequence, for `run.js all`. */
export const ALL = ['discover', 'enqueue-due', 'reap', 'canary', 'rollup', 'cohort', 'purge'];

/** The cheap jobs the in-process scheduler runs every tick. */
export const LIGHT = ['discover', 'enqueue-due', 'reap'];

/**
 * Run one job by name, timing it and logging one line.
 *
 * @param {string} name
 * @returns {Promise<{job: string, ok: boolean, ms: number, summary?: object, error?: string}>}
 */
export async function runJob(name) {
  const fn = JOBS[name];
  if (!fn) return { job: name, ok: false, ms: 0, error: 'unknown job' };
  const t0 = Date.now();
  try {
    const summary = await fn();
    const out = { job: name, ok: true, ms: Date.now() - t0, summary };
    process.stdout.write(`[jobs] ${name} ok ${out.ms}ms ${JSON.stringify(summary)}\n`);
    return out;
  } catch (err) {
    const out = { job: name, ok: false, ms: Date.now() - t0, error: String(err?.message ?? err).slice(0, 255) };
    process.stderr.write(`[jobs] ${name} FAILED ${out.ms}ms ${out.error}\n`);
    return out;
  }
}

export default { JOBS, ALL, LIGHT, runJob };
