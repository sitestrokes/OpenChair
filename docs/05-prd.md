# OpenChair — Product Requirements Document

**Status:** DRAFT v0.1 — for founder approval, pre-build
**Owner:** Nakul / InstantClose AI
**Date:** 2026-09-04
**Related docs:** [00-brief](00-brief.md) · [10-market-and-offer](10-market-and-offer.md) ·
[20-architecture](20-architecture.md) · [30-data-model](30-data-model.md) ·
[35-scoring-model](35-scoring-model.md) · [40-ux-spec](40-ux-spec.md) · [45-flows](45-flows.md) ·
[50-risk-compliance-qa](50-risk-compliance-qa.md)

---

## 1. Summary

OpenChair is a **live capacity prospecting** tool for local-business consultants. It reads the
**public online-booking availability** of local businesses, converts unsold time into a dollar
figure, and hands the consultant a pitch built on that business's own calendar.

Every competing local-lead tool qualifies prospects on how bad their *website* looks. OpenChair
qualifies on how much *unsold time* they have right now — a number the owner already feels and has
never seen written down.

**Primary promise:** walk in with their empty calendar, not a cold pitch.

---

## 2. Problem

### 2.1 The consultant's problem
A local consultant's opener — "Hi, I help local businesses with marketing" — is pattern-matched to
spam within three words. They have no credible, specific, *pre-earned* reason to be in the room. Every
audit tool they own produces the same generic red-yellow-green website scorecard the owner has already
seen from four other people this month.

### 2.2 The business owner's problem
Two distinct, opposite problems, and nobody sells to the second one:

| | Hungry (high vacancy) | Slammed (near-full, long wait) |
|---|---|---|
| Felt problem | "It's dead in here on Tuesdays" | "I keep turning people away" |
| Actual loss | Unsold chair-time, perishable | Walk-aways to the shop next door |
| What they need | Customers | Capacity, waitlist, pricing, staff |
| Who sells to them | Everyone | **Nobody** |
| Can they afford you | Maybe | **Yes** |

The Slammed segment is the strategic insight: nobody pitches them because nobody *knows* they're
slammed. Their problem is real, expensive, and they have cash.

### 2.3 Why now
Booking software penetration in these niches is now high enough that "has online booking" is a large
addressable set rather than an edge case — and having it is itself a qualifier (they already pay for
software, so they buy software).

---

## 3. Users and personas

**P1 — "Second-income Sam", the aspiring local consultant.**
Bought 6–20 JVZoo local tools. Ships almost nothing. Blocked at *the opener*, not at delivery.
Needs: a reason to make contact that doesn't feel like begging. Buys on the demo. Refunds if he
can't get one usable prospect list in the first session.
**Success for Sam = one printed one-pager he'd actually walk into a shop with, in under 10 minutes.**

**P2 — "Agency Ana", the working agency owner.**
Has 4–15 local clients, bills retainers, has a VA. Prospecting is her constraint, and *churn* is her
real problem — she can't prove her retainer worked.
Needs: a repeatable prospect pipeline AND a monthly client-facing report that proves lift.
**Success for Ana = the Ledger becomes her retainer report, and her churn drops.**

**P3 — "Owner Omar", the local business owner.** Not a user — the *subject*. Never logs in. But every
number the product shows must survive being read aloud to Omar in his own shop, by someone who does
not fully understand it. This constraint drives most of the product-integrity requirements below.

**P4 — Vendor operations (internal).** Runs the crawl fleet, watches adapter health, handles removal
requests. Needs the Coverage/Adapters console and the canary alarms.

---

## 4. Goals and non-goals

### 4.1 Goals
| # | Goal | Measure |
|---|---|---|
| G1 | Give a consultant a defensible, specific opener | ≥1 pitch artifact generated per user in first session |
| G2 | Qualify on capacity, not aesthetics | Vacancy Score present + confident for ≥70% of discovered bookable businesses |
| G3 | Double the addressable pitch surface | Both Hungry and Slammed pitch packs shipped and used |
| G4 | Turn prospecting into retention | Ledger re-scan report drives a recurring tier |
| G5 | Never embarrass the user in front of a business owner | Confidence floor enforced; zero "confidently wrong" incidents at launch |
| G6 | Hold unit economics under adversarial anti-bot | Blended COGS per watched business per month within budget (see market doc) |

