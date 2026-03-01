<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { ImagePlus, X } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

import { store } from '@/actions/App/Http/Controllers/OfferController';
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

const props = defineProps<{
    challengeId: number;
    currentItemTitle: string;
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
    offered_item: {
        title: '',
        description: '',
        image: null as File | null,
    },
    message: '',
});

const imagePreview = ref<string | null>(null);
const fileInput = ref<HTMLInputElement | null>(null);

function onFileChange(event: Event) {
    const target = event.target as HTMLInputElement;
    const file = target.files?.[0] ?? null;
    form.offered_item.image = file;
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
    form.offered_item.image = null;
    imagePreview.value = null;
    if (fileInput.value) {
        fileInput.value.value = '';
    }
}

function submit() {
    form.post(store.url({ challenge: props.challengeId }), {
        forceFormData: true,
        onSuccess: () => {
            form.reset();
            imagePreview.value = null;
            isOpen.value = false;
        },
    });
}

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
                <DialogTitle class="font-display">Make an Offer</DialogTitle>
                <DialogDescription>
                    Offer something in exchange for
                    <strong>{{ currentItemTitle }}</strong>
                </DialogDescription>
            </DialogHeader>

            <form class="space-y-4" @submit.prevent="submit">
                <div class="space-y-2">
                    <Label for="offer-title">What are you offering?</Label>
                    <Input
                        id="offer-title"
                        v-model="form.offered_item.title"
                        placeholder="e.g. A vintage typewriter"
                        required
                    />
                    <p
                        v-if="form.errors['offered_item.title']"
                        class="text-sm text-destructive"
                    >
                        {{ form.errors['offered_item.title'] }}
                    </p>
                </div>

                <div class="space-y-2">
                    <Label for="offer-description">
                        Description
                        <span class="text-muted-foreground">(optional)</span>
                    </Label>
                    <textarea
                        id="offer-description"
                        v-model="form.offered_item.description"
                        class="flex min-h-[80px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                        placeholder="Describe your item..."
                    />
                    <p
                        v-if="form.errors['offered_item.description']"
                        class="text-sm text-destructive"
                    >
                        {{ form.errors['offered_item.description'] }}
                    </p>
                </div>

                <div class="space-y-2">
                    <Label>
                        Photo
                        <span class="text-muted-foreground">(optional)</span>
                    </Label>
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
                    <p
                        v-if="form.errors['offered_item.image']"
                        class="text-sm text-destructive"
                    >
                        {{ form.errors['offered_item.image'] }}
                    </p>
                </div>

                <div class="space-y-2">
                    <Label for="offer-message">
                        Message to owner
                        <span class="text-muted-foreground">(optional)</span>
                    </Label>
                    <textarea
                        id="offer-message"
                        v-model="form.message"
                        class="flex min-h-[60px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                        placeholder="Why should they accept this trade?"
                    />
                    <p
                        v-if="form.errors.message"
                        class="text-sm text-destructive"
                    >
                        {{ form.errors.message }}
                    </p>
                </div>

                <DialogFooter>
                    <Button
                        type="button"
                        variant="outline"
                        @click="isOpen = false"
                    >
                        Cancel
                    </Button>
                    <Button
                        type="submit"
                        variant="brand"
                        :disabled="form.processing"
                    >
                        {{ form.processing ? 'Submitting...' : 'Submit Offer' }}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
