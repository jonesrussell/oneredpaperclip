<script setup lang="ts">
import { computed } from 'vue';

const props = withDefaults(defineProps<{
    percent: number;
    size?: number;
    strokeWidth?: number;
    showLabel?: boolean;
}>(), {
    size: 48,
    strokeWidth: 4,
    showLabel: true,
});

const radius = computed(() => (props.size - props.strokeWidth) / 2);
const circumference = computed(() => 2 * Math.PI * radius.value);
const offset = computed(() => circumference.value - (props.percent / 100) * circumference.value);
const center = computed(() => props.size / 2);

const color = computed(() => {
    if (props.percent <= 33) return 'var(--hot-coral)';
    if (props.percent <= 66) return 'var(--sunny-yellow)';
    return 'var(--electric-mint)';
});
</script>

<template>
    <div class="relative inline-flex items-center justify-center" :style="{ width: `${size}px`, height: `${size}px` }">
        <svg :width="size" :height="size" class="-rotate-90">
            <circle
                :cx="center"
                :cy="center"
                :r="radius"
                fill="none"
                stroke="var(--border)"
                :stroke-width="strokeWidth"
            />
            <circle
                :cx="center"
                :cy="center"
                :r="radius"
                fill="none"
                :stroke="color"
                :stroke-width="strokeWidth"
                stroke-linecap="round"
                :stroke-dasharray="circumference"
                :stroke-dashoffset="offset"
                class="transition-[stroke-dashoffset] duration-700 ease-out"
            />
        </svg>
        <span
            v-if="showLabel"
            class="absolute font-mono text-xs font-semibold"
            :style="{ color, fontSize: `${size * 0.22}px` }"
        >
            {{ percent }}%
        </span>
    </div>
</template>
