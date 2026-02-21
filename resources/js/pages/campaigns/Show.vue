<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3';
import { ArrowRight, User } from 'lucide-vue-next';
import { computed, ref } from 'vue';

import MilestoneTimeline from '@/components/MilestoneTimeline.vue';
import ProgressRing from '@/components/ProgressRing.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';

type ItemSummary = {
    id: number;
    title: string;
};

type TradeSummary = {
    id: number;
    status: string;
    position: number;
    offered_item?: ItemSummary | null;
};

type OfferSummary = {
    id: number;
    status: string;
    message?: string | null;
    from_user?: { id: number; name: string } | null;
    offered_item?: ItemSummary | null;
};

type CommentSummary = {
    id: number;
    body: string;
    user?: { id: number; name: string } | null;
    created_at: string;
};

type Campaign = {
    id: number;
    title?: string | null;
    story?: string | null;
    status: string;
    user_id?: number;
    user?: { id: number; name: string } | null;
    category?: { id: number; name: string } | null;
    current_item?: ItemSummary | null;
    goal_item?: ItemSummary | null;
    trades?: TradeSummary[];
    offers?: OfferSummary[];
    comments?: CommentSummary[];
};

const props = defineProps<{
    campaign: Campaign;
    isFollowing: boolean;
}>();

const page = usePage();

const isOwner = computed(() => {
    const userId = page.props.auth?.user?.id;
    return userId != null && userId === props.campaign.user_id;
});

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Campaigns', href: '/campaigns' },
    {
        title: props.campaign.title ?? 'Campaign',
        href: `/campaigns/${props.campaign.id}`,
    },
];

// Tab system
const activeTab = ref<'story' | 'offers' | 'trades' | 'comments'>('story');

const tabs = computed(() => [
    { key: 'story' as const, label: 'Story', count: null },
    {
        key: 'offers' as const,
        label: 'Offers',
        count: props.campaign.offers?.length || 0,
    },
    {
        key: 'trades' as const,
        label: 'Trades',
        count: props.campaign.trades?.length || 0,
    },
    {
        key: 'comments' as const,
        label: 'Comments',
        count: props.campaign.comments?.length || 0,
    },
]);

// Progress percentage (rough estimate: assume 10 trades to reach goal, cap at 100)
const progress = computed(() => {
    const trades = props.campaign.trades?.length ?? 0;
    if (trades === 0) return 0;
    return Math.min(Math.round((trades / 10) * 100), 100);
});

// Milestones from trades
type MilestoneItem = {
    label: string;
    status: 'completed' | 'current' | 'future';
};

const milestones = computed<MilestoneItem[]>(() => {
    const items: MilestoneItem[] = [{ label: 'Start', status: 'completed' }];

    props.campaign.trades?.forEach((trade, i) => {
        items.push({
            label: trade.offered_item?.title ?? `Trade ${i + 1}`,
            status: trade.status === 'completed' ? 'completed' : 'current',
        });
    });

    items.push({
        label: 'Goal',
        status: props.campaign.status === 'completed' ? 'completed' : 'future',
    });

    return items;
});

function getStatusClasses(status: string): string {
    switch (status) {
        case 'active':
            return 'bg-[var(--electric-mint)]/10 text-emerald-700';
        case 'completed':
            return 'bg-[var(--electric-mint)]/15 text-[var(--electric-mint)]';
        default:
            return 'bg-[var(--muted)]';
    }
}

function formatDate(dateString: string): string {
    return new Date(dateString).toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
    });
}
</script>

