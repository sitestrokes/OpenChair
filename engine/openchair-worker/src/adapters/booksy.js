/**
 * @file src/adapters/booksy.js
 * Booksy — per-staff slot grid (C1).
 *
 * ─────────────────────────────────────────────────────────────────────────────
 * THE PAGE SHAPE HERE IS MODELLED, NOT RECORDED
 * ─────────────────────────────────────────────────────────────────────────────
 * Founder decision (docs/70-radar-build.md): no request goes to a real booking
 * platform until the spike's go/no-go. So this adapter was written against the
 * SYNTHETIC fixtures in fixtures/booksy/, which are shaped after Booksy's
 * public customer flow — a business document (staff, service menu) and a
 * time-slot document for a date range — but were not captured from Booksy.
 *
 * Before this adapter leaves `shadow`: record real public pages in the spike,
 * replace the fixtures, re-verify every field path below against them, and bump
 * `meta.version`. Every snapshot is stamped with that version, so a grid read by
 * the modelled parser can always be told apart from one read by the verified one.
 *
 * ─────────────────────────────────────────────────────────────────────────────
 * WHAT IT READS (two GETs, tier 1 — plain JSON, no browser)
 * ─────────────────────────────────────────────────────────────────────────────
 *   {api}/businesses/{id}
 *       → { business: { id, timezone, staffers: [{ id, … }],
 *                       service_categories: [{ services: [{ id, name, variants: [{ duration, price }] }] }] } }
 *   {api}/businesses/{id}/appointments/time_slots?start_date=…&end_date=…
 *       → { slot_length, start_date, end_date,
 *           working_hours: [{ date, staffer_id, start: 'HH:MM', end: 'HH:MM' }],
 *           time_slots:    [{ date, staffer_id, slots: ['HH:MM', …] }] }
 *
 * The business id is the leading number of the public booking URL's last path
 * segment: https://booksy.com/en-us/100001_fade-lab_barber-shop_134763_tampa.
 *
 * ─────────────────────────────────────────────────────────────────────────────
 * WHAT IT NEVER EMITS
 * ─────────────────────────────────────────────────────────────────────────────
 * A person. Staffers carry names on the page; this adapter reads `id` and
 * nothing else from a staffer, hashes it, and refuses the whole read if an id
 * itself looks like a name — a page that put the stylist's name where the id
 * belongs would otherwise write that name into oc_staff.staff_key.
 */

import { createHash } from 'node:crypto';
import { todayIn, addDays, daysBetween, clockToMinutes } from '../lib/localDate.js';

export const meta = {
  platformKey: 'booksy',
  capability: 'C1_SLOT_GRID',
  version: '0.1.0-modelled',
  tier: 1,
};

const API = 'https://us.booksy.com/api/us/2/customer_api';

/** Markers of an anti-bot interstitial served in place of the data. */
const CAPTCHA = /g-recaptcha|cf-chl-|px-captcha|captcha-delivery|"captcha"\s*:\s*true/i;

/** A resource id may be an opaque token. A space or a run of letters-only words is a person. */
const SAFE_ID = /^[A-Za-z0-9][A-Za-z0-9_-]{0,63}$/;

/**
 * @param {{bookingUrl: string, timezone?: string, horizonDays?: number, read: Function}} ctx
 * @returns {Promise<import('../registry/contract.js').AdapterResult>}
 */
