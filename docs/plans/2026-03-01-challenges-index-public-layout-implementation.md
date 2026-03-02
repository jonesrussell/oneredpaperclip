# Challenges Index PublicLayout Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Switch the challenges index page from AppLayout to PublicLayout with a hero section, matching the pattern established in PR #9 for the challenge show page.

**Architecture:** Pure frontend change — swap layout component in `resources/js/pages/challenges/Index.vue`, add hero section, adjust container classes. No backend changes needed.

**Tech Stack:** Vue 3, Inertia.js v2, Tailwind CSS v4, PublicLayout

---

### Task 1: Add guest-accessible index test

**Files:**
- Modify: `tests/Feature/ChallengeControllerTest.php`

**Step 1: Write the test**

Add a new test that verifies a guest (unauthenticated user) can access the challenges index page and receives the correct Inertia component with challenge and category data:

```php
test('guest can get challenges index page', function () {
    $category = Category::factory()->create();
    $challenge = Challenge::factory()
        ->for($this->user)
        ->for($category)
        ->active()
        ->create();

    $response = $this->get(route('challenges.index'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('challenges/Index')
        ->has('challenges.data', 1)
        ->has('categories')
    );
});
```

Check `database/factories/ChallengeFactory.php` and `database/factories/CategoryFactory.php` for available factory states before writing. The test above uses factories — adjust if an `active()` state doesn't exist (use `->state(['status' => 'active'])` instead).

**Step 2: Run test to verify it passes**

Run: `php artisan test --compact --filter="guest can get challenges index page"`
Expected: PASS (this tests existing behavior, not the layout change)

**Step 3: Commit**

```
git add tests/Feature/ChallengeControllerTest.php
git commit -m "test: add guest challenges index test"
```

---

### Task 2: Switch Index.vue to PublicLayout with hero section

**Files:**
- Modify: `resources/js/pages/challenges/Index.vue`

**Reference files:**
- `resources/js/pages/challenges/Show.vue` — example of PublicLayout usage and hero styling
- `resources/js/layouts/PublicLayout.vue` — the target layout

**Step 1: Update the script section**

Replace the `AppLayout` import and remove the breadcrumbs:

- Remove: `import AppLayout from '@/layouts/AppLayout.vue';`
- Remove: `import type { BreadcrumbItem } from '@/types';`
- Add: `import PublicLayout from '@/layouts/PublicLayout.vue';`
- Remove: the `breadcrumbs` const

**Step 2: Replace the template**

Replace `<AppLayout :breadcrumbs="breadcrumbs">` with `<PublicLayout>` and restructure the content:

```vue
<template>
    <Head title="Explore Challenges" />

    <PublicLayout>
        <div class="bg-background">
            <div class="mx-auto w-full max-w-6xl p-4 sm:p-6">
                <!-- Hero Section -->
                <div
                    class="overflow-hidden rounded-2xl border border-border bg-muted/50"
                >
                    <div
                        class="h-1.5"
                        style="background-color: var(--brand-red)"
                    />
                    <div class="p-4 sm:p-6">
                        <h1
                            class="font-display text-3xl font-bold tracking-tight text-foreground lg:text-4xl"
                        >
                            Explore Challenges
                        </h1>
                        <p class="mt-2 text-sm text-muted-foreground">
                            Browse active trade-ups and find something to trade.
                        </p>

                        <!-- Category filter pills -->
                        <div
                            v-if="categoryList.length > 0"
                            class="scrollbar-hide mt-4 flex gap-2 overflow-x-auto pb-1"
                            role="group"
                            aria-label="Filter by category"
                        >
                            <button
                                class="inline-flex shrink-0 items-center gap-1.5 rounded-full border px-3 py-1.5 text-sm font-medium transition-colors"
                                :class="
                                    !activeCategory
                                        ? 'border-[var(--brand-red)] bg-[var(--brand-red)]/10 text-[var(--brand-red)]'
                                        : 'border-[var(--border)] text-[var(--ink-muted)] hover:bg-[var(--accent)]'
                                "
                                @click="filterByCategory(null)"
                            >
                                All
                            </button>
                            <button
                                v-for="cat in categoryList"
                                :key="cat.id"
                                class="inline-flex shrink-0 items-center gap-1.5 rounded-full border px-3 py-1.5 text-sm font-medium transition-colors"
                                :class="
                                    activeCategory === cat.id
                                        ? 'border-[var(--brand-red)] bg-[var(--brand-red)]/10 text-[var(--brand-red)]'
                                        : 'border-[var(--border)] text-[var(--ink-muted)] hover:bg-[var(--accent)]'
                                "
                                @click="filterByCategory(cat.id)"
                            >
                                {{ cat.name }}
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Empty state -->
                <div
                    v-if="challengeList.length === 0"
                    class="mt-6 rounded-2xl border border-dashed border-[var(--border)] bg-white/60 py-16 text-center text-[var(--ink-muted)]"
                >
                    No challenges yet. Be the first to start a trade-up.
                    <br />
                    <Link
                        href="/challenges/create"
                        class="mt-2 inline-block font-semibold text-[var(--brand-red)] hover:underline"
                    >
                        Create a challenge
                    </Link>
                </div>

                <!-- Challenge grid -->
                <ul
                    v-else
                    class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3"
                >
                    <li v-for="challenge in challengeList" :key="challenge.id">
                        <ChallengeCard :challenge="challenge" />
                    </li>
                </ul>

                <!-- Pagination -->
                <nav
                    v-if="challenges.last_page > 1"
                    class="flex flex-wrap items-center justify-center gap-2 pt-4"
                    aria-label="Challenge pagination"
                >
                    <template
                        v-for="link in challenges.links"
                        :key="link.label"
                    >
                        <Link
                            v-if="link.url"
                            :href="link.url"
                            class="inline-flex min-w-9 items-center justify-center rounded-md border px-3 py-1.5 text-sm transition-colors hover:bg-accent"
                            :class="
                                link.active
                                    ? 'border-primary bg-primary/10 text-primary'
                                    : 'border-transparent'
                            "
                            :aria-current="link.active ? 'page' : undefined"
                        >
                            <span v-html="link.label" />
                        </Link>
                        <span
                            v-else
                            class="inline-flex min-w-9 cursor-default items-center justify-center rounded-md border border-transparent px-3 py-1.5 text-sm opacity-50"
                            v-html="link.label"
                        />
                    </template>
                </nav>
            </div>
        </div>
    </PublicLayout>
</template>
```

**Step 3: Run linters**

Run: `npm run lint && npm run format`

**Step 4: Run tests**

Run: `php artisan test --compact --filter=ChallengeControllerTest`
Expected: All tests PASS

**Step 5: Commit**

```
git add resources/js/pages/challenges/Index.vue
git commit -m "refactor: switch challenges index to PublicLayout with hero section"
```

---

### Task 3: Visual verification and final cleanup

**Step 1: Build frontend assets**

Run: `npm run build`

**Step 2: Run full test suite**

Run: `php artisan test --compact`
Expected: All tests PASS

**Step 3: Run Pint**

Run: `vendor/bin/pint --dirty --format agent`
Expected: No PHP files changed (only Vue was modified)

**Step 4: Commit if any cleanup needed**

Only commit if linters made changes.
