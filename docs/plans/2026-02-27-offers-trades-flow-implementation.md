# Offers & Trades Flow Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Wire the complete offers/trades lifecycle frontend and add minimal backend support (flash messages, image upload, eager loading).

**Architecture:** Backend actions, policies, and tests already exist. This plan adds: flash message sharing via Inertia middleware, image upload in the CreateOffer action, expanded eager loading in ChallengeController@show, and three new Vue components (CreateOfferDialog, OfferCard, TradeCard) wired into the Challenge Show page.

**Tech Stack:** Laravel 12, Inertia v2, Vue 3, TypeScript, shadcn-vue (Dialog, Button, Badge, Input, Label), Wayfinder route helpers, Pest v4.

**Design doc:** `docs/plans/2026-02-27-offers-trades-flow-design.md`

---

## Task 1: Share Flash Messages via Inertia

**Files:**
- Modify: `app/Http/Middleware/HandleInertiaRequests.php:36-61`
- Test: `tests/Feature/FlashMessageTest.php` (create)

**Step 1: Write the failing test**

Create `tests/Feature/FlashMessageTest.php`:

```php
<?php

use App\Models\User;

test('flash success message is shared via Inertia', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->withSession(['success' => 'Test flash message'])
        ->get('/challenges');

    $response->assertInertia(fn ($page) => $page
        ->has('flash')
        ->where('flash.success', 'Test flash message')
    );
});
```

**Step 2: Run test to verify it fails**

Run: `php artisan test --compact --filter="flash success message is shared"`
Expected: FAIL — `flash` prop not present.

**Step 3: Add flash sharing to HandleInertiaRequests**

In `app/Http/Middleware/HandleInertiaRequests.php`, add to the `share()` return array (after the `sidebarOpen` line):

```php
'flash' => [
    'success' => fn () => $request->session()->get('success'),
],
```

**Step 4: Run test to verify it passes**

Run: `php artisan test --compact --filter="flash success message is shared"`
Expected: PASS

**Step 5: Update TypeScript types for flash**

In `resources/js/types/global.d.ts`, add `flash` to `sharedPageProps`:

```ts
flash: {
    success: string | null;
};
```

**Step 6: Commit**

```
feat: share flash messages via Inertia middleware
```

---

## Task 2: Add Flash Messages to Offer and Trade Controllers

**Files:**
- Modify: `app/Http/Controllers/OfferController.php:20-23,33-34,45-46`
- Modify: `app/Http/Controllers/TradeController.php:16-18`
- Test: `tests/Feature/AcceptOfferTest.php` (add assertion)
- Test: `tests/Feature/DeclineOfferTest.php` (add assertion)
- Test: `tests/Feature/ConfirmTradeTest.php` (add assertion)
- Test: `tests/Feature/CreateOfferTest.php` (create)

**Step 1: Write a failing test for create offer flash**

Create `tests/Feature/CreateOfferTest.php`:

```php
<?php

use App\Models\Category;
use App\Models\Challenge;
use App\Models\Item;
use App\Models\User;

uses()->group('offers');

beforeEach(function () {
    $this->owner = User::factory()->create();
    $this->offerer = User::factory()->create();
    $category = Category::create(['name' => 'Electronics', 'slug' => 'electronics']);
    $challenge = Challenge::create([
        'user_id' => $this->owner->id,
        'category_id' => $category->id,
        'status' => 'active',
        'visibility' => 'public',
        'title' => 'Red paperclip to house',
        'story' => 'Starting with one red paperclip.',
        'current_item_id' => null,
        'goal_item_id' => null,
    ]);
    $startItem = Item::create([
        'itemable_type' => Challenge::class,
        'itemable_id' => $challenge->id,
        'role' => 'start',
        'title' => 'One red paperclip',
        'description' => 'A single red paperclip.',
    ]);
    $challenge->update(['current_item_id' => $startItem->id]);
    $this->challenge = $challenge->fresh();
});

test('authenticated user can create offer and sees flash message', function () {
    $response = $this->actingAs($this->offerer)->post(
        route('challenges.offers.store', $this->challenge),
        [
            'offered_item' => [
                'title' => 'A pen',
                'description' => 'Blue ballpoint.',
            ],
            'message' => 'I offer my pen.',
        ]
    );

    $response->assertRedirect()
        ->assertSessionHas('success', 'Offer submitted!');
});

test('guest cannot create offer', function () {
    $response = $this->post(
        route('challenges.offers.store', $this->challenge),
        [
            'offered_item' => ['title' => 'A pen'],
        ]
    );

    $response->assertRedirect('/login');
});
```

