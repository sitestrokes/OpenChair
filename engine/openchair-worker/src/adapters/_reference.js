/**
 * @file src/adapters/_reference.js
 * The reference adapter. Copy this to start a real one.
 *
 * Not registered and never scheduled — the leading underscore keeps it out of
 * the registry. It exists so the contract is demonstrated by working code rather
 * than described in prose, and so the contract tests have something honest to
 * run against.
 *
 * ─────────────────────────────────────────────────────────────────────────────
 * THE FOUR RULES OF AN ADAPTER
 * ─────────────────────────────────────────────────────────────────────────────
 * 1. It exports `read(ctx)` and `meta`. Nothing else. No book/hold/submit/login
 *    — the registry refuses a module that exports any of them.
 * 2. Its only network access is `ctx.read(url)`, which can only GET, through the
 *    broker, on another machine. Importing node:http or calling global fetch is
 *    a CI failure, not a code-review note.
 * 3. It emits chair-MINUTES, never slot counts. A 15-minute beard trim and a
 *    90-minute colour are not comparable units.
 * 4. It never returns a name. `staffHash` is an opaque per-platform identifier;
 *    a human name in a snapshot is a personal-data store nobody signed off on.
 *
 * ─────────────────────────────────────────────────────────────────────────────
 * THE MISTAKE TO AVOID
 * ─────────────────────────────────────────────────────────────────────────────
 * If your selectors match nothing, return status 'error'. Do NOT return
 * status 'ok' with an empty unit list — that reads downstream as "every chair
 * booked solid", which buckets the business Slammed and sends a consultant in to
 * congratulate a struggling owner on being too busy. The validator rejects it,
 * but the validator is a backstop, not an excuse.
 */

/** Static description of what this adapter is and what class of signal it emits. */
export const meta = {
  platformKey: '_reference',
  capability: 'C1',        // per-staff slot grid
  version: '0.0.0',
  /** Tier 1 = plain GET. Only claim tier 2 if the page genuinely needs a browser. */
  tier: 1,
};

/**
 * Read one business's public availability.
 *
 * @param {Object} ctx
 * @param {string} ctx.bookingUrl   the public "Book now" page
 * @param {string} ctx.timezone     IANA zone; ALL day/hour maths is local to the business
 * @param {(url: string, opts?: object) => Promise<{status:number, body:string}>} ctx.read
 * @param {number} ctx.horizonDays  how far ahead to look
 * @returns {Promise<import('../registry/contract.js').AdapterResult>}
 */
export async function read(ctx) {
  try {
    const page = await ctx.read(ctx.bookingUrl, { tier: meta.tier });

    if (page.status === 403 || page.status === 429) {
      // Distinguish "they blocked us" from "we broke". The block rate is an
      // operating metric that drives backoff; a parse failure is a defect that
      // drives a fix. Collapsing them hides both.
      return { status: 'blocked', capability: meta.capability, adapterVersion: meta.version, errorCode: `HTTP_${page.status}` };
    }
    if (page.status === 404) {
      return { status: 'not_found', capability: meta.capability, adapterVersion: meta.version };
    }
    if (page.status >= 400) {
      return { status: 'error', capability: meta.capability, adapterVersion: meta.version, errorCode: `HTTP_${page.status}` };
    }

    const units = parseGrid(page.body, ctx);

    if (!units.length) {
      // See "the mistake to avoid" above. A successful fetch that yields no grid
      // is a parse failure, and saying so is what keeps a broken selector from
      // becoming a confident lie.
      return {
        status: 'error',
        capability: meta.capability,
        adapterVersion: meta.version,
        errorCode: 'NO_GRID_PARSED',
        errorNote: 'Fetch succeeded but no availability grid was found — selectors are stale.',
      };
    }

    return {
      status: 'ok',
      capability: meta.capability,
      adapterVersion: meta.version,
      horizonDays: ctx.horizonDays,
      slotMinutes: 30,
      units,
      services: [],
    };
  } catch (err) {
    return {
      status: err?.code === 'EGRESS_ERROR' ? 'error' : 'timeout',
      capability: meta.capability,
      adapterVersion: meta.version,
      errorCode: err?.code ?? 'ADAPTER_THREW',
      errorNote: String(err?.message ?? err).slice(0, 255),
    };
  }
}

/**
 * Turn a page into units. Replace this entirely in a real adapter.
 *
 * The envelope is the span the BUSINESS publishes as bookable, not clock hours
 * and not 9-to-5. A barber closed on Mondays must not be scored as vacant for
 * being closed — that is the difference between a number an owner recognises and
 * one they dismiss in the first sentence.
 *
 * @param {string} _html
 * @param {{timezone: string, horizonDays: number}} _ctx
 * @returns {Array<{staffHash: string, day: string, openMinutes: number, envelopeMinutes: number}>}
 */
function parseGrid(_html, _ctx) {
  return [];
}

export default { meta, read };
