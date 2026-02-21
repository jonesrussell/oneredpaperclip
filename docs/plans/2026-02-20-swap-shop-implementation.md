# Swap Shop Redesign — Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Redesign One Red Paperclip from proof-of-concept to MVP with a vibrant, social, gamified marketplace aesthetic ("Swap Shop" direction).

**Architecture:** This is a frontend-only redesign. No backend changes needed. We update design tokens (CSS variables + fonts), create new reusable Vue components (ProgressRing, BottomTabBar, CampaignCard, MilestoneTimeline), restructure layouts (add PublicLayout for unauthenticated pages, restyle sidebar), and redesign all pages. Existing shadcn-vue components and Inertia/Vue patterns are preserved.

**Tech Stack:** Vue 3 + TypeScript, Inertia.js v2, Tailwind CSS v4, shadcn-vue (new-york-v4), Lucide icons, Bunny Fonts (Fredoka + DM Sans + JetBrains Mono).

**Design doc:** `docs/plans/2026-02-20-swap-shop-redesign-design.md`

---

## Phase 1: Foundation (Design Tokens & Fonts)

### Task 1: Update CSS design tokens

**Files:**
- Modify: `resources/css/app.css`

**Step 1: Update the CSS variables and tokens**

Replace the `:root` block and update the `@theme inline` font references. Key changes:
- Swap `--font-sans` from Nunito to DM Sans
- Add `--font-mono` for JetBrains Mono
- Update `--paper` to warmer `hsl(28 60% 98%)`
- Update `--ink` to deeper `hsl(28 25% 16%)`
- Update `--ink-muted` to `hsl(28 15% 45%)`
- Rename fun colors to semantic names: `--electric-mint`, `--sunny-yellow`, `--hot-coral`, `--soft-lavender`, `--sky-blue`
- Update `--radius` from `0.875rem` to `1rem`
- Update border styling to warm shadows
- Add new keyframes: `count-up`, `confetti-burst`, `progress-fill`, `slide-up`, `pulse-ring`

The full updated `app.css` should contain:
- `@theme inline` block with DM Sans as `--font-sans`, Fredoka as `--font-display`, JetBrains Mono as `--font-mono`
- `:root` with all updated design tokens including new accent colors
- `.dark` block preserved (update `--paper` to `hsl(24 10% 8%)`)
- All new animation keyframes
- Utility classes: `.font-display`, `.font-mono`, `.animate-float`, `.animate-blob-pulse`, `.animate-slide-up`, `.animate-progress-fill`, `.animate-pulse-ring`

Specific new CSS variables to add to `:root`:
```css
--electric-mint: hsl(162 65% 52%);
--sunny-yellow: hsl(45 95% 60%);
--hot-coral: hsl(12 90% 65%);
--soft-lavender: hsl(265 55% 75%);
--sky-blue: hsl(205 85% 65%);
```

New keyframes to add:
```css
@keyframes slide-up {
    from { opacity: 0; transform: translateY(1rem); }
    to { opacity: 1; transform: translateY(0); }
}
@keyframes progress-fill {
    from { stroke-dashoffset: var(--circumference); }
    to { stroke-dashoffset: var(--target-offset); }
}
@keyframes pulse-ring {
    0%, 100% { box-shadow: 0 0 0 0 var(--pulse-color, rgba(239, 68, 68, 0.4)); }
    50% { box-shadow: 0 0 0 6px var(--pulse-color, rgba(239, 68, 68, 0)); }
}
```

**Step 2: Run build to verify no CSS errors**

Run: `npm run build`
Expected: Build succeeds with no errors.

**Step 3: Commit**

```bash
git add resources/css/app.css
git commit -m "style: update design tokens for Swap Shop redesign

Update color palette, add accent colors, swap body font to DM Sans,
add new animation keyframes for gamification elements."
```

---

### Task 2: Update font imports in Blade template

**Files:**
- Modify: `resources/views/app.blade.php`

**Step 1: Update the Bunny Fonts link**

Change the fonts link from:
```html
<link href="https://fonts.bunny.net/css?family=fredoka:400,500,600,700|nunito:400,500,600,700" rel="stylesheet" />
```
To:
```html
<link href="https://fonts.bunny.net/css?family=dm-sans:400,500,600,700|fredoka:400,500,600,700|jetbrains-mono:400,500" rel="stylesheet" />
```

Also update the inline HTML background color to match the new `--paper` token:
```css
html { background-color: hsl(28 60% 98%); }
```

**Step 2: Run build to verify**

Run: `npm run build`
Expected: Build succeeds.

**Step 3: Commit**

```bash
git add resources/views/app.blade.php
git commit -m "style: swap fonts to DM Sans + JetBrains Mono

Replace Nunito with DM Sans for body text, add JetBrains Mono for
stats/data display. Keep Fredoka for display headings."
```

---

## Phase 2: New Reusable Components

### Task 3: Create ProgressRing component

**Files:**
- Create: `resources/js/components/ProgressRing.vue`

**Step 1: Create the component**

This is an SVG-based circular progress indicator. Props: `percent` (0-100), `size` (px, default 48), `strokeWidth` (default 4), `showLabel` (boolean, default true).

