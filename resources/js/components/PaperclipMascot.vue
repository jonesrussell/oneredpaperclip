<script setup lang="ts">
import { computed } from 'vue';

type MascotMood =
    | 'happy'
    | 'excited'
    | 'encouraging'
    | 'sleeping'
    | 'thinking'
    | 'celebrating';

const props = withDefaults(
    defineProps<{
        mood?: MascotMood;
        size?: number;
        animate?: boolean;
    }>(),
    {
        mood: 'happy',
        size: 64,
        animate: true,
    },
);

const animationClass = computed(() => {
    if (!props.animate) return '';
    switch (props.mood) {
        case 'excited':
        case 'celebrating':
            return 'animate-bounce-mascot';
        case 'encouraging':
            return 'animate-wiggle-mascot';
        case 'sleeping':
            return 'animate-breathe-mascot';
        case 'thinking':
            return 'animate-tilt-mascot';
        default:
            return 'animate-float-mascot';
    }
});

const eyeStyle = computed(() => {
    switch (props.mood) {
        case 'sleeping':
            return 'closed';
        case 'excited':
        case 'celebrating':
            return 'sparkle';
        case 'thinking':
            return 'looking-up';
        default:
            return 'normal';
    }
});

const mouthStyle = computed(() => {
    switch (props.mood) {
        case 'sleeping':
            return 'zzz';
        case 'excited':
        case 'celebrating':
            return 'open-smile';
        case 'encouraging':
            return 'cheer';
        case 'thinking':
            return 'hmm';
        default:
            return 'smile';
    }
});

const accessory = computed(() => {
    switch (props.mood) {
        case 'celebrating':
            return 'party-hat';
        case 'encouraging':
            return 'pom-poms';
        default:
            return null;
    }
});
</script>

