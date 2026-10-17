# OpenChair — Canonical Nav Contract

Single source of truth for the OpenChair rail. Following the house convention
from the Agentic Affiliates engine, where the nav is contracted in writing so the
active-state highlighting cannot drift between a controller and its view.

Brand: **OpenChair** · tagline **"Capacity radar"**. Display name resolves through `$ic_brand`.
Shell: `application/views/oc_shell/` (engine-wide — NOT inside this module, so
`whitelabel`, `setting`, `users` and `config` render in the same chrome).

## Active-state contract

Each rail item has a `key`. The controller passes `$page` to
`Oc_render_lib::render()`; the rail highlights the item whose `key === $page`.

| Controller method | `$page` key | Route |
|---|---|---|
| `today()`     | `today`     | `/openchair` |
| `radar()`     | `radar`     | `/openchair/radar` |
| `scan()`      | `radar`     | `/openchair/scan` — a scan belongs to Find businesses |
| `business()`  | `prospects` when watched, else `radar` | `/openchair/business/{id}` |
| `pitch()`     | `prospects` when watched, else `radar` | `/openchair/pitch/{id}` |
| `prospects()` | `prospects` | `/openchair/prospects` |
| `ledger()`    | `ledger`    | `/openchair/ledger` |
| `how()`       | `how`       | `/openchair/how-it-works` |
| `coverage()`  | `coverage`  | `/openchair/coverage` |
| `adapters()`  | `adapters`  | `/openchair/adapters` |
| `card()`      | — own chrome, no rail | `/openchair/card/{id}/{kind}` |

Common modules rendering into the same shell set: `settings`, `users`,
`whitelabel`, `config`, `profile`.

## Canonical rail (order, group, key, label, gate)

Since the signature visuals (docs/60-signature-visuals.md §4.7, PDF p.8). The
keys did not change; labels and grouping did. `Meter` and `Pitch` left the rail:
a business is reached from Find businesses or My pipeline, and its screen carries
the "Make pitch" and "Share card" actions.

**Top:** `+ New scan` button → `/openchair/scan`, and the Sample data flag when the
market is seeded.

### Work
| key | label | badge | gate |
|---|---|---|---|
| `today` | Home | `n due` — prospects with `next_due_on <= today` | — |
| `radar` | Find businesses | — | — |
| `prospects` | My pipeline | open prospects | — |
| `ledger` | Client reports | — | `$oc_can_ledger` |

### Agency
| key | label | gate |
|---|---|---|
| `how` | How it works | — |
| `coverage` | What we can see | — |
| `users` | Team & roles | admin |
| `whitelabel` | Brand & white-label | admin |
| `config` | API & webhooks | superadmin |
| `adapters` | Adapters | admin |

### Account
`settings` · Log out · Theme

**Foot:** "Agency plan · used of cap" per quota line.

## Two departures from ui/prototype.html, both deliberate

1. **`prospects` is added.** The prototype's rail omits it, but the watchlist is a
   v1 FE screen in `40-ux-spec.md` §2.2 and the pipeline is what turns a scan into
   a client.
2. **The Account group is added.** The prototype is a standalone demo with nowhere
   to navigate; this is a real app with the common modules in it.

## Gating rule

Follows the host engine's anti-mugging precedent (`$ic_can_offer_finder`): **a
rail item for a tier the user has not bought is not rendered.** No dimmed bait, no
upsell modal in the daily path. The `OTO2` tag on Ledger marks a tier the user
*has*, not one being dangled.

Gate flags are computed once in `Oc_render_lib::shell_flags()` so the rail is
identical on every page: `$oc_can_slammed`, `$oc_can_deadzone`, `$oc_can_ledger`,
`$oc_can_kit`. They all resolve TRUE until the OpenChair access-code map is
decided; at that point they become `Access_control_lib` calls and nothing else in
the shell changes.