export async function read(ctx) {
  const base = { capability: meta.capability, adapterVersion: meta.version };
  try {
    const id = businessId(ctx.bookingUrl);
    if (!id) {
      return { ...base, status: 'error', errorCode: 'BAD_BOOKING_URL', errorNote: 'No Booksy business id in the booking URL.' };
    }

    const horizon = Math.max(1, Math.min(28, Number(ctx.horizonDays) || 14));
    const start = todayIn(ctx.timezone);
    const end = addDays(start, horizon - 1);

    const biz = await ctx.read(`${API}/businesses/${id}`, { tier: meta.tier });
    const stop = statusOf(biz);
    if (stop) return { ...base, ...stop };

    const slotsPage = await ctx.read(
      `${API}/businesses/${id}/appointments/time_slots?start_date=${start}&end_date=${end}`,
      { tier: meta.tier },
    );
    const stop2 = statusOf(slotsPage);
    if (stop2) return { ...base, ...stop2 };

    const business = parseJson(biz.body)?.business;
    const avail = parseJson(slotsPage.body);
    if (!business || !avail || !Array.isArray(avail.working_hours) || !Array.isArray(avail.time_slots)) {
      return noGrid(base, 'Expected business.staffers and time_slots/working_hours; the document shape changed.');
    }

    // ── Staff: ids only, hashed. ────────────────────────────────────────────
    /** @type {Map<string, string>} resourceId → staffHash */
    const staff = new Map();
    for (const s of Array.isArray(business.staffers) ? business.staffers : []) {
      const rid = String(s?.id ?? '');
      if (!SAFE_ID.test(rid)) {
        return {
          ...base, status: 'error', errorCode: 'UNSAFE_STAFF_ID',
          errorNote: 'A staffer id is not an opaque token. Refusing rather than storing what may be a person.',
        };
      }
      staff.set(rid, sha1(`${meta.platformKey}:${rid}`));
    }

    const slotLen = Number.isInteger(avail.slot_length) && avail.slot_length >= 5 ? avail.slot_length : 30;

    // ── Envelope per chair per day, from the published working hours. ──────
    /** @type {Map<string, {rid: string, day: string, s: number, e: number, open: Set<number>}>} */
    const cells = new Map();
    for (const w of avail.working_hours) {
      const rid = String(w?.staffer_id ?? '');
      if (!staff.has(rid)) continue;                 // hours for a chair not on the roster
      const s = clockToMinutes(w.start);
      const e = clockToMinutes(w.end);
      if (s === null || e === null || e <= s || !isIsoDate(w.date)) continue;
      cells.set(`${rid}|${w.date}`, { rid, day: w.date, s, e, open: new Set() });
    }

    // ── Open slots, placed inside their envelope; anything outside is dropped
    //    (a slot outside the published day is a parse artefact, not capacity).
    for (const t of avail.time_slots) {
      const cell = cells.get(`${String(t?.staffer_id ?? '')}|${t?.date}`);
      if (!cell) continue;
      for (const hhmm of Array.isArray(t.slots) ? t.slots : []) {
        const m = clockToMinutes(hhmm);
        if (m !== null && m >= cell.s && m < cell.e) cell.open.add(m);
      }
    }

    const units = [...cells.values()]
      .filter((c) => c.day >= start && c.day <= end)
      .sort((a, b) => (a.day === b.day ? a.rid.localeCompare(b.rid) : a.day.localeCompare(b.day)))
      .map((c) => {
        const envelopeMinutes = c.e - c.s;
        const openStarts = [...c.open].sort((a, b) => a - b);
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
      return noGrid(base, 'Fetched both documents but found no working hours for any listed staffer.');
    }

    // A response that stops short of the range we asked for is a partial
    // horizon: the scoring model weights by coverage, so it must know.
    const servedEnd = isIsoDate(avail.end_date) ? avail.end_date : end;
    const covered = Math.min(horizon, daysBetween(start, servedEnd) + 1);

    return {
      ...base,
      status: covered < horizon ? 'partial' : 'ok',
      horizonDays: Math.max(1, covered),
      slotMinutes: slotLen,
      units,
      services: services(business),
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
 * The leading digits of the booking URL's last path segment.
 *
 * @param {string} url
 * @returns {string|null}
 */
export function businessId(url) {
  try {
    const u = new URL(url);
    if (!/(^|\.)booksy\.com$/i.test(u.hostname)) return null;
    const last = u.pathname.split('/').filter(Boolean).pop() ?? '';
    const m = /^(\d{1,12})_/.exec(last);
    return m ? m[1] : null;
  } catch {
    return null;
  }
}

/** HTTP status → a terminal adapter status, or null to carry on. */
function statusOf(page) {
  if (page.status === 403 || page.status === 429) return { status: 'blocked', errorCode: `HTTP_${page.status}` };
  if (page.status === 404) return { status: 'not_found', errorCode: page.code ?? 'HTTP_404' };
  if (page.status >= 400) return { status: 'error', errorCode: `HTTP_${page.status}` };
  if (CAPTCHA.test(page.body ?? '')) return { status: 'captcha', errorCode: 'CAPTCHA_PAGE' };
  return null;
}

/** The menu, by service id. Names are SERVICE names; nobody's name. */
function services(business) {
  const out = [];
  for (const cat of Array.isArray(business.service_categories) ? business.service_categories : []) {
    for (const s of Array.isArray(cat?.services) ? cat.services : []) {
      const v = Array.isArray(s?.variants) ? s.variants[0] : null;
      const price = Math.round(Number(v?.price) * 100);
      const dur = Number(v?.duration);
      const name = String(s?.name ?? '').trim().slice(0, 120);
      if (!name || !Number.isFinite(price) || price < 0) continue;
      out.push({
        key: `booksy:${String(s.id ?? name)}`.slice(0, 64),
        name,
        durationMin: Number.isInteger(dur) && dur > 0 ? dur : null,
        priceCents: price,
      });
    }
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

export default { meta, read };