**Step 2: Run test to verify it fails**

Run: `php artisan test --compact --filter="authenticated user can create offer and sees flash"`
Expected: FAIL — no `success` in session.

**Step 3: Add flash messages to OfferController**

In `app/Http/Controllers/OfferController.php`:

`store` method — change the return to:
```php
return redirect()->route('challenges.show', $challenge)
    ->with('success', 'Offer submitted!');
```

`accept` method — change the return to:
```php
return redirect()->route('challenges.show', $offer->challenge)
    ->with('success', 'Offer accepted — trade created!');
```

`decline` method — change the return to:
```php
return redirect()->route('challenges.show', $offer->challenge)
    ->with('success', 'Offer declined.');
```

**Step 4: Add flash message to TradeController**

In `app/Http/Controllers/TradeController.php`, the `confirm` method needs to return different messages depending on whether the trade is now complete. Change to:

```php
public function confirm(Trade $trade, ConfirmTrade $confirmTrade): RedirectResponse
{
    $this->authorize('confirm', $trade);

    $trade = $confirmTrade($trade, request()->user());

    $message = $trade->status === TradeStatus::Completed
        ? 'Trade complete!'
        : 'Trade confirmed! Waiting for the other party.';

    return redirect()->route('challenges.show', $trade->challenge)
        ->with('success', $message);
}
```

This requires adding the import: `use App\Enums\TradeStatus;`

**Step 5: Add flash assertions to existing tests**

In `tests/Feature/AcceptOfferTest.php`, in the first test (line 58), add after `$response->assertRedirect();`:
```php
$response->assertSessionHas('success', 'Offer accepted — trade created!');
```

In `tests/Feature/DeclineOfferTest.php`, in the owner-can-decline test, add:
```php
$response->assertSessionHas('success', 'Offer declined.');
```

In `tests/Feature/ConfirmTradeTest.php`, in the "when both confirm" test (line 79), add:
```php
$response->assertSessionHas('success', 'Trade complete!');
```

**Step 6: Run all affected tests**

Run: `php artisan test --compact --filter="CreateOfferTest|AcceptOfferTest|DeclineOfferTest|ConfirmTradeTest"`
Expected: All PASS.

**Step 7: Commit**

```
feat: add flash messages to offer and trade controllers
```

---

## Task 3: Add Image Upload to CreateOffer Action

**Files:**
- Modify: `app/Http/Requests/StoreOfferRequest.php:17-23`
- Modify: `app/Actions/CreateOffer.php`
- Test: `tests/Feature/CreateOfferTest.php` (add image test)

**Step 1: Write a failing test for image upload**

Add to `tests/Feature/CreateOfferTest.php`:

```php
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('offer can include an image for the offered item', function () {
    Storage::fake('public');

    $response = $this->actingAs($this->offerer)->post(
        route('challenges.offers.store', $this->challenge),
        [
            'offered_item' => [
                'title' => 'A pen',
                'description' => 'Blue ballpoint.',
                'image' => UploadedFile::fake()->image('pen.jpg', 640, 480),
            ],
            'message' => 'I offer my pen.',
        ]
    );

    $response->assertRedirect();

    $offer = \App\Models\Offer::where('challenge_id', $this->challenge->id)->latest()->first();
    $item = $offer->offeredItem;

    expect($item->media)->toHaveCount(1);
    Storage::disk('public')->assertExists($item->media->first()->path);
});
```

**Step 2: Run test to verify it fails**

Run: `php artisan test --compact --filter="offer can include an image"`
Expected: FAIL — media count is 0 (image not processed).

**Step 3: Add image validation rule**

In `app/Http/Requests/StoreOfferRequest.php`, add to the rules array:

```php
'offered_item.image' => ['nullable', 'file', 'image', 'max:5120'],
```

**Step 4: Add image handling to CreateOffer action**

In `app/Actions/CreateOffer.php`, add import at top:

```php
use App\Models\Media;
use Illuminate\Http\UploadedFile;
```

After the `$offeredItem = Item::create(...)` block (after line 32), add:

