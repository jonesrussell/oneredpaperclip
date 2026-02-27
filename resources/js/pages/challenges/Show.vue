<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { Heart, Pencil, Share2 } from 'lucide-vue-next';
import { computed, ref } from 'vue';

import CelebrationOverlay from '@/components/CelebrationOverlay.vue';
import PaperclipMascot from '@/components/PaperclipMascot.vue';
import StatsPanel from '@/components/StatsPanel.vue';
import TradePathMap from '@/components/TradePathMap.vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { useInitials } from '@/composables/useInitials';
import AppLayout from '@/layouts/AppLayout.vue';
import campaigns from '@/routes/campaigns';
import type { BreadcrumbItem } from '@/types';

type ItemSummary = {
    id: number;
    title: string;
    image_url?: string | null;
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
    story_safe?: string;
    status: string;
    user_id?: number;
    user?: {
        id: number;
        name: string;
        avatar?: string | null;
        xp?: number;
        level?: number;
        current_streak?: number;
        longest_streak?: number;
    } | null;
    category?: { id: number; name: string } | null;
    current_item?: ItemSummary | null;
    goal_item?: ItemSummary | null;
    trades?: TradeSummary[];
    offers?: OfferSummary[];
    comments?: CommentSummary[];
    created_at?: string;
};

const props = defineProps<{
    campaign: Campaign;
    isFollowing: boolean;
}>();

const page = usePage();
const { getInitials } = useInitials();

const isOwner = computed(() => {
    const userId = page.props.auth?.user?.id;
    return userId != null && userId === props.campaign.user_id;
});

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Challenges', href: '/campaigns' },
    {
        title: props.campaign.title ?? 'Challenge',
        href: `/campaigns/${props.campaign.id}`,
    },
];

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

const tradesCompleted = computed(
    () =>
        props.campaign.trades?.filter((t) => t.status === 'completed').length ??
        0,
);

const daysActive = computed(() => {
    if (!props.campaign.created_at) return 0;
    const created = new Date(props.campaign.created_at);
    const now = new Date();
    return Math.ceil(
        (now.getTime() - created.getTime()) / (1000 * 60 * 60 * 24),
    );
});

const ownerStats = computed(() => ({
    xp: props.campaign.user?.xp ?? 0,
    level: props.campaign.user?.level ?? 1,
    levelProgress: 35,
    xpForNextLevel: 500,
    currentStreak: props.campaign.user?.current_streak ?? 0,
    longestStreak: props.campaign.user?.longest_streak ?? 0,
    tradesCompleted: tradesCompleted.value,
    daysActive: daysActive.value,
}));

type PathNode = {
    id: string | number;
    type: 'start' | 'trade' | 'goal';
    status: 'completed' | 'current' | 'locked';
    title: string;
    subtitle?: string;
    imageUrl?: string | null;
};

const pathNodes = computed<PathNode[]>(() => {
    const nodes: PathNode[] = [];
    const trades = props.campaign.trades ?? [];
    const completedTrades = trades.filter((t) => t.status === 'completed');
    const hasCurrentTrade = trades.some(
        (t) => t.status === 'pending_confirmation',
    );

    nodes.push({
        id: 'start',
        type: 'start',
        status: 'completed',
        title: props.campaign.current_item?.title ?? 'Start Item',
        imageUrl: props.campaign.current_item?.image_url,
    });

    completedTrades.forEach((trade, i) => {
        nodes.push({
            id: trade.id,
            type: 'trade',
            status: 'completed',
            title: trade.offered_item?.title ?? `Trade ${i + 1}`,
            subtitle: `Trade #${trade.position}`,
            imageUrl: trade.offered_item?.image_url,
        });
    });

    if (hasCurrentTrade) {
        const currentTrade = trades.find(
            (t) => t.status === 'pending_confirmation',
        );
        if (currentTrade) {
            nodes.push({
                id: currentTrade.id,
                type: 'trade',
                status: 'current',
                title:
                    currentTrade.offered_item?.title ??
                    `Trade ${completedTrades.length + 1}`,
                subtitle: 'In Progress',
                imageUrl: currentTrade.offered_item?.image_url,
            });
        }
    }

    const futureTrades = Math.max(0, 3 - nodes.length);
    for (let i = 0; i < futureTrades; i++) {
        nodes.push({
            id: `future-${i}`,
            type: 'trade',
            status: 'locked',
            title: '???',
            subtitle: 'Future trade',
        });
    }

    const goalStatus =
        props.campaign.status === 'completed'
            ? 'completed'
            : nodes.some((n) => n.status === 'current')
              ? 'locked'
              : nodes.length > 1
                ? 'current'
                : 'locked';

    nodes.push({
        id: 'goal',
        type: 'goal',
        status: goalStatus,
        title: props.campaign.goal_item?.title ?? 'Goal Item',
        imageUrl: props.campaign.goal_item?.image_url,
    });

    return nodes;
});

