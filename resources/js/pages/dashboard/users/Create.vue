<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { ArrowLeft } from 'lucide-vue-next';
import UserForm from '@/components/admin/UserForm.vue';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import type { FieldDefinition } from '@/types/admin';

interface Props {
    fields: FieldDefinition[];
}

const props = defineProps<Props>();

const routePrefix = '/dashboard/users';
const breadcrumbs = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Users', href: routePrefix },
    { title: 'Create', href: `${routePrefix}/create` },
];

const initFormData = (): Record<string, unknown> => {
    const data: Record<string, unknown> = {};
    for (const field of props.fields) {
        if (field.type === 'checkbox') data[field.name] = false;
        else data[field.name] = '';
    }
    data.password_confirmation = '';
    return data;
};

const form = useForm(initFormData());

const handleSubmit = () => {
    form.post(routePrefix, {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Create User - Dashboard" />

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
                        Back to Users
                    </Button>
                    <h1 class="text-3xl font-bold tracking-tight">
                        Create User
                    </h1>
                    <p class="mt-1 text-muted-foreground">
                        Add a new user account
                    </p>
                </div>
            </div>

            <!-- Form -->
            <form @submit.prevent="handleSubmit">
                <UserForm
                    :fields="fields"
                    :model-value="form.data()"
                    @update:model-value="Object.assign(form, $event)"
                    :errors="form.errors"
                    :is-edit="false"
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
                    <Button type="submit" :disabled="form.processing">
                        {{ form.processing ? 'Creating...' : 'Create User' }}
                    </Button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
