/**
 * @file src/lib/readers.js
 * Which reader an adapter gets — and the two switches that keep it off the
 * internet until the founder says otherwise (docs/70-radar-build.md §4).
 *
 *   OC_READ_MODE=fixture  (default) pages come from fixtures/. No network.
 *   OC_READ_MODE=broker   reads go through openchair-egress to the platform.
 *                         Refused at BOOT unless OC_ALLOW_LIVE=1, and refused
 *                         per ITEM unless that platform's rollout is 'live'.
 *
 * Either switch alone is enough to stop a live read. Both exist because the
 * failure they guard against — a deploy that quietly starts crawling real
 * booking pages before the spike, the legal review and the go/no-go — is not
 * one anybody gets to undo afterwards.
 */

import { createFixtureReader } from './fixtureReader.js';
import { read as brokerRead } from './egressClient.js';

/**
 * Choose the reader for this process. Throws on an unsafe or unknown setting:
 * the worker must fail to start rather than start in a mode nobody chose.
 *
 * @param {{mode: string, allowLive: boolean, fixturesDir: string}} readCfg
 * @returns {{mode: 'fixture'|'broker', read: Function}}
 */
export function selectReader(readCfg) {
  const mode = String(readCfg?.mode ?? 'fixture');
  if (mode === 'fixture') {
    return createFixtureReader({ dir: readCfg.fixturesDir });
  }
  if (mode === 'broker') {
    if (readCfg.allowLive !== true) {
      throw Object.assign(
        new Error('OC_READ_MODE=broker reads real booking pages and requires OC_ALLOW_LIVE=1. Live crawling is off by founder decision until the spike go/no-go.'),
        { code: 'LIVE_NOT_ALLOWED' },
      );
    }
    return { mode: 'broker', read: brokerRead };
  }
  throw Object.assign(new Error(`Unknown OC_READ_MODE '${mode}' (fixture | broker).`), { code: 'BAD_READ_MODE' });
}

/**
 * The read function for ONE claimed item.
 *
 * In broker mode a platform that is not rolled out 'live' (research, shadow,
 * frozen by the canary) gets a reader that refuses — so a shadow adapter cannot
 * reach a real page even with both switches on. Returns null in that case and
 * the worker reports the item as an error without running the adapter.
 *
 * @param {{mode: string, read: Function}} reader
 * @param {{rollout?: string, timezone?: string}} item
 * @param {(n: number) => void} [onBytes] meter, for the run's bytes column
 * @returns {Function|null}
 */
export function readerForItem(reader, item, onBytes = () => {}) {
  if (reader.mode === 'broker' && item?.rollout !== 'live') return null;
  return async (url, opts = {}) => {
    const out = await reader.read(url, { ...opts, timezone: item?.timezone });
    onBytes(Number(out?.bytes ?? 0));
    return out;
  };
}

export default { selectReader, readerForItem };
