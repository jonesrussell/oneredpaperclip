<script setup lang="ts">
import { Check, Lock, Star, Trophy } from 'lucide-vue-next';
import { computed, onMounted, ref } from 'vue';

import ImageLightbox from '@/components/ImageLightbox.vue';
import PaperclipMascot from '@/components/PaperclipMascot.vue';
import { useImageLightbox } from '@/composables/useImageLightbox';

type PathNode = {
    id: string | number;
    type: 'start' | 'trade' | 'goal';
    status: 'completed' | 'current' | 'locked';
    title: string;
    subtitle?: string;
    imageUrl?: string | null;
};

const props = defineProps<{
    nodes: PathNode[];
}>();

const isVisible = ref(false);
onMounted(() => {
    const show = () => {
        isVisible.value = true;
    };
    if (typeof requestAnimationFrame === 'function') {
        requestAnimationFrame(show);
    } else {
        show();
    }
});

// Track broken images so we can fall back to icons
const brokenImages = ref(new Set<string | number>());
function onImageError(nodeId: string | number): void {
    brokenImages.value.add(nodeId);
}

// Layout constants
const NODE_R = 36;
const GOAL_R = 40;
const V_GAP = 140;
const PAD_Y = 52;

// Clean zigzag: left ↔ right
const X_LEFT = 35;
const X_RIGHT = 65;

function xPct(i: number): number {
    return i % 2 === 0 ? X_LEFT : X_RIGHT;
}

function yPx(i: number): number {
    return PAD_Y + i * V_GAP;
}

function r(node: PathNode): number {
    return node.type === 'goal' ? GOAL_R : NODE_R;
}

// Returns the side the LABEL appears on (opposite the node's horizontal position)
function labelSide(i: number): 'left' | 'right' {
    return xPct(i) < 50 ? 'right' : 'left';
}

const mapHeight = computed(() => {
    if (!props.nodes.length) return 200;
    const last = props.nodes.length - 1;
    return yPx(last) + r(props.nodes[last]) + PAD_Y;
});

// SVG viewBox uses x: 0–100 (percentage-like), y: pixel values.
// preserveAspectRatio="none" stretches both axes to fill the container.
// y maps 1:1 to pixels only because the container height matches mapHeight exactly.
const svgViewBox = computed(() => `0 0 100 ${mapHeight.value}`);

// Connector curves between adjacent nodes
const connectors = computed(() => {
    return props.nodes.slice(0, -1).map((node, i) => {
        const next = props.nodes[i + 1];
        const x1 = xPct(i);
        const y1 = yPx(i) + r(node) + 2;
        const x2 = xPct(i + 1);
        const y2 = yPx(i + 1) - r(next) - 2;
        const midY = (y1 + y2) / 2;

        return {
            d: `M ${x1} ${y1} C ${x1} ${midY}, ${x2} ${midY}, ${x2} ${y2}`,
            completed:
                node.status === 'completed' && next.status === 'completed',
            active: node.status === 'completed' && next.status === 'current',
        };
    });
});

function nodeStatusClass(node: PathNode): string {
    if (node.status === 'completed') return 'tpm-node--completed';
    if (node.status === 'current') return 'tpm-node--current';
    if (node.type === 'goal') return 'tpm-node--goal';
    return 'tpm-node--locked';
}

function labelColor(node: PathNode): string {
    if (node.status === 'completed') return 'text-[var(--electric-mint)]';
    if (node.status === 'current' || node.type === 'goal')
        return 'text-[var(--hot-coral)]';
    return 'text-muted-foreground/50';
}

function nodeLabel(node: PathNode, index: number): string {
    if (node.type === 'start') return 'Start';
    if (node.type === 'goal') return 'Goal';
    return `Trade ${index}`;
}

type ConnectorStyle = {
    stroke: string;
    strokeWidth: number;
    dashArray: string;
};

