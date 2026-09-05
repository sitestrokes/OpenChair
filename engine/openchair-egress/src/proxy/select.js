/**
 * @file src/proxy/select.js
 * Proxy selection by tier. This module is the only place proxy credentials are
 * read, and they never leave the process.
 *
 * The ladder, and what each rung costs (20-architecture §5.1, §7.2):
 *
 *   tier 1  plain GET, no browser, direct or datacenter    ~$0.0001/scan
 *   tier 2  headless browser + datacenter/ISP proxy        ~$0.0007/scan
 *   tier 3  stealth fingerprinting + residential rotation  ~$0.014/scan
 *
 * Tier 3 IS NOT IMPLEMENTED, and its absence is the design. The brief asked for
 * "residential rotation and patience"; the compliance review rejected it as
 * simultaneously the largest legal exposure in the project and the largest cost
 * line. A platform that cannot be read within tier 2 is a platform we do not
 * ship — which will shrink adapter coverage, and that is the accepted trade.
 *
 * There is nothing here to switch on. The guard refuses tier 3, the database
 * refuses a tier-3 scan row (chk_scan_tier_ceiling), and this function has no
 * branch for it.
 */

import config from '../config.js';

/**
 * The proxy URL for a tier, or null to go direct.
 *
 * @param {number} tier 1 or 2
 * @returns {string|null}
 * @throws {Error} if asked for a tier above the ceiling
 */
export function selectProxy(tier) {
  const t = Number(tier);

  if (t > config.maxTier) {
    // Unreachable through the broker — the guard rejects first. Kept as a hard
    // stop so a future caller that bypasses the guard still cannot get out.
    throw new Error(
      `tier ${t} has no implementation. Tier 3 (stealth / residential rotation) is ` +
      'out of scope by decision; see 50-risk-compliance-qa.md §A1.3.',
    );
  }

  if (t === 2) return config.proxies.isp || config.proxies.datacenter || null;
  return config.proxies.datacenter || null;
}

export default { selectProxy };
