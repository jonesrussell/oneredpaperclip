<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import PaperclipIcon from '@/components/PaperclipIcon.vue';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
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
        <!-- Decorative blobs -->
        <div
            class="animate-blob-pulse pointer-events-none absolute -top-20 -right-20 h-64 w-64 rounded-full bg-[var(--hot-coral)]/25 blur-3xl"
            aria-hidden="true"
        />
        <div
            class="animate-blob-pulse pointer-events-none absolute -bottom-16 -left-16 h-48 w-48 rounded-full bg-[var(--sunny-yellow)]/20 blur-3xl"
            aria-hidden="true"
            style="animation-delay: -2s"
        />
        <div
            class="animate-blob-pulse pointer-events-none absolute top-1/2 left-1/2 h-40 w-40 -translate-x-1/2 -translate-y-1/2 rounded-full bg-[var(--electric-mint)]/15 blur-3xl"
            aria-hidden="true"
            style="animation-delay: -4s"
        />
        <div class="relative z-10 flex w-full max-w-md flex-col gap-6">
            <Link
                :href="home()"
                class="flex items-center gap-2 self-center font-medium"
            >
                <PaperclipIcon class="size-8 text-[var(--brand-red)]" />
                <span
                    class="font-display text-base font-bold tracking-tight text-[var(--ink)]"
                    >One Red Paperclip</span
                >
            </Link>

            <div class="flex flex-col gap-6">
                <Card class="rounded-xl">
                    <CardHeader class="px-10 pt-8 pb-0 text-center">
                        <CardTitle class="text-xl">{{ title }}</CardTitle>
                        <CardDescription>
                            {{ description }}
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="px-10 py-8">
                        <slot />
                    </CardContent>
                </Card>
            </div>
        </div>
    </div>
</template>
