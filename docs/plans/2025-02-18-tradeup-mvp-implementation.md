# TradeUp MVP Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Ship the TradeUp MVP: campaigns (start → goal items with media), offers, double-confirmed trades with timeline, comments, follows, in-app notifications, explore/search, and user profile with simple reputation.

**Architecture:** Laravel 12 + Inertia v2 + Vue 3; Form Requests and single-purpose Action classes for writes; policies for authorization; Eloquent models and relationships as in design doc §3; no escrow or disputes in MVP.

**Tech Stack:** Laravel 12, PHP 8.4, PostgreSQL, Inertia v2, Vue 3, Wayfinder, Fortify, reka-ui/shadcn-vue, Tailwind v4, Pest, S3-compatible storage for media.

**Design reference:** `docs/plans/2025-02-18-tradeup-design.md`

---

## Phase 1: Data model and categories

### Task 1: Migrations — users extensions, categories, campaigns, items

**Files:**
- Create: `database/migrations/xxxx_01_01_000001_add_tradeup_fields_to_users_table.php`
- Create: `database/migrations/xxxx_01_01_000002_create_categories_table.php`
- Create: `database/migrations/xxxx_01_01_000003_create_campaigns_table.php`
- Create: `database/migrations/xxxx_01_01_000004_create_items_table.php`
- Test: (no test for migrations; run migrate and rollback once manually)

**Step 1: Add TradeUp fields to users**

Create migration that adds to `users`: `reputation_score` (smallint, default 0), `verified_at` (timestamp nullable).

**Step 2: Create categories table**

Table: `id`, `name`, `slug` (unique), `timestamps`.

**Step 3: Create campaigns table**

Table: `id`, `user_id` (FK users), `category_id` (FK nullable), `status` (string: draft, active, completed, paused), `visibility` (string: public, unlisted), `title` (string nullable), `story` (text nullable), `current_item_id` (FK items nullable), `goal_item_id` (FK items nullable), `timestamps`. Indexes: user_id, category_id, status, created_at.

**Step 4: Create items table**

Table: `id`, `itemable_type` (string), `itemable_id` (ulid or bigInteger), `role` (string: start, goal, offered), `title` (string), `description` (text nullable), `timestamps`. Index: (itemable_type, itemable_id).

**Step 5: Run migrations**

Run: `php artisan migrate`
Expected: All new tables exist. Then run: `php artisan migrate:rollback --step=4` then `php artisan migrate` to confirm reversibility.

**Step 6: Commit**

```bash
git add database/migrations/
git commit -m "feat(tradeup): add users tradeup fields, categories, campaigns, items migrations"
```

---

### Task 2: Migrations — media, offers, trades

**Files:**
- Create: `database/migrations/xxxx_01_01_000005_create_media_table.php` (Laravel convention or Spatie Media Library if used; else simple polymorphic)
- Create: `database/migrations/xxxx_01_01_000006_create_offers_table.php`
- Create: `database/migrations/xxxx_01_01_000007_create_trades_table.php`
- Test: (manual migrate/rollback)

**Step 1: Media table**

Polymorphic: `id`, `model_type`, `model_id`, `collection_name`, `file_name`, `disk`, `path` (or use Laravel `media` table from package). If custom: `id`, `model_type`, `model_id`, `file_name`, `disk`, `path`, `size`, `timestamps`; index (model_type, model_id).

**Step 2: Offers table**

`id`, `campaign_id` (FK), `from_user_id` (FK), `offered_item_id` (FK), `for_campaign_item_id` (FK nullable), `message` (text nullable), `status` (string: pending, accepted, declined, withdrawn, expired), `expires_at` (nullable), `timestamps`. Indexes: campaign_id, from_user_id, status.

**Step 3: Trades table**

`id`, `campaign_id` (FK), `offer_id` (FK), `position` (smallInteger), `offered_item_id` (FK), `received_item_id` (FK), `status` (string: pending_confirmation, completed, disputed), `confirmed_by_offerer_at` (nullable), `confirmed_by_owner_at` (nullable), `timestamps`. Unique (campaign_id, position). Indexes: campaign_id, offer_id.

