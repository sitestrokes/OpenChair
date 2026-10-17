/**
 * @file src/lib/localDate.js
 * Business-local calendar dates.
 *
 * Every day an adapter reports is the BUSINESS's day, not the worker's and not
 * UTC's. A Tampa barber's Tuesday that a UTC worker files under Wednesday after
 * 8pm puts the evening's open chairs on the wrong card and splits one day into
 * two half-days — so the conversion happens once, here, and nowhere else does
 * date arithmetic on a wall clock.
 *
 * Dates are plain 'YYYY-MM-DD' strings throughout. Day arithmetic is done at
 * UTC noon so a daylight-saving transition can never move a date.
 */

/**
 * Today's date in an IANA zone.
 *
 * @param {string} [timezone]  e.g. 'America/New_York'; falls back to UTC if unknown
 * @param {Date}   [now]
 * @returns {string} YYYY-MM-DD
 */
export function todayIn(timezone, now = new Date()) {
  try {
    // en-CA formats as YYYY-MM-DD, which is the one thing we want from it.
    return new Intl.DateTimeFormat('en-CA', {
      timeZone: timezone || 'UTC', year: 'numeric', month: '2-digit', day: '2-digit',
    }).format(now);
  } catch {
    return now.toISOString().slice(0, 10);
  }
}

/**
 * @param {string} isoDate YYYY-MM-DD
 * @param {number} n       days to add (may be negative)
 * @returns {string}
 */
export function addDays(isoDate, n) {
  const t = Date.parse(`${isoDate}T12:00:00Z`) + n * 86400000;
  return new Date(t).toISOString().slice(0, 10);
}

/**
 * Whole days from `a` to `b` (b − a).
 *
 * @param {string} a
 * @param {string} b
 * @returns {number}
 */
export function daysBetween(a, b) {
  return Math.round((Date.parse(`${b}T12:00:00Z`) - Date.parse(`${a}T12:00:00Z`)) / 86400000);
}

/**
 * '09:30' → 570. Null for anything that is not a 24h clock time.
 *
 * @param {unknown} hhmm
 * @returns {number|null}
 */
export function clockToMinutes(hhmm) {
  const m = /^(\d{1,2}):(\d{2})$/.exec(String(hhmm ?? '').trim());
  if (!m) return null;
  const h = Number(m[1]);
  const min = Number(m[2]);
  if (h > 24 || min > 59 || (h === 24 && min !== 0)) return null;
  return h * 60 + min;
}

export default { todayIn, addDays, daysBetween, clockToMinutes };
