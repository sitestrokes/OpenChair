/**
 * @file src/registry/index.js
 * Which adapters exist, and the gate every one of them passes to get loaded.
 *
 * An adapter is a file in src/adapters/ named after its platform_key
 * ('booksy.js' ↔ oc_platform.platform_key 'booksy'). Files starting with "_"
 * are examples and are never loaded — that is how _reference.js stays out of
 * production while remaining runnable documentation.
 *
 * Every module is checked with validateAdapterModule() at load time, not at
 * first use: an adapter that grew a write verb must stop the worker booting,
 * not fail quietly on the first business it is handed at 3am.
 */

import { readdir } from 'node:fs/promises';
import { fileURLToPath, pathToFileURL } from 'node:url';
import { join, basename } from 'node:path';
import { validateAdapterModule } from './contract.js';

const DEFAULT_DIR = fileURLToPath(new URL('../adapters/', import.meta.url));

/**
 * Load and validate every adapter.
 *
 * @param {string} [dir] adapters directory (tests pass a temporary one)
 * @returns {Promise<Map<string, {meta: object, read: Function}>>} keyed by platformKey
 * @throws {Error} when a module breaks the contract — loudly, at boot
 */
export async function loadAdapters(dir = DEFAULT_DIR) {
  const files = (await readdir(dir)).filter((f) => f.endsWith('.js') && !f.startsWith('_')).sort();
  /** @type {Map<string, any>} */
  const out = new Map();

  for (const f of files) {
    const mod = await import(pathToFileURL(join(dir, f)).href);
    const check = validateAdapterModule(mod);
    if (!check.ok) {
      throw new Error(`adapter ${f} refused: ${check.reason}`);
    }
    const key = mod.meta.platformKey;
    if (key !== basename(f, '.js')) {
      // The filename IS the join key to oc_platform. A mismatch means a queue
      // item for 'square_appointments' would find nothing, or the wrong thing.
      throw new Error(`adapter ${f} declares platformKey '${key}'; the file must be named ${key}.js`);
    }
    out.set(key, mod);
  }
  return out;
}

/**
 * The adapter for a platform, or a ready-made error result when there is none.
 * Not a throw: one unknown platform in a batch must not stop the other nine.
 *
 * @param {Map<string, any>} adapters
 * @param {string} platformKey
 * @returns {{adapter: any} | {error: object}}
 */
export function adapterFor(adapters, platformKey) {
  const adapter = adapters.get(String(platformKey ?? ''));
  if (adapter) return { adapter };
  return {
    error: {
      status: 'error',
      capability: 'C1_SLOT_GRID',
      adapterVersion: '0.0.0',
      errorCode: 'UNKNOWN_PLATFORM',
      errorNote: `No adapter for platform '${platformKey}'. Only adapters in src/adapters/ can be scheduled.`,
    },
  };
}

export default { loadAdapters, adapterFor };
