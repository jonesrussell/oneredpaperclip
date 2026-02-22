<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';

import CampaignCard from '@/components/CampaignCard.vue';
import PaperclipIcon from '@/components/PaperclipIcon.vue';
import PublicLayout from '@/layouts/PublicLayout.vue';
import { about, register } from '@/routes';
import campaigns from '@/routes/campaigns';

type FeaturedCampaign = {
    id: number;
    title: string | null;
    status: string;
    trades_count?: number;
    user?: { id: number; name: string } | null;
    current_item?: { id: number; title: string } | null;
    goal_item?: { id: number; title: string } | null;
    category?: { id: number; name: string } | null;
};

const props = withDefaults(
    defineProps<{
        canRegister: boolean;
        featuredCampaigns: FeaturedCampaign[];
        stats: {
            campaignsCount: number;
            tradesCount: number;
            usersCount: number;
        };
    }>(),
    {
        canRegister: true,
        featuredCampaigns: () => [],
        stats: () => ({
            campaignsCount: 0,
            tradesCount: 0,
            usersCount: 0,
        }),
    },
);

function formatStat(count: number): string {
    return `${count.toLocaleString()}+`;
}

const howItWorksSteps = [
    {
        title: 'Start',
        body: 'Create a campaign with something you have and something you want.',
        color: 'coral',
    },
    {
        title: 'Offer',
        body: 'Others browse and submit offers: they propose a trade for your current item.',
        color: 'yellow',
    },
    {
        title: 'Trade',
        body: 'Accept an offer. Both parties confirm the trade to complete it.',
        color: 'mint',
    },
    {
        title: 'Goal',
        body: 'Your current item updates. Keep trading until you reach your goal—or beyond.',
        color: 'brand',
    },
];

function stepNodeColor(color: string): string {
    switch (color) {
        case 'coral':
            return 'bg-[var(--hot-coral)]';
        case 'yellow':
            return 'bg-[var(--sunny-yellow)]';
        case 'mint':
            return 'bg-[var(--electric-mint)]';
        case 'brand':
            return 'bg-[var(--brand-red)]';
        default:
            return 'bg-[var(--brand-red)]';
    }
}
</script>

