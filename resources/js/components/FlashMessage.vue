<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { X } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

import { Alert, AlertDescription } from '@/components/ui/alert';

const page = usePage();
const dismissed = ref(false);

const message = computed(() => page.props.flash?.success ?? null);

watch(message, (val) => {
    if (val) {
        dismissed.value = false;
        setTimeout(() => {
            dismissed.value = true;
        }, 5000);
    }
});
</script>

<template>
    <div
        v-if="message && !dismissed"
        class="fixed top-4 right-4 z-50 max-w-sm animate-in fade-in slide-in-from-top-2"
    >
        <Alert
            class="border-[var(--electric-mint)]/30 bg-[var(--electric-mint)]/10 text-foreground shadow-lg"
        >
            <AlertDescription class="flex items-center justify-between gap-2">
                <span>{{ message }}</span>
                <button
                    class="shrink-0 text-muted-foreground hover:text-foreground"
                    @click="dismissed = true"
                >
                    <X class="size-4" />
                </button>
            </AlertDescription>
        </Alert>
    </div>
</template>
