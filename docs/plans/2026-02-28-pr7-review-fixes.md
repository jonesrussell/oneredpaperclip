# PR #7 Review Fixes Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Fix all critical, important, and select suggestion issues found in the comprehensive PR #7 review.

**Architecture:** Fixes target the notification system (User model, action classes, notification classes, Vue components), documentation accuracy (CLAUDE.md, PHPDocs), and missing test coverage. No new features — only bug fixes, hardening, and documentation corrections.

**Tech Stack:** Laravel 12, PHP 8.4, Vue 3, TypeScript, Pest v4, Inertia v2

---

### Task 1: Fix User model notification_preferences cast/accessor conflict

The `notification_preferences` attribute has both an `'array'` cast and a custom `getNotificationPreferencesAttribute(?string $value)` accessor. The cast decodes JSON first, so the accessor receives an array, calls `json_decode(array)` which returns null, and user preferences are silently ignored — defaults always returned.

**Files:**
- Modify: `app/Models/User.php:63-77,100-117`
- Test: `tests/Feature/NotificationSystemTest.php`

**Step 1: Write failing test to prove the bug exists**

Add to `tests/Feature/NotificationSystemTest.php` inside the `notification preferences` describe block:

```php
it('persists and reads back custom preferences correctly', function () {
    $user = User::factory()->create();

    $user->update([
        'notification_preferences' => [
            'offer_received' => ['database' => false, 'email' => false],
            'offer_accepted' => ['database' => true, 'email' => true],
            'offer_declined' => ['database' => true, 'email' => false],
            'trade_pending_confirmation' => ['database' => true, 'email' => true],
            'trade_completed' => ['database' => true, 'email' => true],
            'challenge_completed' => ['database' => true, 'email' => true],
        ],
    ]);

    $user->refresh();

    expect($user->wantsNotification('offer_received', 'database'))->toBeFalse();
    expect($user->wantsNotification('offer_received', 'email'))->toBeFalse();
});
```

**Step 2: Run test to verify it fails**

Run: `php artisan test --compact --filter="persists and reads back custom preferences correctly"`
Expected: FAIL — `wantsNotification` returns `true` because the accessor ignores saved prefs.

**Step 3: Fix the cast/accessor conflict**

In `app/Models/User.php`, remove the `'notification_preferences' => 'array'` cast from `casts()` and keep the accessor (which handles both JSON decoding and merging with defaults):

Remove line 76:
```php
'notification_preferences' => 'array',
```

**Step 4: Run all notification preference tests**

Run: `php artisan test --compact --filter="notification preferences"`
Expected: All PASS.

**Step 5: Run full test suite**

Run: `php artisan test --compact`
Expected: All PASS.

**Step 6: Run Pint**

Run: `vendor/bin/pint --dirty --format agent`

**Step 7: Commit**

```bash
git add app/Models/User.php tests/Feature/NotificationSystemTest.php
git commit -m "fix: resolve notification_preferences cast/accessor conflict

The 'array' cast and custom accessor conflicted — cast decoded JSON
first, then accessor called json_decode on the resulting array which
returned null. User preferences were silently ignored. Removed the
cast and let the accessor handle both decoding and default merging."
```

---

### Task 2: Fix CSRF token and fetch error handling in NotificationDropdown

The component uses `document.querySelector('meta[name="csrf-token"]')` but no such meta tag exists in `app.blade.php`. Also, `fetch()` calls don't check `response.ok`, so HTTP errors are silently swallowed.

**Files:**
- Modify: `resources/views/app.blade.php:5`
- Modify: `resources/js/components/NotificationDropdown.vue:33-86`

**Step 1: Add CSRF meta tag to app.blade.php**

In `resources/views/app.blade.php`, after line 5 (`<meta name="viewport"...>`), add:

```html
<meta name="csrf-token" content="{{ csrf_token() }}">
```

**Step 2: Fix fetch calls to check response.ok**

In `resources/js/components/NotificationDropdown.vue`, replace the three fetch functions (lines 33-86) with error-checked versions:

