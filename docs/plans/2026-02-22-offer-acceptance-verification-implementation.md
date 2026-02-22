# Offer and Acceptance Verification — Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Run a top-down audit of the offer/accept/trade/confirm journey, fill the design doc’s audit table and gap list, and add/extend automated tests so the flow is covered and regressions are caught.

**Architecture:** Read-only audit per step (route → controller → action → policy → UI); record findings in `docs/plans/2026-02-22-offer-acceptance-verification-design.md`. Add or extend Pest feature tests for create-offer (web) and full journey; add gap-driven tests if the audit finds missing coverage.

**Tech Stack:** Laravel 12, Pest 4, Inertia (Vue), existing Actions and Policies.

**Design reference:** `docs/plans/2026-02-22-offer-acceptance-verification-design.md`

---

### Task 1: Audit steps 1–3 (view campaign, make offer, owner sees offers)

**Files:**
- Modify: `docs/plans/2026-02-22-offer-acceptance-verification-design.md` (audit table and gap list)

**Step 1: Trace step 1 (View campaign)**

In codebase find: route for viewing a single campaign (e.g. `campaigns.show`), controller method, any policy, and the Inertia page (e.g. `campaigns/Show.vue`). Confirm unauthenticated users can view public campaigns.

**Step 2: Trace step 2 (Make an offer)**

Find: route for storing an offer (e.g. `campaigns.offers.store`), `OfferController@store`, `StoreOfferRequest`, `CreateOffer` action, and the UI that submits the form (or note if missing). Check if “Make an Offer” on campaign show is wired to a form or modal and POST.

**Step 3: Trace step 3 (Owner sees offers)**

Find: where campaign show loads offers (e.g. `CampaignController@show` eager load), and that the offers tab in `campaigns/Show.vue` displays them. Note if only pending offers are loaded.

**Step 4: Update design doc**

Fill the first three rows of the audit table in `docs/plans/2026-02-22-offer-acceptance-verification-design.md`. For each step set: Route(s), Controller → Action, Policy, UI location, Wired? (Y/N), Notes. Add any missing or broken item to the Gap list (e.g. “Make an Offer button not wired to offer form”).

**Step 5: Commit**

```bash
git add docs/plans/2026-02-22-offer-acceptance-verification-design.md
git commit -m "docs: audit steps 1-3 for offer-acceptance verification"
```

---

### Task 2: Audit steps 4–7 (accept/decline, trade created, confirm, completed)

**Files:**
- Modify: `docs/plans/2026-02-22-offer-acceptance-verification-design.md`

**Step 1: Trace step 4 (Accept / decline)**

Find: routes `offers.accept`, `offers.decline`; `OfferController@accept`, `@decline`; `AcceptOffer`, `DeclineOffer` actions; `OfferPolicy`. Check campaign show offers tab for Accept/Decline buttons (or note missing).

**Step 2: Trace step 5 (Trade created)**

Confirm `AcceptOffer` creates a `Trade` with status PendingConfirmation and correct `offered_item_id` / `received_item_id`. Note where trades are displayed (e.g. campaign show trades tab).

**Step 3: Trace step 6 (Confirm trade)**

Find: route `trades.confirm`, `TradeController@confirm`, `ConfirmTrade` action, `TradePolicy`. Check if trades tab (or trade detail) has a Confirm button for offerer/owner (or note missing).

**Step 4: Trace step 7 (Trade completed)**

Confirm `ConfirmTrade` sets both confirmation timestamps and marks trade Completed, and updates `Campaign.current_item_id` to the offered item.

**Step 5: Update design doc**

Fill the remaining rows of the audit table and add any new gaps to the Gap list.

**Step 6: Commit**

```bash
git add docs/plans/2026-02-22-offer-acceptance-verification-design.md
git commit -m "docs: audit steps 4-7 for offer-acceptance verification"
```

---

### Task 3: Add CreateOffer web feature test

**Files:**
- Create or modify: `tests/Feature/CreateOfferTest.php` (or add to existing offer test file)

**Step 1: Write failing test (authenticated user, valid payload)**

Create a Pest feature test that: creates a campaign with current item (or use existing pattern from `AcceptOfferTest`); as a different authenticated user POSTs to `route('campaigns.offers.store', $campaign)` with `offered_item.title`, optional `offered_item.description` and `message`; asserts redirect; asserts an `Offer` and its offered `Item` exist and are linked to the campaign and current item.

**Step 2: Run test**

Run: `php artisan test --compact --filter=CreateOffer` (or the test name you used).  
Expected: PASS if backend is already correct; FAIL if route or validation is wrong — fix until test passes.

**Step 3: Add validation and auth tests**

Add: (a) unauthenticated POST → 302/401 or redirect to login; (b) missing `offered_item.title` → 302 redirect back with validation error (or 422). Run tests and ensure they pass.

**Step 4: Commit**

```bash
git add tests/Feature/CreateOfferTest.php
git commit -m "test: add CreateOffer web feature tests"
```

---

### Task 4: Add full-journey feature test

**Files:**
- Create: `tests/Feature/OfferAcceptanceJourneyTest.php`

**Step 1: Write full journey test**

One test that: creates campaign with start item as owner; as second user creates an offer via POST to `campaigns.offers.store`; as owner POSTs to `offers.accept`; as offerer POSTs to `trades.confirm`; as owner POSTs to `trades.confirm`; asserts trade status Completed and campaign `current_item_id` equals the offered item id. Use same model-setup patterns as `AcceptOfferTest` / `ConfirmTradeTest`.

**Step 2: Run test**

Run: `php artisan test --compact --filter=OfferAcceptanceJourney`  
Expected: PASS. If FAIL, fix (e.g. route names, auth, or model state) until it passes.

**Step 3: Commit**

```bash
git add tests/Feature/OfferAcceptanceJourneyTest.php
git commit -m "test: add full offer-acceptance journey feature test"
```

---

### Task 5: Gap-driven tests and final run

**Files:**
- Modify: `tests/Feature/*.php` as needed; `docs/plans/2026-02-22-offer-acceptance-verification-design.md` if gaps are fixed later

**Step 1: Review gap list**

From the design doc gap list, identify any step with no test coverage (e.g. campaign show returns pending offers for owner). Add one minimal test per gap if needed (e.g. GET campaign show as owner and assert offers in response or page props).

**Step 2: Run full test suite for offers/trades**

Run: `php artisan test --compact tests/Feature/AcceptOfferTest.php tests/Feature/DeclineOfferTest.php tests/Feature/ConfirmTradeTest.php tests/Feature/CreateOfferTest.php tests/Feature/OfferAcceptanceJourneyTest.php tests/Feature/OfferTest.php tests/Feature/Api/OfferApiTest.php tests/Feature/Api/TradeApiTest.php`  
Expected: All pass.

**Step 3: Commit if any new tests added**

```bash
git add tests/Feature/
git commit -m "test: add gap-driven tests for offer-acceptance verification"
```

---

### Execution handoff

Plan complete and saved to `docs/plans/2026-02-22-offer-acceptance-verification-implementation.md`.

**Two execution options:**

1. **Subagent-driven (this session)** — Dispatch a fresh subagent per task, review between tasks, fast iteration.
2. **Parallel session (separate)** — Open a new session with executing-plans and run through the plan with checkpoints.

Which approach do you want?