```php
if (($image = $validated['offered_item']['image'] ?? null) instanceof UploadedFile) {
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

**Step 5: Run test to verify it passes**

Run: `php artisan test --compact --filter="offer can include an image"`
Expected: PASS

**Step 6: Run all offer tests to check for regressions**

Run: `php artisan test --compact --group=offers`
Expected: All PASS.

**Step 7: Commit**

```
feat: support image upload in create offer flow
```

---

## Task 4: Expand Eager Loading in ChallengeController@show

**Files:**
- Modify: `app/Http/Controllers/ChallengeController.php:119-165`
- Test: `tests/Feature/ChallengeShowTest.php` (create)

**Step 1: Write a failing test**

Create `tests/Feature/ChallengeShowTest.php`:

```php
<?php

use App\Enums\OfferStatus;
use App\Enums\TradeStatus;
use App\Models\Category;
use App\Models\Challenge;
use App\Models\Item;
use App\Models\Offer;
use App\Models\Trade;
use App\Models\User;

beforeEach(function () {
    $this->owner = User::factory()->create();
    $this->offerer = User::factory()->create();
    $category = Category::create(['name' => 'Electronics', 'slug' => 'electronics']);
    $challenge = Challenge::create([
        'user_id' => $this->owner->id,
        'category_id' => $category->id,
        'status' => 'active',
        'visibility' => 'public',
        'title' => 'Test challenge',
        'current_item_id' => null,
        'goal_item_id' => null,
    ]);
    $startItem = Item::create([
        'itemable_type' => Challenge::class,
        'itemable_id' => $challenge->id,
        'role' => 'start',
        'title' => 'Paperclip',
    ]);
    $challenge->update(['current_item_id' => $startItem->id]);
    $this->challenge = $challenge->fresh();
});

test('show page includes offer from_user data', function () {
    $offeredItem = Item::create([
        'itemable_type' => User::class,
        'itemable_id' => $this->offerer->id,
        'role' => 'offered',
        'title' => 'A pen',
    ]);
    Offer::create([
        'challenge_id' => $this->challenge->id,
        'from_user_id' => $this->offerer->id,
        'offered_item_id' => $offeredItem->id,
        'for_challenge_item_id' => $this->challenge->current_item_id,
        'status' => OfferStatus::Pending,
    ]);

    $response = $this->actingAs($this->owner)->get(
        route('challenges.show', $this->challenge)
    );

    $response->assertInertia(fn ($page) => $page
        ->has('challenge.offers.0.from_user')
        ->where('challenge.offers.0.from_user.name', $this->offerer->name)
    );
});

test('show page includes trade confirmation booleans and offerer', function () {
    $offeredItem = Item::create([
        'itemable_type' => User::class,
        'itemable_id' => $this->offerer->id,
        'role' => 'offered',
        'title' => 'A pen',
    ]);
    $offer = Offer::create([
        'challenge_id' => $this->challenge->id,
        'from_user_id' => $this->offerer->id,
        'offered_item_id' => $offeredItem->id,
        'for_challenge_item_id' => $this->challenge->current_item_id,
        'status' => OfferStatus::Accepted,
    ]);
    Trade::create([
        'challenge_id' => $this->challenge->id,
        'offer_id' => $offer->id,
        'position' => 1,
        'offered_item_id' => $offeredItem->id,
        'received_item_id' => $this->challenge->current_item_id,
        'status' => TradeStatus::PendingConfirmation,
        'confirmed_by_owner_at' => now(),
    ]);

    $response = $this->actingAs($this->owner)->get(
        route('challenges.show', $this->challenge)
    );

    $response->assertInertia(fn ($page) => $page
        ->has('challenge.trades.0.owner_confirmed')
        ->where('challenge.trades.0.owner_confirmed', true)
        ->where('challenge.trades.0.offerer_confirmed', false)
        ->has('challenge.trades.0.offerer')
        ->where('challenge.trades.0.offerer.name', $this->offerer->name)
    );
});
```

**Step 2: Run tests to verify they fail**

Run: `php artisan test --compact --filter="ChallengeShowTest"`
Expected: FAIL — missing `from_user`, `owner_confirmed`, `offerer` props.

**Step 3: Update ChallengeController@show**

In `app/Http/Controllers/ChallengeController.php`, replace the eager loading block (lines 127-136) with:

```php
$challenge->load([
    'items',
    'trades' => fn ($q) => $q->with(['offeredItem', 'offer.fromUser'])->orderBy('position'),
    'offers' => fn ($q) => $q->with(['offeredItem.media', 'fromUser'])->where('status', OfferStatus::Pending),
    'comments' => fn ($q) => $q->with('user')->latest()->limit(20),
    'user',
    'category',
    'currentItem.media',
    'goalItem.media',
]);
```

Then, after the `$challenge->setAttribute('story_safe', ...)` line (line 147), add trade data mapping:

```php
$challenge->trades->transform(function ($trade) {
    $trade->setAttribute('owner_confirmed', (bool) $trade->confirmed_by_owner_at);
    $trade->setAttribute('offerer_confirmed', (bool) $trade->confirmed_by_offerer_at);
    $trade->setAttribute('offerer', $trade->offer?->fromUser?->only('id', 'name'));

    return $trade;
});
```

**Step 4: Run tests to verify they pass**

Run: `php artisan test --compact --filter="ChallengeShowTest"`
Expected: All PASS.

**Step 5: Run existing challenge tests for regressions**

Run: `php artisan test --compact --filter="ChallengeController"`
Expected: All PASS.

**Step 6: Commit**

```
feat: expand eager loading for offers and trades on show page
```

---

## Task 5: Create FlashMessage Component

**Files:**
- Create: `resources/js/components/FlashMessage.vue`
- Modify: `resources/js/layouts/AppLayout.vue`

**Step 1: Create FlashMessage.vue**

Create `resources/js/components/FlashMessage.vue`:

```vue
<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { X } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

