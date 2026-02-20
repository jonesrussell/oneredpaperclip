<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import PaperclipIcon from '@/components/PaperclipIcon.vue';
import { dashboard } from '@/routes';

const href = dashboard().url;
</script>

<template>
    <Link
        :href="href"
        class="flex items-center gap-2 rounded-md outline-none ring-sidebar-ring focus-visible:ring-2"
    >
        <div
            class="flex aspect-square size-8 shrink-0 items-center justify-center rounded-md bg-[var(--brand-red)] text-white"
        >
            <PaperclipIcon class="size-5" />
        </div>
        <span
            class="font-display truncate text-sm font-semibold leading-tight text-sidebar-foreground"
        >
            One Red Paperclip
        </span>
    </Link>
</template>
