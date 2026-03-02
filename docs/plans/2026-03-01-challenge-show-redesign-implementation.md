# Challenge Show Page Redesign — Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Switch the challenge show page from the sidebar dashboard layout to PublicLayout and reorganize content into a flowing hero + trade path + tabs structure.

**Architecture:** Pure frontend refactor of `resources/js/pages/challenges/Show.vue`. No backend, route, or controller changes. Swap `AppLayout` to `PublicLayout`, replace the 3-column grid with vertical sections, and inline the stats from the sidebar into the hero area.

**Tech Stack:** Vue 3, Inertia.js v2, Tailwind CSS v4, shadcn-vue components

---

### Task 1: Run existing tests to establish baseline

**Files:**
- None modified

**Step 1: Run challenge show tests**

Run: `php artisan test --compact --filter=ChallengeShowTest`
Expected: All 4 tests pass

**Step 2: Run challenge controller tests**

Run: `php artisan test --compact --filter=ChallengeControllerTest`
Expected: All tests pass

**Step 3: Commit**

No commit needed — this is a baseline check.

---

### Task 2: Swap layout from AppLayout to PublicLayout

**Files:**
- Modify: `resources/js/pages/challenges/Show.vue`

This task changes only the layout wrapper and removes the breadcrumbs. No content reorganization yet.

**Step 1: Update imports**

In `resources/js/pages/challenges/Show.vue`, replace:

```ts
import AppLayout from '@/layouts/AppLayout.vue';
```

with:

```ts
import PublicLayout from '@/layouts/PublicLayout.vue';
```

Also remove:
```ts
import type { BreadcrumbItem } from '@/types';
```

**Step 2: Remove breadcrumbs data**

Delete the `breadcrumbs` const (lines 84-90):

```ts
const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Challenges', href: '/challenges' },
    {
        title: props.challenge.title ?? 'Challenge',
        href: `/challenges/${props.challenge.id}`,
    },
];
```

**Step 3: Swap template wrapper**

Replace `<AppLayout :breadcrumbs="breadcrumbs">` with `<PublicLayout>` and `</AppLayout>` with `</PublicLayout>`.

**Step 4: Run tests**

Run: `php artisan test --compact --filter=ChallengeShowTest`
Expected: All 4 tests pass (tests check component name and props, not layout)

Run: `php artisan test --compact --filter=ChallengeControllerTest`
Expected: All tests pass

**Step 5: Build frontend**

Run: `npm run build`
Expected: No build errors

**Step 6: Commit**

```bash
git add resources/js/pages/challenges/Show.vue
git commit -m "refactor: swap challenge show page from AppLayout to PublicLayout"
```

---

### Task 3: Reorganize content — hero section with inline stats

**Files:**
- Modify: `resources/js/pages/challenges/Show.vue`

Replace the 3-column grid (header + trade path + stats sidebar) with the new vertical flow. This task restructures the hero section and moves stats inline.

**Step 1: Remove StatsPanel import**

Remove:
```ts
import StatsPanel from '@/components/StatsPanel.vue';
```

**Step 2: Replace header and grid with new hero section**

Replace everything from `<!-- Header -->` through the closing `</div>` of the 3-column grid (the `<div class="grid grid-cols-1 gap-6 lg:grid-cols-3">` block) with:

