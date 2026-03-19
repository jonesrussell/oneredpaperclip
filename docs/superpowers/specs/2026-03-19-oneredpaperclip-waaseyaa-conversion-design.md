# One Red Paperclip — Waaseyaa Conversion Design

## Overview

Convert the One Red Paperclip trade-up platform from Laravel 12 to a Waaseyaa application. The new app lives in a separate repo (`oneredpaperclip-waaseyaa`), consumes `waaseyaa/*` framework packages, and keeps all trade-up domain logic in-app. The Vue frontend is ported and polished, rendered via `waaseyaa/inertia`.

## Approach

Entity-first (bottom-up): define entity types and storage → port business logic actions → wire access policies → add routes and controllers with Inertia responses → port and polish the Vue frontend.

## Sub-project Order

1. Entity types + storage schemas
2. Actions + domain events
3. Access policies
4. Routes + controllers
5. Auth (waaseyaa/user)
6. Frontend port and polish
7. Testing across all layers

Each sub-project gets its own implementation plan and review cycle.

---

## 1. Entity Types & Data Model

### Category → Taxonomy

The 9 fixed categories (Electronics, Collectibles, etc.) become taxonomy terms in a `challenge_category` vocabulary using `waaseyaa/taxonomy`. No custom entity type needed.

### Entity Types

| Entity Type ID | Entity Class | Key Fields | Notes |
|---|---|---|---|
| `challenge` | `Challenge` | id, user_id, category_tid, title, slug, description, status, visibility, current_item_id, goal_item_id | Status: ChallengeStatus enum. Visibility: ChallengeVisibility enum. Soft deletes via `deleted_at` field. `category_tid` references taxonomy term. |
| `item` | `Item` | id, itemable_type, itemable_id, role, title, description, estimated_value | Polymorphic link to challenge or offer. Role enum: Start, Goal, Offered. |
| `offer` | `Offer` | id, user_id, challenge_id, item_id, target_item_id, status, message | Status enum: Pending, Accepted, Declined, Withdrawn, Expired. |
| `trade` | `Trade` | id, challenge_id, offer_id, position, status, confirmed_by_owner_at, confirmed_by_offerer_at | Status enum: PendingConfirmation, Completed, Disputed. Unique constraint on (challenge_id, position). |
| `comment` | `Comment` | id, commentable_type, commentable_id, user_id, parent_id, body | Polymorphic, nested replies via parent_id. |
| `follow` | `Follow` | id, user_id, followable_type, followable_id | Polymorphic followable. |

### Media

Use `waaseyaa/media` package directly — no custom media entity type.

### User

Use `waaseyaa/user` package. Extend with:
- `notification_preferences` — JSON field for per-type database/email preferences
- `is_admin` — boolean flag for admin access

### Admin Role Mapping

The `is_admin` boolean field on the User entity must map to the `'admin'` role string used in `_role: 'admin'` route options. This is done via a custom middleware or `AccessChecker` integration that checks `$account->get('is_admin')` when evaluating the `'admin'` role. If `waaseyaa/user` has a role system with string role IDs, register `'admin'` as a role and assign it based on the `is_admin` field during session hydration. If not, implement the role check as a simple callback in the app's service provider.

### Polymorphic References

Three entity types use polymorphic fields (Item, Comment, Follow). In Waaseyaa's entity system, these are stored as raw string/integer pairs in the schema:

- **Item:** `itemable_type` (string — entity type ID, e.g. `'challenge'` or `'offer'`) + `itemable_id` (integer). Resolution is done in the action/controller layer by loading the target entity via `EntityStorage` using the type ID and entity ID.
- **Comment:** `commentable_type` + `commentable_id` — same pattern.
- **Follow:** `followable_type` + `followable_id` — same pattern.

These are plain string/integer columns in the `SqlSchemaHandler` definition, not typed entity reference fields. The application layer is responsible for interpreting and resolving them.

### Notification Storage

Notifications are stored in a `notification` entity type (not a raw table):