<template>
    <Head :title="campaign.title ?? 'Campaign'" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto w-full max-w-4xl space-y-6 p-4 sm:p-6">
            <!-- Header -->
            <div>
                <div class="flex flex-wrap items-center gap-2">
                    <Badge
                        v-if="campaign.category?.name"
                        variant="secondary"
                        class="rounded-full text-xs"
                    >
                        {{ campaign.category.name }}
                    </Badge>
                    <Badge
                        variant="secondary"
                        class="rounded-full text-xs capitalize"
                        :class="getStatusClasses(campaign.status)"
                    >
                        {{ campaign.status }}
                    </Badge>
                </div>
                <h1
                    class="mt-2 font-display text-2xl font-bold tracking-tight text-[var(--ink)] sm:text-3xl"
                >
                    {{ campaign.title ?? 'Untitled campaign' }}
                </h1>
                <div
                    class="mt-2 flex items-center gap-2 text-sm text-[var(--ink-muted)]"
                >
                    <User class="size-4" />
                    <span>{{ campaign.user?.name ?? 'Anonymous' }}</span>
                </div>
            </div>

            <!-- Journey card -->
            <div class="rounded-2xl border border-[var(--border)] bg-white p-5">
                <h2
                    class="text-sm font-semibold tracking-wider text-[var(--ink-muted)] uppercase"
                >
                    Trade Journey
                </h2>

                <!-- Start -> Current -> Goal -->
                <div
                    class="mt-4 flex items-center justify-between gap-3 text-sm"
                >
                    <div class="flex-1 text-center">
                        <p class="text-xs text-[var(--ink-muted)]">Start</p>
                        <p class="mt-0.5 font-semibold text-[var(--ink)]">
                            {{ campaign.current_item?.title ?? 'TBD' }}
                        </p>
                    </div>
                    <ArrowRight
                        class="size-5 shrink-0 text-[var(--brand-red)]"
                    />
                    <div class="flex-1 text-center">
                        <p class="text-xs text-[var(--ink-muted)]">Goal</p>
                        <p class="mt-0.5 font-semibold text-[var(--ink)]">
                            {{ campaign.goal_item?.title ?? 'TBD' }}
                        </p>
                    </div>
                </div>

                <!-- Progress ring -->
                <div class="flex items-center justify-center py-4">
                    <ProgressRing
                        :percent="progress"
                        :size="80"
                        :stroke-width="6"
                    />
                </div>

                <!-- Milestone timeline -->
                <MilestoneTimeline
                    v-if="milestones.length > 2"
                    :milestones="milestones"
                />
            </div>

            <!-- Tab bar -->
            <div class="flex gap-1 rounded-xl bg-[var(--muted)] p-1">
                <button
                    v-for="tab in tabs"
                    :key="tab.key"
                    class="rounded-lg px-4 py-2 text-sm font-medium transition-colors"
                    :class="
                        activeTab === tab.key
                            ? 'bg-white text-[var(--ink)] shadow-sm'
                            : 'text-[var(--ink-muted)] hover:text-[var(--ink)]'
                    "
                    @click="activeTab = tab.key"
                >
                    {{ tab.label }}
                    <Badge
                        v-if="tab.count"
                        variant="secondary"
                        class="ml-1 rounded-full text-xs"
                    >
                        {{ tab.count }}
                    </Badge>
                </button>
            </div>

            <!-- Tab content -->
            <div v-show="activeTab === 'story'" class="space-y-4">
                <div
                    v-if="campaign.story"
                    class="prose prose-sm max-w-none rounded-2xl border border-[var(--border)] bg-white p-5 text-[var(--ink)]"
                >
                    {{ campaign.story }}
                </div>
                <div
                    v-else
                    class="rounded-2xl border border-dashed border-[var(--border)] bg-white/60 py-12 text-center text-sm text-[var(--ink-muted)]"
                >
                    No story yet.
                </div>
            </div>

            <div v-show="activeTab === 'offers'" class="space-y-3">
                <div
                    v-if="!campaign.offers?.length"
                    class="rounded-2xl border border-dashed border-[var(--border)] bg-white/60 py-12 text-center text-sm text-[var(--ink-muted)]"
                >
                    No pending offers.
                </div>
                <div
                    v-for="offer in campaign.offers"
                    v-else
                    :key="offer.id"
                    class="rounded-xl border border-[var(--border)] bg-white p-4"
                >
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-sm font-semibold text-[var(--ink)]">
                                {{
                                    offer.offered_item?.title ?? 'Unknown item'
                                }}
                            </p>
                            <p class="mt-0.5 text-xs text-[var(--ink-muted)]">
                                from {{ offer.from_user?.name ?? 'Anonymous' }}
                            </p>
                            <p
                                v-if="offer.message"
                                class="mt-2 text-sm text-[var(--ink-muted)]"
                            >
                                {{ offer.message }}
                            </p>
                        </div>
                        <Badge
                            variant="secondary"
                            class="shrink-0 rounded-full text-xs capitalize"
                        >
                            {{ offer.status }}
                        </Badge>
                    </div>
                </div>
            </div>

            <div v-show="activeTab === 'trades'" class="space-y-3">
                <div
                    v-if="!campaign.trades?.length"
                    class="rounded-2xl border border-dashed border-[var(--border)] bg-white/60 py-12 text-center text-sm text-[var(--ink-muted)]"
                >
                    No trades yet.
                </div>
                <div
                    v-for="trade in campaign.trades"
                    v-else
                    :key="trade.id"
                    class="rounded-xl border border-[var(--border)] bg-white p-4"
                >
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <p class="text-sm font-semibold text-[var(--ink)]">
                                Trade #{{ trade.position }}
                            </p>
                            <p class="mt-0.5 text-xs text-[var(--ink-muted)]">
                                {{
                                    trade.offered_item?.title ?? 'Unknown item'
                                }}
                            </p>
                        </div>
                        <Badge
                            variant="secondary"
                            class="shrink-0 rounded-full text-xs capitalize"
                            :class="
                                trade.status === 'completed'
                                    ? 'bg-[var(--electric-mint)]/15 text-[var(--electric-mint)]'
                                    : ''
                            "
                        >
                            {{ trade.status }}
                        </Badge>
                    </div>
                </div>
            </div>

            <div v-show="activeTab === 'comments'" class="space-y-3">
                <div
                    v-if="!campaign.comments?.length"
                    class="rounded-2xl border border-dashed border-[var(--border)] bg-white/60 py-12 text-center text-sm text-[var(--ink-muted)]"
                >
                    No comments yet.
                </div>
                <div
                    v-for="comment in campaign.comments"
                    v-else
                    :key="comment.id"
                    class="rounded-xl border border-[var(--border)] bg-white p-4"
                >
                    <div class="flex items-start gap-3">
                        <div
                            class="flex size-8 shrink-0 items-center justify-center rounded-full bg-[var(--muted)] text-xs font-semibold text-[var(--ink-muted)]"
                        >
                            {{
                                (comment.user?.name ?? 'A')
                                    .charAt(0)
                                    .toUpperCase()
                            }}
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-2">
                                <span
                                    class="text-sm font-semibold text-[var(--ink)]"
                                >
                                    {{ comment.user?.name ?? 'Anonymous' }}
                                </span>
                                <span class="text-xs text-[var(--ink-muted)]">
                                    {{ formatDate(comment.created_at) }}
                                </span>
                            </div>
                            <p class="mt-1 text-sm text-[var(--ink)]">
                                {{ comment.body }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sticky mobile CTA -->
        <div
            v-if="!isOwner"
            class="fixed inset-x-0 bottom-16 z-40 border-t border-[var(--border)] bg-white/95 p-3 backdrop-blur-md lg:hidden"
        >
            <Button class="w-full" size="lg"> Make an Offer </Button>
        </div>
    </AppLayout>
</template>
