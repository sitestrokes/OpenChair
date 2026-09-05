# OpenChair — Flow Diagrams

All diagrams are Mermaid. They render on GitHub, in the published blueprint artifact, and in most
markdown viewers.

Legend for lanes: **Radar** = crawler/adapters · **Meter** = scoring · **Ledger** = trend store ·
**Pitch** = artifact generator · **Discovery** = "does this business even have online booking?"

---

## 1. The master flow — S.E.A.T. end to end

```mermaid
flowchart TD
    A[Consultant: niche + city] --> B[Discovery: find businesses]
    B --> B1{Has public<br/>online booking?}
    B1 -- no --> B2[(park: not_bookable<br/>never re-scanned for 90d)]
    B1 -- yes --> B3[Detect platform<br/>route to adapter]
    B3 --> C[Radar: nightly scan<br/>one pass per business per day]
    C --> C1{Fetch OK?}
    C1 -- blocked/error --> C2[Backoff + circuit breaker<br/>serve last good, flag STALE]
    C1 -- ok --> D[Normalise to canonical<br/>bookable-minutes grid]
    D --> E[Meter: Vacancy Score<br/>+ confidence band]
    E --> F[Meter: Empty-Chair Revenue<br/>price source x vacancy = range]
    F --> G{Bucket}
    G -- "high vacancy" --> H1[RED: sell CUSTOMERS]
    G -- "middle" --> H2[AMBER: sell EFFICIENCY]
    G -- "near-full + long wait" --> H3[GREEN: sell CAPACITY]
    D --> I[Ledger: append daily aggregate<br/>+ weekly rollup]
    I --> J[Dead-Zone Map<br/>DOW x hour-block chronic vacancy]
    H1 --> K[Alert: 8am list]
    H2 --> K
    H3 --> K
    K --> L[Consultant opens Business Detail]
    L --> M[Pitch: one-pager / email / SMS<br/>variant matched to bucket]
    M --> N[LossReel handoff: 30s video]
    M --> O[Meeting booked]
    O --> P[Becomes a client]
    P --> Q[Ledger monthly re-scan<br/>= the retainer report]
    Q --> P
```

**The loop that matters commercially** is `P → Q → P`: the prospecting artifact and the retainer
report are the same screen, so the thing that won the client also keeps them.

---

## 2. Discovery pipeline — getting a business into the system

You cannot scan what you have not found. Discovery is a separate, cheaper, slower pipeline that runs
ahead of Radar and is heavily cached — the booking platform a shop uses rarely changes.

```mermaid
flowchart LR
    S1[Seed: niche + city] --> S2[Places/Maps source<br/>name, address, website, phone]
    S2 --> S3{Website present?}
    S3 -- no --> X1[(not_bookable)]
    S3 -- yes --> S4[Fetch homepage<br/>+ common booking paths]
    S4 --> S5[Booking-link signature match<br/>booksy · vagaro · fresha · square<br/>acuity · mindbody · momence ...]
    S5 --> S6{Signature hit?}
    S6 -- no --> S7[Widget / iframe / script sniff<br/>second pass]
    S7 --> S8{Hit?}
    S8 -- no --> X1
    S6 -- yes --> S9[Resolve booking URL<br/>+ platform + venue id]
    S8 -- yes --> S9
    S9 --> S10[Adapter capability probe<br/>slot-grid? class-capacity? next-available-only?]
    S10 --> S11[(business + booking_source<br/>status = verified)]
    S11 --> S12[Enqueue for nightly Radar]
```

Discovery results are cached **globally and shared across all tenants** — this is the primary cost
lever, and it is a schema decision, not an optimisation you bolt on later.

---

## 3. Radar scan — sequence, with the shared-scan dedupe

The single most important cost decision: two consultants watching the same Tampa barbershop trigger
**one** scan, not two.