import { Alert, AlertDescription } from '@/components/ui/alert';

const page = usePage();
const dismissed = ref(false);

const message = computed(() => page.props.flash?.success ?? null);

watch(message, (val) => {
    if (val) {
        dismissed.value = false;
        setTimeout(() => {
            dismissed.value = true;
        }, 5000);
    }
});
</script>

<template>
    <div
        v-if="message && !dismissed"
        class="fixed top-4 right-4 z-50 max-w-sm animate-in fade-in slide-in-from-top-2"
    >
        <Alert
            class="border-[var(--electric-mint)]/30 bg-[var(--electric-mint)]/10 text-foreground shadow-lg"
        >
            <AlertDescription class="flex items-center justify-between gap-2">
                <span>{{ message }}</span>
                <button
                    class="shrink-0 text-muted-foreground hover:text-foreground"
                    @click="dismissed = true"
                >
                    <X class="size-4" />
                </button>
            </AlertDescription>
        </Alert>
    </div>
</template>
```

**Step 2: Add FlashMessage to AppLayout**

In `resources/js/layouts/AppLayout.vue`, import and render `FlashMessage` inside the layout (after the opening layout wrapper, before the main content slot):

```ts
import FlashMessage from '@/components/FlashMessage.vue';
```

Add `<FlashMessage />` in the template at the top level (inside the root element).

Also add it to `resources/js/layouts/PublicLayout.vue` if it exists (public challenge pages use it).

**Step 3: Run lint and build to verify no errors**

Run: `npm run lint && npm run build`
Expected: Clean.

**Step 4: Commit**

```
feat: add flash message component to app layout
```

---

## Task 6: Create the CreateOfferDialog Component

**Files:**
- Create: `resources/js/components/CreateOfferDialog.vue`

**Step 1: Create the component**

Create `resources/js/components/CreateOfferDialog.vue`:

```vue
<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { ImagePlus, X } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

import { store } from '@/actions/App/Http/Controllers/OfferController';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

const props = defineProps<{
    challengeId: number;
    currentItemTitle: string;
    open: boolean;
}>();

const emit = defineEmits<{
    'update:open': [value: boolean];
}>();

const isOpen = computed({
    get: () => props.open,
    set: (val) => emit('update:open', val),
});

const form = useForm({
    offered_item: {
        title: '',
        description: '',
        image: null as File | null,
    },
    message: '',
});

const imagePreview = ref<string | null>(null);
const fileInput = ref<HTMLInputElement | null>(null);

function onFileChange(event: Event) {
    const target = event.target as HTMLInputElement;
    const file = target.files?.[0] ?? null;
    form.offered_item.image = file;
    if (file) {
        const reader = new FileReader();
        reader.onload = (e) => {
            imagePreview.value = e.target?.result as string;
        };
        reader.readAsDataURL(file);
    } else {
        imagePreview.value = null;
    }
}

