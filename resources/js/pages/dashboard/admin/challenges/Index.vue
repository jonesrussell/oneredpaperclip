<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import {
    FileCheck,
    FileMinus,
    FileText,
    PauseCircle,
    Trash2,
} from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import ChallengesTable from '@/components/admin/ChallengesTable.vue';
import DeleteConfirmDialog from '@/components/admin/DeleteConfirmDialog.vue';
import StatCard from '@/components/admin/StatCard.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import AppLayout from '@/layouts/AppLayout.vue';

type ChallengeStatus = 'draft' | 'active' | 'completed' | 'paused';
type ChallengeVisibility = 'public' | 'unlisted';

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
    current_page: number;
    last_page: number;
    from: number;
    to: number;
    total: number;
    prev_page_url: string | null;
    next_page_url: string | null;
    [key: string]: unknown;
}

interface Props {
    challenges: PaginatedChallenges;
    filters: Record<string, string | undefined>;
    stats: { total: number; active: number; draft: number; paused: number };
    categories: Array<{ id: number; name: string }>;
    columns: Array<{ name: string; label: string; sortable?: boolean }>;
}

const props = defineProps<Props>();

const routePrefix = '/dashboard/admin/challenges';
const breadcrumbs = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Challenges (Admin)', href: routePrefix },
];

const filterValues = ref<Record<string, string | undefined>>({
    search: props.filters.search,
    status: props.filters.status,
    visibility: props.filters.visibility,
    category: props.filters.category,
});
const selectedIds = ref<number[]>([]);
const deleteDialogOpen = ref(false);
const challengeToDelete = ref<Challenge | null>(null);
const isDeleting = ref(false);
const isBulkLoading = ref(false);

const applyFilters = () => {
    const params: Record<string, string | undefined> = {};
    for (const [key, value] of Object.entries(filterValues.value)) {
        if (value) params[key] = value;
    }
    router.get(routePrefix, params, {
        preserveState: true,
        preserveScroll: true,
    });
};

const handleDeleteClick = (challenge: Challenge) => {
    challengeToDelete.value = challenge;
    deleteDialogOpen.value = true;
};

const confirmDelete = () => {
    if (challengeToDelete.value) {
        isDeleting.value = true;
        router.delete(`${routePrefix}/${challengeToDelete.value.id}`, {
            preserveScroll: true,
            onSuccess: () => {
                deleteDialogOpen.value = false;
                challengeToDelete.value = null;
                selectedIds.value = [];
            },
            onFinish: () => {
                isDeleting.value = false;
            },
        });
    } else if (selectedIds.value.length > 0) {
        isBulkLoading.value = true;
        router.post(
            `${routePrefix}/bulk-delete`,
            { ids: selectedIds.value },
            {
                preserveScroll: true,
                onSuccess: () => {
                    deleteDialogOpen.value = false;
                    selectedIds.value = [];
                },
                onFinish: () => {
                    isBulkLoading.value = false;
                },
            },
        );
    }
};

const handleBulkDelete = () => {
    challengeToDelete.value = null;
    deleteDialogOpen.value = true;
};

const handleBulkUnpublish = () => {
    if (selectedIds.value.length === 0) return;
    isBulkLoading.value = true;
    router.post(
        `${routePrefix}/bulk-unpublish`,
        { ids: selectedIds.value },
        {
            preserveScroll: true,
            onSuccess: () => {
                selectedIds.value = [];
            },
            onFinish: () => {
                isBulkLoading.value = false;
            },
        },
    );
};

const handleUnpublish = (challenge: Challenge) => {
    router.post(
        `${routePrefix}/${challenge.id}/unpublish`,
        {},
        { preserveScroll: true },
    );
};

const handleSort = (column: string, direction: string) => {
    router.get(
        routePrefix,
        { ...props.filters, sort: column, direction },
        {
            preserveState: true,
            preserveScroll: true,
        },
    );
};

const goToPage = (url: string | null) => {
    if (url) router.get(url);
};

