# OpenChair — design package

**Status:** design complete, awaiting founder approval. **No application code has been written yet.**
"OpenChair" is a working name only — see the naming blocker below.

Live capacity prospecting: read the public online-booking availability of local businesses, convert
unsold time into a dollar range, and hand a consultant a pitch built on that business's own calendar.

---

## Read in this order

| # | Doc | What it settles |
|---|---|---|
| 1 | [00-brief.md](docs/00-brief.md) | The founder's original idea, verbatim, plus host-repo context |
| 2 | [05-prd.md](docs/05-prd.md) | **The PRD** — problem, personas, goals/non-goals, 60+ numbered functional requirements, NFRs, product-integrity rules, success metrics, open questions, risk register |
| 3 | [45-flows.md](docs/45-flows.md) | **Flow diagrams** — 10 Mermaid diagrams: S.E.A.T. end-to-end, discovery, the scan sequence with shared-scan dedupe, the 60-second demo, bucket routing, tenancy, alerts, the Ledger loop, the data-quality canary, build phases |
| 4 | [30-data-model.md](docs/30-data-model.md) | **ERD + schema design** — conceptual ERD, entity dictionary, the global-vs-tenant split, time-series tiering, storage math, indexing per real query, retention/PII |
| 5 | [35-scoring-model.md](docs/35-scoring-model.md) | The Vacancy Score, confidence gates, Empty-Chair Revenue ranges, bucket thresholds, Dead-Zone significance testing, Ledger attribution |
| 6 | [40-ux-spec.md](docs/40-ux-spec.md) | 15 screens, IA, first-run, the demo storyboard, component library, tokens, accessibility, wireframes |
| 7 | [10-market-and-offer.md](docs/10-market-and-offer.md) | Avatar, positioning, 40 pitch scripts, offer-ladder critique + EPC model, COGS risk, launch plan, **naming/trademark clearance** |
| 8 | [20-architecture.md](docs/20-architecture.md) | Build target, the Radar adapter framework, the 5-layer read-only guarantee, anti-bot tiers, cost model, the spike, phased plan |
| 9 | [50-risk-compliance-qa.md](docs/50-risk-compliance-qa.md) | Scraping legality, per-platform risk, data protection, the canary, acceptance criteria, go/no-go gate |

Roughly 8,600 lines of design across nine documents, plus 17 tables of DDL and a runnable
scoring implementation.

**Runnable / clickable:**
- [ui/prototype.html](ui/prototype.html) — clickable UI prototype (6 screens + a guided 60-second demo)
- [docs/meter-reference.mjs](docs/meter-reference.mjs) — reference implementation of the scoring math,
  `node docs/meter-reference.mjs` (14 self-tests)
- [sql/](sql/) — 17 tables across 6 idempotent migrations + a rollback

---

## Five decisions needed before a line of code

1. **Rename the product.** "OpenChair" collides with a live salon SaaS whose headline feature is
   calendar-gap detection, an AI booking platform that finds open slots, a chair-rental marketplace,
   an iOS barber app, and a registered US mark covering appointment scheduling. In salon vocabulary
   "an open chair" already means a chair to rent to a stylist — the name tells the target vertical
   the wrong thing in their own language. Shortlist and reasoning in
   [10-market-and-offer.md §10](docs/10-market-and-offer.md).
2. **Move the FE crippling from *the idea* to *volume*.** Withholding the Slammed bucket removes the
   demo's punch line. Recommended: FE gets both buckets and all niches but one metro and 250 credits;
   OTO1 becomes "it runs nightly and alerts you" — which is also where the COGS actually sits.
3. **Fund the spike before anything else.** 5–8 dev-days, 2 adapters, 100 real listings each, three
   consecutive nights. Go requires ≥85% read success with no night-1→3 degradation, ≥90% of grids
   within ±10% of hand-verified chair-minutes, 20/20 correct buckets, ≤$0.04/scan measured, and zero
   non-GET requests. Everything else is downstream of this.
4. **Accept the tier-2 ceiling, or don't build it.** The brief's "residential rotation + stealth" is
   both the largest legal exposure and the largest cost line. A platform that can't be read within
   tier 2 is a platform you don't ship — which may shrink adapter coverage.
5. **Ship two meters, not one.** Cheap on-demand *pulls* and *monitored-business slots* are different
   costs; a $47 one-time FE carrying 250 credits **per month forever** goes negative around month 10.

Total build ≈ **60–80 dev-days**, plus a permanent **3–6 dev-days/month** of adapter maintenance.
That maintenance line is not optional and does not end.

## Rules that are not negotiable once built

From [05-prd.md §8](docs/05-prd.md):

1. Never book, hold or modify a slot — enforced structurally, not by policy.
2. Never say "empty shop". The unit is *online-bookable open slots*.
3. Never show a number below the confidence floor — show the gap instead.
4. Always a range, never a point estimate, and always name the price source.
5. Always disclose what was not visible, as a first-class UI block.
6. Always show data age; stale data is banner-labelled.
7. Never auto-contact a business. The product drafts; the consultant sends — no Send button, and no
   code path from Pitch into `sequence_queue` or telephony.
8. Honour a business's removal request on a published SLA.
9. Never cross the login line. Anonymous public pages only — this is what the case law turns on.
10. Never publish or index vacancy rankings. Scores are private to the tenant watching the business.

---

## Provenance

Written by a panel of specialist agents (market/offer, architecture, data, scoring, UX, risk/QA)
against the brief, then reconciled. Figures carry [VERIFIED] or [ASSUMPTION] tags in their source
docs; anything untagged and inherited from the brief should be treated as an assumption until the
spike returns. Nothing here has been committed to git.
