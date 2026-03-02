<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { Heart, Pencil } from 'lucide-vue-next';
import { computed, ref } from 'vue';

import CelebrationOverlay from '@/components/CelebrationOverlay.vue';
import CreateOfferDialog from '@/components/CreateOfferDialog.vue';
import OfferCard from '@/components/OfferCard.vue';
import PaperclipMascot from '@/components/PaperclipMascot.vue';
import ShareDropdown from '@/components/ShareDropdown.vue';
import TradeCard from '@/components/TradeCard.vue';
import TradePathMap from '@/components/TradePathMap.vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { useInitials } from '@/composables/useInitials';
import PublicLayout from '@/layouts/PublicLayout.vue';
import { login } from '@/routes';
import challenges from '@/routes/challenges';
import type {
    ChallengeStatus,
    CommentSummary,
    ItemSummary,
    OfferSummary,
    TradeSummary,
} from '@/types/models';

type Challenge = {
    id: number;
    title?: string | null;
    story?: string | null;
    story_safe?: string;
    status: ChallengeStatus;
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
    start_item?: ItemSummary | null;
    current_item?: ItemSummary | null;
    goal_item?: ItemSummary | null;
    trades?: TradeSummary[];
    offers?: OfferSummary[];
    comments?: CommentSummary[];
    created_at?: string;
};

const props = defineProps<{
    challenge: Challenge;
    isFollowing: boolean;
}>();

const page = usePage();
const { getInitials } = useInitials();

const isOwner = computed(() => {
    const userId = page.props.auth?.user?.id;
    return userId != null && userId === props.challenge.user_id;
});

const currentUser = computed(() => page.props.auth?.user);
const showOfferDialog = ref(false);

const shareUrl = computed(() => {
    if (typeof window === 'undefined') return '';
    return `${window.location.origin}/challenges/${props.challenge.id}`;
});

function handleMakeOffer(): void {
    if (!currentUser.value) {
        router.visit('/login');
        return;
    }
    showOfferDialog.value = true;
}

const activeTab = ref<'story' | 'offers' | 'trades' | 'comments'>('story');

const tabs = computed(() => [
    { key: 'story' as const, label: 'Story', count: null },
    {
        key: 'offers' as const,
        label: 'Offers',
        count: props.challenge.offers?.length ?? 0,
    },
    {
        key: 'trades' as const,
        label: 'Trades',
        count: props.challenge.trades?.length ?? 0,
    },
    {
        key: 'comments' as const,
        label: 'Comments',
        count: props.challenge.comments?.length ?? 0,
    },
]);

const tradesCompleted = computed(
    () =>
        props.challenge.trades?.filter((t) => t.status === 'completed')
            .length ?? 0,
);

const daysActive = computed(() => {
    if (!props.challenge.created_at) return 0;
    const created = new Date(props.challenge.created_at);
    const now = new Date();
    return Math.ceil(
        (now.getTime() - created.getTime()) / (1000 * 60 * 60 * 24),
    );
});

