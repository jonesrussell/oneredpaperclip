<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';

type CampaignItem = {
    id: number;
    title: string | null;
    status: string;
    user?: { id: number; name: string } | null;
    current_item?: { id: number; title: string } | null;
    goal_item?: { id: number; title: string } | null;
};

defineProps<{
    campaigns: {
        data: CampaignItem[];
        current_page: number;
        last_page: number;
        links: { url: string | null; label: string; active: boolean }[];
    };
    categories: { id: number; name: string; slug: string }[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Campaigns', href: '/campaigns' },
];
</script>

<template>
    <Head title="Campaigns" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <h1 class="text-xl font-semibold">Campaigns</h1>

            <div
                v-if="campaigns.data.length === 0"
                class="rounded-xl border border-dashed py-12 text-center text-muted-foreground"
            >
                No campaigns yet. Be the first to start a trade-up.
            </div>

            <ul v-else class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <li v-for="campaign in campaigns.data" :key="campaign.id">
                    <Link
                        :href="`/campaigns/${campaign.id}`"
                        class="block transition-opacity hover:opacity-90"
                        prefetch
                    >
                        <Card class="h-full">
                            <CardHeader class="pb-2">
                                <CardTitle class="line-clamp-2 text-base">
                                    {{ campaign.title ?? 'Untitled campaign' }}
                                </CardTitle>
                                <CardDescription
                                    v-if="
                                        campaign.current_item?.title &&
                                        campaign.goal_item?.title
                                    "
                                    class="line-clamp-1 text-xs"
                                >
                                    {{ campaign.current_item.title }} →
                                    {{ campaign.goal_item.title }}
                                </CardDescription>
                            </CardHeader>
                            <CardContent class="pt-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <Badge variant="secondary" class="text-xs">
                                        {{ campaign.status }}
                                    </Badge>
                                    <span
                                        v-if="campaign.user?.name"
                                        class="text-muted-foreground text-xs"
                                    >
                                        {{ campaign.user.name }}
                                    </span>
                                </div>
                            </CardContent>
                        </Card>
                    </Link>
                </li>
            </ul>

            <nav
                v-if="campaigns.last_page > 1"
                class="flex flex-wrap items-center justify-center gap-2 pt-4"
                aria-label="Campaign pagination"
            >
                <template v-for="link in campaigns.links" :key="link.label">
                    <Link
                        v-if="link.url"
                        :href="link.url"
                        class="inline-flex min-w-9 items-center justify-center rounded-md border px-3 py-1.5 text-sm transition-colors hover:bg-accent"
                        :class="
                            link.active
                                ? 'border-primary bg-primary/10 text-primary'
                                : 'border-transparent'
                        "
                        :aria-current="link.active ? 'page' : undefined"
                    >
                        <span v-html="link.label" />
                    </Link>
                    <span
                        v-else
                        class="inline-flex min-w-9 cursor-default items-center justify-center rounded-md border border-transparent px-3 py-1.5 text-sm opacity-50"
                        v-html="link.label"
                    />
                </template>
            </nav>
        </div>
    </AppLayout>
</template>
