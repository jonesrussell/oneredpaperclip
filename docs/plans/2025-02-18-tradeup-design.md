# TradeUp Platform — Implementation-Ready Blueprint

**Document:** Design & architecture  
**Date:** 2025-02-18  
**Project:** oneredpaperclip → TradeUp

---

## 1. Core Product Definition

### One-sentence summary
TradeUp is a public, sequential barter platform where users start with a "Start Item," set a "Goal Item," and progress through a visible chain of trades with community support—like GoFundMe for trading instead of donations.

### Primary user personas
| Persona | Goal | Key behavior |
|--------|-----|----------------|
| **Trader** | Complete a trade chain (start → goal) | Creates campaign, responds to offers, confirms trades |
| **Offerer** | Find interesting items / help others | Browses campaigns, submits offers, follows chains |
| **Spectator** | Watch journeys, share, comment | Follows campaigns, comments, shares, no trades |

### Core motivations and psychological drivers
- **Progress narrative:** Visible chain = story and momentum (streaks, milestones).
- **Social proof:** Public timeline and reputation reduce perceived risk.
- **Low commitment:** One trade at a time; no marketplace inventory.
- **Viral potential:** Unusual start/goal pairs are shareable.

### Key differentiators
| vs. Barter/swap platforms | TradeUp |
|---------------------------|--------|
| One-off swaps | **Single sequential chain** per campaign; linear story |
| Private or ad-hoc | **Public timeline**; every trade visible |
| Trust via ratings only | **Double-confirmation + optional escrow**; verification events |
| Browse catalog | **Campaign-centric discovery** (start/goal, category, trending) |

---

## 2. Feature Architecture

### 2.1 Create a TradeUp campaign (Start Item → Goal Item)
- **Purpose:** User defines one chain: one start item, one goal item, optional story.
- **User flow:** Log in → Create campaign → Enter start item (title, description, photo) → Enter goal item → Optional story/why → Publish (or save draft).
- **Data:** `campaigns` (user_id, status, visibility), `items` (campaign_id, role: start|goal, title, description), media via `media` (polymorphic).
- **Edge cases:** Draft vs published; edit allowed only before first trade; start/goal cannot be identical; max media per item (e.g. 5); title/description length limits.

### 2.2 Trade chain timeline (visual progression)
- **Purpose:** Show ordered list of trades from start to current; “distance” to goal.
- **User flow:** Campaign page shows vertical (or horizontal) timeline: Start Item → Trade 1 → Trade 2 → … → Current Item → (Goal).
- **Data:** `trades` ordered by `position` (or `created_at`); each trade links `campaign_id`, `offered_item_id`, `received_item_id`, `offer_id`, status, timestamps.
- **Edge cases:** Empty chain (no trades yet); single trade; very long chains (paginate or collapse); deleted/withdrawn offers removed from timeline.

### 2.3 Trade offers (incoming / outgoing)
- **Purpose:** Others propose “I give you X for your current item”; campaign owner accepts or declines.
- **User flow:** Offerer: campaign page → “Make offer” → describe + attach item (or link to “what I’m offering”) → submit. Owner: “Offers” tab → list with accept/decline; on accept, trade is created and both confirm.
- **Data:** `offers` (campaign_id, from_user_id, offered_item_id, message, status: pending|accepted|declined|withdrawn), optional `items` for offered item.
- **Edge cases:** Offer for wrong “current” item (e.g. chain advanced); max pending offers per campaign (e.g. 50); offer expiry (e.g. 7 days); one accepted offer per position.

### 2.4 User reputation + verification
- **Purpose:** Trust: completed trades, verification events, visible score.
- **User flow:** Profile shows: trades completed, verification badges (email, phone, identity if added), reputation score; optionally “verified trader” badge.
- **Data:** `users` (existing + reputation_score, verified_at), `verification_events` (user_id, type, payload, verified_at); reputation derived or cached from completed trades + disputes.
- **Edge cases:** New user (no score); what counts as “verified” (email required, phone/ID optional); score decay or only positive.

### 2.5 Community engagement (follows, comments, shares)
- **Purpose:** Audience and virality; comments for support and questions.
- **User flow:** Follow: button on campaign → follow; unfollow same. Comment: campaign page → comment box → submit; reply optional. Share: copy link / share to social.
- **Data:** `follows` (user_id, followable_type, followable_id — campaign); `comments` (user_id, commentable_type, commentable_id, body, parent_id for replies).
- **Edge cases:** Follow own campaign (no-op or allow); comment rate limit; soft-delete comments; block list later.