function removeImage() {
    form.offered_item.image = null;
    imagePreview.value = null;
    if (fileInput.value) {
        fileInput.value.value = '';
    }
}

function submit() {
    form.post(store.url({ challenge: props.challengeId }), {
        forceFormData: true,
        onSuccess: () => {
            form.reset();
            imagePreview.value = null;
            isOpen.value = false;
        },
    });
}

watch(isOpen, (val) => {
    if (!val) {
        form.clearErrors();
    }
});
</script>

<template>
    <Dialog v-model:open="isOpen">
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle class="font-display">Make an Offer</DialogTitle>
                <DialogDescription>
                    Offer something in exchange for
                    <strong>{{ currentItemTitle }}</strong>
                </DialogDescription>
            </DialogHeader>

            <form class="space-y-4" @submit.prevent="submit">
                <div class="space-y-2">
                    <Label for="offer-title">What are you offering?</Label>
                    <Input
                        id="offer-title"
                        v-model="form.offered_item.title"
                        placeholder="e.g. A vintage typewriter"
                        required
                    />
                    <p
                        v-if="form.errors['offered_item.title']"
                        class="text-sm text-destructive"
                    >
                        {{ form.errors['offered_item.title'] }}
                    </p>
                </div>

                <div class="space-y-2">
                    <Label for="offer-description">
                        Description
                        <span class="text-muted-foreground">(optional)</span>
                    </Label>
                    <textarea
                        id="offer-description"
                        v-model="form.offered_item.description"
                        class="border-input bg-background ring-offset-background placeholder:text-muted-foreground focus-visible:ring-ring flex min-h-[80px] w-full rounded-md border px-3 py-2 text-sm focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                        placeholder="Describe your item..."
                    />
                    <p
                        v-if="form.errors['offered_item.description']"
                        class="text-sm text-destructive"
                    >
                        {{ form.errors['offered_item.description'] }}
                    </p>
                </div>

                <div class="space-y-2">
                    <Label>
                        Photo
                        <span class="text-muted-foreground">(optional)</span>
                    </Label>
                    <div v-if="imagePreview" class="relative inline-block">
                        <img
                            :src="imagePreview"
                            alt="Preview"
                            class="h-24 w-24 rounded-lg object-cover"
                        />
                        <button
                            type="button"
                            class="absolute -top-2 -right-2 rounded-full bg-destructive p-1 text-white shadow-sm"
                            @click="removeImage"
                        >
                            <X class="size-3" />
                        </button>
                    </div>
                    <div v-else>
                        <button
                            type="button"
                            class="flex h-24 w-24 items-center justify-center rounded-lg border-2 border-dashed border-border text-muted-foreground transition-colors hover:border-foreground hover:text-foreground"
                            @click="fileInput?.click()"
                        >
                            <ImagePlus class="size-6" />
                        </button>
                    </div>
                    <input
                        ref="fileInput"
                        type="file"
                        accept="image/*"
                        class="hidden"
                        @change="onFileChange"
                    />
                    <p
                        v-if="form.errors['offered_item.image']"
                        class="text-sm text-destructive"
                    >
                        {{ form.errors['offered_item.image'] }}
                    </p>
                </div>

                <div class="space-y-2">
                    <Label for="offer-message">
                        Message to owner
                        <span class="text-muted-foreground">(optional)</span>
                    </Label>
                    <textarea
                        id="offer-message"
                        v-model="form.message"
                        class="border-input bg-background ring-offset-background placeholder:text-muted-foreground focus-visible:ring-ring flex min-h-[60px] w-full rounded-md border px-3 py-2 text-sm focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                        placeholder="Why should they accept this trade?"
                    />
                    <p
                        v-if="form.errors.message"
                        class="text-sm text-destructive"
                    >
                        {{ form.errors.message }}
                    </p>
                </div>

                <DialogFooter>
                    <Button
                        type="button"
                        variant="outline"
                        @click="isOpen = false"
                    >
                        Cancel
                    </Button>
                    <Button
                        type="submit"
                        variant="brand"
                        :disabled="form.processing"
                    >
                        {{ form.processing ? 'Submitting...' : 'Submit Offer' }}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