Color logic: 0-33% = `var(--hot-coral)`, 34-66% = `var(--sunny-yellow)`, 67-100% = `var(--electric-mint)`.

```vue
<script setup lang="ts">
import { computed } from 'vue';

const props = withDefaults(defineProps<{
    percent: number;
    size?: number;
    strokeWidth?: number;
    showLabel?: boolean;
}>(), {
    size: 48,
    strokeWidth: 4,
    showLabel: true,
});

const radius = computed(() => (props.size - props.strokeWidth) / 2);
const circumference = computed(() => 2 * Math.PI * radius.value);
const offset = computed(() => circumference.value - (props.percent / 100) * circumference.value);
const center = computed(() => props.size / 2);

const color = computed(() => {
    if (props.percent <= 33) return 'var(--hot-coral)';
    if (props.percent <= 66) return 'var(--sunny-yellow)';
    return 'var(--electric-mint)';
});
</script>

<template>
    <div class="relative inline-flex items-center justify-center" :style="{ width: `${size}px`, height: `${size}px` }">
        <svg :width="size" :height="size" class="-rotate-90">
            <circle
                :cx="center"
                :cy="center"
                :r="radius"
                fill="none"
                stroke="var(--border)"
                :stroke-width="strokeWidth"
            />
            <circle
                :cx="center"
                :cy="center"
                :r="radius"
                fill="none"
                :stroke="color"
                :stroke-width="strokeWidth"
                stroke-linecap="round"
                :stroke-dasharray="circumference"
                :stroke-dashoffset="offset"
                class="transition-[stroke-dashoffset] duration-700 ease-out"
            />
        </svg>
        <span
            v-if="showLabel"
            class="absolute font-mono text-xs font-semibold"
            :style="{ color, fontSize: `${size * 0.22}px` }"
        >
            {{ percent }}%
        </span>
    </div>
</template>
```

**Step 2: Run build to verify**

Run: `npm run build`
Expected: Build succeeds.

**Step 3: Commit**

```bash
git add resources/js/components/ProgressRing.vue
git commit -m "feat: add ProgressRing component

SVG circular progress indicator with color transitions based on
completion percentage. Used on campaign cards and detail pages."
```

---

### Task 4: Create BottomTabBar component

**Files:**
- Create: `resources/js/components/BottomTabBar.vue`

**Step 1: Create the component**

Fixed bottom navigation bar for mobile. Shows 5 tabs: Home, Explore, Create (elevated), Activity, Profile. The center "Create" tab has a raised circular button in brand red.

```vue
<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { Bell, Compass, Home, PlusCircle, User } from 'lucide-vue-next';
import { computed } from 'vue';
import { dashboard, home } from '@/routes';
import campaigns from '@/routes/campaigns';

const page = usePage();
const currentUrl = computed(() => page.url);
const user = computed(() => page.props.auth?.user);

function isActive(path: string): boolean {
    return currentUrl.value.startsWith(path);
}
</script>

<template>
    <nav
        class="fixed inset-x-0 bottom-0 z-50 flex h-16 items-center justify-around border-t border-[var(--border)] bg-white/95 backdrop-blur-md lg:hidden"
        aria-label="Mobile navigation"
    >
        <Link
            :href="user ? dashboard().url : home().url"
            class="flex flex-col items-center gap-0.5 px-3 py-1 text-xs font-medium transition-colors"
            :class="isActive('/dashboard') || currentUrl === '/' ? 'text-[var(--brand-red)]' : 'text-[var(--ink-muted)]'"
        >
            <Home class="size-5" />
            <span>Home</span>
        </Link>

        <Link
            :href="campaigns.index().url"
            class="flex flex-col items-center gap-0.5 px-3 py-1 text-xs font-medium transition-colors"
            :class="isActive('/campaigns') && !currentUrl.includes('/create') ? 'text-[var(--brand-red)]' : 'text-[var(--ink-muted)]'"
        >
            <Compass class="size-5" />
            <span>Explore</span>
        </Link>

        <Link
            :href="user ? campaigns.create().url : '/register'"
            class="-mt-5 flex flex-col items-center gap-0.5"
        >
            <div
                class="flex size-12 items-center justify-center rounded-full bg-[var(--brand-red)] text-white shadow-lg transition-transform hover:scale-105 active:scale-95"
            >
                <PlusCircle class="size-6" />
            </div>
            <span class="text-xs font-semibold text-[var(--brand-red)]">Create</span>
        </Link>

        <Link
            :href="user ? dashboard().url : '/login'"
            class="flex flex-col items-center gap-0.5 px-3 py-1 text-xs font-medium text-[var(--ink-muted)] transition-colors"
        >
            <Bell class="size-5" />
            <span>Activity</span>
        </Link>

        <Link
            :href="user ? dashboard().url : '/login'"
            class="flex flex-col items-center gap-0.5 px-3 py-1 text-xs font-medium text-[var(--ink-muted)] transition-colors"
        >
            <User class="size-5" />
            <span>Profile</span>
        </Link>
    </nav>
</template>
```

**Step 2: Run build to verify**

