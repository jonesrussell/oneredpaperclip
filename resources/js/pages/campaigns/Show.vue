<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';

const props = defineProps<{
    campaign: { id: number; title?: string | null };
    isFollowing: boolean;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Campaigns', href: '/campaigns' },
    { title: props.campaign.title ?? 'Campaign', href: `/campaigns/${props.campaign.id}` },
];
</script>

<template>
    <Head :title="campaign.title ?? 'Campaign'" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <h1 class="text-xl font-semibold">{{ campaign.title ?? 'Campaign' }}</h1>
        </div>
    </AppLayout>
</template>
