<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { ArrowLeft } from 'lucide-vue-next';
import ArticleForm from '@/components/admin/ArticleForm.vue';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import type { FieldDefinition } from '@/types/admin';

interface Props {
    fields: FieldDefinition[];
    relationOptions: Record<string, Array<{ id: number; name: string }>>;
}

const props = defineProps<Props>();

const routePrefix = '/dashboard/articles';
const breadcrumbs = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Articles', href: routePrefix },
    { title: 'Create', href: `${routePrefix}/create` },
];

const initFormData = (): Record<string, unknown> => {
    const data: Record<string, unknown> = {};
    for (const field of props.fields) {
        if (field.type === 'checkbox') data[field.name] = false;
        else if (field.type === 'belongs-to-many') data[field.name] = [];
        else if (field.type === 'belongs-to') {
            const options =
                props.relationOptions.news_sources ??
                props.relationOptions[field.name] ??
                [];
            data[field.name] = options[0]?.id ?? null;
        } else data[field.name] = '';
    }
    return data;
};

const form = useForm(initFormData());

const handleSubmit = (publish: boolean = false) => {
    form.transform((data) => ({
        ...data,
        published_at: publish ? new Date().toISOString() : null,
    })).post(routePrefix, {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Create Article - Dashboard" />

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
                        Create Article
                    </h1>
                    <p class="mt-1 text-muted-foreground">
                        Add a new article to your collection
                    </p>
                </div>
            </div>

            <!-- Form -->
            <form @submit.prevent="handleSubmit(false)">
                <ArticleForm
                    :fields="fields"
                    :model-value="form.data()"
                    @update:model-value="Object.assign(form, $event)"
                    :errors="form.errors"
                    :relation-options="relationOptions"
                />

                <!-- Actions -->
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
                        type="submit"
                        variant="outline"
                        :disabled="form.processing"
                    >
                        {{ form.processing ? 'Saving...' : 'Save as Draft' }}
                    </Button>
                    <Button
                        type="button"
                        @click="handleSubmit(true)"
                        :disabled="form.processing"
                    >
                        {{ form.processing ? 'Publishing...' : 'Publish' }}
                    </Button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