### 2.6 Optional escrow for high-value trades
- **Purpose:** Reduce fraud for valuable items: third party or platform holds “commitment” until both confirm.
- **User flow:** On accept, either party can request escrow (if enabled); both deposit “commitment” (e.g. photo + description); escrow released when both confirm trade.
- **Data:** `escrow_holds` (trade_id, status, requester_user_id, expires_at); evidence stored as media on trade.
- **Edge cases:** Escrow optional; expiry and refund path; no real money—escrow is “evidence” or symbolic; disputes open ticket.

### 2.7 Fraud prevention + trade confirmation
- **Purpose:** Both sides must confirm; disputes possible; audit trail.
- **User flow:** After trade created (offer accepted): both see “Confirm trade” (I received the item / completed my part); once both confirm, trade is completed and chain advances. Dispute: “Report problem” → form → admin/review.
- **Data:** `trades` (confirmed_by_offerer_at, confirmed_by_owner_at, status: pending_confirmation|completed|disputed); `disputes` (trade_id, raised_by_user_id, reason, status, resolution).
- **Edge cases:** One confirms, other doesn’t (reminders, timeout); dispute blocks chain progress until resolution; fraud patterns (many disputes) affect reputation.

### 2.8 Search + discovery (campaigns, categories, trending)
- **Purpose:** Find campaigns by category, keyword, or “trending.”
- **User flow:** Explore: grid of campaigns (card: start item, goal, owner, trade count); filters: category, status (active/completed), sort (new, most trades, most followers). Search: query against title/description/tags.
- **Data:** `campaigns` (category_id, status); `categories` (name, slug); tags on campaigns or items; trending = computed (follows + trades + comments in window) or cached.
- **Edge cases:** Empty results; SEO for public campaign URLs; index/cache invalidation when campaign updated.

### 2.9 Notifications + activity feed
- **Purpose:** Keep users informed: new offer, offer accepted/declined, new comment, new follower, trade confirmation request.
- **User flow:** Bell icon → list of notifications; “Mark read”; optional email digest. Activity feed on profile or dashboard: “Your campaigns,” “Offers you made,” “Trades involving you.”
- **Data:** `notifications` (user_id, type, data JSON, read_at); Laravel notification table or polymorphic; activity = query from trades, offers, comments.
- **Edge cases:** Rate of emails; batch “3 new offers” vs one per offer; real-time via polling or WebSockets (optional).

---

## 3. Data Model (Normalized Schema)

### 3.1 Tables overview

| Table | Purpose |
|-------|--------|
| users | Extended from default (reputation, verification) |
| categories | Campaign taxonomy |
| campaigns | One chain per campaign (start → goal) |
| items | Start/goal and offered items (linked to campaigns or offers) |
| media | Polymorphic attachments (items, campaigns, evidence) |
| offers | Proposed swap (campaign, from_user, offered_item, status) |
| trades | One row per completed step in chain (campaign, offered/received items, offer) |
| comments | Polymorphic (campaign, or trade) |
| follows | User follows campaign (polymorphic followable) |
| notifications | Per-user notification rows |
| verification_events | Email/phone/ID verification events |
| disputes | Trade dispute and resolution |
| escrow_holds | Optional escrow per trade |

### 3.2 Schema (PostgreSQL-friendly)

