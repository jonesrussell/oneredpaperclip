import { router } from '@inertiajs/vue3';
import {
    computed,
    type ComputedRef,
    type MaybeRefOrGetter,
    toValue,
} from 'vue';

interface PaginationData {
    current_page: number;
    last_page: number;
    prev_page_url: string | null;
    next_page_url: string | null;
}

interface UsePaginationOptions {
    routeUrl: string;
    filters?: MaybeRefOrGetter<Record<string, unknown>>;
}

interface UsePaginationReturn {
    getPageNumbers: () => (number | string)[];
    goToPage: (direction: 'prev' | 'next') => void;
    goToPageNumber: (page: number | string) => void;
    showPagination: ComputedRef<boolean>;
    hasSelected: ComputedRef<boolean>;
}

export function usePagination<T extends PaginationData>(
    paginatedData: MaybeRefOrGetter<T | null | undefined>,
    selectedIds: MaybeRefOrGetter<number[]>,
    options: UsePaginationOptions,
): UsePaginationReturn {
    const getPageNumbers = (): (number | string)[] => {
        const data = toValue(paginatedData);
        if (!data?.last_page) return [];

        const current = data.current_page;
        const last = data.last_page;
        const pages: (number | string)[] = [];

        if (last <= 7) {
            for (let i = 1; i <= last; i++) pages.push(i);
        } else if (current <= 3) {
            for (let i = 1; i <= 5; i++) pages.push(i);
            pages.push('...', last);
        } else if (current >= last - 2) {
            pages.push(1, '...');
            for (let i = last - 4; i <= last; i++) pages.push(i);
        } else {
            pages.push(1, '...');
            for (let i = current - 1; i <= current + 1; i++) pages.push(i);
            pages.push('...', last);
        }
        return pages;
    };

    const goToPage = (direction: 'prev' | 'next'): void => {
        const data = toValue(paginatedData);
        const url =
            direction === 'prev' ? data?.prev_page_url : data?.next_page_url;
        if (url) {
            router.get(url, {}, { preserveState: true, preserveScroll: true });
        }
    };

    const goToPageNumber = (page: number | string): void => {
        const data = toValue(paginatedData);
        if (typeof page === 'string' || page === data?.current_page) return;

        const filters = toValue(options.filters) ?? {};
        router.get(
            options.routeUrl,
            { ...filters, page },
            { preserveState: true, preserveScroll: true },
        );
    };

    const showPagination = computed(
        () => (toValue(paginatedData)?.last_page ?? 0) > 1,
    );

    const hasSelected = computed(() => toValue(selectedIds).length > 0);

    return {
        getPageNumbers,
        goToPage,
        goToPageNumber,
        showPagination,
        hasSelected,
    };
}