```typescript
async function fetchNotifications() {
    if (!isAuthenticated.value) return;

    loading.value = true;
    try {
        const response = await fetch(NotificationController.index.url());
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}`);
        }
        const data = await response.json();
        notifications.value = data.notifications;
        unreadCount.value = data.unread_count;
    } catch (error) {
        console.error('Failed to fetch notifications:', error);
    } finally {
        loading.value = false;
    }
}

async function markAsRead(id: string) {
    try {
        const response = await fetch(NotificationController.markAsRead.url({ id }), {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN':
                    document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            },
        });
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}`);
        }
        const notification = notifications.value.find((n) => n.id === id);
        if (notification) {
            notification.read_at = new Date().toISOString();
            unreadCount.value = Math.max(0, unreadCount.value - 1);
        }
    } catch (error) {
        console.error('Failed to mark notification as read:', error);
    }
}

async function markAllAsRead() {
    try {
        const response = await fetch(NotificationController.markAllAsRead.url(), {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN':
                    document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            },
        });
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}`);
        }
        notifications.value.forEach((n) => {
            if (!n.read_at) {
                n.read_at = new Date().toISOString();
            }
        });
        unreadCount.value = 0;
    } catch (error) {
        console.error('Failed to mark all notifications as read:', error);
    }
}
```

**Step 3: Run frontend linting**

Run: `npm run lint && npm run format`

**Step 4: Commit**

```bash
git add resources/views/app.blade.php resources/js/components/NotificationDropdown.vue
git commit -m "fix: add CSRF meta tag and check response.ok in NotificationDropdown

The component used document.querySelector for a CSRF meta tag that
didn't exist, causing all POST requests to 419. Also added response.ok
checks so HTTP errors are properly caught instead of producing
misleading JSON parse errors."
```

---

### Task 3: Fix ConfirmTrade race condition — move pre-state checks inside transaction

Pre-state variables (`$wasAlreadyCompleted`, `$previouslyConfirmedByOfferer`, `$previouslyConfirmedByOwner`) are captured before `lockForUpdate()`, so concurrent requests could see stale state and cause duplicate/missed notifications.

**Files:**
- Modify: `app/Actions/ConfirmTrade.php:27-108`

**Step 1: Refactor ConfirmTrade to capture pre-state inside the transaction**

Replace the `__invoke` method body (lines 27-108) so the transaction returns both the trade and pre-state data:

```php
public function __invoke(Trade $trade, User $user): Trade
{
    [$trade, $wasAlreadyCompleted, $previouslyConfirmedByOfferer, $previouslyConfirmedByOwner] = DB::transaction(function () use ($trade, $user) {
        $trade = Trade::lockForUpdate()->findOrFail($trade->id);
        $trade->load(['offer', 'challenge']);

        $wasAlreadyCompleted = $trade->status === TradeStatus::Completed;
        $previouslyConfirmedByOfferer = $trade->confirmed_by_offerer_at !== null;
        $previouslyConfirmedByOwner = $trade->confirmed_by_owner_at !== null;

        $isOfferer = $trade->offer->from_user_id === $user->id;
        $isOwner = $trade->challenge->user_id === $user->id;

        if ($isOfferer && $trade->confirmed_by_offerer_at === null) {
            $trade->update(['confirmed_by_offerer_at' => Carbon::now()]);
        }

        if ($isOwner && $trade->confirmed_by_owner_at === null) {
            $trade->update([
                'confirmed_by_owner_at' => Carbon::now(),
                'confirmed_by_offerer_at' => $trade->confirmed_by_offerer_at ?? Carbon::now(),
            ]);
        }

        $trade->refresh();

        if ($trade->confirmed_by_owner_at !== null) {
            $trade->update(['status' => TradeStatus::Completed]);
            $trade->challenge->update(['current_item_id' => $trade->offered_item_id]);
            $trade->challenge->increment('trades_count');

            $offerer = $trade->offer->fromUser;
            $owner = $trade->challenge->user;

            if ($offerer) {
                $this->xpService->awardTradeCompletion($offerer);
            }
            if ($owner) {
                $this->xpService->awardTradeCompletion($owner);
            }

            if ($trade->offered_item_id === $trade->challenge->goal_item_id) {
                $trade->challenge->update(['status' => ChallengeStatus::Completed]);
                if ($owner) {
                    $this->xpService->awardChallengeCompletion($owner);
                }
            }
        }

        return [$trade->fresh(), $wasAlreadyCompleted, $previouslyConfirmedByOfferer, $previouslyConfirmedByOwner];
    });

    $trade->load(['offer.fromUser', 'challenge.user', 'challenge.goalItem', 'offeredItem']);

    $offerer = $trade->offer->fromUser;
    $owner = $trade->challenge->user;
    $isNowCompleted = $trade->status === TradeStatus::Completed;
    $nowConfirmedByOfferer = $trade->confirmed_by_offerer_at !== null;

    if ($isNowCompleted && ! $wasAlreadyCompleted) {
        if ($offerer) {
            $offerer->notify(new TradeCompletedNotification($trade));
        }
        if ($owner) {
            $owner->notify(new TradeCompletedNotification($trade));
        }

        if ($trade->challenge->status === ChallengeStatus::Completed) {
            $this->notifyChallengeCompleted($trade);
        }
    } elseif (! $isNowCompleted) {
        if ($nowConfirmedByOfferer && ! $previouslyConfirmedByOfferer && $owner) {
            $owner->notify(new TradePendingConfirmationNotification($trade, $offerer));
        }
    }

    return $trade;
}
```

Note: Removed the dead code path where owner confirms but trade is NOT completed (impossible since owner confirmation auto-completes).

**Step 2: Run existing ConfirmTrade tests**

Run: `php artisan test --compact --filter=ConfirmTradeTest`
Expected: All PASS.

**Step 3: Run Pint**

Run: `vendor/bin/pint --dirty --format agent`

**Step 4: Commit**

```bash
git add app/Actions/ConfirmTrade.php
git commit -m "fix: move pre-state checks inside transaction in ConfirmTrade

