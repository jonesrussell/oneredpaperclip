<script setup lang="ts">
import { Calendar, Flame, Package } from 'lucide-vue-next';
import { computed, onMounted, ref } from 'vue';

import ProgressRing from '@/components/ProgressRing.vue';

type Stats = {
    xp: number;
    level: number;
    levelProgress: number;
    xpForNextLevel: number;
    currentStreak: number;
    longestStreak: number;
    tradesCompleted: number;
    daysActive: number;
    isStreakAtRisk?: boolean;
};

const props = withDefaults(
    defineProps<{
        stats: Stats;
        compact?: boolean;
    }>(),
    {
        compact: false,
    },
);

const animatedXp = ref(0);
const animatedStreak = ref(0);
const animatedTrades = ref(0);

onMounted(() => {
    animateValue(animatedXp, props.stats.xp, 1000);
    animateValue(animatedStreak, props.stats.currentStreak, 600);
    animateValue(animatedTrades, props.stats.tradesCompleted, 800);
});

function animateValue(
    target: { value: number },
    end: number,
    duration: number,
): void {
    const start = 0;
    const range = end - start;
    const startTime = performance.now();

    function update(currentTime: number): void {
        const elapsed = currentTime - startTime;
        const progress = Math.min(elapsed / duration, 1);
        const easeOut = 1 - Math.pow(1 - progress, 3);
        target.value = Math.round(start + range * easeOut);

        if (progress < 1) {
            requestAnimationFrame(update);
        }
    }

    requestAnimationFrame(update);
}

const levelBadgeColor = computed(() => {
    if (props.stats.level >= 10) return 'from-amber-400 to-yellow-500';
    if (props.stats.level >= 5) return 'from-violet-500 to-purple-600';
    return 'from-sky-400 to-blue-500';
});

const streakFlameColor = computed(() => {
    if (props.stats.currentStreak >= 30) return 'text-amber-400';
    if (props.stats.currentStreak >= 7) return 'text-orange-500';
    return 'text-red-500';
});
</script>

