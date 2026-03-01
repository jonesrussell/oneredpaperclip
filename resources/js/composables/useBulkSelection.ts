import {
    computed,
    type ComputedRef,
    type MaybeRefOrGetter,
    ref,
    toValue,
    watch,
} from 'vue';

interface HasId {
    id: number;
}

interface UseBulkSelectionOptions<T extends HasId> {
    items: MaybeRefOrGetter<T[] | undefined>;
    onSelectionChange?: (ids: number[]) => void;
}

interface UseBulkSelectionReturn {
    selectedIds: ReturnType<typeof ref<number[]>>;
    isAllSelected: ComputedRef<boolean>;
    isSomeSelected: ComputedRef<boolean>;
    hasSelected: ComputedRef<boolean>;
    selectedCount: ComputedRef<number>;
    toggleSelectAll: (checked: boolean | 'indeterminate') => void;
    toggleSelect: (id: number) => void;
    handleSelectAll: (checked: boolean) => void;
    handleSelectOne: (id: number, checked: boolean) => void;
    clearSelection: () => void;
    isSelected: (id: number) => boolean;
    getCheckedStates: () => Record<number, boolean>;
}

export function useBulkSelection<T extends HasId>(
    options: UseBulkSelectionOptions<T>,
): UseBulkSelectionReturn {
    const selectedIds = ref<number[]>([]);

    const allItemIds = computed(() => {
        const items = toValue(options.items);
        return items?.map((item) => item.id) ?? [];
    });

    const selectedIdsSet = computed(() => new Set(selectedIds.value));

    const isAllSelected = computed(() => {
        if (allItemIds.value.length === 0) return false;
        return allItemIds.value.every((id) => selectedIdsSet.value.has(id));
    });

    const isSomeSelected = computed(() => {
        return selectedIds.value.length > 0 && !isAllSelected.value;
    });

    const hasSelected = computed(() => selectedIds.value.length > 0);

    const selectedCount = computed(() => selectedIds.value.length);

    const toggleSelectAll = (checked: boolean | 'indeterminate'): void => {
        const shouldSelect = checked === true || checked === 'indeterminate';

        if (shouldSelect) {
            const newIds = allItemIds.value.filter(
                (id) => !selectedIds.value.includes(id),
            );
            selectedIds.value = [...selectedIds.value, ...newIds];
        } else {
            selectedIds.value = selectedIds.value.filter(
                (id) => !allItemIds.value.includes(id),
            );
        }

        options.onSelectionChange?.(selectedIds.value);
    };

    const toggleSelect = (id: number): void => {
        if (selectedIds.value.includes(id)) {
            selectedIds.value = selectedIds.value.filter((i) => i !== id);
        } else {
            selectedIds.value = [...selectedIds.value, id];
        }

        options.onSelectionChange?.(selectedIds.value);
    };

    const handleSelectAll = (checked: boolean): void => {
        const items = toValue(options.items);
        selectedIds.value = checked
            ? (items?.map((item) => item.id) ?? [])
            : [];
        options.onSelectionChange?.(selectedIds.value);
    };

    const handleSelectOne = (id: number, checked: boolean): void => {
        selectedIds.value = checked
            ? [...selectedIds.value, id]
            : selectedIds.value.filter((i) => i !== id);

        options.onSelectionChange?.(selectedIds.value);
    };

    const clearSelection = (): void => {
        selectedIds.value = [];
        options.onSelectionChange?.(selectedIds.value);
    };

    const isSelected = (id: number): boolean => {
        return selectedIdsSet.value.has(id);
    };

    const getCheckedStates = (): Record<number, boolean> => {
        const states: Record<number, boolean> = {};
        const items = toValue(options.items);
        items?.forEach((item) => {
            states[item.id] = selectedIdsSet.value.has(item.id);
        });
        return states;
    };

    watch(
        () => allItemIds.value.join(','),
        () => {
            clearSelection();
        },
    );

    return {
        selectedIds,
        isAllSelected,
        isSomeSelected,
        hasSelected,
        selectedCount,
        toggleSelectAll,
        toggleSelect,
        handleSelectAll,
        handleSelectOne,
        clearSelection,
        isSelected,
        getCheckedStates,
    };
}