| Field | Type | Notes |
|---|---|---|
| id | string (UUID) | Primary key |
| user_id | integer | Recipient |
| type | string | Event class name (e.g. `OfferAccepted`) |
| data | JSON | Serialized event payload |
| read_at | datetime, nullable | Null = unread |

The `notification_preferences` field on User is an extended JSON field on the `waaseyaa/user` entity, storing per-type delivery preferences (e.g. `{"offer_accepted": {"database": true, "email": true}}`).

### Enums

Port existing backed string enums as-is:
- `ChallengeStatus` — Draft, Published, Completed, Archived
- `ChallengeVisibility` — Public, Private, Unlisted
- `ItemRole` — Start, Goal, Offered
- `OfferStatus` — Pending, Accepted, Declined, Withdrawn, Expired
- `TradeStatus` — PendingConfirmation, Completed, Disputed

---

## 2. Business Logic (Actions)

Action classes live in the app (not a package). Each action takes dependencies via constructor injection and executes a single business operation.

| Action | Behavior | Waaseyaa Implementation |
|---|---|---|
| `CreateChallenge` | Creates challenge + start/goal items, links item IDs | EntityStorage transaction: persist challenge + 2 item entities, update challenge with item IDs |
| `UpdateChallenge` | Updates challenge details | EntityStorage update with access check |
| `CreateOffer` | Creates offer + offered item targeting a challenge item | Creates 2 entities (offer + item), validates challenge is active/published |
| `AcceptOffer` | Creates Trade, marks offer accepted, sends notification | Multi-entity transaction. Dispatches `OfferAccepted` domain event. |
| `DeclineOffer` | Marks offer declined, sends notification | Status update + `OfferDeclined` domain event |
| `ConfirmTrade` | Records confirmation timestamp; owner confirmation completes trade and advances challenge | Updates trade status. If owner confirms: sets challenge.current_item_id to offered item, dispatches `TradeCompleted` event. |
| `UpdateTrade` | Updates traded item details while pending | Access-gated to challenge owner, trade must be PendingConfirmation |
| `SuggestChallengeText` | AI-powered text suggestions | Wire to `waaseyaa/ai-agent` (tool orchestration — correct fit for text generation; `ai-pipeline` is for embedding/similarity search, not applicable here) |

### Domain Events

Notifications handled via domain events rather than a dedicated notification package:

| Event | Trigger | Listener Behavior |
|---|---|---|
| `OfferAccepted` | AcceptOffer action | Notify offerer (database + optional email) |
| `OfferDeclined` | DeclineOffer action | Notify offerer |
| `OfferReceived` | CreateOffer action | Notify challenge owner |
| `TradeCompleted` | ConfirmTrade (owner confirms) | Notify both parties |
| `TradeConfirmed` | ConfirmTrade (either party) | Notify the other party |
| `ChallengeCompleted` | Trade completes and current_item matches goal | Notify challenge owner |

Notification delivery and storage is in-app — listeners write to a `notifications` table and optionally send email via `waaseyaa/mail`.

---

## 3. Access Policies

Each policy implements `AccessPolicyInterface` and is registered via `#[AccessPolicy(id: 'policy_id', entityTypes: ['entity_type'])]` (from `Waaseyaa\Access\Attribute\AccessPolicy`). Note: `PolicyAttribute` is a separate, simpler attribute — use `AccessPolicy` for entity-type-scoped policies.

### ChallengeAccessPolicy

- **view:** Public if published. Owner can always view. Admin can view all.
- **create:** Authenticated users.
- **update:** Owner only. Admin can update any.
- **delete:** Owner only (soft delete). Admin can delete/restore any.

### OfferAccessPolicy

- **create:** Authenticated, must not be challenge owner.
- **accept/decline:** Challenge owner only, offer must be Pending.
- **withdraw:** Offer creator only, offer must be Pending.

### TradeAccessPolicy

- **confirm:** Offerer or challenge owner. Trade must be PendingConfirmation.
- **update:** Challenge owner only. Trade must be PendingConfirmation.

### CommentAccessPolicy

- **create:** Authenticated.
- **delete:** Comment author or challenge owner.

### FollowAccessPolicy

- **create/delete:** Authenticated, own follows only.