**Step 4: Run and verify**

Run: `php artisan migrate`

**Step 5: Commit**

```bash
git add database/migrations/
git commit -m "feat(tradeup): add media, offers, trades migrations"
```

---

### Task 3: Migrations — comments, follows, notifications

**Files:**
- Create: `database/migrations/xxxx_01_01_000008_create_comments_table.php`
- Create: `database/migrations/xxxx_01_01_000009_create_follows_table.php`
- Create: `database/migrations/xxxx_01_01_000010_create_notifications_table.php` (or use Laravel's default notifications table name)

**Steps:** Create tables per design §3.2 (comments: user_id, commentable_type, commentable_id, parent_id nullable, body, timestamps; follows: user_id, followable_type, followable_id, timestamps + unique; notifications: Laravel standard or user_id, type, data json, read_at, timestamps). Run migrate, commit.

**Step 6: Commit**

```bash
git add database/migrations/
git commit -m "feat(tradeup): add comments, follows, notifications migrations"
```

---

### Task 4: Eloquent models — Category, Campaign, Item

**Files:**
- Create: `app/Models/Category.php`
- Create: `app/Models/Campaign.php`
- Create: `app/Models/Item.php`
- Modify: `app/Models/User.php` (add tradeup relations and fillable)
- Test: `tests/Unit/Models/CampaignTest.php` (or Feature: create campaign, assert relations)

**Step 1: Write failing test**

In `tests/Feature/CampaignTest.php`: create a campaign with start and goal items, assert campaign has startItem and goalItem relations and current_item_id is null.

**Step 2: Run test**

Run: `php artisan test --compact tests/Feature/CampaignTest.php`
Expected: FAIL (models/relations missing).

**Step 3: Implement Category model**

Category: fillable name, slug; casts; no relations. Slug set in observer or mutator to Str::slug(name) if empty.

**Step 4: Implement Item model**

Item: fillable itemable_type, itemable_id, role, title, description; belongsTo itemable (morphTo); morphMany media if media table exists. Use casts for itemable_id if ULID.

**Step 5: Implement Campaign model**

Campaign: fillable per table; belongsTo user, category; morphMany items (as itemable); belongsTo currentItem, goalItem (Item); hasMany offers, trades. Scopes: active(), publicVisibility(). Casts: status, visibility.

**Step 6: User model**

Add fillable: reputation_score, verified_at. Add relations: hasMany(Campaign::class), hasMany(Offer::class), hasMany(Comment::class), hasMany(Follow::class).

**Step 7: Run test**

Run: `php artisan test --compact tests/Feature/CampaignTest.php`
Expected: PASS.

**Step 8: Commit**

```bash
git add app/Models/ database/factories/ tests/
git commit -m "feat(tradeup): add Category, Campaign, Item models and Campaign test"
```

---

### Task 5: Eloquent models — Offer, Trade, Comment, Follow, Notification

**Files:**
- Create: `app/Models/Offer.php`
- Create: `app/Models/Trade.php`
- Create: `app/Models/Comment.php`
- Create: `app/Models/Follow.php`
- Create: `app/Models/Notification.php` (or use Laravel Notifiable + database channel)
- Modify: `app/Models/User.php` (add remaining relations)
- Test: `tests/Feature/OfferTest.php` (minimal: create offer, assert campaign relation)

**Step 1: Write failing test**

Feature test: create campaign, create offer for that campaign, assert offer->campaign_id and offer->campaign relation.

**Step 2: Run test**

Expected: FAIL.

**Step 3: Implement Offer, Trade, Comment, Follow**

Offer: belongsTo campaign, fromUser, offeredItem, forCampaignItem; hasOne trade. Trade: belongsTo campaign, offer, offeredItem, receivedItem. Comment: belongsTo user; morphTo commentable; belongsTo parent. Follow: belongsTo user; morphTo followable. Use Laravel's DatabaseNotification or custom Notification model for notifications table.

**Step 4: Run test**

Expected: PASS.

**Step 5: Commit**

```bash
git add app/Models/ tests/
git commit -m "feat(tradeup): add Offer, Trade, Comment, Follow models and offer test"
```

---

### Task 6: Enums and Category seeder

**Files:**
- Create: `app/Enums/CampaignStatus.php`
- Create: `app/Enums/CampaignVisibility.php`
- Create: `app/Enums/OfferStatus.php`
- Create: `app/Enums/TradeStatus.php`
- Create: `app/Enums/ItemRole.php`
- Create: `database/seeders/CategorySeeder.php`
- Modify: `database/seeders/DatabaseSeeder.php` (call CategorySeeder)

**Steps:** Define enums with cases matching design (draft, active, completed, paused; public, unlisted; pending, accepted, declined, withdrawn, expired; pending_confirmation, completed, disputed; start, goal, offered). Seed 5–10 categories (e.g. Electronics, Books, Art, Collectibles, Other). Run seed, commit.

**Commit:** `feat(tradeup): add campaign/offer/trade enums and category seeder`

---

## Phase 2: Campaign CRUD and media

### Task 7: Form Requests and CreateCampaign action

**Files:**
- Create: `app/Http/Requests/StoreCampaignRequest.php`
- Create: `app/Http/Requests/UpdateCampaignRequest.php`
- Create: `app/Actions/CreateCampaign.php`
- Test: `tests/Feature/CreateCampaignTest.php`

**Step 1: Failing test**

POST to campaigns.store with valid start_item, goal_item; assert campaign and two items exist, campaign status draft or active per request.

**Step 2: Implement StoreCampaignRequest**

Rules: title nullable max 255; story nullable max 2000; category_id nullable exists:categories; start_item (array) required; start_item.title required max 255; start_item.description nullable max 2000; goal_item same; limit media count in request if needed.

**Step 3: CreateCampaign action**

Accept validated data and User; create Campaign (user_id, category_id, status, visibility, title, story, goal_item_id null for now); create Item (itemable Campaign, role start); create Item (itemable Campaign, role goal); set campaign.start_item_id and goal_item_id if you add those columns, or rely on relation; set campaign.current_item_id = start item id; return Campaign (load items).

**Step 4: Run test**

Expected: PASS.

**Step 5: Commit**

```bash
git add app/Http/Requests/ app/Actions/ tests/
git commit -m "feat(tradeup): add StoreCampaignRequest and CreateCampaign action"
```

---

### Task 8: Campaign controller and routes

**Files:**
- Create: `app/Http/Controllers/CampaignController.php`
- Modify: `routes/web.php`
- Test: `tests/Feature/CampaignControllerTest.php`

**Step 1: Failing test**

GET campaign create page (auth); POST valid campaign and assert redirect to show; GET campaign show (guest and auth).

**Step 2: CampaignController**

index: list campaigns (explore; filter by category, status; paginate). create: Inertia render create page with categories. store: validate StoreCampaignRequest, call CreateCampaign, redirect to campaign show. show: load campaign with items, trades (ordered), offers (pending), comments (paginated), follow state for auth user; Inertia render. edit/update (optional for MVP): only draft.

**Step 3: Routes**

Resource or explicit: campaigns.index, campaigns.create, campaigns.store, campaigns.show; middleware auth for create/store. Run wayfinder:generate.

**Step 4: Run test**

Expected: PASS.

**Step 5: Commit**

```bash
git add app/Http/Controllers/ routes/ tests/
git commit -m "feat(tradeup): add CampaignController and routes"
```

---

### Task 9: Campaign create and show Inertia pages

**Files:**
- Create: `resources/js/pages/campaigns/Create.vue`
- Create: `resources/js/pages/campaigns/Show.vue`
- Modify: `app/Http/Controllers/CampaignController.php` (return correct Inertia props)
- Test: (manual or Dusk; or assert Inertia response in feature test)

**Steps:** Create.vue: multi-step or single form for start item (title, description, file input), goal item, optional title/story/category; submit via Wayfinder action. Show.vue: header (title, owner, follow button); timeline (start item → trades → current → goal); tabs/sections offers, comments; “Make offer” button. Use existing UI components (Button, Input, etc.). Assert Inertia::render in controller test with correct component names.

**Commit:** `feat(tradeup): add campaign Create and Show Inertia pages`

---

### Task 10: Item media upload and storage

**Files:**
- Create: `app/Http/Requests/StoreItemMediaRequest.php`
- Create: `app/Actions/StoreItemMedia.php` (or use Laravel Media Library)
- Modify: `app/Http/Controllers/ItemMediaController.php` or campaign store flow to attach files
- Config: `config/filesystems.php` (s3 or local disk)
- Test: `tests/Feature/ItemMediaTest.php`

**Steps:** Accept file(s) for item; validate image, max size; store on disk (local or S3); create media record (polymorphic to Item). If using Spatie Media Library, register and use attach. Endpoint: POST /items/{item}/media or include in campaign create form. Test: upload file, assert media record and file exists.

**Commit:** `feat(tradeup): item media upload and storage`

---

## Phase 3: Offers and trades

### Task 11: Create offer (action, request, controller)

**Files:**
- Create: `app/Http/Requests/StoreOfferRequest.php`
- Create: `app/Actions/CreateOffer.php`
- Create: `app/Http/Controllers/OfferController.php` (or nested under CampaignController)
- Modify: `routes/web.php`
- Test: `tests/Feature/CreateOfferTest.php`

**Step 1: Failing test**

POST campaigns/{campaign}/offers with offered_item title/description; assert offer and offered item created, status pending, notification for campaign owner.

**Step 2: StoreOfferRequest**

campaign must be active; offered_item required; offered_item.title required max 255; message nullable max 500.

**Step 3: CreateOffer action**

Create Item (itemable = offer placeholder or create offer first then item with itemable Offer — design says offered_item_id on offers, so create Item with itemable_type/itemable_id pointing to… Offer. So create Offer (campaign_id, from_user_id, offered_item_id null, status pending), then create Item (itemable Offer, role offered), set offer.offered_item_id, set for_campaign_item_id = campaign.current_item_id. Return offer.

**Step 4: Controller and route**

POST /campaigns/{campaign}/offers, auth, policy: user is not owner. After create: create notification for campaign owner. Run test. Commit.

**Commit:** `feat(tradeup): create offer action, request, controller and test`

---

### Task 12: Accept and decline offer

**Files:**
- Create: `app/Actions/AcceptOffer.php`
- Create: `app/Actions/DeclineOffer.php`
- Modify: `app/Http/Controllers/OfferController.php` (accept, decline methods)
- Modify: `routes/web.php`
- Test: `tests/Feature/AcceptOfferTest.php`, `tests/Feature/DeclineOfferTest.php`

**Step 1: Failing test**

Accept: as owner, POST accept; assert Trade created, offer status accepted, campaign current_item updated to received item (after both confirm — so Accept only creates Trade and sets offer accepted; current_item advances only on both confirm). So: Accept creates Trade (position = campaign.trades_count + 1, offered_item_id, received_item_id = campaign.current_item_id, status pending_confirmation); offer.status = accepted; create notifications for offerer. Decline: POST decline; offer.status = declined; notify offerer.

**Step 2: Implement AcceptOffer**

Check offer is pending and campaign.current_item_id equals offer.for_campaign_item_id; create Trade; update offer; notifications. Implement DeclineOffer. Policy: only campaign owner can accept/decline.

**Step 3: Run tests**

Expected: PASS.

**Step 4: Commit**

```bash
git add app/Actions/ app/Http/Controllers/ routes/ tests/
git commit -m "feat(tradeup): accept and decline offer with trade creation"
```

---

### Task 13: Confirm trade (both sides)

**Files:**
- Create: `app/Actions/ConfirmTrade.php`
- Create: `app/Http/Controllers/TradeController.php`
- Modify: `routes/web.php`
- Test: `tests/Feature/ConfirmTradeTest.php`

**Step 1: Failing test**

Create campaign, offer, accept offer (trade exists). As offerer, POST confirm → trade.confirmed_by_offerer_at set. As owner, POST confirm → trade.confirmed_by_owner_at set; then assert trade status completed and campaign.current_item_id = trade.offered_item_id (the item owner received).

**Step 2: ConfirmTrade action**

Accept trade and user. If user is offerer, set confirmed_by_offerer_at; if owner, set confirmed_by_owner_at. When both set, set status completed, update campaign.current_item_id to trade.offered_item_id (what owner received), dispatch notification to both. Idempotent: if already confirmed by this user, return success.

**Step 3: Route and controller**

POST /trades/{trade}/confirm, auth, policy: user is offerer or campaign owner.

**Step 4: Run test**

Expected: PASS.

**Step 5: Commit**

```bash
git add app/Actions/ app/Http/Controllers/ routes/ tests/
git commit -m "feat(tradeup): confirm trade and advance campaign chain"
```

---

### Task 14: Campaign timeline and offers UI

**Files:**
- Modify: `resources/js/pages/campaigns/Show.vue`
- Create: `resources/js/components/campaign/Timeline.vue` (optional)
- Create: `resources/js/components/campaign/OfferCard.vue` (optional)
- Modify: `app/Http/Controllers/CampaignController.php` (ensure trades ordered by position, offers pending)

**Steps:** Timeline: render start item → trades (each with offered_item, received_item) → current item → goal item. Offers section: list pending offers with accept/decline buttons; call Wayfinder actions for accept/decline. Confirm trade: add “Confirm trade” on campaign or trades section when user is party and trade pending_confirmation. Commit.

**Commit:** `feat(tradeup): campaign timeline and offers UI with accept/decline/confirm`

---

## Phase 4: Comments, follows, notifications

### Task 15: Comments (create, list)

**Files:**
- Create: `app/Http/Requests/StoreCommentRequest.php`
- Create: `app/Http/Controllers/CommentController.php` (or CampaignCommentController)
- Modify: `routes/web.php`
- Modify: `app/Http/Controllers/CampaignController.php` (eager load comments, paginate)
- Create: `resources/js/components/campaign/CommentForm.vue`, `CommentList.vue` (or inline in Show.vue)
- Test: `tests/Feature/CommentTest.php`

**Steps:** POST /campaigns/{campaign}/comments with body; validate body required max 1000; rate limit 10/min; create Comment (commentable campaign). List: already in show payload; render in Show.vue. Commit.

**Commit:** `feat(tradeup): comments on campaign with create and list`

---

### Task 16: Follow campaign

**Files:**
- Create: `app/Http/Controllers/CampaignFollowController.php` (follow, unfollow)
- Modify: `routes/web.php`
- Modify: `resources/js/pages/campaigns/Show.vue` (follow button, use Wayfinder)
- Test: `tests/Feature/FollowCampaignTest.php`

**Steps:** POST follow: create Follow (user, followable campaign); DELETE unfollow. Toggle button on show page; show followers count. Policy: do not follow own campaign or allow (design says no-op or allow). Commit.

**Commit:** `feat(tradeup): follow and unfollow campaign`

---

### Task 17: In-app notifications (list, mark read)

**Files:**
- Create: `app/Notifications/OfferReceivedNotification.php` (database)
- Create: `app/Notifications/OfferAcceptedNotification.php`
- Create: `app/Notifications/OfferDeclinedNotification.php`
- Create: `app/Notifications/TradeConfirmationRequestNotification.php`
- Create: `app/Notifications/CommentNotification.php` (optional)
- Modify: `app/Models/User.php` (use Notifiable; ensure database channel)
- Create: `app/Http/Controllers/NotificationController.php` (index, markAsRead)
- Create: `resources/js/pages/notifications/Index.vue`
- Modify: layout (bell icon with unread count, link to notifications)
- Test: `tests/Feature/NotificationTest.php`

**Steps:** When offer created, notify campaign owner. When offer accepted/declined, notify offerer. When trade created, notify both to confirm. Store in notifications table. List: GET /notifications, Inertia; mark read: PATCH /notifications/{id}/read or bulk. Run test. Commit.

**Commit:** `feat(tradeup): in-app notifications for offers and trade confirmation`

---

## Phase 5: Explore, search, profile, reputation

### Task 18: Explore and search campaigns

**Files:**
- Modify: `app/Http/Controllers/CampaignController.php` (index: filter category_id, status, sort, q)
- Create: `resources/js/pages/campaigns/Index.vue`
- Modify: `routes/web.php` (campaigns.index)
- Test: `tests/Feature/ExploreCampaignsTest.php`

**Steps:** index: Campaign::query()->when($category_id)->when($status)->when($q, full-text or like on title/story); order by sort (new, trades_count, followers_count); paginate. Pass categories for filter dropdown. Index.vue: grid of campaign cards; filters; search input. Commit.

**Commit:** `feat(tradeup): explore and search campaigns`

---

### Task 19: User profile (public) and reputation

**Files:**
- Create: `app/Http/Controllers/ProfileController.php` (public show) or use route profile.show with username/id
- Create: `resources/js/pages/profile/Show.vue`
- Modify: `app/Models/User.php` (reputation: accessor or scope from completed trades count)
- Modify: `routes/web.php`
- Test: `tests/Feature/ProfileTest.php`

**Steps:** GET /users/{user} or /profile/{user}: show user name, avatar, reputation_score (or computed completed_trades count), list of campaigns. Reputation: for MVP, set reputation_score = completed trades count; update in ConfirmTrade when trade completed. Commit.

**Commit:** `feat(tradeup): public profile and simple reputation`

---

## Phase 6: Polish and launch prep

### Task 20: Policies and authorization

**Files:**
- Create: `app/Policies/CampaignPolicy.php`
- Create: `app/Policies/OfferPolicy.php`
- Create: `app/Policies/TradePolicy.php`
- Register in `app/Providers/AuthServiceProvider.php` or bootstrap
- Test: `tests/Feature/AuthorizationTest.php`

**Steps:** Campaign: update/delete only owner; accept offers only owner. Offer: view if campaign visible; decline/accept owner. Trade: confirm only offerer or campaign owner. Run test. Commit.

**Commit:** `feat(tradeup): policies for campaign, offer, trade`

---

### Task 21: Empty states and validation messages

**Files:**
- Modify: `resources/js/pages/campaigns/Show.vue` (no offers, no comments)
- Modify: `resources/js/pages/campaigns/Index.vue` (no results)
- Modify: Form Requests (custom messages for key rules)
- Test: (manual or assert 422 responses in feature tests)

**Steps:** Show empty state copy and CTA where relevant. Add validation messages for StoreCampaignRequest, StoreOfferRequest, StoreCommentRequest. Commit.

**Commit:** `chore(tradeup): empty states and validation messages`

---

### Task 22: Run full test suite and fix regressions

**Command:** `php artisan test --compact`  
**Steps:** Fix any failing tests; ensure existing auth and app tests still pass. Commit: `fix: tradeup test and regression fixes`

---

### Task 23: Wayfinder generate and frontend build

**Commands:** `php artisan wayfinder:generate --with-form`; `npm run build`  
**Steps:** Ensure no TS errors; commit: `chore: wayfinder and build`

---

## Execution handoff

Plan complete and saved to `docs/plans/2025-02-18-tradeup-mvp-implementation.md`.

**Two execution options:**

1. **Subagent-driven (this session)** — Dispatch a fresh subagent per task (or per phase), review between tasks, fast iteration.
2. **Parallel session (separate)** — Open a new session with the executing-plans skill and run in a worktree with checkpoint reviews.

Which approach do you prefer?
