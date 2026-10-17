/**
 * @file src/jobs/canary.js
 * The data-quality canary over today's reads. See observability/canary.js.
 * A platform whose night looks wrong is frozen before its numbers publish.
 */
import { runCanary } from '../observability/canary.js';

/** @returns {Promise<{platformsWithFindings: number, frozen: string[]}>} */
export default async function canary() {
  const out = await runCanary();
  return {
    platformsWithFindings: out.length,
    frozen: out.filter((o) => o.frozen).map((o) => o.platformKey),
  };
}
