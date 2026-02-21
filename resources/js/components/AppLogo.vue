<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import PaperclipIcon from '@/components/PaperclipIcon.vue';
import { dashboard } from '@/routes';

const href = dashboard().url;
</script>

<template>
    <Link
        :href="href"
        class="flex items-center gap-2 rounded-md ring-sidebar-ring outline-none focus-visible:ring-2"
    >
        <PaperclipIcon class="size-7 shrink-0 text-[var(--brand-red)]" />
        <span
            class="truncate font-display text-sm leading-tight font-semibold text-sidebar-foreground"
        >
            One Red Paperclip
        </span>
    </Link>
</template>
