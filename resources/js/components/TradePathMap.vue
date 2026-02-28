<script setup lang="ts">
import { ImageIcon, Lock, Star, Trophy } from 'lucide-vue-next';

import PaperclipMascot from '@/components/PaperclipMascot.vue';

type PathNode = {
    id: string | number;
    type: 'start' | 'trade' | 'goal';
    status: 'completed' | 'current' | 'locked';
    title: string;
    subtitle?: string;
    imageUrl?: string | null;
};

defineProps<{
    nodes: PathNode[];
}>();

const getNodePosition = (index: number): 'left' | 'center' | 'right' => {
    const positions: ('left' | 'center' | 'right')[] = [
        'center',
        'right',
        'center',
        'left',
    ];
    return positions[index % 4];
};

const getNodeClasses = (
    node: PathNode,
): { ring: string; bg: string; glow: string } => {
    switch (node.status) {
        case 'completed':
            return {
                ring: 'ring-4 ring-[var(--electric-mint)] ring-offset-2 ring-offset-background',
                bg: 'bg-[var(--electric-mint)]',
                glow: 'shadow-[0_0_20px_rgba(52,211,153,0.5)]',
            };
        case 'current':
            return {
                ring: 'ring-4 ring-[var(--hot-coral)] ring-offset-2 ring-offset-background animate-glow-pulse-coral',
                bg: 'bg-[var(--hot-coral)]',
                glow: 'shadow-[0_0_30px_rgba(251,146,60,0.6)]',
            };
        case 'locked':
        default:
            return {
                ring: 'ring-2 ring-border ring-offset-2 ring-offset-background',
                bg: 'bg-muted',
                glow: '',
            };
    }
};

const getConnectorStyle = (fromNode: PathNode): string => {
    if (fromNode.status === 'completed') {
        return 'stroke-[var(--electric-mint)]';
    }
    return 'stroke-border';
};
</script>