```mermaid
sequenceDiagram
    autonumber
    participant SCH as Scheduler (nightly)
    participant Q as Job queue
    participant RD as Radar worker
    participant AD as Platform adapter
    participant PX as Proxy / browser pool
    participant SITE as Booking platform
    participant DB as Shared DB
    participant EV as Event bus

    SCH->>DB: select businesses due (last_scan < today, status=verified)
    Note over SCH,DB: DISTINCT business — not per tenant.<br/>N tenants watching one shop = 1 job.
    SCH->>Q: enqueue scan jobs (per-domain concurrency cap)
    Q->>RD: lease job
    RD->>DB: check cache (fresh scan within TTL?)
    alt fresh scan exists
        DB-->>RD: hit
        RD->>Q: ack, no fetch
    else needs fetch
        RD->>AD: fetch(bookingRef, horizonDays)
        AD->>PX: acquire session (tier1 http → tier2 headless → tier3 stealth+residential)
        PX->>SITE: GET availability (read-only, identified UA, rate-limited)
        alt blocked / 429 / challenge
            SITE-->>PX: challenge
            PX-->>AD: escalate tier or fail
            AD-->>RD: AdapterError(BLOCKED)
            RD->>DB: record failure, open circuit if threshold hit
            RD->>EV: emit scan.failed
        else ok
            SITE-->>PX: availability payload
            PX-->>AD: raw
            AD-->>RD: CanonicalGrid + capability + confidence inputs
            RD->>DB: write raw snapshot (compressed, short retention)
            RD->>DB: write vacancy_daily aggregate
            RD->>EV: emit scan.completed
        end
    end
    EV->>DB: Meter recomputes score / revenue / bucket
    EV->>DB: Ledger updates weekly rollup + dead-zone matrix
    EV->>DB: fan-out to every tenant watching this business
```

**Read-only guarantee** lives at the adapter/proxy boundary: adapters expose no write verb, and the
HTTP layer refuses any non-GET/HEAD request to a booking host. See the architecture doc for the
code-level guard.

---

## 4. The 60-second demo flow (the thing that sells it)

```mermaid
stateDiagram-v2
    [*] --> Search
    Search: Type "barbershop, Tampa"
    Search --> Streaming: press Enter
    Streaming: Pins drop progressively as businesses resolve
    Streaming: no spinner — results stream in
    Streaming --> Results
    Results: 47 pins, red to green by vacancy score
    Results --> Hungry: click the reddest pin
    Hungry: Real slot grid, 38 open slots highlighted
    Hungry: dollar range of unsold online-bookable time
    Hungry: Dead-Zone shows Tue 1-4pm chronically empty
    Hungry --> PitchRed: tap Pitch
    PitchRed: One-pager renders — grid + range + fill-these offer
    PitchRed --> Results: back
    Results --> Slammed: click a green pin
    Slammed: Next available in 19 days
    Slammed --> PitchGreen: tap Pitch
    PitchGreen: Offer FLIPS — waitlist, deposit gate, price rise, second chair
    PitchGreen --> [*]
```

Beat timing targets: search → first pins **< 4s**, full 47 pins **< 25s**, pin → detail **< 400ms**,
detail → rendered one-pager **< 3s**. If the demo cannot hit these on a webinar connection, the
launch loses its single strongest asset.

---

## 5. Two-bucket routing — one scan, two products

```mermaid
flowchart LR
    V[Vacancy Score + confidence] --> D{Confidence<br/>above floor?}
    D -- no --> U[Show insufficient data<br/>NEVER show a number]
    D -- yes --> B{Bucket by niche-relative percentile}
    B -- "high vacancy" --> R[RED · Hungry]
    B -- "mid" --> A[AMBER · Steady]
    B -- "low vacancy, long wait" --> G[GREEN · Slammed]

    R --> R1[Problem: unsold time]
    R1 --> R2[Offers: midweek promo, Google offer post,<br/>review velocity, LossReel, paid local]
    A --> A1[Problem: lumpy demand]
    A1 --> A2[Offers: dead-zone-only campaign,<br/>off-peak pricing, rebooking flow]
    G --> G1[Problem: leaking walk-aways]
    G1 --> G2[Offers: waitlist automation, deposit gate,<br/>price-increase justification, hire / second chair]

    R2 --> P[Pitch generator<br/>same data, different template + CTA]
    A2 --> P
    G2 --> P
```

The AMBER bucket is a recommendation from the scoring workstream, not in the original brief. It turns
the businesses that fall between the two thresholds from "no pitch" into "the easiest pitch", because
for a lumpy-demand shop the Dead-Zone Map alone *is* the whole offer.

---

## 6. Tenancy — global scan layer vs per-tenant layer