```text
users (existing + additions)
  id (PK)
  name, email, email_verified_at, password, remember_token
  reputation_score smallint default 0
  verified_at timestamp nullable  -- "verified trader" badge
  timestamps

categories
  id (PK)
  name, slug (unique)
  timestamps

campaigns
  id (PK)
  user_id (FK users)
  category_id (FK categories, nullable)
  status enum: draft, active, completed, paused
  visibility enum: public, unlisted
  title varchar(255)  -- optional campaign title
  story text nullable
  current_item_id (FK items, nullable)  -- denormalized: current item in chain
  goal_item_id (FK items)  -- goal item
  timestamps
  index (user_id), index (category_id), index (status), index (created_at)

items
  id (PK)
  itemable_type, itemable_id (polymorphic: Campaign start/goal, or Offer)
  role enum: start, goal, offered  -- for campaign: start|goal; for offer: offered
  title varchar(255)
  description text nullable
  timestamps
  index (itemable_type, itemable_id)

media (Laravel Media Library or custom polymorphic)
  id (PK)
  model_type, model_id (polymorphic)
  collection_name, file_name, disk, path, size, custom_properties
  timestamps
  index (model_type, model_id)

offers
  id (PK)
  campaign_id (FK campaigns)
  from_user_id (FK users)
  offered_item_id (FK items)  -- the item offerer gives
  for_campaign_item_id (FK items, nullable)  -- campaign's current item at offer time
  message text nullable
  status enum: pending, accepted, declined, withdrawn, expired
  expires_at timestamp nullable
  timestamps
  index (campaign_id), index (from_user_id), index (status)

trades
  id (PK)
  campaign_id (FK campaigns)
  offer_id (FK offers)
  position smallint  -- 1-based order in chain
  offered_item_id (FK items)  -- what was given to campaign owner
  received_item_id (FK items)  -- what campaign had before (or start item)
  status enum: pending_confirmation, completed, disputed
  confirmed_by_offerer_at, confirmed_by_owner_at timestamp nullable
  timestamps
  unique (campaign_id, position), index (campaign_id), index (offer_id)

comments
  id (PK)
  user_id (FK users)
  commentable_type, commentable_id (polymorphic: Campaign, Trade)
  parent_id (FK comments, nullable)  -- for replies
  body text
  timestamps
  index (commentable_type, commentable_id), index (user_id)

follows
  id (PK)
  user_id (FK users)
  followable_type, followable_id (polymorphic: Campaign)
  timestamps
  unique (user_id, followable_type, followable_id)

notifications
  id (PK)
  user_id (FK users)
  type varchar  -- class name or slug
  data jsonb
  read_at timestamp nullable
  timestamps
  index (user_id), index (read_at)

verification_events
  id (PK)
  user_id (FK users)
  type enum: email, phone, identity
  payload jsonb nullable  -- e.g. last4 of phone
  verified_at timestamp
  timestamps
  index (user_id)

disputes
  id (PK)
  trade_id (FK trades)
  raised_by_user_id (FK users)
  reason text
  status enum: open, resolved, rejected
  resolution text nullable
  resolved_at, resolved_by nullable
  timestamps
  index (trade_id)

escrow_holds
  id (PK)
  trade_id (FK trades)
  requester_user_id (FK users)
  status enum: active, released, expired, refunded
  expires_at timestamp
  timestamps
  index (trade_id)
```

### 3.3 Relationships (concise)
- User: hasMany campaigns, hasMany offers (from_user), hasMany comments, hasMany follows, hasMany notifications, hasMany verification_events.
- Campaign: belongsTo user, belongsTo category; hasMany items (as itemable), hasMany offers, hasMany trades, hasMany comments, hasMany follows; belongsTo current_item, goal_item (items).
- Item: belongsTo itemable (Campaign or Offer); morphMany media.
- Offer: belongsTo campaign, from_user, offered_item; hasOne trade (when accepted).
- Trade: belongsTo campaign, offer, offered_item, received_item; hasOne dispute; hasOne escrow_hold (optional).
- Comment: belongsTo user, commentable; belongsTo parent (comment).
- Follow: belongsTo user; belongsTo followable (Campaign).

### 3.4 Indexing recommendations
- Campaigns: (status, created_at) for explore; (user_id) for “my campaigns.”
- Offers: (campaign_id, status) for “offers for this campaign.”
- Trades: (campaign_id, position) for timeline order.
- Notifications: (user_id, read_at) for unread list.
- Comments: (commentable_type, commentable_id, created_at) for thread order.
- Full-text: consider `tsvector` on campaigns (title, story) and items (title, description) for search.

---

## 4. System Architecture

### 4.1 Stack (aligned with existing project)
- **Backend:** Laravel 12, PHP 8.4
- **Frontend:** Vue 3, Inertia v2, shadcn-vue (reka-ui), Tailwind v4, Wayfinder
- **Database:** PostgreSQL
- **Storage:** S3-compatible (e.g. MinIO, AWS S3) for media
- **Queue:** Redis (Laravel queue)
- **Auth:** Laravel Fortify (already in project; no Passport unless API-only clients later)
- **Image processing:** Intervention Image or Laravel Glide (resize/thumb for timeline and cards)
- **Real-time (optional):** Laravel Reverb or Pusher for “new offer” / “offer accepted” to avoid polling

