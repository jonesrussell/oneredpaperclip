<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import PaperclipIcon from '@/components/PaperclipIcon.vue';
import { home } from '@/routes';

defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <div
        class="relative flex min-h-svh flex-col items-center justify-center gap-6 overflow-hidden bg-[var(--paper)] p-6 md:p-10"
    >
        <!-- Soft background blobs -->
        <div
            class="animate-blob-pulse absolute -top-24 -right-24 h-80 w-80 rounded-full bg-[var(--hot-coral)]/35 blur-3xl"
        />
        <div
            class="animate-blob-pulse absolute -bottom-16 -left-16 h-64 w-64 rounded-full bg-[var(--electric-mint)]/28 blur-3xl"
            style="animation-delay: -2s"
        />
        <div
            class="animate-blob-pulse absolute top-1/4 left-1/2 h-48 w-48 rounded-full bg-[var(--sunny-yellow)]/25 blur-3xl"
            style="animation-delay: -4s"
        />

        <div class="relative w-full max-w-sm">
            <div class="flex flex-col gap-8">
                <div class="flex flex-col items-center gap-4">
                    <Link
                        :href="home()"
                        class="flex flex-col items-center gap-2 font-medium"
                    >
                        <PaperclipIcon
                            class="mb-1 size-10 text-[var(--brand-red)]"
                        />
                        <span
                            class="font-display text-base font-bold tracking-tight text-[var(--ink)]"
                            >One Red Paperclip</span
                        >
                    </Link>
                    <div class="space-y-2 text-center">
                        <h1 class="text-xl font-medium">{{ title }}</h1>
                        <p class="text-center text-sm text-muted-foreground">
                            {{ description }}
                        </p>
                    </div>
                </div>
                <slot />
            </div>
        </div>
    </div>
</template>
