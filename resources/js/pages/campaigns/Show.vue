<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import campaigns from '@/routes/campaigns';
import offers from '@/routes/offers';
import type { BreadcrumbItem } from '@/types';
import { ArrowRight, MessageCircle, User } from 'lucide-vue-next';

type Item = { id: number; role: string; title: string | null; description?: string | null };
type UserRef = { id: number; name: string };
type Offer = {
    id: number;
    message?: string | null;
    from_user?: UserRef | null;
    offered_item?: Item | null;
    for_campaign_item?: Item | null;
};
type Trade = {
    id: number;
    position: number;
    status: string;
    offered_item?: Item | null;
    received_item?: Item | null;
};
type Comment = {
    id: number;
    body: string;
    user?: UserRef | null;
};

const props = defineProps<{
    campaign: {
        id: number;
        title: string | null;
        story: string | null;
        status: string;
        visibility: string;
        user?: UserRef | null;
        category?: { id: number; name: string } | null;
        current_item?: Item | null;
        goal_item?: Item | null;
        items?: Item[];
        offers: Offer[];
        trades: Trade[];
        comments: Comment[];
    };
    isFollowing: boolean;
}>();

const page = usePage();
const authUser = page.props.auth?.user as { id: number } | undefined;
const isOwner = authUser && props.campaign.user && authUser.id === props.campaign.user.id;

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Campaigns', href: campaigns.index().url },
    {
        title: props.campaign.title ?? 'Campaign',
        href: campaigns.show({ campaign: props.campaign.id }).url,
    },
];

const startItem = props.campaign.items?.find((i) => i.role === 'start') ?? null;

function acceptOffer(offerId: number) {
    router.post(offers.accept({ offer: offerId }).url);
}

function declineOffer(offerId: number) {
    router.post(offers.decline({ offer: offerId }).url);
}
</script>