<template>
    <Head
        title="One Red Paperclip — Trade up from one thing to something better"
    />
    <PublicLayout>
        <!-- Hero: full-bleed background, content centered -->
        <section class="relative w-full py-16 sm:py-24 md:py-32">
            <!-- Full-bleed hero gradient (edge to edge, no rounded corners) -->
            <div
                class="pointer-events-none absolute inset-0 bg-gradient-to-br from-[var(--hot-coral)]/12 via-transparent to-[var(--sunny-yellow)]/10"
                aria-hidden="true"
            />
            <div class="relative mx-auto max-w-6xl px-4 sm:px-6">
            <div class="relative">
            <div
                class="grid gap-10 md:grid-cols-[1fr,auto] md:grid-rows-[auto,auto] md:items-start md:gap-x-16 md:gap-y-4"
            >
                <h1
                    class="order-1 animate-in font-display text-4xl leading-[1.15] font-semibold tracking-tight text-[var(--ink)] [animation-duration:0.6s] [animation-fill-mode:both] fade-in slide-in-from-bottom-4 sm:text-5xl md:order-none md:row-start-1 md:col-start-1 md:text-6xl"
                    style="animation-delay: 0ms"
                >
                    Start with one thing.
                    <span class="text-[var(--brand-red)]">Trade up.</span>
                </h1>
                <!-- Paperclip: own column, spans both rows, vertically centered to left column -->
                <div
                    class="order-3 relative flex h-full animate-in justify-center [animation-duration:0.8s] [animation-fill-mode:both] fade-in slide-in-from-bottom-6 md:order-none md:row-start-1 md:row-span-2 md:col-start-2 md:h-full md:items-center md:justify-end"
                    style="animation-delay: 120ms"
                >
                    <div
                        class="absolute -inset-4 rounded-full bg-gradient-to-br from-[var(--brand-red)]/20 to-transparent blur-2xl"
                        aria-hidden="true"
                    />
                    <!-- House-shaped container: one red paperclip → house -->
                    <div
                        class="hero-house relative flex h-48 w-48 shrink-0 items-center justify-center bg-[var(--paper)] shadow-lg dark:bg-[var(--paper)] dark:shadow-[var(--shadow-card)]"
                    >
                        <PaperclipIcon
                            class="hero-house-paperclip h-16 w-16 !text-[var(--brand-red)]"
                        />
                    </div>
                </div>
                <div
                    class="order-2 max-w-xl md:order-none md:row-start-2 md:col-start-1"
                >
                    <p
                        class="mt-5 animate-in text-lg leading-relaxed text-[var(--ink-muted)] [animation-duration:0.6s] [animation-fill-mode:both] fade-in slide-in-from-bottom-4 md:mt-0"
                        style="animation-delay: 80ms"
                    >
                        Create a campaign with a start item and a goal. Others
                        make offers. You trade, confirm, and move closer to your
                        goal—one swap at a time.
                    </p>
                    <div
                        class="mt-8 flex animate-in flex-wrap gap-4 [animation-duration:0.6s] [animation-fill-mode:both] fade-in slide-in-from-bottom-4"
                        style="animation-delay: 160ms"
                    >
                        <Link
                            :href="campaigns.index().url"
                            class="inline-flex items-center rounded-md bg-[var(--brand-red)] px-5 py-2.5 font-medium text-white shadow-sm transition-colors hover:bg-[var(--brand-red-hover)]"
                        >
                            Browse campaigns
                        </Link>
                        <Link
                            v-if="$page.props.auth.user"
                            :href="campaigns.create().url"
                            class="inline-flex items-center rounded-md border border-[var(--ink)]/25 px-5 py-2.5 font-medium text-[var(--ink)] transition-colors hover:border-[var(--ink)]/40 hover:bg-[var(--ink)]/5"
                        >
                            Start a campaign
                        </Link>
                        <Link
                            v-else-if="props.canRegister"
                            :href="register().url"
                            class="inline-flex items-center rounded-md border border-[var(--ink)]/25 px-5 py-2.5 font-medium text-[var(--ink)] transition-colors hover:border-[var(--ink)]/40 hover:bg-[var(--ink)]/5"
                        >
                            Create an account
                        </Link>
                    </div>
                </div>
            </div>
            </div>
            </div>
        </section>

        <!-- Social proof stats -->
        <section
            class="border-y border-[var(--border)] bg-[var(--card)]/80 py-8 dark:bg-white/5"
        >
            <div
                class="mx-auto flex max-w-6xl flex-wrap items-center justify-center gap-8 px-4 sm:gap-16 sm:px-6"
            >
                <div class="text-center">
                    <p
                        class="font-mono text-2xl font-bold text-[var(--brand-red)]"
                    >
                        {{ formatStat(props.stats.campaignsCount) }}
                    </p>
                    <p class="text-sm text-[var(--ink-muted)]">Campaigns</p>
                </div>
                <div class="text-center">
                    <p
                        class="font-mono text-2xl font-bold text-[var(--electric-mint)]"
                    >
                        {{ formatStat(props.stats.tradesCount) }}
                    </p>
                    <p class="text-sm text-[var(--ink-muted)]">Trades</p>
                </div>
                <div class="text-center">
                    <p
                        class="font-mono text-2xl font-bold text-[var(--sunny-yellow)]"
                    >
                        {{ formatStat(props.stats.usersCount) }}
                    </p>
                    <p class="text-sm text-[var(--ink-muted)]">Community</p>
                </div>
            </div>
        </section>

        <!-- How it works + Inspired by (one story-led section) -->
        <section
            id="how-it-works"
            class="border-t border-[var(--ink)]/10 bg-[var(--ink)]/[0.02] py-16 sm:py-20"
        >
            <div class="mx-auto max-w-4xl px-4 sm:px-6">
                <!-- Intro: true story + bridge to path -->
                <div class="text-center">
                    <span
                        class="text-xs font-semibold tracking-wider text-[var(--brand-red)] uppercase"
                    >
                        Inspired by a true story
                    </span>
                    <p
                        class="mt-3 text-base leading-relaxed text-[var(--ink-muted)] sm:text-lg"
                    >
                        In 2005, Kyle MacDonald traded a single red paperclip
                        through 14 swaps until he owned a house—all in one year.
                        Same idea here. Here’s how it works:
                    </p>
                </div>

                <!-- Path: vertical line with alternating nodes (Duolingo-style) -->
                <div class="relative mt-14 sm:mt-16">
                    <!-- Vertical track (line through the middle) -->
                    <div
                        class="absolute left-1/2 top-0 bottom-0 w-1 -translate-x-1/2 rounded-full bg-gradient-to-b from-[var(--hot-coral)] via-[var(--sunny-yellow)] to-[var(--brand-red)] opacity-40 sm:w-1.5"
                        aria-hidden="true"
                    />

                    <ul class="relative space-y-0" role="list">
                        <li
                            v-for="(step, i) in howItWorksSteps"
                            :key="step.title"
                            class="flex flex-col items-center gap-6 py-6 sm:flex-row sm:gap-6 sm:py-8"
                            :style="{
                                animation:
                                    'welcome-path-in 0.6s ease-out both',
                                animationDelay: `${280 + i * 100}ms`,
                            }"
                        >
                            <!-- Content card: order 1 on left (even i), order 3 on right (odd i) -->
                            <div
                                class="w-full flex-1 sm:max-w-[calc(50%-2.5rem)]"
                                :class="[
                                    i % 2 === 0
                                        ? 'sm:order-1 sm:pr-3 sm:text-right'
                                        : 'sm:order-3 sm:pl-3 sm:text-left',
                                ]"
                            >
                                <div
                                    class="rounded-2xl border border-[var(--ink)]/10 bg-[var(--paper)] p-5 shadow-sm transition-all hover:-translate-y-0.5 hover:shadow-md sm:p-6"
                                >
                                    <h3
                                        class="font-display text-lg font-semibold text-[var(--ink)] sm:text-xl"
                                    >
                                        {{ step.title }}
                                    </h3>
                                    <p
                                        class="mt-2 text-sm leading-relaxed text-[var(--ink-muted)]"
                                    >
                                        {{ step.body }}
                                    </p>
                                </div>
                            </div>

                            <!-- Node on the path (always center) -->
                            <div
                                class="relative z-10 flex h-14 w-14 shrink-0 items-center justify-center rounded-full text-white shadow-lg ring-4 ring-[var(--paper)] sm:order-2 sm:h-16 sm:w-16 sm:ring-[6px]"
                                :class="stepNodeColor(step.color)"
                            >
                                <span
                                    class="font-display text-xl font-bold sm:text-2xl"
                                >
                                    {{ i + 1 }}
                                </span>
                            </div>

                            <!-- Spacer so card alternates left/right -->
                            <div
                                class="hidden flex-1 sm:block sm:max-w-[calc(50%-2.5rem)]"
                                :class="i % 2 === 0 ? 'sm:order-3' : 'sm:order-1'"
                            />
                        </li>
                    </ul>
                </div>

                <!-- CTA: full story -->
                <p class="mt-10 text-center">
                    <Link
                        :href="about().url"
                        class="inline-flex items-center gap-1.5 text-sm font-medium text-[var(--brand-red)] transition-colors hover:underline"
                    >
                        Read the full story
                        <span aria-hidden="true">&rarr;</span>
                    </Link>
                </p>
            </div>
        </section>

        <!-- Featured campaigns -->
        <section class="border-t border-[var(--ink)]/8 py-16 sm:py-24">
            <div class="mx-auto max-w-6xl px-4 sm:px-6">
                <div class="flex flex-wrap items-end justify-between gap-4">
                    <div>
                        <h2
                            class="font-display text-2xl font-semibold tracking-tight text-[var(--ink)] sm:text-3xl"
                        >
                            Active campaigns
                        </h2>
                        <p class="mt-1 text-[var(--ink-muted)]">
                            See what others are trading toward.
                        </p>
                    </div>
                    <Link
                        :href="campaigns.index().url"
                        class="text-sm font-medium text-[var(--brand-red)] transition-colors hover:underline"
                    >
                        View all &rarr;
                    </Link>
                </div>
                <div
                    v-if="props.featuredCampaigns.length === 0"
                    class="mt-10 rounded-xl border border-dashed border-[var(--ink)]/20 bg-[var(--paper)]/60 py-16 text-center text-[var(--ink-muted)]"
                >
                    No public campaigns yet. Be the first to start one.
                    <Link
                        v-if="$page.props.auth.user"
                        :href="campaigns.create().url"
                        class="ml-1 font-medium text-[var(--brand-red)] hover:underline"
                    >
                        Create a campaign
                    </Link>
                </div>
                <ul
                    v-else
                    class="mt-10 grid gap-5 sm:grid-cols-2 lg:grid-cols-3"
                    role="list"
                >
                    <li
                        v-for="(campaign, i) in props.featuredCampaigns"
                        :key="campaign.id"
                        class="animate-in fade-in slide-in-from-bottom-3 [animation-duration:0.45s] [animation-fill-mode:both]"
                        :style="{ animationDelay: `${120 + i * 50}ms` }"
                    >
                        <CampaignCard :campaign="campaign" />
                    </li>
                </ul>
            </div>
        </section>

        <!-- CTA strip -->
        <section
            class="border-t border-white/10 bg-[hsl(0_0%_11%)] py-16 text-center sm:py-20 dark:bg-background"
        >
            <div class="mx-auto max-w-2xl px-4 sm:px-6">
                <h2
                    class="font-display text-2xl font-semibold tracking-tight text-white sm:text-3xl"
                >
                    Ready to trade up?
                </h2>
                <p class="mt-3 text-white/80">
                    Join others who started with one thing and see how far they
                    go.
                </p>
                <div
                    class="mt-8 flex flex-wrap items-center justify-center gap-4"
                >
                    <Link
                        :href="campaigns.index().url"
                        class="surface-light inline-flex rounded-md bg-white px-5 py-2.5 font-medium text-[var(--ink)] transition-opacity hover:opacity-90"
                    >
                        Browse campaigns
                    </Link>
                    <Link
                        v-if="!$page.props.auth.user && props.canRegister"
                        :href="register().url"
                        class="inline-flex rounded-md border border-white/40 px-5 py-2.5 font-medium text-white transition-colors hover:border-white/70 hover:bg-white/10"
                    >
                        Create an account
                    </Link>
                </div>
            </div>
        </section>
    </PublicLayout>
</template>

<style scoped>
/* House shape: roof peak + body + chimney; chimney left edge on same roof line as peak→eave */
.hero-house {
    clip-path: polygon(
        50% 4%,
        82% 29%,
        82% 16%,
        94% 16%,
        94% 38%,
        94% 96%,
        6% 96%,
        6% 38%
    );
    -webkit-clip-path: polygon(
        50% 4%,
        82% 29%,
        82% 16%,
        94% 16%,
        94% 38%,
        94% 96%,
        6% 96%,
        6% 38%
    );
}

/* Center paperclip in the square body of the house (roof ends at 38%) */
.hero-house-paperclip {
    transform: translateY(24%);
}

@keyframes welcome-path-in {
    from {
        opacity: 0;
        transform: translateY(0.75rem) scale(0.98);
    }
    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}
</style>