<template>
    <div
        class="paperclip-mascot relative inline-flex items-center justify-center"
        :class="animationClass"
        :style="{ width: `${size}px`, height: `${size}px` }"
    >
        <svg
            :width="size"
            :height="size"
            viewBox="0 0 64 64"
            fill="none"
            xmlns="http://www.w3.org/2000/svg"
        >
            <!-- Glow effect for excited/celebrating -->
            <defs>
                <filter id="glow" x="-50%" y="-50%" width="200%" height="200%">
                    <feGaussianBlur stdDeviation="2" result="coloredBlur" />
                    <feMerge>
                        <feMergeNode in="coloredBlur" />
                        <feMergeNode in="SourceGraphic" />
                    </feMerge>
                </filter>
                <linearGradient
                    id="clipGradient"
                    x1="0%"
                    y1="0%"
                    x2="100%"
                    y2="100%"
                >
                    <stop offset="0%" stop-color="#ef4444" />
                    <stop offset="100%" stop-color="#dc2626" />
                </linearGradient>
                <linearGradient
                    id="shineGradient"
                    x1="0%"
                    y1="0%"
                    x2="100%"
                    y2="100%"
                >
                    <stop offset="0%" stop-color="#fff" stop-opacity="0.4" />
                    <stop offset="50%" stop-color="#fff" stop-opacity="0" />
                </linearGradient>
            </defs>

            <!-- Party hat accessory -->
            <g v-if="accessory === 'party-hat'" class="animate-wiggle-slow">
                <polygon
                    points="32,2 26,18 38,18"
                    fill="#fbbf24"
                    stroke="#f59e0b"
                    stroke-width="1"
                />
                <circle cx="32" cy="4" r="3" fill="#ec4899" />
                <circle cx="28" cy="12" r="1.5" fill="#8b5cf6" />
                <circle cx="36" cy="10" r="1.5" fill="#06b6d4" />
            </g>

            <!-- Pom-poms accessory -->
            <g v-if="accessory === 'pom-poms'" class="animate-shake">
                <circle cx="12" cy="28" r="6" fill="#ec4899" opacity="0.9" />
                <circle cx="52" cy="28" r="6" fill="#06b6d4" opacity="0.9" />
                <line
                    x1="18"
                    y1="28"
                    x2="24"
                    y2="32"
                    stroke="#a855f7"
                    stroke-width="2"
                />
                <line
                    x1="46"
                    y1="28"
                    x2="40"
                    y2="32"
                    stroke="#a855f7"
                    stroke-width="2"
                />
            </g>

            <!-- Main paperclip body -->
            <g
                :filter="
                    mood === 'excited' || mood === 'celebrating'
                        ? 'url(#glow)'
                        : undefined
                "
            >
                <!-- Outer loop -->
                <path
                    d="M24 52 C24 56, 40 56, 40 52 L40 20 C40 14, 24 14, 24 20 L24 44 C24 48, 32 48, 32 44 L32 24"
                    stroke="url(#clipGradient)"
                    stroke-width="5"
                    stroke-linecap="round"
                    fill="none"
                />
                <!-- Shine highlight -->
                <path
                    d="M26 50 C26 53, 38 53, 38 50 L38 22 C38 17, 26 17, 26 22"
                    stroke="url(#shineGradient)"
                    stroke-width="2"
                    stroke-linecap="round"
                    fill="none"
                />
            </g>

            <!-- Face container -->
            <g transform="translate(32, 34)">
                <!-- Eyes -->
                <g v-if="eyeStyle === 'normal'">
                    <circle cx="-6" cy="-4" r="3" fill="#1f2937" />
                    <circle cx="6" cy="-4" r="3" fill="#1f2937" />
                    <circle cx="-5" cy="-5" r="1" fill="white" />
                    <circle cx="7" cy="-5" r="1" fill="white" />
                </g>
                <g v-else-if="eyeStyle === 'closed'">
                    <path
                        d="M-9 -4 Q-6 -2 -3 -4"
                        stroke="#1f2937"
                        stroke-width="2"
                        fill="none"
                        stroke-linecap="round"
                    />
                    <path
                        d="M3 -4 Q6 -2 9 -4"
                        stroke="#1f2937"
                        stroke-width="2"
                        fill="none"
                        stroke-linecap="round"
                    />
                </g>
                <g v-else-if="eyeStyle === 'sparkle'" class="animate-sparkle">
                    <circle cx="-6" cy="-4" r="3.5" fill="#1f2937" />
                    <circle cx="6" cy="-4" r="3.5" fill="#1f2937" />
                    <circle cx="-5" cy="-5" r="1.5" fill="white" />
                    <circle cx="7" cy="-5" r="1.5" fill="white" />
                    <circle cx="-7" cy="-3" r="0.8" fill="white" />
                    <circle cx="5" cy="-3" r="0.8" fill="white" />
                </g>
                <g v-else-if="eyeStyle === 'looking-up'">
                    <circle cx="-6" cy="-4" r="3" fill="#1f2937" />
                    <circle cx="6" cy="-4" r="3" fill="#1f2937" />
                    <circle cx="-5" cy="-6" r="1" fill="white" />
                    <circle cx="7" cy="-6" r="1" fill="white" />
                </g>

                <!-- Mouth -->
                <g v-if="mouthStyle === 'smile'">
                    <path
                        d="M-4 4 Q0 8 4 4"
                        stroke="#1f2937"
                        stroke-width="2"
                        fill="none"
                        stroke-linecap="round"
                    />
                </g>
                <g v-else-if="mouthStyle === 'open-smile'">
                    <ellipse cx="0" cy="5" rx="5" ry="4" fill="#1f2937" />
                    <ellipse cx="0" cy="7" rx="3" ry="2" fill="#ec4899" />
                </g>
                <g v-else-if="mouthStyle === 'cheer'">
                    <ellipse cx="0" cy="5" rx="6" ry="5" fill="#1f2937" />
                    <ellipse cx="0" cy="7" rx="4" ry="2.5" fill="#ec4899" />
                </g>
                <g v-else-if="mouthStyle === 'hmm'">
                    <circle cx="3" cy="4" r="2" fill="#1f2937" />
                </g>
                <g v-else-if="mouthStyle === 'zzz'" class="animate-fade-zzz">
                    <text
                        x="8"
                        y="0"
                        font-size="8"
                        fill="#64748b"
                        font-family="var(--font-display)"
                    >
                        z
                    </text>
                    <text
                        x="14"
                        y="-6"
                        font-size="6"
                        fill="#64748b"
                        font-family="var(--font-display)"
                        opacity="0.7"
                    >
                        z
                    </text>
                    <text
                        x="18"
                        y="-10"
                        font-size="5"
                        fill="#64748b"
                        font-family="var(--font-display)"
                        opacity="0.5"
                    >
                        z
                    </text>
                </g>

                <!-- Blush for happy moods -->
                <g
                    v-if="
                        mood === 'happy' ||
                        mood === 'excited' ||
                        mood === 'celebrating'
                    "
                    opacity="0.4"
                >
                    <ellipse cx="-10" cy="2" rx="3" ry="2" fill="#fca5a5" />
                    <ellipse cx="10" cy="2" rx="3" ry="2" fill="#fca5a5" />
                </g>
            </g>

            <!-- Celebration sparkles -->
            <g v-if="mood === 'celebrating'" class="animate-sparkle-burst">
                <circle cx="12" cy="12" r="2" fill="#fbbf24" />
                <circle cx="52" cy="16" r="1.5" fill="#ec4899" />
                <circle cx="8" cy="40" r="1.5" fill="#8b5cf6" />
                <circle cx="56" cy="44" r="2" fill="#06b6d4" />
                <polygon
                    points="48,8 50,12 54,12 51,15 52,19 48,16 44,19 45,15 42,12 46,12"
                    fill="#fbbf24"
                />
            </g>
        </svg>
    </div>
