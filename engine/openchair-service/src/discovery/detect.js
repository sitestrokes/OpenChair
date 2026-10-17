/**
 * @file src/discovery/detect.js
 * Which booking platform is this URL? Answered from the URL alone.
 *
 * docs/70-radar-build.md §2, §6. Discovery on the app box never fetches: the
 * service makes no outbound request (test/isolation.test.js), and a URL a user
 * pasted is the last thing that should be fetched from the box beside the voice
 * bridge. A signature match on the URL is enough to place a listing on a
 * platform; whether its calendar is READABLE is the worker's job, on the crawl
 * VM, through the read-only broker.
 *
 * Every input is untrusted text from a form. So: https only, a hostname on a
 * known platform domain (exact or a real subdomain — never a suffix match that
 * would accept `evilbooksy.com`), no credentials, no IP literals, no localhost,
 * query and fragment stripped, 512 characters max.
 */

/** Longest URL we accept — the oc_business.booking_url column width. */
export const MAX_URL = 512;

/**
 * @typedef {Object} Detection
 * @property {boolean} ok
 * @property {string}  [platformKey]          oc_platform.platform_key
 * @property {string}  [platformBusinessKey]  the platform's own id for the listing
 * @property {string}  [canonicalUrl]         https, no query/fragment
 * @property {string}  [slugName]             a display name guessed from the URL, or ''
 * @property {string}  [reason]               why it was refused (ok=false)
 * @property {string}  [evidence]             which signature matched
 */

/**
 * Hosts per platform. A host matches when it equals one of these or ends with
 * `.${one of these}`.
 */
const HOSTS = {
  booksy: ['booksy.com'],
  square_appointments: ['book.squareup.com', 'squareup.com', 'square.site'],
  vagaro: ['vagaro.com'],
  fresha: ['fresha.com'],
  glossgenius: ['glossgenius.com'],
  acuity: ['acuityscheduling.com', 'as.me'],
};

/**
 * @param {string} host
 * @param {string} domain
 * @returns {boolean}
 */
function hostIs(host, domain) {
  return host === domain || host.endsWith(`.${domain}`);
}

/**
 * "fade-lab_barber-shop" → "Fade Lab". Words only; never trusted as a real name,
 * just a placeholder until the first scan or the user names it.
 *
 * @param {string} slug
 * @returns {string}
 */
export function nameFromSlug(slug) {
  const first = String(slug ?? '').split('_')[0];
  return first
    .split('-')
    .filter((w) => /^[a-z0-9]+$/i.test(w))
    .slice(0, 6)
    .map((w) => w.charAt(0).toUpperCase() + w.slice(1).toLowerCase())
    .join(' ')
    .slice(0, 120);
}

/**
 * Parse and police a pasted URL.
 *
 * @param {unknown} input
 * @returns {{ok: true, url: URL} | {ok: false, reason: string}}
 */
export function safeUrl(input) {
  const raw = typeof input === 'string' ? input.trim() : '';
  if (!raw) return { ok: false, reason: 'EMPTY' };
  if (raw.length > MAX_URL) return { ok: false, reason: 'TOO_LONG' };
  // Control characters and whitespace inside a URL are how a parser and a
  // browser are made to disagree about where it points.
  if (/[\u0000-\u001f\u007f\s]/.test(raw)) return { ok: false, reason: 'BAD_CHARS' };

  let u;
  try { u = new URL(raw); } catch { return { ok: false, reason: 'NOT_A_URL' }; }

  if (u.protocol !== 'https:') return { ok: false, reason: 'NOT_HTTPS' };
  if (u.username || u.password) return { ok: false, reason: 'CREDENTIALS' };
  if (u.port && u.port !== '443') return { ok: false, reason: 'PORT' };
  const host = u.hostname.toLowerCase();
  if (!host || host === 'localhost' || host.endsWith('.localhost') || host.endsWith('.local')) {
    return { ok: false, reason: 'LOCAL_HOST' };
  }
  // IPv4 literal, or any IPv6 literal (URL keeps the brackets).
  if (/^\d{1,3}(\.\d{1,3}){3}$/.test(host) || host.startsWith('[') || /^[0-9a-f:]+$/i.test(host) && host.includes(':')) {
    return { ok: false, reason: 'IP_HOST' };
  }
  u.search = '';
  u.hash = '';
  return { ok: true, url: u };
}

/**
 * Detect the platform of a booking URL.
 *
 * @param {unknown} input
 * @returns {Detection}
 */
