<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

import ProgressRing from '@/components/ProgressRing.vue';
import { Badge } from '@/components/ui/badge';
import campaigns from '@/routes/campaigns';

defineProps<{
    campaign: {
        id: number;
        title: string | null;
        status: string;
        trades_count?: number;
        user?: { id: number; name: string } | null;
        current_item?: { id: number; title: string } | null;
        goal_item?: { id: number; title: string } | null;
        category?: { id: number; name: string } | null;
    };
    progress?: number;
}>();

const categoryColors: Record<string, string> = {
    Electronics: 'var(--sky-blue)',
    Collectibles: 'var(--soft-lavender)',
    Home: 'var(--sunny-yellow)',
    Sports: 'var(--electric-mint)',
    Fashion: 'var(--hot-coral)',
    Art: 'var(--soft-lavender)',
    Music: 'var(--sky-blue)',
    Books: 'var(--sunny-yellow)',
    Other: 'var(--border)',
};

function getCategoryColor(name?: string): string {
    return categoryColors[name ?? ''] ?? 'var(--border)';
}

function getStatusClasses(status: string): string {
    switch (status) {
        case 'completed':
            return 'bg-[var(--electric-mint)]/15 text-[var(--electric-mint)]';
        case 'active':
            return 'bg-[var(--electric-mint)]/10 text-emerald-700';
        default:
            return 'bg-[var(--muted)]';
    }
}
</script>

<template>
    <Link
        :href="campaigns.show({ campaign: campaign.id }).url"
        class="group block overflow-hidden rounded-2xl border border-[var(--border)] bg-white shadow-sm transition-all hover:-translate-y-0.5 hover:shadow-md"
        prefetch
    >
        <!-- Category accent strip -->
        <div
            class="h-1.5 w-full"
            :style="{
                backgroundColor: getCategoryColor(campaign.category?.name),
            }"
        />

        <div class="relative p-4">
            <!-- Progress ring -->
            <div v-if="progress != null" class="absolute top-4 right-4">
                <ProgressRing
                    :percent="progress"
                    :size="40"
                    :stroke-width="3"
                />
            </div>

            <!-- Title -->
            <h3
                class="line-clamp-2 pr-12 font-display text-base font-bold text-[var(--ink)] transition-colors group-hover:text-[var(--brand-red)]"
            >
                {{ campaign.title ?? 'Untitled campaign' }}
            </h3>

            <!-- Current -> Goal -->
            <p
                v-if="campaign.current_item?.title && campaign.goal_item?.title"
                class="mt-2 line-clamp-1 text-sm text-[var(--ink-muted)]"
            >
                {{ campaign.current_item.title }}
                <span class="mx-1 text-[var(--brand-red)]">&rarr;</span>
                {{ campaign.goal_item.title }}
            </p>

            <!-- Footer: trade count + user + status -->
            <div class="mt-3 flex flex-wrap items-center gap-2">
                <Badge
                    v-if="campaign.trades_count"
                    variant="secondary"
                    class="rounded-full bg-[var(--muted)] text-xs font-medium"
                >
                    {{ campaign.trades_count }} trade{{
                        campaign.trades_count === 1 ? '' : 's'
                    }}
                </Badge>
                <span class="flex-1" />
                <span
                    v-if="campaign.user?.name"
                    class="text-xs text-[var(--ink-muted)]"
                >
                    {{ campaign.user.name }}
                </span>
                <Badge
                    variant="secondary"
                    class="rounded-full text-xs capitalize"
                    :class="getStatusClasses(campaign.status)"
                >
                    {{ campaign.status }}
                </Badge>
            </div>
        </div>
    </Link>
</template>
