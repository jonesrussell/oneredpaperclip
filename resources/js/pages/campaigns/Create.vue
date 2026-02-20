<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';

defineProps<{
    categories: { id: number; name: string; slug: string }[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Campaigns', href: '/campaigns' },
    { title: 'Create', href: '/campaigns/create' },
];
</script>

<template>
    <Head title="Create Campaign" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div
            class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4"
        >
            <h1 class="text-xl font-semibold">Create Campaign</h1>
        </div>
    </AppLayout>
</template>