Pre-state variables were captured before lockForUpdate, creating a race
condition where concurrent requests could see stale state and cause
duplicate or missed notifications. Also removed dead code path for
owner pending confirmation (owner confirmation always completes)."
```

---

### Task 4: Wrap notification dispatch in try-catch in action classes

Notification failures after DB commit crash the HTTP request with a 500, even though the underlying operation succeeded. Wrap `->notify()` calls in try-catch with `report()`.

**Files:**
- Modify: `app/Actions/AcceptOffer.php:45-50`
- Modify: `app/Actions/CreateOffer.php:65-69`
- Modify: `app/Actions/DeclineOffer.php:18-24`
- Modify: `app/Actions/ConfirmTrade.php` (notification section from Task 3)

**Step 1: Wrap AcceptOffer notification**

In `app/Actions/AcceptOffer.php`, replace lines 48-50 with:

```php
if ($offer->fromUser) {
    try {
        $offer->fromUser->notify(new OfferAcceptedNotification($offer, $trade));
    } catch (\Throwable $e) {
        report($e);
    }
}
```

**Step 2: Wrap CreateOffer notification**

In `app/Actions/CreateOffer.php`, replace lines 67-69 with:

```php
if ($challenge->user) {
    try {
        $challenge->user->notify(new OfferReceivedNotification($offer));
    } catch (\Throwable $e) {
        report($e);
    }
}
```

**Step 3: Wrap DeclineOffer notification**

In `app/Actions/DeclineOffer.php`, replace lines 20-22 with:

```php
if ($offer->fromUser) {
    try {
        $offer->fromUser->notify(new OfferDeclinedNotification($offer));
    } catch (\Throwable $e) {
        report($e);
    }
}
```

**Step 4: Wrap ConfirmTrade notifications**

In `app/Actions/ConfirmTrade.php`, wrap the notification block (after the transaction) in try-catch:

```php
try {
    if ($isNowCompleted && ! $wasAlreadyCompleted) {
        if ($offerer) {
            $offerer->notify(new TradeCompletedNotification($trade));
        }
        if ($owner) {
            $owner->notify(new TradeCompletedNotification($trade));
        }

        if ($trade->challenge->status === ChallengeStatus::Completed) {
            $this->notifyChallengeCompleted($trade);
        }
    } elseif (! $isNowCompleted) {
        if ($nowConfirmedByOfferer && ! $previouslyConfirmedByOfferer && $owner) {
            $owner->notify(new TradePendingConfirmationNotification($trade, $offerer));
        }
    }
} catch (\Throwable $e) {
    report($e);
}
```

**Step 5: Run all tests**

Run: `php artisan test --compact`
Expected: All PASS.

**Step 6: Run Pint**

Run: `vendor/bin/pint --dirty --format agent`

**Step 7: Commit**

```bash
git add app/Actions/AcceptOffer.php app/Actions/CreateOffer.php app/Actions/DeclineOffer.php app/Actions/ConfirmTrade.php
git commit -m "fix: wrap notification dispatch in try-catch in action classes

