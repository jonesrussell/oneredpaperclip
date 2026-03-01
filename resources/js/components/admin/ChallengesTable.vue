<script setup lang="ts">
import { ArrowDown, ArrowUp, Eye, EyeOff, Trash2 } from 'lucide-vue-next';
import { computed } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { useDateFormat } from '@/composables/useDateFormat';
import ChallengeStatusBadge from './ChallengeStatusBadge.vue';
import ChallengeVisibilityBadge from './ChallengeVisibilityBadge.vue';

type ChallengeStatus = 'draft' | 'active' | 'completed' | 'paused';
type ChallengeVisibility = 'public' | 'unlisted';

interface ColumnDefinition {
    name: string;
    label: string;
    sortable?: boolean;
}

interface Challenge {
    id: number;
    title: string;
    status: ChallengeStatus;
    visibility: ChallengeVisibility;
    created_at: string;
    updated_at: string;
    trades_count?: number;
    offers_count?: number;
    user?: { id: number; name: string } | null;
    category?: { id: number; name: string } | null;
    [key: string]: unknown;
}

interface PaginatedChallenges {
    data: Challenge[];
    [key: string]: unknown;
}

interface Props {
    challenges: PaginatedChallenges;
    columns: ColumnDefinition[];
    filters?: {
        sort?: string;
        direction?: 'asc' | 'desc';
        [key: string]: unknown;
    };
    selectedIds?: number[];
    showUrl: (challengeId: number) => string;
    indexUrl: string;
}

const props = withDefaults(defineProps<Props>(), {
    selectedIds: () => [],
});

const emit = defineEmits<{
    delete: [challenge: Challenge];
    'update:selected': [ids: number[]];
    unpublish: [challenge: Challenge];
    sort: [column: string, direction: string];
}>();

const allChallengeIds = computed(
    () => props.challenges?.data?.map((c) => c.id) ?? [],
);

const selectedIdsSet = computed(() => new Set(props.selectedIds));

const challengeCheckedStates = computed(() => {
    const states: Record<number, boolean> = {};
    props.challenges?.data?.forEach((challenge) => {
        states[challenge.id] = selectedIdsSet.value.has(challenge.id);
    });
    return states;
});

const isAllSelected = computed(() => {
    if (allChallengeIds.value.length === 0) return false;
    return allChallengeIds.value.every((id) => selectedIdsSet.value.has(id));
});

const isSomeSelected = computed(() => {
    return props.selectedIds.length > 0 && !isAllSelected.value;
});

const toggleSelectAll = (checked: boolean | 'indeterminate') => {
    const shouldSelect = checked === true || checked === 'indeterminate';

    let newSelectedIds: number[];
    if (shouldSelect) {
        const newIds = allChallengeIds.value.filter(
            (id) => !props.selectedIds.includes(id),
        );
        newSelectedIds = [...props.selectedIds, ...newIds];
    } else {
        newSelectedIds = props.selectedIds.filter(
            (id) => !allChallengeIds.value.includes(id),
        );
    }
    emit('update:selected', newSelectedIds);
};

const toggleSelect = (challengeId: number) => {
    let newSelectedIds: number[];
    if (props.selectedIds.includes(challengeId)) {
        newSelectedIds = props.selectedIds.filter((id) => id !== challengeId);
    } else {
        newSelectedIds = [...props.selectedIds, challengeId];
    }
    emit('update:selected', newSelectedIds);
};

const handleSort = (column: string) => {
    const newDirection =
        props.filters?.sort === column && props.filters?.direction === 'asc'
            ? 'desc'
            : 'asc';
    emit('sort', column, newDirection);
};

const getSortIcon = (column: string) => {
    if (props.filters?.sort !== column) return null;
    return props.filters?.direction === 'asc' ? ArrowUp : ArrowDown;
};

const isActive = (challenge: Challenge) => {
    return challenge.status === 'active';
};

const { formatShortDate } = useDateFormat();

const formatDate = (date: string | null) =>
    formatShortDate(date, { fallback: 'Never' });

const getCellValue = (challenge: Challenge, column: ColumnDefinition) => {
    const value = challenge[column.name];
    if (value === null || value === undefined) return '-';
    return String(value);
};
</script>

