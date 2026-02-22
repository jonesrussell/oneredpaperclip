<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

import ProgressRing from '@/components/ProgressRing.vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { useInitials } from '@/composables/useInitials';
import campaigns from '@/routes/campaigns';

defineProps<{
    campaign: {
        id: number;
        title: string | null;
        status: string;
        trades_count?: number;
        user?: { id: number; name: string; avatar?: string | null } | null;
        current_item?: { id: number; title: string } | null;
        goal_item?: { id: number; title: string } | null;
        category?: { id: number; name: string } | null;
    };
    progress?: number;
}>();

const { getInitials } = useInitials();

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

function statusLabel(status: string): string {
    switch (status) {
        case 'completed':
            return 'Completed';
        case 'active':
            return 'Active';
        default:
            return status;
    }
}

function statusStyles(status: string): string {
    switch (status) {
        case 'completed':
            return 'bg-[var(--electric-mint)]/20 text-[var(--electric-mint)]';
        case 'active':
            return 'bg-[var(--electric-mint)]/15 text-emerald-700';
        default:
            return 'bg-[var(--muted)] text-[var(--ink-muted)]';
    }
}
</script>

<template>
    <Link
        :href="campaigns.show({ campaign: campaign.id }).url"
        class="surface-light group relative block overflow-hidden rounded-xl border border-[var(--ink)]/10 bg-[var(--paper)] transition-all duration-200 hover:-translate-y-0.5 hover:shadow-[0_8px_24px_rgba(28,18,8,0.12)]"
        style="box-shadow: 0 2px 12px rgba(28, 18, 8, 0.06);"
        prefetch
    >
        <!-- Left-edge category accent -->
        <div
            class="absolute left-0 top-0 bottom-0 w-1 rounded-l-xl transition-transform duration-200 group-hover:scale-y-105"
            :style="{
                backgroundColor: getCategoryColor(campaign.category?.name),
            }"
            aria-hidden="true"
        />

        <div class="relative p-4 pl-5">
            <!-- Progress ring (when provided) -->
            <div v-if="progress != null" class="absolute top-4 right-4">
                <ProgressRing
                    :percent="progress"
                    :size="40"
                    :stroke-width="3"
                />
            </div>

            <!-- Title -->
            <h3
                class="line-clamp-2 pr-12 font-display text-lg font-semibold leading-snug text-[hsl(28,18%,26%)] transition-colors group-hover:text-[var(--brand-red)] dark:text-[hsl(38,15%,88%)]"
            >
                {{ campaign.title ?? 'Untitled campaign' }}
            </h3>

            <!-- Journey: current → goal -->
            <p
                v-if="campaign.current_item?.title && campaign.goal_item?.title"
                class="mt-2.5 line-clamp-1 flex items-center gap-1.5 text-sm text-[hsl(28,12%,42%)] dark:text-[hsl(38,8%,68%)]"
            >
                <span class="truncate">{{ campaign.current_item.title }}</span>
                <span
                    class="shrink-0 font-mono text-[var(--brand-red)]"
                    aria-hidden="true"
                >
                    →
                </span>
                <span class="truncate font-medium text-[hsl(28,15%,32%)] dark:text-[hsl(38,12%,82%)]">
                    {{ campaign.goal_item.title }}
                </span>
            </p>

            <!-- Footer: trade count, user, status -->
            <div
                class="mt-4 flex flex-wrap items-center gap-x-3 gap-y-1 border-t border-[var(--ink)]/5 pt-3"
            >
                <span
                    v-if="campaign.trades_count != null && campaign.trades_count > 0"
                    class="font-mono text-xs font-semibold text-[hsl(28,10%,48%)] dark:text-[hsl(38,8%,62%)]"
                >
                    {{ campaign.trades_count }}
                    {{ campaign.trades_count === 1 ? 'trade' : 'trades' }}
                </span>
                <span
                    v-if="campaign.user?.name"
                    class="flex items-center gap-2 text-xs text-[hsl(28,10%,48%)] dark:text-[hsl(38,8%,62%)]"
                >
                    <Avatar class="h-8 w-8 shrink-0 overflow-hidden rounded-lg">
                        <AvatarImage
                            v-if="campaign.user.avatar"
                            :src="campaign.user.avatar"
                            :alt="campaign.user.name"
                        />
                        <AvatarFallback class="rounded-lg text-black dark:text-white">
                            {{ getInitials(campaign.user.name) }}
                        </AvatarFallback>
                    </Avatar>
                    {{ campaign.user.name }}
                </span>
                <span class="flex-1" />
                <span
                    class="rounded-full px-2 py-0.5 text-xs font-medium capitalize"
                    :class="statusStyles(campaign.status)"
                >
                    {{ statusLabel(campaign.status) }}
                </span>
            </div>
        </div>
    </Link>
</template>
