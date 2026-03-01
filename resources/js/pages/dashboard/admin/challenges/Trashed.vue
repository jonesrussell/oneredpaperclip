<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import {
    Archive,
    ArrowUpDown,
    FileText,
    RotateCcw,
    Trash2,
} from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import BulkActionBar from '@/components/admin/BulkActionBar.vue';
import DeleteConfirmDialog from '@/components/admin/DeleteConfirmDialog.vue';
import StatCard from '@/components/admin/StatCard.vue';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { useDateFormat } from '@/composables/useDateFormat';
import { usePagination } from '@/composables/usePagination';
import AppLayout from '@/layouts/AppLayout.vue';

interface Challenge {
    id: number;
    title: string;
    deleted_at: string | null;
    user?: { id: number; name: string } | null;
    category?: { id: number; name: string } | null;
}

interface PaginatedChallenges {
    data: Challenge[];
    current_page: number;
    last_page: number;
    from: number;
    to: number;
    total: number;
    prev_page_url: string | null;
    next_page_url: string | null;
}

interface Props {
    challenges: PaginatedChallenges;
    filters?: Record<string, string | undefined>;
}

const props = defineProps<Props>();

const routePrefix = '/dashboard/admin/challenges';
const trashedUrl = `${routePrefix}/trashed`;
const breadcrumbs = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Challenges (Admin)', href: routePrefix },
    { title: 'Trashed', href: trashedUrl },
];

const searchQuery = ref(props.filters?.search ?? '');
const selectedIds = ref<number[]>([]);
const isRestoring = ref(false);
const isForceDeleting = ref(false);
const deleteDialogOpen = ref(false);
const challengeToDelete = ref<Challenge | null>(null);
const isBulkAction = ref(false);

const applyFilters = () => {
    router.get(
        trashedUrl,
        {
            search: searchQuery.value || undefined,
        },
        { preserveState: true, preserveScroll: true },
    );
};

const toggleSort = (column: string) => {
    const newDirection =
        props.filters?.sort === column && props.filters?.direction === 'asc'
            ? 'desc'
            : 'asc';
    router.get(
        trashedUrl,
        { ...props.filters, sort: column, direction: newDirection },
        {
            preserveState: true,
            preserveScroll: true,
        },
    );
};

const handleSelectAll = (checked: boolean) => {
    selectedIds.value = checked ? props.challenges.data.map((c) => c.id) : [];
};

const handleSelectOne = (id: number, checked: boolean) => {
    selectedIds.value = checked
        ? [...selectedIds.value, id]
        : selectedIds.value.filter((i) => i !== id);
};

const handleRestore = (challenge: Challenge) => {
    isRestoring.value = true;
    router.post(
        `${routePrefix}/${challenge.id}/restore`,
        {},
        {
            preserveScroll: true,
            onFinish: () => {
                isRestoring.value = false;
            },
        },
    );
};

const handleBulkRestore = () => {
    if (selectedIds.value.length === 0) return;
    isRestoring.value = true;
    router.post(
        `${routePrefix}/bulk-restore`,
        { ids: selectedIds.value },
        {
            preserveScroll: true,
            onSuccess: () => {
                selectedIds.value = [];
            },
            onFinish: () => {
                isRestoring.value = false;
            },
        },
    );
};

const handleForceDeleteClick = (challenge: Challenge) => {
    challengeToDelete.value = challenge;
    isBulkAction.value = false;
    deleteDialogOpen.value = true;
};

const handleBulkForceDelete = () => {
    if (selectedIds.value.length === 0) return;
    challengeToDelete.value = null;
    isBulkAction.value = true;
    deleteDialogOpen.value = true;
};

