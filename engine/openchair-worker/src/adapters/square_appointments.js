/**
 * @file src/adapters/square_appointments.js
 * Square Appointments — per-staff slot grid (C1).
 *
 * ─────────────────────────────────────────────────────────────────────────────
 * THE PAGE SHAPE HERE IS MODELLED, NOT RECORDED
 * ─────────────────────────────────────────────────────────────────────────────
 * Founder decision (docs/70-radar-build.md): no request goes to a real booking
 * platform until the spike's go/no-go. This adapter was written against the
 * SYNTHETIC fixtures in fixtures/square_appointments/, shaped after Square's
 * public booking flow — a booking page that embeds its state as JSON, and an
 * availability document for a date range — but not captured from Square.
 *
 * Before it leaves `shadow`: record real public pages in the spike, replace the
 * fixtures, re-verify every path below, and bump `meta.version`.
 *
 * ─────────────────────────────────────────────────────────────────────────────
 * WHAT IT READS (two GETs, tier 1)
 * ─────────────────────────────────────────────────────────────────────────────
 *   https://book.squareup.com/appointments/{merchant}/location/{location}/services
 *       HTML with  <script>window.__BOOKING_STATE__ = { … };</script>
 *       → { merchant: { token, timezone }, location: { id },
 *           team_members: [{ id, … }], services: [{ id, name, duration_minutes, price: { amount } }] }
 *   …/location/{location}/availability?start_date=…&end_date=…
 *       → { slot_minutes, range: { start_date, end_date },
 *           business_hours: [{ date, team_member_id, opens: 'HH:MM', closes: 'HH:MM' }],
 *           availabilities: [{ start_at: 'YYYY-MM-DDTHH:MM:SS±hh:mm', team_member_id }] }
 *
 * `start_at` is read as the LOCAL wall time it states (date and HH:MM taken
 * straight off the string), never converted through the worker's clock — the
 * offset already says whose day it is.
 *
 * Team members carry display names on the page. This adapter reads their `id`
 * and nothing else, and refuses the read if an id looks like a person.
 */

import { createHash } from 'node:crypto';
import { todayIn, addDays, daysBetween, clockToMinutes } from '../lib/localDate.js';

export const meta = {
  platformKey: 'square_appointments',
  capability: 'C1_SLOT_GRID',
  version: '0.1.0-modelled',
  tier: 1,
};

const CAPTCHA = /g-recaptcha|cf-chl-|px-captcha|captcha-delivery|"captcha"\s*:\s*true/i;
const SAFE_ID = /^[A-Za-z0-9][A-Za-z0-9_-]{0,63}$/;
const STATE = /window\.__BOOKING_STATE__\s*=\s*(\{[\s\S]*?\})\s*;\s*<\/script>/;

/**
 * @param {{bookingUrl: string, timezone?: string, horizonDays?: number, read: Function}} ctx
 * @returns {Promise<import('../registry/contract.js').AdapterResult>}
 */
