<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ImageIcon } from 'lucide-vue-next';
import { computed } from 'vue';

import ImageLightbox from '@/components/ImageLightbox.vue';
import ProgressRing from '@/components/ProgressRing.vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { useImageLightbox } from '@/composables/useImageLightbox';
import { useInitials } from '@/composables/useInitials';
import challenges from '@/routes/challenges';

const props = defineProps<{
    challenge: {
        id: number;
        slug: string;
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

const heroImageUrl = computed(
    () =>
        props.challenge.current_item?.image_url ??
        props.challenge.goal_item?.image_url ??
        null,
);

const {
    open: lightboxOpen,
    imageUrl: lightboxImageUrl,
    title: lightboxTitle,
    openLightbox,
    onOpenChange,
} = useImageLightbox();
</script>

<template>
    <div class="relative">
    <Link
        :href="challenges.show({ challenge: challenge.slug }).url"
        class="group relative block min-w-0 overflow-hidden rounded-xl border-2 border-[var(--border)] bg-[var(--paper)] transition-colors duration-200 hover:border-[var(--brand-red)]/30"
        prefetch
    >
        <!-- Left-edge category accent -->
        <div
            class="absolute top-0 bottom-0 left-0 w-1.5 rounded-l-xl transition-transform duration-200 group-hover:scale-y-105"
            :style="{
                backgroundColor: getCategoryColor(challenge.category?.name),
            }"
            aria-hidden="true"
        />

        <!-- Hero image area -->
        <div
            class="relative h-36 w-full shrink-0 overflow-hidden bg-[var(--muted)] transition-transform duration-200 group-hover:scale-[1.02]"
        >
            <button
                v-if="heroImageUrl"
                type="button"
                class="size-full cursor-pointer border-0 bg-transparent p-0 focus:outline-none focus:ring-2 focus:ring-[var(--hot-coral)] focus:ring-inset"
                aria-label="View full size"
                @click.stop.prevent="openLightbox(heroImageUrl, challenge.title ?? 'Challenge')"
            >
                <img
                    :src="heroImageUrl"
                    alt=""
                    class="size-full object-cover"
                />
            </button>
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
                class="line-clamp-2 pr-12 font-display text-xl leading-snug font-semibold text-foreground transition-colors group-hover:text-[var(--brand-red)]"
            >
                {{ challenge.title ?? 'Untitled challenge' }}
            </h3>

            <!-- Journey: current → goal (with optional item thumbnails) -->
            <p
                v-if="
                    challenge.current_item?.title || challenge.goal_item?.title
                "
                class="mt-2.5 flex min-w-0 items-center gap-1.5 text-sm text-muted-foreground"
            >
                <!-- Current item thumbnail -->
                <span
                    class="flex shrink-0 overflow-hidden rounded-md bg-[var(--ink)]/5"
                >
                    <button
                        v-if="challenge.current_item?.image_url"
                        type="button"
                        class="size-8 cursor-pointer border-0 bg-transparent p-0 focus:outline-none focus:ring-2 focus:ring-[var(--hot-coral)] focus:ring-inset rounded-md"
                        aria-label="View full size"
                        @click.stop.prevent="openLightbox(challenge.current_item.image_url, challenge.current_item?.title ?? 'Current item')"
                    >
                        <img
                            :src="challenge.current_item.image_url"
                            :alt="challenge.current_item?.title ?? 'Current item'"
                            class="size-8 object-cover"
                        />
                    </button>
                    <span
                        v-else
                        class="flex size-8 items-center justify-center text-[var(--ink-muted)]"
                        aria-hidden="true"
                    >
                        <ImageIcon class="size-4 opacity-60" />
                    </span>
                </span>
                <span class="min-w-0 truncate">
                    {{ challenge.current_item?.title ?? 'Start' }}
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
                    <button
                        v-if="challenge.goal_item?.image_url"
                        type="button"
                        class="size-8 cursor-pointer border-0 bg-transparent p-0 focus:outline-none focus:ring-2 focus:ring-[var(--hot-coral)] focus:ring-inset rounded-md"
                        aria-label="View full size"
                        @click.stop.prevent="openLightbox(challenge.goal_item.image_url, challenge.goal_item?.title ?? 'Goal item')"
                    >
                        <img
                            :src="challenge.goal_item.image_url"
                            :alt="challenge.goal_item?.title ?? 'Goal item'"
                            class="size-8 object-cover"
                        />
                    </button>
                    <span
                        v-else
                        class="flex size-8 items-center justify-center text-[var(--ink-muted)]"
                        aria-hidden="true"
                    >
                        <ImageIcon class="size-4 opacity-60" />
                    </span>
                </span>
                <span class="min-w-0 truncate font-medium text-foreground">
                    {{ challenge.goal_item?.title ?? 'Goal' }}
                </span>
            </p>

            <!-- Footer: trade count, user, status -->
            <div
                class="mt-4 flex flex-wrap items-center gap-x-3 gap-y-1 border-t border-[var(--ink)]/5 pt-3"
            >
                <span
                    v-if="
                        challenge.trades_count != null &&
                        challenge.trades_count > 0
                    "
                    class="font-mono text-xs font-semibold text-muted-foreground"
                >
                    {{ challenge.trades_count }}
                    {{ challenge.trades_count === 1 ? 'trade' : 'trades' }}
                </span>
                <span
                    v-if="challenge.user?.name"
                    class="flex items-center gap-2 text-xs text-muted-foreground"
                >
                    <Avatar
                        class="h-8 w-8 shrink-0 overflow-hidden rounded-full"
                    >
                        <AvatarImage
                            v-if="challenge.user.avatar"
                            :src="challenge.user.avatar"
                            :alt="challenge.user.name"
                        />
                        <AvatarFallback class="rounded-full text-foreground">
                            {{ getInitials(challenge.user.name) }}
                        </AvatarFallback>
                    </Avatar>
                    {{ challenge.user.name }}
                </span>
                <span class="flex-1" />
                <span
                    class="rounded-full px-2 py-0.5 text-xs font-medium capitalize"
                    :class="statusStyles(challenge.status)"
                >
                    {{ statusLabel(challenge.status) }}
                </span>
            </div>
        </div>

    </Link>
        <ImageLightbox
            v-if="lightboxImageUrl"
            :open="lightboxOpen"
            :image-url="lightboxImageUrl"
            :title="lightboxTitle"
            @update:open="onOpenChange"
        />
    </div>
</template>