Notification failures after DB commit caused 500 errors even though
the underlying operation (accept/decline/create/confirm) succeeded.
Now failures are reported but don't crash the request."
```

---

### Task 5: Add null-safety in queued notification classes

Since notifications implement `ShouldQueue`, relationships could be null when the queue worker processes them (models deleted between dispatch and processing). Add null-safe operators.

**Files:**
- Modify: `app/Notifications/ChallengeCompletedNotification.php`
- Modify: `app/Notifications/OfferReceivedNotification.php`
- Modify: `app/Notifications/TradeCompletedNotification.php`
- Modify: `app/Notifications/OfferAcceptedNotification.php`
- Modify: `app/Notifications/OfferDeclinedNotification.php`
- Modify: `app/Notifications/TradePendingConfirmationNotification.php`

**Step 1: Fix ChallengeCompletedNotification**

In `app/Notifications/ChallengeCompletedNotification.php`, update `toMail()`:

```php
public function toMail(object $notifiable): MailMessage
{
    $isOwner = $notifiable->id === $this->challenge->user_id;

    if ($isOwner) {
        return (new MailMessage)
            ->subject('You Did It! Challenge Completed!')
            ->greeting('Amazing, '.$notifiable->name.'!')
            ->line('You\'ve completed your challenge "'.$this->challenge->title.'"!')
            ->line('You successfully traded up to: **'.($this->challenge->goalItem?->title ?? 'your goal').'**')
            ->line('Total trades: '.($this->challenge->trades_count ?? 0))
            ->action('View Your Achievement', url('/challenges/'.$this->challenge->id))
            ->line('Congratulations on this incredible journey!');
    }

    return (new MailMessage)
        ->subject('Challenge Completed: '.$this->challenge->title)
        ->greeting('Hi '.$notifiable->name.'!')
        ->line('A challenge you\'ve been following has been completed!')
        ->line('"'.$this->challenge->title.'" by '.($this->challenge->user?->name ?? 'someone').' reached its goal!')
        ->line('Final item: **'.($this->challenge->goalItem?->title ?? 'Unknown').'**')
        ->action('See the Journey', url('/challenges/'.$this->challenge->id))
        ->line('What an inspiring trade-up story!');
}
```

Update `toArray()`:

```php
public function toArray(object $notifiable): array
{
    return [
        'challenge_id' => $this->challenge->id,
        'challenge_title' => $this->challenge->title,
        'owner_name' => $this->challenge->user?->name ?? 'Unknown',
        'goal_item_title' => $this->challenge->goalItem?->title ?? 'Unknown',
        'trades_count' => $this->challenge->trades_count ?? 0,
    ];
}
```

**Step 2: Fix OfferReceivedNotification**

In `app/Notifications/OfferReceivedNotification.php`, update `toMail()`:

```php
public function toMail(object $notifiable): MailMessage
{
    $challenge = $this->offer->challenge;
    $offerer = $this->offer->fromUser;
    $offeredItem = $this->offer->offeredItem;

    return (new MailMessage)
        ->subject('New Offer on Your Challenge: '.($challenge?->title ?? 'your challenge'))
        ->greeting('Hey '.$notifiable->name.'!')
        ->line(($offerer?->name ?? 'Someone').' has made an offer on your challenge "'.($challenge?->title ?? 'your challenge').'".')
        ->line('They\'re offering: **'.($offeredItem?->title ?? 'an item').'**')
        ->action('View Offer', url('/challenges/'.($challenge?->id ?? '')))
        ->line('Don\'t keep them waiting - check it out!');
}
```

Update `toArray()`:

```php
public function toArray(object $notifiable): array
{
    return [
        'offer_id' => $this->offer->id,
        'challenge_id' => $this->offer->challenge_id,
        'from_user_id' => $this->offer->from_user_id,
        'from_user_name' => $this->offer->fromUser?->name ?? 'Unknown',
        'offered_item_title' => $this->offer->offeredItem?->title ?? 'Unknown',
        'challenge_title' => $this->offer->challenge?->title ?? 'Unknown',
    ];
}
```

**Step 3: Fix TradeCompletedNotification**

In `app/Notifications/TradeCompletedNotification.php`, update `toMail()`:

```php
public function toMail(object $notifiable): MailMessage
{
    $challenge = $this->trade->challenge;
    $offeredItem = $this->trade->offeredItem;

    return (new MailMessage)
        ->subject('Trade Completed!')
        ->greeting('Congratulations, '.$notifiable->name.'!')
        ->line('The trade for "'.($challenge?->title ?? 'a challenge').'" is now complete!')
        ->line('The challenge has advanced to: **'.($offeredItem?->title ?? 'the next item').'**')
        ->action('View Challenge Progress', url('/challenges/'.($challenge?->id ?? '')))
        ->line('Thanks for being part of this trade-up journey!');
}
```

Update `toArray()`:

```php
public function toArray(object $notifiable): array
{
    return [
        'trade_id' => $this->trade->id,
        'challenge_id' => $this->trade->challenge_id,
        'challenge_title' => $this->trade->challenge?->title ?? 'Unknown',
        'new_item_title' => $this->trade->offeredItem?->title ?? 'Unknown',
    ];
}
```

**Step 4: Fix OfferAcceptedNotification**

In `app/Notifications/OfferAcceptedNotification.php`, update `toArray()`:

```php
public function toArray(object $notifiable): array
{
    return [
        'offer_id' => $this->offer->id,
        'trade_id' => $this->trade->id,
        'challenge_id' => $this->offer->challenge_id,
        'challenge_title' => $this->offer->challenge?->title ?? 'Unknown',
    ];
}
```

**Step 5: Fix OfferDeclinedNotification**

In `app/Notifications/OfferDeclinedNotification.php`, update `toArray()`:

```php
public function toArray(object $notifiable): array
{
    return [
        'offer_id' => $this->offer->id,
        'challenge_id' => $this->offer->challenge_id,
        'challenge_title' => $this->offer->challenge?->title ?? 'Unknown',
    ];
}
```

**Step 6: Fix TradePendingConfirmationNotification**

In `app/Notifications/TradePendingConfirmationNotification.php`, update `toArray()`:

```php
public function toArray(object $notifiable): array
{
    return [
        'trade_id' => $this->trade->id,
        'challenge_id' => $this->trade->challenge_id,
        'challenge_title' => $this->trade->challenge?->title ?? 'Unknown',
        'confirmed_by_user_id' => $this->confirmedBy->id,
        'confirmed_by_user_name' => $this->confirmedBy->name,
    ];
}
```

**Step 7: Run all tests**

Run: `php artisan test --compact`
Expected: All PASS.

**Step 8: Run Pint**

Run: `vendor/bin/pint --dirty --format agent`

**Step 9: Commit**

```bash
git add app/Notifications/
git commit -m "fix: add null-safety to queued notification toArray/toMail methods

