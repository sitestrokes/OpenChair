/**
 * @file src/config.js
 * Central configuration for openchair-egress.
 *
 * Same house rule as bridge-service: import the named `config` object and never
 * read `process.env` anywhere else. That matters more here than usual — this
 * process holds every proxy credential in the system, and a second place that
 * reads the environment is a second place to leak one.
 */

import { fileURLToPath } from 'node:url';

try {
  process.loadEnvFile(fileURLToPath(new URL('../.env', import.meta.url)));
} catch { /* no .env — rely on the process environment */ }

/** @param {string} k @param {string} d @returns {string} */
const str = (k, d) => (process.env[k] === undefined || process.env[k] === '' ? d : process.env[k]);
/** @param {string} k @param {number} d @returns {number} */
const int = (k, d) => {
  const v = process.env[k];
  if (v === undefined || v === '') return d;
  const n = Number.parseInt(v, 10);
  return Number.isNaN(n) ? d : n;
};

export const config = {
  port: int('OC_EGRESS_PORT', 4110),

  /** Shared secret the worker presents. Empty in dev = gate disabled. */
  token: str('OC_EGRESS_TOKEN', ''),

  /**
   * The tier ceiling. Tier 3 — stealth browsers, residential rotation — is out
   * of scope by decision, not configuration: it is simultaneously the largest
   * legal exposure (DMCA §1201, and post-block revocation of authorisation) and
   * the largest COGS line. Raising this number does not enable tier 3; there is
   * no tier-3 code path to enable, and the database refuses a tier-3 scan row.
   */
  maxTier: 2,

  proxies: {
    datacenter: str('OC_PROXY_DATACENTER_URL', ''),
    isp: str('OC_PROXY_ISP_URL', ''),
  },

  /**
   * Honest identification (50-risk §A6.1). We say who we are and where to
   * complain. A crawler that hides its identity has a much worse story to tell
   * later, and the contact URL is what turns a complaint into an email rather
   * than a lawsuit.
   */
  userAgent: str(
    'OC_USER_AGENT',
    'OpenChairBot/0.1 (+https://openchair.example/bot; read-only availability reader)',
  ),
  contactUrl: str('OC_CONTACT_URL', ''),

  audit: {
    dir: str('OC_AUDIT_DIR', './audit'),
    rotateMb: int('OC_AUDIT_ROTATE_MB', 64),
  },

  /** Politeness + blast-radius limits. */
  limits: {
    timeoutMs: int('OC_FETCH_TIMEOUT_MS', 20000),
    maxBytes: int('OC_FETCH_MAX_BYTES', 5 * 1024 * 1024),
    maxRedirects: int('OC_FETCH_MAX_REDIRECTS', 5),
  },
};

export default config;
