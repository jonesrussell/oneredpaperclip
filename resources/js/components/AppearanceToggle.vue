<script setup lang="ts">
import { Moon, Sun } from 'lucide-vue-next';
import { computed } from 'vue';
import { Button } from '@/components/ui/button';
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { useAppearance } from '@/composables/useAppearance';

const { resolvedAppearance, updateAppearance } = useAppearance();

function toggle(): void {
    updateAppearance(resolvedAppearance.value === 'dark' ? 'light' : 'dark');
}

const label = computed(() =>
    resolvedAppearance.value === 'dark'
        ? 'Switch to light mode'
        : 'Switch to dark mode',
);
</script>

<template>
    <TooltipProvider :delay-duration="300">
        <Tooltip>
            <TooltipTrigger as-child>
                <Button
                    variant="ghost"
                    size="icon"
                    class="size-9 shrink-0"
                    :aria-label="label"
                    @click="toggle"
                >
                    <Sun v-if="resolvedAppearance === 'dark'" class="size-5" />
                    <Moon v-else class="size-5" />
                </Button>
            </TooltipTrigger>
            <TooltipContent>
                <p>{{ label }}</p>
            </TooltipContent>
        </Tooltip>
    </TooltipProvider>
</template>