function connectorStyle(c: {
    completed: boolean;
    active: boolean;
}): ConnectorStyle {
    if (c.completed) {
        return {
            stroke: 'var(--electric-mint)',
            strokeWidth: 3.5,
            dashArray: 'none',
        };
    }
    if (c.active) {
        return {
            stroke: 'var(--hot-coral)',
            strokeWidth: 3,
            dashArray: '10 8',
        };
    }
    return { stroke: 'var(--border)', strokeWidth: 2, dashArray: '6 10' };
}

const lastNode = computed(() =>
    props.nodes.length > 0 ? props.nodes[props.nodes.length - 1] : null,
);
const isChallengeComplete = computed(
    () => lastNode.value?.status === 'completed',
);

const {
    open: lightboxOpen,
    imageUrl: lightboxImageUrl,
    title: lightboxTitle,
    openLightbox,
    onOpenChange,
} = useImageLightbox();

function openNodeLightbox(node: PathNode): void {
    if (node.imageUrl) openLightbox(node.imageUrl, node.title);
}
</script>

<template>
    <div
        role="img"
        :aria-label="`Trade journey path with ${nodes.length} steps`"
        class="trade-path-map relative mx-auto w-full max-w-md overflow-hidden"
        :style="{ height: `${mapHeight}px` }"
    >
        <!-- Subtle dot grid for depth -->
        <div
            aria-hidden="true"
            class="pointer-events-none absolute inset-0 opacity-[0.035]"
            style="
                background-image: radial-gradient(
                    circle,
                    currentColor 1px,
                    transparent 1px
                );
                background-size: 20px 20px;
            "
        />

        <!-- SVG connector layer -->
        <svg
            aria-hidden="true"
            class="pointer-events-none absolute inset-0 size-full"
            :viewBox="svgViewBox"
            preserveAspectRatio="none"
            fill="none"
        >
            <template v-for="(c, i) in connectors" :key="`conn-${i}`">
                <!-- Glow behind completed connectors -->
                <path
                    v-if="c.completed"
                    :d="c.d"
                    stroke="var(--electric-mint)"
                    stroke-width="8"
                    stroke-linecap="round"
                    vector-effect="non-scaling-stroke"
                    :opacity="isVisible ? 0.15 : 0"
                    :style="{
                        transition: `opacity 0.8s ease ${i * 0.15 + 0.2}s`,
                    }"
                />

                <!-- Main connector path -->
                <path
                    :d="c.d"
                    :stroke="connectorStyle(c).stroke"
                    :stroke-width="connectorStyle(c).strokeWidth"
                    stroke-linecap="round"
                    :stroke-dasharray="connectorStyle(c).dashArray"
                    vector-effect="non-scaling-stroke"
                    :class="{ 'tpm-dash-animate': c.active }"
                    :opacity="
                        isVisible ? (c.completed || c.active ? 1 : 0.35) : 0
                    "
                    :style="{
                        transition: `opacity 0.6s ease ${i * 0.15 + 0.1}s`,
                    }"
                />
            </template>
        </svg>

        <!-- Nodes -->
        <template v-for="(node, index) in nodes" :key="node.id">
            <!-- Position wrapper (centered on calculated point) -->
            <div
                class="absolute"
                :style="{
                    left: `${xPct(index)}%`,
                    top: `${yPx(index)}px`,
                    transform: 'translate(-50%, -50%)',
                    zIndex: node.status === 'current' ? 20 : 10,
                }"
            >
                <!-- Entrance animation wrapper -->
                <div
                    class="flex items-center gap-3 transition-all duration-500 ease-out"
                    :class="[
                        labelSide(index) === 'left'
                            ? 'flex-row-reverse'
                            : 'flex-row',
                        isVisible
                            ? 'scale-100 opacity-100'
                            : 'scale-75 opacity-0',
                    ]"
                    :style="{
                        transitionDelay: `${index * 0.12 + 0.05}s`,
                    }"
                >
                    <!-- Node circle group -->
                    <div class="relative shrink-0">
                        <!-- Mascot above current node -->
                        <div
                            v-if="node.status === 'current'"
                            class="absolute -top-12 left-1/2 z-20 -translate-x-1/2"
                        >
                            <PaperclipMascot mood="encouraging" :size="40" />
                        </div>

                        <!-- Expanding ping ring for current -->
                        <div
                            v-if="node.status === 'current'"
                            class="tpm-ping absolute top-1/2 left-1/2 rounded-full border-2 border-[var(--hot-coral)]"
                            :style="{
                                width: `${r(node) * 2 + 16}px`,
                                height: `${r(node) * 2 + 16}px`,
                                transform: 'translate(-50%, -50%)',
                            }"
                        />

                        <!-- Main circle -->
                        <div
                            class="relative flex items-center justify-center rounded-full"
                            :class="nodeStatusClass(node)"
                            :style="{
                                width: `${r(node) * 2}px`,
                                height: `${r(node) * 2}px`,
                            }"
                        >
                            <button
                                v-if="
                                    node.imageUrl && !brokenImages.has(node.id)
                                "
                                type="button"
                                class="overflow-hidden rounded-full ring-2 ring-white/20 cursor-pointer transition-opacity hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-[var(--hot-coral)] focus:ring-offset-2 focus:ring-offset-background"
                                :style="{
                                    width: `${r(node) * 2 - 10}px`,
                                    height: `${r(node) * 2 - 10}px`,
                                }"
                                aria-label="View full size"
                                @click="openNodeLightbox(node)"
                            >
                                <img
                                    :src="node.imageUrl"
                                    :alt="node.title"
                                    class="size-full object-cover"
                                    @error="onImageError(node.id)"
                                />
                            </button>
                            <!-- Start icon -->
                            <Star
                                v-else-if="node.type === 'start'"
                                class="size-7 text-white drop-shadow-sm"
                            />
                            <!-- Goal icon -->
                            <Trophy
                                v-else-if="node.type === 'goal'"
                                class="size-8 drop-shadow-sm"
                                :class="
                                    node.status === 'locked'
                                        ? 'text-white/90'
                                        : 'text-white'
                                "
                            />
                            <!-- Locked icon -->
                            <Lock
                                v-else-if="node.status === 'locked'"
                                class="size-5 text-muted-foreground/40"
                            />
                            <!-- Completed check -->
                            <Check
                                v-else-if="node.status === 'completed'"
                                class="size-7 stroke-[3] text-white drop-shadow-sm"
                            />
                            <!-- Fallback (current trade without image) -->
                            <span
                                v-else
                                class="font-display text-lg font-bold text-white"
                            >
                                ?
                            </span>
                        </div>

                        <!-- Completion badge -->
                        <div
                            v-if="node.status === 'completed'"
                            class="absolute -right-0.5 -bottom-0.5 flex size-6 items-center justify-center rounded-full bg-background shadow-md ring-2 ring-background"
                        >
                            <div
                                class="flex size-5 items-center justify-center rounded-full bg-[var(--electric-mint)]"
                            >
                                <Check class="size-3 stroke-[3] text-white" />
                            </div>
                        </div>
                    </div>

                    <!-- Label -->
                    <div
                        class="max-w-[140px]"
                        :class="
                            labelSide(index) === 'left'
                                ? 'text-right'
                                : 'text-left'
                        "
                    >
                        <p
                            class="font-mono text-[10px] font-semibold tracking-[0.15em] uppercase"
                            :class="labelColor(node)"
                        >
                            {{ nodeLabel(node, index) }}
                        </p>
                        <p
                            class="mt-0.5 line-clamp-2 font-display text-sm leading-tight font-bold"
                            :class="
                                node.status === 'locked' && node.type !== 'goal'
                                    ? 'text-muted-foreground/40'
                                    : 'text-foreground'
                            "
                        >
                            {{ node.title }}
                        </p>
                        <p
                            v-if="node.subtitle"
                            class="mt-0.5 line-clamp-1 text-[11px] text-muted-foreground"
                        >
                            {{ node.subtitle }}
                        </p>
                    </div>
                </div>
            </div>
        </template>

        <!-- Celebration mascot when challenge is complete -->
        <div
            v-if="isChallengeComplete && lastNode"
            class="absolute left-1/2 -translate-x-1/2"
            :style="{
                top: `${yPx(nodes.length - 1) + r(lastNode) + 24}px`,
            }"
        >
            <PaperclipMascot mood="celebrating" :size="80" />
        </div>

        <ImageLightbox
            v-if="lightboxImageUrl"
            :open="lightboxOpen"
            :image-url="lightboxImageUrl"
            :title="lightboxTitle"
            @update:open="onOpenChange"
        />
    </div>