</template>

<style scoped>
.animate-bounce-mascot {
    animation: bounce-mascot 0.6s ease-in-out infinite;
}

.animate-wiggle-mascot {
    animation: wiggle-mascot 0.4s ease-in-out infinite;
}

.animate-breathe-mascot {
    animation: breathe-mascot 3s ease-in-out infinite;
}

.animate-float-mascot {
    animation: float-mascot 3s ease-in-out infinite;
}

.animate-tilt-mascot {
    animation: tilt-mascot 2s ease-in-out infinite;
}

.animate-wiggle-slow {
    animation: wiggle-mascot 1s ease-in-out infinite;
}

.animate-shake {
    animation: shake-mascot 0.3s ease-in-out infinite;
}

.animate-sparkle {
    animation: sparkle-mascot 1s ease-in-out infinite;
}

.animate-sparkle-burst {
    animation: sparkle-burst 1.5s ease-in-out infinite;
}

.animate-fade-zzz {
    animation: fade-zzz 2s ease-in-out infinite;
}

@keyframes bounce-mascot {
    0%,
    100% {
        transform: translateY(0);
    }
    50% {
        transform: translateY(-8px);
    }
}

@keyframes wiggle-mascot {
    0%,
    100% {
        transform: rotate(-5deg);
    }
    50% {
        transform: rotate(5deg);
    }
}

@keyframes breathe-mascot {
    0%,
    100% {
        transform: scale(1);
    }
    50% {
        transform: scale(0.95);
    }
}

@keyframes float-mascot {
    0%,
    100% {
        transform: translateY(0);
    }
    50% {
        transform: translateY(-4px);
    }
}

@keyframes tilt-mascot {
    0%,
    100% {
        transform: rotate(0deg);
    }
    25% {
        transform: rotate(-8deg);
    }
    75% {
        transform: rotate(8deg);
    }
}

@keyframes shake-mascot {
    0%,
    100% {
        transform: translateX(0);
    }
    25% {
        transform: translateX(-2px);
    }
    75% {
        transform: translateX(2px);
    }
}

@keyframes sparkle-mascot {
    0%,
    100% {
        opacity: 1;
    }
    50% {
        opacity: 0.7;
    }
}

@keyframes sparkle-burst {
    0%,
    100% {
        opacity: 1;
        transform: scale(1);
    }
    50% {
        opacity: 0.6;
        transform: scale(1.1);
    }
}

@keyframes fade-zzz {
    0%,
    100% {
        opacity: 0.3;
    }
    50% {
        opacity: 1;
    }
}
</style>
