# OpenChair — Meter Scoring Model

**Component:** Meter (Vacancy Score · Empty-Chair Revenue · Dead-Zone Map)
**Model version:** `meter-1.0.0`
**Reference implementation:** [`meter-reference.mjs`](./meter-reference.mjs) — plain ESM, Node 22, zero deps.
`node meter-reference.mjs` prints every worked example in this document; `node --test meter-reference.mjs`
runs 14 assertions. All numeric output pasted below is real program output, not hand arithmetic.

---

## 0. The design constraint everything else follows from

This number gets shown to a skeptical owner **on their own screen, next to their own booking page**.
That imposes one rule that overrides elegance:

> **Every input to the headline number must be something the owner can point at and verify in
> ten seconds. Everything that is not observable must either be disclosed on the artifact or
> excluded from the headline.**

Two corollaries we enforce mechanically:

1. **We never assert a counterfactual.** "You lost $2,340" claims demand existed. We cannot see
   demand. We can see *unsold time* and *published prices*. That is the whole claim.
2. **We never show a number we cannot defend.** A confidence gate can suppress the score entirely.
   A prospecting tool that says "not enough data — rescanning tonight" on 8% of businesses is far
   more valuable than one that guesses on 100% and is humiliated on 3%.

Everything below is in service of surviving one sentence from the owner:
*"Where did you get that?"*

---

## 1. Vacancy Score — the definition problem

### 1.1 Why the naive formula fails

The brief's formula is `open_slots ÷ bookable_slots` over the next 7 days. It breaks in five places:

| Break | What goes wrong |
|---|---|
| **Slot granularity** | 24 open 15-min slots and 6 open 60-min slots are the same hours. Counting *slots* makes a fine-grained platform look 4× emptier than a coarse one. |
| **Capacity** | A yoga class with 11 of 20 mats free is one "slot", not eleven. Slot-counting says 0% vacant. |
| **Multi-staff** | 5 barbers is 5 parallel calendars. Whose slots? |
| **Denominator** | "Bookable slots" is undefined. Clock hours? Google's business hours? Both are wrong. |
| **Horizon** | A slot open tomorrow and a slot open next Sunday are counted identically, when tomorrow's is nearly dead and next Sunday's is normal. |

### 1.2 The canonical unit — **chair-minutes**

One unit of capacity × one minute of published bookable time. It is the only unit that survives
translation across all six adapter families, and it is the unit that multiplies cleanly by price
(revenue per minute), which is what §3 needs.

Every adapter emits the same record:

```
{ unitId, dayOffset, envelopeMin, openMin }
```

* `unitId` — one **servable unit**: a staff chair, a class's seat-block, a service crew, a table-turn.
  Must be stable across scans (used for dispersion and ghost-staff detection).
* `dayOffset` — 1..7 days ahead. **Day 0 (today) is excluded**: it is half-consumed at scan time and
  not comparable across businesses scanned at different hours.
* `envelopeMin` — published bookable minutes for that unit that day (§1.3).
* `openMin` — bookable minutes still open.

`envelopeMin − openMin` is *booked-or-blocked*. **We never observe "booked" directly.** It is always
a residual, and the report says so.

### 1.3 Capability classes and their conversions

| Class | Platforms | What is exposed | Conversion to chair-minutes | Confidence ceiling |
|---|---|---|---|---|
| **C1** slot grid | Booksy, Vagaro, Fresha, GlossGenius, Square, Acuity | per-staff slot grid | `open = Σ openSlots × slotMinutes`; `env` from envelope (§1.4) | **0.95** |
| **C2** class capacity | Mindbody, Momence | class duration, capacity K, spots left S | `env = D×K`, `open = D×S` | **0.90** |
| **C3** arrival window | Housecall Pro, Jobber, ServiceTitan | 2–4h windows, crew count | `env = windowMin × crews`, `open = windowMin × crewsFree` | **0.75** |
| **C4** reservation | OpenTable, Resy | which times have availability for one party size | unit = one seating time-point, capacity 1 turn; `env = turnMin × offered`, `open = turnMin × available` | **0.65** |
| **C5** next-available | Zocdoc-style clinics | a single date | **no chair-minutes exist** — lead-time proxy only (§1.7) | **0.40** |

**C1 tops out at 0.95, not 1.00, and no scrape ever reaches 1.00.** Even a perfect grid cannot show
owner-placed blocks, walk-ins, or phone bookings. Only an owner-verified envelope and price lift a
business to 1.00. This is a structural cap, not a tuning knob — it is the arithmetic form of the
sentence "we measure your online-bookable time, not your shop."

**C4 caveat we print on the artifact:** availability is party-size dependent, and we cannot see table
count. The C4 score is a *seating-availability* ratio, not an occupancy ratio. We always disclose the
party size scanned ("checked for a table of 2").

### 1.4 The denominator — **published capacity envelope**, not clock hours

> If a shop publishes 4 online hours a day, it is **not** 50% vacant. It is a shop with a 4-hour
> online window, and the denominator is 4 hours.

For each `(unit, day-of-week)`, the envelope is the **p90 of the offered-span minutes observed over
the trailing R = 6 weeks**.

* Not `max` — one freak 9pm booking permanently inflates the denominator.
* Not `median` — most days carry some blocked time, so the median under-counts true capacity.
* p90 is the robust "how wide does this chair actually open" statistic.

Two problems this solves that a snapshot cannot:

1. **A fully booked day shows zero slots today.** Without history you compute 0/0 and the Slammed
   business — your highest-value prospect — falls off the map entirely.
2. **Blocked lunch hours** get absorbed into the envelope over time rather than being counted as
   vacancy every single week.

Fallback when there is no history: a niche default envelope, which drops `envelopeEvidence` to
**0.55** and, if there is *zero* observed history, triggers a hard refusal.

**Unit eligibility** (this is where phantom capacity gets killed). A unit counts on a given DOW only if:

