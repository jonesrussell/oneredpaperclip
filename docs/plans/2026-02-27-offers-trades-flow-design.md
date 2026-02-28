# Offers & Trades Flow — Design

**Date:** 2026-02-27
**Status:** Approved
**Scope:** Wire the complete offers/trades lifecycle on the frontend, with minimal backend additions.

## Context

The backend for offers and trades is fully implemented and tested: models, enums, actions (`CreateOffer`, `AcceptOffer`, `DeclineOffer`, `ConfirmTrade`), controllers (web + API), policies, form requests, migrations, and feature tests. Wayfinder route helpers are generated.

The frontend gap: the Challenge Show page displays offers and trades in tabs, but all interactive buttons are non-functional stubs. No create offer form, no accept/decline buttons, no trade confirm buttons, no flash message system.

## Decisions

| Decision | Choice | Rationale |
|---|---|---|
| Priority | Create Offer first | Keystone — establishes UI patterns, unlocks testing the rest of the flow |
| Modal pattern | shadcn-vue Dialog (centered) | Already installed, works at all sizes, reused for confirmations |
| Feedback | Flash messages via Inertia shared data | No new dependencies, works with server redirects |
| Image upload | Single request in CreateOffer action | Avoids fragile two-step upload; small additive change to action + form request |

## Backend Changes

### `StoreOfferRequest`

Add optional image validation:

```php
'offered_item.image' => ['nullable', 'file', 'image', 'max:5120'],
```

### `CreateOffer` Action

After creating the item, if an image is present, store it using the same pattern as `ItemMediaController`:

```php
if ($image = $validated['offered_item']['image'] ?? null) {
    $path = $image->store('items/'.$offeredItem->id, 'public');
    Media::create([
        'model_type' => Item::class,
        'model_id' => $offeredItem->id,
        'collection_name' => 'default',
        'file_name' => $image->getClientOriginalName(),
        'disk' => 'public',
        'path' => $path,
        'size' => $image->getSize(),
    ]);
}
```

### Flash Messages

Add `->with('success', '...')` to redirects in:

- `OfferController@store` — "Offer submitted!"
- `OfferController@accept` — "Offer accepted — trade created!"
- `OfferController@decline` — "Offer declined."
- `TradeController@confirm` — "Trade confirmed!" (or "Trade complete!" when both confirmed)

### `HandleInertiaRequests`

Share flash data:

```php
'flash' => [
    'success' => $request->session()->get('success'),
],
```

### `ChallengeController@show` — Eager Loading

Expand relationships:

```php
'offers' => fn ($q) => $q->with(['offeredItem.media', 'fromUser'])->where('status', OfferStatus::Pending),
'trades' => fn ($q) => $q->with(['offeredItem', 'offer.fromUser'])->orderBy('position'),
```

Map trade confirmation timestamps to booleans before passing to Inertia (the frontend doesn't need raw timestamps):

```php
'owner_confirmed' => (bool) $trade->confirmed_by_owner_at,
'offerer_confirmed' => (bool) $trade->confirmed_by_offerer_at,
'offerer' => $trade->offer?->fromUser?->only('id', 'name'),
```

## Frontend Changes

### New Components

#### `CreateOfferDialog.vue`

Form dialog triggered by the "Make an Offer" buttons. Contains:

- **Item title** — required text input
- **Item description** — optional textarea
- **Image** — optional file input with preview thumbnail
- **Message to owner** — optional textarea ("Why should they accept?")
- **Submit button** — posts via Inertia `useForm` to `challenges.offers.store`

Form data shape matches `StoreOfferRequest`:

```ts
const form = useForm({
    offered_item: { title: '', description: '', image: null },
    message: '',
});
```

On success: dialog closes, Inertia redirect reloads Show page, flash message displays.

Auth guard: if user is not logged in, clicking "Make an Offer" redirects to login instead of opening the dialog.

#### `OfferCard.vue`

Extracted from Show.vue inline offer markup. Props: `offer`, `isOwner`, `challengeId`.

For pending offers when `isOwner` is true, shows:

- **Accept button** (success variant) — opens confirmation Dialog: "Accept this offer? This will create a trade with [offerer] for their [item]." Actions: Cancel / Accept Offer. Posts: `router.post(offers.accept.url({ offer: offer.id }))`.
- **Decline button** (ghost variant) — opens confirmation Dialog: "Decline this offer from [offerer]?" Actions: Cancel / Decline. Posts: `router.post(offers.decline.url({ offer: offer.id }))`.

For non-owners or non-pending offers: displays read-only card with status badge (current behavior).

#### `TradeCard.vue`

Extracted from Show.vue inline trade markup. Props: `trade`, `isOwner`, `currentUserId`.

UI states:

- **Waiting for you** — current user hasn't confirmed. Shows "Confirm Trade" button (brand variant). Opens confirmation Dialog: "Confirm you've completed this trade?" Actions: Cancel / Confirm. Posts: `router.post(trades.confirm.url({ trade: trade.id }))`.
- **Waiting for other party** — current user confirmed, other hasn't. Muted badge: "Waiting for [name] to confirm."
- **Completed** — both confirmed. Green checkmark badge.

### Modified Files

#### `Show.vue`

- Wire both "Make an Offer" buttons (`@click="showOfferDialog = true"`)
- Add auth guard (redirect to login if not authenticated)
- Replace inline offer markup with `<OfferCard>` component
- Replace inline trade markup with `<TradeCard>` component
- Import and render `<CreateOfferDialog>`
- Update TypeScript types:
  - `OfferSummary`: add `offered_item.image_url`
  - `TradeSummary`: add `offerer`, `owner_confirmed`, `offerer_confirmed`

#### `AppLayout.vue` (or new `FlashMessage.vue`)

Read `page.props.flash.success` and render a dismissible alert banner. Auto-dismiss after a few seconds or on click.

## Flow

```
Visitor clicks "Make an Offer"
  → Not logged in? Redirect to login
  → CreateOfferDialog opens
  → Fills title, description, optional image, optional message
  → Submits → POST /challenges/{id}/offers
  → Redirect back with flash "Offer submitted!"
  → Offer appears in Offers tab

Owner sees pending offer in Offers tab
  → Clicks Accept → confirmation dialog → POST /offers/{id}/accept
  → Trade created, flash "Offer accepted — trade created!"
  → Trade appears in Trades tab, offer removed from pending list

Both parties see pending trade in Trades tab
  → Click "Confirm Trade" → confirmation dialog → POST /trades/{id}/confirm
  → Flash "Trade confirmed! Waiting for other party." or "Trade complete!"
  → When both confirm: challenge current_item advances, XP awarded
```

## Not in Scope

- Withdraw offer (offerer retracting their own offer)
- Offer expiration handling
- Comment system wiring
- CelebrationOverlay integration with trade completion
- Toast/sonner system
- Offer notifications inbox
