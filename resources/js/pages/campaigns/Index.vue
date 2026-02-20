<script setup lang="ts">
import { computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import campaigns from '@/routes/campaigns';
import type { BreadcrumbItem } from '@/types';
import { Plus } from 'lucide-vue-next';

type CampaignItem = {
    id: number;
    title: string | null;
    status: string;
    user?: { id: number; name: string } | null;
    current_item?: { id: number; title: string } | null;
    goal_item?: { id: number; title: string } | null;
};

const props = withDefaults(
    defineProps<{
        campaigns?: {
            data: CampaignItem[];
            current_page: number;
            last_page: number;
            links: { url: string | null; label: string; active: boolean }[];
        };
        categories?: { id: number; name: string; slug: string }[];
    }>(),
    {
        campaigns: () => ({
            data: [],
            current_page: 1,
            last_page: 1,
            links: [],
        }),
        categories: () => [],
    },
);

const campaignList = computed(() => props.campaigns?.data ?? []);
const categoryList = computed(() => props.categories ?? []);
const campaignLinks = computed(() => props.campaigns?.links ?? []);

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Campaigns', href: campaigns.index().url },
];

function filterByCategory(categoryId: number | null) {
    router.get(campaigns.index().url, categoryId != null ? { category_id: categoryId } : {}, { preserveState: true });
}
</script>

<template>
    <Head title="Campaigns" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div
            class="flex h-full flex-1 flex-col gap-6 overflow-x-auto p-4 md:p-6"
        >
            <div class="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <h1
                        class="font-display text-2xl font-semibold tracking-tight text-[var(--ink)] sm:text-3xl"
                    >
                        Campaigns
                    </h1>
                    <p class="mt-1 text-[var(--ink-muted)]">
                        Browse trade-up campaigns and make offers.
                    </p>
                </div>
                <Button variant="brand" as-child>
                    <Link :href="campaigns.create()">
                        <Plus class="size-4" />
                        New campaign
                    </Link>
                </Button>
            </div>

            <div
                v-if="categoryList.length > 0"
                class="flex flex-wrap gap-2"
                role="group"
                aria-label="Filter by category"
            >
                <Button
                    variant="outline"
                    size="sm"
                    class="border-[var(--ink)]/20"
                    @click="filterByCategory(null)"
                >
                    All
                </Button>
                <Button
                    v-for="cat in categoryList"
                    :key="cat.id"
                    variant="outline"
                    size="sm"
                    class="border-[var(--ink)]/20"
                    @click="filterByCategory(cat.id)"
                >
                    {{ cat.name }}
                </Button>
            </div>

            <div
                v-if="campaignList.length === 0"
                class="rounded-xl border border-dashed border-[var(--ink)]/20 py-16 text-center text-[var(--ink-muted)]"
            >
                No campaigns yet. Be the first to start a trade-up.
                <Link
                    :href="campaigns.create()"
                    class="mt-2 inline-block font-medium text-[var(--brand-red)] hover:underline"
                >
                    Create a campaign
                </Link>
            </div>

            <ul v-else class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <li v-for="campaign in campaignList" :key="campaign.id">
                    <Link
                        :href="campaigns.show({ campaign: campaign.id })"
                        class="block transition-opacity hover:opacity-90"
                        prefetch
                    >
                        <Card
                            class="h-full border-[var(--ink)]/10 transition-shadow hover:border-[var(--brand-red)]/30 hover:shadow-md"
                        >
                            <CardHeader class="pb-2">
                                <CardTitle
                                    class="line-clamp-2 font-display text-base font-semibold text-[var(--ink)]"
                                >
                                    {{ campaign.title ?? 'Untitled campaign' }}
                                </CardTitle>
                                <CardDescription
                                    v-if="
                                        campaign.current_item?.title &&
                                        campaign.goal_item?.title
                                    "
                                    class="line-clamp-1 text-xs text-[var(--ink-muted)]"
                                >
                                    {{ campaign.current_item.title }} →
                                    {{ campaign.goal_item.title }}
                                </CardDescription>
                            </CardHeader>
                            <CardContent class="pt-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <Badge
                                        variant="secondary"
                                        class="text-xs capitalize"
                                    >
                                        {{ campaign.status }}
                                    </Badge>
                                    <span
                                        v-if="campaign.user?.name"
                                        class="text-xs text-[var(--ink-muted)]"
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
                <template v-for="link in campaignLinks" :key="link.label">
                    <Link
                        v-if="link.url"
                        :href="link.url"
                        class="inline-flex min-w-9 items-center justify-center rounded-md border px-3 py-1.5 text-sm transition-colors hover:bg-[var(--ink)]/5"
                        :class="
                            link.active
                                ? 'border-[var(--brand-red)] bg-[var(--brand-red)]/10 text-[var(--brand-red)]'
                                : 'border-[var(--ink)]/20'
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
