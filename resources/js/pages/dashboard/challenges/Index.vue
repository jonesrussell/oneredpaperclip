<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ref } from 'vue';

import ChallengeCard from '@/components/ChallengeCard.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import challengeRoutes from '@/routes/challenges';
import { challenges as dashboardChallenges } from '@/routes/dashboard';
import type { BreadcrumbItem } from '@/types';

type CampaignItem = {
    id: number;
    title: string | null;
    status: string;
    trades_count?: number;
    user?: { id: number; name: string } | null;
    current_item?: { id: number; title: string } | null;
    goal_item?: { id: number; title: string } | null;
    category?: { id: number; name: string } | null;
};

const props = defineProps<{
    campaigns: {
        data: CampaignItem[];
        current_page: number;
        last_page: number;
        links: { url: string | null; label: string; active: boolean }[];
    };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: dashboard().url },
    { title: 'My Challenges', href: dashboardChallenges().url },
];

const campaignList = ref(props.campaigns.data);
</script>

<template>
    <Head title="My Challenges" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div
            class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4"
        >
            <h1 class="text-xl font-semibold">My Challenges</h1>

            <!-- Empty state -->
            <div
                v-if="campaignList.length === 0"
                class="rounded-2xl border border-dashed border-[var(--border)] bg-white/60 py-16 text-center text-[var(--ink-muted)]"
            >
                You haven't started any challenges yet.
                <br />
                <Link
                    :href="challengeRoutes.create().url"
                    class="mt-2 inline-block font-semibold text-[var(--brand-red)] hover:underline"
                >
                    Create a challenge
                </Link>
            </div>

            <!-- Challenge grid -->
            <ul v-else class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <li v-for="campaign in campaignList" :key="campaign.id">
                    <ChallengeCard :campaign="campaign" />
                </li>
            </ul>

            <!-- Pagination -->
            <nav
                v-if="campaigns.last_page > 1"
                class="flex flex-wrap items-center justify-center gap-2 pt-4"
                aria-label="Challenge pagination"
            >
                <template v-for="link in campaigns.links" :key="link.label">
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
    </AppLayout>
</template>