### Route-Level Access

Routes use Waaseyaa access options:
- `_public: true` — public pages (welcome, challenge browsing)
- `_authenticated: true` — requires login (entity-level policy enforcement done inside controllers via `AccessPolicyInterface`)
- `_permission: 'permission_name'` — specific permission check
- `_role: 'admin'` — admin dashboard routes

Note: there is no `_gate` option in Waaseyaa. Entity-level access checks (e.g. "is this user the challenge owner?") are performed in the controller/action layer by calling the relevant `AccessPolicyInterface` directly, not at the route level.

---

## 4. Routes & Controllers

### Public Routes (`_public: true`)

| Method | Path | Controller | Inertia Page |
|---|---|---|---|
| GET | `/` | `WelcomeController` | `Welcome` |
| GET | `/about` | `AboutController` | `About` |
| GET | `/challenges` | `ChallengeController@index` | `challenges/Index` |
| GET | `/challenges/{slug}` | `ChallengeController@show` | `challenges/Show` |
| GET | `/sitemap.xml` | `SitemapController` | — (XML response) |

### Authenticated Routes

| Method | Path | Controller | Access |
|---|---|---|---|
| GET | `/challenges/create` | `ChallengeController@create` | Authenticated |
| POST | `/challenges` | `ChallengeController@store` | Authenticated |
| GET | `/challenges/{slug}/edit` | `ChallengeController@edit` | Owner |
| PUT | `/challenges/{slug}` | `ChallengeController@update` | Owner |
| DELETE | `/challenges/{slug}` | `ChallengeController@destroy` | Owner |
| POST | `/challenges/{slug}/offers` | `OfferController@store` | Authenticated, not owner |
| POST | `/offers/{id}/accept` | `OfferController@accept` | Challenge owner |
| POST | `/offers/{id}/decline` | `OfferController@decline` | Challenge owner |
| POST | `/trades/{id}/confirm` | `TradeController@confirm` | Owner or offerer |
| PUT | `/trades/{id}` | `TradeController@update` | Challenge owner |
| POST | `/items/{id}/media` | `ItemMediaController@store` | Item owner |
| GET | `/notifications` | `NotificationController@index` | Authenticated |
| POST | `/notifications/{id}/read` | `NotificationController@markRead` | Authenticated |

### Dashboard Routes (`_role: admin`)

| Method | Path | Controller |
|---|---|---|
| GET | `/dashboard` | `DashboardController` |
| GET | `/dashboard/admin/challenges` | `Admin\ChallengeController@index` |
| GET | `/dashboard/admin/challenges/{id}` | `Admin\ChallengeController@show` |
| DELETE | `/dashboard/admin/challenges/{id}` | `Admin\ChallengeController@destroy` |
| POST | `/dashboard/admin/challenges/{id}/restore` | `Admin\ChallengeController@restore` |
| CRUD | `/dashboard/admin/users/*` | `Admin\UserController` |

### JSON API

Parallel JSON API routes under `/api/*` prefix, same session auth. Returns JSON instead of Inertia responses.

### Settings Routes

| Method | Path | Inertia Page |
|---|---|---|
| GET | `/settings/profile` | `settings/Profile` |
| GET | `/settings/password` | `settings/Password` |
| GET | `/settings/appearance` | `settings/Appearance` |
| GET | `/settings/notifications` | `settings/Notifications` |
| GET | `/settings/two-factor` | `settings/TwoFactor` |

---

## 5. Auth

Wire `waaseyaa/user` package for authentication base. The package provides:

- **From waaseyaa/user:** Login, logout, user lookup (`me`, `findUserByName`)

The following must be **built in-app** (waaseyaa/user does not provide them):

- **Registration** — custom controller + action, creates user entity, sends verification email
- **Password reset** — forgot + reset flow with token generation and email via `waaseyaa/mail`
- **Email verification** — signed URL verification flow
- **Two-factor authentication** — TOTP with confirmation (generate secret, verify code, store recovery codes)
- **Profile update** — update name, email, password

These in-app auth controllers render Inertia pages (same Vue pages ported from current app).

