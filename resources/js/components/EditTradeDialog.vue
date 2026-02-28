<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { ImagePlus, X } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { TradeSummary } from '@/types/models';
import { update } from '@/actions/App/Http/Controllers/TradeController';

const props = defineProps<{
    trade: TradeSummary;
    open: boolean;
}>();

const emit = defineEmits<{
    'update:open': [value: boolean];
}>();

const isOpen = computed({
    get: () => props.open,
    set: (val) => emit('update:open', val),
});

const form = useForm({
    title: props.trade.offered_item?.title ?? '',
    description: props.trade.offered_item?.description ?? '',
    image: null as File | null,
});

const imagePreview = ref<string | null>(
    props.trade.offered_item?.image_url ?? null,
);
const fileInput = ref<HTMLInputElement | null>(null);
const hasNewImage = ref(false);

function onFileChange(event: Event) {
    const target = event.target as HTMLInputElement;
    const file = target.files?.[0] ?? null;
    form.image = file;
    hasNewImage.value = true;
    if (file) {
        const reader = new FileReader();
        reader.onload = (e) => {
            imagePreview.value = e.target?.result as string;
        };
        reader.readAsDataURL(file);
    } else {
        imagePreview.value = null;
    }
}

function removeImage() {
    form.image = null;
    imagePreview.value = null;
    hasNewImage.value = false;
    if (fileInput.value) {
        fileInput.value.value = '';
    }
}

function submit() {
    form.transform((data) => ({
        ...data,
        _method: 'PATCH',
    })).post(update.url({ trade: props.trade.id }), {
        forceFormData: true,
        onSuccess: () => {
            isOpen.value = false;
        },
    });
}

watch(
    () => props.trade,
    (newTrade) => {
        form.title = newTrade.offered_item?.title ?? '';
        form.description = newTrade.offered_item?.description ?? '';
        form.image = null;
        imagePreview.value = newTrade.offered_item?.image_url ?? null;
        hasNewImage.value = false;
    },
);

watch(isOpen, (val) => {
    if (!val) {
        form.clearErrors();
    }
});
</script>

<template>
    <Dialog v-model:open="isOpen">
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle class="font-display">Edit Trade Item</DialogTitle>
                <DialogDescription>
                    Update the item details for Trade #{{ trade.position }}
                </DialogDescription>
            </DialogHeader>

            <form class="space-y-4" @submit.prevent="submit">
                <div class="space-y-2">
                    <Label for="edit-title">Item Title</Label>
                    <Input
                        id="edit-title"
                        v-model="form.title"
                        placeholder="e.g. A vintage typewriter"
                        required
                    />
                    <p v-if="form.errors.title" class="text-sm text-destructive">
                        {{ form.errors.title }}
                    </p>
                </div>

                <div class="space-y-2">
                    <Label for="edit-description">
                        Description
                        <span class="text-muted-foreground">(optional)</span>
                    </Label>
                    <textarea
                        id="edit-description"
                        v-model="form.description"
                        class="flex min-h-[80px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                        placeholder="Describe the item..."
                    />
                    <p
                        v-if="form.errors.description"
                        class="text-sm text-destructive"
                    >
                        {{ form.errors.description }}
                    </p>
                </div>

                <div class="space-y-2">
                    <Label>Photo</Label>
                    <div v-if="imagePreview" class="relative inline-block">
                        <img
                            :src="imagePreview"
                            alt="Preview"
                            class="h-24 w-24 rounded-lg object-cover"
                        />
                        <button
                            type="button"
                            class="absolute -top-2 -right-2 rounded-full bg-destructive p-1 text-white shadow-sm"
                            @click="removeImage"
                        >
                            <X class="size-3" />
                        </button>
                    </div>
                    <div v-else>
                        <button
                            type="button"
                            class="flex h-24 w-24 items-center justify-center rounded-lg border-2 border-dashed border-border text-muted-foreground transition-colors hover:border-foreground hover:text-foreground"
                            @click="fileInput?.click()"
                        >
                            <ImagePlus class="size-6" />
                        </button>
                    </div>
                    <input
                        ref="fileInput"
                        type="file"
                        accept="image/*"
                        class="hidden"
                        @change="onFileChange"
                    />
                    <p v-if="form.errors.image" class="text-sm text-destructive">
                        {{ form.errors.image }}
                    </p>
                </div>

                <DialogFooter>
                    <Button type="button" variant="outline" @click="isOpen = false">
                        Cancel
                    </Button>
                    <Button
                        type="submit"
                        variant="brand"
                        :disabled="form.processing"
                    >
                        {{ form.processing ? 'Saving...' : 'Save Changes' }}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