const ownerStats = computed(() => ({
    xp: props.challenge.user?.xp ?? 0,
    level: props.challenge.user?.level ?? 1,
    levelProgress: 35,
    xpForNextLevel: 500,
    currentStreak: props.challenge.user?.current_streak ?? 0,
    longestStreak: props.challenge.user?.longest_streak ?? 0,
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
    const trades = props.challenge.trades ?? [];
    const completedTrades = trades.filter((t) => t.status === 'completed');
    const hasCurrentTrade = trades.some(
        (t) => t.status === 'pending_confirmation',
    );

    nodes.push({
        id: 'start',
        type: 'start',
        status: 'completed',
        title: props.challenge.start_item?.title ?? 'Start Item',
        imageUrl: props.challenge.start_item?.image_url,
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
        props.challenge.status === 'completed'
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
        title: props.challenge.goal_item?.title ?? 'Goal Item',
        imageUrl: props.challenge.goal_item?.image_url,
    });

    return nodes;
});

const mascotMood = computed(() => {
    if (props.challenge.status === 'completed') return 'celebrating';
    if (tradesCompleted.value > 0) return 'encouraging';
    return 'happy';
});

const showCelebration = ref(false);
const celebrationType = ref<'xp' | 'level-up' | 'trade' | 'challenge-complete'>(
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
        :title="`${challenge.title ?? 'Challenge'} — ${page.props.name ?? 'One Red Paperclip'}`"
    />

    <PublicLayout>
        <div class="bg-background">
            <div class="mx-auto w-full max-w-6xl p-4 sm:p-6">
                <!-- Hero Section -->
                <div
                    class="overflow-hidden rounded-2xl border border-border bg-muted/50"
                >
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
                                    <Button
                                        variant="outline"
                                        class="rounded-full"
                                    >
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
                                        v-if="challenge.user?.avatar"
                                        :src="challenge.user.avatar"
                                        :alt="
                                            challenge.user?.name ??
                                            'Challenge owner'
                                        "
                                    />
                                    <AvatarFallback
                                        class="rounded-full bg-[var(--sky-blue)]/20 text-[var(--sky-blue)]"
                                    >
                                        {{
                                            getInitials(
                                                challenge.user?.name ??
                                                    'Anonymous',
                                            )
                                        }}
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
                        <div
                            class="mt-5 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
                        >
                            <!-- Stats pills -->
                            <div
                                class="grid grid-cols-2 gap-2 sm:flex sm:gap-3"
                            >
                                <div
                                    class="flex items-center gap-2 rounded-xl bg-card p-3"
                                >
                                    <span class="text-lg">🏆</span>
                                    <div>
                                        <div
                                            class="font-mono text-sm font-bold text-foreground"
                                        >
                                            {{ ownerStats.level }}
                                        </div>
                                        <div
                                            class="text-xs text-muted-foreground"
                                        >
                                            Level
                                        </div>
                                    </div>
                                </div>
                                <div
                                    class="flex items-center gap-2 rounded-xl bg-card p-3"
                                >
                                    <span class="text-lg">🔥</span>
                                    <div>
                                        <div
                                            class="font-mono text-sm font-bold text-[var(--hot-coral)]"
                                        >
                                            {{ ownerStats.currentStreak }}
                                        </div>
                                        <div
                                            class="text-xs text-muted-foreground"
                                        >
                                            Streak
                                        </div>
                                    </div>
                                </div>
                                <div
                                    class="flex items-center gap-2 rounded-xl bg-card p-3"
                                >
                                    <span class="text-lg">🔄</span>
                                    <div>
                                        <div
                                            class="font-mono text-sm font-bold text-[var(--electric-mint)]"
                                        >
                                            {{ ownerStats.tradesCompleted }}
                                        </div>
                                        <div
                                            class="text-xs text-muted-foreground"
                                        >
                                            Trades
                                        </div>
                                    </div>
                                </div>
                                <div
                                    class="flex items-center gap-2 rounded-xl bg-card p-3"
                                >
                                    <span class="text-lg">📅</span>
                                    <div>
                                        <div
                                            class="font-mono text-sm font-bold text-[var(--sky-blue)]"
                                        >
                                            {{ ownerStats.daysActive }}
                                        </div>
                                        <div
                                            class="text-xs text-muted-foreground"
                                        >
                                            Days
                                        </div>
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

                <!-- Trade Journey -->
                <div class="mt-6">
                    <div class="mb-4 flex items-center justify-between">
                        <h2
                            class="font-display text-lg font-semibold text-foreground"
                        >
                            Trade Journey
                        </h2>
                        <PaperclipMascot :mood="mascotMood" :size="48" />
                    </div>
                    <div
                        class="rounded-3xl border border-border bg-card/50 p-6 backdrop-blur-sm"
                    >
                        <TradePathMap :nodes="pathNodes" />
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
                            v-if="challenge.story || challenge.story_safe"
                            class="prose prose-sm dark:prose-invert max-w-none rounded-2xl border border-border bg-card p-6 text-foreground shadow-sm"
                            v-html="challenge.story_safe ?? ''"
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
                            v-if="!challenge.offers?.length"
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
                        <OfferCard
                            v-for="offer in challenge.offers"
                            v-else
                            :key="offer.id"
                            :offer="offer"
                            :is-owner="isOwner"
                        />
                    </div>

                    <div v-show="activeTab === 'trades'" class="space-y-3">
                        <div
                            v-if="!challenge.trades?.length"
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
                        <TradeCard
                            v-for="trade in challenge.trades"
                            v-else
                            :key="trade.id"
                            :trade="trade"
                            :is-owner="isOwner"
                            :current-user-id="currentUser?.id ?? 0"
                        />
                    </div>

                    <div v-show="activeTab === 'comments'" class="space-y-3">
                        <div
                            v-if="!challenge.comments?.length"
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
                            v-for="comment in challenge.comments"
                            v-else
                            :key="comment.id"
                            class="rounded-2xl border border-border bg-card p-4 shadow-sm"
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
                variant="brand"
                class="w-full"
                size="lg"
                @click="handleMakeOffer"
            >
                Make an Offer
            </Button>
        </div>

        <!-- Create offer dialog -->
        <CreateOfferDialog
            :challenge-id="challenge.id"
            :current-item-title="challenge.current_item?.title ?? 'this item'"
            v-model:open="showOfferDialog"
        />

        <!-- Celebration overlay -->
        <CelebrationOverlay
            :show="showCelebration"
            :type="celebrationType"
            :xp-gained="celebrationXp"
            @close="showCelebration = false"
        />
    </PublicLayout>
</template>