export function platformFromUrl(input) {
  const safe = safeUrl(input);
  if (!safe.ok) return { ok: false, reason: safe.reason };
  const u = safe.url;
  const host = u.hostname.toLowerCase();
  const parts = u.pathname.split('/').filter(Boolean).map((p) => decodeURIComponent(p));
  const canonical = `https://${host}${u.pathname.replace(/\/+$/, '') || '/'}`;

  // ── Booksy: booksy.com/<locale>/<id>_<slug>[_…] ───────────────────────────
  if (HOSTS.booksy.some((d) => hostIs(host, d))) {
    for (const seg of parts) {
      const m = /^(\d{3,12})_([a-z0-9-]+)/i.exec(seg);
      if (m) {
        return {
          ok: true, platformKey: 'booksy', platformBusinessKey: m[1], canonicalUrl: canonical,
          slugName: nameFromSlug(m[2]), evidence: 'booksy:<id>_<slug>',
        };
      }
    }
    return { ok: false, reason: 'BOOKSY_NO_ID', platformKey: 'booksy' };
  }

  // ── Square Appointments ───────────────────────────────────────────────────
  //   book.squareup.com/appointments/<merchant>/location/<location>/…
  //   squareup.com/appointments/book/<token>/…
  if (HOSTS.square_appointments.some((d) => hostIs(host, d))) {
    const ix = parts.indexOf('appointments');
    if (ix >= 0) {
      const after = parts.slice(ix + 1);
      const loc = after.indexOf('location');
      if (after[0] && after[0] !== 'book' && loc === 1 && after[2]) {
        const key = `${after[0]}:${after[2]}`;
        if (/^[A-Za-z0-9_-]{2,60}:[A-Za-z0-9_-]{2,60}$/.test(key)) {
          return {
            ok: true, platformKey: 'square_appointments', platformBusinessKey: key,
            canonicalUrl: `https://${host}/appointments/${after[0]}/location/${after[2]}`,
            slugName: '', evidence: 'square:<merchant>/location/<location>',
          };
        }
      }
      if (after[0] === 'book' && after[1] && /^[A-Za-z0-9_-]{4,80}$/.test(after[1])) {
        return {
          ok: true, platformKey: 'square_appointments', platformBusinessKey: after[1],
          canonicalUrl: `https://${host}/appointments/book/${after[1]}`,
          slugName: '', evidence: 'square:book/<token>',
        };
      }
    }
    return { ok: false, reason: 'SQUARE_NO_ID', platformKey: 'square_appointments' };
  }

  // ── Recognised by host; no adapter in v1 yet ───────────────────────────────
  // Their listings are real and worth counting in coverage ("we can see 38 of
  // 91"), so they are identified — but nothing is scheduled until an adapter
  // for them reaches shadow.
  if (HOSTS.vagaro.some((d) => hostIs(host, d)) && parts[0] && /^[a-z0-9-]{2,80}$/i.test(parts[0])) {
    return { ok: true, platformKey: 'vagaro', platformBusinessKey: parts[0].toLowerCase(), canonicalUrl: `https://${host}/${parts[0]}`, slugName: nameFromSlug(parts[0]), evidence: 'vagaro:<slug>' };
  }
  if (HOSTS.fresha.some((d) => hostIs(host, d))) {
    const slug = parts.find((p) => /^[a-z0-9-]{3,120}$/i.test(p) && !['a', 'lvp', 'book-now', 'providers'].includes(p));
    if (slug) return { ok: true, platformKey: 'fresha', platformBusinessKey: slug.toLowerCase(), canonicalUrl: canonical, slugName: nameFromSlug(slug), evidence: 'fresha:<slug>' };
  }
  if (HOSTS.glossgenius.some((d) => hostIs(host, d)) && host !== 'glossgenius.com' && host !== 'www.glossgenius.com') {
    const sub = host.slice(0, -'.glossgenius.com'.length);
    if (/^[a-z0-9-]{2,63}$/.test(sub)) return { ok: true, platformKey: 'glossgenius', platformBusinessKey: sub, canonicalUrl: `https://${host}/`, slugName: nameFromSlug(sub), evidence: 'glossgenius:<subdomain>' };
  }
  if (hostIs(host, 'as.me') && host !== 'as.me') {
    const sub = host.slice(0, -'.as.me'.length);
    if (/^[a-z0-9-]{2,63}$/.test(sub)) return { ok: true, platformKey: 'acuity', platformBusinessKey: sub, canonicalUrl: `https://${host}/`, slugName: nameFromSlug(sub), evidence: 'acuity:<subdomain>.as.me' };
  }

  return { ok: false, reason: 'UNKNOWN_PLATFORM' };
}

export default { platformFromUrl, safeUrl, nameFromSlug, MAX_URL };
