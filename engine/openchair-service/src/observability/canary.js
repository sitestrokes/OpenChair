/**
 * @file src/observability/canary.js
 * The canary: catching an adapter that returns wrong-but-PLAUSIBLE data.
 *
 * ─────────────────────────────────────────────────────────────────────────────
 * WHY THIS IS THE HARDEST FAILURE TO CATCH
 * ─────────────────────────────────────────────────────────────────────────────
 * A crash announces itself. A timeout announces itself. A selector that quietly
 * stops matching does not — it returns a well-formed grid full of confident
 * nonsense, the validator passes it because the SHAPE is fine, and the Meter
 * scores it. The product then tells a consultant, with a high confidence badge,
 * that a struggling barbershop is booked solid. They walk in and say so.
 *
 * PRD R5 rates this Critical, and FR-9.2 requires the ability to freeze
 * publishing for a platform. The validator (in the worker) checks one result in
 * isolation. The canary checks the POPULATION, over time, which is the only
 * place this failure is visible: one business reading 100% booked is a busy
 * shop, and forty of them overnight is a broken selector.
 *
 * Everything here is comparative. There is no absolute threshold that
 * distinguishes "this platform's businesses are genuinely busy" from "this
 * adapter broke" — only the shape of the change.
 */

import { query } from '../db.js';

/**
 * @typedef {Object} CanaryFinding
 * @property {string} code
 * @property {string} severity  'freeze' | 'warn'
 * @property {string} detail
 */

/**
 * Run every check for one platform on one date.
 *
 * @param {number} platformId
 * @param {string} observedOn
 * @returns {Promise<{platformId: number, findings: CanaryFinding[], shouldFreeze: boolean}>}
 */
export async function checkPlatform(platformId, observedOn) {
  /** @type {CanaryFinding[]} */
  const findings = [];

  const [today] = await query(
    `SELECT COUNT(*) AS n,
            AVG(d.vacancy_bp)                        AS avg_vac,
            -- Naming these precisely, because the direction is easy to invert
            -- and a canary that reports the wrong failure mode at 3am is worse
            -- than one that says nothing. vacancy_bp is OPEN over BOOKABLE:
            --   0     = nothing open  = FULLY BOOKED
            --   10000 = all open      = TOTALLY OPEN
            SUM(d.vacancy_bp = 0)                    AS n_fully_booked,
            SUM(d.vacancy_bp >= 9700)                AS n_totally_open,
            AVG(d.bookable_slots)                    AS avg_bookable,
            SUM(d.bookable_slots = 0)                AS zero_bookable,
            AVG(d.staff_count)                       AS avg_staff
       FROM oc_vacancy_daily d
       JOIN oc_business b ON b.id = d.business_id
      WHERE b.platform_id = ? AND d.observed_on = ?`,
    [platformId, observedOn],
  );

  if (!today || Number(today.n) < 5) {
    // Below a handful of businesses the population signal is noise, and a
    // false freeze is expensive: it stops publishing for every tenant watching
    // that platform.
    return { platformId, findings, shouldFreeze: false };
  }

  const [prior] = await query(
    `SELECT AVG(d.vacancy_bp) AS avg_vac,
            AVG(d.bookable_slots) AS avg_bookable,
            AVG(d.staff_count) AS avg_staff,
            COUNT(*) AS n
       FROM oc_vacancy_daily d
       JOIN oc_business b ON b.id = d.business_id
      WHERE b.platform_id = ?
        AND d.observed_on BETWEEN DATE_SUB(?, INTERVAL 14 DAY) AND DATE_SUB(?, INTERVAL 1 DAY)`,
    [platformId, observedOn, observedOn],
  );

  const n = Number(today.n);

  // ── 1. The population went fully booked overnight. ────────────────────────
  // The signature failure. A selector that stops matching yields zero open
  // slots for every business at once, which reads as "every shop on this
  // platform sold out today" — and puts them all at the top of the Slammed
  // list with high confidence.
  const bookedShare = Number(today.n_fully_booked) / n;
  if (bookedShare > 0.6) {
    findings.push({
      code: 'MASS_FULLY_BOOKED',
      severity: 'freeze',
      detail: `${Math.round(bookedShare * 100)}% of ${n} businesses read 0% open. `
            + 'A whole platform selling out overnight is a broken selector, not a busy week.',
    });
  }

  // ── 2. The population went fully OPEN overnight. ──────────────────────────
  // The mirror failure, and worse commercially: these businesses go straight to
  // the top of the 8am Hungry list and get pitched first.
  const openShare = Number(today.n_totally_open) / n;
  if (openShare > 0.6) {
    findings.push({
      code: 'MASS_TOTALLY_OPEN',
      severity: 'freeze',
      detail: `${Math.round(openShare * 100)}% of ${n} businesses read ~100% open. `
            + 'These would lead the Hungry list tomorrow morning.',
    });
  }

  // ── 3. The denominator collapsed. ─────────────────────────────────────────
  // If bookable_slots goes to zero the envelope parse broke. Vacancy becomes
  // undefined or 0, and either way every number downstream is meaningless.
  const zeroDenomShare = Number(today.zero_bookable) / n;
  if (zeroDenomShare > 0.3) {
    findings.push({
      code: 'ENVELOPE_COLLAPSE',
      severity: 'freeze',
      detail: `${Math.round(zeroDenomShare * 100)}% of businesses report zero bookable slots. `
            + 'The capacity envelope stopped parsing.',
    });
  }

  // ── 4. The distribution moved as a block. ─────────────────────────────────
  // Real markets move gradually and unevenly. A uniform shift across an entire
  // platform in one night is a code change, not a behaviour change.
  if (prior && Number(prior.n) >= 20) {
    const shift = Math.abs(Number(today.avg_vac) - Number(prior.avg_vac));
    if (shift > 2500) {
      findings.push({
        code: 'DISTRIBUTION_JUMP',
        severity: 'freeze',
        detail: `Mean vacancy moved ${Math.round(shift / 100)} points against a 14-day baseline. `
              + 'Whole populations do not move that far in a day.',
      });
    } else if (shift > 1200) {
      findings.push({
        code: 'DISTRIBUTION_DRIFT',
        severity: 'warn',
        detail: `Mean vacancy moved ${Math.round(shift / 100)} points. Worth a look.`,
      });
    }

    // ── 5. The roster changed shape. ────────────────────────────────────────
    // Chairs per business is a slow-moving physical fact. If it halves
    // overnight the staff parse broke, which silently halves every envelope.
    const staffNow = Number(today.avg_staff ?? 0);
    const staffWas = Number(prior.avg_staff ?? 0);
    if (staffWas > 0 && staffNow > 0 && Math.abs(staffNow - staffWas) / staffWas > 0.4) {
      findings.push({
        code: 'ROSTER_SHIFT',
        severity: 'freeze',
        detail: `Average chairs per business went ${staffWas.toFixed(1)} to ${staffNow.toFixed(1)}. `
              + 'Physical capacity does not change that fast.',
      });
    }
  }

  return {
    platformId,
    findings,
    shouldFreeze: findings.some((f) => f.severity === 'freeze'),
  };
}