Since notifications implement ShouldQueue, related models could be
deleted between dispatch and queue processing. Added null-safe
operators with fallback values to prevent queue worker crashes."
```

---

### Task 6: Fix NotificationController markAllAsRead memory issue and route imports

`markAllAsRead` uses the property accessor (loads all to Collection). Also fix inline FQCN in route definitions.

**Files:**
- Modify: `app/Http/Controllers/NotificationController.php:67`
- Modify: `routes/web.php:1-4,89-92`

**Step 1: Fix markAllAsRead to use query builder**

In `app/Http/Controllers/NotificationController.php`, replace line 67:

```php
// Before:
$request->user()->unreadNotifications->markAsRead();

// After:
$request->user()->unreadNotifications()->update(['read_at' => now()]);
```

**Step 2: Fix route imports**

In `routes/web.php`, add to the imports at the top (after the existing imports):

```php
use App\Http\Controllers\NotificationController;
```

Then replace lines 89-92 to use the imported class:

```php
Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');
Route::get('notifications/unread-count', [NotificationController::class, 'unreadCount'])->name('notifications.unread-count');
Route::post('notifications/{id}/read', [NotificationController::class, 'markAsRead'])->name('notifications.mark-read');
Route::post('notifications/mark-all-read', [NotificationController::class, 'markAllAsRead'])->name('notifications.mark-all-read');
```

**Step 3: Run notification tests**

Run: `php artisan test --compact --filter=NotificationSystemTest`
Expected: All PASS.

**Step 4: Run Pint**

Run: `vendor/bin/pint --dirty --format agent`

**Step 5: Commit**

```bash
git add app/Http/Controllers/NotificationController.php routes/web.php
git commit -m "fix: use query builder for markAllAsRead and fix route imports