* it has been seen offering ≥1 slot on that DOW in the trailing window (else: staff doesn't work Tuesdays), **and**
* it has been seen on ≥2 distinct days in the last 14 (else: ghost profile / brand-new hire), **and**
* it is **not** 100% vacant for 14+ consecutive days (that is a departed or fully-blocked employee, not an empty chair).

**Days with `env = 0` drop out of both the numerator and the denominator.** Closed days are a
*coverage* fact, not a *vacancy* fact — and a day the shop is known-closed does not count against
horizon coverage either (that fix is why Example A scores C = 0.95 rather than 0.81).

### 1.5 Multi-staff: is 1-of-5 barbers booked solid "80% vacant"?

**Decision: yes — pool chair-minutes across units. The headline is capacity-weighted, not
headcount-weighted. But it never ships alone.**

Justification: the headline number exists to *size an offer*. The offer is sold against sellable
capacity, and 4 idle chairs is 4 chairs' worth of sellable capacity regardless of how the fifth is
doing. Any headcount-based alternative ("60% of staff are busy") cannot be multiplied by a price and
so cannot produce Empty-Chair Revenue at all.

But pooled vacancy is *ambiguous about cause*, and that ambiguity is what gets you thrown out of a
meeting. So every score ships with:

* **per-unit vacancy** (each chair listed), and
* an **`uneven_chairs` flag**: fires when `max(unit utilisation) ≥ 0.75` **and** `shop utilisation ≤ 0.60`.

When `uneven_chairs` fires, **the pitch changes and the copy must change with it**:

* ❌ "You have a demand problem."
* ✅ "Marcus is booked 89% solid. Your other two chairs are running 47% and 59% open. That's not a
  marketing problem — that's a rebooking-and-ramp problem, and it's cheaper to fix."

That is a *better* sale than the generic one, and the owner already knows it is true, which is the
whole OpenChair thesis.

### 1.6 Time decay — empty tomorrow ≠ empty next Sunday

A slot 1 day out has roughly one day of remaining sell-through. A slot 7 days out has seven. Weighting
them equally systematically flatters far-horizon vacancy and makes every business look emptier than
its owner experiences it.

**Prior (ships at launch):** exponential decay, **half-life 3 days**, over `t = 1..7`:

```
w(t) = 2^(−(t−1)/3)
```

| t (days ahead) | 1 | 2 | 3 | 4 | 5 | 6 | 7 |
|---|---|---|---|---|---|---|---|
| `w(t)` | 1.000 | 0.794 | 0.630 | 0.500 | 0.397 | 0.315 | 0.250 |
| normalised | 0.257 | 0.204 | 0.162 | 0.129 | 0.102 | 0.081 | 0.064 |

Half-life 3 chosen so day 4 is worth exactly half of day 1 and day 7 exactly a quarter — round numbers
you can defend out loud without a slide.

**Empirical override (preferred as soon as data allows, and this is the defensibility win):**

```
w(t) = 1 − fillIn(t)
```

where `fillIn(t)` is the observed fraction of slots standing open at horizon `t` that get booked before
that day arrives, measured per niche across our own corpus. This makes the weight the **residual
vacancy probability** — an auditable empirical quantity rather than a taste parameter. Switch a niche
over once it has ≥100 businesses × ≥4 weeks of nightly scans. Until then the exponential prior stands,
and the weights are printed on the report.

**What we deliberately do NOT do:** day-of-week *demand* weighting in the headline (discounting an
empty Sunday because Sundays are slow). It double-counts with the Dead-Zone Map, it is unauditable by
the owner, and it is the exact kind of hidden adjustment that destroys trust when discovered. DOW
structure is the Map's job.

### 1.7 C5 — next-available-date-only businesses

There is no grid, so there are no chair-minutes, and **we must not invent them**. We map lead time to
a monotone, rank-preserving proxy calibrated by regressing observed `V` on lead time for businesses
that expose *both* a grid and a next-available date:

| lead days | 0 | 1 | 2 | 3 | 5 | 7 | 10 | 14 | 19 | 21 | 30 | 45 |
|---|---|---|---|---|---|---|---|---|---|---|---|---|
| `V̂` | 0.750 | 0.550 | 0.450 | 0.380 | 0.280 | 0.220 | 0.150 | 0.100 | **0.070** | 0.060 | 0.040 | 0.040 |

Interpolated **log-linearly in lead days** (the gap between 1 and 2 days matters enormously; between
20 and 21 it is noise).

**Rules of use, enforced in code:** C5 businesses are capped at confidence 0.40 → display band `low` →
**bucket and lead time only, never a Vacancy Score shown to two significant figures and never dollars.**
For a C5 business the lead time *is* the pitch and it is stronger than any score we could compute:
"next available: 19 days."

### 1.8 The final formula

Let `U(t)` be the eligible units on day `t`, `H = 7`:

$$
V \;=\; \frac{\sum_{t=1}^{H} w(t)\sum_{u \in U(t)} \mathrm{Open}(u,t)}{\sum_{t=1}^{H} w(t)\sum_{u \in U(t)} \mathrm{Env}(u,t)}
\qquad\qquad
S \;=\; \mathrm{round}\!\left(100\,V\right),\; S \in [0,100]
$$

with `w(t) = 2^(−(t−1)/3)`, days where `Σ Env = 0` dropped from both sums, and units filtered by the
eligibility rule of §1.4.

**This is a ratio of weighted sums, not a mean of daily ratios.** A Thursday with a 30-minute
denominator (one chair, one leftover half-hour) must not swing the headline. Mean-of-ratios lets it.

Complements:

* `V_raw` — the same ratio with `w(t) = 1`. Shown on the artifact next to `V` so the weighting is
  visible, never hidden.
* `openMinutes` — **unweighted** total. This is what §3 multiplies by price. *Money is money whatever
  day it sits on; the decay is a prioritisation device, not an accounting one.*

### 1.9 Worked examples (real program output)

#### A — Barbershop, 3 chairs, Booksy (C1)

Envelope 09:00–18:00 = 540 min/chair/day, open Tue–Sat, closed Sun/Mon, 30-min slot pitch.

```
  open slots (7d)      : 105  (52.5 h of 135 h envelope)
  unweighted vacancy   : 38.9%   -> score 39
  TIME-WEIGHTED vacancy: 42.9%   -> VACANCY SCORE 43
  daily:
    t=1  env  1620m  open   840m  v=51.9%
    t=2  env  1620m  open   810m  v=50.0%
    t=3  env  1620m  open   780m  v=48.1%
    t=4  env  1620m  open   480m  v=29.6%
    t=5  env  1620m  open   240m  v=14.8%
    t=6  env     0m  open     0m  v=closed
    t=7  env     0m  open     0m  v=closed
  per chair:
    A: vacancy 11.1%  utilisation 88.9%
    B: vacancy 46.7%  utilisation 53.3%
    C: vacancy 58.9%  utilisation 41.1%
  uneven-chairs flag   : true
  confidence           : C=0.95 (high)  parts={"ceiling":0.95,"freshness":1,"evidence":1,"coverage":1,"stability":1}
```

The decay moves 38.9% → **42.9%** because the near days are the empty ones — which is the honest
reading: this week is in trouble, not next week. `uneven_chairs` fires (chair A at 88.9% utilisation
against a 57% shop utilisation), so the pitch is a ramp/rebooking play, not a discount blast.

#### B — Yoga studio, Mindbody (C2, class capacity)

5 classes/day × 7 days, capacities 12–20, durations 45–75 min.

```
  seat-minutes open    : 15150 of 39480  (252.5 of 658 seat-hours)
  unweighted vacancy   : 38.4%   -> score 38
  TIME-WEIGHTED vacancy: 37.3%   -> VACANCY SCORE 37
  confidence           : C=0.72 (medium)  parts={"ceiling":0.9,"freshness":1,"evidence":1,"coverage":1,"stability":0.8}
```

Note the decay moves the score **down** here (37.3% < 38.4%): the near days are the *fuller* ones.
The weighting is not a thumb on the scale in one direction — it just relocates emphasis to the days
that are about to expire.

#### C — Dentist, next-available-only (C5)

```
  next available       : 19 days
  vacancy PROXY        : 7.0%  (rank-preserving only, not a measurement)
  confidence           : C=0.4 (low)
  display permitted    : bucket+slot_count_only
  UNSOLD VALUE         : SUPPRESSED (no_price_source)
  bucket               : slammed  [lead_time] next_available_19d
```

The product refuses to show a Vacancy Score or a dollar figure and pitches the lead time instead.

---

## 2. Confidence / data-quality score

Blocked slots and staff days off are invisible. Therefore **no score ships without a confidence band**,
and some scores do not ship at all.

### 2.1 The formula

```
C = ceiling(capability)
    × freshness(scanAgeHours)
    × envelopeEvidence
    × horizonCoverage
    × rosterStability
    × Π anomalyPenalties          , clamped to [0,1]
```

Multiplicative because the failure modes are independent **and any single one of them is on its own
sufficient to make the number wrong**. An additive/averaged score lets four healthy factors hide one
fatal one — precisely the behaviour you cannot afford here.

| Factor | Definition | Values |
|---|---|---|
| **ceiling** | adapter capability class | C1 0.95 · C2 0.90 · C3 0.75 · C4 0.65 · C5 0.40 |
| **freshness** | piecewise-linear in scan age (hours) | 24h → 1.00 · 48h → 0.90 · 72h → 0.75 · 168h → 0.40 · 336h → 0.00 |
| **envelopeEvidence** | `min(1, observedWeeks / 4)`; `0.55` if the envelope came from a niche default | 0.25 … 1.00 |
| **horizonCoverage** | weight-weighted fraction of *expected-open* days successfully parsed | 0 … 1 |
| **rosterStability** | `1 − min(0.4, 2 × churn)`, churn = \|units today − median(units,14d)\| / median | 0.60 … 1.00 |

**Anomaly penalties** (multiplicative, stackable):

| Flag | × | Meaning |
|---|---|---|
| `suspect_total_blackout` | 0.50 | `V ≥ 0.97` on every day — far likelier closed/holiday/parse-break than desperate |
| `suspect_walk_in` | 0.60 | grid pinned near-empty, lead time ≈ 0, unusually stable week-over-week |
| `captcha_degraded` | 0.70 | bot mitigation served a partial page |
| `multi_location` | 0.70 | one booking page, staff at more than one address |
| `release_limited` | 0.85 | platform publishes only `k < 7` days |
| `dst_unresolved` | 0.85 | scan window spans a DST transition without tz metadata |
| `slot_pitch_changed` | 0.90 | modal slot duration moved week-over-week (menu change or parser drift) |
| `ghost_staff_present` | 0.90 | a 100%-vacant-for-14-days unit is in the set |

### 2.2 Display bands

| C | Band | What the UI may render |
|---|---|---|
| ≥ 0.75 | **High** | Score + range + dollars |
| 0.50–0.75 | **Medium** | Score + *widened* range + dollars |
| 0.35–0.50 | **Low** | Bucket + raw slot count only. **No dollars.** |
| < 0.35 | **Refuse** | "Not enough data — rescanning tonight." Excluded from lists, Pitch and Map. |

Confidence is rendered as a labelled band with the *reason* attached, never as a bare decimal. The
consultant needs "based on 6 weeks of scans, page read 8 hours ago", not "0.95".

### 2.3 When the product must REFUSE (hard gates, independent of `C`)

1. `priorSuccessfulScans < 2` — one scan is an anecdote.
2. `horizonCoverage < 0.5` — over half the week unparsed.
3. `scanAge > 14 days`.
4. Envelope is a niche default **and** `observedWeeks = 0` — we invented the denominator.
5. `captcha_degraded` — a partial parse looks exactly like a booked-out shop.
6. `suspect_total_blackout` — otherwise this business **tops the 8am Hungry list** and the consultant
   calls a shop that was shut for a public holiday. This is the single highest-embarrassment path in
   the product and it is hard-gated, not confidence-gated.
7. `C < 0.35`.

Plus two **display caps** that permit the pitch but forbid the dollars: `suspect_walk_in` and
`multi_location` are downgraded to `bucket + slot_count_only` at any confidence.

Real output:

```
  fresh scan but only 1 prior success  C=0.712  refuse  REFUSE need_two_successful_scans
  stale 10-day-old scan                C=0.217  refuse  REFUSE confidence_below_floor
  booking window only 3 of 7 days      C=0.339  refuse  REFUSE horizon_coverage_below_50pct,confidence_below_floor
  captcha-degraded page                C=0.598  refuse  REFUSE bot_mitigation_partial_parse
  total blackout (holiday?)            C=0.475  refuse  REFUSE total_blackout_needs_human_check
  suspected walk-in shop               C=0.57   medium  bucket+slot_count_only
  restaurant, 2 days old               C=0.585  medium  score+wide_range+dollars
```

Note row 1: `C = 0.712` is a perfectly healthy number and the product still refuses. **Hard gates
outrank the score.**

---

## 3. Empty-Chair Revenue

### 3.1 Two numbers, deliberately

| Number | Definition | Role |
|---|---|---|
| **Unsold Value** | `openMinutes × price band` | **Headline.** A pure arithmetic restatement of observable facts: minutes on their page × prices on their page. |
| **Recoverable Revenue** | `Unsold Value(low end) × 15–35%` | Sizes the offer. Never the headline. |

The split exists because "vacancy × price" as a single figure silently asserts 100% sell-through,
which nobody achieves, which any owner will call out in the first thirty seconds. Splitting it lets us
put a big, true, unattackable number in front of them *and* a small, honest, credible one.

### 3.2 The rate: revenue per bookable minute

Not "average ticket". `r = price ÷ durationMin`, and drawn from **services whose duration matches the
modal open slot (±25%)**. Averaging the whole menu lets a $450 / 240-min bridal package price a 30-min
beard trim. Guards:

* Drop services with `ppm > 3 × median ppm` (packages, memberships, typos).
* Require ≥3 slot-matched services, else fall back to the full sane menu and tag it as such.
* Use **p25 / p50 / p75** of the pool, never mean, never max.

### 3.3 The 3-tier price-source model

| Tier | Source | Conf tag | Availability | Requirement |
|---|---|---|---|---|
| **T1** | Owner-verified / consultant-entered | **1.00** `owner-verified` | after the meeting | — |
| **T2** | Published service menu on the same booking page | **0.85** `published-menu` | default at prospecting time | ≥1 priced service with a duration |
| **T3** | Cohort median (niche+metro) | **0.50** `cohort median (scope, n=…)` | fallback | n ≥ 12 metro, else n ≥ 40 national, else **no dollars** |

T2 is the workhorse and it is the *reason the pitch works*: the prices are theirs, printed on their
own page. T1 is a feature, not a gap — "tell me your actual average ticket and I'll re-run it" is a
meeting, and correcting our number is the fastest way an owner buys into it.

**T3 + `C < 0.60` → dollars suppressed entirely.** A guessed price on a shaky score is two guesses
multiplied together.

### 3.4 Why a range, and how it is constructed

A point estimate invites exactly one response: "that's wrong." A range invites "…huh, probably."
More importantly, a point estimate is a **false claim about our precision**, and we have two genuinely
independent uncertainties:

**(a) Open-minute band.** The observed open minutes are near-certain; what is uncertain is whether
they were *truly sellable* (blocks, walk-ins, day-off artefacts). So the band is modest and widens as
confidence falls:

```
ρ = 0.05 + 0.45 × (1 − C)          →  C=0.95: ±7.3%   C=0.72: ±17.6%   C=0.60: ±23%
```

**(b) Price band.** p25 → p75 of matched services (or cohort IQR at T3).

```
UnsoldLow  = openMinutes × (1 − ρ) × r_p25
UnsoldHigh = openMinutes × (1 + ρ) × r_p75
```

**Presentation rules:**

* Round **both** endpoints **down** to the nearest $50. We never overstate, at either end.
* If `High / Low > 2.5`, **do not show dollars at all** — show the slot count. A range that wide is
  decoration, not information, and an owner will read it as an admission you don't know.
* Recoverable is computed off the **bottom** of the Unsold range only. Multiplying the top of a price
  band by the top of a sell-through band is how forecasts become fiction.
* Always print the assumed ticket alongside: *"we assumed a $30–$37.50 half-hour service."* That single
  line converts your biggest attack surface into their invitation to correct you.

### 3.5 Worked output

**Barbershop (T2, C = 0.95):**

```
  price source         : T2 published-menu (slot-matched)  n=3  $/min p25=1.000 p50=1.167 p75=1.250
  assumed 30-min ticket: $30 - $37.5 (mid $35)
  open-minute band rho : +/-7.3%   range ratio 1.45x (cap 2.5x)
  UNSOLD VALUE         : $2900 - $4200
  RECOVERABLE (15-35%) : $400 - $1000
```

**Yoga studio (T3 cohort, C = 0.72):**

```
  price source         : T3 cohort median (niche+metro, n=23)  $/min p25=0.367 p50=0.433 p75=0.533
  UNSOLD VALUE         : $4550 - $9500
  RECOVERABLE (15-35%) : $650 - $1600
```

**Dentist (C5):** `UNSOLD VALUE : SUPPRESSED (no_price_source)`.

### 3.6 Exact wording

**Defensible — ship these:**

> "52.5 hours of online-bookable time on your Booksy page went unsold in the next 7 days. At your own
> published prices that is **$2,900–$4,200 of retail value**. Fill a third of it and that's $400–$1,000."

> "$1,600–$2,300 of online-bookable time went unsold this week."

> "Your page showed 38 open slots in the next 7 days — 22 of them Tuesday to Thursday afternoons."

> "Next available appointment on your booking page is 19 days out. You're turning people away — that's
> a capacity problem, not a marketing problem."

> "We assumed a $30–$37.50 half-hour service, from your own menu. If that's off, tell me and I'll re-run it."

**Not defensible — never ship these:**

| ❌ Wording | Why it is indefensible |
|---|---|
| "Your shop was **empty**." | We see the online grid, not the shop. Walk-ins, phone bookings and blocks are invisible. One walk-in-heavy barber destroys the meeting. |
| "You **lost** $2,340 last week." | Asserts demand existed for that exact time. We cannot observe demand. |
| "You **could have made** $2,340." | Asserts 100% sell-through. Nobody fills 100%. |
| "$2,340." (a point estimate) | Claims a precision we do not have; invites a single-number rebuttal. |
| "Your competitors are 80% booked." | We cannot see their blocks either. Symmetric ignorance. |
| "You're **only 41% booked**." | "Booked" is a residual we never observed, phrased as a measurement. Say *open*, not *booked*. |

**The mandatory disclosure line, on every artifact:**

> *Measured from publicly visible availability on [platform] between [date] and [date]. Counts
> online-bookable time only — walk-ins, phone bookings, and time you blocked off are not visible to us
> and are not included. Prices are your own published menu prices.*

---

## 4. Bucket thresholds

### 4.1 Critique of Hungry ≥60% open / Slammed ≥95% booked

**Four problems.**

1. **They encode a hidden assumption that all niches have the same equilibrium utilisation.** They do
   not. A dentist who books three weeks out and holds slots for emergencies sits at 55–65% open on a
   7-day horizon *while thriving*. A Saturday-driven barbershop at 60% open is in real trouble. The
   same threshold labels a healthy clinic "Hungry" and hands the consultant a pitch the owner will
   laugh at.
2. **95% booked is nearly unreachable against our denominator.** The envelope necessarily includes
   lunch, admin and buffer time we cannot see, so genuine sell-outs land around 80–90% booked. A
   literal 95% gate would leave the Slammed bucket almost empty — and the Slammed bucket is *the half
   of the brief that doubles the market*.
3. **Knife-edge boundaries.** 59% and 61% get opposite pitches with no meaningful difference, and the
   8am list flip-flops nightly as normal noise crosses the line. Consultants notice this immediately
   and stop trusting the list.
4. **They leave no middle.** Everything between 5% and 60% open is unclassified — which in practice is
   *most of the market* and, as argued below, the best retainer bucket you have.

Worth noting: our own worked barbershop at **42.9% open** is not Hungry under the brief's rule, and
correctly so — its real story is `uneven_chairs` plus a Tuesday dead zone, not "empty shop."

### 4.2 Recommendation: **niche-relative percentile with absolute guardrails and hysteresis**

Cohort = **niche × metro**, requiring `n ≥ 30` businesses scanned at `C ≥ 0.6`; else niche × national;
else fall back to absolute thresholds and *say so on the artifact*.

| Bucket | Rule |
|---|---|
| 🔴 **Hungry** | `percentile ≥ 70` **AND** `V ≥ 0.40` |
| 🟡 **Steady** | everything else (percentile 15–70) |
| 🟢 **Slammed** | (`percentile ≤ 15` **AND** `V ≤ 0.20`) **OR** `next-available ≥ 10 days` |

* **Percentile is the primary signal** — it answers the only question that matters: *is this business
  emptier than its neighbours in the same trade?* That is also the sentence that opens the call.
* **Absolute guardrails stop the percentile from lying at the tails.** In a booming cohort, someone is
  always in the top 30% — the `V ≥ 0.40` floor stops us calling a healthy business Hungry. Symmetrically
  the `V ≤ 0.20` ceiling stops us calling a mediocre business Slammed just because its cohort is worse.
* **Lead time overrides everything.** `next-available ≥ 10 days` → Slammed regardless of `V`. Lead time
  is the most robust signal we have: it survives blocked slots, walk-ins and a broken denominator. It
  also catches the `release_limited` case where a business only publishes 5 days of bookings, so its
  grid looks open while it is actually sold out.
* **Hysteresis:** a business must clear a boundary by ≥5 percentile points, or hold the new side for two
  consecutive scans, before its bucket changes. Buckets carry a `since` date. This is what makes the
  8am list feel like a list of businesses rather than a random number generator.

### 4.3 The third bucket — 🟡 **Steady** — and what you sell it

Steady is the biggest bucket, and commercially it is the best one:

* **Hungry churns.** A genuinely desperate business is often a broke business. They buy once and cancel.
* **Slammed converts high but has a short ceiling.** You sell waitlist automation, deposit gates and a
  price-increase justification — high-value, largely one-time work.
* **Steady is where the retainer lives.** A Steady business has a respectable weekly average *and a
  chronically dead block* — the Tuesday 1–4pm in the Map. That is a specific, non-insulting,
  immediately actionable finding.

**The Steady pitch:** *"You're doing fine overall — 57% booked, right around the middle for Tampa
barbershops. But Tuesday and Wednesday afternoons have been dead for eight straight weeks: nine chair-
hours a week, every week. That's the piece I'd go after, and it's the piece I'd keep an eye on."*
→ Dead-Zone offer kit (OTO3) + monitoring retainer (OTO2). This is the highest-LTV path in the product,
and it does not exist under two absolute buckets.

Real output — both worked businesses land in Steady, which under absolute thresholds would have been
mislabelled or unclassified:

```
  barbershop: bucket steady  [percentile(niche+metro, n=40)] p58
  yoga      : bucket steady  [percentile(niche+metro, n=34)] p47
  dentist   : bucket slammed [lead_time] next_available_19d
```

---

## 5. Dead-Zone Map

### 5.1 Structure

**7 days-of-week × 7 two-hour bins** (07–09, 09–11, 11–13, 13–15, 15–17, 17–19, 19–21) = **49 cells**,
in **business-local wall-clock time**, DST-corrected. Fixed 2-hour bins internally (statistically clean,
equal-width, comparable across businesses); friendly labels in the UI ("Tuesday early afternoon").

**Cell statistic — pooled vacancy:**

$$
v_{c} \;=\; \frac{\sum_{\text{weeks}} \mathrm{Open}_c}{\sum_{\text{weeks}} \mathrm{Env}_c}
$$

Pooled across trailing weeks *and* staff — **not** a mean of weekly ratios, for the same
small-denominator reason as §1.8.

**Eligibility (a cell is grey and unpitchable unless all three hold):**

* ≥ 3 distinct weeks with data,
* ≥ 180 total envelope-minutes,
* ≥ 2 staff-days contributing (multi-staff businesses only).

### 5.2 Chronic dead zone vs. noise — three gates, all shipped

With 49 cells, testing at α = 0.05 hands you **~2.5 fake dead zones per shop, every shop**. Walking
into a meeting and pointing at a Thursday that is actually random is the fastest way to lose the room.
So:

| Gate | Rule | Why |
|---|---|---|
| **1 — effect size** | `v_c ≥ V̄ + 0.15` **AND** `v_c ≥ 0.50` | Must be both *absolutely* empty and *relatively worse than the shop*. In a shop that is dead everywhere, the whole shop is the dead zone and the pitch is different. |
| **2 — consistency** | cell beat shop vacancy in ≥ 75% of observed weeks | This is the gate owners find convincing: *"every single Tuesday for eight weeks."* |
| **3 — significance** | one-sided sign test on `k of n` weeks, `H₀: p = 0.5`, then **Benjamini–Hochberg FDR 0.10** across all eligible cells | Kills the multiple-comparisons false positives. |

A sign test rather than a t-test: vacancy fractions are bounded, skewed and heteroscedastic across
cells, and the thing we actually want to claim is *"it happens every week"*, which is exactly a sign
test. It is also the version a consultant can explain.

**Consequence you must accept and disclose:**

```
  n=4 weeks  k=3: p=0.3125   k=4: p=0.0625
  n=6 weeks  k=5: p=0.1094   k=6: p=0.0156
  n=8 weeks  k=6: p=0.1445   k=7: p=0.0352   k=8: p=0.0039
```

**With 4 weeks of data, even a perfect 4-for-4 cannot reach p < 0.05.** So 4-week findings ship
labelled **PROVISIONAL** and 8-week findings **CONFIRMED**. Do not hide this — "provisional, I'll
confirm it in four weeks" is a *reason to keep scanning*, which is the retention product.

### 5.3 Zones and offers

Contiguous significant bins on the same day merge into one sellable **zone**. Real output:

```
  cells tested (eligible): 25
  dow bin  label          vacancy  k/n   p        gate1 gate2  status
    2   3  Tue 1-3p         78.1%  8/8  0.0039   true  true   confirmed
    2   4  Tue 3-5p         72.1%  8/8  0.0039   true  true   confirmed
    3   3  Wed 1-3p         56.9%  6/8  0.1445   false true   noise
  merged zones:
    Tue 13:00-17:00    vacancy 75.1%  541 open min/week  [confirmed]
       -> midweek-fill: Tue 13:00-17:00 is your dead zone.
          Midweek-only offer + 48h SMS to lapsed clients + Google offer post timed to that window.
```

Wed 1–3p is instructive: it *passes* the consistency gate (6/8) and has an elevated 56.9% vacancy, but
it oscillates around the shop mean rather than sitting below it, so it fails the effect-size gate and
`p = 0.1445` fails significance. **It is correctly rejected as noise.** That is the cell that would
have embarrassed you.

**Zone → offer mapping** (deliberately boring and auditable):

| Zone shape | Offer | Play |
|---|---|---|
| Weekday (Mon–Thu) midday, 10:00–18:00 | `midweek-fill` | Midweek-only offer + 48h SMS to lapsed clients + timed Google offer post |
| Ends by 10:00 | `early-bird` | Before-work pricing, nearby-employer partnership |
| Starts ≥ 17:00 (peak window empty) | `demand-problem` | Not a scheduling problem — reviews/pricing/listing. Lead with LossReel. |
| Spans ≥ 8 hours (whole day) | `verify-first` | **Do not pitch.** A full-day zone is almost always a closed day still published. Verify first. |
| `uneven_chairs` set | `chair-ramp` | Rebooking prompts + new-staff intro offer. Never a discount blast. |

The `verify-first` and `chair-ramp` branches exist purely as false-positive containment, and they are
the two that save the meeting.

---

## 6. The Ledger — 8-week trend

Metric: weekly **fill rate** `F_w = 1 − V_w`, computed on the **same envelope basis**, stamped with
`MODEL_VERSION` on every row.

> **A report rendered under `meter-1.0.0` is never re-rendered under `meter-1.1.0` without
> relabelling.** Silently mixing two definitions of "fill rate" inside one 8-week chart is the easiest
> way in this entire product to get caught telling a client something untrue.

### 6.1 Smoothing

Raw weekly points as dots, **plus a 3-week centred moving average** as the line. Centred, not trailing:
a trailing average makes the most recent point drift after the fact, and a client who screenshots
Monday's chart and compares it Friday will notice. The two most recent points have no complete centred
window, so they are drawn **dashed and labelled provisional**. The raw current-week point is always
shown unsmoothed — never smooth away the number the client came to see.

### 6.2 Seasonality and holidays

* **Cohort seasonal index.** `s_w = median_cohort(F_w) / median_cohort(F_all)` per week-of-year, per
  niche × metro. Report raw `F_w` and, when `n ≥ 20` at `C ≥ 0.6` for that week, cohort-adjusted
  `F*_w = F_w / s_w`. If the cohort is too thin, **no adjustment, and say so.**
* **Holiday weeks** (regional public-holiday calendar) and weeks with `C < 0.6` or coverage `< 0.7` are
  **marked, excluded from fitting, and drawn hollow — never deleted.** Deleting inconvenient weeks is
  what gets a consultant caught; showing them with a marker is what makes the rest believable.

### 6.3 Never claim a seasonal lift as yours

The only claimable form is a **difference-in-differences against a synthetic control cohort** of ≥20
comparable businesses in the same metro, scanned over the same weeks:

```
  week      fill   cohort  phase     usable  3wk-MA
  2026-W10   41.0%   44.0%  baseline  true     -
  2026-W11   39.0%   43.0%  baseline  true   41.0%
  2026-W12   43.0%   45.0%  baseline  true   41.0%
  2026-W13   41.0%   44.0%  baseline  true   45.3%
  2026-W14   52.0%   47.0%  campaign  true     -
  2026-W15   98.0%   51.0%  campaign  false    -      <- holiday week, excluded not deleted
  2026-W16   61.0%   50.0%  campaign  true     -
  2026-W17   68.0%   52.0%  campaign  true     -
  client  baseline->post: 41.0% -> 60.3%  (19.3 pts)
  cohort  baseline->post: 44.0% -> 49.7%  (5.7 pts)
  NET OF MARKET         : 13.7 pts   claimable=true
```

* ❌ **"Fill rate went 41% → 68%."** True and useless — most of a naive lift can be the season.
* ✅ **"Fill rate 41% → 60% (+19.3 pts). Comparable Tampa barbershops moved 44% → 50% (+5.7 pts) over
  the same weeks. Net of the market: +13.7 points."**

**+13.7 is a smaller number than +27, and it is worth vastly more**, because it is the one that
survives the client asking their brother-in-law.

Also **pre-register**: the baseline window and campaign start date are recorded and made immutable
*before* the campaign runs, so nobody can retroactively pick a flattering baseline. The report says so.

### 6.4 The attribution caveat line (ship verbatim)

> *This measures online-bookable availability on the booking platform, not total business. It cannot
> separate the effect of our work from seasonality, weather, pricing, staffing or anything you did
> yourself. Where at least 20 comparable businesses in this metro were scanned over the same weeks,
> their movement is shown alongside yours so you can judge how much of the change was the market.
> Baseline window and campaign start date were recorded before the campaign began and have not been
> changed.*

---

## 7. Anti-gaming and false-positive audit

The eight ways this metric gets you embarrassed in a real sales meeting, worst first.

### 1. The walk-in shop *(the #1 risk in the product)*
An old-school barber takes 70% walk-ins and deliberately keeps the online grid thin. We score him 85%
vacant. He says "I turned away six people Saturday." **Meeting over.**
**Mitigation:** (a) never the phrase "empty shop" — always "online-bookable"; (b) detect the signature —
high vacancy **+** near-zero lead time **+** unusually *low* week-over-week variance (a truly dead shop's
grid fluctuates; a walk-in shop's is pinned) → `suspect_walk_in`, ×0.60 confidence, **dollars
suppressed**; (c) the pitch flips to *"your online booking isn't carrying any weight — that's leaked
revenue too"*, which is still a sale; (d) the opener script **asks**: "how much of your book is walk-in?"
Make the owner correct you before he catches you.

### 2. Adapter drift / silent parse failure *(the highest-blast-radius risk)*
A platform changes its markup, the parser returns zero slots, and **every business on that adapter looks
100% booked** — or 100% open. Silent, systemic, and it poisons the whole 8am list.
**Mitigation:** per-adapter **canary businesses** with known-stable grids checked every run; a page
schema hash; a cohort distribution monitor that halts publication if a niche's median `V` jumps >15
points overnight; and refusal to publish any batch whose parse-success rate is <90% of its trailing
median. Fail closed, always.

### 3. Holiday or closure week
Shop shut for Thanksgiving → 100% vacancy → **top of the Hungry list** → consultant calls a closed shop.
**Mitigation:** `suspect_total_blackout` (`V ≥ 0.97` all days) is a **hard refusal**, not a confidence
penalty — the alert is suppressed entirely. Plus a regional holiday calendar and the ≥2-scans rule.

### 4. Phantom capacity — staff on holiday, or departed and still listed
A profile that never gets booked inflates the denominator forever and manufactures vacancy.
**Mitigation:** the §1.4 eligibility rule (must have offered a slot on that DOW recently; ≥2 distinct
days in 14); `ghost_staff` exclusion at 14 consecutive fully-vacant days; roster-churn factor in
confidence.

### 5. Booking-window truncation
The platform only publishes 5 days ahead. Days 6–7 have zero envelope. Naively that reads as vacancy;
naively excluded, it reads as full coverage.
**Mitigation:** all-units-zero from day *k* onward, consistent across scans → `release_limited`;
days drop from both sums; coverage falls; the artifact says *"measured over the 5 days your page
publishes."* And a long lead time with a truncated window routes to **Slammed**, not Hungry.

### 6. Wrong service priced
Menu contains "Bridal package $450 / 240 min"; the open slots are 30-minute beard trims. The dollar
figure comes out 3× reality and the owner spots it instantly.
**Mitigation:** slot-pitch-matched services only (±25% of modal slot), `ppm > 3× median` dropped,
p25–p75 band not max, and the **assumed ticket printed on the artifact** — converting the attack into
their correction.

### 7. Timezone and DST
Scanning from a different timezone shifts every block by an hour: you pitch a "Tuesday 1–4pm" dead zone
that is really 12–3pm, and the DST week corrupts week-over-week cell comparisons.
**Mitigation:** store every slot with the business's IANA zone and the UTC offset at that instant; bin
in local wall clock; `dst_unresolved` flag; unit tests on both transitions.

### 8. Multi-location / shared calendar
One Booksy page covers two shops, or a chain lists 30 staff of whom 20 work elsewhere. Capacity, and
therefore vacancy and dollars, are attributed to the wrong address.
**Mitigation:** per-staff address/geo check; `multi_location` flag at ×0.70 with a **display cap** —
bucket and slot count only, **no dollars**, until the unit set is attributable to one address.

**Runners-up worth logging:** new business with <2 weeks of history (refused by the 2-scan gate);
"next available" quoted for a service the practice rarely sells; marketplace pages listing only partner
staff; and a genuinely adversarial one — **a competitor or rival consultant could pad or fake-book a
grid to distort our reading, and because we are strictly read-only we cannot detect it.** We say so in
the limitations, and we keep the read-only guarantee, because "the tool booked a fake appointment" is
the one story that ends the launch.

---

## 8. Reference implementation

[`meter-reference.mjs`](./meter-reference.mjs) — plain ESM, Node 22, no TypeScript, no dependencies.
All pure functions: no I/O, no clock reads (the caller injects `scanAgeHours`).

**Exports:** `fromSlotGrid` · `fromClassSchedule` · `fromArrivalWindows` · `fromReservations` ·
`nextAvailableProxy` · `capacityEnvelope` · `isUnitEligible` · `horizonWeights` · `vacancyScore` ·
`confidenceScore` · `freshnessFactor` · `priceRate` · `emptyChairRevenue` · `revenueSentence` ·
`bucketBusiness` · `percentileRank` · `binomTailGE` · `detectDeadZones` · `mergeZones` ·
`recommendOffer` · `centeredMA` · `ledgerLift` · `ATTRIBUTION_CAVEAT`

### 8.1 Test run — real output

```
$ node --test meter-reference.mjs
✔ time-decay weights: half-life 3 days (9.1ms)
✔ closed days drop out of both sums (1.0619ms)
✔ near-horizon emptiness raises the weighted score above the raw score (2.7078ms)
✔ multi-staff pooling: one slammed chair does not rescue the shop (0.7809ms)
✔ confidence: C1 can never exceed 0.95 without owner verification (0.5175ms)
✔ confidence: hard refusals fire independently of the numeric score (0.4224ms)
✔ price: a $450 bridal package cannot price a 30-minute cut (0.5661ms)
✔ revenue: dollars suppressed for a C5 next-available business (0.907ms)
✔ revenue: cohort price at low confidence is refused (0.3572ms)
✔ bucket: long lead time overrides everything (0.4199ms)
✔ bucket: hysteresis holds a business near the line (0.3948ms)
✔ sign test: 4 weeks cannot reach significance, 8 weeks can (1.9022ms)
✔ dead zones: chronic Tuesday found, oscillating Wednesday rejected (4.4229ms)
✔ ledger: lift is only claimable net of the cohort (0.9001ms)
ℹ tests 14
ℹ pass 14
ℹ fail 0
ℹ duration_ms 337.8083
```

### 8.2 Full demo run — real output

```
$ node meter-reference.mjs
OpenChair Meter reference — meter-1.0.0
node v24.18.0

==================================================================
TIME-DECAY WEIGHTS  w(t) = 2^(-(t-1)/3), t = 1..7
==================================================================
  t      :      1     2     3     4     5     6     7
  w(t)   :  1.000 0.794 0.630 0.500 0.397 0.315 0.250
  norm   :  0.257 0.204 0.162 0.129 0.102 0.081 0.064
  sum w  : 3.8855

==================================================================
EXAMPLE A — Barbershop, 3 chairs, Booksy (C1 slot grid)
==================================================================
  open slots (7d)      : 105  (52.5 h of 135 h envelope)
  unweighted vacancy   : 38.9%   -> score 39
  TIME-WEIGHTED vacancy: 42.9%   -> VACANCY SCORE 43
  daily:
    t=1  env  1620m  open   840m  v=51.9%
    t=2  env  1620m  open   810m  v=50.0%
    t=3  env  1620m  open   780m  v=48.1%
    t=4  env  1620m  open   480m  v=29.6%
    t=5  env  1620m  open   240m  v=14.8%
    t=6  env     0m  open     0m  v=closed
    t=7  env     0m  open     0m  v=closed
  per chair:
    A: vacancy 11.1%  utilisation 88.9%
    B: vacancy 46.7%  utilisation 53.3%
    C: vacancy 58.9%  utilisation 41.1%
  uneven-chairs flag   : true
  confidence           : C=0.95 (high)  parts={"ceiling":0.95,"freshness":1,"evidence":1,"coverage":1,"stability":1}
  display permitted    : score+range+dollars
  price source         : T2 published-menu (slot-matched)  n=3  $/min p25=1.000 p50=1.167 p75=1.250
  assumed 30-min ticket: $30 - $37.5 (mid $35)
  open-minute band rho : +/-7.3%   range ratio 1.45x (cap 2.5x)
  UNSOLD VALUE         : $2900 - $4200
  RECOVERABLE (15-35%) : $400 - $1000
  bucket               : steady  [percentile(niche+metro, n=40)] p58
  sentence:
    "52.5 hours of online-bookable time on your Booksy page went unsold in the next 7 days. At your own published prices that is $2,900-$4,200 of retail value. Fill a third of it and that is $400-$1,000."

==================================================================
EXAMPLE B — Yoga studio, Mindbody (C2 class capacity)
==================================================================
  seat-minutes open    : 15150 of 39480  (252.5 of 658 seat-hours)
  unweighted vacancy   : 38.4%   -> score 38
  TIME-WEIGHTED vacancy: 37.3%   -> VACANCY SCORE 37
  confidence           : C=0.72 (medium)  parts={"ceiling":0.9,"freshness":1,"evidence":1,"coverage":1,"stability":0.8}
  price source         : T3 cohort median (niche+metro, n=23)  $/min p25=0.367 p50=0.433 p75=0.533
  UNSOLD VALUE         : $4550 - $9500
  RECOVERABLE (15-35%) : $650 - $1600
  bucket               : steady  [percentile(niche+metro, n=34)] p47

==================================================================
EXAMPLE C — Dentist, next-available-date only (C5)
==================================================================
  next available       : 19 days
  vacancy PROXY        : 7.0%  (rank-preserving only, not a measurement)
  confidence           : C=0.4 (low)  parts={"ceiling":0.4,"freshness":1,"evidence":1,"coverage":1,"stability":1}
  display permitted    : bucket+slot_count_only
  price source         : none (cohort-too-thin)
  UNSOLD VALUE         : SUPPRESSED (no_price_source)
  bucket               : slammed  [lead_time] next_available_19d
  sentence:
    "Next available appointment on your booking page is 19 days out. You are turning people away — that is a capacity problem, not a marketing problem."

==================================================================
REFUSAL CASES
==================================================================
  fresh scan but only 1 prior success  C=0.712  refuse  REFUSE need_two_successful_scans
  stale 10-day-old scan                C=0.217  refuse  REFUSE confidence_below_floor
  booking window only 3 of 7 days      C=0.339  refuse  REFUSE horizon_coverage_below_50pct,confidence_below_floor
  captcha-degraded page                C=0.598  refuse  REFUSE bot_mitigation_partial_parse
  total blackout (holiday?)            C=0.475  refuse  REFUSE total_blackout_needs_human_check
  suspected walk-in shop               C=0.57   medium  bucket+slot_count_only
  restaurant, 2 days old               C=0.585  medium  score+wide_range+dollars

==================================================================
DEAD-ZONE MAP — 8 weeks, 3 chairs, shop vacancy 43%
==================================================================
  cells tested (eligible): 25
  dow bin  label          vacancy  k/n   p        gate1 gate2  status
    2   3  Tue 1-3p         78.1%  8/8  0.0039   true  true   confirmed
    2   4  Tue 3-5p         72.1%  8/8  0.0039   true  true   confirmed
    3   3  Wed 1-3p         56.9%  6/8  0.1445   false true   noise
  merged zones:
    Tue 13:00-17:00    vacancy 75.1%  541 open min/week  [confirmed]
       -> midweek-fill: Tue 13:00-17:00 is your dead zone.
          Midweek-only offer + 48h SMS to lapsed clients + Google offer post timed to that window.

==================================================================
SIGN-TEST REFERENCE (H0: cell no worse than shop, p=0.5)
==================================================================
  n=4 weeks  k=3: p=0.3125   k=4: p=0.0625
  n=6 weeks  k=5: p=0.1094   k=6: p=0.0156
  n=8 weeks  k=6: p=0.1445   k=7: p=0.0352   k=8: p=0.0039
  => 4 weeks can never reach p<0.05 -> label PROVISIONAL. 8 weeks needs k>=7.

==================================================================
LEDGER — 8-week fill rate with difference-in-differences
==================================================================
  week      fill   cohort  phase     usable  3wk-MA
  2026-W10   41.0%   44.0%  baseline  true     -
  2026-W11   39.0%   43.0%  baseline  true   41.0%
  2026-W12   43.0%   45.0%  baseline  true   41.0%
  2026-W13   41.0%   44.0%  baseline  true   45.3%
  2026-W14   52.0%   47.0%  campaign  true     -
  2026-W15   98.0%   51.0%  campaign  false    -
  2026-W16   61.0%   50.0%  campaign  true     -
  2026-W17   68.0%   52.0%  campaign  true     -
  excluded             : [{"week":"2026-W15","why":"public_holiday_week"}]
  client  baseline->post: 41.0% -> 60.3%  (19.3 pts)
  cohort  baseline->post: 44.0% -> 49.7%  (5.7 pts)
  NET OF MARKET         : 13.7 pts   claimable=true

==================================================================
LEAD-TIME PROXY CURVE (C5)
==================================================================
  days :      0     1     2     3     5     7    10    14    19    21    30    45
  V^   :  0.750 0.550 0.450 0.380 0.280 0.220 0.150 0.100 0.070 0.060 0.040 0.040
```

---

## 9. Implementation notes for the host repo

* **Storage.** Follow the InstantClose *extend, don't duplicate* rule. Suggested net-new tables:
  `oc_scans` (one row per business per night: raw envelope/open minutes, coverage, anomalies,
  `model_version`), `oc_cells` (dow × bin weekly aggregates), `oc_scores` (V, C, bucket, revenue band —
  derived, recomputable), `oc_prices` (tier, source, ppm quantiles). Never a parallel "businesses"
  table if an existing entity fits.
* **Where it runs.** The Meter is pure computation over scan rows — it belongs in the Node service
  (`engine/bridge-service`), reading config via `src/config.js` and never `process.env`, with routes
  lazily imported per the defensive-loading pattern so a Meter fault 503s only the Meter.
* **Every stored score carries `model_version`.** Recompute-on-read, never mutate historical rows in
  place, and never re-render an old Ledger under a new model version without relabelling it.
* **`meter-reference.mjs` is the spec.** If the production implementation and this file disagree, the
  file is right until the doc and its pasted output are updated together.

---

## 10. Open calibration questions (before the first paid scan)

1. Empirical `fillIn(t)` curves per niche — replaces the exponential prior and turns the decay from a
   taste parameter into a measurement. **Highest-value calibration item.**
2. Regress observed `V` on next-available lead time for dual-exposure businesses to firm up the C5
   anchor table.
3. Measure realised sell-through for actual dead-zone campaigns to replace the assumed 15–35% band with
   a measured one.
4. Measure the true distribution of `V` per niche × metro to confirm the 70th/15th percentile cut points
   and the `V ≥ 0.40` / `V ≤ 0.20` guardrails.
5. Estimate walk-in prevalence per niche — it determines how aggressively `suspect_walk_in` should fire,
   and it is the largest single source of systematic bias in the product.