const confirmForceDelete = () => {
    if (isBulkAction.value) {
        isForceDeleting.value = true;
        router.post(
            `${routePrefix}/bulk-force-delete`,
            { ids: selectedIds.value },
            {
                preserveScroll: true,
                onSuccess: () => {
                    deleteDialogOpen.value = false;
                    selectedIds.value = [];
                },
                onFinish: () => {
                    isForceDeleting.value = false;
                },
            },
        );
    } else if (challengeToDelete.value) {
        isForceDeleting.value = true;
        router.delete(`${routePrefix}/${challengeToDelete.value.id}/force`, {
            preserveScroll: true,
            onSuccess: () => {
                deleteDialogOpen.value = false;
                challengeToDelete.value = null;
            },
            onFinish: () => {
                isForceDeleting.value = false;
            },
        });
    }
};

const {
    getPageNumbers,
    goToPage,
    goToPageNumber,
    showPagination,
    hasSelected,
} = usePagination(() => props.challenges, selectedIds, {
    routeUrl: trashedUrl,
    filters: () => props.filters,
});

const allSelected = computed(
    () =>
        props.challenges.data.length > 0 &&
        selectedIds.value.length === props.challenges.data.length,
);

const deleteDescription = computed(() => {
    if (isBulkAction.value) {
        const count = selectedIds.value.length;
        return count === 1
            ? 'Are you sure you want to PERMANENTLY delete this challenge? This action cannot be undone.'
            : `Are you sure you want to PERMANENTLY delete ${count} challenges? This action cannot be undone.`;
    }
    return challengeToDelete.value
        ? `Are you sure you want to PERMANENTLY delete "${challengeToDelete.value.title}"? This action cannot be undone.`
        : '';
});

const { formatDateTime: formatDate } = useDateFormat();

watch(
    () => props.challenges?.data?.map((c) => c.id).join(','),
    () => {
        selectedIds.value = [];
    },
);
</script>