### 4.2 Component interaction
- **Web:** Browser → Laravel (Inertia) → Vue SPA; Wayfinder for type-safe route calls from frontend.
- **API (if needed):** Same Laravel app; stateless API routes with Sanctum tokens for mobile later; for MVP, Inertia-only is enough.
- **Jobs:** After “accept offer” → CreateTradeJob; after “both confirmed” → UpdateCampaignCurrentItemJob, NotifyFollowersJob; image upload → ProcessMediaJob (resize, store in S3).
- **Storage:** Uploads go to local/S3 disk; `media` table points to path; URLs via `Storage::url()` or CDN.

### 4.3 Where business logic lives
- **Controllers:** Thin: validate (Form Requests), call actions/services, return Inertia response or redirect.
- **Actions/Services:** Single-purpose classes (e.g. `CreateCampaign`, `AcceptOffer`, `ConfirmTrade`, `CreateOffer`) in `app/Actions` or `app/Services`; use Eloquent and DTOs; no HTTP.
- **Models:** Scopes, relationships, accessors; no heavy logic (e.g. no “accept offer” in model).
- **Policies:** Authorization: CampaignPolicy (update, delete, accept offers), OfferPolicy (view, withdraw), TradePolicy (confirm, dispute).
- **Events/Listeners (optional):** OfferAccepted → notify followers, update campaign; TradeCompleted → reputation update, notifications.

### 4.4 Maintainability
- One action per use case; test actions with unit/feature tests.
- Form Requests for every write endpoint; validation rules and messages in one place.
- Repository only if query logic becomes complex; otherwise Eloquent in actions.
- Enums for status (campaign, offer, trade, dispute) in `app/Enums`.

---

## 5. API Design (Key Endpoints)

All require auth unless noted. Base: web routes (Inertia) + optional API prefix for future. Wayfinder generates TS route helpers.

### 5.1 Create campaign
- **POST** `/campaigns` (or named `campaigns.store`)
- **Body:** title?, story?, category_id?, start_item: { title, description }, goal_item: { title, description }, media: [files] for start/goal
- **Validation:** title max 255; story max 2000; item titles required, max 255; description max 2000; category exists; media count limit
- **Response:** 201 redirect to `campaigns.show` (campaign id)
- **Auth:** Fortify auth

### 5.2 Submit trade offer
- **POST** `/campaigns/{campaign}/offers`
- **Body:** offered_item: { title, description } or offered_item_id (if reusing), message?
- **Validation:** campaign active; not owner; offered_item present; message max 500
- **Response:** 201 redirect to campaign with success; create notification for owner
- **Auth:** required

### 5.3 Accept / decline offer
- **POST** `/offers/{offer}/accept`  
  **POST** `/offers/{offer}/decline`
- **Body (decline):** reason? (optional)
- **Validation:** offer pending; user is campaign owner; campaign still at same “current” item (no race)
- **Response:** 302; accept creates Trade (pending_confirmation), updates campaign current_item when both confirm later
- **Auth:** campaign owner

### 5.4 Confirm trade
- **POST** `/trades/{trade}/confirm`
- **Body:** (none or optional note)
- **Validation:** trade pending_confirmation; user is offerer or campaign owner; not already confirmed by this user
- **Response:** 200/302; when both confirmed → status completed, advance chain, notifications
- **Auth:** offerer or campaign owner

### 5.5 Upload item media
- **POST** `/items/{item}/media` (or multipart on campaign create/update)
- **Body:** file(s); collection: e.g. `photos`
- **Validation:** file image, max size (e.g. 5MB), user owns item’s campaign/offer
- **Response:** 201 with media id/url
- **Auth:** owner

### 5.6 Follow / unfollow campaign
- **POST** `/campaigns/{campaign}/follow`  
  **DELETE** `/campaigns/{campaign}/follow`
- **Validation:** campaign public; not following self (if campaign owner)
- **Response:** 204 or 200 { following: true|false }
- **Auth:** required

### 5.7 Comment on campaign
- **POST** `/campaigns/{campaign}/comments`
- **Body:** body, parent_id? (reply)
- **Validation:** body required, max 1000; rate limit (e.g. 10/min)
- **Response:** 201 with comment payload
- **Auth:** required

### 5.8 Fetch timeline
- **GET** `/campaigns/{campaign}` (Inertia page)
- **Query:** (none for timeline; pagination for comments/offers if needed)
- **Response:** Inertia props: campaign, timeline (trades with items + media), offers (pending), comments (paginated), auth user’s follow state
- **Auth:** optional (public campaign)