const getPageNumbers = () => {
    if (!props.challenges?.last_page) return [];
    const current = props.challenges.current_page;
    const last = props.challenges.last_page;
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

const goToPageNumber = (page: number | string) => {
    if (typeof page === 'string' || page === props.challenges?.current_page)
        return;
    router.get(
        routePrefix,
        { ...props.filters, page },
        { preserveState: true, preserveScroll: true },
    );
};

const hasSelected = computed(() => selectedIds.value.length > 0);
const showPagination = computed(() => (props.challenges?.last_page ?? 0) > 1);

const bulkDeleteDescription = computed(() => {
    const count = selectedIds.value.length;
    return count === 1
        ? 'Are you sure you want to delete this challenge? This action can be undone from the Trashed page.'
        : `Are you sure you want to delete ${count} challenges? This action can be undone from the Trashed page.`;
});

watch(
    () => props.challenges?.data?.map((c) => c.id).join(','),
    () => {
        selectedIds.value = [];
    },
);
</script>

<template>
    <Head title="Challenges (Admin) - Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div
            class="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4 md:p-6"
        >
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold tracking-tight">
                        Challenges (Admin)
                    </h1>
                    <p class="mt-1 text-muted-foreground">
                        Moderate user challenges
                    </p>
                </div>
                <Button
                    variant="outline"
                    as="a"
                    :href="`${routePrefix}/trashed`"
                >
                    <Trash2 class="mr-2 h-4 w-4" />
                    View Trashed
                </Button>
            </div>

            <!-- Stats -->
            <div class="grid gap-4 md:grid-cols-4">
                <StatCard
                    label="Total Challenges"
                    :value="stats?.total ?? 0"
                    :icon="FileText"
                />
                <StatCard
                    label="Active"
                    :value="stats?.active ?? 0"
                    :icon="FileCheck"
                />
                <StatCard
                    label="Draft"
                    :value="stats?.draft ?? 0"
                    :icon="FileMinus"
                />
                <StatCard
                    label="Paused"
                    :value="stats?.paused ?? 0"
                    :icon="PauseCircle"
                />
            </div>

            <!-- Filters -->
            <div class="flex flex-wrap items-center gap-4">
                <Input
                    v-model="filterValues.search"
                    placeholder="Search challenges..."
                    class="w-64"
                    @keyup.enter="applyFilters"
                />
                <Select
                    v-model="filterValues.status"
                    @update:model-value="applyFilters"
                >
                    <SelectTrigger class="w-40">
                        <SelectValue placeholder="Status" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="">All Statuses</SelectItem>
                        <SelectItem value="draft">Draft</SelectItem>
                        <SelectItem value="active">Active</SelectItem>
                        <SelectItem value="completed">Completed</SelectItem>
                        <SelectItem value="paused">Paused</SelectItem>
                    </SelectContent>
                </Select>
                <Select
                    v-model="filterValues.visibility"
                    @update:model-value="applyFilters"
                >
                    <SelectTrigger class="w-40">
                        <SelectValue placeholder="Visibility" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="">All Visibility</SelectItem>
                        <SelectItem value="public">Public</SelectItem>
                        <SelectItem value="unlisted">Unlisted</SelectItem>
                    </SelectContent>
                </Select>
                <Select
                    v-model="filterValues.category"
                    @update:model-value="applyFilters"
                >
                    <SelectTrigger class="w-48">
                        <SelectValue placeholder="Category" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="">All Categories</SelectItem>
                        <SelectItem
                            v-for="category in categories"
                            :key="category.id"
                            :value="String(category.id)"
                        >
                            {{ category.name }}
                        </SelectItem>
                    </SelectContent>
                </Select>
                <Button variant="outline" @click="applyFilters">
                    Apply Filters
                </Button>
            </div>

            <!-- Bulk Actions (for admin: unpublish and delete only) -->
            <div
                v-if="hasSelected"
                class="rounded-md border border-primary/50 bg-primary/5 p-4"
            >
                <div class="flex items-center justify-between">
                    <div class="text-sm font-medium">
                        {{ selectedIds.length }} challenge{{
                            selectedIds.length === 1 ? '' : 's'
                        }}
                        selected
                    </div>
                    <div class="flex gap-2">
                        <Button
                            variant="outline"
                            size="sm"
                            :disabled="isBulkLoading"
                            @click="handleBulkUnpublish"
                        >
                            Unpublish
                        </Button>
                        <Button
                            variant="destructive"
                            size="sm"
                            :disabled="isBulkLoading"
                            @click="handleBulkDelete"
                        >
                            <Trash2 class="mr-2 h-4 w-4" />
                            Delete
                        </Button>
                    </div>
                </div>
            </div>

            <!-- Table -->
            <ChallengesTable
                v-if="challenges"
                :challenges="challenges"
                :columns="columns"
                :filters="filters"
                :selected-ids="selectedIds"
                :show-url="(id: number) => `${routePrefix}/${id}`"
                :index-url="routePrefix"
                @delete="handleDeleteClick"
                @update:selected="(ids: number[]) => (selectedIds = ids)"
                @unpublish="handleUnpublish"
                @sort="handleSort"
            />

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
                        @click="goToPage(challenges.prev_page_url)"
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
                        @click="goToPage(challenges.next_page_url)"
                    >
                        Next
                    </Button>
                </div>
            </div>
        </div>

        <DeleteConfirmDialog
            v-model:open="deleteDialogOpen"
            :title="
                challengeToDelete ? 'Delete Challenge' : 'Delete Challenges'
            "
            :description="
                challengeToDelete
                    ? `Are you sure you want to delete &quot;${challengeToDelete.title}&quot;? This action can be undone from the Trashed page.`
                    : bulkDeleteDescription
            "
            :loading="isDeleting || isBulkLoading"
            @confirm="confirmDelete"
            @cancel="
                () => {
                    deleteDialogOpen = false;
                    challengeToDelete = null;
                }
            "
        />
    </AppLayout>
</template>