```

**Step 2: Run lint and build**

Run: `npm run lint && npm run build`
Expected: Clean (component not yet mounted anywhere, but should compile).

**Step 3: Commit**

```
feat: create CreateOfferDialog component
```

---

## Task 7: Create OfferCard Component

**Files:**
- Create: `resources/js/components/OfferCard.vue`

**Step 1: Create the component**

Create `resources/js/components/OfferCard.vue`:

```vue
<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { Check, X } from 'lucide-vue-next';
import { ref } from 'vue';

import { accept, decline } from '@/actions/App/Http/Controllers/OfferController';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';

type OfferSummary = {
    id: number;
    status: string;
    message?: string | null;
    from_user?: { id: number; name: string } | null;
    offered_item?: {
        id: number;
        title: string;
        image_url?: string | null;
    } | null;
};

const props = defineProps<{
    offer: OfferSummary;
    isOwner: boolean;
}>();

const showAcceptDialog = ref(false);
const showDeclineDialog = ref(false);
const processing = ref(false);

function acceptOffer() {
    processing.value = true;
    router.post(accept.url({ offer: props.offer.id }), {}, {
        onFinish: () => {
            processing.value = false;
            showAcceptDialog.value = false;
        },
    });
}

function declineOffer() {
    processing.value = true;
    router.post(decline.url({ offer: props.offer.id }), {}, {
        onFinish: () => {
            processing.value = false;
            showDeclineDialog.value = false;
        },
    });
}
</script>