### 5.9 Search campaigns
- **GET** `/campaigns` or `/explore`
- **Query:** q?, category_id?, status?, sort=new|trades|followers, page
- **Validation:** sort enum; category exists
- **Response:** Inertia props: campaigns (paginated), categories, filters
- **Auth:** optional

### 5.10 Request/response shapes (representative)
- **Campaign (show):** id, user, category, status, title, story, start_item, goal_item, current_item, trades (array of { id, position, offered_item, received_item, status, confirmed_at }), offers_count, followers_count, comments (paginated), user_follows
- **Offer (list):** id, from_user, offered_item, message, status, created_at
- **Trade (timeline item):** id, position, offered_item, received_item, status, confirmed_by_offerer_at, confirmed_by_owner_at

---

## 6. Trade Verification Logic

### 6.1 Double-confirmation
- Trade row has `confirmed_by_offerer_at`, `confirmed_by_owner_at`.
- Either party can hit “Confirm trade” once; when both timestamps set, trade status → `completed`.
- Then: set campaign’s `current_item_id` to the item the owner received; increment `position` for next trade; update reputation; send notifications.
- Idempotent: already-confirmed user’s request returns 200 and no change.

### 6.2 Preventing fraudulent swaps
- Accept offer only if offer’s `for_campaign_item_id` equals campaign’s current `current_item_id` (reject if chain advanced).
- One accepted offer per trade position; no duplicate positions.
- Rate limit: offers per user per campaign (e.g. 3 pending at a time).
- Reputation: completed trades increase score; disputed/rejected trades decrease or flag; very low score can restrict (e.g. no escrow, or warning on offer).

### 6.3 Disputes
- Any party to the trade can open one dispute per trade (status `open`).
- Trade status → `disputed`; chain does not advance until resolved.
- Admin or support: resolve (resolution text, status `resolved`/`rejected`); optionally adjust reputation; trade can stay `disputed` or be forced to `completed`/`cancelled` by business rule.
- Notifications to both parties on resolution.

### 6.4 Optional escrow workflow
- On “Accept offer,” UI: “Use escrow for this trade?” (optional; maybe only if item value > threshold or both agree).
- If yes: create `escrow_hold` (active); both upload “evidence” (photo + short description) to trade media; when both confirm trade, escrow → released; if expiry (e.g. 14 days) and not both confirmed, escrow → expired and dispute path or refund (policy-defined).
- No real money: escrow is commitment/evidence only; disputes use same dispute table.

### 6.5 Reputation scoring (simple)
- +1 per completed trade (both confirmed); -2 per trade where user was reported and dispute resolved against; optional: decay or cap.
- “Verified trader”: email verified + (optional) phone or identity verification event; badge on profile and next to name on offers.

---

## 7. UI/UX Wireframe Descriptions

### 7.1 Campaign page
- **Header:** Campaign title (or “Start Item → Goal Item”); owner avatar + name; follow button; share.
- **Timeline (main):** Vertical timeline: Start Item (card with image, title) → Trade 1 card → Trade 2 → … → Current Item → Goal Item (greyed until reached). Each trade card: offered item, received item, dates, “Confirmed by both” or “Pending confirmation.”
- **Tabs or sections:** Offers (list of pending with accept/decline); Comments (threaded, paginated); About (story).
- **Sidebar or below:** Stats: trades count, followers, “X% to goal” (optional); CTA “Make an offer” (if logged in, not owner).

### 7.2 Create campaign flow
- **Step 1:** Start item: title, description, upload photos (drag-drop or click).
- **Step 2:** Goal item: same fields.
- **Step 3 (optional):** Campaign title, story (why), category.
- **Step 4:** Preview + Publish (or Save draft). Clear “back” and progress indicator.

### 7.3 Offer submission modal
- Trigger: “Make offer” on campaign page.
- Modal: “You’re offering:” item title + description (or “Link to your item” if we allow link-only); “Message to owner” (optional); Submit / Cancel.
- Success: toast + modal close; owner sees new offer in Offers tab and notification.

### 7.4 User profile
- **Public:** Avatar, name, verified badge; reputation score; “Member since”; list of campaigns (created); optional “Trades completed” count. Tabs: Campaigns, Activity (trades they’re part of).
- **Own profile:** Same + edit profile (name, avatar); settings link; notifications link.

