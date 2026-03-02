<script setup lang="ts">
import { computed } from 'vue';

import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogTitle,
} from '@/components/ui/dialog';

const props = withDefaults(
    defineProps<{
        open: boolean;
        imageUrl: string;
        title?: string;
    }>(),
    { title: '' },
);

const emit = defineEmits<{
    'update:open': [value: boolean];
}>();

const isOpen = computed({
    get: () => props.open,
    set: (value) => emit('update:open', value),
});
</script>

<template>
    <Dialog v-model:open="isOpen">
        <DialogContent class="sm:max-w-4xl border-0 bg-transparent p-2 shadow-none">
            <DialogTitle class="sr-only">
                {{ title || 'Enlarged image' }}
            </DialogTitle>
            <div class="flex flex-col items-center gap-2">
                <img
                    :src="imageUrl"
                    :alt="title || 'Enlarged image'"
                    class="max-h-[85vh] w-auto object-contain rounded-lg"
                />
                <DialogDescription
                    v-if="title"
                    class="text-center text-sm text-muted-foreground"
                >
                    {{ title }}
                </DialogDescription>
            </div>
        </DialogContent>
    </Dialog>
</template>
