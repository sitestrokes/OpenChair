/**
 * @file src/guard/readOnly.js
 * The read-only guard. This is the narrowest point in the whole system.
 *
 * ─────────────────────────────────────────────────────────────────────────────
 * WHY THIS FILE EXISTS
 * ─────────────────────────────────────────────────────────────────────────────
 * PRD R4 — "a booking gets created by accident or by a bad actor via the tool" —
 * is the one risk marked launch-ending. Not "serious": launch-ending. One "the
 * tool booked a fake appointment" story kills the product, and no amount of
 * careful adapter code is a defence, because the whole point of a defence is
 * that it holds when the adapter is wrong.
 *
 * So the guarantee is not "adapters only issue GETs". It is: **the only code
 * path to the internet physically cannot issue anything else.** Every request
 * from every worker passes through checkRequest() below. There is no bypass, no
 * flag, and no privileged caller.
 *
 * Spike criterion S7 is *zero* non-GET requests — pass/fail, not a threshold.
 * This module is what makes that a fact about the system rather than a hope
 * about its authors.
 *
 * ─────────────────────────────────────────────────────────────────────────────
 * WHAT IS REFUSED, AND WHY EACH ONE
 * ─────────────────────────────────────────────────────────────────────────────
 *   method       Anything but GET/HEAD. A booking is a POST; so is a hold, a
 *                cancel and a login. Refusing the verb refuses the whole class.
 *   body         A GET with a body is a GET pretending. Some endpoints act on
 *                one; we never send one.
 *   scheme       http/https only. file:, data:, gopher: are not the web.
 *   private net  Loopback, RFC1918, link-local, cloud metadata. A crawl target
 *                that resolves inward is either a mistake or SSRF, and 169.254
 *                .169.254 is how a proxy credential leaves a box.
 *   auth headers Cookie, Authorization, and friends. Rule 9 — never cross the
 *                login line — is the distinction the case law actually turns on
 *                (hiQ v LinkedIn hinges on public vs authenticated access). A
 *                request carrying credentials is on the wrong side of it, and
 *                that is a legal argument, not a preference.
 */

/** Verbs that read. Everything else is a write, or is pretending not to be. */
const ALLOWED_METHODS = new Set(['GET', 'HEAD']);

/** Schemes that are the public web. */
const ALLOWED_PROTOCOLS = new Set(['http:', 'https:']);

/**
 * Headers that carry or establish an identity. Sending any of these means we
 * are no longer reading an anonymous public page.
 */
const FORBIDDEN_HEADERS = new Set([
  'cookie',
  'authorization',
  'proxy-authorization',
  'www-authenticate',
  'x-api-key',
  'x-auth-token',
  'x-access-token',
  'x-csrf-token',
  'x-xsrf-token',
  'set-cookie',
]);

/** @typedef {{ok: true, url: URL} | {ok: false, code: string, reason: string}} GuardResult */

/**
 * True if a hostname is one we must never fetch: loopback, private ranges,
 * link-local (which includes the cloud metadata endpoint), or a bare non-public
 * name.
 *
 * Note the limit honestly: this checks the hostname as written. A public name
 * that RESOLVES to a private address defeats it, so the DNS-level check belongs
 * in the fetch path where resolution happens. This catches the literal case,
 * which is the one that shows up in a bad adapter or a crafted booking_url.
 *
 * @param {string} hostname
 * @returns {boolean}
 */
export function isPrivateHost(hostname) {
  const h = String(hostname || '').toLowerCase().replace(/^\[|\]$/g, '');

  if (h === 'localhost' || h.endsWith('.localhost') || h.endsWith('.local')) return true;
  if (h === '::1' || h === '0.0.0.0' || h === '::') return true;
  // Unique-local (fc00::/7) and IPv6 link-local (fe80::/10).
  if (/^f[cd][0-9a-f]{2}:/.test(h) || /^fe[89ab][0-9a-f]:/.test(h)) return true;

  const m = h.match(/^(\d{1,3})\.(\d{1,3})\.(\d{1,3})\.(\d{1,3})$/);
  if (!m) return false;

  const [a, b] = [Number(m[1]), Number(m[2])];
  if ([a, b, Number(m[3]), Number(m[4])].some((n) => n > 255)) return true; // malformed → refuse

  if (a === 10 || a === 127 || a === 0) return true;
  if (a === 172 && b >= 16 && b <= 31) return true;
  if (a === 192 && b === 168) return true;
  if (a === 169 && b === 254) return true;   // link-local + 169.254.169.254 metadata
  if (a === 100 && b >= 64 && b <= 127) return true; // CGNAT
  return false;
}

/**
 * Decide whether a request may leave this box.
 *
 * Fails closed: anything unrecognised is refused. A guard that lets through what
 * it does not understand is not a guard.
 *
 * @param {{method?: string, url?: string, headers?: Record<string,string>, body?: unknown, tier?: number}} req
 * @param {{maxTier?: number}} [opts]
 * @returns {GuardResult}
 */
export function checkRequest(req, opts = {}) {
  const maxTier = opts.maxTier ?? 2;

  // ── Method ────────────────────────────────────────────────────────────────
  // Default to GET when absent rather than refusing: an omitted method is a
  // read in every HTTP client. An explicitly WRONG method is the failure.
  const method = String(req?.method ?? 'GET').toUpperCase();
  if (!ALLOWED_METHODS.has(method)) {
    return { ok: false, code: 'METHOD_NOT_READ_ONLY', reason: `${method} is not a read. Only GET and HEAD leave this process.` };
  }

  // ── Body ──────────────────────────────────────────────────────────────────
  if (req?.body !== undefined && req.body !== null && req.body !== '') {
    return { ok: false, code: 'BODY_ON_READ', reason: 'A read request carries no body.' };
  }

  // ── URL ───────────────────────────────────────────────────────────────────
  let url;
  try {
    url = new URL(String(req?.url ?? ''));
  } catch {
    return { ok: false, code: 'BAD_URL', reason: 'Unparseable URL.' };
  }

  if (!ALLOWED_PROTOCOLS.has(url.protocol)) {
    return { ok: false, code: 'BAD_SCHEME', reason: `${url.protocol} is not http or https.` };
  }
  if (url.username || url.password) {
    return { ok: false, code: 'URL_CREDENTIALS', reason: 'URL carries inline credentials — that crosses the login line.' };
  }
  if (isPrivateHost(url.hostname)) {
    return { ok: false, code: 'PRIVATE_HOST', reason: `${url.hostname} is loopback, private or link-local.` };
  }

  // ── Headers ───────────────────────────────────────────────────────────────
  for (const name of Object.keys(req?.headers ?? {})) {
    if (FORBIDDEN_HEADERS.has(name.toLowerCase())) {
      return { ok: false, code: 'AUTH_HEADER', reason: `Header '${name}' would authenticate the request. Anonymous public pages only.` };
    }
  }

  // ── Tier ceiling ──────────────────────────────────────────────────────────
  const tier = Number(req?.tier ?? 1);
  if (!Number.isInteger(tier) || tier < 1 || tier > maxTier) {
    return { ok: false, code: 'TIER_CEILING', reason: `Tier ${req?.tier} refused; ceiling is ${maxTier}. Tier 3 is out of scope by decision, not configuration.` };
  }

  return { ok: true, url };
}

export default { checkRequest, isPrivateHost };