### 4.2 Non-goals (v1 — explicitly out of scope)
- **Booking, holding, cancelling or modifying any appointment. Ever.** Read-only is architectural.
- Contacting the prospect business on the user's behalf (no auto-send email/SMS/calls from OpenChair).
- Scraping anything behind a login, paywall, or account creation.
- **Tier-3 collection — stealth browsers, residential-proxy rotation, or any technique whose purpose
  is to disguise origin or defeat an access control.** The brief proposed this; the compliance review
  rejects it. A hard tier-2 ceiling, not a config flag. It is simultaneously the largest legal
  exposure (DMCA §1201, post-block revocation of authorisation) and the largest COGS line. A platform
  that cannot be read within tier 2 is a platform we do not ship.
- **Screen-capturing a booking platform's page.** The one-pager renders *our* grid from the observed
  data. Capturing their page copies their layout, imagery and staff photos.
- **Storing staff members' names.** Chairs are an opaque index plus a positional label ("Chair 2").
- Being a CRM. Watchlist + notes + stage only; deeper CRM is an integration, not a build.
- Real-time availability. Once-daily observation is the product; "live" refers to *current market
  reality*, not sub-minute freshness.
- Predicting future bookings / ML forecasting. v1 is descriptive, not predictive.
- Consumer-facing "find an open slot near me" search. Different product, different legal posture.

---

## 5. Product scope — the four components

### 5.1 Radar (collection)
Discovers businesses with public online booking, routes each to a platform adapter, and takes **one
observation pass per business per day**. Adapters are pluggable; new platforms ship as value drops.
Detailed design: [20-architecture](20-architecture.md).

### 5.2 Meter (scoring)
Converts a raw availability grid into: **Vacancy Score** (0–100, normalised across platform types),
a **confidence band**, **Empty-Chair Revenue** (a *range*, never a point estimate), a **bucket**
(Hungry / Steady / Slammed), and the **Dead-Zone Map** (day-of-week × hour-block chronic vacancy).
Detailed design: [35-scoring-model](35-scoring-model.md).

