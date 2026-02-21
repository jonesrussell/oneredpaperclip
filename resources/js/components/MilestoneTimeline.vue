<script setup lang="ts">
type Milestone = {
    label: string;
    status: 'completed' | 'current' | 'future';
};

defineProps<{
    milestones: Milestone[];
}>();
</script>

<template>
    <div class="flex items-center gap-0 overflow-x-auto py-2">
        <template v-for="(milestone, i) in milestones" :key="i">
            <div class="flex flex-col items-center gap-1.5 px-1">
                <!-- Node -->
                <div
                    class="flex size-8 shrink-0 items-center justify-center rounded-full border-2 text-xs font-bold transition-all"
                    :class="{
                        'border-[var(--electric-mint)] bg-[var(--electric-mint)] text-white':
                            milestone.status === 'completed',
                        'animate-pulse border-[var(--hot-coral)] bg-[var(--hot-coral)]/10 text-[var(--hot-coral)]':
                            milestone.status === 'current',
                        'border-[var(--border)] bg-white text-[var(--ink-muted)]':
                            milestone.status === 'future',
                    }"
                >
                    <span v-if="milestone.status === 'completed'"
                        >&#10003;</span
                    >
                    <span v-else>{{ i + 1 }}</span>
                </div>
                <!-- Label -->
                <span
                    class="max-w-16 truncate text-center text-[10px] leading-tight font-medium"
                    :class="
                        milestone.status === 'current'
                            ? 'text-[var(--hot-coral)]'
                            : 'text-[var(--ink-muted)]'
                    "
                >
                    {{ milestone.label }}
                </span>
            </div>
            <!-- Connector line -->
            <div
                v-if="i < milestones.length - 1"
                class="mb-5 h-0.5 w-8 shrink-0"
                :class="
                    milestone.status === 'completed'
                        ? 'bg-[var(--electric-mint)]'
                        : 'bg-[var(--border)]'
                "
            />
        </template>
    </div>
</template>