</template>

<style scoped>
/* ── Node status styles ── */

.tpm-node--completed {
    background: linear-gradient(145deg, var(--electric-mint), hsl(88 62% 32%));
    box-shadow:
        inset 0 2px 4px rgba(255, 255, 255, 0.25),
        inset 0 -2px 4px rgba(0, 0, 0, 0.12),
        0 0 20px rgba(76, 175, 80, 0.25),
        0 4px 6px rgba(0, 0, 0, 0.08);
}

.tpm-node--current {
    background: linear-gradient(145deg, var(--hot-coral), hsl(0 100% 55%));
    box-shadow:
        inset 0 2px 4px rgba(255, 255, 255, 0.3),
        inset 0 -2px 4px rgba(0, 0, 0, 0.15),
        0 0 28px rgba(255, 100, 100, 0.3),
        0 4px 8px rgba(0, 0, 0, 0.1);
    animation: tpm-breathe 2.5s ease-in-out infinite;
}

.tpm-node--goal {
    background: linear-gradient(145deg, var(--hot-coral), hsl(0 80% 50%));
    box-shadow:
        inset 0 2px 4px rgba(255, 255, 255, 0.2),
        inset 0 -2px 4px rgba(0, 0, 0, 0.15),
        0 0 16px rgba(255, 100, 100, 0.2),
        0 4px 6px rgba(0, 0, 0, 0.08);
}

