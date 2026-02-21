<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { home } from '@/routes';

defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <div
        class="relative flex min-h-svh flex-col items-center justify-center gap-6 overflow-hidden bg-background p-6 md:p-10"
    >
        <!-- Soft background blobs -->
        <div class="absolute -right-24 -top-24 h-72 w-72 rounded-full bg-[var(--hot-coral)]/30 blur-3xl animate-blob-pulse" />
        <div class="absolute -bottom-16 -left-16 h-56 w-56 rounded-full bg-[var(--electric-mint)]/20 blur-3xl animate-blob-pulse" style="animation-delay: -2s" />
        <div class="absolute left-1/2 top-1/4 h-40 w-40 rounded-full bg-[var(--sunny-yellow)]/15 blur-3xl animate-blob-pulse" style="animation-delay: -4s" />

        <div class="relative w-full max-w-sm">
            <div class="flex flex-col gap-8">
                <div class="flex flex-col items-center gap-4">
                    <Link
                        :href="home()"
                        class="flex flex-col items-center gap-2 font-medium"
                    >
                        <div
                            class="mb-1 flex h-9 w-9 items-center justify-center rounded-md"
                        >
                            <AppLogoIcon
                                class="size-9 fill-current text-[var(--foreground)] dark:text-white"
                            />
                        </div>
                        <span class="sr-only">{{ title }}</span>
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
