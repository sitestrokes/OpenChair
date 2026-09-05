/**
 * @file src/security/internalAuth.js
 * The CI3 -> service gate.
 *
 * Mirrors the host engine's X-Internal-Token pattern exactly, including the
 * dev behaviour: an EMPTY configured token disables the gate, so a fresh
 * checkout runs with no configuration. That is a deliberate convenience with a
 * deliberate limit -- the service binds to loopback, so "no gate" still means
 * "same box only".
 */

import { timingSafeEqual } from 'node:crypto';
import config from '../config.js';

/**
 * Constant-time string compare. A plain === on a secret leaks its length and,
 * over enough attempts, its content.
 *
 * @param {string} a
 * @param {string} b
 * @returns {boolean}
 */
function safeEqual(a, b) {
  const ba = Buffer.from(String(a ?? ''), 'utf8');
  const bb = Buffer.from(String(b ?? ''), 'utf8');
  if (ba.length !== bb.length) return false;
  return timingSafeEqual(ba, bb);
}

/**
 * @param {import('node:http').IncomingMessage} req
 * @returns {boolean}
 */
export function requireInternalToken(req) {
  const expected = config.security.internalToken;
  if (!expected) return true; // dev: gate disabled
  return safeEqual(req.headers['x-internal-token'], expected);
}

/**
 * The worker fleet reports completed scans with its own secret, separate from
 * the CI3 one. Two callers, two credentials: a leaked worker token must not
 * also grant whatever the admin surface can do.
 *
 * @param {import('node:http').IncomingMessage} req
 * @returns {boolean}
 */
export function requireWorkerToken(req) {
  const expected = config.security.workerToken;
  if (!expected) return true;
  return safeEqual(req.headers['x-worker-token'], expected);
}

export default { requireInternalToken, requireWorkerToken };