<template>
    <div class="trade-path-map relative w-full py-6">
        <div class="relative mx-auto max-w-md">
            <template v-for="(node, index) in nodes" :key="node.id">
                <!-- Connector line to next node -->
                <svg
                    v-if="index < nodes.length - 1"
                    class="connector-svg absolute left-1/2 z-0 h-20 w-40 -translate-x-1/2"
                    :style="{ top: `${index * 140 + 80}px` }"
                    viewBox="0 0 160 80"
                    fill="none"
                    preserveAspectRatio="none"
                >
                    <path
                        :d="
                            getNodePosition(index) === 'left'
                                ? 'M40 0 Q40 40 80 40 Q120 40 120 80'
                                : getNodePosition(index) === 'right'
                                  ? 'M120 0 Q120 40 80 40 Q40 40 40 80'
                                  : getNodePosition(index + 1) === 'right'
                                    ? 'M80 0 Q80 40 120 80'
                                    : 'M80 0 Q80 40 40 80'
                        "
                        :class="getConnectorStyle(node)"
                        stroke-width="3"
                        stroke-dasharray="8 6"
                        fill="none"
                        stroke-linecap="round"
                    />
                </svg>

                <!-- Node -->
                <div
                    class="node-container relative z-10 flex items-center gap-4"
                    :class="{
                        'justify-start pl-4': getNodePosition(index) === 'left',
                        'justify-center': getNodePosition(index) === 'center',
                        'justify-end pr-4': getNodePosition(index) === 'right',
                    }"
                    :style="{
                        marginTop: index === 0 ? '0' : '60px',
                    }"
                >
                    <!-- Node circle -->
                    <div class="relative">
                        <!-- Mascot for current node -->
                        <div
                            v-if="node.status === 'current'"
                            class="absolute -top-12 left-1/2 z-20 -translate-x-1/2"
                        >
                            <PaperclipMascot mood="encouraging" :size="48" />
                        </div>

                        <div
                            class="node-circle flex size-16 items-center justify-center rounded-full transition-all duration-300"
                            :class="[
                                getNodeClasses(node).ring,
                                getNodeClasses(node).bg,
                                getNodeClasses(node).glow,
                            ]"
                        >
                            <!-- Icon or image -->
                            <div
                                v-if="node.imageUrl"
                                class="size-14 overflow-hidden rounded-full"
                            >
                                <img
                                    :src="node.imageUrl"
                                    :alt="node.title"
                                    class="size-full object-cover"
                                />
                            </div>
                            <div
                                v-else-if="node.type === 'start'"
                                class="flex size-full items-center justify-center"
                            >
                                <Star
                                    class="size-7"
                                    :class="
                                        node.status === 'completed'
                                            ? 'text-white'
                                            : 'text-muted-foreground'
                                    "
                                />
                            </div>
                            <div
                                v-else-if="node.type === 'goal'"
                                class="flex size-full items-center justify-center"
                            >
                                <Trophy
                                    class="size-7"
                                    :class="
                                        node.status === 'completed'
                                            ? 'text-white'
                                            : node.status === 'current'
                                              ? 'text-white'
                                              : 'text-muted-foreground'
                                    "
                                />
                            </div>
                            <div
                                v-else-if="node.status === 'locked'"
                                class="flex size-full items-center justify-center"
                            >
                                <Lock class="size-6 text-muted-foreground" />
                            </div>
                            <div
                                v-else-if="node.status === 'completed'"
                                class="flex size-full items-center justify-center text-2xl font-bold text-white"
                            >
                                ✓
                            </div>
                            <div
                                v-else
                                class="flex size-full items-center justify-center"
                            >
                                <ImageIcon
                                    class="size-6 text-muted-foreground"
                                />
                            </div>
                        </div>

                        <!-- Completion badge -->
                        <div
                            v-if="node.status === 'completed'"
                            class="absolute -right-1 -bottom-1 flex size-6 items-center justify-center rounded-full bg-[var(--electric-mint)] text-xs font-bold text-white shadow-lg"
                        >
                            ✓
                        </div>

                        <!-- Current indicator pulse -->
                        <div
                            v-if="node.status === 'current'"
                            class="absolute inset-0 animate-ping rounded-full bg-[var(--hot-coral)] opacity-30"
                        />
                    </div>

                    <!-- Label -->
                    <div
                        class="node-label max-w-32"
                        :class="{
                            'text-left': getNodePosition(index) !== 'right',
                            'order-first text-right':
                                getNodePosition(index) === 'right',
                        }"
                    >
                        <p
                            class="text-xs font-medium tracking-wide uppercase"
                            :class="
                                node.status === 'current'
                                    ? 'text-[var(--hot-coral)]'
                                    : node.status === 'completed'
                                      ? 'text-[var(--electric-mint)]'
                                      : 'text-muted-foreground'
                            "
                        >
                            {{
                                node.type === 'start'
                                    ? 'Start'
                                    : node.type === 'goal'
                                      ? 'Goal'
                                      : `Trade ${index}`
                            }}
                        </p>
                        <p
                            class="line-clamp-2 font-display text-sm font-semibold"
                            :class="
                                node.status === 'locked'
                                    ? 'text-muted-foreground'
                                    : 'text-foreground'
                            "
                        >
                            {{ node.title }}
                        </p>
                        <p
                            v-if="node.subtitle"
                            class="mt-0.5 line-clamp-1 text-xs text-muted-foreground"
                        >
                            {{ node.subtitle }}
                        </p>
                    </div>
                </div>
            </template>
        </div>

        <!-- Celebration mascot at the end when completed -->
        <div
            v-if="
                nodes.length > 0 &&
                nodes[nodes.length - 1].status === 'completed'
            "
            class="mt-8 flex justify-center"
        >
            <PaperclipMascot mood="celebrating" :size="80" />
        </div>
    </div>
</template>

<style scoped>
.animate-glow-pulse-coral {
    animation: glow-pulse-coral 2s ease-in-out infinite;
}

@keyframes glow-pulse-coral {
    0%,
    100% {
        box-shadow: 0 0 20px rgba(251, 146, 60, 0.4);
    }
    50% {
        box-shadow: 0 0 40px rgba(251, 146, 60, 0.7);
    }
}

.connector-svg {
    pointer-events: none;
}
</style>