### 7.5 Discovery / explore page
- **Filters:** Category dropdown; Status (active/completed); Sort (newest, most trades, most followers).
- **Grid:** Campaign cards (3–4 columns): start item image, goal item image or icon, title/one-liner, owner, trade count; click → campaign page.
- **Search bar:** Full-text on title/description; results same grid.
- **Trending block (optional):** “Trending this week” — same card component, limited set.

---

## 8. Gamification Layer

- **Progress:** “Step 3 of ?” (trades count); “Distance to goal” (optional: semantic “closer” based on category or manual tagging).
- **Milestones:** Badges or toasts: “First trade,” “5 trades,” “10 trades,” “Chain completed”; optional “First offer accepted in 24h.”
- **Trade streaks:** Consecutive trades without dispute or decline (e.g. “3-trade streak”); show on profile or campaign.
- **Social badges:** “Verified trader,” “Early supporter” (first N users), “Top offerer” (most offers accepted in period).
- **Viral sharing:** “Share my chain” (prefill link + image: start → current → goal); Open Graph meta on campaign page; “I just completed trade 5 on TradeUp” CTA.

---

## 9. Launch Strategy

### MVP scope (ship in 30 days)
- Auth (Fortify): register, login, email verify.
- Campaigns: create (start + goal items, media), draft/publish, view campaign page with timeline (read-only trades).
- Offers: create offer, list on campaign; accept/decline (accept creates trade).
- Trades: double-confirm; on both confirm, advance chain (current_item update).
- Comments on campaign (create, list, paginated).
- Follow campaign (follow/unfollow).
- Notifications: in-app (new offer, offer accepted/declined, comment, trade confirm request).
- Explore: list campaigns (active), category filter, sort by new; search by text.
- User profile: public view (campaigns, basic stats); reputation = completed trades count.
- No escrow, no disputes in MVP (manual process if needed); no WebSockets (polling or page refresh).

### 30-day build plan (high level)
- Week 1: Data model (migrations, models, relations); categories seed; campaign CRUD + start/goal items + media; campaign show page with static “timeline” (no trades yet).
- Week 2: Offers (create, list, accept/decline); trade creation on accept; confirm trade (both sides); timeline reflects trades; notifications (DB + in-app list).
- Week 3: Comments, follows; explore + search; user profile; reputation (simple count); image resize/store S3.
- Week 4: Polish (validation, errors, empty states); basic tests (campaign, offer, trade, confirm); deploy staging; seed a few campaigns.

### 90-day roadmap
- Post-MVP: Disputes (form, status, admin resolution); optional escrow; verification_events (phone/ID); reputation formula and “verified” badge; email digests; trending.
- Then: WebSockets for live notifications; share hooks and OG meta; badges and streaks; mobile-friendly PWA or API for app.

### Early community seeding
- Invite 20–50 beta users; run 2–3 “featured chains” (e.g. odd start → goal); encourage posting on social with #TradeUp; simple referral (e.g. “invite link” that pre-fills referrer).

### Metrics to track
- DAU/MAU; campaigns created (draft vs published); offers per campaign; accept rate; time to first trade and to chain completion; follows and comments per campaign; notification open rate; search usage; drop-off at each step (create campaign, make offer, confirm).

---

## 10. Risks & Mitigations

| Risk | Mitigation |
|------|------------|
| **Fraud / fake trades** | Double-confirmation; reputation; optional escrow; dispute path; rate limits on offers; flag low-rep users. |
| **Low liquidity (no offers)** | Discovery and categories; “trending” and featured campaigns; email “new campaigns in your category”; reduce friction to make first offer (simple form). |
| **User drop-off** | Clear progress (timeline, “one more trade”); notifications for new offers and confirm requests; email reminders; onboarding tooltip on first campaign. |
| **Spam / abuse** | Rate limits (offers, comments); report campaign/comment (future); block list; moderation queue for first N campaigns if needed. |
| **Trust in strangers** | Public timeline; verified badge; reputation score; optional escrow; clear “what happens when you confirm.” |
| **Scaling** | Indexes as in §3.4; queue jobs for notifications and image processing; cache trending/explore (e.g. 5 min); DB read replica later if needed. |

---

## Next step
After approval of this design, the next step is to produce a **phased implementation plan** (writing-plans) with concrete tasks, file-level changes, and test coverage for the MVP.
