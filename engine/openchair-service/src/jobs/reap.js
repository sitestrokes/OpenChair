/**
 * @file src/jobs/reap.js
 * Return claims whose worker died. See scan/queue.js reapStaleLocks().
 */
import { reapStaleLocks } from '../scan/queue.js';

/** @returns {Promise<{swept: number}>} */
export default async function reap() {
  return { swept: await reapStaleLocks() };
}