```mermaid
flowchart TB
    subgraph GLOBAL["GLOBAL layer — scanned once, shared by everyone"]
        GB[business]
        GS[booking_source]
        GO[scan_observation / raw snapshot]
        GD[vacancy_daily]
        GW[vacancy_weekly]
        GZ[dead_zone_matrix]
    end
    subgraph TENANT["PER-TENANT layer — what each consultant owns"]
        TW[watchlist / prospect]
        TN[notes, status, stage]
        TP[pitch artifacts]
        TR[client + ledger report config]
        TQ[quota / credits]
    end
    GB --- GS
    GS --- GO
    GO --- GD
    GD --- GW
    GW --- GZ
    TW -->|references| GB
    TP -->|snapshot-of| GD
    TR -->|white-label of| GW
    TQ -.->|gates which businesses<br/>a tenant may add| TW
```

Consequence for the business model: quota is charged on **watchlist adds and pitch generations**, not
on scans — because scans are shared. That is what protects margin when 500 launch-day buyers all pick
the same three big cities.

---

## 7. Alert — the 8am list

```mermaid
sequenceDiagram
    participant CR as Cron 08:00 tenant-local
    participant AL as Alert builder
    participant DB as DB
    participant CH as Channels (email / in-app / Telegram)
    CR->>AL: build list for tenant T
    AL->>DB: watchlist(T) joined to latest vacancy_daily
    DB-->>AL: rows + deltas since last alert
    AL->>AL: rank by absolute delta, then revenue, confidence floor applied
    AL->>AL: split RED / AMBER / GREEN, cap N per bucket
    AL->>CH: send digest with deep links to Business Detail
    Note over AL,CH: Nothing is auto-sent to the BUSINESS.<br/>Alerts go to the consultant only.
```

The product never contacts a prospect business on its own. That boundary is a compliance requirement
(TCPA/CAN-SPAM exposure sits with the consultant, and must stay there), not a missing feature.

---

## 8. Ledger — prospecting artifact becomes retainer report

```mermaid
flowchart LR
    W1[Week 1 scan: fill rate 41%] --> W8[Week 8 scan: fill rate 68%]
    W1 -.-> PRE[Pre-client framing:<br/>here is your unsold time]
    W8 -.-> POST[Client framing:<br/>here is what changed]
    PRE --> SAME[[Same component<br/>same data, different framing]]
    POST --> SAME
    SAME --> WL[White-label: consultant logo,<br/>colours, own domain — OTO2]
    WL --> MRR[Consultant bills 97-197/mo<br/>Vendor bills consultant a sub tier]
```

---

## 9. Data-quality guardrail — the canary

The scariest failure is not a crash. It is an adapter whose selector quietly broke, returning **0 open
slots**, making every shop in a city look slammed — and the consultant walks into a meeting with a
confidently wrong number.

```mermaid
flowchart TD
    SC[Scan result for platform P] --> M1[Compare against P's rolling baseline:<br/>mean vacancy, null rate, slot-count distribution]
    M1 --> M2{Population shift<br/>beyond tolerance?}
    M2 -- no --> OK[Publish scores]
    M2 -- yes --> ALARM[Freeze publishing for platform P]
    ALARM --> A1[Serve last-good, banner = STALE]
    ALARM --> A2[Page the operator, open adapter circuit]
    ALARM --> A3[Fixture diff: recorded shape vs live shape]
    A3 --> A4[Adapter fix + fixture refresh]
    A4 --> OK
```

---

## 10. Build sequence and phase gates

```mermaid
flowchart LR
    SPIKE[SPIKE: prove Radar reads<br/>2 real platforms at target success rate] --> G1{Go / no-go}
    G1 -- no-go --> PIVOT[Re-scope to API-available<br/>platforms only, or kill]
    G1 -- go --> P1[P1: Discovery + 2 adapters<br/>+ Meter + Business Detail]
    P1 --> P2[P2: Map/list hero screen<br/>+ buckets + 8am alerts]
    P2 --> P3[P3: Pitch generator<br/>+ one-pager PDF]
    P3 --> P4[P4: Ledger + Dead-Zone Map<br/>+ white-label]
    P4 --> P5[P5: four more adapters<br/>+ quota/billing + launch hardening]
```

The spike is the entire project risk. Everything downstream of it is ordinary product work.