markAllAsRead was loading all unread notifications into memory via
property accessor. Now uses query builder for a single UPDATE.
Also cleaned up inline FQCN in routes to match file convention."
```

---

### Task 7: Update CLAUDE.md and PHPDocs to reflect current behavior

Documentation now contradicts the code in multiple places.

**Files:**
- Modify: `CLAUDE.md:36-37,45,49,53-59,67-70,84-88,98-102`
- Modify: `app/Actions/ConfirmTrade.php:23-25`
- Modify: `app/Http/Controllers/TradeController.php:25-27`
- Modify: `app/Notifications/TradePendingConfirmationNotification.php:49`
- Modify: `app/Notifications/OfferAcceptedNotification.php:48`

**Step 1: Fix CLAUDE.md domain model**

Replace line 36:
```
  → Both parties confirm the Trade (dual-confirmation via TradePolicy)
```
With:
```
  → Challenge owner confirms the Trade (owner confirmation completes; offerer can also confirm independently via TradePolicy)
```

**Step 2: Fix CLAUDE.md Trade model description**

In line 45, change `Dual-confirmation design` to `Owner-completes design` (keep the column references).

**Step 3: Fix CLAUDE.md Notification model**

Replace line 49:
```
- **Notification** — custom model (not Laravel's built-in notification system).
```
With:
```
- **Notification** — uses Laravel's built-in notification system (`Illuminate\Notifications\Notification`). Six notification types stored in the standard `notifications` table (UUID primary keys). User preferences (database/email per type) stored in `users.notification_preferences` JSON column.
```

**Step 4: Add UpdateTrade to Action classes list**

After line 59 (ConfirmTrade), add:
```
- `UpdateTrade` — updates traded item title/description/image while trade is pending confirmation (owner only)
```

**Step 5: Update Authorization section**

After line 70, add:
```
- `TradePolicy` — update: caller must be challenge owner, trade must be PendingConfirmation
```

**Step 6: Add NotificationDropdown and ShareDropdown to custom components**

After line 88 (BottomTabBar), add:
```
  - `NotificationDropdown` — bell icon with unread count badge, notification list with mark-as-read
  - `ShareDropdown` — social sharing (X, Facebook, LinkedIn, WhatsApp, copy link)
```

**Step 7: Update ConfirmTrade PHPDoc**

In `app/Actions/ConfirmTrade.php`, replace the PHPDoc (lines 23-25):
```php
/**
 * Record confirmation from offerer or challenge owner. Owner confirmation
 * completes the trade immediately and advances challenge current_item_id.
 */
```

**Step 8: Update TradeController::confirm PHPDoc**

In `app/Http/Controllers/TradeController.php`, replace lines 25-27:
```php
/**
 * Confirm the trade (offerer or challenge owner). Owner confirmation
 * completes the trade and advances the challenge current item.
 */
```

**Step 9: Fix user-facing notification text**

In `app/Notifications/TradePendingConfirmationNotification.php`, replace line 49:
```php
->line('Confirm to complete the trade!');
```

In `app/Notifications/OfferAcceptedNotification.php`, replace line 48:
```php
->line('The trade is now pending confirmation. Confirm your side to complete it!')
```

**Step 10: Run Pint**

Run: `vendor/bin/pint --dirty --format agent`

**Step 11: Commit**

```bash
git add CLAUDE.md app/Actions/ConfirmTrade.php app/Http/Controllers/TradeController.php app/Notifications/TradePendingConfirmationNotification.php app/Notifications/OfferAcceptedNotification.php
git commit -m "docs: update CLAUDE.md and PHPDocs to reflect current behavior

- Notification model now uses Laravel's built-in system, not custom
- Trade confirmation is owner-completes, not dual-confirmation
- Added UpdateTrade action, TradePolicy update, new components
- Fixed user-facing email text about confirmation flow"
```

---

### Task 8: Add missing ConfirmTrade notification dispatch tests

The most complex notification logic (3 types, up to 4 recipients) is untested. `Notification::fake()` is already called in `beforeEach`.

**Files:**
- Modify: `tests/Feature/ConfirmTradeTest.php`

**Step 1: Add notification assertions to existing tests**

Add these tests after the existing ones in `tests/Feature/ConfirmTradeTest.php`:

```php
test('offerer confirmation sends TradePendingConfirmationNotification to owner', function () {
    $this->actingAs($this->offerer)->post(route('trades.confirm', $this->trade));

    Notification::assertSentTo($this->owner, TradePendingConfirmationNotification::class);
    Notification::assertNotSentTo($this->offerer, TradePendingConfirmationNotification::class);
});

test('owner auto-complete sends TradeCompletedNotification to both parties', function () {
    $this->actingAs($this->owner)->post(route('trades.confirm', $this->trade));

    Notification::assertSentTo($this->owner, TradeCompletedNotification::class);
    Notification::assertSentTo($this->offerer, TradeCompletedNotification::class);
    Notification::assertNotSentTo($this->owner, TradePendingConfirmationNotification::class);
    Notification::assertNotSentTo($this->offerer, TradePendingConfirmationNotification::class);
});

test('offerer then owner confirm sends TradeCompletedNotification to both', function () {
    $this->actingAs($this->offerer)->post(route('trades.confirm', $this->trade));
    Notification::assertSentTo($this->owner, TradePendingConfirmationNotification::class);

    $this->actingAs($this->owner)->post(route('trades.confirm', $this->trade));

    Notification::assertSentTo($this->owner, TradeCompletedNotification::class);
    Notification::assertSentTo($this->offerer, TradeCompletedNotification::class);
});
```

Also add the required imports at the top of the file:

```php
use App\Notifications\TradeCompletedNotification;
use App\Notifications\TradePendingConfirmationNotification;
```

**Step 2: Run the new tests**

Run: `php artisan test --compact --filter=ConfirmTradeTest`
Expected: All PASS.

**Step 3: Run Pint**

Run: `vendor/bin/pint --dirty --format agent`

**Step 4: Commit**

```bash
git add tests/Feature/ConfirmTradeTest.php
git commit -m "test: add notification dispatch assertions to ConfirmTradeTest

Verifies that:
- Offerer confirmation sends TradePendingConfirmation to owner
- Owner auto-complete sends TradeCompleted to both parties
- Sequential confirm sends correct notifications at each step"
```

---

### Task 9: Run full test suite and lint as final verification

**Step 1: Run all PHP tests**

Run: `php artisan test --compact`
Expected: All PASS.

**Step 2: Run PHP linting**

Run: `vendor/bin/pint --dirty --format agent`

**Step 3: Run frontend linting**

Run: `npm run lint && npm run format`

**Step 4: Run frontend build**

Run: `npm run build`
Expected: No errors.
