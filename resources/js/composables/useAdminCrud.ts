import { router } from '@inertiajs/vue3';
import { ref, type Ref } from 'vue';

interface HasId {
    id: number;
}

interface UseAdminCrudOptions {
    routePrefix: string;
}

interface UseAdminCrudReturn {
    isDeleting: Ref<boolean>;
    isBulkLoading: Ref<boolean>;
    deleteDialogOpen: Ref<boolean>;
    itemToDelete: Ref<HasId | null>;

    openDeleteDialog: (item: HasId | null) => void;
    closeDeleteDialog: () => void;

    deleteItem: (id: number, onSuccess?: () => void) => void;

    bulkDelete: (ids: number[], onSuccess?: () => void) => void;

    bulkAction: (
        action: string,
        ids: number[],
        payload?: Record<string, unknown>,
        onSuccess?: () => void,
    ) => void;

    restoreItem: (id: number, onSuccess?: () => void) => void;

    bulkRestore: (ids: number[], onSuccess?: () => void) => void;

    forceDeleteItem: (id: number, onSuccess?: () => void) => void;

    bulkForceDelete: (ids: number[], onSuccess?: () => void) => void;
}

export function useAdminCrud<T extends HasId>(
    options: UseAdminCrudOptions,
): UseAdminCrudReturn {
    const isDeleting = ref(false);
    const isBulkLoading = ref(false);
    const deleteDialogOpen = ref(false);
    const itemToDelete = ref<T | null>(null) as Ref<T | null>;

    const openDeleteDialog = (item: T | null): void => {
        itemToDelete.value = item;
        deleteDialogOpen.value = true;
    };

    const closeDeleteDialog = (): void => {
        deleteDialogOpen.value = false;
        itemToDelete.value = null;
    };

    const deleteItem = (id: number, onSuccess?: () => void): void => {
        isDeleting.value = true;
        router.delete(`${options.routePrefix}/${id}`, {
            preserveScroll: true,
            onSuccess: () => {
                closeDeleteDialog();
                onSuccess?.();
            },
            onFinish: () => {
                isDeleting.value = false;
            },
        });
    };

    const bulkDelete = (ids: number[], onSuccess?: () => void): void => {
        isBulkLoading.value = true;
        router.post(
            `${options.routePrefix}/bulk-delete`,
            { ids },
            {
                preserveScroll: true,
                onSuccess: () => {
                    closeDeleteDialog();
                    onSuccess?.();
                },
                onFinish: () => {
                    isBulkLoading.value = false;
                },
            },
        );
    };

    const bulkAction = (
        action: string,
        ids: number[],
        payload?: Record<string, unknown>,
        onSuccess?: () => void,
    ): void => {
        if (ids.length === 0) return;
        isBulkLoading.value = true;
        router.post(
            `${options.routePrefix}/${action}`,
            { ids, ...payload },
            {
                preserveScroll: true,
                onSuccess: () => {
                    onSuccess?.();
                },
                onFinish: () => {
                    isBulkLoading.value = false;
                },
            },
        );
    };

    const restoreItem = (id: number, onSuccess?: () => void): void => {
        isBulkLoading.value = true;
        router.post(
            `${options.routePrefix}/${id}/restore`,
            {},
            {
                preserveScroll: true,
                onSuccess: () => {
                    onSuccess?.();
                },
                onFinish: () => {
                    isBulkLoading.value = false;
                },
            },
        );
    };

    const bulkRestore = (ids: number[], onSuccess?: () => void): void => {
        if (ids.length === 0) return;
        isBulkLoading.value = true;
        router.post(
            `${options.routePrefix}/bulk-restore`,
            { ids },
            {
                preserveScroll: true,
                onSuccess: () => {
                    onSuccess?.();
                },
                onFinish: () => {
                    isBulkLoading.value = false;
                },
            },
        );
    };

    const forceDeleteItem = (id: number, onSuccess?: () => void): void => {
        isDeleting.value = true;
        router.delete(`${options.routePrefix}/${id}/force`, {
            preserveScroll: true,
            onSuccess: () => {
                closeDeleteDialog();
                onSuccess?.();
            },
            onFinish: () => {
                isDeleting.value = false;
            },
        });
    };

    const bulkForceDelete = (ids: number[], onSuccess?: () => void): void => {
        if (ids.length === 0) return;
        isBulkLoading.value = true;
        router.post(
            `${options.routePrefix}/bulk-force-delete`,
            { ids },
            {
                preserveScroll: true,
                onSuccess: () => {
                    closeDeleteDialog();
                    onSuccess?.();
                },
                onFinish: () => {
                    isBulkLoading.value = false;
                },
            },
        );
    };

    return {
        isDeleting,
        isBulkLoading,
        deleteDialogOpen,
        itemToDelete: itemToDelete as Ref<HasId | null>,

        openDeleteDialog: openDeleteDialog as (item: HasId | null) => void,
        closeDeleteDialog,

        deleteItem,
        bulkDelete,
        bulkAction,

        restoreItem,
        bulkRestore,

        forceDeleteItem,
        bulkForceDelete,
    };
}