/**
 * Freeze a platform: keep scanning it, publish nothing.
 *
 * Deliberately NOT disabling the adapter. FR-3.5 wants last-good data served
 * with a staleness label rather than a business vanishing from a consultant's
 * list, and continuing to scan is what lets an operator see whether the next
 * run looks sane. Freezing stops the numbers reaching a tenant; it does not
 * blind us.
 *
 * @param {number} platformId
 * @param {string} reason
 * @returns {Promise<void>}
 */
export async function freezePlatform(platformId, reason) {
  await query(
    `UPDATE oc_platform
        SET rollout = 'frozen', rollout_changed_at = NOW(), rollout_note = ?
      WHERE id = ? AND rollout <> 'frozen'`,
    [String(reason).slice(0, 255), platformId],
  );
}

/**
 * Run the canary across every scheduled platform.
 *
 * @param {string} [observedOn]
 * @returns {Promise<Array<{platformKey: string, findings: CanaryFinding[], frozen: boolean}>>}
 */
export async function runCanary(observedOn = new Date().toISOString().slice(0, 10)) {
  const platforms = await query(
    `SELECT id, platform_key, rollout FROM oc_platform
      WHERE enabled = 1 AND rollout IN ('live', 'shadow')`,
  );

  const out = [];
  for (const p of platforms) {
    const res = await checkPlatform(p.id, observedOn);
    let frozen = false;

    if (res.shouldFreeze) {
      const why = res.findings.filter((f) => f.severity === 'freeze').map((f) => f.code).join(', ');
      await freezePlatform(p.id, `canary ${observedOn}: ${why}`);
      frozen = true;
    }
    if (res.findings.length) {
      out.push({ platformKey: p.platform_key, findings: res.findings, frozen });
    }
  }
  return out;
}

export default { checkPlatform, freezePlatform, runCanary };