```html
<!-- Hero Section -->
<div class="overflow-hidden rounded-2xl border border-border bg-muted/50">
    <!-- Category accent strip -->
    <div
        class="h-1.5"
        :style="{
            backgroundColor: 'var(--soft-lavender)',
        }"
    />

    <div class="p-4 sm:p-6">
        <!-- Top row: badges + actions -->
        <div class="flex items-start justify-between gap-4">
            <div class="flex flex-wrap items-center gap-2">
                <Badge
                    v-if="challenge.category?.name"
                    variant="secondary"
                    class="rounded-full border-0 text-xs"
                    :style="{
                        backgroundColor: 'var(--soft-lavender)',
                        color: 'var(--foreground)',
                    }"
                >
                    {{ challenge.category.name }}
                </Badge>
                <Badge
                    variant="secondary"
                    class="rounded-full border-0 text-xs capitalize"
                    :class="getStatusClasses(challenge.status)"
                >
                    {{ challenge.status }}
                </Badge>
            </div>
            <div class="flex items-center gap-2">
                <Button
                    variant="outline"
                    size="icon"
                    class="rounded-full"
                >
                    <Heart class="size-4" />
                </Button>
                <ShareDropdown
                    :url="shareUrl"
                    :title="
                        challenge.title ??
                        'Check out this challenge!'
                    "
                />
                <Link
                    v-if="isOwner"
                    :href="
                        challenges.edit.url({
                            challenge: challenge.id,
                        })
                    "
                >
                    <Button variant="outline" class="rounded-full">
                        <Pencil class="mr-2 size-4" />
                        Edit
                    </Button>
                </Link>
            </div>
        </div>

        <!-- Title + owner -->
        <h1
            class="mt-4 font-display text-3xl font-bold tracking-tight text-foreground lg:text-4xl"
        >
            {{ challenge.title ?? 'Untitled challenge' }}
        </h1>
        <div class="mt-3 flex flex-wrap items-center gap-3 text-sm text-muted-foreground">
            <Link
                href="#"
                class="flex items-center gap-2 transition-colors hover:text-foreground"
            >
                <Avatar
                    class="size-8 shrink-0 overflow-hidden rounded-full ring-2 ring-[var(--electric-mint)]/30"
                >
                    <AvatarImage
                        v-if="challenge.user?.avatar"
                        :src="challenge.user.avatar"
                        :alt="challenge.user?.name ?? 'Challenge owner'"
                    />
                    <AvatarFallback
                        class="rounded-full bg-[var(--sky-blue)]/20 text-[var(--sky-blue)]"
                    >
                        {{ getInitials(challenge.user?.name ?? 'Anonymous') }}
                    </AvatarFallback>
                </Avatar>
                <span class="font-medium">
                    {{ challenge.user?.name ?? 'Anonymous' }}
                </span>
                <Badge
                    v-if="challenge.user?.level"
                    class="rounded-full border border-[var(--soft-lavender-border)] bg-[hsl(275_70%_50%)] px-2 py-0.5 text-[10px] text-white"
                >
                    Lvl {{ challenge.user.level }}
                </Badge>
            </Link>
        </div>

        <!-- Bottom row: stats pills + CTA -->
        <div class="mt-5 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <!-- Stats pills -->
            <div class="grid grid-cols-2 gap-2 sm:flex sm:gap-3">
                <div class="flex items-center gap-2 rounded-xl bg-card p-3">
                    <span class="text-lg">🏆</span>
                    <div>
                        <div class="font-mono text-sm font-bold text-foreground">
                            {{ ownerStats.level }}
                        </div>
                        <div class="text-xs text-muted-foreground">Level</div>
                    </div>
                </div>
                <div class="flex items-center gap-2 rounded-xl bg-card p-3">
                    <span class="text-lg">🔥</span>
                    <div>
                        <div class="font-mono text-sm font-bold text-[var(--hot-coral)]">
                            {{ ownerStats.currentStreak }}
                        </div>
                        <div class="text-xs text-muted-foreground">Streak</div>
                    </div>
                </div>
                <div class="flex items-center gap-2 rounded-xl bg-card p-3">
                    <span class="text-lg">🔄</span>
                    <div>
                        <div class="font-mono text-sm font-bold text-[var(--electric-mint)]">
                            {{ ownerStats.tradesCompleted }}
                        </div>
                        <div class="text-xs text-muted-foreground">Trades</div>
                    </div>
                </div>
                <div class="flex items-center gap-2 rounded-xl bg-card p-3">
                    <span class="text-lg">📅</span>
                    <div>
                        <div class="font-mono text-sm font-bold text-[var(--sky-blue)]">
                            {{ ownerStats.daysActive }}
                        </div>
                        <div class="text-xs text-muted-foreground">Days</div>
                    </div>
                </div>
            </div>

            <!-- CTA -->
            <div v-if="!isOwner" class="sm:shrink-0">
                <Button
                    v-if="currentUser"
                    variant="brand"
                    size="lg"
                    class="w-full sm:w-auto"
                    @click="handleMakeOffer"
                >
                    Make an Offer
                </Button>
                <Link v-else :href="login().url" class="block">
                    <Button
                        variant="outline"
                        size="lg"
                        class="w-full sm:w-auto"
                    >
                        Sign in to make an offer
                    </Button>
                </Link>
            </div>
        </div>
    </div>
</div>
```

**Step 3: Add `login` route import**

Add `login` to the route imports. Find the existing import:
```ts
import challenges from '@/routes/challenges';
```

Add above it:
```ts
import { login } from '@/routes';
```

**Step 4: Run tests**

Run: `php artisan test --compact --filter=ChallengeShowTest`
Expected: All 4 tests pass

**Step 5: Build frontend**

Run: `npm run build`
Expected: No build errors

**Step 6: Commit**

```bash
git add resources/js/pages/challenges/Show.vue
git commit -m "refactor: reorganize challenge show hero with inline stats pills"
```

---

### Task 4: Add Trade Path Map section and reposition mascot

