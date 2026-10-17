/**
 * @file src/jobs/discover.js
 * Place new candidates on a platform and queue them. See discovery/run.js.
 */
import { runDiscovery } from '../discovery/run.js';

/** @returns {Promise<object>} */
export default async function discover() {
  return runDiscovery();
}