Run: `npm run build`
Expected: Build succeeds.

**Step 3: Commit**

```bash
git add resources/js/components/BottomTabBar.vue
git commit -m "feat: add BottomTabBar mobile navigation component

Fixed bottom nav with 5 tabs and elevated center Create button.
Visible only on mobile (<lg breakpoint), uses backdrop blur."
```

---

### Task 5: Create CampaignCard component

**Files:**
- Create: `resources/js/components/CampaignCard.vue`

**Step 1: Create the component**

Reusable campaign card with category accent strip, progress ring, trade count badge, and hover effects. Extracted from inline card markup to avoid duplication between Welcome, Dashboard, and Campaign Index pages.

```vue
<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import ProgressRing from '@/components/ProgressRing.vue';
import { Badge } from '@/components/ui/badge';
import campaigns from '@/routes/campaigns';

const props = defineProps<{
    campaign: {
        id: number;
        title: string | null;
        status: string;
        trades_count?: number;
        user?: { id: number; name: string } | null;
        current_item?: { id: number; title: string } | null;
        goal_item?: { id: number; title: string } | null;
        category?: { id: number; name: string } | null;
    };
    progress?: number;
}>();

const categoryColors: Record<string, string> = {
    Electronics: 'var(--sky-blue)',
    Collectibles: 'var(--soft-lavender)',
    Home: 'var(--sunny-yellow)',
    Sports: 'var(--electric-mint)',
    Fashion: 'var(--hot-coral)',
    Art: 'var(--soft-lavender)',
    Music: 'var(--sky-blue)',
    Books: 'var(--sunny-yellow)',
    Other: 'var(--border)',
};

function getCategoryColor(name?: string): string {
    return categoryColors[name ?? ''] ?? 'var(--border)';
}
</script>

<template>
    <Link
        :href="campaigns.show({ campaign: campaign.id }).url"
        class="group block overflow-hidden rounded-2xl border border-[var(--border)] bg-white shadow-sm transition-all hover:-translate-y-0.5 hover:shadow-md"
        prefetch
    >
        <!-- Category accent strip -->
        <div
            class="h-1.5 w-full"
            :style="{ backgroundColor: getCategoryColor(campaign.category?.name) }"
        />

        <div class="relative p-4">
            <!-- Progress ring -->
            <div v-if="progress != null" class="absolute right-4 top-4">
                <ProgressRing :percent="progress" :size="40" :stroke-width="3" />
            </div>

            <!-- Title -->
            <h3 class="line-clamp-2 pr-12 font-display text-base font-bold text-[var(--ink)] group-hover:text-[var(--brand-red)] transition-colors">
                {{ campaign.title ?? 'Untitled campaign' }}
            </h3>

            <!-- Current → Goal -->
            <p
                v-if="campaign.current_item?.title && campaign.goal_item?.title"
                class="mt-2 line-clamp-1 text-sm text-[var(--ink-muted)]"
            >
                {{ campaign.current_item.title }}
                <span class="mx-1 text-[var(--brand-red)]">&rarr;</span>
                {{ campaign.goal_item.title }}
            </p>

            <!-- Footer: trade count + user + status -->
            <div class="mt-3 flex flex-wrap items-center gap-2">
                <Badge
                    v-if="campaign.trades_count"
                    variant="secondary"
                    class="rounded-full bg-[var(--muted)] text-xs font-medium"
                >
                    {{ campaign.trades_count }} trade{{ campaign.trades_count === 1 ? '' : 's' }}
                </Badge>
                <span class="flex-1" />
                <span
                    v-if="campaign.user?.name"
                    class="text-xs text-[var(--ink-muted)]"
                >
                    {{ campaign.user.name }}
                </span>
                <Badge
                    variant="secondary"
                    class="rounded-full text-xs capitalize"
                    :class="campaign.status === 'completed'
                        ? 'bg-[var(--electric-mint)]/15 text-[var(--electric-mint)]'
                        : campaign.status === 'active'
                            ? 'bg-[var(--electric-mint)]/10 text-emerald-700'
                            : 'bg-[var(--muted)]'"
                >
                    {{ campaign.status }}
                </Badge>
            </div>
        </div>
    </Link>
</template>
```

**Step 2: Run build to verify**

Run: `npm run build`
Expected: Build succeeds.

**Step 3: Commit**

```bash
git add resources/js/components/CampaignCard.vue
git commit -m "feat: add CampaignCard component

Reusable campaign card with category accent strip, progress ring,
trade count badge, and current→goal item display."
```

---

### Task 6: Create MilestoneTimeline component

**Files:**
- Create: `resources/js/components/MilestoneTimeline.vue`

**Step 1: Create the component**

Horizontal timeline showing trade milestones. Each node is a circle on a connecting line. Completed = mint filled, current = pulsing coral, future = outlined gray.