### 5.3 Ledger (trend + retention)
8-week rolling vacancy trend per business. The same screen serves prospecting ("here is your unsold
time") and retention ("here is what changed since you hired me"), with white-label controls.

### 5.4 Pitch (activation)
Generates the walk-in one-pager (print-spec PDF), the email, and the SMS — in a **Hungry** variant
(sell customers) or a **Slammed** variant (sell capacity) — from the same scan. Hands off to LossReel
for a 30-second video version.

---

## 6. Functional requirements

Priority: **M** = must (v1 launch) · **S** = should · **C** = could (OTO//later)

### 6.1 Onboarding and scan setup
| ID | Req | Pri |
|---|---|---|
| FR-1.1 | User selects a niche and a city/geography and starts a scan | M |
| FR-1.2 | Quota/entitlement enforced per tier (cities, niches, watched businesses) | M |
| FR-1.3 | Results **stream in progressively** as businesses resolve — never a blocking spinner | M |
| FR-1.4 | A demo/sample dataset is available instantly on first login so time-to-first-value is not gated on a crawl | M |
| FR-1.5 | Saved scans re-run on a schedule | S |
| FR-1.6 | Multi-city / unlimited niches | C (OTO1) |

### 6.2 Discovery
| ID | Req | Pri |
|---|---|---|
| FR-2.1 | Given niche+city, enumerate candidate businesses with name, address, website, phone | M |
| FR-2.2 | Detect whether a candidate has public online booking and which platform | M |
| FR-2.3 | Record `not_bookable` with a re-check horizon, and show the user the coverage ratio honestly ("38 of 91 have online booking") | M |
| FR-2.4 | Never require or attempt account creation on a booking platform | M |
| FR-2.5 | Manual add: user pastes a booking URL directly | S |

### 6.3 Scanning
| ID | Req | Pri |
|---|---|---|
| FR-3.1 | One observation per business per day, shared across all tenants watching it | M |
| FR-3.2 | Read-only: the system is structurally incapable of issuing a write/booking request | M |
| FR-3.3 | Per-domain rate limits, backoff, circuit breaker, per-adapter kill switch | M |
| FR-3.4 | Every fetch audit-logged (what, when, from where, outcome) | M |
| FR-3.5 | On failure, serve last-good data explicitly labelled stale with its age | M |
| FR-3.6 | Six adapters at launch; additional adapters shippable without full redeploy risk | M |

### 6.4 Scoring and display
| ID | Req | Pri |
|---|---|---|
| FR-4.1 | Vacancy Score 0–100 comparable across platform capability classes | M |
| FR-4.2 | Every score carries a confidence band; below the floor the product shows "insufficient data" and **no number** | M |
| FR-4.3 | Empty-Chair Revenue displayed as a **range** with its price-source disclosed | M |
| FR-4.4 | Bucketing into Hungry / Steady / Slammed, niche-relative | M |
| FR-4.5 | "What we could not see" disclosure block on every business detail — a first-class UI element, not fine print | M |
| FR-4.6 | Wording lock: "online-bookable open slots", never "empty shop" — enforced in all generated copy | M |
| FR-4.7 | Dead-Zone Map (DOW × hour-block) with a minimum-observations rule | M (OTO1 gate) |

### 6.5 Prospect management
| ID | Req | Pri |
|---|---|---|
| FR-5.1 | Map + list hero view, pins coloured by vacancy with a redundant non-colour encoding | M |
| FR-5.2 | Filter by niche, bucket, score range, confidence, last-scanned; sort by score/revenue/delta | M |
| FR-5.3 | Add to watchlist; per-prospect notes and pipeline stage | M |
| FR-5.4 | Mobile view usable while standing outside the shop | M |
| FR-5.5 | Export (CSV) of the watchlist | S |

### 6.6 Alerts
| ID | Req | Pri |
|---|---|---|
| FR-6.1 | 8am digest in the tenant's local timezone, split by bucket, ranked by change | M |
| FR-6.2 | Deep links straight to Business Detail | M |
| FR-6.3 | Alerts go to the **consultant only** — never to the business | M |
| FR-6.4 | Channel choice: email, in-app, Telegram (reuse the engine's existing digest plumbing) | S |

### 6.7 Pitch
| ID | Req | Pri |
|---|---|---|
| FR-7.1 | One-pager generated from the business's own grid + dollar range + matched offer | M |
| FR-7.2 | Hungry and Slammed variants, switchable | M (Slammed = OTO1 gate) |
| FR-7.3 | Print/PDF at a real page spec | M |
| FR-7.4 | Email + SMS variants, editable before use | M |
| FR-7.5 | Compliance guardrails in generated copy (no earnings claims, no auto-send, opt-out language) | M |
| FR-7.6 | LossReel handoff for a 30-second video | C |
| FR-7.7 | Fill-the-Chair campaign kit (dead-zone offers, SMS templates, Google offer posts) | C (OTO3) |

### 6.8 Ledger
| ID | Req | Pri |
|---|---|---|
| FR-8.1 | 8-week vacancy/fill-rate trend per business | M |
| FR-8.2 | Same component in prospecting and retainer framing | M |
| FR-8.3 | Attribution caveat line on every client-facing report | M |
| FR-8.4 | White-label: logo, colours, custom domain (reuse the engine's existing custom-domain machinery) | C (OTO2) |

### 6.9 Operations console (internal)
| ID | Req | Pri |
|---|---|---|
| FR-9.1 | Adapter health: success rate, block rate, last fixture refresh, circuit state | M |
| FR-9.2 | **Canary**: detect an adapter returning wrong-but-plausible data and freeze publishing for that platform | M |
| FR-9.3 | Business removal-request intake with an SLA, and a permanent do-not-scan list | M |
| FR-9.4 | Per-tenant quota and cost attribution | M |

---

## 7. Non-functional requirements

| ID | Requirement | Target |
|---|---|---|
| NFR-1 | Demo performance: search → first pins | < 4 s |
| NFR-2 | Full result set rendered | < 25 s for ~50 businesses |
| NFR-3 | Pin → Business Detail | < 400 ms |
| NFR-4 | Detail → rendered one-pager | < 3 s |
| NFR-5 | Scan freshness | ≤ 24 h, age always displayed |
| NFR-6 | Accuracy: hand-verified sample error rate | see the go/no-go gate in the QA doc |
| NFR-7 | Isolation: the crawl fleet must not degrade the existing bridge-service voice-capture SLO (<300 ms) | hard requirement |
| NFR-8 | Accessibility | WCAG AA contrast; no meaning by colour alone; keyboard path through the demo flow |
| NFR-9 | Data retention | raw snapshots short-lived; aggregates retained for the Ledger window; documented and enforced |
| NFR-10 | Secrets | none in repo; follow the existing env conventions |

---

## 8. Product-integrity rules (non-negotiable)

These exist because P3 (Owner Omar) will hear these numbers read aloud.

1. **Never book, hold, or modify a slot.** Enforced in code, not policy.
2. **Never say "empty shop".** The unit is *online-bookable open slots*.
3. **Never show a number below the confidence floor.** Show the gap instead.
4. **Always show the range**, never a single dollar figure, and always name the price source.
5. **Always disclose what was not visible** (blocked slots, staff days off, walk-ins, phone bookings).
6. **Always show data age**; stale data is banner-labelled.
7. **Never auto-contact a business.** The consultant sends; the product only drafts. There must be no
   Send button and no code path from Pitch into `sequence_queue` or telephony — asserted in CI.
8. **Honour a business's removal request** on a published SLA.
9. **Never cross the login line.** Anonymous public pages only. No accounts, no credentials, no
   fabricated identities — this is the distinction the case law actually turns on.
10. **Never publish or index vacancy rankings.** Scores are visible to the tenant who watches the
    business, never on a public page. A public "least busy barbers in Tampa" list is a defamation
    surface with no upside.

---

## 9. Success metrics

**Launch (first 30 days)**
- Refund rate below the vendor's historical launch baseline
- ≥60% of buyers run at least one scan; ≥35% generate at least one pitch artifact
- Zero "the tool booked a fake appointment" incidents (this is a pass/fail, not a rate)
- Zero platform legal/abuse contacts

**Product (90 days)**
- Median time from login to first pitch artifact
- Watchlist retention: % of buyers with ≥1 business still watched at day 30
- Adapter health: median success rate per adapter, canary false-negative count
- Conversion into the recurring tier (the actual business outcome)

---

## 10. Open questions for the founder

These need a decision before the build starts; each is analysed in the linked doc.

| # | Question | Where analysed |
|---|---|---|
| Q1 | ~~Does the spike prove Radar can read the top platforms — and does an official API beat scraping?~~ **PARTLY ANSWERED:** the spike is defined (5–8 dev-days, 2 adapters × 100 listings × 3 consecutive nights; go = ≥85% read success, no night-1→3 degradation, ≥90% within ±10% on hand-verified chair-minutes with 20/20 correct buckets, ≤$0.04/scan, and **zero non-GET requests**). On APIs: **every** official API in the set (Square, Acuity, Mindbody, Housecall Pro, Jobber, ServiceTitan) is *merchant-authorised*, so none can replace the scrape for prospecting — but Square-first merchant OAuth is the **post-sale** path to confidence 1.00 and a caveat-free Ledger. Still needs the spike actually run. | 20-architecture |
| Q2 | ~~Build target?~~ **ANSWERED: (b) a sibling Node service.** Three processes (`openchair-service` :4100, an `openchair-worker` fleet on its own VM, an `openchair-egress` broker) plus a CI3 module for the UI. The deciding argument is deploy cadence, not CPU: adapters break weekly, and bridge-service holds live WebRTC/Twilio media sessions — option (a) means restarting live AI voice calls to fix a Booksy selector. Option (c) is out because it invalidates the shared-DB island in 30-data-model. | 20-architecture |
| Q3 | Is the FE crippling (1 city / 3 niches / Hungry only) right, or does it strand the demo's best moment behind OTO1? | 10-market-and-offer |
| Q4 | Absolute bucket thresholds (60% / 95%) or niche-relative percentiles? Do we ship the third bucket? | 35-scoring-model |
| Q5 | ~~What COGS ceiling makes the FE viable, and what quota design survives it?~~ **ANSWERED, and the two reviews reconcile:** tiers cost T1 ≈ $0.0001 / T2 ≈ $0.0007 / T3 ≈ $0.014 per scan; a realistic engineered mix is **$0.010**, but an un-engineered day-one build is **$0.08–0.25**. So the founder's $0.02 is a defensible *steady state* and the market doc's "10–25× too low" describes the *starting* condition — same curve, different point. Adopt $0.02 as a budget with an automatic circuit on `cost_micros`. Quota must use **two meters**: cheap on-demand *pulls* vs *monitored-business slots* (≈$0.60/mo cost, retail $1–2). A $47 one-time FE carrying 250 credits **per month forever** goes negative around month 10. | 10-market-and-offer + 20-architecture |
| Q6 | ~~Which platforms do we *refuse* to touch on legal grounds even if technically easy?~~ **ANSWERED:** ship Booksy, Vagaro, Fresha, GlossGenius, Square, Acuity, Momence; prioritise Housecall Pro + Jobber (first-party-hosted widgets — the best legal posture in the set); **refuse OpenTable and Resy for v1** (highest legal risk attached to our weakest confidence class); **defer Mindbody** to its paid partner API, and Zocdoc-style clinics on health adjacency. Rebrand the $997 tier as enterprise + home services rather than "restaurant/clinic adapters". | 50-risk-compliance-qa |
| Q7 | Does the Ledger become a vendor-side subscription (MRR) rather than a one-time OTO? | 10-market-and-offer |
| Q8 | **Three schema gaps found after 30-data-model was written** — fold in before migrations are applied: (a) `oc_discovery_candidate` is missing, so the `not_bookable` park in 45-flows §2 has nowhere to live (`oc_business.platform_id` is NOT NULL); (b) `oc_platform.rollout` for shadow/research adapters; (c) `oc_scan_run.tier` + `.bytes` for cost attribution. Also: per-fetch audit (FR-3.4) should be rotated JSONL, not a table — a row per fetch is ~800k rows/night. | 20-architecture + 30-data-model |
| Q9 | **Does C3 home-services have an observable signal at all?** Arrival-window platforms rarely publish crews-free, which would leave the C3 branch of the scoring model with no numerator — and the $997 backend currently gates exactly these least-reliable classes at the highest price. Verify before selling it. | 20-architecture + 35-scoring-model |

---

## 11. Risk register

Carried from the founder brief and expanded by the specialist reviews; mitigations are in the linked
docs.

| ID | Risk | Severity | Owner doc |
|---|---|---|---|
| R1 | Vacancy ≠ vacancy (blocked slots, days off, walk-ins invisible) | High | 35-scoring, 40-ux |
| R2 | Anti-bot defeats Radar on the platforms that matter | **Critical — whole-project** | 20-architecture |
| R3 | Coverage too thin in a chosen city+niche to feel worth $47 | High | 20-architecture, 40-ux |
| R4 | A booking gets created by accident or by a bad actor via the tool | **Critical — launch-ending** | 20-architecture, 50-risk |
| R5 | Adapter silently returns plausible-but-wrong data | Critical | 50-risk (canary) |
| R6 | Platform ToS / legal contact | High | 50-risk |
| R7 | COGS blowout under residential-proxy pricing | High | 10-market, 20-architecture |
| R8 | Generated pitch copy creates TCPA/CAN-SPAM exposure for the user | Medium | 50-risk |

---

*Sections 5–8 are stable. Any figure in this PRD that originates from a specialist review is marked
in that review as [VERIFIED] or [ASSUMPTION]; treat unmarked founder-brief figures as assumptions
until the spike returns.*
