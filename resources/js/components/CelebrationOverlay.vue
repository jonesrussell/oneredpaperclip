<script setup lang="ts">
import { onMounted, onUnmounted, ref, watch } from 'vue';

import PaperclipMascot from '@/components/PaperclipMascot.vue';
import { Button } from '@/components/ui/button';

type CelebrationType = 'xp' | 'level-up' | 'trade' | 'campaign-complete';

const props = withDefaults(
    defineProps<{
        show: boolean;
        type: CelebrationType;
        xpGained?: number;
        newLevel?: number;
        message?: string;
    }>(),
    {
        xpGained: 0,
        newLevel: undefined,
        message: undefined,
    },
);

const emit = defineEmits<{
    close: [];
}>();

const confettiPieces = ref<
    Array<{
        id: number;
        x: number;
        color: string;
        delay: number;
        rotation: number;
        size: number;
    }>
>([]);

const floatingXp = ref<
    Array<{
        id: number;
        x: number;
        value: number;
        delay: number;
    }>
>([]);

const colors = [
    '#ef4444',
    '#f59e0b',
    '#10b981',
    '#3b82f6',
    '#8b5cf6',
    '#ec4899',
    '#06b6d4',
];

function generateConfetti(): void {
    confettiPieces.value = Array.from({ length: 50 }, (_, i) => ({
        id: i,
        x: Math.random() * 100,
        color: colors[Math.floor(Math.random() * colors.length)],
        delay: Math.random() * 0.5,
        rotation: Math.random() * 360,
        size: Math.random() * 8 + 4,
    }));
}

function generateFloatingXp(): void {
    if (props.xpGained <= 0) return;

    const numPieces = Math.min(Math.ceil(props.xpGained / 20), 8);
    const valuePerPiece = Math.ceil(props.xpGained / numPieces);

    floatingXp.value = Array.from({ length: numPieces }, (_, i) => ({
        id: i,
        x: 30 + Math.random() * 40,
        value:
            i === numPieces - 1
                ? props.xpGained - valuePerPiece * i
                : valuePerPiece,
        delay: i * 0.15,
    }));
}

let autoCloseTimeout: ReturnType<typeof setTimeout> | null = null;

watch(
    () => props.show,
    (newVal) => {
        if (newVal) {
            generateConfetti();
            generateFloatingXp();

            if (props.type === 'xp') {
                autoCloseTimeout = setTimeout(() => {
                    emit('close');
                }, 2000);
            }
        } else {
            confettiPieces.value = [];
            floatingXp.value = [];
        }
    },
);

onMounted(() => {
    if (props.show) {
        generateConfetti();
        generateFloatingXp();
    }
});

onUnmounted(() => {
    if (autoCloseTimeout) {
        clearTimeout(autoCloseTimeout);
    }
});

function handleClose(): void {
    emit('close');
}

function getTitle(): string {
    switch (props.type) {
        case 'level-up':
            return `Level ${props.newLevel}!`;
        case 'trade':
            return 'Trade Complete!';
        case 'campaign-complete':
            return 'Challenge Complete!';
        default:
            return '';
    }
}

function getMascotMood(): 'celebrating' | 'excited' | 'happy' {
    switch (props.type) {
        case 'level-up':
        case 'campaign-complete':
            return 'celebrating';
        case 'trade':
            return 'excited';
        default:
            return 'happy';
    }
}
</script>

