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
        class="flex min-h-svh flex-col items-center justify-center gap-6 bg-[var(--paper)] p-6 text-[var(--ink)] md:p-10"
    >
        <div class="w-full max-w-sm">
            <div class="flex flex-col gap-8">
                <div class="flex flex-col items-center gap-4">
                    <Link
                        :href="home()"
                        class="flex flex-col items-center gap-2 font-medium outline-none ring-[var(--ring)] focus-visible:ring-2"
                    >
                        <div
                            class="mb-1 flex h-10 w-10 items-center justify-center rounded-lg bg-[var(--brand-red)] text-white"
                        >
                            <PaperclipIcon class="size-5" />
                        </div>
                        <span class="font-display text-lg font-semibold text-[var(--ink)]">
                            One Red Paperclip
                        </span>
                        <span class="sr-only">{{ title }}</span>
                    </Link>
                    <div class="space-y-2 text-center">
                        <h1 class="font-display text-xl font-semibold text-[var(--ink)]">
                            {{ title }}
                        </h1>
                        <p class="text-center text-sm text-[var(--ink-muted)]">
                            {{ description }}
                        </p>
                    </div>
                </div>
                <slot />
            </div>
        </div>
    </div>
</template>