```vue
<script setup lang="ts">
type Milestone = {
    label: string;
    status: 'completed' | 'current' | 'future';
};

defineProps<{
    milestones: Milestone[];
}>();
</script>

<template>
    <div class="flex items-center gap-0 overflow-x-auto py-2">
        <template v-for="(milestone, i) in milestones" :key="i">
            <div class="flex flex-col items-center gap-1.5 px-1">
                <!-- Node -->
                <div
                    class="flex size-8 shrink-0 items-center justify-center rounded-full border-2 text-xs font-bold transition-all"
                    :class="{
                        'border-[var(--electric-mint)] bg-[var(--electric-mint)] text-white': milestone.status === 'completed',
                        'border-[var(--hot-coral)] bg-[var(--hot-coral)]/10 text-[var(--hot-coral)] animate-pulse': milestone.status === 'current',
                        'border-[var(--border)] bg-white text-[var(--ink-muted)]': milestone.status === 'future',
                    }"
                >
                    <span v-if="milestone.status === 'completed'">&#10003;</span>
                    <span v-else>{{ i + 1 }}</span>
                </div>
                <!-- Label -->
                <span
                    class="max-w-16 truncate text-center text-[10px] font-medium leading-tight"
                    :class="milestone.status === 'current' ? 'text-[var(--hot-coral)]' : 'text-[var(--ink-muted)]'"
                >
                    {{ milestone.label }}
                </span>
            </div>
            <!-- Connector line -->
            <div
                v-if="i < milestones.length - 1"
                class="mb-5 h-0.5 w-8 shrink-0"
                :class="milestone.status === 'completed' ? 'bg-[var(--electric-mint)]' : 'bg-[var(--border)]'"
            />
        </template>
    </div>
</template>
```

**Step 2: Run build to verify**

Run: `npm run build`
Expected: Build succeeds.

**Step 3: Commit**

```bash
git add resources/js/components/MilestoneTimeline.vue
git commit -m "feat: add MilestoneTimeline component

Horizontal timeline showing trade milestones with completed/current/future
states using color-coded nodes and connecting lines."
```

---

### Task 7: Add success and social button variants

**Files:**
- Modify: `resources/js/components/ui/button/index.ts`

**Step 1: Add the new variants**

Add two new variant entries to the `variants.variant` object:

```typescript
success:
    "bg-[var(--electric-mint)] text-white shadow-md hover:bg-[var(--electric-mint)]/90 hover:shadow-lg hover:-translate-y-0.5",
social:
    "bg-[var(--sky-blue)] text-white shadow-md hover:bg-[var(--sky-blue)]/90 hover:shadow-lg hover:-translate-y-0.5",
```

Insert these after the existing `brand` variant.

**Step 2: Run build to verify**

Run: `npm run build`
Expected: Build succeeds.

**Step 3: Commit**

```bash
git add resources/js/components/ui/button/index.ts
git commit -m "feat: add success and social button variants

Success (electric mint) for Accept/Confirm actions.
Social (sky blue) for Share/Follow actions."
```

---

## Phase 3: Layout Restructure

### Task 8: Create PublicLayout component

**Files:**
- Create: `resources/js/layouts/PublicLayout.vue`

**Step 1: Create the layout**

This layout is for unauthenticated public pages (Welcome, campaign browsing when not logged in). Features: slim sticky top header, optional bottom tab bar on mobile, and full-width content area with warm cream background + decorative blobs.

