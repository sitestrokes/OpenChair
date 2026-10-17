/**
 * @file src/jobs/run.js
 * Cron entry point: `node src/jobs/run.js <job>|all`.
 *
 *   0  2 * * *  node src/jobs/run.js all        # the nightly sequence
 *   *\/10 * * * *  node src/jobs/run.js discover  # new candidates, promptly
 *
 * Exits 0 when every job it ran succeeded, 1 otherwise, so cron's own mail and
 * any monitor watching exit codes see a failure without parsing logs.
 */
import { JOBS, ALL, runJob } from './index.js';
import { closePool } from '../db.js';

const arg = process.argv[2] ?? '';
const names = arg === 'all' ? ALL : [arg];

if (!arg || names.some((n) => !JOBS[n])) {
  process.stderr.write(`usage: node src/jobs/run.js <${Object.keys(JOBS).join('|')}|all>\n`);
  process.exit(2);
}

let failed = 0;
for (const n of names) {
  const r = await runJob(n);
  if (!r.ok) failed++;
}
await closePool();
process.exit(failed ? 1 : 0);