<template>
    <div
        class="rounded-2xl border border-border bg-card p-4 shadow-sm transition-all hover:shadow-md dark:shadow-[var(--shadow-card)]"
    >
        <div class="flex items-start justify-between gap-3">
            <div class="flex items-start gap-3">
                <div
                    class="flex size-12 shrink-0 items-center justify-center overflow-hidden rounded-xl bg-[var(--sunny-yellow)]/20"
                >
                    <img
                        v-if="offer.offered_item?.image_url"
                        :src="offer.offered_item.image_url"
                        :alt="offer.offered_item?.title ?? 'Offered item'"
                        class="size-12 rounded-xl object-cover"
                    />
                    <span v-else class="text-xl">📦</span>
                </div>
                <div>
                    <p class="font-display font-semibold text-foreground">
                        {{ offer.offered_item?.title ?? 'Unknown item' }}
                    </p>
                    <p class="mt-0.5 text-xs text-muted-foreground">
                        from {{ offer.from_user?.name ?? 'Anonymous' }}
                    </p>
                    <p
                        v-if="offer.message"
                        class="mt-2 text-sm text-muted-foreground"
                    >
                        "{{ offer.message }}"
                    </p>
                </div>
            </div>

            <!-- Owner actions for pending offers -->
            <div
                v-if="isOwner && offer.status === 'pending'"
                class="flex shrink-0 items-center gap-1.5"
            >
                <Button
                    variant="ghost"
                    size="icon"
                    class="size-8 text-destructive hover:bg-destructive/10"
                    @click="showDeclineDialog = true"
                >
                    <X class="size-4" />
                </Button>
                <Button
                    variant="ghost"
                    size="icon"
                    class="size-8 text-[var(--electric-mint)] hover:bg-[var(--electric-mint)]/10"
                    @click="showAcceptDialog = true"
                >
                    <Check class="size-4" />
                </Button>
            </div>

            <!-- Status badge for non-actionable offers -->
            <Badge
                v-else
                variant="secondary"
                class="shrink-0 rounded-full text-xs capitalize"
            >
                {{ offer.status }}
            </Badge>
        </div>
    </div>

    <!-- Accept confirmation dialog -->
    <Dialog v-model:open="showAcceptDialog">
        <DialogContent class="sm:max-w-sm">
            <DialogHeader>
                <DialogTitle class="font-display">Accept Offer</DialogTitle>
                <DialogDescription>
                    Accept
                    <strong>{{ offer.offered_item?.title }}</strong>
                    from
                    <strong>{{ offer.from_user?.name }}</strong>?
                    This will create a trade.
                </DialogDescription>
            </DialogHeader>
            <DialogFooter>
                <Button
                    variant="outline"
                    @click="showAcceptDialog = false"
                >
                    Cancel
                </Button>
                <Button
                    variant="brand"
                    :disabled="processing"
                    @click="acceptOffer"
                >
                    {{ processing ? 'Accepting...' : 'Accept Offer' }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>

    <!-- Decline confirmation dialog -->
    <Dialog v-model:open="showDeclineDialog">
        <DialogContent class="sm:max-w-sm">
            <DialogHeader>
                <DialogTitle class="font-display">Decline Offer</DialogTitle>
                <DialogDescription>
                    Decline this offer from
                    <strong>{{ offer.from_user?.name }}</strong>?
                </DialogDescription>
            </DialogHeader>
            <DialogFooter>
                <Button
                    variant="outline"
                    @click="showDeclineDialog = false"
                >
                    Cancel
                </Button>
                <Button
                    variant="destructive"
                    :disabled="processing"
                    @click="declineOffer"
                >
                    {{ processing ? 'Declining...' : 'Decline' }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
```

**Step 2: Run lint and build**

Run: `npm run lint && npm run build`
Expected: Clean.

**Step 3: Commit**

```
feat: create OfferCard component with accept/decline dialogs
```

---

## Task 8: Create TradeCard Component

**Files:**
- Create: `resources/js/components/TradeCard.vue`

**Step 1: Create the component**

Create `resources/js/components/TradeCard.vue`:

```vue
<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { ref } from 'vue';

import { confirm } from '@/actions/App/Http/Controllers/TradeController';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';

type TradeSummary = {
    id: number;
    status: string;
    position: number;
    offered_item?: { id: number; title: string; image_url?: string | null } | null;
    offerer?: { id: number; name: string } | null;
    owner_confirmed: boolean;
    offerer_confirmed: boolean;
};

const props = defineProps<{
    trade: TradeSummary;
    isOwner: boolean;
    currentUserId: number;
}>();

const showConfirmDialog = ref(false);
const processing = ref(false);

const userHasConfirmed = props.isOwner
    ? props.trade.owner_confirmed
    : props.trade.offerer_confirmed;

const otherPartyName = props.isOwner
    ? (props.trade.offerer?.name ?? 'the offerer')
    : 'the challenge owner';

function confirmTrade() {
    processing.value = true;
    router.post(confirm.url({ trade: props.trade.id }), {}, {
        onFinish: () => {
            processing.value = false;
            showConfirmDialog.value = false;
        },
    });
}
</script>

<template>
    <div
        class="rounded-2xl border border-border bg-card p-4 shadow-sm transition-all hover:shadow-md dark:shadow-[var(--shadow-card)]"
    >
        <div class="flex items-center justify-between gap-3">
            <div class="flex items-center gap-3">
                <div
                    class="flex size-12 items-center justify-center overflow-hidden rounded-xl"
                    :class="
                        trade.status === 'completed'
                            ? 'bg-[var(--electric-mint)]/20'
                            : 'bg-[var(--hot-coral)]/20'
                    "
                >
                    <img
                        v-if="trade.offered_item?.image_url"
                        :src="trade.offered_item.image_url"
                        :alt="trade.offered_item?.title ?? 'Trade item'"
                        class="size-12 rounded-xl object-cover"
                    />
                    <span v-else class="text-xl">
                        {{ trade.status === 'completed' ? '✓' : '⏳' }}
                    </span>
                </div>
                <div>
                    <p class="font-display font-semibold text-foreground">
                        Trade #{{ trade.position }}
                    </p>
                    <p class="mt-0.5 text-sm text-muted-foreground">
                        {{ trade.offered_item?.title ?? 'Unknown item' }}
                    </p>
                    <p
                        v-if="trade.offerer"
                        class="mt-0.5 text-xs text-muted-foreground"
                    >
                        with {{ trade.offerer.name }}
                    </p>
                </div>
            </div>

            <!-- Completed -->
            <Badge
                v-if="trade.status === 'completed'"
                variant="secondary"
                class="shrink-0 rounded-full bg-[var(--electric-mint)]/15 text-xs text-[var(--electric-mint)]"
            >
                Completed
            </Badge>

            <!-- Pending: user needs to confirm -->
            <Button
                v-else-if="!userHasConfirmed"
                variant="brand"
                size="sm"
                @click="showConfirmDialog = true"
            >
                Confirm Trade
            </Button>

            <!-- Pending: waiting for other party -->
            <Badge
                v-else
                variant="secondary"
                class="shrink-0 rounded-full bg-[var(--hot-coral)]/15 text-xs text-[var(--hot-coral)]"
            >
                Waiting for {{ otherPartyName }}
            </Badge>
        </div>
    </div>

    <!-- Confirm trade dialog -->
    <Dialog v-model:open="showConfirmDialog">
        <DialogContent class="sm:max-w-sm">
            <DialogHeader>
                <DialogTitle class="font-display">Confirm Trade</DialogTitle>
                <DialogDescription>
                    Confirm that you've completed this trade for
                    <strong>{{ trade.offered_item?.title }}</strong>?
                </DialogDescription>
            </DialogHeader>
            <DialogFooter>
                <Button
                    variant="outline"
                    @click="showConfirmDialog = false"
                >
                    Cancel
                </Button>
                <Button
                    variant="brand"
                    :disabled="processing"
                    @click="confirmTrade"
                >
                    {{ processing ? 'Confirming...' : 'Confirm' }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
```

**Step 2: Run lint and build**

Run: `npm run lint && npm run build`
Expected: Clean.

**Step 3: Commit**

```
feat: create TradeCard component with confirm dialog
```

---

## Task 9: Wire Everything into Challenge Show Page

**Files:**
- Modify: `resources/js/pages/challenges/Show.vue`

This is the integration task. Wire the three new components into Show.vue.

**Step 1: Update TypeScript types**

At the top of Show.vue, update the types:

Replace the existing `OfferSummary` type with:
```ts
type OfferSummary = {
    id: number;
    status: string;
    message?: string | null;
    from_user?: { id: number; name: string } | null;
    offered_item?: {
        id: number;
        title: string;
        image_url?: string | null;
    } | null;
};
```

Replace the existing `TradeSummary` type with:
```ts
type TradeSummary = {
    id: number;
    status: string;
    position: number;
    offered_item?: {
        id: number;
        title: string;
        image_url?: string | null;
    } | null;
    offerer?: { id: number; name: string } | null;
    owner_confirmed: boolean;
    offerer_confirmed: boolean;
};
```

**Step 2: Add imports**

Add to the `<script setup>` imports:

```ts
import { router } from '@inertiajs/vue3';
import CreateOfferDialog from '@/components/CreateOfferDialog.vue';
import OfferCard from '@/components/OfferCard.vue';
import TradeCard from '@/components/TradeCard.vue';
```

**Step 3: Add offer dialog state**

After the existing `const isOwner` computed, add:

```ts
const currentUser = computed(() => page.props.auth?.user);
const showOfferDialog = ref(false);

function handleMakeOffer() {
    if (!currentUser.value) {
        router.visit('/login');
        return;
    }
    showOfferDialog.value = true;
}
```

**Step 4: Wire the "Make an Offer" buttons**

The sidebar CTA button (around line 414): add `@click="handleMakeOffer"` to the Button.

The mobile sticky footer button (around line 683): add `@click="handleMakeOffer"` to the Button.

**Step 5: Replace inline offer cards with OfferCard**

Replace the offer `v-for` block (lines 492-537) with:

```vue
<OfferCard
    v-for="offer in challenge.offers"
    :key="offer.id"
    :offer="offer"
    :is-owner="isOwner"
/>
```

**Step 6: Replace inline trade cards with TradeCard**

Replace the trade `v-for` block (lines 554-611) with:

```vue
<TradeCard
    v-for="trade in challenge.trades"
    :key="trade.id"
    :trade="trade"
    :is-owner="isOwner"
    :current-user-id="currentUser?.id ?? 0"
/>
```

**Step 7: Add CreateOfferDialog to template**

Before the `<!-- Celebration overlay -->` comment (around line 688), add:

```vue
<CreateOfferDialog
    :challenge-id="challenge.id"
    :current-item-title="challenge.current_item?.title ?? 'this item'"
    v-model:open="showOfferDialog"
/>
```

**Step 8: Run lint and build**

Run: `npm run lint && npm run build`
Expected: Clean.

**Step 9: Run PHP tests for regressions**

Run: `php artisan test --compact`
Expected: All PASS.

**Step 10: Commit**

```
feat: wire offers and trades flow into challenge show page
```

---

## Task 10: Run Pint and Final Verification

**Step 1: Format PHP**

Run: `vendor/bin/pint --dirty`
Expected: Clean or minor formatting fixes.

**Step 2: Format frontend**

Run: `npm run lint && npm run format`
Expected: Clean.

**Step 3: Run full test suite**

Run: `php artisan test --compact`
Expected: All PASS.

**Step 4: Build production assets**

Run: `npm run build`
Expected: Clean build.

**Step 5: Final commit if any formatting changes**

```
style: format with pint and prettier
```