const mascotMood = computed(() => {
    if (props.campaign.status === 'completed') return 'celebrating';
    if (tradesCompleted.value > 0) return 'encouraging';
    return 'happy';
});

const showCelebration = ref(false);
const celebrationType = ref<'xp' | 'level-up' | 'trade' | 'campaign-complete'>(
    'xp',
);
const celebrationXp = ref(0);

function getStatusClasses(status: string): string {
    switch (status) {
        case 'active':
            return 'bg-[var(--electric-mint)]/10 text-emerald-700 dark:text-[var(--electric-mint)]';
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
    <Head
        :title="`${campaign.title ?? 'Challenge'} — ${page.props.name ?? 'One Red Paperclip'}`"
    />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="min-h-full bg-background">
            <div class="mx-auto w-full max-w-6xl p-4 sm:p-6">
                <!-- Header -->
                <div class="relative mb-6">
                    <div
                        class="pointer-events-none absolute -top-4 -right-8 -left-8 h-40 rounded-b-3xl bg-gradient-to-br from-[var(--hot-coral)]/15 via-[var(--sunny-yellow)]/10 to-[var(--electric-mint)]/10"
                        aria-hidden="true"
                    />
                    <div class="relative flex items-start justify-between gap-4">
                        <div class="flex-1">
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
                                class="mt-3 font-display text-3xl font-bold tracking-tight text-foreground sm:text-4xl"
                            >
                                {{ campaign.title ?? 'Untitled challenge' }}
                            </h1>
                            <div
                                class="mt-3 flex flex-wrap items-center gap-3 text-sm text-muted-foreground"
                            >
                                <Link
                                    href="#"
                                    class="flex items-center gap-2 transition-colors hover:text-foreground"
                                >
                                    <Avatar
                                        class="size-8 shrink-0 overflow-hidden rounded-full ring-2 ring-[var(--electric-mint)]/30"
                                    >
                                        <AvatarImage
                                            v-if="campaign.user?.avatar"
                                            :src="campaign.user.avatar"
                                            :alt="
                                                campaign.user?.name ??
                                                'Challenge owner'
                                            "
                                        />
                                        <AvatarFallback
                                            class="rounded-full bg-[var(--sky-blue)]/20 text-[var(--sky-blue)]"
                                        >
                                            {{
                                                getInitials(
                                                    campaign.user?.name ??
                                                        'Anonymous',
                                                )
                                            }}
                                        </AvatarFallback>
                                    </Avatar>
                                    <span class="font-medium">
                                        {{
                                            campaign.user?.name ?? 'Anonymous'
                                        }}
                                    </span>
                                    <Badge
                                        v-if="campaign.user?.level"
                                        class="rounded-full bg-gradient-to-r from-violet-500 to-purple-600 px-2 py-0.5 text-[10px] text-white"
                                    >
                                        Lvl {{ campaign.user.level }}
                                    </Badge>
                                </Link>
                            </div>
                        </div>

                        <!-- Action buttons -->
                        <div class="flex items-center gap-2">
                            <Button
                                variant="outline"
                                size="icon"
                                class="rounded-full"
                            >
                                <Heart class="size-4" />
                            </Button>
                            <Button
                                variant="outline"
                                size="icon"
                                class="rounded-full"
                            >
                                <Share2 class="size-4" />
                            </Button>
                            <Link
                                v-if="isOwner"
                                :href="
                                    campaigns.edit.url({
                                        campaign: campaign.id,
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
                </div>

                <!-- Main content: Path Map + Stats sidebar -->
                <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                    <!-- Trade Path Map (main area) -->
                    <div
                        class="lg:col-span-2 rounded-3xl border border-border bg-card/50 p-6 backdrop-blur-sm dark:shadow-[var(--shadow-card)]"
                    >
                        <div class="mb-4 flex items-center justify-between">
                            <h2
                                class="font-display text-lg font-semibold text-foreground"
                            >
                                Trade Journey
                            </h2>
                            <PaperclipMascot
                                :mood="mascotMood"
                                :size="48"
                                class="hidden sm:block"
                            />
                        </div>

                        <TradePathMap :nodes="pathNodes" />
                    </div>

                    <!-- Stats Panel (sidebar) -->
                    <div class="space-y-4">
                        <StatsPanel :stats="ownerStats" />

                        <!-- Make an Offer CTA -->
                        <div
                            v-if="!isOwner"
                            class="rounded-2xl border border-[var(--brand-red)]/20 bg-gradient-to-br from-[var(--brand-red)]/5 to-[var(--hot-coral)]/5 p-4"
                        >
                            <p
                                class="mb-3 text-center text-sm text-muted-foreground"
                            >
                                Have something to trade?
                            </p>
                            <Button
                                class="w-full bg-[var(--brand-red)] text-white shadow-lg shadow-[var(--brand-red)]/25 hover:bg-[var(--brand-red-hover)] hover:-translate-y-0.5 transition-all"
                                size="lg"
                            >
                                Make an Offer
                            </Button>
                        </div>

                        <!-- Compact mascot for mobile -->
                        <div
                            class="flex justify-center rounded-2xl border border-border bg-card/50 p-4 sm:hidden"
                        >
                            <PaperclipMascot :mood="mascotMood" :size="64" />
                        </div>
                    </div>
                </div>

                <!-- Tab bar -->
                <div
                    class="mt-6 flex gap-1 rounded-2xl border border-border bg-muted/30 p-1.5"
                >
                    <button
                        v-for="tab in tabs"
                        :key="tab.key"
                        class="flex-1 rounded-xl px-4 py-2.5 text-sm font-medium transition-all"
                        :class="
                            activeTab === tab.key
                                ? 'bg-card text-foreground shadow-sm'
                                : 'text-muted-foreground hover:bg-card/50 hover:text-foreground'
                        "
                        @click="activeTab = tab.key"
                    >
                        {{ tab.label }}
                        <Badge
                            v-if="tab.count"
                            variant="secondary"
                            class="ml-1.5 rounded-full border-0 px-2 py-0.5 text-xs"
                            :class="
                                activeTab === tab.key
                                    ? 'bg-[var(--hot-coral)]/15 text-[var(--hot-coral)]'
                                    : ''
                            "
                        >
                            {{ tab.count }}
                        </Badge>
                    </button>
                </div>

                <!-- Tab content -->
                <div class="mt-4">
                    <div v-show="activeTab === 'story'" class="space-y-4">
                        <div
                            v-if="campaign.story || campaign.story_safe"
                            class="prose prose-sm dark:prose-invert max-w-none rounded-2xl border border-border bg-card p-6 text-foreground shadow-sm dark:shadow-[var(--shadow-card)]"
                            v-html="campaign.story_safe ?? ''"
                        />
                        <div
                            v-else
                            class="flex flex-col items-center justify-center rounded-2xl border border-dashed border-border bg-card/60 py-16 text-center"
                        >
                            <PaperclipMascot mood="thinking" :size="64" />
                            <p class="mt-4 text-sm text-muted-foreground">
                                No story yet.
                            </p>
                        </div>
                    </div>

                    <div v-show="activeTab === 'offers'" class="space-y-3">
                        <div
                            v-if="!campaign.offers?.length"
                            class="flex flex-col items-center justify-center rounded-2xl border border-dashed border-border bg-card/60 py-16 text-center"
                        >
                            <PaperclipMascot mood="encouraging" :size="64" />
                            <p class="mt-4 text-sm text-muted-foreground">
                                No pending offers.
                            </p>
                            <p class="mt-1 text-xs text-muted-foreground">
                                Be the first to make an offer!
                            </p>
                        </div>
                        <div
                            v-for="offer in campaign.offers"
                            v-else
                            :key="offer.id"
                            class="rounded-2xl border border-border bg-card p-4 shadow-sm transition-all hover:shadow-md dark:shadow-[var(--shadow-card)]"
                        >
                            <div class="flex items-start justify-between gap-3">
                                <div class="flex items-start gap-3">
                                    <div
                                        class="flex size-12 items-center justify-center rounded-xl bg-[var(--sunny-yellow)]/20"
                                    >
                                        <span class="text-xl">📦</span>
                                    </div>
                                    <div>
                                        <p
                                            class="font-display font-semibold text-foreground"
                                        >
                                            {{
                                                offer.offered_item?.title ??
                                                'Unknown item'
                                            }}
                                        </p>
                                        <p
                                            class="mt-0.5 text-xs text-muted-foreground"
                                        >
                                            from
                                            {{
                                                offer.from_user?.name ??
                                                'Anonymous'
                                            }}
                                        </p>
                                        <p
                                            v-if="offer.message"
                                            class="mt-2 text-sm text-muted-foreground"
                                        >
                                            "{{ offer.message }}"
                                        </p>
                                    </div>
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
                            class="flex flex-col items-center justify-center rounded-2xl border border-dashed border-border bg-card/60 py-16 text-center"
                        >
                            <PaperclipMascot mood="happy" :size="64" />
                            <p class="mt-4 text-sm text-muted-foreground">
                                No trades yet.
                            </p>
                            <p class="mt-1 text-xs text-muted-foreground">
                                The journey begins with a single trade!
                            </p>
                        </div>
                        <div
                            v-for="trade in campaign.trades"
                            v-else
                            :key="trade.id"
                            class="rounded-2xl border border-border bg-card p-4 shadow-sm transition-all hover:shadow-md dark:shadow-[var(--shadow-card)]"
                        >
                            <div class="flex items-center justify-between gap-3">
                                <div class="flex items-center gap-3">
                                    <div
                                        class="flex size-12 items-center justify-center rounded-xl"
                                        :class="
                                            trade.status === 'completed'
                                                ? 'bg-[var(--electric-mint)]/20'
                                                : 'bg-[var(--hot-coral)]/20'
                                        "
                                    >
                                        <span class="text-xl">
                                            {{
                                                trade.status === 'completed'
                                                    ? '✓'
                                                    : '⏳'
                                            }}
                                        </span>
                                    </div>
                                    <div>
                                        <p
                                            class="font-display font-semibold text-foreground"
                                        >
                                            Trade #{{ trade.position }}
                                        </p>
                                        <p
                                            class="mt-0.5 text-sm text-muted-foreground"
                                        >
                                            {{
                                                trade.offered_item?.title ??
                                                'Unknown item'
                                            }}
                                        </p>
                                    </div>
                                </div>
                                <Badge
                                    variant="secondary"
                                    class="shrink-0 rounded-full text-xs capitalize"
                                    :class="
                                        trade.status === 'completed'
                                            ? 'bg-[var(--electric-mint)]/15 text-[var(--electric-mint)]'
                                            : 'bg-[var(--hot-coral)]/15 text-[var(--hot-coral)]'
                                    "
                                >
                                    {{
                                        trade.status === 'pending_confirmation'
                                            ? 'Pending'
                                            : trade.status
                                    }}
                                </Badge>
                            </div>
                        </div>
                    </div>

                    <div v-show="activeTab === 'comments'" class="space-y-3">
                        <div
                            v-if="!campaign.comments?.length"
                            class="flex flex-col items-center justify-center rounded-2xl border border-dashed border-border bg-card/60 py-16 text-center"
                        >
                            <PaperclipMascot mood="thinking" :size="64" />
                            <p class="mt-4 text-sm text-muted-foreground">
                                No comments yet.
                            </p>
                            <p class="mt-1 text-xs text-muted-foreground">
                                Start the conversation!
                            </p>
                        </div>
                        <div
                            v-for="comment in campaign.comments"
                            v-else
                            :key="comment.id"
                            class="rounded-2xl border border-border bg-card p-4 shadow-sm dark:shadow-[var(--shadow-card)]"
                        >
                            <div class="flex items-start gap-3">
                                <Avatar
                                    class="size-10 shrink-0 overflow-hidden rounded-full"
                                >
                                    <AvatarFallback
                                        class="rounded-full bg-[var(--sky-blue)]/20 text-sm font-semibold text-[var(--sky-blue)]"
                                    >
                                        {{
                                            getInitials(
                                                comment.user?.name ??
                                                    'Anonymous',
                                            )
                                        }}
                                    </AvatarFallback>
                                </Avatar>
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-center gap-2">
                                        <span
                                            class="font-display text-sm font-semibold text-foreground"
                                        >
                                            {{
                                                comment.user?.name ??
                                                'Anonymous'
                                            }}
                                        </span>
                                        <span
                                            class="text-xs text-muted-foreground"
                                        >
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
        </div>

        <!-- Mobile sticky CTA -->
        <div
            v-if="!isOwner"
            class="fixed inset-x-0 bottom-16 z-40 border-t border-border bg-background/95 p-3 backdrop-blur-md lg:hidden"
        >
            <Button
                class="w-full bg-[var(--brand-red)] text-white shadow-lg shadow-[var(--brand-red)]/25 hover:bg-[var(--brand-red-hover)]"
                size="lg"
            >
                Make an Offer
            </Button>
        </div>

        <!-- Celebration overlay -->
        <CelebrationOverlay
            :show="showCelebration"
            :type="celebrationType"
            :xp-gained="celebrationXp"
            @close="showCelebration = false"
        />
    </AppLayout>
</template>
