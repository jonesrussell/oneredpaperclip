import { router } from '@inertiajs/vue3';
import { ref, type Ref } from 'vue';

interface UseAdminFiltersOptions {
    routeUrl: string;
    initialFilters?: Record<string, string | undefined>;
}

interface UseAdminFiltersReturn {
    filterValues: Ref<Record<string, string | undefined>>;
    applyFilters: () => void;
    handleSort: (column: string, direction: string) => void;
    updateFilter: (key: string, value: string | undefined) => void;
    clearFilters: () => void;
}

export function useAdminFilters(
    options: UseAdminFiltersOptions,
): UseAdminFiltersReturn {
    const filterValues = ref<Record<string, string | undefined>>({
        ...options.initialFilters,
    });

    const applyFilters = (): void => {
        const params: Record<string, string | undefined> = {};
        for (const [key, value] of Object.entries(filterValues.value)) {
            if (value && value !== 'all') {
                params[key] = value;
            }
        }
        router.get(options.routeUrl, params, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    const handleSort = (column: string, direction: string): void => {
        router.get(
            options.routeUrl,
            { ...filterValues.value, sort: column, direction },
            {
                preserveState: true,
                preserveScroll: true,
            },
        );
    };

    const updateFilter = (key: string, value: string | undefined): void => {
        filterValues.value[key] = value;
        applyFilters();
    };

    const clearFilters = (): void => {
        filterValues.value = {};
        applyFilters();
    };

    return {
        filterValues,
        applyFilters,
        handleSort,
        updateFilter,
        clearFilters,
    };
}