```vue
<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import BottomTabBar from '@/components/BottomTabBar.vue';
import { home, login, register } from '@/routes';
import campaigns from '@/routes/campaigns';
import { dashboard } from '@/routes';
import { Search } from 'lucide-vue-next';

const page = usePage();
const user = page.props.auth?.user;
const canRegister = page.props.canRegister ?? true;
</script>

<template>
    <div class="min-h-screen bg-[var(--paper)] text-[var(--ink)]">
        <!-- Decorative background blobs -->
        <div class="pointer-events-none fixed inset-0 z-0 overflow-hidden" aria-hidden="true">
            <div class="absolute -right-32 -top-32 h-96 w-96 rounded-full bg-[var(--hot-coral)]/20 blur-3xl animate-blob-pulse" />
            <div class="absolute -bottom-24 -left-24 h-80 w-80 rounded-full bg-[var(--sunny-yellow)]/15 blur-3xl animate-blob-pulse" style="animation-delay: -2s" />
            <div class="absolute right-1/3 top-1/2 h-64 w-64 rounded-full bg-[var(--electric-mint)]/10 blur-3xl animate-blob-pulse" style="animation-delay: -4s" />
        </div>

        <!-- Grain overlay -->
        <div class="welcome-grain pointer-events-none fixed inset-0 z-[1] opacity-[0.03]" aria-hidden="true" />

        <!-- Sticky top header -->
        <header class="sticky top-0 z-40 border-b border-[var(--border)] bg-white/85 backdrop-blur-md">
            <div class="mx-auto flex h-14 max-w-6xl items-center justify-between gap-4 px-4 sm:px-6">
                <!-- Logo -->
                <Link
                    :href="home().url"
                    class="flex items-center gap-2 font-display text-lg font-bold tracking-tight transition-transform hover:scale-[1.02]"
                >
                    <span class="text-[var(--brand-red)]">One Red Paperclip</span>
                </Link>

                <!-- Desktop nav -->
                <nav class="hidden items-center gap-2 lg:flex" aria-label="Main navigation">
                    <a
                        href="#how-it-works"
                        class="rounded-lg px-3 py-2 text-sm font-medium text-[var(--ink-muted)] transition-colors hover:bg-[var(--accent)] hover:text-[var(--ink)]"
                    >
                        How it works
                    </a>
                    <Link
                        :href="campaigns.index().url"
                        class="rounded-lg px-3 py-2 text-sm font-medium text-[var(--ink-muted)] transition-colors hover:bg-[var(--accent)] hover:text-[var(--ink)]"
                    >
                        Explore
                    </Link>
                    <template v-if="user">
                        <Link
                            :href="dashboard().url"
                            class="rounded-xl bg-[var(--brand-red)] px-4 py-2 text-sm font-semibold text-white shadow-md transition-all hover:bg-[var(--brand-red-hover)] hover:shadow-lg hover:-translate-y-0.5"
                        >
                            Dashboard
                        </Link>
                    </template>
                    <template v-else>
                        <Link
                            :href="login().url"
                            class="rounded-lg px-3 py-2 text-sm font-medium text-[var(--ink-muted)] transition-colors hover:bg-[var(--accent)] hover:text-[var(--ink)]"
                        >
                            Log in
                        </Link>
                        <Link
                            v-if="canRegister"
                            :href="register().url"
                            class="rounded-xl bg-[var(--brand-red)] px-4 py-2 text-sm font-semibold text-white shadow-md transition-all hover:bg-[var(--brand-red-hover)] hover:shadow-lg hover:-translate-y-0.5"
                        >
                            Get started
                        </Link>
                    </template>
                </nav>
            </div>
        </header>

        <!-- Main content -->
        <main class="relative z-10 pb-20 lg:pb-0">
            <slot />
        </main>

        <!-- Footer -->
        <footer class="relative z-10 border-t border-[var(--border)] bg-white/60 py-8 pb-24 lg:pb-8">
            <div class="mx-auto flex max-w-6xl flex-wrap items-center justify-between gap-4 px-4 sm:px-6">
                <Link :href="home().url" class="font-display text-lg font-bold text-[var(--ink)] transition-opacity hover:opacity-80">
                    One Red Paperclip
                </Link>
                <nav class="flex flex-wrap items-center gap-6 text-sm font-medium text-[var(--ink-muted)]" aria-label="Footer navigation">
                    <a href="#how-it-works" class="hover:text-[var(--ink)]">How it works</a>
                    <Link :href="campaigns.index().url" class="hover:text-[var(--ink)]">Campaigns</Link>
                    <Link v-if="!user" :href="login().url" class="hover:text-[var(--ink)]">Log in</Link>
                </nav>
            </div>
        </footer>

        <!-- Mobile bottom tab bar -->
        <BottomTabBar />
    </div>
</template>

<style scoped>
.welcome-grain {
    background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 256 256' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)'/%3E%3C/svg%3E");
}
</style>
```

**Step 2: Run build to verify**

Run: `npm run build`
Expected: Build succeeds.

**Step 3: Commit**

```bash
git add resources/js/layouts/PublicLayout.vue
git commit -m "feat: add PublicLayout with sticky header + bottom tab bar

Public-facing layout for Welcome and campaign browsing. Features slim
sticky header, mobile bottom tab bar, decorative background blobs."
```

---

### Task 9: Restyle the sidebar layout

**Files:**
- Modify: `resources/js/layouts/app/AppSidebarLayout.vue`
- Modify: `resources/js/components/AppSidebar.vue`
- Modify: `resources/js/components/NavMain.vue`

**Step 1: Update AppSidebarLayout.vue**

Update the `AppContent` class to use the new paper background and add bottom padding for mobile tab bar:

Change the `AppContent` line from:
```
class="overflow-x-hidden bg-[var(--paper)] text-[var(--ink)]"
```
To:
```
class="overflow-x-hidden bg-[var(--paper)] text-[var(--ink)] pb-20 lg:pb-0"
```

**Step 2: Add BottomTabBar to AppSidebarLayout**

Import and add `<BottomTabBar />` at the end of the template, after the closing `</AppContent>` but inside `<AppShell>`:

```vue
<template>
    <AppShell variant="sidebar">
        <AppSidebar />
        <AppContent
            variant="sidebar"
            class="overflow-x-hidden bg-[var(--paper)] text-[var(--ink)] pb-20 lg:pb-0"
        >
            <AppSidebarHeader :breadcrumbs="breadcrumbs" />
            <slot />
        </AppContent>
        <BottomTabBar />
    </AppShell>
</template>
```

Add the import at the top:
```typescript
import BottomTabBar from '@/components/BottomTabBar.vue';
```

**Step 3: Update NavMain.vue for colorful active states**

In `NavMain.vue`, the active state is handled by the `SidebarMenuButton` `is-active` prop. The sidebar colors come from CSS variables which we updated in Task 1. No code change needed here — the sidebar accent color (`--sidebar-accent`) already applies.

**Step 4: Run build to verify**

Run: `npm run build`
Expected: Build succeeds.

**Step 5: Commit**

