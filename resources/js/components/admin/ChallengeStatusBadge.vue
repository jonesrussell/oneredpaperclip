<script setup lang="ts">
import { computed } from 'vue';
import { Badge } from '@/components/ui/badge';

type ChallengeStatus = 'draft' | 'active' | 'completed' | 'paused';

const props = defineProps<{
    status: ChallengeStatus;
}>();

const variantMap: Record<
    ChallengeStatus,
    'default' | 'secondary' | 'destructive' | 'outline'
> = {
    draft: 'secondary',
    active: 'default',
    completed: 'outline',
    paused: 'destructive',
};

const labelMap: Record<ChallengeStatus, string> = {
    draft: 'Draft',
    active: 'Active',
    completed: 'Completed',
    paused: 'Paused',
};

const variant = computed(() => variantMap[props.status]);
const label = computed(() => labelMap[props.status]);
</script>

<template>
    <Badge :variant="variant">
        {{ label }}
    </Badge>
</template>