Rate limiting: 5/min for login and 2FA attempts (implemented as middleware).

---

## 6. Frontend Migration & Polish

### What Stays

- Vue 3 + Tailwind CSS v4 + shadcn-vue (new-york-v4 style, reka-ui primitives, lucide icons)
- All custom components: TradePathMap, CelebrationOverlay, PaperclipMascot, ProgressRing, MilestoneTimeline, BottomTabBar, NotificationDropdown, ShareDropdown, TradeCard, OfferCard, ChallengeCard, StatsPanel, CreateOfferDialog, EditTradeDialog
- Duolingo-inspired design system: chunky 3D buttons (border-b-4 + active press), gamification (XP, streaks, levels), vibrant accent colors
- Design tokens: CSS variables (--brand-red, --electric-mint, --sunny-yellow, etc.)
- Fonts: DM Sans (body), Fredoka (display), JetBrains Mono (stats)
- Layouts: Public, App (sidebar), Auth (centered card), Admin
- Button variants: default, brand, destructive, outline, secondary, ghost, link, success, social

### What Changes

- **Route helpers:** Laravel Wayfinder imports (`@/actions/`, `@/routes/`) replaced with a thin in-app route helper module. This module exports typed functions matching the app's route structure (e.g. `routes.challenges.show(slug)` returns `/challenges/${slug}`). Routes are defined as string templates — no code generation step needed. All Wayfinder imports must be replaced during the port.
- **Layouts:** Same structure, wired to Waaseyaa Inertia root template via `RootTemplateRenderer`.
- **Auth props:** Match what Waaseyaa Inertia middleware shares via `InertiaMiddleware` shared props.
- **Inertia protocol:** Waaseyaa Inertia implements protocol v3. The source app uses `@inertiajs/vue3` v2. Must bump the Vue client package to match v3 and audit for breaking changes: deferred props API, `encryptHistory`/`clearHistory` flags, polling changes. This is a prerequisite for the frontend port.
- **Form submissions:** `useForm` / `router.post()` — still standard Inertia, changes limited to v2→v3 API adjustments.

### Polish

- Clean up inconsistent component patterns
- Ensure shared component usage is consistent across all pages
- Improve mobile responsiveness where rough
- Tighten TypeScript types to match Waaseyaa entity shapes
- Remove any dead code or unused imports

### What Gets Dropped

- NorthCloud integration (articles, Redis subscriber, admin articles CRUD)
- Laravel-specific middleware (HandleAppearance, HandleInertiaRequests) — replaced by Waaseyaa equivalents

---

## 7. Testing

### Unit Tests

- Entity creation and field validation for each entity type
- All action classes tested with `InMemoryEntityStorage` or `PdoDatabase::createSqlite()`
- Access policy tests with anonymous classes (Waaseyaa pattern — PHPUnit `createMock()` cannot mock intersection types)
- Enum transition validation

### Integration Tests

- Each route returns correct Inertia response with expected props
- Access enforcement: unauthenticated, wrong user, admin bypass
- Full trade-up flow: create challenge → create offer → accept → confirm → challenge advances
- Domain event dispatch and listener behavior

### Frontend Tests

- Adapt existing Playwright tests for new app
- Component rendering tests

### Test Tooling

PHPUnit (Waaseyaa standard), not Pest. Use Waaseyaa's `testing` package utilities.

---

## Tech Stack Summary

| Concern | Technology |
|---|---|
| Framework | Waaseyaa (entity, routing, access, foundation, etc.) |
| Data layer | Waaseyaa Entity System + EntityStorage |
| Auth | waaseyaa/user |
| Server rendering | waaseyaa/inertia (RootTemplateRenderer — waaseyaa/ssr is Twig-based traditional SSR, not used with Inertia) |
| Categories | waaseyaa/taxonomy |
| Media | waaseyaa/media |
| AI suggestions | waaseyaa/ai-agent |
| Mail | waaseyaa/mail |
| Frontend | Vue 3 + Tailwind CSS v4 + shadcn-vue |
| Testing | PHPUnit + Waaseyaa testing package |
| Database | MariaDB (same as current) |
