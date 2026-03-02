<script setup lang="ts">
import { computed, ref, watch } from 'vue';

import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogTitle,
} from '@/components/ui/dialog';

const props = withDefaults(
    defineProps<{
        open: boolean;
        imageUrl: string;
        title?: string;
    }>(),
    { title: '' },
);

const emit = defineEmits<{
    'update:open': [value: boolean];
}>();

const isOpen = computed({
    get: () => props.open,
    set: (value) => emit('update:open', value),
});

const scale = ref(1);
const translateX = ref(0);
const translateY = ref(0);

function resetTransform(): void {
    scale.value = 1;
    translateX.value = 0;
    translateY.value = 0;
}

watch(
    () => [props.open, props.imageUrl],
    () => {
        if (props.open) resetTransform();
    },
);

let lastPinchDistance = 0;
let lastPinchScale = 1;
let lastPanPoint = { x: 0, y: 0 };
let isPanning = false;
let lastTapTime = 0;

function getTouchDistance(touches: TouchList): number {
    if (touches.length < 2) return 0;
    const a = touches[0];
    const b = touches[1];
    return Math.hypot(b.clientX - a.clientX, b.clientY - a.clientY);
}

function onTouchStart(e: TouchEvent): void {
    if (e.touches.length === 2) {
        lastPinchDistance = getTouchDistance(e.touches);
        lastPinchScale = scale.value;
    } else if (e.touches.length === 1) {
        isPanning = true;
        lastPanPoint = { x: e.touches[0].clientX, y: e.touches[0].clientY };
    }
}

function onTouchMove(e: TouchEvent): void {
    if (e.touches.length === 2 && lastPinchDistance > 0) {
        e.preventDefault();
        const dist = getTouchDistance(e.touches);
        const delta = dist / lastPinchDistance;
        scale.value = Math.min(4, Math.max(0.5, lastPinchScale * delta));
    } else if (e.touches.length === 1 && isPanning) {
        e.preventDefault();
        const dx = e.touches[0].clientX - lastPanPoint.x;
        const dy = e.touches[0].clientY - lastPanPoint.y;
        translateX.value += dx;
        translateY.value += dy;
        lastPanPoint = { x: e.touches[0].clientX, y: e.touches[0].clientY };
    }
}

function onTouchEnd(): void {
    lastPinchDistance = 0;
    isPanning = false;
}

function onWheel(e: WheelEvent): void {
    e.preventDefault();
    const delta = e.deltaY > 0 ? -0.15 : 0.15;
    const newScale = Math.min(4, Math.max(0.5, scale.value + delta));
    scale.value = newScale;
}

function onDoubleTap(): void {
    const now = Date.now();
    if (now - lastTapTime < 350) {
        resetTransform();
    }
    lastTapTime = now;
}

function onMouseDown(e: MouseEvent): void {
    if (e.button !== 0) return;
    isPanning = true;
    lastPanPoint = { x: e.clientX, y: e.clientY };
}

function onMouseMove(e: MouseEvent): void {
    if (!isPanning) return;
    const dx = e.clientX - lastPanPoint.x;
    const dy = e.clientY - lastPanPoint.y;
    translateX.value += dx;
    translateY.value += dy;
    lastPanPoint = { x: e.clientX, y: e.clientY };
}

function onMouseUp(): void {
    isPanning = false;
}

const imageStyle = computed(() => ({
    transform: `translate(${translateX.value}px, ${translateY.value}px) scale(${scale.value})`,
}));
</script>

<template>
    <Dialog v-model:open="isOpen">
        <DialogContent class="sm:max-w-4xl border-0 bg-transparent p-2 shadow-none">
            <DialogTitle class="sr-only">
                {{ title || 'Enlarged image' }}
            </DialogTitle>
            <div class="flex flex-col items-center gap-2">
                <div
                    class="overflow-hidden rounded-lg"
                    style="touch-action: none"
                    @touchstart.passive="onTouchStart"
                    @touchmove="onTouchMove"
                    @touchend="onTouchEnd"
                    @touchcancel="onTouchEnd"
                    @wheel.prevent="onWheel"
                    @dblclick="onDoubleTap"
                    @mousedown="onMouseDown"
                    @mousemove="onMouseMove"
                    @mouseup="onMouseUp"
                    @mouseleave="onMouseUp"
                >
                    <img
                        :src="imageUrl"
                        :alt="title || 'Enlarged image'"
                        class="max-h-[85vh] w-auto select-none object-contain rounded-lg"
                        :style="imageStyle"
                        draggable="false"
                    />
                </div>
                <DialogDescription
                    v-if="title"
                    class="text-center text-sm text-muted-foreground"
                >
                    {{ title }}
                </DialogDescription>
            </div>
        </DialogContent>
    </Dialog>
</template>