<template>
    <Head :title="campaign.title ?? 'Campaign'" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div
            class="flex h-full flex-1 flex-col gap-8 overflow-x-auto p-4 md:p-6"
        >
            <!-- Header -->
            <div>
                <h1
                    class="font-display text-2xl font-semibold tracking-tight text-[var(--ink)] sm:text-3xl"
                >
                    {{ campaign.title ?? 'Untitled campaign' }}
                </h1>
                <div class="mt-2 flex flex-wrap items-center gap-3 text-sm text-[var(--ink-muted)]">
                    <span v-if="campaign.user?.name" class="flex items-center gap-1">
                        <User class="size-4" />
                        {{ campaign.user.name }}
                    </span>
                    <span v-if="campaign.category?.name">
                        {{ campaign.category.name }}
                    </span>
                    <span class="capitalize">{{ campaign.status }}</span>
                </div>
                <p
                    v-if="campaign.story"
                    class="mt-4 max-w-2xl whitespace-pre-wrap text-[var(--ink-muted)]"
                >
                    {{ campaign.story }}
                </p>
            </div>

            <!-- Journey: Start → Current → Goal -->
            <Card class="border-[var(--ink)]/10">
                <CardHeader>
                    <CardTitle class="font-display text-lg">The journey</CardTitle>
                    <CardDescription class="text-[var(--ink-muted)]">
                        From start item to current item to goal.
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="flex flex-wrap items-stretch gap-2 sm:gap-4">
                        <div
                            class="min-w-0 flex-1 rounded-xl border border-[var(--ink)]/10 bg-[var(--paper)] p-4"
                        >
                            <p class="text-xs font-medium uppercase tracking-wider text-[var(--ink-muted)]">
                                Start
                            </p>
                            <p class="mt-1 font-display font-semibold text-[var(--ink)]">
                                {{ startItem?.title ?? '—' }}
                            </p>
                            <p
                                v-if="startItem?.description"
                                class="mt-1 text-sm text-[var(--ink-muted)]"
                            >
                                {{ startItem.description }}
                            </p>
                        </div>
                        <div class="flex shrink-0 items-center">
                            <ArrowRight
                                class="size-6 text-[var(--ink-muted)]"
                                aria-hidden="true"
                            />
                        </div>
                        <div
                            class="min-w-0 flex-1 rounded-xl border-2 border-[var(--brand-red)]/40 bg-[var(--brand-red)]/5 p-4"
                        >
                            <p class="text-xs font-medium uppercase tracking-wider text-[var(--brand-red)]">
                                Current
                            </p>
                            <p class="mt-1 font-display font-semibold text-[var(--ink)]">
                                {{ campaign.current_item?.title ?? '—' }}
                            </p>
                            <p
                                v-if="campaign.current_item?.description"
                                class="mt-1 text-sm text-[var(--ink-muted)]"
                            >
                                {{ campaign.current_item.description }}
                            </p>
                        </div>
                        <div class="flex shrink-0 items-center">
                            <ArrowRight
                                class="size-6 text-[var(--ink-muted)]"
                                aria-hidden="true"
                            />
                        </div>
                        <div
                            class="min-w-0 flex-1 rounded-xl border border-[var(--ink)]/10 bg-[var(--paper)] p-4"
                        >
                            <p class="text-xs font-medium uppercase tracking-wider text-[var(--ink-muted)]">
                                Goal
                            </p>
                            <p class="mt-1 font-display font-semibold text-[var(--ink)]">
                                {{ campaign.goal_item?.title ?? '—' }}
                            </p>
                            <p
                                v-if="campaign.goal_item?.description"
                                class="mt-1 text-sm text-[var(--ink-muted)]"
                            >
                                {{ campaign.goal_item.description }}
                            </p>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Pending offers -->
            <Card v-if="campaign.offers?.length" class="border-[var(--ink)]/10">
                <CardHeader>
                    <CardTitle class="font-display text-lg">Pending offers</CardTitle>
                    <CardDescription class="text-[var(--ink-muted)]">
                        Offers for your current item. Accept to create a trade.
                    </CardDescription>
                </CardHeader>
                <CardContent class="space-y-4">
                    <div
                        v-for="offer in campaign.offers"
                        :key="offer.id"
                        class="flex flex-col gap-3 rounded-xl border border-[var(--ink)]/10 bg-[var(--paper)] p-4 sm:flex-row sm:items-center sm:justify-between"
                    >
                        <div>
                            <p class="font-medium text-[var(--ink)]">
                                {{ offer.from_user?.name ?? 'Someone' }} offers
                                <strong>{{ offer.offered_item?.title ?? 'an item' }}</strong>
                                for your current item.
                            </p>
                            <p
                                v-if="offer.message"
                                class="mt-1 text-sm text-[var(--ink-muted)]"
                            >
                                {{ offer.message }}
                            </p>
                        </div>
                        <div v-if="isOwner" class="flex shrink-0 gap-2">
                            <Button
                                variant="brand"
                                size="sm"
                                @click="acceptOffer(offer.id)"
                            >
                                Accept
                            </Button>
                            <Button
                                variant="outline"
                                size="sm"
                                class="border-[var(--ink)]/20"
                                @click="declineOffer(offer.id)"
                            >
                                Decline
                            </Button>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Trades history -->
            <Card v-if="campaign.trades?.length" class="border-[var(--ink)]/10">
                <CardHeader>
                    <CardTitle class="font-display text-lg">Trades</CardTitle>
                    <CardDescription class="text-[var(--ink-muted)]">
                        Completed or pending confirmation.
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <ul class="space-y-3">
                        <li
                            v-for="(trade, idx) in campaign.trades"
                            :key="trade.id"
                            class="flex flex-wrap items-center gap-2 rounded-lg border border-[var(--ink)]/10 px-3 py-2 text-sm"
                        >
                            <span class="font-medium text-[var(--ink-muted)]"
                                >#{{ trade.position }}</span
                            >
                            <span>{{ trade.received_item?.title ?? '—' }}</span>
                            <span class="text-[var(--ink-muted)]">→</span>
                            <span>{{ trade.offered_item?.title ?? '—' }}</span>
                            <span
                                class="ml-auto rounded px-2 py-0.5 text-xs font-medium capitalize"
                                :class="
                                    trade.status === 'completed'
                                        ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300'
                                        : 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300'
                                "
                            >
                                {{ trade.status }}
                            </span>
                        </li>
                    </ul>
                </CardContent>
            </Card>

            <!-- Comments -->
            <Card class="border-[var(--ink)]/10">
                <CardHeader>
                    <CardTitle class="font-display text-lg flex items-center gap-2">
                        <MessageCircle class="size-5" />
                        Comments
                    </CardTitle>
                    <CardDescription class="text-[var(--ink-muted)]">
                        {{ campaign.comments?.length ?? 0 }} comment(s)
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <div
                        v-if="!campaign.comments?.length"
                        class="rounded-lg border border-dashed border-[var(--ink)]/20 py-8 text-center text-sm text-[var(--ink-muted)]"
                    >
                        No comments yet.
                    </div>
                    <ul v-else class="space-y-4">
                        <li
                            v-for="comment in campaign.comments"
                            :key="comment.id"
                            class="rounded-lg border border-[var(--ink)]/10 p-3"
                        >
                            <p class="text-sm font-medium text-[var(--ink)]">
                                {{ comment.user?.name ?? 'Anonymous' }}
                            </p>
                            <p class="mt-1 whitespace-pre-wrap text-sm text-[var(--ink-muted)]">
                                {{ comment.body }}
                            </p>
                        </li>
                    </ul>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
