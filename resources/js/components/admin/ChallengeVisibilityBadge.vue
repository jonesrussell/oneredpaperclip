<script setup lang="ts">
import { computed } from 'vue';
import { Badge } from '@/components/ui/badge';

type ChallengeVisibility = 'public' | 'unlisted';

const props = defineProps<{
    visibility: ChallengeVisibility;
}>();

const variantMap: Record<
    ChallengeVisibility,
    'default' | 'secondary' | 'destructive' | 'outline'
> = {
    public: 'default',
    unlisted: 'secondary',
};

const labelMap: Record<ChallengeVisibility, string> = {
    public: 'Public',
    unlisted: 'Unlisted',
};

const variant = computed(() => variantMap[props.visibility]);
const label = computed(() => labelMap[props.visibility]);
</script>

<template>
    <Badge :variant="variant">
        {{ label }}
    </Badge>
</template>
