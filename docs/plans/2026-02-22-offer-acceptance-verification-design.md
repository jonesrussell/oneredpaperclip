# Offer and Acceptance Process Verification — Design

**Date:** 2026-02-22  
**Goal:** Ensure the trading offer and acceptance process is in place via codebase audit and automated tests. After verification, fix gaps (build missing UI and/or improve UX) as needed.

## 1. Scope and user journey

**Scope:** Verify the flow end-to-end (audit + tests). Remediation (build/improve) is planned separately using the audit results.

**User journey (steps audited):**

| # | Step | Description |
|---|------|-------------|
| 1 | View campaign | User (non-owner) sees a public campaign and can open it. |
| 2 | Make an offer | Non-owner submits an offer (offered item + optional message) for the campaign’s current item. |
| 3 | Owner sees offers | Campaign owner sees pending offers (e.g. on campaign show, offers tab). |
| 4 | Accept or decline | Owner can accept (creates trade, notifies offerer) or decline (notifies offerer). |
| 5 | Trade created | After accept, a trade exists in PendingConfirmation; both parties can see it. |
| 6 | Confirm trade | Offerer and campaign owner each confirm the trade (order doesn’t matter). |
| 7 | Trade completed | When both have confirmed, trade status is Completed and campaign `current_item_id` becomes the offered item. |

Each step is mapped to: route(s), controller, action(s), policy, and UI (page/component and whether the action is wired).

**Out of scope for this design:** Implementing missing UI or UX improvements; that follows in a separate implementation plan.

## 2. Audit method and deliverables

**Method (top-down):**

- For each journey step, trace in the codebase: routes (named route + method), controller (delegation to actions), actions (inputs, side effects, errors), policies (who may act), and UI (Inertia page/component, wired vs read-only).
- Audit is read-only; gaps are recorded, not fixed in the audit phase.

**Deliverables:**

1. **Audit table** — One row per step; columns: Step, Route(s), Controller → Action, Policy, UI location, Wired? (Y/N), Notes. (Filled when audit is run.)
2. **Gap list** — Bullet list of missing or broken items. (Filled when audit is run.)
3. **Test plan** — See Section 3.

### Audit table (to be filled during implementation)

| Step | Route(s) | Controller → Action | Policy | UI location | Wired? | Notes |
|------|----------|---------------------|--------|-------------|--------|-------|
| 1. View campaign | | | | | | |
| 2. Make an offer | | | | | | |
| 3. Owner sees offers | | | | | | |
| 4. Accept / decline | | | | | | |
| 5. Trade created | | | | | | |
| 6. Confirm trade | | | | | | |
| 7. Trade completed | | | | | | |

### Gap list (to be filled during implementation)

- (Gaps will be listed here after the audit.)

## 3. Test strategy

**Goal:** Automated tests that lock in the offer → accept/decline → trade → dual-confirm journey.

**Existing coverage (keep and extend):**

- Accept: `AcceptOfferTest` — owner accepts, trade + notification; non-owner/declined/stale forbidden.
- Decline: `DeclineOfferTest` — owner declines, notification; non-owner/not pending forbidden.
- Confirm: `ConfirmTradeTest` — offerer/owner confirm, both → completed + campaign advances; third party forbidden; idempotent.
- API: `OfferApiTest`, `TradeApiTest` — JSON equivalents.

**Add or extend:**

1. **Create offer (web):** Feature test — authenticated user POSTs to `campaigns.offers.store` with valid payload; offer and offered item exist. Include validation failure and unauthenticated cases.
2. **Full journey (recommended):** One (or a small set of) feature test(s) that run the full flow via HTTP: create campaign → create offer as second user → accept as owner → confirm offerer → confirm owner → assert trade completed and campaign `current_item_id` updated.
3. **Gap-driven:** After audit, add tests for any step with no coverage.

**Not in scope:** E2E/browser tests; testing UI rendering in isolation. All in `tests/Feature/` (Pest).

## 4. Follow-up and handoff

**After the audit:** The gap list drives build (missing UI) and/or improve (copy, errors, notifications). Implementation of B/C is planned separately (e.g. in the same or a follow-up implementation plan).

**Handoff:**

- This design doc is the single source of truth; audit table and gap list are updated when the audit is run.
- Implementation plan (via writing-plans skill) will include: run audit and fill table/gap list, add/extend tests per Section 3, fix test failures, and optionally address gaps.

**Success criteria for verification phase:** Audit table and gap list completed; new/extended tests added and passing; design doc committed.
