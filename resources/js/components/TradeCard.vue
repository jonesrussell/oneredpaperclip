<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { ref } from 'vue';

import { confirm } from '@/actions/App/Http/Controllers/TradeController';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';

type TradeSummary = {
    id: number;
    status: string;
    position: number;
    offered_item?: {
        id: number;
        title: string;
        image_url?: string | null;
    } | null;
    offerer?: { id: number; name: string } | null;
    owner_confirmed: boolean;
    offerer_confirmed: boolean;
};

const props = defineProps<{
    trade: TradeSummary;
    isOwner: boolean;
    currentUserId: number;
}>();

const showConfirmDialog = ref(false);
const processing = ref(false);

const userHasConfirmed = props.isOwner
    ? props.trade.owner_confirmed
    : props.trade.offerer_confirmed;

const otherPartyName = props.isOwner
    ? (props.trade.offerer?.name ?? 'the offerer')
    : 'the challenge owner';

function confirmTrade() {
    processing.value = true;
    router.post(
        confirm.url({ trade: props.trade.id }),
        {},
        {
            onFinish: () => {
                processing.value = false;
                showConfirmDialog.value = false;
            },
        },
    );
}
</script>

<template>
    <div
        class="rounded-2xl border border-border bg-card p-4 shadow-sm transition-all hover:shadow-md dark:shadow-[var(--shadow-card)]"
    >
        <div class="flex items-center justify-between gap-3">
            <div class="flex items-center gap-3">
                <div
                    class="flex size-12 items-center justify-center overflow-hidden rounded-xl"
                    :class="
                        trade.status === 'completed'
                            ? 'bg-[var(--electric-mint)]/20'
                            : 'bg-[var(--hot-coral)]/20'
                    "
                >
                    <img
                        v-if="trade.offered_item?.image_url"
                        :src="trade.offered_item.image_url"
                        :alt="trade.offered_item?.title ?? 'Trade item'"
                        class="size-12 rounded-xl object-cover"
                    />
                    <span v-else class="text-xl">
                        {{ trade.status === 'completed' ? '✓' : '⏳' }}
                    </span>
                </div>
                <div>
                    <p class="font-display font-semibold text-foreground">
                        Trade #{{ trade.position }}
                    </p>
                    <p class="mt-0.5 text-sm text-muted-foreground">
                        {{ trade.offered_item?.title ?? 'Unknown item' }}
                    </p>
                    <p
                        v-if="trade.offerer"
                        class="mt-0.5 text-xs text-muted-foreground"
                    >
                        with {{ trade.offerer.name }}
                    </p>
                </div>
            </div>

            <!-- Completed -->
            <Badge
                v-if="trade.status === 'completed'"
                variant="secondary"
                class="shrink-0 rounded-full bg-[var(--electric-mint)]/15 text-xs text-[var(--electric-mint)]"
            >
                Completed
            </Badge>

            <!-- Pending: user needs to confirm -->
            <Button
                v-else-if="!userHasConfirmed"
                variant="brand"
                size="sm"
                @click="showConfirmDialog = true"
            >
                Confirm Trade
            </Button>

            <!-- Pending: waiting for other party -->
            <Badge
                v-else
                variant="secondary"
                class="shrink-0 rounded-full bg-[var(--hot-coral)]/15 text-xs text-[var(--hot-coral)]"
            >
                Waiting for {{ otherPartyName }}
            </Badge>
        </div>
    </div>

    <!-- Confirm trade dialog -->
    <Dialog v-model:open="showConfirmDialog">
        <DialogContent class="sm:max-w-sm">
            <DialogHeader>
                <DialogTitle class="font-display">Confirm Trade</DialogTitle>
                <DialogDescription>
                    Confirm that you've completed this trade for
                    <strong>{{ trade.offered_item?.title }}</strong
                    >?
                </DialogDescription>
            </DialogHeader>
            <DialogFooter>
                <Button variant="outline" @click="showConfirmDialog = false">
                    Cancel
                </Button>
                <Button
                    variant="brand"
                    :disabled="processing"
                    @click="confirmTrade"
                >
                    {{ processing ? 'Confirming...' : 'Confirm' }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
