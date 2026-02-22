<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { ArrowRight, Pencil, User } from 'lucide-vue-next';
import { computed, ref } from 'vue';

import MilestoneTimeline from '@/components/MilestoneTimeline.vue';
import ProgressRing from '@/components/ProgressRing.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import campaigns from '@/routes/campaigns';
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
        <div class="min-h-full bg-background">
            <div class="mx-auto w-full max-w-4xl space-y-6 p-4 sm:p-6">
                <!-- Header with subtle gradient (matches Welcome hero feel) -->
                <div class="relative">
                    <div
                        class="pointer-events-none absolute -left-4 -right-4 -top-2 h-32 rounded-b-2xl bg-gradient-to-br from-[var(--hot-coral)]/10 via-transparent to-[var(--sunny-yellow)]/8 sm:-left-6 sm:-right-6"
                        aria-hidden="true"
                    />
                    <div class="relative">
                        <div class="flex flex-wrap items-center gap-2">
                            <Badge
                                v-if="campaign.category?.name"
                                variant="secondary"
                                class="rounded-full border-0 text-xs"
                                :style="{
                                    backgroundColor: 'var(--soft-lavender)',
                                    color: 'var(--foreground)',
                                }"
                            >
                                {{ campaign.category.name }}
                            </Badge>
                            <Badge
                                variant="secondary"
                                class="rounded-full border-0 text-xs capitalize"
                                :class="getStatusClasses(campaign.status)"
                            >
                                {{ campaign.status }}
                            </Badge>
                        </div>
                        <h1
                            class="mt-2 font-display text-2xl font-bold tracking-tight text-foreground sm:text-3xl"
                        >
                            {{ campaign.title ?? 'Untitled campaign' }}
                        </h1>
                        <div
                            class="mt-2 flex flex-wrap items-center gap-2 text-sm text-muted-foreground"
                        >
                            <User class="size-4 text-[var(--sky-blue)]" />
                            <span>{{ campaign.user?.name ?? 'Anonymous' }}</span>
                            <Link
                                v-if="isOwner"
                                :href="campaigns.edit.url({ campaign: campaign.id })"
                                class="inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-sm font-medium text-foreground transition-colors hover:bg-muted"
                            >
                                <Pencil class="size-4" />
                                Edit campaign
                            </Link>
                        </div>
                    </div>
                </div>

                <!-- Journey card (Swap Shop card style: warm shadow, border) -->
                <div
                    class="rounded-2xl border border-border bg-card p-5 shadow-sm transition-shadow hover:shadow-md dark:shadow-[var(--shadow-card)]"
                >
                <h2
                    class="text-sm font-semibold tracking-wider text-muted-foreground uppercase"
                >
                    Trade Journey
                </h2>

                <!-- Start -> Current -> Goal -->
                <div
                    class="mt-4 flex items-center justify-between gap-3 text-sm"
                >
                    <div class="flex-1 text-center">
                        <p class="text-xs text-muted-foreground">Start</p>
                        <p class="mt-0.5 font-semibold text-foreground">
                            {{ campaign.current_item?.title ?? 'TBD' }}
                        </p>
                    </div>
                    <ArrowRight
                        class="size-5 shrink-0 text-[var(--brand-red)]"
                    />
                    <div class="flex-1 text-center">
                        <p class="text-xs text-muted-foreground">Goal</p>
                        <p class="mt-0.5 font-semibold text-foreground">
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

            <!-- Tab bar (Swap Shop: active = accent pill) -->
            <div
                class="flex gap-1 rounded-xl border border-border bg-muted/50 p-1.5"
            >
                <button
                    v-for="tab in tabs"
                    :key="tab.key"
                    class="rounded-lg px-4 py-2 text-sm font-medium transition-all"
                    :class="
                        activeTab === tab.key
                            ? 'bg-[var(--hot-coral)]/15 text-[var(--hot-coral)] shadow-sm'
                            : 'text-muted-foreground hover:bg-muted hover:text-foreground'
                    "
                    @click="activeTab = tab.key"
                >
                    {{ tab.label }}
                    <Badge
                        v-if="tab.count"
                        variant="secondary"
                        class="ml-1 rounded-full border-0 text-xs"
                        :class="
                            activeTab === tab.key
                                ? 'bg-[var(--hot-coral)]/25 text-[var(--hot-coral)]'
                                : ''
                        "
                    >
                        {{ tab.count }}
                    </Badge>
                </button>
            </div>

            <!-- Tab content -->
            <div v-show="activeTab === 'story'" class="space-y-4">
                <div
                    v-if="campaign.story"
                    class="prose prose-sm max-w-none rounded-2xl border border-border bg-card p-5 text-foreground shadow-sm dark:prose-invert dark:shadow-[var(--shadow-card)]"
                >
                    {{ campaign.story }}
                </div>
                <div
                    v-else
                    class="rounded-2xl border border-dashed border-border bg-card/60 py-12 text-center text-sm text-muted-foreground"
                >
                    No story yet.
                </div>
            </div>

            <div v-show="activeTab === 'offers'" class="space-y-3">
                <div
                    v-if="!campaign.offers?.length"
                    class="rounded-2xl border border-dashed border-border bg-card/60 py-12 text-center text-sm text-muted-foreground"
                >
                    No pending offers.
                </div>
                <div
                    v-for="offer in campaign.offers"
                    v-else
                    :key="offer.id"
                    class="rounded-xl border border-border bg-card p-4 shadow-sm dark:shadow-[var(--shadow-card)]"
                    style="box-shadow: 0 2px 12px rgba(28, 18, 8, 0.06);"
                >
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-sm font-semibold text-foreground">
                                {{
                                    offer.offered_item?.title ?? 'Unknown item'
                                }}
                            </p>
                            <p class="mt-0.5 text-xs text-muted-foreground">
                                from {{ offer.from_user?.name ?? 'Anonymous' }}
                            </p>
                            <p
                                v-if="offer.message"
                                class="mt-2 text-sm text-muted-foreground"
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
                    class="rounded-2xl border border-dashed border-border bg-card/60 py-12 text-center text-sm text-muted-foreground"
                >
                    No trades yet.
                </div>
                <div
                    v-for="trade in campaign.trades"
                    v-else
                    :key="trade.id"
                    class="rounded-xl border border-border bg-card p-4 shadow-sm dark:shadow-[var(--shadow-card)]"
                    style="box-shadow: 0 2px 12px rgba(28, 18, 8, 0.06);"
                >
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <p class="text-sm font-semibold text-foreground">
                                Trade #{{ trade.position }}
                            </p>
                            <p class="mt-0.5 text-xs text-muted-foreground">
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
                    class="rounded-2xl border border-dashed border-border bg-card/60 py-12 text-center text-sm text-muted-foreground"
                >
                    No comments yet.
                </div>
                <div
                    v-for="comment in campaign.comments"
                    v-else
                    :key="comment.id"
                    class="rounded-xl border border-border bg-card p-4 shadow-sm dark:shadow-[var(--shadow-card)]"
                    style="box-shadow: 0 2px 12px rgba(28, 18, 8, 0.06);"
                >
                    <div class="flex items-start gap-3">
                        <div
                            class="flex size-8 shrink-0 items-center justify-center rounded-full bg-[var(--sky-blue)]/20 text-xs font-semibold text-[var(--sky-blue)]"
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
                                    class="text-sm font-semibold text-foreground"
                                >
                                    {{ comment.user?.name ?? 'Anonymous' }}
                                </span>
                                <span class="text-xs text-muted-foreground">
                                    {{ formatDate(comment.created_at) }}
                                </span>
                            </div>
                            <p class="mt-1 text-sm text-foreground">
                                {{ comment.body }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            </div>
        </div>

        <!-- Sticky mobile CTA (Swap Shop: brand CTA) -->
        <div
            v-if="!isOwner"
            class="fixed inset-x-0 bottom-16 z-40 border-t border-border bg-background/95 p-3 backdrop-blur-md lg:hidden"
        >
            <Button
                class="w-full bg-[var(--brand-red)] text-white hover:bg-[var(--brand-red-hover)]"
                size="lg"
            >
                Make an Offer
            </Button>
        </div>
    </AppLayout>
</template>
