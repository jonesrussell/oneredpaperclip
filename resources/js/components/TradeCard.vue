<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { Pencil } from 'lucide-vue-next';
import { computed, ref } from 'vue';

import EditTradeDialog from '@/components/EditTradeDialog.vue';
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
import type { TradeSummary } from '@/types/models';
import { confirm } from '@/actions/App/Http/Controllers/TradeController';

const props = defineProps<{
    trade: TradeSummary;
    isOwner: boolean;
    currentUserId: number;
}>();

const showConfirmDialog = ref(false);
const showEditDialog = ref(false);
const processing = ref(false);

const isOfferer = computed(
    () => props.trade.offerer?.id === props.currentUserId,
);

const canConfirm = computed(() => {
    if (props.isOwner) return !props.trade.owner_confirmed;
    if (isOfferer.value) return !props.trade.offerer_confirmed;
    return false;
});

const canEdit = computed(
    () => props.isOwner && props.trade.status === 'pending_confirmation',
);

function confirmTrade() {
    processing.value = true;
    router.post(
        confirm.url({ trade: props.trade.id }),
        {},
        {
            onSuccess: () => {
                showConfirmDialog.value = false;
            },
            onFinish: () => {
                processing.value = false;
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

            <!-- Pending: owner can edit and mark complete -->
            <div
                v-else-if="isOwner && canConfirm"
                class="flex items-center gap-2"
            >
                <Button
                    variant="ghost"
                    size="sm"
                    class="h-8 w-8 p-0"
                    title="Edit trade item"
                    @click="showEditDialog = true"
                >
                    <Pencil class="size-4" />
                </Button>
                <Button
                    variant="brand"
                    size="sm"
                    @click="showConfirmDialog = true"
                >
                    Mark Complete
                </Button>
            </div>

            <!-- Pending: offerer needs to confirm -->
            <Button
                v-else-if="canConfirm"
                variant="brand"
                size="sm"
                @click="showConfirmDialog = true"
            >
                Confirm Trade
            </Button>

            <!-- Pending: offerer waiting for owner -->
            <Badge
                v-else-if="isOfferer"
                variant="secondary"
                class="shrink-0 rounded-full bg-[var(--hot-coral)]/15 text-xs text-[var(--hot-coral)]"
            >
                Waiting for the challenge owner
            </Badge>

            <!-- Third-party viewer -->
            <Badge
                v-else
                variant="secondary"
                class="shrink-0 rounded-full text-xs"
            >
                Pending
            </Badge>
        </div>
    </div>

    <!-- Confirm trade dialog -->
    <Dialog v-model:open="showConfirmDialog">
        <DialogContent class="sm:max-w-sm">
            <DialogHeader>
                <DialogTitle class="font-display">
                    {{ isOwner ? 'Mark Trade Complete' : 'Confirm Trade' }}
                </DialogTitle>
                <DialogDescription>
                    <template v-if="isOwner">
                        Mark this trade as complete? This will advance your
                        challenge to
                        <strong>{{ trade.offered_item?.title }}</strong
                        >.
                    </template>
                    <template v-else>
                        Confirm that you've completed this trade for
                        <strong>{{ trade.offered_item?.title }}</strong
                        >?
                    </template>
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
                    {{
                        processing
                            ? 'Confirming...'
                            : isOwner
                              ? 'Complete'
                              : 'Confirm'
                    }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>

    <!-- Edit trade dialog -->
    <EditTradeDialog
        v-if="canEdit"
        :trade="trade"
        :open="showEditDialog"
        @update:open="showEditDialog = $event"
    />
</template>