<template>
    <Head title="Trashed Challenges - Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div
            class="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4 md:p-6"
        >
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold tracking-tight">
                        Trashed Challenges
                    </h1>
                    <p class="mt-1 text-muted-foreground">
                        Manage soft-deleted challenges - restore or permanently
                        delete
                    </p>
                </div>
                <Button variant="outline" as="a" :href="routePrefix">
                    <FileText class="mr-2 h-4 w-4" />
                    Back to Challenges
                </Button>
            </div>

            <!-- Stats -->
            <div class="grid gap-4 md:grid-cols-1">
                <StatCard
                    label="Trashed Challenges"
                    :value="challenges.total"
                    :icon="Archive"
                />
            </div>

            <!-- Filters -->
            <Card>
                <div class="p-6">
                    <div
                        class="flex flex-col gap-4 md:flex-row md:items-center"
                    >
                        <div class="flex-1">
                            <Input
                                v-model="searchQuery"
                                type="search"
                                placeholder="Search by title..."
                                class="max-w-sm"
                                @keyup.enter="applyFilters"
                            />
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <Button variant="outline" @click="applyFilters">
                                Apply Filters
                            </Button>
                        </div>
                    </div>
                </div>
            </Card>

            <!-- Bulk Actions -->
            <BulkActionBar
                v-if="hasSelected"
                :selected-count="selectedIds.length"
                :loading="isRestoring || isForceDeleting"
                mode="trashed"
                @restore="handleBulkRestore"
                @force-delete="handleBulkForceDelete"
            />

            <!-- Table -->
            <Card>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="border-b">
                            <tr>
                                <th class="w-[50px] p-4 text-left">
                                    <Checkbox
                                        :checked="allSelected"
                                        @update:checked="handleSelectAll"
                                    />
                                </th>
                                <th
                                    class="cursor-pointer p-4 text-left font-medium hover:bg-muted/50"
                                    @click="toggleSort('id')"
                                >
                                    <div class="flex items-center gap-1">
                                        ID <ArrowUpDown class="h-4 w-4" />
                                    </div>
                                </th>
                                <th
                                    class="cursor-pointer p-4 text-left font-medium hover:bg-muted/50"
                                    @click="toggleSort('title')"
                                >
                                    <div class="flex items-center gap-1">
                                        Title <ArrowUpDown class="h-4 w-4" />
                                    </div>
                                </th>
                                <th class="p-4 text-left font-medium">Owner</th>
                                <th class="p-4 text-left font-medium">
                                    Category
                                </th>
                                <th
                                    class="cursor-pointer p-4 text-left font-medium hover:bg-muted/50"
                                    @click="toggleSort('deleted_at')"
                                >
                                    <div class="flex items-center gap-1">
                                        Deleted At
                                        <ArrowUpDown class="h-4 w-4" />
                                    </div>
                                </th>
                                <th class="p-4 text-right font-medium">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="challenge in challenges.data"
                                :key="challenge.id"
                                class="border-b hover:bg-muted/50"
                            >
                                <td class="p-4">
                                    <Checkbox
                                        :checked="
                                            selectedIds.includes(challenge.id)
                                        "
                                        @update:checked="
                                            (checked: boolean) =>
                                                handleSelectOne(
                                                    challenge.id,
                                                    checked,
                                                )
                                        "
                                    />
                                </td>
                                <td class="p-4 font-mono text-xs">
                                    {{ challenge.id }}
                                </td>
                                <td class="max-w-[300px] p-4">
                                    <div class="truncate font-medium">
                                        {{ challenge.title }}
                                    </div>
                                </td>
                                <td class="p-4 text-muted-foreground">
                                    {{ challenge.user?.name || '-' }}
                                </td>
                                <td class="p-4 text-muted-foreground">
                                    {{ challenge.category?.name || '-' }}
                                </td>
                                <td class="p-4 text-muted-foreground">
                                    {{ formatDate(challenge.deleted_at) }}
                                </td>
                                <td class="p-4 text-right">
                                    <div class="flex justify-end gap-1">
                                        <Button
                                            variant="ghost"
                                            size="sm"
                                            :disabled="isRestoring"
                                            @click="handleRestore(challenge)"
                                        >
                                            <RotateCcw class="h-4 w-4" />
                                        </Button>
                                        <Button
                                            variant="ghost"
                                            size="sm"
                                            class="text-destructive hover:text-destructive"
                                            :disabled="isForceDeleting"
                                            @click="
                                                handleForceDeleteClick(
                                                    challenge,
                                                )
                                            "
                                        >
                                            <Trash2 class="h-4 w-4" />
                                        </Button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="challenges.data.length === 0">
                                <td
                                    colspan="7"
                                    class="p-8 text-center text-muted-foreground"
                                >
                                    No trashed challenges found.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </Card>

            <!-- Pagination -->
            <div
                v-if="showPagination"
                class="flex items-center justify-between"
            >
                <div class="text-sm text-muted-foreground">
                    Showing {{ challenges.from }} to {{ challenges.to }} of
                    {{ challenges.total }} results
                </div>
                <div class="flex items-center gap-2">
                    <Button
                        variant="outline"
                        size="sm"
                        :disabled="!challenges?.prev_page_url"
                        @click="goToPage('prev')"
                    >
                        Previous
                    </Button>
                    <div class="flex gap-1">
                        <Button
                            v-for="page in getPageNumbers()"
                            :key="page"
                            size="sm"
                            :variant="
                                page === challenges.current_page
                                    ? 'default'
                                    : 'outline'
                            "
                            :disabled="typeof page === 'string'"
                            @click="goToPageNumber(page)"
                        >
                            {{ page }}
                        </Button>
                    </div>
                    <Button
                        variant="outline"
                        size="sm"
                        :disabled="!challenges?.next_page_url"
                        @click="goToPage('next')"
                    >
                        Next
                    </Button>
                </div>
            </div>
        </div>

        <DeleteConfirmDialog
            v-model:open="deleteDialogOpen"
            title="Permanently Delete"
            :description="deleteDescription"
            :loading="isForceDeleting"
            @confirm="confirmForceDelete"
            @cancel="
                () => {
                    deleteDialogOpen = false;
                    challengeToDelete = null;
                    isBulkAction = false;
                }
            "
        />
    </AppLayout>
</template>