<template>
    <div class="rounded-md border">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="border-b bg-muted/50">
                    <tr>
                        <th
                            class="w-12 px-4 py-3 text-left text-sm font-medium"
                        >
                            <Checkbox
                                :model-value="isAllSelected"
                                :indeterminate="isSomeSelected"
                                @update:model-value="toggleSelectAll"
                            />
                        </th>
                        <th
                            v-for="col in columns"
                            :key="col.name"
                            class="px-4 py-3 text-left text-sm font-medium"
                            :class="{
                                'cursor-pointer hover:bg-muted': col.sortable,
                            }"
                            @click="col.sortable && handleSort(col.name)"
                        >
                            <div class="flex items-center gap-1">
                                {{ col.label }}
                                <component
                                    :is="getSortIcon(col.name)"
                                    v-if="col.sortable && getSortIcon(col.name)"
                                    class="h-3 w-3"
                                />
                            </div>
                        </th>
                        <th class="px-4 py-3 text-right text-sm font-medium">
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="challenge in challenges?.data ?? []"
                        :key="challenge.id"
                        class="border-b transition-colors hover:bg-muted/50"
                    >
                        <td class="px-4 py-3">
                            <Checkbox
                                :model-value="
                                    challengeCheckedStates[challenge.id]
                                "
                                @update:model-value="
                                    () => toggleSelect(challenge.id)
                                "
                            />
                        </td>
                        <td
                            v-for="col in columns"
                            :key="col.name"
                            class="px-4 py-3"
                        >
                            <!-- ID -->
                            <span v-if="col.name === 'id'" class="text-sm">
                                {{ challenge.id }}
                            </span>

                            <!-- Title -->
                            <div
                                v-else-if="col.name === 'title'"
                                class="max-w-md"
                            >
                                <a
                                    :href="showUrl(challenge.id)"
                                    class="line-clamp-2 text-sm font-medium transition-colors hover:text-primary"
                                >
                                    {{ challenge.title }}
                                </a>
                                <div
                                    v-if="challenge.user"
                                    class="mt-1 text-xs text-muted-foreground"
                                >
                                    by {{ challenge.user.name }}
                                </div>
                            </div>

                            <!-- Category -->
                            <template v-else-if="col.name === 'category'">
                                <Badge
                                    v-if="challenge.category"
                                    variant="outline"
                                    class="text-xs"
                                >
                                    {{ challenge.category.name }}
                                </Badge>
                            </template>

                            <!-- Status -->
                            <template v-else-if="col.name === 'status'">
                                <ChallengeStatusBadge
                                    :status="challenge.status"
                                />
                            </template>

                            <!-- Visibility -->
                            <template v-else-if="col.name === 'visibility'">
                                <ChallengeVisibilityBadge
                                    :visibility="challenge.visibility"
                                />
                            </template>

                            <!-- Trades count -->
                            <span
                                v-else-if="col.name === 'trades_count'"
                                class="text-sm text-muted-foreground"
                            >
                                {{ challenge.trades_count ?? 0 }}
                            </span>

                            <!-- Offers count -->
                            <span
                                v-else-if="col.name === 'offers_count'"
                                class="text-sm text-muted-foreground"
                            >
                                {{ challenge.offers_count ?? 0 }}
                            </span>

                            <!-- Created date -->
                            <span
                                v-else-if="col.name === 'created_at'"
                                class="text-sm text-muted-foreground"
                            >
                                {{ formatDate(challenge.created_at) }}
                            </span>

                            <!-- Updated date -->
                            <span
                                v-else-if="col.name === 'updated_at'"
                                class="text-sm text-muted-foreground"
                            >
                                {{ formatDate(challenge.updated_at) }}
                            </span>

                            <!-- Generic fallback -->
                            <span v-else class="text-sm text-muted-foreground">
                                {{ getCellValue(challenge, col) }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-end gap-2">
                                <Button
                                    v-if="isActive(challenge)"
                                    variant="ghost"
                                    size="sm"
                                    title="Unpublish (set to Draft)"
                                    @click="emit('unpublish', challenge)"
                                >
                                    <EyeOff class="h-4 w-4" />
                                </Button>
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    as="a"
                                    :href="showUrl(challenge.id)"
                                    title="View"
                                >
                                    <Eye class="h-4 w-4" />
                                </Button>
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    title="Delete"
                                    @click="emit('delete', challenge)"
                                >
                                    <Trash2 class="h-4 w-4 text-destructive" />
                                </Button>
                            </div>
                        </td>
                    </tr>
                    <tr
                        v-if="!challenges?.data || challenges.data.length === 0"
                    >
                        <td
                            :colspan="columns.length + 2"
                            class="px-4 py-12 text-center text-muted-foreground"
                        >
                            <div class="flex flex-col items-center gap-2">
                                <p class="text-sm">No challenges found.</p>
                                <p class="text-xs">
                                    Try adjusting your filters.
                                </p>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