**Files:**
- Modify: `resources/js/pages/challenges/Show.vue`

Add the trade path map as a standalone wide section below the hero. The trade path was previously inside the 3-column grid — now it gets its own section.

**Step 1: Add trade path section after the hero**

Insert after the hero `</div>` and before the tab bar:

```html
<!-- Trade Journey -->
<div class="mt-6">
    <div class="mb-4 flex items-center justify-between">
        <h2 class="font-display text-lg font-semibold text-foreground">
            Trade Journey
        </h2>
        <PaperclipMascot
            :mood="mascotMood"
            :size="48"
        />
    </div>
    <div class="rounded-3xl border border-border bg-card/50 p-6 backdrop-blur-sm">
        <TradePathMap :nodes="pathNodes" />
    </div>
</div>
```

**Step 2: Remove the old compact mascot mobile section**

The old template had a "Compact mascot for mobile" `<div>` inside the stats sidebar. Since the sidebar is gone (removed in Task 3), verify it's no longer in the template. If any orphaned mascot div remains, remove it.

**Step 3: Run tests**

Run: `php artisan test --compact --filter=ChallengeShowTest`
Expected: All 4 tests pass

**Step 4: Build frontend**

Run: `npm run build`
Expected: No build errors

**Step 5: Commit**

```bash
git add resources/js/pages/challenges/Show.vue
git commit -m "refactor: add standalone trade path map section with mascot"
```

---

### Task 5: Clean up unused code

**Files:**
- Modify: `resources/js/pages/challenges/Show.vue`

**Step 1: Remove unused ownerStats references if StatsPanel is gone**

The `ownerStats` computed is still needed for the inline stats pills (Task 3 uses `ownerStats.level`, `ownerStats.currentStreak`, etc.), so keep it.

**Step 2: Verify the mobile sticky CTA positioning**

The mobile sticky CTA (`<div v-if="!isOwner" class="fixed inset-x-0 bottom-16 ...">`) is now inside `<PublicLayout>` instead of `<AppLayout>`. The `bottom-16` offset accounts for the `BottomTabBar`. Since `PublicLayout` also renders `BottomTabBar`, this should still work correctly.

**Step 3: Run lint and format**

Run: `vendor/bin/pint --dirty --format agent`
Expected: No PHP changes needed (this is a Vue-only change)

Run: `npm run lint`
Expected: No errors or auto-fixed

Run: `npm run format`
Expected: Formatted

**Step 4: Run full test suite**

Run: `php artisan test --compact`
Expected: All tests pass

**Step 5: Build frontend**

Run: `npm run build`
Expected: No build errors

**Step 6: Commit**

```bash
git add -A
git commit -m "chore: lint and format after challenge show page redesign"
```

---

### Task 6: Write a feature test for the layout change

**Files:**
- Modify: `tests/Feature/ChallengeShowTest.php`

Add a test that verifies the show page renders correctly for unauthenticated users (guests), since this is now explicitly a public-layout page.

**Step 1: Write the test**

Add to `tests/Feature/ChallengeShowTest.php`:

```php
test('show page renders for guest users without authentication', function () {
    $response = $this->get("/challenges/{$this->challenge->id}");

    $response->assertOk();
    $response->assertInertia(
        fn ($page) => $page
            ->component('challenges/Show')
            ->has('challenge')
            ->where('challenge.id', $this->challenge->id)
    );
});
```

**Step 2: Run the new test**

Run: `php artisan test --compact --filter="show page renders for guest"`
Expected: PASS

**Step 3: Run full challenge tests**

Run: `php artisan test --compact --filter=ChallengeShowTest`
Expected: All tests pass (4 existing + 1 new)

**Step 4: Commit**

```bash
git add tests/Feature/ChallengeShowTest.php
git commit -m "test: add guest access test for challenge show page"
```

---

### Task 7: Final verification

**Files:**
- None modified

**Step 1: Run full test suite**

Run: `php artisan test --compact`
Expected: All tests pass

**Step 2: Build production frontend**

Run: `npm run build`
Expected: Clean build, no warnings

**Step 3: Visual check**

Start dev server (`composer dev` or `npm run dev`) and visit `/challenges/1` in both:
- Logged-in state: should see "Make an Offer" button in hero
- Guest state (incognito): should see "Sign in to make an offer" link, no sidebar, public header/footer

Verify:
- Category accent strip visible at top of hero card
- Stats pills display horizontally on desktop, 2x2 grid on mobile
- Trade path map has full width
- Tabs (Story/Offers/Trades/Comments) work correctly
- Mobile bottom tab bar visible on small screens
- No sidebar or breadcrumbs visible