```bash
git add resources/js/layouts/app/AppSidebarLayout.vue resources/js/components/AppSidebar.vue resources/js/components/NavMain.vue
git commit -m "style: restyle sidebar layout with bottom tab bar

Add mobile bottom tab bar to authenticated layout, add bottom padding
to prevent content overlap on mobile."
```

---

### Task 10: Restyle auth layout

**Files:**
- Modify: `resources/js/layouts/auth/AuthSimpleLayout.vue`

**Step 1: Update the auth layout**

Enhance with more playful floating elements and updated background. The key changes:
- Use the new accent color variables
- Add a subtle floating paperclip illustration
- Update the card styling to use warmer shadows
- Keep the centered card pattern but make it feel more alive

Update the background blobs to use new color tokens:
```html
<div class="absolute -right-24 -top-24 h-72 w-72 rounded-full bg-[var(--hot-coral)]/30 blur-3xl animate-blob-pulse" />
<div class="absolute -bottom-16 -left-16 h-56 w-56 rounded-full bg-[var(--electric-mint)]/20 blur-3xl animate-blob-pulse" style="animation-delay: -2s" />
<div class="absolute left-1/2 top-1/4 h-40 w-40 rounded-full bg-[var(--sunny-yellow)]/15 blur-3xl animate-blob-pulse" style="animation-delay: -4s" />
```

**Step 2: Run build to verify**

Run: `npm run build`
Expected: Build succeeds.

**Step 3: Commit**

```bash
git add resources/js/layouts/auth/AuthSimpleLayout.vue
git commit -m "style: update auth layout with new accent colors

Use new design token colors for background blobs, add third
decorative blob for more visual interest."
```

---

## Phase 4: Page Redesigns

### Task 11: Redesign Welcome page

**Files:**
- Modify: `resources/js/pages/Welcome.vue`

**Step 1: Rewrite Welcome.vue**

Major changes:
- Use `PublicLayout` instead of inline layout markup
- Add social proof stats strip with animated count-up (simple CSS animation)
- Update "How it works" section with stagger animations
- Use `CampaignCard` component for featured campaigns carousel
- Update CTA section with new gradient colors
- All inline header/footer/blob markup moves to PublicLayout

The page should now wrap in `<PublicLayout>` and only contain the page-specific content sections:

1. **Hero** — Same structure but using updated design tokens, animated heading, "Start Trading" primary CTA
2. **Social proof strip** — New section with 3 stats (e.g., "500+ campaigns", "1,200+ trades", "Growing community") using `font-mono` for numbers
3. **How it works** — Same 4-step cards, updated colors to use new accent tokens
4. **Trending campaigns** — Use `CampaignCard` component instead of inline card markup
5. **CTA banner** — Updated gradient using new accent colors

Key code changes:
- Remove `<header>`, `<footer>`, background blobs, and grain overlay (now in PublicLayout)
- Wrap everything in `<PublicLayout>` instead of the raw `<div>`
- Import and use `CampaignCard` for featured campaigns
- Import and use `PublicLayout`

**Step 2: Run build to verify**

Run: `npm run build`
Expected: Build succeeds.

**Step 3: Run lint**

Run: `npm run lint`
Expected: Lint passes (or only auto-fixable issues).

**Step 4: Commit**

```bash
git add resources/js/pages/Welcome.vue
git commit -m "feat: redesign Welcome page with Swap Shop aesthetic

Use PublicLayout, CampaignCard component, social proof stats strip,
and updated color tokens. Move header/footer to PublicLayout."
```

---

### Task 12: Redesign Dashboard page

**Files:**
- Modify: `resources/js/pages/Dashboard.vue`

**Step 1: Rewrite Dashboard.vue**

Major changes:
- Add greeting header with user name ("Hey {name}!")
- Add 3 quick-stats mini-cards row (Active Campaigns, Pending Offers, Completed Trades)
  - For now, these will show placeholder data ("—") since we're not passing real counts from the backend yet. We'll use 0 as default.
- Replace the two shortcut cards with a "Your campaigns" grid that includes a "Create new" dashed card
- Add a "How it works" educational card (keep from current design)
- Remove the "Browse campaigns" shortcut card (users have the sidebar/bottom nav for that)

The Dashboard controller currently doesn't pass any campaign data. For this redesign, we add frontend structure that works with empty/placeholder data. Backend data can be added later.

Structure:
```
<AppLayout :breadcrumbs="breadcrumbs">
  <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
    <!-- Greeting -->
    <div>
      <h1 class="font-display text-2xl font-bold">Hey {{ user.name }}!</h1>
      <p class="text-sm text-[var(--ink-muted)]">Your trading desk</p>
    </div>

    <!-- Quick stats row -->
    <div class="grid grid-cols-3 gap-3">
      <StatCard label="Active Campaigns" value="0" accent="var(--hot-coral)" />
      <StatCard label="Pending Offers" value="0" accent="var(--sunny-yellow)" />
      <StatCard label="Completed Trades" value="0" accent="var(--electric-mint)" />
    </div>

    <!-- Campaign shortcuts -->
    <div class="grid gap-4 sm:grid-cols-2">
      <!-- Create new campaign card (dashed) -->
      <!-- How it works card -->
    </div>
  </div>
</AppLayout>
```