<template>
    <Teleport to="body">
        <Transition name="celebration">
            <div
                v-if="show"
                class="fixed inset-0 z-50 flex items-center justify-center"
            >
                <!-- Backdrop -->
                <div
                    class="absolute inset-0 bg-background/80 backdrop-blur-sm"
                    @click="type !== 'xp' ? handleClose() : undefined"
                />

                <!-- Confetti layer -->
                <div
                    class="pointer-events-none absolute inset-0 overflow-hidden"
                >
                    <div
                        v-for="piece in confettiPieces"
                        :key="piece.id"
                        class="confetti-piece absolute"
                        :style="{
                            left: `${piece.x}%`,
                            backgroundColor: piece.color,
                            width: `${piece.size}px`,
                            height: `${piece.size * 0.6}px`,
                            animationDelay: `${piece.delay}s`,
                            transform: `rotate(${piece.rotation}deg)`,
                        }"
                    />
                </div>

                <!-- Floating XP numbers -->
                <div
                    class="pointer-events-none absolute inset-0 overflow-hidden"
                >
                    <div
                        v-for="xp in floatingXp"
                        :key="xp.id"
                        class="floating-xp absolute font-display text-2xl font-bold text-[var(--sunny-yellow)]"
                        :style="{
                            left: `${xp.x}%`,
                            animationDelay: `${xp.delay}s`,
                        }"
                    >
                        +{{ xp.value }} XP
                    </div>
                </div>

                <!-- Modal content (for level-up, trade, campaign-complete) -->
                <div
                    v-if="type !== 'xp'"
                    class="celebration-modal relative z-10 mx-4 w-full max-w-sm rounded-3xl border border-border bg-card p-8 text-center shadow-2xl"
                >
                    <!-- Mascot -->
                    <div class="mb-4 flex justify-center">
                        <PaperclipMascot :mood="getMascotMood()" :size="96" />
                    </div>

                    <!-- Title -->
                    <h2
                        class="font-display text-3xl font-bold tracking-tight text-foreground"
                    >
                        {{ getTitle() }}
                    </h2>

                    <!-- Message -->
                    <p v-if="message" class="mt-2 text-muted-foreground">
                        {{ message }}
                    </p>

                    <!-- XP gained badge -->
                    <div
                        v-if="xpGained > 0"
                        class="mt-4 inline-flex items-center gap-2 rounded-full bg-[var(--sunny-yellow)]/20 px-4 py-2"
                    >
                        <span
                            class="font-mono text-lg font-bold text-[var(--sunny-yellow)]"
                        >
                            +{{ xpGained }} XP
                        </span>
                    </div>

                    <!-- Level up badge -->
                    <div v-if="type === 'level-up' && newLevel" class="mt-4">
                        <div
                            class="mx-auto flex size-20 items-center justify-center rounded-full bg-gradient-to-br from-violet-500 to-purple-600 font-display text-3xl font-bold text-white shadow-lg"
                        >
                            {{ newLevel }}
                        </div>
                    </div>

                    <!-- Continue button -->
                    <Button
                        class="mt-6 w-full bg-[var(--brand-red)] text-white hover:bg-[var(--brand-red-hover)]"
                        size="lg"
                        @click="handleClose"
                    >
                        Continue
                    </Button>
                </div>

                <!-- XP-only toast (auto-closes) -->
                <div
                    v-else
                    class="xp-toast pointer-events-none relative z-10 rounded-2xl bg-gradient-to-r from-[var(--sunny-yellow)] to-amber-500 px-6 py-4 text-center shadow-2xl"
                >
                    <span class="font-display text-2xl font-bold text-white">
                        +{{ xpGained }} XP
                    </span>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<style scoped>
.celebration-enter-active {
    animation: celebration-in 0.4s ease-out;
}

.celebration-leave-active {
    animation: celebration-out 0.3s ease-in;
}

@keyframes celebration-in {
    from {
        opacity: 0;
        transform: scale(0.8);
    }
    to {
        opacity: 1;
        transform: scale(1);
    }
}

@keyframes celebration-out {
    from {
        opacity: 1;
        transform: scale(1);
    }
    to {
        opacity: 0;
        transform: scale(0.8);
    }
}

.celebration-modal {
    animation: modal-bounce-in 0.5s cubic-bezier(0.34, 1.56, 0.64, 1);
}

@keyframes modal-bounce-in {
    0% {
        opacity: 0;
        transform: scale(0.5) translateY(20px);
    }
    60% {
        transform: scale(1.05) translateY(-5px);
    }
    100% {
        opacity: 1;
        transform: scale(1) translateY(0);
    }
}

.xp-toast {
    animation:
        xp-toast-in 0.4s cubic-bezier(0.34, 1.56, 0.64, 1),
        xp-toast-out 0.3s ease-in 1.5s forwards;
}

@keyframes xp-toast-in {
    from {
        opacity: 0;
        transform: translateY(20px) scale(0.9);
    }
    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

@keyframes xp-toast-out {
    from {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
    to {
        opacity: 0;
        transform: translateY(-20px) scale(0.9);
    }
}

.confetti-piece {
    animation: confetti-fall 2.5s ease-out forwards;
    border-radius: 2px;
}

@keyframes confetti-fall {
    0% {
        top: -10%;
        opacity: 1;
        transform: translateX(0) rotate(0deg);
    }
    100% {
        top: 110%;
        opacity: 0;
        transform: translateX(calc(var(--drift, 50px) - 25px)) rotate(720deg);
    }
}

.confetti-piece:nth-child(odd) {
    --drift: 80px;
}

.confetti-piece:nth-child(even) {
    --drift: 20px;
}

.floating-xp {
    top: 60%;
    animation: float-up 1.5s ease-out forwards;
    text-shadow:
        0 2px 4px rgba(0, 0, 0, 0.3),
        0 0 20px rgba(251, 191, 36, 0.5);
}

@keyframes float-up {
    0% {
        opacity: 0;
        transform: translateY(0) scale(0.5);
    }
    20% {
        opacity: 1;
        transform: translateY(-20px) scale(1.1);
    }
    100% {
        opacity: 0;
        transform: translateY(-150px) scale(0.8);
    }
}
</style>
