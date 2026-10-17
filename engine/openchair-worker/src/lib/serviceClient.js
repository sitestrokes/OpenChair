/**
 * @file src/lib/serviceClient.js
 * The worker's control-plane link to openchair-service: claim work, report it.
 *
 * Two POSTs to ONE host — the service URL from config. This is not a route to
 * the internet and adapters never see it; their only network primitive is the
 * reader they are handed (fixtures, or the broker).
 */

/**
 * @param {{url: string, token: string}} service
 * @param {typeof fetch} [fetchImpl]
 */
export function createServiceClient(service, fetchImpl = fetch) {
  const base = String(service.url).replace(/\/+$/, '');

  async function post(path, body) {
    const res = await fetchImpl(`${base}${path}`, {
      method: 'POST',
      headers: {
        'content-type': 'application/json',
        ...(service.token ? { 'x-worker-token': service.token } : {}),
      },
      body: JSON.stringify(body),
      signal: AbortSignal.timeout(30000),
    });
    const out = await res.json().catch(() => ({}));
    if (!res.ok || out.ok === false) {
      throw Object.assign(new Error(out.error ?? `service ${path} → HTTP ${res.status}`), { code: 'SERVICE_ERROR', status: res.status });
    }
    return out;
  }

  return {
    /** @returns {Promise<any[]>} claimed queue rows */
    async claim(workerId, limit) {
      const out = await post('/v1/scan/claim', { workerId, limit });
      return Array.isArray(out.work) ? out.work : [];
    },
    /** @param {object} payload §3.2 completion payload */
    async complete(payload) {
      return post('/v1/scan/complete', payload);
    },
  };
}

export default { createServiceClient };