The stat cards can be inline `<div>` elements — they don't need a separate component for now.

**Step 2: Run build to verify**

Run: `npm run build`
Expected: Build succeeds.

**Step 3: Commit**

```bash
git add resources/js/pages/Dashboard.vue
git commit -m "feat: redesign Dashboard with greeting, stats, and campaign grid

Add personalized greeting, quick-stats row with accent colors,
and streamlined campaign shortcut cards."
```

---

### Task 13: Redesign Campaign Index page

**Files:**
- Modify: `resources/js/pages/campaigns/Index.vue`

**Step 1: Rewrite campaigns/Index.vue**

Major changes:
- Use `CampaignCard` component instead of inline card markup
- Convert category filter buttons to pill-shaped chips with colored dots
- Add sort tabs: "All" / "Active" / "Almost there" (these can filter by status client-side or server-side)
- Replace pagination with a "Load more" button (simpler for now — keep pagination markup as fallback)
- Add search input at top (visual only for now — search functionality can be added later)
- Update empty state with warmer styling

Key structural changes:
- Import `CampaignCard` component
- Category chips: horizontal scrollable container with `overflow-x-auto`, each chip is a pill with colored dot
- Campaign grid uses `CampaignCard` instead of inline `Card` + `CardHeader` etc.

**Step 2: Run build to verify**

Run: `npm run build`
Expected: Build succeeds.

**Step 3: Commit**

```bash
git add resources/js/pages/campaigns/Index.vue
git commit -m "feat: redesign Campaign Index with CampaignCard and pill chips

Use CampaignCard component, pill-shaped category filters with colored
dots, and updated empty state styling."
```

---

### Task 14: Redesign Campaign Create page (step wizard)

**Files:**
- Modify: `resources/js/pages/campaigns/Create.vue`

**Step 1: Convert to step wizard**

This is the most significant page change. Convert the single long form into a 4-step wizard:
1. "What do you have?" — start item (title, description)
2. "What's your dream item?" — goal item (title, description)
3. "Tell your story" — campaign title, story, category, visibility, status
4. "Review & launch" — preview of entered data + submit button

Implementation approach:
- Add a `currentStep` ref (1-4)
- Show/hide card sections based on `currentStep`
- Add a progress bar at top showing steps 1-4
- "Next" and "Back" buttons at the bottom of each step
- Step 4 shows a preview card and the submit button
- The form still submits everything at once (Inertia `Form` component handles this)

Use `useForm` from Inertia for reactive form data so we can read values in the preview step:
```typescript
import { useForm } from '@inertiajs/vue3';

const form = useForm({
    title: '',
    story: '',
    category_id: '',
    visibility: 'public',
    status: 'active',
    'start_item[title]': '',
    'start_item[description]': '',
    'goal_item[title]': '',
    'goal_item[description]': '',
});
```

Note: Check how the existing form submission works (it uses `<Form v-bind="campaigns.store.form()">` with Wayfinder). The step wizard needs to preserve this submission pattern. Either keep the Wayfinder form binding on the final submit, or use `form.post(campaigns.store().url)`.

The step progress bar at top:
```html
<div class="flex items-center gap-2">
    <div v-for="step in 4" :key="step"
        class="flex items-center gap-2">
        <div class="flex size-8 items-center justify-center rounded-full text-sm font-bold"
            :class="step <= currentStep
                ? 'bg-[var(--brand-red)] text-white'
                : 'bg-[var(--muted)] text-[var(--ink-muted)]'">
            {{ step }}
        </div>
        <div v-if="step < 4" class="h-0.5 w-8"
            :class="step < currentStep ? 'bg-[var(--brand-red)]' : 'bg-[var(--border)]'" />
    </div>
</div>
```

**Step 2: Run build to verify**

Run: `npm run build`
Expected: Build succeeds.

**Step 3: Commit**

```bash
git add resources/js/pages/campaigns/Create.vue
git commit -m "feat: convert Campaign Create to 4-step wizard

Step 1: Start item, Step 2: Goal item, Step 3: Campaign details,
Step 4: Review & launch. Progress bar at top, back/next navigation."
```

---

### Task 15: Redesign Campaign Show page

**Files:**
- Modify: `resources/js/pages/campaigns/Show.vue`

**Step 1: Rewrite campaigns/Show.vue**

Major changes:
- Add hero banner with category badge and status
- Redesign item showcase: Start (small) → Current (large, highlighted) → Goal (small, glowing ring)
- Add `ProgressRing` showing journey completion
- Add `MilestoneTimeline` below the progress ring
- Convert sections to tab-like layout: Story | Offers | Trades | Comments
  - For simplicity, use show/hide with a `activeTab` ref rather than real route-based tabs
- Add sticky "Make an Offer" CTA on mobile (fixed at bottom)

For the tab system:
```typescript
const activeTab = ref<'story' | 'offers' | 'trades' | 'comments'>('story');
```

