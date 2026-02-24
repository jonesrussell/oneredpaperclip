<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

import ChallengeCard from '@/components/ChallengeCard.vue';
import AppLayout from '@/layouts/AppLayout.vue';
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
    categories: { id: number; name: string; slug: string }[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Challenges', href: '/campaigns' },
];

const campaignList = ref(props.campaigns.data);
const categoryList = ref(props.categories);
const activeCategory = ref<number | null>(null);

function filterByCategory(categoryId: number | null): void {
    activeCategory.value = categoryId;
    router.get(
        '/campaigns',
        categoryId != null ? { category_id: categoryId } : {},
        { preserveState: true },
    );
}
</script>

<template>
    <Head title="Challenges" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div
            class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4"
        >
            <h1 class="text-xl font-semibold">Challenges</h1>

            <!-- Category filter pills -->
            <div
                v-if="categoryList.length > 0"
                class="scrollbar-hide flex gap-2 overflow-x-auto pb-1"
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

            <!-- Empty state -->
            <div
                v-if="campaignList.length === 0"
                class="rounded-2xl border border-dashed border-[var(--border)] bg-white/60 py-16 text-center text-[var(--ink-muted)]"
            >
                No challenges yet. Be the first to start a trade-up.
                <br />
                <Link
                    href="/campaigns/create"
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