.tpm-node--locked {
    background: var(--muted);
    box-shadow:
        inset 0 1px 2px rgba(255, 255, 255, 0.05),
        inset 0 -1px 2px rgba(0, 0, 0, 0.08);
    border: 2px dashed var(--border);
}

/* ── Animations ── */

@keyframes tpm-breathe {
    0%,
    100% {
        box-shadow:
            inset 0 2px 4px rgba(255, 255, 255, 0.3),
            inset 0 -2px 4px rgba(0, 0, 0, 0.15),
            0 0 20px rgba(255, 100, 100, 0.25),
            0 4px 8px rgba(0, 0, 0, 0.1);
    }
    50% {
        box-shadow:
            inset 0 2px 4px rgba(255, 255, 255, 0.3),
            inset 0 -2px 4px rgba(0, 0, 0, 0.15),
            0 0 40px rgba(255, 100, 100, 0.45),
            0 6px 12px rgba(0, 0, 0, 0.12);
    }
}

@keyframes tpm-ping {
    0% {
        transform: translate(-50%, -50%) scale(1);
        opacity: 0.6;
    }
    75%,
    100% {
        transform: translate(-50%, -50%) scale(1.5);
        opacity: 0;
    }
}

.tpm-ping {
    animation: tpm-ping 2s cubic-bezier(0, 0, 0.2, 1) infinite;
}

@keyframes tpm-dash {
    to {
        stroke-dashoffset: -36;
    }
}

.tpm-dash-animate {
    animation: tpm-dash 1.2s linear infinite;
}

/* ── Accessibility ── */

@media (prefers-reduced-motion: reduce) {
    .tpm-node--current {
        animation: none;
    }

    .tpm-ping {
        animation: none;
        display: none;
    }

    .tpm-dash-animate {
        animation: none;
    }
}
</style>
