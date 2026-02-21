# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

**One Red Paperclip** — a trade-up platform where users create campaigns with a start item and goal item, receive offers from other users, and confirm trades that advance the campaign's current item toward the goal. Inspired by Kyle MacDonald's barter experiment.

## Commands

```bash
composer dev                # Start server + queue + Pail logs + Vite (all-in-one)
composer test               # Pint lint check + Pest tests
php artisan test --compact  # Run all tests
php artisan test --compact --filter=CampaignControllerTest  # Run specific test file
php artisan test --compact --filter="test campaign show"     # Run single test by name
vendor/bin/pint --dirty --format agent   # Format only changed PHP files
npm run lint                # ESLint fix
npm run format              # Prettier format resources/
npm run build               # Vite production build
php artisan wayfinder:generate  # Regenerate TypeScript route helpers after route changes
```

DDEV is available but optional — prefix commands with `ddev exec` if using it.

## Architecture

### Domain Model (the trade-up flow)

```
User creates Campaign (with start Item + goal Item)
  → Other users submit Offers (each has an offered Item targeting a campaign Item)
  → Campaign owner accepts/declines Offers (via OfferPolicy)
  → Accepted Offer creates a Trade (status: PendingConfirmation)
  → Both parties confirm the Trade (dual-confirmation via TradePolicy)
  → Completed Trade advances Campaign.current_item_id to the offered Item
```

### Key Models and Relationships

- **Campaign** — central entity. Has `status` (CampaignStatus enum), `visibility` (CampaignVisibility enum), belongs to User and Category. References `current_item_id` and `goal_item_id` on the items table.
- **Item** — polymorphic (`itemable_type`/`itemable_id`). Role enum: Start, Goal, Offered. Attached to campaigns or offers.
- **Offer** — links a user's offered Item to a campaign's current Item. Status enum: Pending, Accepted, Declined, Withdrawn, Expired.
- **Trade** — created from an accepted Offer. Dual-confirmation design (`confirmed_by_offerer_at`, `confirmed_by_owner_at`). Status enum: PendingConfirmation, Completed, Disputed. Unique constraint on `(campaign_id, position)`.
- **Comment** — polymorphic, supports nested replies via `parent_id`.
- **Follow** — polymorphic followable.
- **Media** — custom polymorphic media model (not Spatie).
- **Notification** — custom model (not Laravel's built-in notification system).

### Action Classes (`app/Actions/`)

Business logic lives in Action classes, not controllers:
- `CreateCampaign` — creates campaign + start/goal items, links item IDs
- `AcceptOffer` — creates Trade, marks offer accepted, sends notification
- `DeclineOffer` — marks offer declined, sends notification
- `ConfirmTrade` — records confirmation timestamp; when both confirm, completes trade and advances campaign

Controllers delegate to these actions. Follow this pattern for new business logic.

### Enums (`app/Enums/`)

All status fields use backed string enums: `CampaignStatus`, `CampaignVisibility`, `ItemRole`, `OfferStatus`, `TradeStatus`. Use these enums (not raw strings) in code.

### Authorization

- `OfferPolicy` — accept/decline: caller must be campaign owner, offer must be Pending
- `TradePolicy` — confirm: caller must be offerer or campaign owner

### Frontend

- **UI library:** shadcn-vue (new-york-v4 style, reka-ui primitives, lucide icons)
- **Design:** "Swap Shop" aesthetic — vibrant marketplace feel (Depop meets Duolingo), warm cream backgrounds, fun accent colors
- **Fonts:** DM Sans (body), Fredoka (display headings), JetBrains Mono (stats/data) — loaded via Bunny Fonts
- **Design tokens:** CSS variables in `resources/css/app.css` — `--brand-red`, `--electric-mint`, `--sunny-yellow`, `--hot-coral`, `--soft-lavender`, `--sky-blue`, `--paper`, `--ink`
- **Layouts:**
  - `PublicLayout` — public pages (Welcome, campaign browsing): sticky header, decorative blobs, mobile bottom tab bar, footer
  - `AppLayout` (wraps `AppSidebarLayout`) — authenticated pages: sidebar on desktop, bottom tab bar on mobile
  - `AuthLayout` — auth pages: centered card with accent blobs
  - Settings has its own `Layout`
- **Custom components:**
  - `CampaignCard` — reusable card with category accent strip, progress ring, trade count badge
  - `ProgressRing` — SVG circular progress indicator, color changes by completion (coral/yellow/mint)
  - `MilestoneTimeline` — horizontal trade timeline with completed/current/future nodes
  - `BottomTabBar` — mobile navigation (Home, Explore, Create[elevated], Activity, Profile)
- **Button variants:** default, brand, destructive, outline, secondary, ghost, link, success (mint), social (sky blue)
- **Pages:** `resources/js/pages/` — Inertia Vue components
- **Wayfinder:** TypeScript route helpers generated at `resources/js/actions/` and `resources/js/routes/`. Import routes from `@/actions/` (controllers) or `@/routes/` (named routes).
- **ESLint enforces:** alphabetical imports, `import type` for type-only imports, prettier compatibility

### Middleware

Configured in `bootstrap/app.php`:
- `HandleAppearance` — dark/light theme from `appearance` cookie
- `HandleInertiaRequests` — shares `auth.user` and `sidebarOpen` (from `sidebar_state` cookie)

### Auth

Fortify headless with Inertia views. Features: registration, password reset, email verification, 2FA (TOTP with confirmation). Rate limiters: 5/min for login and 2FA. Views registered in `FortifyServiceProvider`.

### Database

MariaDB. Default queue connection is `database`. Categories seeded with 9 predefined values. `CampaignSeeder` creates sample campaigns using the `CreateCampaign` action.

### Validation

Form Requests use **array-style** rules (not pipe-delimited strings). Check `StoreCampaignRequest` for the pattern.

## Known Gaps

These are referenced in code but don't exist yet:
- `CreateOffer` action (referenced in `OfferController@store`)
- `ItemMediaController` (referenced in `routes/web.php` and Wayfinder)
- No model factories for Campaign, Item, Offer, Trade (tests use `Model::create()` directly)

## Design Documentation

- `docs/plans/2026-02-20-swap-shop-redesign-design.md` — Swap Shop design system (colors, typography, components, page layouts)
- `docs/plans/2026-02-20-swap-shop-implementation.md` — implementation plan for the redesign
- `docs/plans/2025-02-18-tradeup-design.md` — original TradeUp product architecture and data model
- `docs/plans/2025-02-18-tradeup-mvp-implementation.md` — original MVP implementation plan
