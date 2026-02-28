<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { Check, X } from 'lucide-vue-next';
import { ref } from 'vue';

import { accept, decline } from '@/actions/App/Http/Controllers/OfferController';
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

type OfferSummary = {
    id: number;
    status: string;
    message?: string | null;
    from_user?: { id: number; name: string } | null;
    offered_item?: {
        id: number;
        title: string;
        image_url?: string | null;
    } | null;
};

const props = defineProps<{
    offer: OfferSummary;
    isOwner: boolean;
}>();

const showAcceptDialog = ref(false);
const showDeclineDialog = ref(false);
const processing = ref(false);

function acceptOffer() {
    processing.value = true;
    router.post(accept.url({ offer: props.offer.id }), {}, {
        onFinish: () => {
            processing.value = false;
            showAcceptDialog.value = false;
        },
    });
}

function declineOffer() {
    processing.value = true;
    router.post(decline.url({ offer: props.offer.id }), {}, {
        onFinish: () => {
            processing.value = false;
            showDeclineDialog.value = false;
        },
    });
}
</script>

<template>
    <div
        class="rounded-2xl border border-border bg-card p-4 shadow-sm transition-all hover:shadow-md dark:shadow-[var(--shadow-card)]"
    >
        <div class="flex items-start justify-between gap-3">
            <div class="flex items-start gap-3">
                <div
                    class="flex size-12 shrink-0 items-center justify-center overflow-hidden rounded-xl bg-[var(--sunny-yellow)]/20"
                >
                    <img
                        v-if="offer.offered_item?.image_url"
                        :src="offer.offered_item.image_url"
                        :alt="offer.offered_item?.title ?? 'Offered item'"
                        class="size-12 rounded-xl object-cover"
                    />
                    <span v-else class="text-xl">📦</span>
                </div>
                <div>
                    <p class="font-display font-semibold text-foreground">
                        {{ offer.offered_item?.title ?? 'Unknown item' }}
                    </p>
                    <p class="mt-0.5 text-xs text-muted-foreground">
                        from {{ offer.from_user?.name ?? 'Anonymous' }}
                    </p>
                    <p
                        v-if="offer.message"
                        class="mt-2 text-sm text-muted-foreground"
                    >
                        "{{ offer.message }}"
                    </p>
                </div>
            </div>

            <!-- Owner actions for pending offers -->
            <div
                v-if="isOwner && offer.status === 'pending'"
                class="flex shrink-0 items-center gap-1.5"
            >
                <Button
                    variant="ghost"
                    size="icon"
                    class="size-8 text-destructive hover:bg-destructive/10"
                    @click="showDeclineDialog = true"
                >
                    <X class="size-4" />
                </Button>
                <Button
                    variant="ghost"
                    size="icon"
                    class="size-8 text-[var(--electric-mint)] hover:bg-[var(--electric-mint)]/10"
                    @click="showAcceptDialog = true"
                >
                    <Check class="size-4" />
                </Button>
            </div>

            <!-- Status badge for non-actionable offers -->
            <Badge
                v-else
                variant="secondary"
                class="shrink-0 rounded-full text-xs capitalize"
            >
                {{ offer.status }}
            </Badge>
        </div>
    </div>

    <!-- Accept confirmation dialog -->
    <Dialog v-model:open="showAcceptDialog">
        <DialogContent class="sm:max-w-sm">
            <DialogHeader>
                <DialogTitle class="font-display">Accept Offer</DialogTitle>
                <DialogDescription>
                    Accept
                    <strong>{{ offer.offered_item?.title }}</strong>
                    from
                    <strong>{{ offer.from_user?.name }}</strong>?
                    This will create a trade.
                </DialogDescription>
            </DialogHeader>
            <DialogFooter>
                <Button
                    variant="outline"
                    @click="showAcceptDialog = false"
                >
                    Cancel
                </Button>
                <Button
                    variant="brand"
                    :disabled="processing"
                    @click="acceptOffer"
                >
                    {{ processing ? 'Accepting...' : 'Accept Offer' }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>

    <!-- Decline confirmation dialog -->
    <Dialog v-model:open="showDeclineDialog">
        <DialogContent class="sm:max-w-sm">
            <DialogHeader>
                <DialogTitle class="font-display">Decline Offer</DialogTitle>
                <DialogDescription>
                    Decline this offer from
                    <strong>{{ offer.from_user?.name }}</strong>?
                </DialogDescription>
            </DialogHeader>
            <DialogFooter>
                <Button
                    variant="outline"
                    @click="showDeclineDialog = false"
                >
                    Cancel
                </Button>
                <Button
                    variant="destructive"
                    :disabled="processing"
                    @click="declineOffer"
                >
                    {{ processing ? 'Declining...' : 'Decline' }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