<template>
    <div
        class="stats-panel"
        :class="
            compact
                ? 'flex flex-wrap items-center gap-3'
                : 'space-y-4 rounded-2xl border border-border bg-card/80 p-4 backdrop-blur-sm dark:shadow-[var(--shadow-card)]'
        "
    >
        <!-- XP and Level -->
        <div
            :class="
                compact
                    ? 'flex items-center gap-2 rounded-xl bg-muted/50 px-3 py-2'
                    : 'rounded-xl bg-gradient-to-br from-[var(--soft-lavender)]/10 to-[var(--sky-blue)]/10 p-4'
            "
        >
            <div class="flex items-center gap-3">
                <!-- Level badge -->
                <div
                    class="flex size-10 items-center justify-center rounded-full bg-gradient-to-br font-display text-lg font-bold text-white shadow-lg"
                    :class="levelBadgeColor"
                >
                    {{ stats.level }}
                </div>
                <div v-if="!compact" class="flex-1">
                    <div class="flex items-center justify-between">
                        <span
                            class="font-display text-sm font-semibold text-foreground"
                        >
                            Level {{ stats.level }}
                        </span>
                        <span class="font-mono text-xs text-muted-foreground">
                            {{ animatedXp.toLocaleString() }} XP
                        </span>
                    </div>
                    <!-- XP progress bar -->
                    <div class="mt-2 h-2 overflow-hidden rounded-full bg-muted">
                        <div
                            class="h-full rounded-full bg-gradient-to-r from-[var(--soft-lavender)] to-[var(--sky-blue)] transition-all duration-1000 ease-out"
                            :style="{ width: `${stats.levelProgress}%` }"
                        />
                    </div>
                    <p class="mt-1 text-right text-xs text-muted-foreground">
                        {{ stats.xpForNextLevel.toLocaleString() }} XP to next
                        level
                    </p>
                </div>
                <div v-else class="text-sm">
                    <span class="font-mono font-semibold text-foreground">
                        {{ animatedXp.toLocaleString() }}
                    </span>
                    <span class="ml-1 text-muted-foreground">XP</span>
                </div>
            </div>
        </div>

        <!-- Streak -->
        <div
            :class="
                compact
                    ? 'flex items-center gap-2 rounded-xl bg-muted/50 px-3 py-2'
                    : 'flex items-center gap-3 rounded-xl bg-gradient-to-br from-orange-500/10 to-red-500/10 p-4'
            "
        >
            <div class="relative">
                <Flame
                    class="size-8 transition-transform"
                    :class="[
                        streakFlameColor,
                        stats.currentStreak > 0
                            ? 'animate-bounce-slow'
                            : 'opacity-30',
                    ]"
                />
                <div
                    v-if="stats.isStreakAtRisk"
                    class="absolute -top-1 -right-1 size-3 animate-pulse rounded-full bg-yellow-400"
                />
            </div>
            <div :class="compact ? 'text-sm' : ''">
                <span
                    class="font-mono font-bold"
                    :class="[
                        streakFlameColor,
                        compact ? 'text-base' : 'text-2xl',
                    ]"
                >
                    {{ animatedStreak }}
                </span>
                <span
                    class="ml-1"
                    :class="compact ? 'text-muted-foreground' : 'text-sm'"
                >
                    day{{ stats.currentStreak !== 1 ? 's' : '' }}
                </span>
                <p
                    v-if="!compact && stats.longestStreak > stats.currentStreak"
                    class="mt-0.5 text-xs text-muted-foreground"
                >
                    Best: {{ stats.longestStreak }} days
                </p>
            </div>
        </div>

        <!-- Trades completed -->
        <div
            :class="
                compact
                    ? 'flex items-center gap-2 rounded-xl bg-muted/50 px-3 py-2'
                    : 'flex items-center gap-3 rounded-xl bg-gradient-to-br from-[var(--electric-mint)]/10 to-emerald-500/10 p-4'
            "
        >
            <div
                class="flex size-10 items-center justify-center rounded-full bg-[var(--electric-mint)]/20"
            >
                <Package class="size-5 text-[var(--electric-mint)]" />
            </div>
            <div :class="compact ? 'text-sm' : ''">
                <span
                    class="font-mono font-bold text-[var(--electric-mint)]"
                    :class="compact ? 'text-base' : 'text-2xl'"
                >
                    {{ animatedTrades }}
                </span>
                <span
                    class="ml-1"
                    :class="compact ? 'text-muted-foreground' : 'text-sm'"
                >
                    trade{{ stats.tradesCompleted !== 1 ? 's' : '' }}
                </span>
            </div>
        </div>

        <!-- Days active (full mode only) -->
        <div
            v-if="!compact"
            class="flex items-center gap-3 rounded-xl bg-gradient-to-br from-[var(--sunny-yellow)]/10 to-amber-500/10 p-4"
        >
            <div
                class="flex size-10 items-center justify-center rounded-full bg-[var(--sunny-yellow)]/20"
            >
                <Calendar class="size-5 text-[var(--sunny-yellow)]" />
            </div>
            <div>
                <span
                    class="font-mono text-2xl font-bold text-[var(--sunny-yellow)]"
                >
                    {{ stats.daysActive }}
                </span>
                <span class="ml-1 text-sm">
                    day{{ stats.daysActive !== 1 ? 's' : '' }} active
                </span>
            </div>
        </div>

        <!-- Progress ring (compact mode) -->
        <div v-if="compact" class="flex items-center gap-2">
            <ProgressRing
                :percent="stats.levelProgress"
                :size="36"
                :stroke-width="3"
                :show-label="false"
            />
        </div>
    </div>
</template>

<style scoped>
.animate-bounce-slow {
    animation: bounce-slow 2s ease-in-out infinite;
}

@keyframes bounce-slow {
    0%,
    100% {
        transform: translateY(0);
    }
    50% {
        transform: translateY(-4px);
    }
}
</style>