Tab bar markup:
```html
<div class="flex gap-1 rounded-xl bg-[var(--muted)] p-1">
    <button v-for="tab in tabs" :key="tab.key"
        class="rounded-lg px-4 py-2 text-sm font-medium transition-colors"
        :class="activeTab === tab.key
            ? 'bg-white text-[var(--ink)] shadow-sm'
            : 'text-[var(--ink-muted)] hover:text-[var(--ink)]'"
        @click="activeTab = tab.key">
        {{ tab.label }}
        <Badge v-if="tab.count" variant="secondary" class="ml-1 rounded-full">{{ tab.count }}</Badge>
    </button>
</div>
```

Import `ProgressRing` and `MilestoneTimeline`. Build milestones from trades data:
```typescript
const milestones = computed(() => {
    const items: Milestone[] = [{ label: 'Start', status: 'completed' }];
    props.campaign.trades.forEach((trade, i) => {
        items.push({
            label: trade.offered_item?.title ?? `Trade ${i + 1}`,
            status: trade.status === 'completed' ? 'completed' : 'current',
        });
    });
    items.push({ label: 'Goal', status: 'future' });
    return items;
});
```

**Step 2: Run build to verify**

Run: `npm run build`
Expected: Build succeeds.

**Step 3: Commit**

```bash
git add resources/js/pages/campaigns/Show.vue
git commit -m "feat: redesign Campaign Show with tabs, progress ring, and timeline

Add tabbed content (Story/Offers/Trades/Comments), ProgressRing for
journey completion, MilestoneTimeline for trade visualization, and
sticky mobile Make an Offer CTA."
```

---

## Phase 5: Polish & Verification

### Task 16: Update Card component styling

**Files:**
- Modify: `resources/js/components/ui/card/Card.vue`

**Step 1: Update Card base styling**

Change from `border-2 border-border` to `border border-[var(--border)]` (thinner border, warmer) and update radius to `rounded-2xl` (keep) with warm shadow:

```
'bg-card text-card-foreground flex flex-col gap-6 rounded-2xl border border-[var(--border)] py-6 shadow-sm transition-shadow hover:shadow-md'
```

Remove the `border-2` — use single-width border for a lighter feel.

**Step 2: Run build to verify**

Run: `npm run build`
Expected: Build succeeds.

**Step 3: Commit**

```bash
git add resources/js/components/ui/card/Card.vue
git commit -m "style: lighten Card borders for Swap Shop aesthetic

Use single-width border instead of border-2 for lighter, more
modern card appearance."
```

---

### Task 17: Lint and format all changes

**Files:**
- All modified files

**Step 1: Run PHP linting**

Run: `vendor/bin/pint --dirty`
Expected: Files formatted (or already clean).

**Step 2: Run JS linting**

Run: `npm run lint`
Expected: Lint passes (auto-fixes applied).

**Step 3: Run Prettier**

Run: `npm run format`
Expected: Files formatted.

**Step 4: Run full build**

Run: `npm run build`
Expected: Build succeeds with no errors.

**Step 5: Commit any formatting changes**

```bash
git add -A
git commit -m "style: lint and format all redesign files"
```

---

### Task 18: Run tests

**Step 1: Run the full test suite**

Run: `composer test`
Expected: All tests pass. The redesign is frontend-only so existing PHP tests should not be affected.

**Step 2: If any tests fail, investigate and fix**

The most likely failure point would be if any test renders Inertia pages and checks for specific text that we changed. Fix any such assertions to match the new copy.

---

### Task 19: Final verification and commit

**Step 1: Start dev server and verify visually**

Run: `composer dev`

Check these pages in the browser:
- `/` (Welcome) — new PublicLayout with header, bottom tab bar on mobile, social proof, CampaignCards
- `/login` and `/register` — updated auth layout with new accent blobs
- `/dashboard` — greeting, stats row, campaign shortcuts
- `/campaigns` — pill category chips, CampaignCard grid
- `/campaigns/create` — 4-step wizard with progress bar
- `/campaigns/{id}` — tabs, progress ring, milestone timeline

**Step 2: Final commit if any visual tweaks were made**

```bash
git add -A
git commit -m "polish: final visual tweaks from manual verification"
```

---

## File Summary

### New Files (6)
- `resources/js/components/ProgressRing.vue`
- `resources/js/components/BottomTabBar.vue`
- `resources/js/components/CampaignCard.vue`
- `resources/js/components/MilestoneTimeline.vue`
- `resources/js/layouts/PublicLayout.vue`

### Modified Files (10)
- `resources/css/app.css` — design tokens, fonts, animations
- `resources/views/app.blade.php` — font imports
- `resources/js/components/ui/button/index.ts` — success + social variants
- `resources/js/components/ui/card/Card.vue` — lighter borders
- `resources/js/layouts/app/AppSidebarLayout.vue` — bottom tab bar + padding
- `resources/js/layouts/auth/AuthSimpleLayout.vue` — new accent colors
- `resources/js/pages/Welcome.vue` — full redesign with PublicLayout
- `resources/js/pages/Dashboard.vue` — greeting, stats, campaign grid
- `resources/js/pages/campaigns/Index.vue` — CampaignCard, pill chips
- `resources/js/pages/campaigns/Create.vue` — step wizard
- `resources/js/pages/campaigns/Show.vue` — tabs, progress ring, timeline
