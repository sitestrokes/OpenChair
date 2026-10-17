/**
 * @file src/lib/http.js
 * The two HTTP chores every route repeats: answer with JSON, read a capped
 * JSON body.
 *
 * The cap is the point. Every JSON surface on this service is called by our own
 * worker or by CI3, never by a browser — so a body bigger than the largest
 * legitimate payload (a 14-day snapshot, docs/70 §3.2) is either a bug or an
 * attack, and either way it is refused before it is buffered, not after.
 */

/** Largest legitimate body: the §3.2 completion payload. */
export const MAX_BODY_BYTES = 256 * 1024;

/**
 * @param {import('node:http').ServerResponse} res
 * @param {number} status
 * @param {unknown} payload
 */
export function json(res, status, payload) {
  const body = JSON.stringify(payload);
  res.writeHead(status, {
    'content-type': 'application/json',
    'content-length': Buffer.byteLength(body),
    'cache-control': 'no-store',
    'x-content-type-options': 'nosniff',
  });
  res.end(body);
}

/**
 * Read a JSON body, refusing anything over the cap.
 *
 * @param {import('node:http').IncomingMessage} req
 * @param {number} [limit]
 * @returns {Promise<any>}
 */
export function readJson(req, limit = MAX_BODY_BYTES) {
  return new Promise((resolve, reject) => {
    let size = 0;
    let over = false;
    const chunks = [];
    req.on('data', (c) => {
      if (over) return;            // drain without buffering
      size += c.length;
      if (size > limit) {
        // Stop keeping bytes, but let the caller ANSWER: destroying the socket
        // here turns a clear 413 into a connection reset the worker cannot
        // tell apart from a network fault.
        over = true;
        chunks.length = 0;
        const err = new Error('payload too large');
        err.code = 'PAYLOAD_TOO_LARGE';
        reject(err);
        return;
      }
      chunks.push(c);
    });
    req.on('end', () => {
      if (over) return;
      try { resolve(chunks.length ? JSON.parse(Buffer.concat(chunks).toString('utf8')) : {}); }
      catch {
        const err = new Error('body is not valid JSON');
        err.code = 'BAD_JSON';
        reject(err);
      }
    });
    req.on('error', reject);
  });
}

export default { json, readJson, MAX_BODY_BYTES };
