<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Trash2 } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import ArticleForm from '@/components/admin/ArticleForm.vue';
import DeleteConfirmDialog from '@/components/admin/DeleteConfirmDialog.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { useDateFormat } from '@/composables/useDateFormat';
import AppLayout from '@/layouts/AppLayout.vue';
import type { FieldDefinition } from '@/types/admin';

interface Article {
    id: number;
    title: string;
    url: string;
    excerpt?: string | null;
    content?: string | null;
    image_url?: string | null;
    author?: string | null;
    news_source_id: number;
    published_at: string | null;
    is_featured: boolean;
    view_count: number;
    created_at: string;
    updated_at: string;
    tags?: Array<{ id: number; name: string }>;
    news_source?: { id: number; name: string } | null;
    [key: string]: unknown;
}

interface Props {
    article: Article;
    fields: FieldDefinition[];
    relationOptions: Record<string, Array<{ id: number; name: string }>>;
}

const props = defineProps<Props>();

const routePrefix = '/dashboard/articles';
const breadcrumbs = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Articles', href: routePrefix },
    { title: 'Edit', href: `${routePrefix}/${props.article.id}/edit` },
];

const initFormData = (): Record<string, unknown> => {
    const data: Record<string, unknown> = {};
    for (const field of props.fields) {
        if (field.type === 'belongs-to-many' && field.name === 'tags') {
            data[field.name] = props.article.tags?.map((t) => t.id) ?? [];
        } else if (field.type === 'belongs-to-many') {
            const relation = props.article[field.relationship as string] as
                | Array<{ id: number }>
                | undefined;
            data[field.name] = relation?.map((r) => r.id) ?? [];
        } else if (field.type === 'checkbox') {
            data[field.name] = props.article[field.name] ?? false;
        } else {
            data[field.name] = props.article[field.name] ?? '';
        }
    }
    return data;
};

const form = useForm(initFormData());
const deleteDialogOpen = ref(false);
const isDeleting = ref(false);

const isPublished = computed(
    () => form.published_at !== null && form.published_at !== '',
);

const handleSubmit = (publish: boolean = false) => {
    form.transform((data) => ({
        ...data,
        published_at: publish
            ? data.published_at || new Date().toISOString()
            : isPublished.value
              ? data.published_at
              : null,
    })).patch(`${routePrefix}/${props.article.id}`, {
        preserveScroll: true,
    });
};

const handleUnpublish = () => {
    form.transform((data) => ({
        ...data,
        published_at: null,
    })).patch(`${routePrefix}/${props.article.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            form.published_at = null;
        },
    });
};

const confirmDelete = () => {
    isDeleting.value = true;
    router.delete(`${routePrefix}/${props.article.id}`, {
        onSuccess: () => {
            router.get(routePrefix);
        },
        onFinish: () => {
            isDeleting.value = false;
        },
    });
};

const { formatDateTime } = useDateFormat();
</script>

<template>
    <Head :title="`Edit: ${article.title} - Dashboard`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div
            class="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4 md:p-6"
        >
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <Button
                        variant="ghost"
                        size="sm"
                        as="a"
                        :href="routePrefix"
                        class="mb-2"
                    >
                        <ArrowLeft class="mr-2 h-4 w-4" />
                        Back to Articles
                    </Button>
                    <h1 class="text-3xl font-bold tracking-tight">
                        Edit Article
                    </h1>
                    <p class="mt-1 text-muted-foreground">
                        Update article details
                    </p>
                </div>
                <Button variant="destructive" @click="deleteDialogOpen = true">
                    <Trash2 class="mr-2 h-4 w-4" />
                    Delete Article
                </Button>
            </div>

            <!-- Metadata -->
            <Card>
                <CardHeader>
                    <CardTitle>Article Metadata</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="grid grid-cols-2 gap-4 text-sm md:grid-cols-4">
                        <div>
                            <p class="text-muted-foreground">Created</p>
                            <p class="font-medium">
                                {{ formatDateTime(article.created_at) }}
                            </p>
                        </div>
                        <div>
                            <p class="text-muted-foreground">Last Updated</p>
                            <p class="font-medium">
                                {{ formatDateTime(article.updated_at) }}
                            </p>
                        </div>
                        <div>
                            <p class="text-muted-foreground">Views</p>
                            <p class="font-medium">
                                {{ article.view_count.toLocaleString() }}
                            </p>
                        </div>
                        <div>
                            <p class="text-muted-foreground">Status</p>
                            <Badge
                                :variant="isPublished ? 'default' : 'secondary'"
                            >
                                {{ isPublished ? 'Published' : 'Draft' }}
                            </Badge>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Form -->
            <form @submit.prevent="handleSubmit(false)">
                <ArticleForm
                    :fields="fields"
                    :model-value="form.data()"
                    @update:model-value="Object.assign(form, $event)"
                    :errors="form.errors"
                    :relation-options="relationOptions"
                />

                <div class="mt-6 flex gap-3 border-t pt-4">
                    <Button
                        type="button"
                        variant="outline"
                        as="a"
                        :href="routePrefix"
                        :disabled="form.processing"
                    >
                        Cancel
                    </Button>
                    <Button
                        v-if="isPublished"
                        type="button"
                        variant="outline"
                        @click="handleUnpublish"
                        :disabled="form.processing"
                    >
                        {{ form.processing ? 'Unpublishing...' : 'Unpublish' }}
                    </Button>
                    <Button
                        type="submit"
                        variant="outline"
                        :disabled="form.processing"
                    >
                        {{ form.processing ? 'Saving...' : 'Save Changes' }}
                    </Button>
                    <Button
                        v-if="!isPublished"
                        type="button"
                        @click="handleSubmit(true)"
                        :disabled="form.processing"
                    >
                        {{ form.processing ? 'Publishing...' : 'Publish' }}
                    </Button>
                </div>
            </form>
        </div>

        <DeleteConfirmDialog
            v-model:open="deleteDialogOpen"
            title="Delete Article"
            :description="`Are you sure you want to delete &quot;${article.title}&quot;? This action cannot be undone.`"
            :loading="isDeleting"
            @confirm="confirmDelete"
            @cancel="() => (deleteDialogOpen = false)"
        />
    </AppLayout>
</template>
