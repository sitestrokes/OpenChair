/**
 * @file src/lib/egressClient.js
 * The worker's ONLY route to the internet.
 *
 * This module exports one function, `read(url)`. There is no post(), no
 * request(), no method parameter — an adapter given this object cannot express
 * the intention to write, let alone carry it out.
 *
 * That is layer 3 of the read-only guarantee, and it is deliberately redundant
 * with layer 2 (the broker's guard). The broker does not trust this file, and
 * this file does not rely on the broker being correct. Two independent
 * mechanisms, each sufficient on its own, because PRD R4 is rated launch-ending
 * and one mechanism is one bug away from nothing.
 *
 * Adapters MUST NOT import node:http, node:https, or call global fetch. The CI
 * isolation check greps for exactly that.
 */

import config from '../config.js';

/**
 * @typedef {Object} ReadResult
 * @property {number} status  HTTP status from the target
 * @property {string} body
 * @property {number} bytes
 * @property {number} ms
 * @property {string} finalUrl  after any redirects the broker followed and re-checked
 */

/**
 * Ask the broker to read a public page.
 *
 * @param {string} url
 * @param {{tier?: number, headers?: Record<string,string>}} [opts]
 * @returns {Promise<ReadResult>}
 * @throws {Error} when the broker refuses, or the read fails
 */
export async function read(url, opts = {}) {
  const res = await fetch(`${config.egress.url}/fetch`, {
    method: 'POST',                    // the CONTROL hop; what goes out is a GET
    headers: {
      'content-type': 'application/json',
      ...(config.egress.token ? { 'x-egress-token': config.egress.token } : {}),
    },
    body: JSON.stringify({
      url,
      method: 'GET',
      tier: opts.tier ?? 1,
      headers: opts.headers,
    }),
  });

  /** @type {any} */
  const out = await res.json().catch(() => ({}));

  if (!res.ok || !out.ok) {
    const err = new Error(out.error ?? `egress refused (${res.status})`);
    // Carry the refusal code so the worker can distinguish "the broker stopped
    // us" (an adapter bug we must fix) from "the site blocked us" (an operating
    // condition we back off from). Conflating those makes the block-rate metric
    // meaningless.
    err.code = out.code ?? 'EGRESS_ERROR';
    throw err;
  }

  return { status: out.status, body: out.body, bytes: out.bytes, ms: out.ms, finalUrl: out.finalUrl };
}

export default { read };