export async function read(ctx) {
  const base = { capability: meta.capability, adapterVersion: meta.version };
  try {
    const ids = bookingIds(ctx.bookingUrl);
    if (!ids) {
      return { ...base, status: 'error', errorCode: 'BAD_BOOKING_URL', errorNote: 'Not a book.squareup.com appointments URL.' };
    }

    const horizon = Math.max(1, Math.min(28, Number(ctx.horizonDays) || 14));
    const start = todayIn(ctx.timezone);
    const end = addDays(start, horizon - 1);
    const root = `https://book.squareup.com/appointments/${ids.merchant}/location/${ids.location}`;

    const page = await ctx.read(`${root}/services`, { tier: meta.tier });
    const stop = statusOf(page);
    if (stop) return { ...base, ...stop };

    const m = STATE.exec(page.body ?? '');
    const state = m ? parseJson(m[1]) : null;
    if (!state || !Array.isArray(state.team_members)) {
      return noGrid(base, 'No __BOOKING_STATE__ with team_members on the booking page; the page shape changed.');
    }

    const av = await ctx.read(`${root}/availability?start_date=${start}&end_date=${end}`, { tier: meta.tier });
    const stop2 = statusOf(av);
    if (stop2) return { ...base, ...stop2 };
    const avail = parseJson(av.body);
    if (!avail || !Array.isArray(avail.business_hours) || !Array.isArray(avail.availabilities)) {
      return noGrid(base, 'Availability document lacks business_hours/availabilities.');
    }

    /** @type {Map<string, string>} */
    const staff = new Map();
    for (const t of state.team_members) {
      const rid = String(t?.id ?? '');
      if (!SAFE_ID.test(rid)) {
        return {
          ...base, status: 'error', errorCode: 'UNSAFE_STAFF_ID',
          errorNote: 'A team member id is not an opaque token. Refusing rather than storing what may be a person.',
        };
      }
      staff.set(rid, sha1(`${meta.platformKey}:${rid}`));
    }

    const slotLen = Number.isInteger(avail.slot_minutes) && avail.slot_minutes >= 5 ? avail.slot_minutes : 15;

    /** @type {Map<string, {rid: string, day: string, s: number, e: number, open: Set<number>}>} */
    const cells = new Map();
    for (const h of avail.business_hours) {
      const rid = String(h?.team_member_id ?? '');
      if (!staff.has(rid)) continue;
      const s = clockToMinutes(h.opens);
      const e = clockToMinutes(h.closes);
      if (s === null || e === null || e <= s || !isIsoDate(h.date)) continue;
      cells.set(`${rid}|${h.date}`, { rid, day: h.date, s, e, open: new Set() });
    }

    for (const a of avail.availabilities) {
      const at = /^(\d{4}-\d{2}-\d{2})T(\d{2}:\d{2})/.exec(String(a?.start_at ?? ''));
      if (!at) continue;
      const cell = cells.get(`${String(a.team_member_id ?? '')}|${at[1]}`);
      if (!cell) continue;
      const min = clockToMinutes(at[2]);
      if (min !== null && min >= cell.s && min < cell.e) cell.open.add(min);
    }

    const units = [...cells.values()]
      .filter((c) => c.day >= start && c.day <= end)
      .sort((a, b) => (a.day === b.day ? a.rid.localeCompare(b.rid) : a.day.localeCompare(b.day)))
      .map((c) => {
        const envelopeMinutes = c.e - c.s;
        const openStarts = [...c.open].sort((x, y) => x - y);
        return {
          staffHash: staff.get(c.rid),
          staffKey: c.rid,
          day: c.day,
          openMinutes: Math.min(envelopeMinutes, openStarts.length * slotLen),
          envelopeMinutes,
          envelopeStart: c.s,
          envelopeEnd: c.e,
          openStarts,
        };
      });

    if (!units.length) {
      return noGrid(base, 'Both documents parsed but no team member has published hours in the range.');
    }

    const servedEnd = isIsoDate(avail.range?.end_date) ? avail.range.end_date : end;
    const covered = Math.min(horizon, daysBetween(start, servedEnd) + 1);

    return {
      ...base,
      status: covered < horizon ? 'partial' : 'ok',
      horizonDays: Math.max(1, covered),
      slotMinutes: slotLen,
      units,
      services: services(state),
      // The SHOP's public name (merchant), never a team member's.
      businessName: shopName(state?.merchant?.name ?? state?.location?.name),
    };
  } catch (err) {
    return {
      ...base,
      status: err?.code === 'EGRESS_ERROR' ? 'error' : 'timeout',
      errorCode: err?.code ?? 'ADAPTER_THREW',
      errorNote: String(err?.message ?? err).slice(0, 255),
    };
  }
}

/**
 * @param {string} url
 * @returns {{merchant: string, location: string}|null}
 */
export function bookingIds(url) {
  try {
    const u = new URL(url);
    if (u.hostname.toLowerCase() !== 'book.squareup.com') return null;
    const m = /^\/appointments\/([A-Za-z0-9_-]{1,64})\/location\/([A-Za-z0-9_-]{1,64})(\/|$)/.exec(u.pathname);
    return m ? { merchant: m[1], location: m[2] } : null;
  } catch {
    return null;
  }
}

function statusOf(page) {
  if (page.status === 403 || page.status === 429) return { status: 'blocked', errorCode: `HTTP_${page.status}` };
  if (page.status === 404) return { status: 'not_found', errorCode: page.code ?? 'HTTP_404' };
  if (page.status >= 400) return { status: 'error', errorCode: `HTTP_${page.status}` };
  if (CAPTCHA.test(page.body ?? '')) return { status: 'captcha', errorCode: 'CAPTCHA_PAGE' };
  return null;
}

function services(state) {
  const out = [];
  for (const s of Array.isArray(state.services) ? state.services : []) {
    const name = String(s?.name ?? '').trim().slice(0, 120);
    const price = Number(s?.price?.amount);
    const dur = Number(s?.duration_minutes);
    if (!name || !Number.isInteger(price) || price < 0) continue;
    out.push({
      key: `square:${String(s.id ?? name)}`.slice(0, 64),
      name,
      durationMin: Number.isInteger(dur) && dur > 0 ? dur : null,
      priceCents: price,               // Square states money in minor units already
    });
  }
  return out;
}

function noGrid(base, note) {
  return { ...base, status: 'error', errorCode: 'NO_GRID_PARSED', errorNote: note };
}

function parseJson(text) {
  try { return JSON.parse(text); } catch { return null; }
}

function isIsoDate(d) {
  return typeof d === 'string' && /^\d{4}-\d{2}-\d{2}$/.test(d);
}

function sha1(s) {
  return createHash('sha1').update(s).digest('hex');
}

/**
 * A shop's public name, cleaned: trimmed, single-spaced, at most 120 chars, or
 * null. Business names are not personal data; staff names are, and never pass
 * through here.
 *
 * @param {unknown} v
 * @returns {string|null}
 */
function shopName(v) {
  const s = String(v ?? '').replace(/\s+/g, ' ').trim().slice(0, 120);
  return s.length >= 2 ? s : null;
}

export default { meta, read };
