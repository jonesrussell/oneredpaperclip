<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { dashboard, home, login, register } from '@/routes';
import campaigns from '@/routes/campaigns';

type FeaturedCampaign = {
    id: number;
    title: string | null;
    user?: { id: number; name: string } | null;
    current_item?: { id: number; title: string } | null;
    goal_item?: { id: number; title: string } | null;
};

withDefaults(
    defineProps<{
        canRegister: boolean;
        featuredCampaigns: FeaturedCampaign[];
    }>(),
    {
        canRegister: true,
        featuredCampaigns: () => [],
    },
);
</script>

<template>
    <Head
        title="One Red Paperclip — Trade up from one thing to something better"
    />
    <div
        class="min-h-screen bg-[var(--paper)] text-[var(--ink)] dark:bg-[var(--paper)]"
    >
        <!-- Grain overlay for texture -->
        <div
            class="welcome-grain pointer-events-none fixed inset-0 z-[1] opacity-[0.025] dark:opacity-[0.04]"
            aria-hidden="true"
        />

        <!-- Public header -->
        <header
            class="relative z-10 border-b border-[var(--ink)]/10 bg-[var(--paper)]/80 backdrop-blur-sm"
        >
            <div
                class="mx-auto flex max-w-6xl items-center justify-between gap-4 px-4 py-4 sm:px-6"
            >
                <Link
                    :href="home().url"
                    class="flex items-center gap-2 font-display text-xl font-semibold tracking-tight text-[var(--ink)] transition-opacity hover:opacity-80"
                >
                    <span class="text-[var(--brand-red)]"
                        >One Red Paperclip</span
                    >
                </Link>
                <nav
                    class="flex flex-wrap items-center justify-end gap-4 text-sm"
                    aria-label="Main navigation"
                >
                    <a
                        href="#how-it-works"
                        class="text-[var(--ink-muted)] transition-colors hover:text-[var(--ink)]"
                    >
                        How it works
                    </a>
                    <Link
                        :href="campaigns.index().url"
                        class="text-[var(--ink-muted)] transition-colors hover:text-[var(--ink)]"
                    >
                        Browse campaigns
                    </Link>
                    <template v-if="$page.props.auth.user">
                        <Link
                            :href="dashboard().url"
                            class="rounded-md border border-[var(--ink)]/20 bg-transparent px-4 py-2 font-medium text-[var(--ink)] transition-colors hover:border-[var(--ink)]/40 hover:bg-[var(--ink)]/5"
                        >
                            Dashboard
                        </Link>
                    </template>
                    <template v-else>
                        <Link
                            :href="login().url"
                            class="text-[var(--ink-muted)] transition-colors hover:text-[var(--ink)]"
                        >
                            Log in
                        </Link>
                        <Link
                            v-if="canRegister"
                            :href="register().url"
                            class="rounded-md border border-[var(--brand-red)] bg-[var(--brand-red)] px-4 py-2 font-medium text-white transition-colors hover:bg-[var(--brand-red-hover)]"
                        >
                            Get started
                        </Link>
                    </template>
                </nav>
            </div>
        </header>

        <main class="relative z-10">
            <!-- Hero -->
            <section
                class="mx-auto max-w-6xl px-4 py-16 sm:px-6 sm:py-24 md:py-32"
            >
                <div
                    class="grid gap-10 md:grid-cols-[1fr,auto] md:items-center md:gap-16"
                >
                    <div class="max-w-xl">
                        <h1
                            class="animate-in font-display text-4xl leading-[1.15] font-semibold tracking-tight text-[var(--ink)] [animation-duration:0.6s] [animation-fill-mode:both] fade-in slide-in-from-bottom-4 sm:text-5xl md:text-6xl"
                            style="animation-delay: 0ms"
                        >
                            Start with one thing.
                            <span class="text-[var(--brand-red)]"
                                >Trade up.</span
                            >
                        </h1>
                        <p
                            class="mt-5 animate-in text-lg leading-relaxed text-[var(--ink-muted)] [animation-duration:0.6s] [animation-fill-mode:both] fade-in slide-in-from-bottom-4"
                            style="animation-delay: 80ms"
                        >
                            Create a campaign with a start item and a goal.
                            Others make offers. You trade, confirm, and move
                            closer to your goal—one swap at a time.
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
                                v-else-if="canRegister"
                                :href="register().url"
                                class="inline-flex items-center rounded-md border border-[var(--ink)]/25 px-5 py-2.5 font-medium text-[var(--ink)] transition-colors hover:border-[var(--ink)]/40 hover:bg-[var(--ink)]/5"
                            >
                                Create an account
                            </Link>
                        </div>
                    </div>
                    <!-- Hero visual: paperclip + gradient orb -->
                    <div
                        class="relative flex animate-in justify-center [animation-duration:0.8s] [animation-fill-mode:both] fade-in slide-in-from-bottom-6 md:justify-end"
                        style="animation-delay: 120ms"
                    >
                        <div
                            class="absolute -inset-4 rounded-full bg-gradient-to-br from-[var(--brand-red)]/20 to-transparent blur-2xl"
                            aria-hidden="true"
                        />
                        <div
                            class="relative flex h-48 w-48 items-center justify-center rounded-2xl border border-[var(--ink)]/10 bg-[var(--paper)] shadow-lg dark:border-white/10"
                        >
                            <svg
                                class="h-24 w-24 text-[var(--brand-red)]"
                                viewBox="0 0 24 24"
                                fill="none"
                                xmlns="http://www.w3.org/2000/svg"
                                stroke="currentColor"
                                stroke-width="1.5"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                aria-hidden="true"
                            >
                                <path
                                    d="M21.44 11.05l-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48"
                                />
                            </svg>
                        </div>
                    </div>
                </div>
            </section>

            <!-- How it works -->
            <section
                id="how-it-works"
                class="border-t border-[var(--ink)]/10 bg-[var(--ink)]/[0.02] py-16 sm:py-24"
            >
                <div class="mx-auto max-w-6xl px-4 sm:px-6">
                    <h2
                        class="font-display text-2xl font-semibold tracking-tight text-[var(--ink)] sm:text-3xl"
                    >
                        How it works
                    </h2>
                    <p class="mt-2 max-w-xl text-[var(--ink-muted)]">
                        The same idea that took a red paperclip to a house.
                    </p>
                    <ul
                        class="mt-12 grid gap-8 sm:grid-cols-2 lg:grid-cols-4"
                        role="list"
                    >
                        <li
                            v-for="(step, i) in [
                                {
                                    title: 'Start',
                                    body: 'Create a campaign with something you have and something you want.',
                                },
                                {
                                    title: 'Offer',
                                    body: 'Others browse and submit offers: they propose a trade for your current item.',
                                },
                                {
                                    title: 'Trade',
                                    body: 'Accept an offer. Both parties confirm the trade to complete it.',
                                },
                                {
                                    title: 'Goal',
                                    body: 'Your current item updates. Keep trading until you reach your goal—or beyond.',
                                },
                            ]"
                            :key="step.title"
                            class="relative rounded-xl border border-[var(--ink)]/10 bg-[var(--paper)] p-6 shadow-sm transition-shadow hover:shadow-md"
                            :style="{
                                animation: 'welcome-fade-in 0.5s ease-out both',
                                animationDelay: `${200 + i * 60}ms`,
                            }"
                        >
                            <span
                                class="absolute -top-2 -left-2 flex h-8 w-8 items-center justify-center rounded-full bg-[var(--brand-red)] font-display text-sm font-semibold text-white"
                            >
                                {{ i + 1 }}
                            </span>
                            <h3
                                class="font-display text-lg font-semibold text-[var(--ink)]"
                            >
                                {{ step.title }}
                            </h3>
                            <p
                                class="mt-2 text-sm leading-relaxed text-[var(--ink-muted)]"
                            >
                                {{ step.body }}
                            </p>
                        </li>
                    </ul>
                </div>
            </section>

            <!-- Featured campaigns -->
            <section class="py-16 sm:py-24">
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
                            View all →
                        </Link>
                    </div>
                    <div
                        v-if="featuredCampaigns.length === 0"
                        class="mt-10 rounded-xl border border-dashed border-[var(--ink)]/20 py-16 text-center text-[var(--ink-muted)]"
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
                        class="mt-10 grid gap-6 sm:grid-cols-2 lg:grid-cols-3"
                        role="list"
                    >
                        <li
                            v-for="(campaign, i) in featuredCampaigns"
                            :key="campaign.id"
                        >
                            <Link
                                :href="
                                    campaigns.show({ campaign: campaign.id })
                                        .url
                                "
                                class="block rounded-xl border border-[var(--ink)]/10 bg-[var(--paper)] p-5 shadow-sm transition-all hover:border-[var(--brand-red)]/30 hover:shadow-md"
                                :style="{
                                    animation:
                                        'welcome-fade-in 0.5s ease-out both',
                                    animationDelay: `${100 + i * 50}ms`,
                                }"
                                prefetch
                            >
                                <h3
                                    class="line-clamp-2 font-display font-semibold text-[var(--ink)]"
                                >
                                    {{ campaign.title ?? 'Untitled campaign' }}
                                </h3>
                                <p
                                    v-if="
                                        campaign.current_item?.title &&
                                        campaign.goal_item?.title
                                    "
                                    class="mt-2 line-clamp-1 text-sm text-[var(--ink-muted)]"
                                >
                                    {{ campaign.current_item.title }} →
                                    {{ campaign.goal_item.title }}
                                </p>
                                <p
                                    v-if="campaign.user?.name"
                                    class="mt-2 text-xs text-[var(--ink-muted)]"
                                >
                                    {{ campaign.user.name }}
                                </p>
                            </Link>
                        </li>
                    </ul>
                </div>
            </section>

            <!-- CTA strip: always dark so white text is readable in light and dark theme -->
            <section
                class="border-t border-white/10 bg-[hsl(24_10%_12%)] py-16 text-center sm:py-20"
            >
                <div class="mx-auto max-w-2xl px-4 sm:px-6">
                    <h2
                        class="font-display text-2xl font-semibold tracking-tight text-white sm:text-3xl"
                    >
                        Ready to trade up?
                    </h2>
                    <p class="mt-3 text-white/80">
                        Join others who started with one thing and see how far
                        they go.
                    </p>
                    <div
                        class="mt-8 flex flex-wrap items-center justify-center gap-4"
                    >
                        <Link
                            :href="campaigns.index().url"
                            class="inline-flex rounded-md bg-white px-5 py-2.5 font-medium text-[var(--ink)] transition-opacity hover:opacity-90"
                        >
                            Browse campaigns
                        </Link>
                        <Link
                            v-if="!$page.props.auth.user && canRegister"
                            :href="register().url"
                            class="inline-flex rounded-md border border-white/40 px-5 py-2.5 font-medium text-white transition-colors hover:border-white/70 hover:bg-white/10"
                        >
                            Create an account
                        </Link>
                    </div>
                </div>
            </section>
        </main>

        <!-- Footer -->
        <footer class="relative z-10 border-t border-[var(--ink)]/10 py-8">
            <div
                class="mx-auto flex max-w-6xl flex-wrap items-center justify-between gap-4 px-4 sm:px-6"
            >
                <Link
                    :href="home().url"
                    class="font-display text-lg font-semibold text-[var(--ink)] transition-opacity hover:opacity-80"
                >
                    One Red Paperclip
                </Link>
                <nav
                    class="flex flex-wrap items-center gap-6 text-sm text-[var(--ink-muted)]"
                    aria-label="Footer navigation"
                >
                    <a href="#how-it-works" class="hover:text-[var(--ink)]">
                        How it works
                    </a>
                    <Link
                        :href="campaigns.index().url"
                        class="hover:text-[var(--ink)]"
                    >
                        Campaigns
                    </Link>
                    <Link
                        v-if="!$page.props.auth.user"
                        :href="login().url"
                        class="hover:text-[var(--ink)]"
                    >
                        Log in
                    </Link>
                </nav>
            </div>
        </footer>
    </div>
</template>

<style scoped>
.welcome-grain {
    background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 256 256' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)'/%3E%3C/svg%3E");
}

@keyframes welcome-fade-in {
    from {
        opacity: 0;
        transform: translateY(0.5rem);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
</style>
