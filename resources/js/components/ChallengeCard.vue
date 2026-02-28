<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ImageIcon } from 'lucide-vue-next';

import ProgressRing from '@/components/ProgressRing.vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { useInitials } from '@/composables/useInitials';
import challenges from '@/routes/challenges';

const props = defineProps<{
    campaign: {
        id: number;
        title: string | null;
        status: string;
        trades_count?: number;
        user?: { id: number; name: string; avatar?: string | null } | null;
        current_item?: {
            id: number;
            title: string;
            image_url?: string | null;
        } | null;
        goal_item?: {
            id: number;
            title: string;
            image_url?: string | null;
        } | null;
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

const heroImageUrl = (): string | null =>
    props.campaign.current_item?.image_url ??
    props.campaign.goal_item?.image_url ??
    null;
</script>

<template>
    <Link
        :href="challenges.show({ challenge: campaign.id }).url"
        class="surface-light group relative block min-w-0 overflow-hidden rounded-xl border border-[var(--ink)]/10 bg-[var(--paper)] transition-all duration-200 hover:-translate-y-0.5 hover:shadow-[0_8px_24px_rgba(28,18,8,0.12)]"
        style="box-shadow: 0 2px 12px rgba(28, 18, 8, 0.06)"
        prefetch
    >
        <!-- Left-edge category accent -->
        <div
            class="absolute top-0 bottom-0 left-0 w-1 rounded-l-xl transition-transform duration-200 group-hover:scale-y-105"
            :style="{
                backgroundColor: getCategoryColor(campaign.category?.name),
            }"
            aria-hidden="true"
        />

        <!-- Hero image area -->
        <div
            class="relative h-36 w-full shrink-0 overflow-hidden bg-gradient-to-br from-[var(--ink)]/8 to-[var(--ink)]/4 transition-transform duration-200 group-hover:scale-[1.02]"
        >
            <img
                v-if="heroImageUrl()"
                :src="heroImageUrl()!"
                alt=""
                class="size-full object-cover"
            />
            <div
                v-else
                class="flex size-full flex-col items-center justify-center gap-1.5 text-[var(--ink-muted)]"
                aria-hidden="true"
            >
                <ImageIcon class="size-10 opacity-50" />
                <span class="text-xs font-medium">No photo</span>
            </div>
        </div>

        <div class="relative min-w-0 p-4 pl-5">
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
                class="line-clamp-2 pr-12 font-display text-xl leading-snug font-semibold text-[hsl(28,18%,26%)] transition-colors group-hover:text-[var(--brand-red)] dark:text-[hsl(38,15%,88%)]"
            >
                {{ campaign.title ?? 'Untitled challenge' }}
            </h3>

            <!-- Journey: current → goal (with optional item thumbnails) -->
            <p
                v-if="campaign.current_item?.title || campaign.goal_item?.title"
                class="mt-2.5 flex min-w-0 items-center gap-1.5 text-sm text-[hsl(28,12%,42%)] dark:text-[hsl(38,8%,68%)]"
            >
                <!-- Current item thumbnail -->
                <span
                    class="flex shrink-0 overflow-hidden rounded-md bg-[var(--ink)]/5"
                >
                    <img
                        v-if="campaign.current_item?.image_url"
                        :src="campaign.current_item.image_url"
                        :alt="campaign.current_item?.title ?? 'Current item'"
                        class="size-8 object-cover"
                    />
                    <span
                        v-else
                        class="flex size-8 items-center justify-center text-[var(--ink-muted)]"
                        aria-hidden="true"
                    >
                        <ImageIcon class="size-4 opacity-60" />
                    </span>
                </span>
                <span class="min-w-0 truncate">
                    {{ campaign.current_item?.title ?? 'Start' }}
                </span>
                <span
                    class="shrink-0 font-mono text-[var(--brand-red)]"
                    aria-hidden="true"
                >
                    →
                </span>
                <!-- Goal item thumbnail -->
                <span
                    class="flex shrink-0 overflow-hidden rounded-md bg-[var(--ink)]/5"
                >
                    <img
                        v-if="campaign.goal_item?.image_url"
                        :src="campaign.goal_item.image_url"
                        :alt="campaign.goal_item?.title ?? 'Goal item'"
                        class="size-8 object-cover"
                    />
                    <span
                        v-else
                        class="flex size-8 items-center justify-center text-[var(--ink-muted)]"
                        aria-hidden="true"
                    >
                        <ImageIcon class="size-4 opacity-60" />
                    </span>
                </span>
                <span
                    class="min-w-0 truncate font-medium text-[hsl(28,15%,32%)] dark:text-[hsl(38,12%,82%)]"
                >
                    {{ campaign.goal_item?.title ?? 'Goal' }}
                </span>
            </p>

            <!-- Footer: trade count, user, status -->
            <div
                class="mt-4 flex flex-wrap items-center gap-x-3 gap-y-1 border-t border-[var(--ink)]/5 pt-3"
            >
                <span
                    v-if="
                        campaign.trades_count != null &&
                        campaign.trades_count > 0
                    "
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
                        <AvatarFallback
                            class="rounded-lg text-black dark:text-white"
                        >
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
